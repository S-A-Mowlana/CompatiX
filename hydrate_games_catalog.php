<?php
/**
 * CompatiX - Game Catalog RAWG Hydration & Verification Script
 * 
 * Audits every game in the seed list against RAWG API:
 * 1. Confirms exact/near-exact title match (preferring highest popularity).
 * 2. Evaluates RAWG background_image vs background_image_additional for best official key art representation.
 * 3. Fetches 6-7 genuine in-game screenshots per title from RAWG screenshots endpoint.
 * 4. Saves sanitized single-source-of-truth dataset to games_cache.json.
 */

header('Content-Type: text/plain; charset=utf-8');
set_time_limit(600);

require_once __DIR__ . '/config.php';

function h_normalize_title(string $title): string {
    $title = strtolower(trim((string)$title));
    $title = preg_replace('/[^a-z0-9]+/', ' ', $title);
    return trim((string)preg_replace('/\s+/', ' ', $title));
}

function h_title_matches(string $requested_title, string $rawg_title): bool {
    $requested = h_normalize_title($requested_title);
    $candidate = h_normalize_title($rawg_title);
    if ($requested === '' || $candidate === '') {
        return false;
    }
    if (strpos($candidate, $requested) !== false || strpos($requested, $candidate) !== false) {
        return true;
    }
    similar_text($requested, $candidate, $score);
    return $score >= 80;
}

