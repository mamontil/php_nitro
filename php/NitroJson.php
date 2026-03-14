<?php

final class NitroJson {
    private static ?FFI $ffi = null;

    /**
     * Загрузка библиотеки.
     * Если путь не указан, пытается найти в стандартных папках.
     */
    public static function load(?string $libPath = null): void {
        if (self::$ffi !== null) return;

        if ($libPath === null) {
            $extension = PHP_OS_FAMILY === 'Windows' ? '.dll' : '.so';
            $libPath = dirname(__DIR__) . "/target/release/nitro_core" . $extension;
        }

        if (!file_exists($libPath)) {
            throw new Exception("NitroJson Error: Library not found at $libPath. Did you run 'cargo build --release'?");
        }

        self::$ffi = FFI::cdef("
            char *nitro_get_field(const char *json, const char *key);
            char *nitro_json_from_file(const char *path, const char *key);
            void nitro_free_string(char *s);
        ", $libPath);
    }

    public static function getFFI(): FFI {
        if (self::$ffi === null) {
            throw new Exception("NitroJson Error: Library not initialized. Call NitroJson::load() first.");
        }
        return self::$ffi;
    }
}

/**
 * Извлечение значения из JSON-строки по ключу (поддерживает вложенность через точку)
 */
function nitro_get_field(string $json, string $key): ?string {
    $ptr = NitroJson::getFFI()->nitro_get_field($json, $key);
    if ($ptr === null) return null;
    $res = FFI::string($ptr);
    NitroJson::getFFI()->nitro_free_string($ptr);
    return $res;
}

/**
 * Извлечение значения напрямую из файла (Zero-copy mmap)
 */
function nitro_json_from_file(string $path, string $key): ?string {
    if (!file_exists($path)) return null;
    $ptr = NitroJson::getFFI()->nitro_json_from_file($path, $key);
    if ($ptr === null) return null;
    $res = FFI::string($ptr);
    NitroJson::getFFI()->nitro_free_string($ptr);
    return $res;
}