<?php
// One-time migration: retain only the two curated endpoint payloads in cache.
function cx_capture_endpoint(string $file): array {
    ob_start(); include __DIR__ . '/' . $file; $json = ob_get_clean();
    $items = json_decode((string)$json, true);
    if (!is_array($items)) throw new RuntimeException('Could not build ' . $file);
    return $items;
}
$games = cx_capture_endpoint('get_games_library.php');
$apps = cx_capture_endpoint('get_apps_library.php');
file_put_contents(__DIR__ . '/games_cache.json', json_encode(['catalog_version' => 250, 'timestamp' => time(), 'data' => $games], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
file_put_contents(__DIR__ . '/apps_cache.json', json_encode(['catalog_version' => 250, 'timestamp' => time(), 'data' => $apps], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
echo 'Pruned to ' . count($games) . ' games and ' . count($apps) . " apps.\n";