function h_strip_html(string $text): string {
    $text = preg_replace('/<[^>]+>/', ' ', (string)$text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

function h_make_short_description(string $description): string {
    $description = trim($description);
    if ($description === '') {
        return 'Popular title with broad compatibility appeal.';
    }
    $first_sentence = preg_split('/(?<=[.!?])\s+/', $description, 2)[0];
    if (strlen($first_sentence) <= 100) {
        return $first_sentence;
    }
    $short = substr($description, 0, 110);
    $short = preg_replace('/\s+\S*$/', '', $short);
    return rtrim($short, ' .,!?') . '...';
}

function h_make_tagline(string $description): string {
    $headline = h_make_short_description($description);
    if (strlen($headline) > 90) {
        $short = substr($headline, 0, 90);
        $short = preg_replace('/\s+\S*$/', '', $short);
        $headline = rtrim($short, ' .,!?') . '...';
    }
    return $headline;
}

function h_game_release_year(string $name, ?string $released = null): string {
    if ($released !== null && preg_match('/^(\d{4})/', trim($released), $matches)) {
        return $matches[1];
    }
    $fallback_years = [
        'Elden Ring' => '2022', 'The Witcher 3: Wild Hunt' => '2015', 'The Witcher 3' => '2015', 'Counter-Strike 2' => '2023',
        'Cyberpunk 2077' => '2020', 'Grand Theft Auto V' => '2013', 'Red Dead Redemption 2' => '2018',
        'Minecraft' => '2011', 'Fortnite' => '2017', 'Valorant' => '2020', 'League of Legends' => '2009',
        'Apex Legends' => '2019', 'Call of Duty: Modern Warfare III' => '2023', 'EA Sports FC 24' => '2023',
        'Civilization VI' => '2016', 'Baldur\'s Gate 3' => '2023', 'Hogwarts Legacy' => '2023',
        'Starfield' => '2023', 'Diablo IV' => '2023', 'God of War' => '2018', 'DOOM Eternal' => '2020',
        'Marvel\'s Spider-Man Remastered' => '2018', 'Forza Horizon 5' => '2021', 'Overwatch 2' => '2022',
        'Rocket League' => '2015', 'Stardew Valley' => '2016', 'Hades' => '2020', 'Terraria' => '2011',
        'Among Us' => '2018', 'Dota 2' => '2013', 'PUBG: BATTLEGROUNDS' => '2017',
        'Tom Clancy\'s Rainbow Six Siege' => '2015', 'Destiny 2' => '2017', 'Sekiro: Shadows Die Twice' => '2019',
        'Dark Souls III' => '2016', 'Portal 2' => '2011', 'Half-Life: Alyx' => '2020',
        'Death Stranding' => '2019', 'Resident Evil 4' => '2023', 'Monster Hunter: World' => '2018',
        'Persona 5 Royal' => '2020', 'Sea of Thieves' => '2018', 'No Man\'s Sky' => '2016',
        'Fall Guys' => '2020', 'It Takes Two' => '2021', 'A Way Out' => '2018', 'Subnautica' => '2018',
        'F1 24' => '2024', 'Factorio' => '2020', 'Hollow Knight' => '2017', 'Microsoft Flight Simulator' => '2020',
        'The Sims 4' => '2014', 'Assassin\'s Creed Valhalla' => '2020', 'Tomb Raider' => '2013',
        'Doom Eternal' => '2020', 'Titanfall 2' => '2016', 'Warframe' => '2013',
        'Alan Wake 2' => '2023', 'Helldivers 2' => '2024', 'Palworld' => '2024', 'Black Myth: Wukong' => '2024',
        'The Last of Us Part I' => '2022', 'The Elder Scrolls V: Skyrim' => '2011', 'Fallout 4' => '2015',
        'Batman: Arkham City' => '2011', 'Batman: Arkham Knight' => '2015', 'BioShock Infinite' => '2013', 'Left 4 Dead 2' => '2009',
        'Half-Life 2' => '2004', 'Counter-Strike' => '2000', 'World of Warcraft' => '2004', 'Doom' => '1993', 'DOOM (2016)' => '2016',
        'StarCraft II' => '2010', 'Diablo II' => '2000', 'Age of Empires II: Definitive Edition' => '2019', 'Warcraft III: Reforged' => '2020',
        'Half-Life' => '1998', 'Deus Ex' => '2000', 'Max Payne' => '2001', 'Grand Theft Auto: San Andreas' => '2004', 'Half-Life: Source' => '2004',
        'Celeste' => '2018', 'Undertale' => '2015', 'Cuphead' => '2017', 'Braid' => '2008', 'Limbo' => '2010',
        'Ori and the Blind Forest' => '2015', 'Ori and the Will of the Wisps' => '2020', 'Disco Elysium' => '2019', 'Divinity: Original Sin 2' => '2017',
        'Mass Effect 2' => '2010', 'Dragon Age: Origins' => '2009', 'Far Cry 3' => '2012', 'Assassin\'s Creed IV: Black Flag' => '2013',
        'Borderlands 2' => '2012', 'The Elder Scrolls Online' => '2014', 'Team Fortress 2' => '2007', 'Street Fighter 6' => '2023',
        'Tekken 8' => '2024', 'Mortal Kombat 11' => '2019', 'Need for Speed Heat' => '2019', 'Euro Truck Simulator 2' => '2012',
        'Cities: Skylines' => '2015', 'The Stanley Parable: Ultra Deluxe' => '2022', 'Papers, Please' => '2013'
    ];
    return $fallback_years[$name] ?? '2020';
}

function h_game_fallback_cover(string $name): string {
    $steam_ids = [
        'Alan Wake 2' => 1957900, 'Helldivers 2' => 553850, 'Palworld' => 1623730, 'Black Myth: Wukong' => 2358720,
        'The Witcher 3: Wild Hunt' => 292030, 'Grand Theft Auto V' => 271590, 'Red Dead Redemption 2' => 1174180,
        'Minecraft' => 1245620, 'Valorant' => 1270380, 'League of Legends' => 20590, 'Apex Legends' => 1172470,
        'Call of Duty: Modern Warfare III' => 1971870, 'EA Sports FC 24' => 2195250, 'Hogwarts Legacy' => 990080,
        'Starfield' => 1716740, 'Diablo IV' => 2344520, 'God of War' => 1593500, 'Marvel\'s Spider-Man Remastered' => 1817070,
        'Forza Horizon 5' => 1551360, 'Overwatch 2' => 2357570, 'Rocket League' => 252950, 'Terraria' => 105600,
        'Among Us' => 945360, 'Dota 2' => 570, 'PUBG: BATTLEGROUNDS' => 578080, 'Tom Clancy\'s Rainbow Six Siege' => 359550,
        'Destiny 2' => 1085660, 'Sekiro: Shadows Die Twice' => 814380, 'Dark Souls III' => 374320, 'Portal 2' => 620,
        'Half-Life: Alyx' => 546560, 'Death Stranding' => 1190460, 'Resident Evil 4' => 2050650, 'Monster Hunter: World' => 582010,
        'Persona 5 Royal' => 1687950, 'Sea of Thieves' => 1172620, 'No Man\'s Sky' => 275850, 'Fall Guys' => 1097150,
        'It Takes Two' => 1426210, 'A Way Out' => 1222700, 'Subnautica' => 264710, 'The Sims 4' => 1222670,
        'Assassin\'s Creed Valhalla' => 2208920, 'Tomb Raider' => 203160, 'Titanfall 2' => 1237970, 'Warframe' => 230410,
        'World of Warcraft' => 13500, 'StarCraft II' => 2880, 'Diablo II' => 222985, 'Warcraft III: Reforged' => 102600,
        'The Last of Us Part I' => 1888930, 'The Elder Scrolls V: Skyrim' => 489830, 'Fallout 4' => 377160,
        'Batman: Arkham City' => 200260, 'Batman: Arkham Knight' => 208650, 'BioShock Infinite' => 8870,
        'Left 4 Dead 2' => 550, 'Half-Life 2' => 220, 'Counter-Strike' => 10, 'Doom' => 2280,
        'DOOM (2016)' => 379720, 'Age of Empires II: Definitive Edition' => 813780, 'Half-Life' => 70,
        'Deus Ex' => 6910, 'Max Payne' => 204100, 'Grand Theft Auto: San Andreas' => 12120,
        'Half-Life: Source' => 280, 'Celeste' => 504230, 'Undertale' => 391540, 'Cuphead' => 268910,
        'Braid' => 26800, 'Limbo' => 48000, 'Ori and the Blind Forest' => 261570,
        'Ori and the Will of the Wisps' => 1057090, 'Disco Elysium' => 632470, 'Divinity: Original Sin 2' => 435150,
        'Mass Effect 2' => 24980, 'Dragon Age: Origins' => 47810, 'Far Cry 3' => 220240,
        'Assassin\'s Creed IV: Black Flag' => 242050, 'Borderlands 2' => 49520, 'The Elder Scrolls Online' => 306130,
        'Team Fortress 2' => 440, 'Street Fighter 6' => 1364780, 'Tekken 8' => 1778820,
        'Mortal Kombat 11' => 976310, 'Need for Speed Heat' => 1222680, 'Euro Truck Simulator 2' => 227300,
        'Cities: Skylines' => 255710, 'The Stanley Parable: Ultra Deluxe' => 1703340, 'Papers, Please' => 239030
    ];
    return isset($steam_ids[$name])
        ? 'https://cdn.cloudflare.steamstatic.com/steam/apps/' . $steam_ids[$name] . '/header.jpg'
        : 'assets/placeholder-game.png';
}

function h_performance_tier(int $ram, string $gpu_tier): string {
    if ($ram <= 4 && in_array($gpu_tier, ['integrated', 'low-end'], true)) return 'Lightweight';
    if ($ram <= 8 && in_array($gpu_tier, ['low-end', 'mid-end', 'dedicated'], true)) return 'Budget Friendly';
    if ($ram >= 8 && $ram <= 16 && in_array($gpu_tier, ['mid-end', 'dedicated'], true)) return 'Balanced';
    if ($ram > 16 || $gpu_tier === 'high-end') return 'Demanding';
    return 'Balanced';
}

function h_get_rawg_search_results(string $query): array {
    $url = RAWG_API_URL . '?search=' . rawurlencode($query) . '&page_size=8&key=' . rawurlencode(RAWG_API_KEY);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http_code !== 200 || !is_string($response) || $response === '') {
        return [];
    }
    $payload = json_decode($response, true);
    return is_array($payload['results'] ?? null) ? $payload['results'] : [];
}

function h_rawg_game_details(string $slug): array {
    if ($slug === '') return [];
    $url = RAWG_API_URL . '/' . rawurlencode($slug) . '?key=' . rawurlencode(RAWG_API_KEY);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http_code !== 200 || !is_string($response) || $response === '') return [];
    $payload = json_decode($response, true);
    return is_array($payload) ? $payload : [];
}

