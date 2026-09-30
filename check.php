<?php
/**
 * CompatiX - Compatibility Checker API Endpoint
 * 
 * Receives POST requests with user specifications (CPU, GPU, RAM, Storage, OS, game/app name).
 * Evaluates hardware compatibility against requirements catalog or Specter AI estimation.
 * 
 * Inputs: JSON POST body containing {cpu, gpu, ram, storage, os, selected_game}.
 * Output Format: JSON object with overall compatibility boolean, matched/mismatched breakdown, and cover image URL.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app_catalog.php';
require_once __DIR__ . '/tier_helper.php';

set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => true, 'message' => 'Only POST requests are allowed.']);
        exit;
    }

    // Get JSON input
    $json_input = file_get_contents('php://input');
    $data = json_decode($json_input, true);

    // Validate input
    if (!is_array($data)) {
        throw new InvalidArgumentException('Invalid input');
    }

    // Multi-title checks reuse the same comparison logic and preserve submission order.
    if (isset($data['title_ids']) && is_array($data['title_ids']) && count($data['title_ids']) > 1) {
        $bulk_cpu = sanitize_input($data['cpu'] ?? '');
        $bulk_gpu = sanitize_input($data['gpu'] ?? '');
        $bulk_ram = floatval($data['ram'] ?? 0);
        $bulk_storage = floatval($data['storage'] ?? 0);
        $bulk_os = sanitize_input($data['os'] ?? '');
        if ($bulk_cpu === '' || $bulk_gpu === '' || $bulk_ram <= 0 || $bulk_storage <= 0 || $bulk_os === '') {
            throw new InvalidArgumentException('Complete PC specifications are required for bulk checks.');
        }
        $bulk_names = is_array($data['title_names'] ?? null) ? $data['title_names'] : [];
        $bulk_results = [];
        foreach ($data['title_ids'] as $raw_id) {
            $title_id = sanitize_input($raw_id);
            if ($title_id === '') continue;
            $title_name = sanitize_input($bulk_names[$title_id] ?? $title_id);
            $requirements = get_game_requirements($title_name);
            $source = 'verified';
            if (!$requirements) { $requirements = estimate_requirements_via_specter($title_name); $source = 'ai_estimated'; }
            $requirements = ensure_distinct_recommended_requirements($requirements);
            $item_result = check_compatibility(['cpu'=>$bulk_cpu, 'gpu'=>$bulk_gpu, 'ram'=>$bulk_ram, 'storage'=>$bulk_storage, 'os'=>$bulk_os], $requirements, $title_name, $source, resolve_image_url_for_selected_game($title_name));
            $item_result['recommended_specs'] = ['cpu'=>$requirements['rec_cpu'] ?? $requirements['min_cpu'], 'gpu'=>$requirements['rec_gpu'] ?? $requirements['min_gpu'], 'ram'=>(float)($requirements['rec_ram'] ?? $requirements['min_ram']), 'storage'=>(float)($requirements['rec_storage'] ?? $requirements['min_storage']), 'os'=>$requirements['rec_os'] ?? $requirements['min_os']];
            $item_result['source'] = $requirements['source'] ?? ($source === 'ai_estimated' ? 'Specter AI' : 'CompatiX requirements catalog');
            $item_result['ai_estimated'] = $source === 'ai_estimated';
            $item_result['title_id'] = $title_id;
            $item_result['status'] = $item_result['compatible'] ? 'Compatible' : 'Not Compatible';
            $item_result['tier'] = $item_result['compatible'] ? 'minimum' : 'below-minimum';
            $item_result['breakdown'] = ['matched' => $item_result['matched_fields'], 'mismatched' => $item_result['mismatched_fields']];
            $bulk_results[] = $item_result;
        }
        echo json_encode($bulk_results, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // Extract and sanitize data
    $cpu = sanitize_input($data['cpu'] ?? '');
    $gpu = sanitize_input($data['gpu'] ?? '');
    $ram = floatval($data['ram'] ?? 0);
    $storage = floatval($data['storage'] ?? 0);
    $os = sanitize_input($data['os'] ?? '');
    $selected_game = sanitize_input($data['selected_game'] ?? '');

    if ($cpu === '' || $gpu === '' || $ram <= 0 || $storage <= 0 || $os === '' || $selected_game === '') {
        throw new InvalidArgumentException('CPU, GPU, RAM, storage, operating system, and a game are required.');
    }

    // Get game requirements locally first.
    $game_requirements = get_game_requirements($selected_game);
    $data_source = 'verified';

    if (!$game_requirements) {
        $game_requirements = estimate_requirements_via_specter($selected_game);
        $data_source = 'ai_estimated';
    }
    $game_requirements = ensure_distinct_recommended_requirements($game_requirements);

    // Resolve a cover image from the local game/app cache if available.
    $image_url = resolve_image_url_for_selected_game($selected_game);

    // Perform compatibility check
    $result = check_compatibility([
        'cpu' => $cpu,
        'gpu' => $gpu,
        'ram' => $ram,
        'storage' => $storage,
        'os' => $os,
    ], $game_requirements, $selected_game, $data_source, $image_url);

    $result['recommended_specs'] = [
        'cpu' => $game_requirements['rec_cpu'] ?? $game_requirements['min_cpu'] ?? 'Not specified',
        'gpu' => $game_requirements['rec_gpu'] ?? $game_requirements['min_gpu'] ?? 'Not specified',
        'ram' => (float)($game_requirements['rec_ram'] ?? $game_requirements['min_ram'] ?? 0),
        'storage' => (float)($game_requirements['rec_storage'] ?? $game_requirements['min_storage'] ?? 0),
        'os' => $game_requirements['rec_os'] ?? $game_requirements['min_os'] ?? 'Not specified',
    ];
    $result['source'] = $game_requirements['source'] ?? ($data_source === 'ai_estimated' ? 'Specter AI' : 'CompatiX requirements catalog');
    $result['ai_estimated'] = $data_source === 'ai_estimated';
    $result['title_id'] = sanitize_input($data['title_id'] ?? $selected_game);
    $result['status'] = $result['compatible'] ? 'Compatible' : 'Not Compatible';
    $result['tier'] = $result['compatible'] ? 'minimum' : 'below-minimum';
    $result['breakdown'] = ['matched' => $result['matched_fields'], 'mismatched' => $result['mismatched_fields']];

    // Send response
    echo json_encode($result);
} catch (Throwable $error) {
    // Invalid form data is a client error; reserve 500 for unexpected server
    // failures so the UI can show useful validation feedback without treating
    // an incomplete submission as an outage.
    http_response_code($error instanceof InvalidArgumentException ? 400 : 500);
    echo json_encode(['error' => true, 'message' => $error->getMessage()]);
} finally {
    restore_error_handler();
}

/**
 * Sanitize input
 */
