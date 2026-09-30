<?php
// fix_cover_images.php
// Run with: php fix_cover_images.php
function updateCache(string $filePath, string $imageKey, string $screenshotKey) {
    $raw = file_get_contents($filePath);
    $data = json_decode($raw, true);
    $updated = 0;
    foreach ($data['data'] as &$item) {
        $placeholder = preg_match('/placeholder(-game)?\.png/', $item[$imageKey] ?? '');
        $hasScreenshots = is_array($item[$screenshotKey] ?? null) && count($item[$screenshotKey]) > 0;
        if ($placeholder && $hasScreenshots) {
            $item[$imageKey] = $item[$screenshotKey][0];
            $updated++;
        }
    }
    file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "✓ Updated $updated entries in " . basename($filePath) . "\n";
}

// Apps
updateCache(__DIR__ . '/apps_cache.json', 'image_url', 'screenshots');
// Games
updateCache(__DIR__ . '/games_cache.json', 'image_url', 'short_screenshots');
?>
