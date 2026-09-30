<?php
/**
 * CompatiX - Get Game/App Requirements
 * Accepts optional GET parameter game and returns a JSON requirements array.
 * Fetches real game data from RAWG.io API
 * Falls back to estimated requirements if specific data unavailable
 */
header('Content-Type: application/json');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app_catalog.php';
require_once __DIR__ . '/curated_catalog.php';

// ===== GAME ALIASES / NICKNAMES MAPPING =====
// Maps common nicknames/abbreviations to official game titles
$game_aliases = [
    'cyberpunk' => 'Cyberpunk 2077',
    // Grand Theft Auto series
    'gta' => 'Grand Theft Auto V',
    'gta v' => 'Grand Theft Auto V',
    'gta 5' => 'Grand Theft Auto V',
    'gta5' => 'Grand Theft Auto V',
    'gtav' => 'Grand Theft Auto V',
    'gta iv' => 'Grand Theft Auto IV',
    'gta 4' => 'Grand Theft Auto IV',
    'gta4' => 'Grand Theft Auto IV',
    'gtaiv' => 'Grand Theft Auto IV',
    
    // The Elder Scrolls series
    'skyrim' => 'The Elder Scrolls V Skyrim',
    'tes v' => 'The Elder Scrolls V Skyrim',
    'tes 5' => 'The Elder Scrolls V Skyrim',
    'oblivion' => 'The Elder Scrolls IV Oblivion',
    'tes iv' => 'The Elder Scrolls IV Oblivion',
    'tes 4' => 'The Elder Scrolls IV Oblivion',
    
    // Call of Duty series
    'cod mw' => 'Call of Duty Modern Warfare',
    'cod mw2' => 'Call of Duty Modern Warfare 2',
    'cod mw3' => 'Call of Duty Modern Warfare 3',
    'cod bo' => 'Call of Duty Black Ops',
    'cod bo2' => 'Call of Duty Black Ops 2',
    'cod bo3' => 'Call of Duty Black Ops 3',
    'cod bo4' => 'Call of Duty Black Ops 4',
    'cod wz' => 'Call of Duty Warzone',
    
    // Fallout series
    'fallout 3' => 'Fallout 3',
    'fallout nv' => 'Fallout New Vegas',
    'fallout 4' => 'Fallout 4',
    
    // Witcher series
    'witcher 3' => 'The Witcher 3 Wild Hunt',
    'tw3' => 'The Witcher 3 Wild Hunt',
    
    // Popular indie games
    'stardew' => 'Stardew Valley',
    'hades' => 'Hades',
    'hollow knight' => 'Hollow Knight',
    'cuphead' => 'Cuphead',
    
    // Microsoft/Office
    'vscode' => 'Visual Studio Code',
    'vs code' => 'Visual Studio Code',
    'visual studio' => 'Visual Studio',
    'photoshop' => 'Adobe Photoshop',
    'ps 2024' => 'Adobe Photoshop 2024',
];

/**
 * Normalize user input for alias matching
 * Converts to lowercase, removes extra whitespace, removes punctuation
 */
function normalize_input($input) {
    // Convert to lowercase
    $normalized = strtolower($input);
    
    // Trim whitespace
    $normalized = trim($normalized);
    
    // Remove common punctuation/roman numerals inconsistencies
    $normalized = str_replace(['-', '_', '.', ',', "'"], '', $normalized);
    
    // Replace multiple spaces with single space
    $normalized = preg_replace('/\s+/', ' ', $normalized);
    
    return $normalized;
}

/**
 * Sanitize search input for API safety
 */
function sanitize_input($input) {
    $input = trim($input);
    $input = stripslashes($input);
    // For API safety, remove special characters but keep spaces
    $input = preg_replace("/[^a-zA-Z0-9\\s\\-']+/", '', $input);
    return $input;
}

/**
 * Fetch game data from RAWG.io API
 */
