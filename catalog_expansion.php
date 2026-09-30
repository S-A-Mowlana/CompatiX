<?php
/**
 * Adds cache-backed popularity expansions without changing the library UI contract.
 */

function compatix_expansion_json(string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => 'CompatiX catalog hydrator/1.0',
    ]);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($status < 200 || $status >= 300 || !is_string($response)) return [];
    $data = json_decode($response, true);
    return is_array($data) ? $data : [];
}

function compatix_expansion_slug(string $value): string {
    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', '', $value);
    return trim((string)$value);
}

function compatix_canonicalize_games(array $games): array {
    $remove = [
        'Forza Horizon',
        'Grand Theft Auto V Enhanced',
        'Grand Theft Auto V Legacy',
        'Grand Theft Auto IV: The Complete Edition',
        'The Elder Scrolls V: Skyrim',
        'The Witcher 3: Wild Hunt',
        'Divinity: Original Sin 2',
        'Fall Guys: Ultimate Knockout',
        'Age of Empires II (Retired)',
        'Sea of Thieves: 2026 Edition',
        'Sekiro: Shadows Die Twice - GOTY Edition',
        'Z1 Battle Royale: Test Server',
        'Tomb Raider',
        'Sid Meier’s Civilization VI',
    ];
    $remove_keys = array_fill_keys(array_map('compatix_normalize_title', $remove), true);
    $filtered = [];
    $known = [];
    foreach ($games as $game) {
        $name = trim((string)($game['name'] ?? ''));
        $key = compatix_normalize_title($name);
        if ($key === '' || isset($remove_keys[$key]) || isset($known[$key])) continue;
        $known[$key] = true;
        $filtered[] = $game;
    }

    if (!isset($known['forza horizon 3'])) {
        $next_id = 1;
        foreach ($filtered as $game) $next_id = max($next_id, (int)($game['id'] ?? 0) + 1);
        $forza_cover = 'https://media.rawg.io/media/games/30a/30afe3a3cb06849dc032ef3b9295f180.jpg';
        $filtered[] = [
            'id' => (string)$next_id,
            'name' => 'Forza Horizon 3',
            'genre' => 'Racing',
            'full_description' => 'Forza Horizon 3 brings an open-world festival to Australia with hundreds of cars, dynamic events, and broad PC racing support.',
            'short_description' => 'Open-world racing across a vibrant Australian festival.',
            'tagline' => 'Open-world racing, built for the Horizon festival.',
            'min_cpu' => 'Intel i5-3570',
            'min_gpu' => 'GTX 750 Ti',
            'min_ram' => 8,
            'min_storage' => 60,
            'min_gpu_tier' => 'dedicated',
            'supported_os' => 'Windows',
            'rec_cpu' => 'Intel i7-3820',
            'rec_gpu' => 'GTX 970',
            'rec_ram' => 12,
            'rec_storage' => 60,
            'image_url' => $forza_cover,
            'cover_position' => 'center top',
            'rating' => 4.1,
            'released' => '2016-09-27',
            'version_info' => '2016',
            'platforms' => ['Windows'],
            'genres' => ['Racing', 'Arcade'],
            'tags' => ['Racing', 'Open World', 'Cars'],
            'short_screenshots' => [$forza_cover],
            'performance_tier' => 'Balanced'
        ];
    }
    return $filtered;
}

