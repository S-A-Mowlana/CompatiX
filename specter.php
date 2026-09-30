<?php
/**
 * Specter AI Chat Backend (Groq Integration)
 * 
 * Receives chat messages and user PC specifications, interacts with the Groq AI service,
 * and formats friendly, plain-text response payloads with optional suggestion chips and inline cards.
 * 
 * Inputs: JSON POST body containing {messages: array, savedSpecs: object}.
 * Output Format: JSON object {reply: string, suggestions: array, compatibility_result: object|null, mentioned_item: object|null}.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/tier_helper.php';

// Read the complete in-memory conversation from the incoming JSON request.
$input = json_decode(file_get_contents('php://input'), true);
$messages = isset($input['messages']) && is_array($input['messages']) ? $input['messages'] : [];
$saved_specs = isset($input['savedSpecs']) && is_array($input['savedSpecs']) ? $input['savedSpecs'] : [];
$context = isset($input['context']) && is_array($input['context']) ? $input['context'] : null;

if (empty($messages)) {
    echo assistant_json('Please type a message.');
    exit;
}

// Keep only valid user/assistant messages and cap the history at the newest 20.
$chat_history = [];
foreach ($messages as $chat_message) {
    if (!is_array($chat_message) || !isset($chat_message['role'], $chat_message['content'])) {
        continue;
    }

    $role = $chat_message['role'];
    $content = trim((string) $chat_message['content']);
    if (($role === 'user' || $role === 'assistant') && $content !== '') {
        $chat_history[] = ['role' => $role, 'content' => $content];
    }
}

if (empty($chat_history)) {
    echo assistant_json('Please type a message.');
    exit;
}

$chat_history = array_slice($chat_history, -20);

// Format saved specs string for context prompt
$saved_specs_text = '';
foreach (['cpu', 'gpu', 'ram', 'storage', 'os'] as $spec_key) {
    if (!empty($saved_specs[$spec_key])) {
        $saved_specs_text .= strtoupper($spec_key) . ': ' . trim((string)$saved_specs[$spec_key]) . '; ';
    }
}

$system_prompt = 'You are Specter, a friendly and approachable AI companion for CompatiX — a PC compatibility checker website. Talk like a knowledgeable friend, not a corporate support bot: warm, casual, encouraging, and genuinely interested in helping the user out. Use natural, conversational language (contractions like \'you\'re\', \'let\'s\', \'that\'s\' are great) and a bit of personality — but stay clear, accurate, and helpful about hardware compatibility, troubleshooting, and upgrade advice. Keep responses in plain text only (no Markdown formatting, no #, **, or | symbols). Keep replies concise (2-5 sentences) unless the user asks for more detail. If asked whether a game/app will run, give a direct, friendly answer with a brief reason — like you\'re texting a friend who asked for a quick opinion, not writing a report.';
if ($saved_specs_text !== '') {
    $system_prompt .= ' The user\'s saved PC specs are: ' . rtrim($saved_specs_text, '; ') . '. Use these automatically when relevant instead of asking the user to repeat them, unless savedSpecs is empty.';
}
if ($context) {
    $context_json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $system_prompt .= ' The current CompatiX page context is: ' . $context_json . '. Use this context when resolving references such as "this title", "this app", or "can I run this?".';
}

$request_body = [
    'model' => 'openai/gpt-oss-20b',
    'messages' => array_merge([
        [
            'role' => 'system',
            'content' => $system_prompt,
        ],
    ], $chat_history),
    'temperature' => 0.6,
    'max_tokens' => 300,
];

// Send request to Groq.
$ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . GROQ_API_KEY,
    ],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($request_body),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 15,
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error !== '') {
    echo assistant_json('Connection error: ' . $curl_error);
    exit;
}

if ($http_code !== 200) {
    $err_detail = 'AI service error (code ' . $http_code . ')';
    if ($response) {
        $json_res = json_decode($response, true);
        if (isset($json_res['error']['message'])) {
            $err_detail .= ': ' . $json_res['error']['message'];
        }
    }
    echo assistant_json($err_detail);
    exit;
}

$decoded = json_decode($response, true);
if (!isset($decoded['choices'][0]['message']['content'])) {
    echo assistant_json('Unexpected response from AI. Please try again.');
    exit;
}

$reply = $decoded['choices'][0]['message']['content'];
$suggestions = [];
if (preg_match('/###SUGGESTIONS:\s*(.+)$/mi', $reply, $suggestion_match)) {
    $suggestions = array_values(array_filter(array_map('trim', explode('|', $suggestion_match[1]))));
    $reply = preg_replace('/\s*###SUGGESTIONS:\s*.+$/mi', '', $reply);
}

// Safety net: remove common Markdown markers if the model includes them anyway.
$reply = preg_replace(['/\*\*/', '/##/', '/\|/', '/^\s*-\s+/m'], '', $reply);