function sanitize_input($input) {
    if (is_array($input)) {
        return array_map('sanitize_input', $input);
    }
    $input = trim((string)$input);
    // JSON API values are not HTML. Encoding here breaks exact catalog titles
    // such as apostrophes before they reach the requirements lookup.
    return preg_replace('/[\x00-\x1F\x7F]/u', '', $input);
}

/**
 * Get game requirements from database
 */
function get_game_requirements($game_name) {
    $games_requirements = [
        'Cyberpunk 2077' => [
            'min_cpu' => 'Intel Core i7-9700 / AMD Ryzen 5 3600',
            'min_gpu' => 'NVIDIA GeForce RTX 2060 / AMD Radeon RX 5700',
            'min_ram' => 8,
            'min_storage' => 160,
            'min_os' => 'Windows 10/11 64-bit',
        ],
        'Grand Theft Auto V' => [
            'min_cpu' => 'Intel Core i5-3470 / AMD FX-8350',
            'min_gpu' => 'NVIDIA GTX 660 / AMD HD 7870',
            'min_ram' => 8,
            'min_storage' => 110,
            'min_os' => 'Windows 10/11 64-bit',
        ],
        'Elden Ring' => [
            'min_cpu' => 'Intel Core i7-10700 / AMD Ryzen 5 3600',
            'min_gpu' => 'NVIDIA GeForce RTX 2080 Ti / AMD Radeon RX 5700',
            'min_ram' => 12,
            'min_storage' => 60,
            'min_os' => 'Windows 10/11 64-bit',
        ],
        'Baldur\'s Gate 3' => [
            'min_cpu' => 'Intel Core i7-9700 / AMD Ryzen 5 3600',
            'min_gpu' => 'NVIDIA GeForce RTX 2070 / AMD Radeon RX 5600 XT',
            'min_ram' => 8,
            'min_storage' => 150,
            'min_os' => 'Windows 10/11 64-bit',
        ],
        'Visual Studio Code' => [
            'min_cpu' => 'Intel Pentium 4 or newer',
            'min_gpu' => 'Integrated graphics',
            'min_ram' => 2,
            'min_storage' => 500,
            'min_os' => 'Windows 7 and later 64-bit',
        ],
        'Adobe Photoshop 2024' => [
            'min_cpu' => 'Intel Core i5 6th Gen / AMD Ryzen 5 1600',
            'min_gpu' => 'NVIDIA GeForce GTX 960 / AMD Radeon R9 380',
            'min_ram' => 8,
            'min_storage' => 50,
            'min_os' => 'Windows 10/11 21H2 or later',
        ],
    ];

    $query = compatix_normalize_title($game_name);
    $aliases = [
        'gta' => 'grand theft auto v',
        'gta 5' => 'grand theft auto v',
        'gta5' => 'grand theft auto v',
        'gtav' => 'grand theft auto v',
        'photoshop' => 'adobe photoshop 2024',
        'vscode' => 'visual studio code',
        'vs code' => 'visual studio code',
    ];
    $query = $aliases[$query] ?? $query;
    $requirements = null;
    foreach ($games_requirements as $title => $candidate) {
        $normalized_title = compatix_normalize_title($title);
        if ($query === $normalized_title || strpos($normalized_title, $query) !== false || strpos($query, $normalized_title) !== false) {
            $requirements = $candidate;
            break;
        }
    }

    if (!$requirements) {
        $requirements = compatix_library_requirements($game_name);
    }
    if (!$requirements) {
        $requirements = compatix_find_app($game_name);
    }
    if ($requirements) {
        $requirements['rec_ram'] ??= max($requirements['min_ram'] * 2, $requirements['min_ram'] + 4, 8);
        $requirements['rec_storage'] ??= max($requirements['min_storage'] * 2, $requirements['min_storage'] + 4, 8);
        $requirements['rec_cpu'] ??= $requirements['min_cpu'];
        $requirements['rec_gpu'] ??= $requirements['min_gpu'];
        $requirements['rec_os'] ??= $requirements['min_os'];
    }
    return $requirements;
}

