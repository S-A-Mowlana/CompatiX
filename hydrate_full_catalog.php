<?php
set_time_limit(0);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/curated_catalog.php';
require_once __DIR__ . '/hydrate_all_catalog.php';

$curated_titles = compatix_curated_game_titles();
echo "Hydrating full catalog of " . count($curated_titles) . " titles...\n";

$full_catalog = [];

foreach ($curated_titles as $idx => $name) {
    $id = (string)($idx + 1);
    $genre = compatix_curated_game_genre($name);
    
    // Default/fallback values
    $game = [
        'id' => $id,
        'name' => $name,
        'genre' => $genre,
        'genres' => cx_dedupe_strings([$genre]),
        'tags' => cx_dedupe_strings([$genre]),
        'supported_os' => 'Windows 10 64-bit',
        'platforms' => ['Windows'],
        'image_url' => cx_game_cover($name, ''),
        'short_screenshots' => [],
        'released' => null,
        'rating' => null,
        'performance_tier' => 'Balanced',
        'requirements_known' => true,
    ];

    // Query Steam Store Search & App Details
    $searchJson = cx_http_get('https://store.steampowered.com/api/storesearch/?term=' . rawurlencode($name) . '&cc=us&l=en');
    $searchData = json_decode($searchJson, true);
    $appid = $searchData['items'][0]['id'] ?? null;

    $got_steam = false;
    if ($appid) {
        $detailsJson = cx_http_get("https://store.steampowered.com/api/appdetails?appids=$appid&cc=us&l=en");
        $detailsData = json_decode($detailsJson, true);
        $appKey = array_key_first($detailsData ?: []);
        $app = $detailsData[$appKey]['data'] ?? null;

        if ($app) {
            $got_steam = true;
            $desc = trim(preg_replace('/\s+/', ' ', strip_tags($app['detailed_description'] ?? $app['short_description'] ?? '')));
            $sentences = preg_split('/(?<=[.!?])\s+/', $desc);
            $clean_desc = implode(' ', array_slice(array_filter($sentences, fn($s) => strlen(trim($s)) > 15), 0, 7));

            if (strlen($clean_desc) >= 100 && cx_count_sentences($clean_desc) >= 4) {
                $game['full_description'] = $clean_desc;
                $game['short_description'] = $sentences[0] ?? $clean_desc;
                $game['tagline'] = $game['short_description'];
            }

            if (!empty($app['screenshots'])) {
                $shots = array_slice(array_map(fn($s) => (string)($s['path_full'] ?? ''), $app['screenshots']), 0, 7);
                $game['short_screenshots'] = array_values(array_filter($shots));
            }

            if (!empty($app['pc_requirements'])) {
                $minParsed = cx_parse_steam_requirements((string)($app['pc_requirements']['minimum'] ?? ''), 'min');
                $recParsed = cx_parse_steam_requirements((string)($app['pc_requirements']['recommended'] ?? ''), 'rec');

                if (!empty($minParsed['min_cpu']) && !empty($minParsed['min_ram'])) {
                    $game['min_cpu'] = $minParsed['min_cpu'];
                    $game['min_gpu'] = $minParsed['min_gpu'] ?? 'DirectX 11 compatible GPU';
                    $game['min_ram'] = $minParsed['min_ram'];
                    $game['min_storage'] = $minParsed['min_storage'] ?? 20;
                    $game['supported_os'] = $minParsed['min_os'] ?? 'Windows 10 64-bit';

                    $game['rec_cpu'] = $recParsed['rec_cpu'] ?? $game['min_cpu'];
                    $game['rec_gpu'] = $recParsed['rec_gpu'] ?? $game['min_gpu'];
                    $game['rec_ram'] = $recParsed['rec_ram'] ?? max(12, $game['min_ram'] + 4);
                    $game['rec_storage'] = $recParsed['rec_storage'] ?? $game['min_storage'];

                    $game['ai_estimated'] = false;
                    $game['source'] = 'RAWG / Steam';
                }
            }

            $game['image_url'] = "https://cdn.cloudflare.steamstatic.com/steam/apps/$appid/header.jpg";
        }
    }

    // Fallback description if missing or insufficient
    if (empty($game['full_description']) || cx_is_generic_description($game['full_description']) || cx_count_sentences($game['full_description']) < 4) {
        $game['full_description'] = cx_generate_ai_fallback_description($name, $genre, null);
        $game['short_description'] = preg_split('/(?<=[.!?])\s+/', $game['full_description'])[0];
        $game['tagline'] = $game['short_description'];
    }

    // Fallback requirements if missing
    if (empty($game['min_cpu'])) {
        $est = cx_generate_ai_fallback_requirements($genre, null);
        $game['min_cpu'] = $est['min_cpu'];
        $game['min_gpu'] = $est['min_gpu'];
        $game['min_ram'] = $est['min_ram'];
        $game['min_storage'] = $est['min_storage'];
        $game['supported_os'] = $est['supported_os'];
        $game['rec_cpu'] = $est['rec_cpu'];
        $game['rec_gpu'] = $est['rec_gpu'];
        $game['rec_ram'] = $est['rec_ram'];
        $game['rec_storage'] = $est['rec_storage'];
        $game['ai_estimated'] = true;
        $game['source'] = 'Specter AI';
    }

    $full_catalog[] = $game;
    echo "[$idx/" . count($curated_titles) . "] " . $name . " - Desc: " . cx_count_sentences($game['full_description']) . " sents, Shots: " . count($game['short_screenshots']) . ", Req: " . ($game['ai_estimated'] ? 'Estimated' : 'Real') . "\n";
    usleep(30000);
}

$cache_file = __DIR__ . '/games_cache.json';
$payload = [
    'timestamp' => time(),
    'catalog_version' => 8,
    'data' => $full_catalog
];

file_put_contents($cache_file, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
echo "\nSuccessfully wrote all " . count($full_catalog) . " hydrated games to $cache_file!\n";
