<?php
/**
 * Build Advisor API Endpoint
 * 
 * Provides automated PC component suggestions based on user budget (LKR currency)
 * and primary workload / use-case, or by targeted game/app requirement tier.
 * 
 * Inputs: POST JSON or form fields `budget` and `usecase`.
 * Output Format: JSON object containing cpu_tier, gpu_tier, ram, storage, and rationale.
 */

function json_response($payload, $status = 200) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

set_exception_handler(function (Throwable $error): void {
    json_response(['error' => 'Unable to create a PC suggestion. Please try again.'], 500);
});

function decode_body() {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return $_POST;
    }

    return [];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'POST request required.'], 405);
}

$data = decode_body();
$budget = trim((string)($data['budget'] ?? ''));
$usecase = trim((string)($data['usecase'] ?? ''));

if ($budget === '' || $usecase === '') {
    json_response(['error' => 'Budget and use case are required.'], 400);
}

$budgetMap = [
    'Under Rs. 150,000' => ['cpu' => 'Intel i3 / Ryzen 3', 'gpu' => 'GTX 1050 / RX 560', 'ram' => '8GB', 'storage' => '256GB SSD'],
    'Rs. 150,000 - Rs. 300,000' => ['cpu' => 'Intel i5 / Ryzen 5', 'gpu' => 'GTX 1660 / RTX 3050', 'ram' => '16GB', 'storage' => '512GB SSD'],
    'Rs. 300,000 - Rs. 450,000' => ['cpu' => 'Intel i7 / Ryzen 7', 'gpu' => 'RTX 3060 / RX 6700 XT', 'ram' => '16GB', 'storage' => '1TB SSD'],
    'Rs. 450,000 - Rs. 750,000' => ['cpu' => 'Intel i7 / Ryzen 7', 'gpu' => 'RTX 3070 / RX 6800 XT', 'ram' => '32GB', 'storage' => '1TB SSD'],
    'Rs. 750,000+' => ['cpu' => 'Intel i9 / Ryzen 9', 'gpu' => 'RTX 4080 / RX 7900 XTX', 'ram' => '32GB', 'storage' => '2TB SSD'],
];

$usecaseMap = [
    'General Use / Browsing' => ['rationale' => 'A balanced entry-level build keeps everyday multitasking simple and dependable for your budget range.', 'cpu_adj' => 0, 'gpu_adj' => 0],
    'Gaming' => ['rationale' => 'This tier balances strong 1080p gaming performance with your budget range.', 'cpu_adj' => 1, 'gpu_adj' => 1],
    'Video/Photo Editing' => ['rationale' => 'This tier is designed for smooth editing workflows with enough CPU, GPU, and memory to keep creative tasks responsive.', 'cpu_adj' => 1, 'gpu_adj' => 1],
    '3D Rendering / CAD' => ['rationale' => 'This build prioritizes strong CPU, memory, and storage throughput for demanding visualization and design work.', 'cpu_adj' => 2, 'gpu_adj' => 2],
    'Programming / Development' => ['rationale' => 'This tier focuses on smooth multi-tasking and comfortable development workflows within your budget range.', 'cpu_adj' => 1, 'gpu_adj' => 0],
];

if (!isset($budgetMap[$budget])) {
    json_response(['error' => 'Unsupported budget range.'], 400);
}

if (!isset($usecaseMap[$usecase])) {
    json_response(['error' => 'Unsupported use case.'], 400);
}

$base = $budgetMap[$budget];
$use = $usecaseMap[$usecase];

$cpu = $base['cpu'];
$gpu = $base['gpu'];
$ram = $base['ram'];
$storage = $base['storage'];

if ($use['cpu_adj'] > 1) {
    $cpu = 'Intel i7 / Ryzen 7';
}
if ($use['gpu_adj'] > 1) {
    $gpu = 'RTX 3070 / RX 6800 XT';
}
if ($use['cpu_adj'] === 1 && $budget === 'Rs. 150,000 - Rs. 300,000') {
    $cpu = 'Intel i5 / Ryzen 5';
}

if ($budget === 'Rs. 450,000 - Rs. 750,000' && $usecase === 'Gaming') {
    $gpu = 'RTX 3070 / RX 6800 XT';
}
if ($budget === 'Rs. 750,000+' && $usecase === '3D Rendering / CAD') {
    $cpu = 'Intel i9 / Ryzen 9';
    $gpu = 'RTX 4080 / RX 7900 XTX';
    $ram = '64GB';
    $storage = '2TB SSD';
}

json_response([
    'cpu_tier' => $cpu,
    'gpu_tier' => $gpu,
    'ram' => $ram,
    'storage' => $storage,
    'rationale' => $use['rationale'],
]);