/** Return requirements only when an exact item exists in the curated caches. */
function compatix_library_requirements($item_name) {
    $needle = compatix_normalize_title($item_name);
    if ($needle === '') return null;

    foreach (['games_cache.json', 'apps_cache.json'] as $file) {
        $path = __DIR__ . '/' . $file;
        $payload = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
        foreach ((array) ($payload['data'] ?? []) as $item) {
            if (compatix_normalize_title((string) ($item['name'] ?? '')) !== $needle) continue;
            if (empty($item['min_cpu']) || (int) ($item['min_ram'] ?? 0) < 1) continue;
            return [
                'min_cpu' => $item['min_cpu'], 'min_gpu' => $item['min_gpu'] ?? 'Not specified',
                'min_ram' => (float) $item['min_ram'], 'min_storage' => (float) ($item['min_storage'] ?? 0),
                'min_os' => $item['supported_os'] ?? 'Windows',
                'rec_cpu' => $item['rec_cpu'] ?? $item['min_cpu'],
                'rec_gpu' => $item['rec_gpu'] ?? ($item['min_gpu'] ?? 'Not specified'),
                'rec_ram' => (float) ($item['rec_ram'] ?? $item['min_ram']),
                'rec_storage' => (float) ($item['rec_storage'] ?? ($item['min_storage'] ?? 0)),
                'rec_os' => $item['supported_os'] ?? 'Windows',
            ];
        }
    }
    return null;
}

/**
 * Check compatibility between user specs and game requirements
 */