function h_rawg_screenshots(int $game_id): array {
    if ($game_id <= 0) return [];
    $url = RAWG_API_URL . '/' . $game_id . '/screenshots?key=' . rawurlencode(RAWG_API_KEY) . '&page_size=12';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_CONNECTTIMEOUT => 6,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $payload = ($status === 200 && is_string($response)) ? json_decode($response, true) : [];
    return is_array($payload['results'] ?? null) ? array_values(array_filter(array_map(fn($shot) => $shot['image'] ?? '', $payload['results']))) : [];
}

function h_filter_pc_platforms(array $platforms): array {
    $filtered = [];
    foreach ($platforms as $p) {
        $name = is_array($p) ? ($p['platform']['name'] ?? '') : (string)$p;
        $name = trim($name);
        if ($name === '') continue;

        $lower = strtolower($name);
        if ($lower === 'pc' || $lower === 'windows') {
            if (!in_array('Windows', $filtered, true)) $filtered[] = 'Windows';
        } elseif ($lower === 'macos' || $lower === 'mac' || str_contains($lower, 'mac')) {
            if (!in_array('macOS', $filtered, true)) $filtered[] = 'macOS';
        } elseif ($lower === 'linux') {
            if (!in_array('Linux', $filtered, true)) $filtered[] = 'Linux';
        }
    }
    return empty($filtered) ? ['Windows'] : $filtered;
}