function fetch_game_from_rawg($game_name) {
    $game_name = trim(preg_replace('/\s+/', ' ', $game_name));
    if ($game_name === '') return null;

    // RAWG's search endpoint already supports partial titles. Do not replace the
    // user query with an exact title before it reaches RAWG.
    $api_url = RAWG_API_URL . '?search=' . rawurlencode($game_name) . '&page_size=20&key=' . rawurlencode(RAWG_API_KEY);
    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    // Execute request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Handle connection errors
    if ($curl_error) {
        return null;
    }
    
    // Handle HTTP errors
    if ($http_code !== 200) {
        return null;
    }
    
    // Parse JSON response
    $data = json_decode($response, true);

    // Check if results found.
    if (!isset($data['results']) || empty($data['results'])) {
        return null;
    }

    // Only accept candidates related to the typed term, then prefer RAWG's most
    // popular entry. This makes "Cyberpunk" resolve to "Cyberpunk 2077" without
    // accepting an unrelated first search result.
    $query = normalize_input($game_name);
    $matches = [];
    foreach ($data['results'] as $candidate) {
        $name = (string)($candidate['name'] ?? '');
        $candidate_name = normalize_input($name);
        if ($name !== '' && $query !== '' && strpos($candidate_name, $query) !== false) $matches[] = $candidate;
    }
    if (!$matches) return null;
    usort($matches, function($left, $right) {
        $left_popularity = (int)($left['added'] ?? 0) + (float)($left['rating'] ?? 0) * 1000;
        $right_popularity = (int)($right['added'] ?? 0) + (float)($right['rating'] ?? 0) * 1000;
        return $right_popularity <=> $left_popularity;
    });
    return $matches[0];
}

function fetch_rawg_game_details(array $game): array {
    $slug = trim((string)($game['slug'] ?? ''));
    if ($slug === '') return $game;
    $url = RAWG_API_URL . '/' . rawurlencode($slug) . '?key=' . rawurlencode(RAWG_API_KEY);
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5, CURLOPT_SSL_VERIFYPEER => true]);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $details = $status === 200 ? json_decode((string)$response, true) : null;
    return is_array($details) ? $details : $game;
}

/**
 * Extract system requirements from RAWG API data
 */
function extract_requirements($rawg_game) {
    // If no platform requirements available, return null
    if (!isset($rawg_game['platforms']) || empty($rawg_game['platforms'])) {
        return null;
    }
    
    $requirements = [
        'name' => $rawg_game['name'] ?? 'Unknown Game',
        'source' => 'RAWG API',
        'min_cpu' => 'Not specified',
        'min_gpu' => 'Not specified',
        'min_ram' => 'Not specified',
        'min_storage' => 'Not specified',
        'min_os' => 'Not specified',
        'rec_cpu' => 'Not specified',
        'rec_gpu' => 'Not specified',
        'rec_ram' => 'Not specified',
        'rec_storage' => 'Not specified',
    ];
    
    // RAWG exposes requirements on the PC platform object in the detail
    // response, not on the search-result object.
    foreach ($rawg_game['platforms'] as $platform) {
        if (strtolower((string)($platform['platform']['name'] ?? '')) !== 'pc') continue;
        $req_en = $platform['requirements_en'] ?? null;
        if (!is_array($req_en) || empty($req_en['minimum'])) continue;
        $requirements['min_cpu'] = (string)$req_en['minimum'];
        $requirements['rec_cpu'] = (string)($req_en['recommended'] ?? 'Not specified');
        return $requirements;
    }

    // Let the metadata-based estimate handle games for which RAWG supplies no
    // PC requirement text instead of returning a misleading empty record.
    return null;
}

/**
 * Generate estimated requirements based on game metadata
 * Fallback when RAWG doesn't have specific requirements
 */