function check_compatibility($user_specs, $game_requirements, $selected_game, $data_source = 'verified', $image_url = '') {
    $matched_fields = [];
    $mismatched_fields = [];
    $compatible = true;

    $user_specs_response = [
        'cpu' => $user_specs['cpu'],
        'gpu' => $user_specs['gpu'],
        'ram' => $user_specs['ram'],
        'storage' => $user_specs['storage'],
        'os' => $user_specs['os'],
    ];

    $required_specs_response = [
        'cpu' => $game_requirements['min_cpu'],
        'gpu' => $game_requirements['min_gpu'],
        'ram' => $game_requirements['min_ram'],
        'storage' => $game_requirements['min_storage'],
        'os' => $game_requirements['min_os'] ?? 'Not specified',
    ];

    // Check RAM
    if ($user_specs['ram'] >= $game_requirements['min_ram']) {
        $matched_fields[] = 'RAM';
    } else {
        $compatible = false;
        $mismatched_fields[] = [
            'field' => 'RAM',
            'reason' => sprintf(
                'Below minimum (%dGB < %dGB)',
                $user_specs['ram'],
                $game_requirements['min_ram']
            ),
            'suggested_upgrade' => 'Consider upgrading to at least 8GB, ideally 16GB, of RAM.',
        ];
    }

    // Check Storage
    if ($user_specs['storage'] >= $game_requirements['min_storage']) {
        $matched_fields[] = 'Storage';
    } else {
        $compatible = false;
        $mismatched_fields[] = [
            'field' => 'Storage',
            'reason' => sprintf(
                'Below minimum (%dGB < %dGB)',
                $user_specs['storage'],
                $game_requirements['min_storage']
            ),
            'suggested_upgrade' => 'You\'ll need at least 20GB of free storage for this.',
        ];
    }

    // CPU compatibility
    $cpu_tier_user = compatix_cpu_tier($user_specs['cpu']);

    if (in_array($cpu_tier_user, ['high-end', 'mid-high', 'mid'], true)) {
        $matched_fields[] = 'CPU';
    } else {
        $compatible = false;
        $mismatched_fields[] = [
            'field' => 'CPU',
            'reason' => 'CPU tier below the expected minimum.',
            'suggested_upgrade' => 'Consider upgrading to at least an Intel Core i5 / Core Ultra 5 or AMD Ryzen 5-class chip.',
        ];
    }

    // GPU compatibility
    $gpu_tier_user = compatix_gpu_tier($user_specs['gpu']);

    if (in_array($gpu_tier_user, ['high-end', 'mid-high', 'mid'], true)) {
        $matched_fields[] = 'GPU';
    } else {
        $compatible = false;
        $mismatched_fields[] = [
            'field' => 'GPU',
            'reason' => 'GPU tier below the expected minimum.',
            'suggested_upgrade' => 'Consider a modern dedicated GPU such as the GTX 1660, RTX 3060, RX 6600, or better.',
        ];
    }

    // OS compatibility
    $required_os = $game_requirements['min_os'] ?? $user_specs['os'];
    if (compatix_os_satisfies_requirement($user_specs['os'], $required_os)) {
        $matched_fields[] = 'OS';
    } else {
        $compatible = false;
        $mismatched_fields[] = [
            'field' => 'OS',
            'reason' => 'Operating system not compatible',
            'suggested_upgrade' => 'Use a supported version such as Windows 10/11, a recent macOS release, or a current Linux distro.',
        ];
    }

    return [
        'compatible' => $compatible,
        'game_name' => $selected_game,
        'user_specs' => $user_specs_response,
        'required_specs' => $required_specs_response,
        'matched_fields' => array_unique($matched_fields),
        'mismatched_fields' => $mismatched_fields,
        'data_source' => $data_source,
        'image_url' => $image_url,
    ];
}

function resolve_image_url_for_selected_game($selected_game) {
    $query = compatix_normalize_title($selected_game);
    if ($query === '') {
        return '';
    }

    $candidates = [
        __DIR__ . '/games_cache.json',
        __DIR__ . '/apps_cache.json',
    ];

    foreach ($candidates as $path) {
        if (!file_exists($path)) {
            continue;
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            continue;
        }

        $decoded = json_decode($raw, true);
        $rows = $decoded['data'] ?? [];
        if (!is_array($rows)) {
            continue;
        }

        foreach ($rows as $row) {
            $name = $row['name'] ?? '';
            if (!$name) {
                continue;
            }

            $nameNorm = compatix_normalize_title($name);
            if ($nameNorm === $query || strpos($nameNorm, $query) !== false || strpos($query, $nameNorm) !== false) {
                return $row['image_url'] ?? '';
            }
        }
    }

    return '';
}