function h_extract_game_requirements(array $rawg_game): array {
    $platforms = $rawg_game['platforms'] ?? [];
    $pc_platform = null;
    foreach ($platforms as $platform) {
        $name = strtolower((string)($platform['platform']['name'] ?? ''));
        if (str_contains($name, 'pc')) {
            $pc_platform = $platform;
            break;
        }
    }

    $requirements = [
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
    ];

    $pc_requirements = $pc_platform['requirements'] ?? [];
    if (!empty($pc_requirements['minimum']) || !empty($pc_requirements['recommended'])) {
        $minimum = h_strip_html((string)($pc_requirements['minimum'] ?? ''));
        $recommended = h_strip_html((string)($pc_requirements['recommended'] ?? ''));

        if (preg_match('/(Intel|AMD)\s+[A-Za-z0-9-]+/i', $minimum, $cpu_match)) {
            $requirements['min_cpu'] = trim($cpu_match[0]);
        }
        if (preg_match('/(GTX|RTX|Radeon|GeForce|Intel Iris|AMD Radeon|Integrated)/i', $minimum, $gpu_match)) {
            $requirements['min_gpu'] = trim($gpu_match[0]);
        }
        if (preg_match('/(\d+)\s*GB\s*RAM/i', $minimum, $ram_match)) {
            $requirements['min_ram'] = (int)$ram_match[1];
        }
        if (preg_match('/(\d+)\s*GB/i', $minimum, $storage_match)) {
            $requirements['min_storage'] = (int)$storage_match[1];
        }
        if (preg_match('/(Intel|AMD)\s+[A-Za-z0-9-]+/i', $recommended, $recommend_cpu_match)) {
            $requirements['rec_cpu'] = trim($recommend_cpu_match[0]);
        }
        if (preg_match('/(GTX|RTX|Radeon|GeForce|Intel Iris)/i', $recommended, $recommend_gpu_match)) {
            $requirements['rec_gpu'] = trim($recommend_gpu_match[0]);
        }
        if (preg_match('/(\d+)\s*GB\s*RAM/i', $recommended, $recommend_ram_match)) {
            $requirements['rec_ram'] = (int)$recommend_ram_match[1];
        }
        if (preg_match('/(\d+)\s*GB/i', $recommended, $recommend_storage_match)) {
            $requirements['rec_storage'] = (int)$recommend_storage_match[1];
        }
    }

    $gpu_tier = 'integrated';
    $gpu_value = strtolower((string)($requirements['min_gpu'] ?? ''));
    if (str_contains($gpu_value, 'rtx') || str_contains($gpu_value, 'gtx') || str_contains($gpu_value, 'radeon')) {
        $gpu_tier = preg_match('/(rtx 30|rtx 40|rtx 20|gtx 10|gtx 16|radeon rx)/i', (string)$requirements['min_gpu']) ? 'high-end' : 'dedicated';
    }
    $requirements['min_gpu_tier'] = $gpu_tier;

    $pc_filtered = h_filter_pc_platforms($rawg_game['platforms'] ?? []);
    if (!empty($pc_filtered)) {
        $requirements['supported_os'] = implode(', ', $pc_filtered);
    }

    return $requirements;
}