$latest_user_message = '';
foreach (array_reverse($messages) as $message) {
    if (($message['role'] ?? '') === 'user') {
        $latest_user_message = (string)$message['content'];
        break;
    }
}

$mentioned_item = find_mentioned_item($reply . ' ' . $latest_user_message);
$compatibility_result = build_compatibility_result($reply . ' ' . $latest_user_message, $messages, $saved_specs);

echo json_encode([
    'reply' => trim($reply),
    'suggestions' => array_slice($suggestions, 0, 4),
    'compatibility_result' => $compatibility_result,
    'mentioned_item' => $mentioned_item,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

function load_library_cache($filename) {
    $path = __DIR__ . '/' . $filename;
    if (!is_file($path)) return [];
    $cache = json_decode((string)file_get_contents($path), true);
    return is_array($cache['data'] ?? null) ? $cache['data'] : [];
}

function find_mentioned_item($reply) {
    foreach (load_library_cache('games_cache.json') as $item) {
        if (stripos($reply, $item['name']) !== false) return ['name' => $item['name'], 'image_url' => $item['image_url'] ?? '', 'id' => $item['id'], 'type' => 'game'];
    }
    foreach (load_library_cache('apps_cache.json') as $item) {
        if (stripos($reply, $item['name']) !== false) return ['name' => $item['name'], 'image_url' => $item['icon_url'] ?? $item['image_url'] ?? '', 'id' => $item['id'], 'type' => 'app'];
    }
    return null;
}

function build_compatibility_result($reply, $messages, $saved_specs) {
    $item = find_mentioned_item($reply);
    if (!$item) return null;
    $records = load_library_cache($item['type'] === 'app' ? 'apps_cache.json' : 'games_cache.json');
    $record = null;
    foreach ($records as $candidate) {
        if ((string)$candidate['id'] === (string)$item['id']) {
            $record = $candidate;
            break;
        }
    }
    if (!$record || empty($saved_specs['cpu']) || empty($saved_specs['gpu']) || empty($saved_specs['ram']) || empty($saved_specs['storage']) || empty($saved_specs['os'])) return null;
    
    $ram = (float)$saved_specs['ram'];
    $storage = (float)$saved_specs['storage'];
    
    $checks = [
        ['field' => 'CPU', 'ok' => in_array(compatix_cpu_tier($saved_specs['cpu']), ['mid', 'mid-high', 'high-end'], true)],
        ['field' => 'GPU', 'ok' => in_array(compatix_gpu_tier($saved_specs['gpu']), ['mid', 'mid-high', 'high-end'], true)],
        ['field' => 'RAM', 'ok' => $ram >= (float)$record['min_ram']],
        ['field' => 'Storage', 'ok' => $storage >= (float)$record['min_storage']],
        ['field' => 'OS', 'ok' => compatix_os_satisfies_requirement($saved_specs['os'], $record['supported_os'] ?? 'Windows')],
    ];
    $compatible = !in_array(false, array_column($checks, 'ok'), true);
    return [
        'game_name' => $record['name'],
        'item_id' => $record['id'],
        'item_type' => $item['type'],
        'image_url' => $record['image_url'] ?? $item['image_url'] ?? '',
        'compatible' => $compatible,
        'overall_status' => $compatible ? 'Compatible' : 'Needs attention',
        'breakdown' => array_map(function ($check) { return ['field' => $check['field'], 'status' => $check['ok'] ? 'Meets minimum' : 'Below minimum']; }, $checks),
        'minimum' => [
            'cpu' => $record['min_cpu'] ?? 'Not listed',
            'gpu' => $record['min_gpu'] ?? 'Not listed',
            'ram' => ($record['min_ram'] ?? '?') . ' GB',
            'storage' => ($record['min_storage'] ?? '?') . ' GB',
            'os' => $record['supported_os'] ?? 'Not listed',
        ],
        'your_pc' => [
            'cpu' => $saved_specs['cpu'],
            'gpu' => $saved_specs['gpu'],
            'ram' => $saved_specs['ram'] . ' GB',
            'storage' => $saved_specs['storage'] . ' GB',
            'os' => $saved_specs['os'],
        ],
    ];
}

function assistant_json($reply) {
    return json_encode(['reply' => $reply, 'suggestions' => [], 'compatibility_result' => null, 'mentioned_item' => null], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