/** Keep the display-only recommended tier meaningfully above minimum values. */
function ensure_distinct_recommended_requirements(array $requirements): array {
    $minRam = (float)($requirements['min_ram'] ?? 0);
    $minStorage = (float)($requirements['min_storage'] ?? 0);
    $recRam = (float)($requirements['rec_ram'] ?? 0);
    $recStorage = (float)($requirements['rec_storage'] ?? 0);
    $requirements['rec_ram'] = $recRam > $minRam ? $recRam : max($minRam + 4, $minRam * 2, 8);
    $requirements['rec_storage'] = $recStorage > $minStorage ? $recStorage : max($minStorage + 4, $minStorage * 2, 8);

    $minCpu = trim((string)($requirements['min_cpu'] ?? 'Not specified'));
    $recCpu = trim((string)($requirements['rec_cpu'] ?? ''));
    if ($recCpu === '' || strcasecmp($recCpu, $minCpu) === 0) {
        if (preg_match('/i3|pentium|celeron/i', $minCpu)) $recCpu = 'Intel i5-8400';
        elseif (preg_match('/i5|ryzen\s*3/i', $minCpu)) $recCpu = 'Intel i7-10700K';
        elseif (preg_match('/i7|ryzen\s*5/i', $minCpu)) $recCpu = 'Intel i9-12900K';
        else $recCpu = $minCpu . ' or better';
        $requirements['rec_cpu'] = $recCpu;
    }

    $minGpu = trim((string)($requirements['min_gpu'] ?? 'Not specified'));
    $recGpu = trim((string)($requirements['rec_gpu'] ?? ''));
    if ($recGpu === '' || strcasecmp($recGpu, $minGpu) === 0) {
        if (strcasecmp($minGpu, 'Integrated') === 0) $recGpu = 'GTX 1050';
        elseif (preg_match('/GTX\s*10(50|60|70|80)/i', $minGpu)) $recGpu = 'RTX 3060';
        elseif (preg_match('/RTX\s*30/i', $minGpu)) $recGpu = 'RTX 4070';
        else $recGpu = $minGpu . ' or better';
        $requirements['rec_gpu'] = $recGpu;
    }

    return $requirements;
}

function estimate_requirements_via_specter($game_name) {
    $prompt = "Estimate typical minimum and recommended PC requirements for '{$game_name}'. Storage values must be numeric gigabytes, never a version number or CPU/GPU frequency; use at least 4 GB for a modern desktop app. Recommended must be a genuinely higher tier than minimum: newer CPU, dedicated or higher-tier GPU when minimum is integrated, and strictly higher RAM and storage. Respond ONLY in this exact JSON format: { \"min_cpu\": \"...\", \"min_gpu\": \"...\", \"min_ram\": ..., \"min_storage\": ..., \"min_os\": \"...\", \"rec_cpu\": \"...\", \"rec_gpu\": \"...\", \"rec_ram\": ..., \"rec_storage\": ..., \"rec_os\": \"...\" } with no extra text.";

    $payload = [
        'messages' => [
            ['role' => 'user', 'content' => $prompt],
        ],
        'savedSpecs' => [],
    ];

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base_path = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    $specter_url = $scheme . '://' . $host . ($base_path ? $base_path : '') . '/specter.php';
    $ch = curl_init($specter_url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error !== '') {
        throw new RuntimeException('AI fallback connection failed: ' . $curl_error);
    }

    if ($http_code !== 200) {
        throw new RuntimeException('AI fallback HTTP failure: ' . $http_code);
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded) || empty($decoded['reply'])) {
        throw new RuntimeException('AI fallback returned an empty response.');
    }

    $json = extract_json_object($decoded['reply']);
    if (!$json) {
        throw new RuntimeException('AI fallback did not return JSON requirements.');
    }

    $parsed = json_decode($json, true);
    if (!is_array($parsed)) {
        throw new RuntimeException('AI fallback could not parse requirements JSON.');
    }

    $minStorage = max(4, (float)($parsed['min_storage'] ?? 0));
    $required = [
        'min_cpu' => $parsed['min_cpu'] ?? 'Not specified',
        'min_gpu' => $parsed['min_gpu'] ?? 'Not specified',
        'min_ram' => (float)($parsed['min_ram'] ?? 0),
        'min_storage' => $minStorage,
        'min_os' => $parsed['min_os'] ?? 'Windows 10/11 64-bit',
        'rec_cpu' => $parsed['rec_cpu'] ?? 'Not specified',
        'rec_gpu' => $parsed['rec_gpu'] ?? 'Not specified',
        'rec_ram' => (float)($parsed['rec_ram'] ?? 0),
        'rec_storage' => (float)($parsed['rec_storage'] ?? 0),
        'rec_os' => $parsed['rec_os'] ?? ($parsed['min_os'] ?? 'Windows 10/11 64-bit'),
        'source' => 'Specter AI',
        'note' => 'Estimated by Specter AI because the exact title was not present in the local knowledge base.',
    ];

    return $required;
}

function extract_json_object($reply) {
    if (preg_match('/\{.*\}/s', $reply, $match)) {
        return $match[0];
    }
    return null;
}