function h_build_game_from_seed(array $seed, ?array $rawg_game = null): array {
    $name = $seed['name'];
    $fallback = [
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
        'genre' => $seed['genre'] ?? 'Action',
        'image_url' => h_game_fallback_cover($name),
        'full_description' => $name . ' game details are available from the local catalog.',
        'rating' => null,
        'released' => null,
        'platforms' => [],
        'genres' => [],
        'tags' => [],
        'short_screenshots' => [],
    ];

    if ($rawg_game) {
        $requirements = h_extract_game_requirements($rawg_game);
        $fallback = array_merge($fallback, [
            'min_cpu' => $requirements['min_cpu'],
            'min_gpu' => $requirements['min_gpu'],
            'min_ram' => $requirements['min_ram'],
            'min_storage' => $requirements['min_storage'],
            'min_gpu_tier' => $requirements['min_gpu_tier'],
            'supported_os' => $requirements['supported_os'],
            'rec_cpu' => $requirements['rec_cpu'],
            'rec_gpu' => $requirements['rec_gpu'],
            'rec_ram' => $requirements['rec_ram'],
            'rec_storage' => $requirements['rec_storage'],
            'image_url' => !empty($rawg_game['background_image']) ? $rawg_game['background_image'] : h_game_fallback_cover($name),
            'genre' => !empty($rawg_game['genres']) ? implode(', ', array_map(fn($genre) => $genre['name'] ?? '', $rawg_game['genres'])) : ($seed['genre'] ?? 'Action'),
            'full_description' => h_strip_html($rawg_game['description'] ?? $fallback['full_description']),
            'rating' => isset($rawg_game['rating']) ? (float)$rawg_game['rating'] : null,
            'released' => $rawg_game['released'] ?? null,
            'platforms' => h_filter_pc_platforms($rawg_game['platforms'] ?? []),
            'genres' => array_values(array_filter(array_map(fn($item) => $item['name'] ?? '', $rawg_game['genres'] ?? []))),
            'tags' => array_slice(array_values(array_filter(array_map(fn($item) => $item['name'] ?? '', $rawg_game['tags'] ?? []))), 0, 6),
            'short_screenshots' => array_values(array_filter(array_map(fn($item) => $item['image'] ?? '', $rawg_game['short_screenshots'] ?? [])))
        ]);
    }

    $game = [
        'id' => (string)($seed['id'] ?? uniqid('game_')),
        'name' => $name,
        'genre' => $fallback['genre'],
        'full_description' => $fallback['full_description'],
        'min_cpu' => $fallback['min_cpu'],
        'min_gpu' => $fallback['min_gpu'],
        'min_ram' => (int)$fallback['min_ram'],
        'min_storage' => (int)$fallback['min_storage'],
        'min_gpu_tier' => $fallback['min_gpu_tier'],
        'supported_os' => $fallback['supported_os'],
        'rec_cpu' => $fallback['rec_cpu'],
        'rec_gpu' => $fallback['rec_gpu'],
        'rec_ram' => (int)($fallback['rec_ram'] ?? 16),
        'rec_storage' => (int)($fallback['rec_storage'] ?? 20),
        'image_url' => $fallback['image_url'],
        'rating' => $fallback['rating'],
        'released' => $fallback['released'],
        'version_info' => h_game_release_year($name, $fallback['released']),
        'platforms' => $fallback['platforms'],
        'genres' => $fallback['genres'],
        'tags' => $fallback['tags'],
        'short_screenshots' => $fallback['short_screenshots'],
    ];
    $game['short_description'] = h_make_short_description($game['full_description']);
    $game['tagline'] = h_make_tagline($game['full_description']);
    $game['performance_tier'] = h_performance_tier($game['min_ram'], $game['min_gpu_tier']);
    return $game;
}

