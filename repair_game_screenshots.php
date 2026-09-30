<?php
/**
 * One-time repair for cached Steam screenshots.
 * Steam screenshot URLs contain asset hashes and cannot be guessed from ss_1.jpg.
 */
set_time_limit(600);
require_once __DIR__ . '/config.php';

$cache_file = __DIR__ . '/games_cache.json';
$cache = is_file($cache_file) ? json_decode((string)file_get_contents($cache_file), true) : [];
$games = is_array($cache['data'] ?? null) ? $cache['data'] : [];
$repaired = 0;
$failed = 0;
$pending = [];
$unresolved = [];

foreach ($games as $index => $game) {
    $image_url = (string)($game['image_url'] ?? '');
    if (!preg_match('~/apps/(\d+)/~', $image_url, $matches)) continue;
    $appid = (int)$matches[1];
    $screenshots = $game['short_screenshots'] ?? [];
    $needs_repair = empty($screenshots) || preg_match('~/ss_\d+\.jpg(?:$|\?)~', (string)($screenshots[0] ?? ''));
    if (!$needs_repair) continue;

    $pending[$index] = $appid;
}

foreach (array_chunk($pending, 25, true) as $batch) {
    $multi = curl_multi_init();
    $handles = [];
    foreach ($batch as $index => $appid) {
        $url = 'https://store.steampowered.com/api/appdetails?appids=' . $appid . '&l=en';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'CompatiX screenshot repair/1.0',
        ]);
        curl_multi_add_handle($multi, $ch);
        $handles[(int)$ch] = [$ch, $index, $appid];
    }

    do {
        $status = curl_multi_exec($multi, $running);
        if ($running) curl_multi_select($multi, 1.0);
    } while ($running && $status === CURLM_OK);

    foreach ($handles as [$ch, $index, $appid]) {
        $response = curl_multi_getcontent($ch);
        $payload = is_string($response) ? json_decode($response, true) : null;
        $official = $payload[(string)$appid]['data']['screenshots'] ?? [];
        $official_urls = array_values(array_filter(array_map(static fn($shot): string => (string)($shot['path_full'] ?? ''), is_array($official) ? $official : [])));
        if ($official_urls) {
            $games[$index]['short_screenshots'] = array_slice($official_urls, 0, 7);
            $repaired++;
        } else {
            $failed++;
            $unresolved[$index] = (string)($games[$index]['name'] ?? '');
        }
        curl_multi_remove_handle($multi, $ch);
        curl_close($ch);
    }
    curl_multi_close($multi);
}

foreach ($unresolved as $index => $name) {
    $url = RAWG_API_URL . '?search=' . rawurlencode($name) . '&page_size=5&key=' . rawurlencode(RAWG_API_KEY);
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_FOLLOWLOCATION => true, CURLOPT_SSL_VERIFYPEER => true]);
    $response = curl_exec($ch);
    curl_close($ch);
    $payload = is_string($response) ? json_decode($response, true) : null;
    $candidate = is_array($payload['results'] ?? null) ? ($payload['results'][0] ?? []) : [];
    $rawg_urls = array_values(array_filter(array_map(static fn($shot): string => (string)($shot['image'] ?? ''), is_array($candidate['short_screenshots'] ?? null) ? $candidate['short_screenshots'] : [])));
    if ($rawg_urls) {
        $games[$index]['short_screenshots'] = array_slice($rawg_urls, 0, 7);
        $repaired++;
        $failed--;
    }
}

file_put_contents($cache_file, json_encode(['timestamp' => time(), 'data' => $games], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
echo "Repaired: {$repaired}\nUnresolved: {$failed}\nTotal games: " . count($games) . "\n";