function compute_name_match_score($query, $name) {
    $queryNorm = normalize_input($query);
    $nameNorm = normalize_input($name);
    if ($queryNorm === $nameNorm) return 100;
    if (stripos($nameNorm, $queryNorm) !== false) return 85;
    if (stripos($queryNorm, $nameNorm) !== false) return 75;

    similar_text($queryNorm, $nameNorm, $pct);
    return (int)$pct;
}

function estimate_requirements($game_name, $rawg_game = null) {
    $estimates = [
        'name' => $game_name,
        'source' => 'Estimated (AI-generated)',
        'min_cpu' => 'Intel Core i5 / AMD Ryzen 5',
        'min_gpu' => 'NVIDIA GTX 1060 / AMD RX 580',
        'min_ram' => 8,
        'min_storage' => 50,
        'min_os' => 'Windows 10/11 64-bit',
        'rec_cpu' => 'Intel Core i7 / AMD Ryzen 7',
        'rec_gpu' => 'NVIDIA RTX 2080 / AMD RX 5700 XT',
        'rec_ram' => 16,
        'rec_storage' => 100,
        'note' => 'These are estimated requirements based on game genre. For exact requirements, contact support or ask Specter.'
    ];
    
    // Adjust estimates based on available RAWG data if available
    if ($rawg_game) {
        // If game has high rating/popularity, increase estimated specs
        if (isset($rawg_game['rating']) && $rawg_game['rating'] > 4.0) {
            $estimates['rec_ram'] = 20;
            $estimates['rec_storage'] = 150;
        }
        
        // If game is recent (2022+), probably more demanding
        if (isset($rawg_game['released'])) {
            $year = date('Y', strtotime($rawg_game['released']));
            if ($year >= 2022) {
                $estimates['min_ram'] = 12;
                $estimates['rec_ram'] = 20;
            }
        }
    }
    
    return $estimates;
}

// ===== MAIN LOGIC =====

// Get search query from URL parameter
$game_query = isset($_GET['game']) ? $_GET['game'] : null;
$result = [];