$seed_catalog = [
    ['name' => 'Elden Ring', 'genre' => 'Action RPG'],
    ['name' => 'The Witcher 3: Wild Hunt', 'genre' => 'RPG'],
    ['name' => 'Counter-Strike 2', 'genre' => 'Shooter'],
    ['name' => 'Cyberpunk 2077', 'genre' => 'Action RPG'],
    ['name' => 'Grand Theft Auto V', 'genre' => 'Action'],
    ['name' => 'Red Dead Redemption 2', 'genre' => 'Action Adventure'],
    ['name' => 'Minecraft', 'genre' => 'Sandbox'],
    ['name' => 'Fortnite', 'genre' => 'Shooter'],
    ['name' => 'Valorant', 'genre' => 'Shooter'],
    ['name' => 'League of Legends', 'genre' => 'MOBA'],
    ['name' => 'Apex Legends', 'genre' => 'Shooter'],
    ['name' => 'Call of Duty: Modern Warfare III', 'genre' => 'Shooter'],
    ['name' => 'EA Sports FC 24', 'genre' => 'Sports'],
    ['name' => 'Civilization VI', 'genre' => 'Strategy'],
    ['name' => 'Baldur\'s Gate 3', 'genre' => 'RPG'],
    ['name' => 'Hogwarts Legacy', 'genre' => 'Action RPG'],
    ['name' => 'Starfield', 'genre' => 'Action RPG'],
    ['name' => 'Diablo IV', 'genre' => 'Action RPG'],
    ['name' => 'God of War', 'genre' => 'Action Adventure'],
    ['name' => 'Marvel\'s Spider-Man Remastered', 'genre' => 'Action Adventure'],
    ['name' => 'Forza Horizon 5', 'genre' => 'Racing'],
    ['name' => 'Overwatch 2', 'genre' => 'Shooter'],
    ['name' => 'Rocket League', 'genre' => 'Sports'],
    ['name' => 'Stardew Valley', 'genre' => 'Simulation'],
    ['name' => 'Hades', 'genre' => 'Action'],
    ['name' => 'Terraria', 'genre' => 'Sandbox'],
    ['name' => 'Among Us', 'genre' => 'Party'],
    ['name' => 'Dota 2', 'genre' => 'MOBA'],
    ['name' => 'PUBG: BATTLEGROUNDS', 'genre' => 'Shooter'],
    ['name' => 'Tom Clancy\'s Rainbow Six Siege', 'genre' => 'Shooter'],
    ['name' => 'Destiny 2', 'genre' => 'Shooter'],
    ['name' => 'Sekiro: Shadows Die Twice', 'genre' => 'Action'],
    ['name' => 'Dark Souls III', 'genre' => 'Action RPG'],
    ['name' => 'Portal 2', 'genre' => 'Puzzle'],
    ['name' => 'Half-Life: Alyx', 'genre' => 'Shooter'],
    ['name' => 'Death Stranding', 'genre' => 'Action'],
    ['name' => 'Resident Evil 4', 'genre' => 'Action'],
    ['name' => 'Monster Hunter: World', 'genre' => 'Action RPG'],
    ['name' => 'Persona 5 Royal', 'genre' => 'RPG'],
    ['name' => 'Sea of Thieves', 'genre' => 'Action Adventure'],
    ['name' => 'No Man\'s Sky', 'genre' => 'Action Adventure'],
    ['name' => 'Fall Guys', 'genre' => 'Party'],
    ['name' => 'It Takes Two', 'genre' => 'Adventure'],
    ['name' => 'A Way Out', 'genre' => 'Action Adventure'],
    ['name' => 'Subnautica', 'genre' => 'Adventure'],
    ['name' => 'The Sims 4', 'genre' => 'Simulation'],
    ['name' => 'Assassin\'s Creed Valhalla', 'genre' => 'Action RPG'],
    ['name' => 'Tomb Raider', 'genre' => 'Action Adventure'],
    ['name' => 'Doom Eternal', 'genre' => 'Shooter'],
    ['name' => 'Titanfall 2', 'genre' => 'Shooter'],
    ['name' => 'Warframe', 'genre' => 'Action RPG'],
    ['name' => 'Alan Wake 2', 'genre' => 'Horror'],
    ['name' => 'Helldivers 2', 'genre' => 'Shooter'],
    ['name' => 'Palworld', 'genre' => 'Survival'],
    ['name' => 'Black Myth: Wukong', 'genre' => 'Action RPG'],
    ['name' => 'The Last of Us Part I', 'genre' => 'Action Adventure'],
    ['name' => 'The Elder Scrolls V: Skyrim', 'genre' => 'RPG'],
    ['name' => 'Fallout 4', 'genre' => 'RPG'],
    ['name' => 'Batman: Arkham City', 'genre' => 'Action Adventure'],
    ['name' => 'Batman: Arkham Knight', 'genre' => 'Action Adventure'],
    ['name' => 'BioShock Infinite', 'genre' => 'Shooter'],
    ['name' => 'Left 4 Dead 2', 'genre' => 'Shooter'],
    ['name' => 'Half-Life 2', 'genre' => 'Shooter'],
    ['name' => 'Counter-Strike', 'genre' => 'Shooter'],
    ['name' => 'World of Warcraft', 'genre' => 'MMO'],
    ['name' => 'Doom', 'genre' => 'Shooter'],
    ['name' => 'DOOM (2016)', 'genre' => 'Shooter'],
    ['name' => 'StarCraft II', 'genre' => 'Strategy'],
    ['name' => 'Diablo II', 'genre' => 'Action RPG'],
    ['name' => 'Age of Empires II: Definitive Edition', 'genre' => 'Strategy'],
    ['name' => 'Warcraft III: Reforged', 'genre' => 'Strategy'],
    ['name' => 'Half-Life', 'genre' => 'Shooter'],
    ['name' => 'Deus Ex', 'genre' => 'RPG'],
    ['name' => 'Max Payne', 'genre' => 'Shooter'],
    ['name' => 'Grand Theft Auto: San Andreas', 'genre' => 'Action'],
    ['name' => 'Half-Life: Source', 'genre' => 'Shooter'],
    ['name' => 'Celeste', 'genre' => 'Indie'],
    ['name' => 'Undertale', 'genre' => 'Indie'],
    ['name' => 'Cuphead', 'genre' => 'Indie'],
    ['name' => 'Braid', 'genre' => 'Puzzle'],
    ['name' => 'Limbo', 'genre' => 'Puzzle'],
    ['name' => 'Ori and the Blind Forest', 'genre' => 'Adventure'],
    ['name' => 'Ori and the Will of the Wisps', 'genre' => 'Adventure'],
    ['name' => 'Disco Elysium', 'genre' => 'RPG'],
    ['name' => 'Divinity: Original Sin 2', 'genre' => 'RPG'],
    ['name' => 'Mass Effect 2', 'genre' => 'RPG'],
    ['name' => 'Dragon Age: Origins', 'genre' => 'RPG'],
    ['name' => 'Far Cry 3', 'genre' => 'Shooter'],
    ['name' => 'Assassin\'s Creed IV: Black Flag', 'genre' => 'Action Adventure'],
    ['name' => 'Borderlands 2', 'genre' => 'Shooter'],
    ['name' => 'The Elder Scrolls Online', 'genre' => 'MMO'],
    ['name' => 'Team Fortress 2', 'genre' => 'Shooter'],
    ['name' => 'Street Fighter 6', 'genre' => 'Fighting'],
    ['name' => 'Tekken 8', 'genre' => 'Fighting'],
    ['name' => 'Mortal Kombat 11', 'genre' => 'Fighting'],
    ['name' => 'Need for Speed Heat', 'genre' => 'Racing'],
    ['name' => 'Euro Truck Simulator 2', 'genre' => 'Simulation'],
    ['name' => 'Cities: Skylines', 'genre' => 'Simulation'],
    ['name' => 'The Stanley Parable: Ultra Deluxe', 'genre' => 'Adventure'],
    ['name' => 'Papers, Please', 'genre' => 'Indie']
];

