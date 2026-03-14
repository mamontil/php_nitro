<?php

declare(strict_types=1);

namespace Nitro;

use FFI;
use Exception;
use RuntimeException;

/**
 * NitroJson - High-performance JSON extractor powered by Rust.
 */
final class NitroJson
{
    private static ?FFI $ffi = null;

    /**
     * Загрузка библиотеки с проверкой окружения и ОС.
     */
    public static function load(?string $libPath = null): void
    {
        if (self::$ffi !== null) {
            return;
        }

        if (!extension_loaded('ffi')) {
            throw new RuntimeException("NitroJson Error: FFI extension is not loaded.");
        }

        $ffiRestrict = ini_get('ffi.enable');
        if ($ffiRestrict === '0' || (PHP_SAPI !== 'cli' && $ffiRestrict !== '1')) {
            throw new RuntimeException("NitroJson Error: FFI is disabled or restricted (ffi.enable=" . $ffiRestrict . ").");
        }

        if ($libPath === null) {
            // На Windows Cargo обычно не добавляет префикс 'lib'
            $prefix = PHP_OS_FAMILY === 'Windows' ? '' : 'lib';
            $extension = match (PHP_OS_FAMILY) {
                'Windows' => '.dll',
                'Darwin'  => '.dylib',
                default   => '.so',
            };
            $libPath = dirname(__DIR__) . "/target/release/{$prefix}nitro_core" . $extension;
        }

        if (!is_file($libPath)) {
            throw new RuntimeException("NitroJson Error: Library not found at: $libPath. Build it with 'cargo build --release'.");
        }

        try {
            self::$ffi = FFI::cdef("
                char *nitro_get_field(const char *json, const char *key);
                char *nitro_json_from_file(const char *path, const char *key);
                void nitro_free_string(char *s);
            ", $libPath);
        } catch (Exception $e) {
            throw new RuntimeException("NitroJson Error: Failed to load C definitions: " . $e->getMessage());
        }
    }

    public static function getField(string $json, string $key): ?string
    {
        $ffi = self::getFFI();
        $ptr = $ffi->nitro_get_field($json, $key);
        return $ptr === null ? null : self::extractAndFree($ptr);
    }

    public static function fromFile(string $path, string $key): ?string
    {
        if (!is_file($path)) {
            return null;
        }

        $ffi = self::getFFI();
        $ptr = $ffi->nitro_json_from_file($path, $key);
        return $ptr === null ? null : self::extractAndFree($ptr);
    }

    private static function getFFI(): FFI
    {
        if (self::$ffi === null) {
            self::load();
        }
        return self::$ffi;
    }

    private static function extractAndFree(FFI\CData $ptr): string
    {
        $res = FFI::string($ptr);
        self::$ffi->nitro_free_string($ptr);
        return $res;
    }
}