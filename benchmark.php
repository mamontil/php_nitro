<?php

require_once __DIR__ . '/php/NitroJson.php';

try {
    // Автоматическая загрузка DLL из target/release
    NitroJson::load();
} catch (Exception $e) {
    die($e->getMessage() . "\n");
}

$filePath = 'ultra_heavy.json';
$itemsCount = 5000000;

if (!file_exists($filePath)) {
    echo "--- Generating heavy JSON (~300MB). Please wait...\n";
    $f = fopen($filePath, 'w');
    fwrite($f, '{"metadata":{"version":1.0},"data":[');
    for ($i = 0; $i < $itemsCount; $i++) {
        $comma = ($i === $itemsCount - 1) ? "" : ",";
        fwrite($f, '{"id":'.$i.',"payload":"data_'.md5($i).'"}'.$comma);
    }
    fwrite($f, '],"footer":"FINAL_BINGO"}');
    fclose($f);
    echo "--- File ready!\n\n";
}

echo "=== NITRO VS PHP BENCHMARK ===\n";
echo "File size: " . round(filesize($filePath) / 1024 / 1024, 2) . " MB\n\n";

// 1. NITRO TEST
$s = microtime(true);
$memNitroStart = memory_get_peak_usage();
$resNitro = nitro_json_from_file($filePath, "footer");
$tNitro = microtime(true) - $s;
$memNitroTotal = memory_get_peak_usage() - $memNitroStart;

echo "[NitroJSON]\n";
echo "Result: $resNitro\n";
echo "Time:   " . round($tNitro, 4) . "s\n";
echo "RAM:    " . round($memNitroTotal / 1024 / 1024, 2) . " MB\n\n";

// 2. PHP TEST
echo "[Standard PHP]\n";
ini_set('memory_limit', '4G');
$s = microtime(true);
$memPhpStart = memory_get_peak_usage();

$content = file_get_contents($filePath);
$data = json_decode($content, true);
$resPhp = $data['footer'] ?? 'not found';

$tPhp = microtime(true) - $s;
$memPhpTotal = memory_get_peak_usage() - $memPhpStart;

echo "Result: $resPhp\n";
echo "Time:   " . round($tPhp, 4) . "s\n";
echo "RAM:    " . round($memPhpTotal / 1024 / 1024, 2) . " MB\n\n";

echo "=== FINAL VERDICT ===\n";
echo "Speed:  " . round($tPhp / $tNitro, 1) . "x faster\n";
echo "Memory: " . ($memNitroTotal === 0 ? "Infinite" : round($memPhpTotal / $memNitroTotal, 1) . "x") . " more efficient\n";