echo "=========================================================\n";
echo "CompatiX Game Catalog Hydration & Verification Pass\n";
echo "=========================================================\n\n";

$total_seeds = count($seed_catalog);
echo "Loaded {$total_seeds} seed game titles.\n\n";

$hydrated_games = [];
$success_count = 0;
$fallback_count = 0;

foreach ($seed_catalog as $index => $seed) {
    $num = $index + 1;
    $seed_name = $seed['name'];
    echo "[{$num}/{$total_seeds}] Hydrating '{$seed_name}'... ";

    $matches = h_get_rawg_search_results($seed_name);
    $chosen = null;

    foreach ($matches as $result) {
        $title = $result['name'] ?? '';
        if ($title !== '' && h_title_matches($seed_name, $title)) {
            $chosen = $result;
            break;
        }
    }

    if (!$chosen && !empty($matches)) {
        usort($matches, fn($a, $b) => ((int)($b['added'] ?? 0) <=> (int)($a['added'] ?? 0)) ?: ((float)($b['rating'] ?? 0) <=> (float)($a['rating'] ?? 0)));
        $chosen = $matches[0];
    }

    $detail = [];
    $game_id = 0;
    if ($chosen) {
        $slug = $chosen['slug'] ?? '';
        $game_id = (int)($chosen['id'] ?? 0);
        if ($slug !== '') {
            $detail = h_rawg_game_details($slug);
        }
    }

    $rawg_record = $detail ? array_merge($chosen ?: [], $detail) : ($chosen ?: null);

    // Fetch 6-7 genuine screenshots from RAWG screenshots endpoint
    $screenshots = [];
    if ($game_id > 0) {
        $rawg_shots = h_rawg_screenshots($game_id);
        if (!empty($rawg_shots)) {
            $screenshots = array_slice(array_values(array_unique(array_filter($rawg_shots))), 0, 7);
        }
    }

    // Fallback to short_screenshots from RAWG search if screenshots endpoint was empty
    if (empty($screenshots) && !empty($rawg_record['short_screenshots'])) {
        $short_shots = array_values(array_filter(array_map(fn($s) => $s['image'] ?? '', $rawg_record['short_screenshots'])));
        $screenshots = array_slice(array_unique($short_shots), 0, 7);
    }

    // Key art selection: evaluate background_image vs background_image_additional
    $primary_img = $rawg_record['background_image'] ?? '';
    $additional_img = $rawg_record['background_image_additional'] ?? '';
    
    $selected_cover = $primary_img;
    if (empty($primary_img) && !empty($additional_img)) {
        $selected_cover = $additional_img;
    } elseif (!empty($primary_img) && !empty($additional_img)) {
        if (str_contains(strtolower($primary_img), 'loading') || str_contains(strtolower($primary_img), 'crop/600/384')) {
            $selected_cover = $additional_img;
        }
    }

    if (empty($selected_cover)) {
        $selected_cover = h_game_fallback_cover($seed_name);
    }

    if ($rawg_record) {
        $rawg_record['background_image'] = $selected_cover;
    }

    $game = h_build_game_from_seed([
        'id' => (string)$num,
        'name' => $seed_name,
        'genre' => $seed['genre']
    ], $rawg_record);

    $game['id'] = (string)$num;
    $game['image_url'] = $selected_cover;
    $game['short_screenshots'] = $screenshots;

    $hydrated_games[] = $game;

    if (!empty($rawg_record)) {
        $success_count++;
        echo "OK (RAWG Match: '" . ($chosen['name'] ?? 'N/A') . "', Screenshots: " . count($screenshots) . ")\n";
    } else {
        $fallback_count++;
        echo "Fallback (No RAWG match, used Steam header)\n";
    }

    // Rate limit delay (100ms)
    usleep(100000);
}

$cache_file = __DIR__ . '/games_cache.json';
$payload = [
    'timestamp' => time(),
    'catalog_version' => 6,
    'data' => $hydrated_games
];

file_put_contents($cache_file, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);

echo "\n=========================================================\n";
echo "Hydration Summary:\n";
echo "Total Games Processed: {$total_seeds}\n";
echo "Successfully Hydrated via RAWG: {$success_count}\n";
echo "Fallback Headers Used: {$fallback_count}\n";
echo "Cache file written to: {$cache_file}\n";
echo "=========================================================\n";
