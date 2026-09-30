<?php
/**
 * CompatiX - System Specifications Parser Endpoint
 * 
 * Inputs: POST upload field containing a dxdiag-compatible text export (`text`).
 * Output Format: JSON object containing parsed cpu, gpu, ram, os, and found boolean.
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Only POST requests are allowed.']);
    exit;
}

$text = (string)($_POST['text'] ?? '');
if ($text === '') {
    http_response_code(422);
    echo json_encode(['error' => 'No text was provided.']);
    exit;
}

function extract_dxdiag_value($text, $labels) {
    foreach ($labels as $label) {
        $pattern = '/^\s*' . preg_quote($label, '/') . '\s*:\s*(.+)$/mi';
        if (preg_match($pattern, $text, $matches)) {
            return trim($matches[1]);
        }
    }
    return '';
}

$cpu = extract_dxdiag_value($text, ['Processor']);
$gpu = extract_dxdiag_value($text, ['Card name', 'Display Device']);
$ram = extract_dxdiag_value($text, ['Memory']);
$os = extract_dxdiag_value($text, ['Operating System']);

if (preg_match('/(\d+(?:\.\d+)?)\s*(GB|MB)\s*(?:RAM|memory)?/i', $ram, $memory_match)) {
    $ram = $memory_match[1] . ' ' . strtoupper($memory_match[2]);
}

$found = array_filter([$cpu, $gpu, $ram, $os]);
echo json_encode([
    'cpu' => $cpu,
    'gpu' => $gpu,
    'ram' => $ram,
    'os' => $os,
    'found' => count($found) > 0,
]);