if (!$game_query) {
    // No query - return empty array (for initial dropdown load)
    $result = [];
} else {
    // Normalize input for alias matching
    $normalized = normalize_input($game_query);
    $found_game_name = null;

    // Step 1: Check against aliases first
    if (isset($game_aliases[$normalized])) {
        $found_game_name = $game_aliases[$normalized];
    } else {
        // If no exact alias match, try partial matches
        foreach ($game_aliases as $alias => $official_name) {
            if (stripos($normalized, $alias) === 0 || stripos($alias, $normalized) === 0) {
                $found_game_name = $official_name;
                break;
            }
        }
    }

    // Apps are local rather than RAWG-backed and use the same partial-title rule.
    require_once __DIR__ . '/get_apps_library.php';
    $apps = cx_apps();
    $app = compatix_find_app($game_query, $apps);
    if ($app) {
        $app['source'] = 'Local app catalog';
        $app['found'] = true;
        $app['type'] = 'app';
        $result = [$app];
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // Alias resolution helps abbreviations such as GTA 5.
    $search_name = $found_game_name ?? trim(preg_replace('/\s+/', ' ', $game_query));

    // Check local catalog cache first before remote RAWG call
    $local_needle = normalize_input($search_name);
    $local_req = null;
    $canonical_req = compatix_canonical_requirements($search_name);
    if ($canonical_req) {
        $local_req = $canonical_req + ['type' => 'game', 'found' => true];
    }
    $games_cache_path = __DIR__ . '/games_cache.json';
    if (!$local_req && is_file($games_cache_path)) {
        $gc_data = json_decode((string)file_get_contents($games_cache_path), true);
        foreach ((array)($gc_data['data'] ?? []) as $item) {
            if (normalize_input((string)($item['name'] ?? '')) === $local_needle || normalize_input((string)($item['name'] ?? '')) === $normalized) {
                if (!empty($item['min_cpu']) && !preg_match('/not published|not specified|contact support/i', (string)$item['min_cpu'])) {
                    $local_req = [
                        'name' => $item['name'],
                        'type' => 'game',
                        'image_url' => $item['image_url'] ?? ($item['background_image'] ?? ''),
                        'min_cpu' => $item['min_cpu'],
                        'min_gpu' => $item['min_gpu'] ?? 'DirectX 11 compatible GPU',
                        'min_ram' => (float)($item['min_ram'] ?? 8),
                        'min_storage' => (float)($item['min_storage'] ?? 20),
                        'min_os' => $item['supported_os'] ?? 'Windows 10 64-bit',
                        'rec_cpu' => $item['rec_cpu'] ?? $item['min_cpu'],
                        'rec_gpu' => $item['rec_gpu'] ?? ($item['min_gpu'] ?? 'DirectX 11 compatible GPU'),
                        'rec_ram' => (float)($item['rec_ram'] ?? max(12, ($item['min_ram'] ?? 8) + 4)),
                        'rec_storage' => (float)($item['rec_storage'] ?? ($item['min_storage'] ?? 20)),
                        'source' => $item['source'] ?? (!empty($item['ai_estimated']) ? 'Specter AI' : 'RAWG'),
                        'ai_estimated' => !empty($item['ai_estimated']),
                        'found' => true,
                    ];
                    break;
                }
            }
        }
    }

    if ($local_req) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([$local_req], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // Step 2: Fetch from RAWG API. Accept closest-match results instead of requiring an exact string.
    $rawg_game = fetch_game_from_rawg($search_name);

    if ($rawg_game) {
        // Step 3: Fetch the selected game's detail record, where RAWG exposes
        // platform requirements (search results only contain summary metadata).
        $rawg_game = fetch_rawg_game_details($rawg_game);
        $requirements = extract_requirements($rawg_game);

        // If extraction failed or requirements empty, generate estimates.
        if (!$requirements) {
            $requirements = estimate_requirements($rawg_game['name'] ?? $search_name, $rawg_game);
        }

        $result = [[
            'name' => $requirements['name'],
            'type' => 'game',
            'image_url' => $rawg_game['background_image'] ?? ($rawg_game['image_url'] ?? ''),
            'min_cpu' => $requirements['min_cpu'] ?? 'Contact support',
            'min_gpu' => $requirements['min_gpu'] ?? 'Contact support',
            'min_ram' => $requirements['min_ram'] ?? 8,
            'min_storage' => $requirements['min_storage'] ?? 50,
            'min_os' => $requirements['min_os'] ?? 'Windows 10/11 64-bit',
            'rec_cpu' => $requirements['rec_cpu'] ?? 'Contact support',
            'rec_gpu' => $requirements['rec_gpu'] ?? 'Contact support',
            'rec_ram' => $requirements['rec_ram'] ?? 16,
            'rec_storage' => $requirements['rec_storage'] ?? 100,
            'source' => $requirements['source'] ?? 'Database',
            'note' => $requirements['note'] ?? null,
            'found' => true,
        ]];
    } else {
        // No local/RAWG match: preserve a selectable result so the checker can
        // continue to its Specter estimate instead of creating a dead end.
        $fallback = estimate_requirements($found_game_name ?? $game_query);
        $result = [[
            'name' => $fallback['name'],
            'min_cpu' => $fallback['min_cpu'],
            'min_gpu' => $fallback['min_gpu'],
            'min_ram' => $fallback['min_ram'],
            'min_storage' => $fallback['min_storage'],
            'min_os' => $fallback['min_os'],
            'rec_cpu' => $fallback['rec_cpu'],
            'rec_gpu' => $fallback['rec_gpu'],
            'rec_ram' => $fallback['rec_ram'],
            'rec_storage' => $fallback['rec_storage'],
            'found' => true,
            'source' => 'Estimated fallback',
            'note' => 'No verified RAWG match was available; Specter can refine this estimate during compatibility checking.',
        ]];
    }
}

// Return JSON response
header('Content-Type: application/json; charset=utf-8');
echo json_encode($result);