function compatix_expand_games(array $games): array {
    if (count($games) >= 700) return $games;

    $known = [];
    foreach ($games as $game) $known[compatix_normalize_title((string)($game['name'] ?? ''))] = true;
    $blocked = array_fill_keys(array_map('compatix_normalize_title', [
        'Forza Horizon',
        'Grand Theft Auto V Enhanced',
        'Grand Theft Auto V Legacy',
        'Grand Theft Auto IV: The Complete Edition',
        'The Elder Scrolls V: Skyrim',
        'The Witcher 3: Wild Hunt',
        'Divinity: Original Sin 2',
        'Fall Guys: Ultimate Knockout',
        'Age of Empires II (Retired)',
        'Sea of Thieves: 2026 Edition',
        'Sekiro: Shadows Die Twice - GOTY Edition',
        'Z1 Battle Royale: Test Server',
        'Tomb Raider',
        'Sid Meier’s Civilization VI',
    ]), true);
    $popular = [];
    foreach (['top100in2weeks', 'top100owned', 'top100forever', 'top100current'] as $feed) {
        $payload = compatix_expansion_json('https://steamspy.com/api.php?request=' . $feed);
        foreach ($payload as $row) {
            if (!is_array($row) || empty($row['appid']) || empty($row['name'])) continue;
            $key = compatix_normalize_title((string)$row['name']);
            if ($key === '' || isset($known[$key]) || isset($blocked[$key]) || isset($popular[$key])) continue;
            $popular[$key] = $row;
            if (count($popular) >= 400) break 2;
        }
    }
    if (count($popular) < 300) {
        $payload = compatix_expansion_json('https://steamspy.com/api.php?request=all');
        foreach ($payload as $row) {
            if (!is_array($row) || empty($row['appid']) || empty($row['name'])) continue;
            $key = compatix_normalize_title((string)$row['name']);
            if ($key === '' || isset($known[$key]) || isset($blocked[$key]) || isset($popular[$key])) continue;
            $popular[$key] = $row;
            if (count($popular) >= 400) break;
        }
    }

    $next_id = count($games) + 1;
    foreach (array_values($popular) as $row) {
        if (count($games) >= 700) break;
        $appid = (int)$row['appid'];
        $name = trim((string)$row['name']);
        $genre = trim((string)($row['genre'] ?? 'Action'));
        $genre = $genre !== '' ? explode(',', $genre)[0] : 'Action';
        $cover = 'https://cdn.cloudflare.steamstatic.com/steam/apps/' . $appid . '/header.jpg';
        $screenshots = [];
        for ($shot = 1; $shot <= 6; $shot++) {
            $screenshots[] = 'https://cdn.cloudflare.steamstatic.com/steam/apps/' . $appid . '/ss_' . $shot . '.jpg';
        }
        $games[] = [
            'id' => (string)$next_id++,
            'name' => $name,
            'genre' => $genre,
            'full_description' => 'Popular Steam title in the ' . $genre . ' category with a large active player community.',
            'short_description' => 'Popular ' . $genre . ' title with broad PC compatibility appeal.',
            'tagline' => 'Popular ' . $genre . ' title for PC players.',
            'min_cpu' => 'Intel i5-8400',
            'min_gpu' => 'GTX 1050',
            'min_ram' => 8,
            'min_storage' => 20,
            'min_gpu_tier' => 'dedicated',
            'supported_os' => 'Windows',
            'rec_cpu' => 'Intel i7-10700K',
            'rec_gpu' => 'RTX 2060',
            'rec_ram' => 16,
            'rec_storage' => 20,
            'image_url' => $cover,
            'cover_position' => 'center top',
            'rating' => (!empty($row['positive']) || !empty($row['negative'])) ? round(((int)$row['positive'] / max(1, (int)$row['positive'] + (int)$row['negative'])) * 5, 1) : null,
            'released' => null,
            'version_info' => '2020',
            'platforms' => ['Windows'],
            'genres' => [$genre],
            'tags' => ['Steam', 'Popular', $genre],
            'short_screenshots' => $screenshots,
            'performance_tier' => 'Balanced'
        ];
    }
    return $games;
}

function compatix_expand_apps(array $apps): array {
    if (count($apps) >= 700) return array_slice($apps, 0, 700);
    $known = [];
    foreach ($apps as $app) $known[compatix_normalize_title((string)($app['name'] ?? ''))] = true;
    $icons = compatix_expansion_json('https://cdn.jsdelivr.net/npm/simple-icons@v11/_data/simple-icons.json');
    $icons = is_array($icons['icons'] ?? null) ? $icons['icons'] : $icons;
    $added = 0;
    foreach ($icons as $icon) {
        if ($added >= 400 || count($apps) >= 700) break;
        if (!is_array($icon)) continue;
        $name = trim((string)($icon['title'] ?? ''));
        $slug = trim((string)($icon['slug'] ?? '')) ?: compatix_expansion_slug($name);
        $key = compatix_normalize_title($name);
        if ($name === '' || $slug === '' || isset($known[$key])) continue;
        $category = 'Utilities';
        $lower = strtolower($name);
        if (preg_match('/code|studio|developer|git|database|cloud|engine|api|linux|python|java|node|docker/', $lower)) $category = 'Development';
        elseif (preg_match('/photo|design|figma|adobe|paint|draw|camera|art|font/', $lower)) $category = 'Design & Creative';
        elseif (preg_match('/video|film|stream|media|youtube|twitch/', $lower)) $category = 'Video Editing';
        elseif (preg_match('/music|audio|sound|spotify|radio/', $lower)) $category = 'Music Production';
        elseif (preg_match('/office|calendar|task|chat|mail|slack|zoom|notion|trello/', $lower)) $category = 'Productivity';
        $description = $name . ' is a popular desktop and web tool used across modern digital workflows.';
        $apps[] = [
            'id' => (string)(count($apps) + 1),
            'name' => $name,
            'category' => $category,
            'version_info' => '2024 Edition',
            'full_description' => $description,
            'min_cpu' => 'Intel i3-6100',
            'min_gpu' => 'Integrated',
            'min_ram' => 4,
            'min_storage' => 2,
            'supported_os' => 'Windows, macOS, Linux',
            'image_url' => 'https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/' . rawurlencode($slug) . '.svg',
            'rec_cpu' => 'Intel i5-8400',
            'rec_gpu' => 'Integrated',
            'rec_ram' => 8,
            'rec_storage' => 4,
            'screenshots' => compatix_app_screenshots($name),
            'short_description' => compatix_make_short_description($description),
            'tagline' => compatix_make_tagline($description),
            'performance_tier' => 'Lightweight'
        ];
        $known[$key] = true;
        $added++;
    }
    return $apps;
}

function compatix_canonicalize_apps(array $apps): array {
    $unique = [];
    $seen = [];
    foreach ($apps as $app) {
        $name = trim((string)($app['name'] ?? ''));
        $key = compatix_normalize_title($name);
        if ($key === '' || isset($seen[$key])) continue;
        $seen[$key] = true;
        $unique[] = $app;
        if (count($unique) >= 700) break;
    }
    return $unique;
}
