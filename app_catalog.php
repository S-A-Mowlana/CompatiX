<?php
/**
 * Shared local application catalog and title helpers.
 * Inputs: included by PHP endpoints; helper functions receive application titles.
 * Output: no direct response; exposes catalog data and normalized-title helpers.
 */
function compatix_normalize_title(string $title): string {
    $title = strtolower(trim($title));
    $title = preg_replace('/[^a-z0-9]+/', ' ', $title);
    return trim(preg_replace('/\s+/', ' ', $title));
}

function compatix_app_catalog(): array {
    return [
        ['name' => 'Adobe Photoshop 2024', 'min_cpu' => 'Intel i5-8400', 'min_gpu' => 'GTX 1050', 'min_ram' => 8, 'min_storage' => 4, 'min_os' => 'Windows 10/11 or macOS', 'rec_cpu' => 'Intel i9-11900K', 'rec_gpu' => 'RTX 3080', 'rec_ram' => 32, 'rec_storage' => 10],
        ['name' => 'Blender 4.0', 'min_cpu' => 'Intel i5-8400', 'min_gpu' => 'Integrated', 'min_ram' => 8, 'min_storage' => 5, 'min_os' => 'Windows, macOS, or Linux', 'rec_cpu' => 'Intel i9-12900K', 'rec_gpu' => 'RTX 3090', 'rec_ram' => 32, 'rec_storage' => 15],
        ['name' => 'Visual Studio Code', 'min_cpu' => 'Intel i3-6100', 'min_gpu' => 'Integrated', 'min_ram' => 2, 'min_storage' => 1, 'min_os' => 'Windows, macOS, or Linux', 'rec_cpu' => 'Intel i7-8700K', 'rec_gpu' => 'Integrated', 'rec_ram' => 8, 'rec_storage' => 3],
        ['name' => 'Adobe Premiere Pro 2024', 'min_cpu' => 'Intel i7-10700K', 'min_gpu' => 'RTX 2080', 'min_ram' => 16, 'min_storage' => 8, 'min_os' => 'Windows 10/11 or macOS', 'rec_cpu' => 'Intel i9-12900K', 'rec_gpu' => 'RTX 3090 Ti', 'rec_ram' => 32, 'rec_storage' => 20],
        ['name' => 'AutoCAD 2024', 'min_cpu' => 'Intel i5-8400', 'min_gpu' => 'GTX 1050', 'min_ram' => 8, 'min_storage' => 7, 'min_os' => 'Windows 10/11', 'rec_cpu' => 'Intel i7-10700K', 'rec_gpu' => 'RTX 2080', 'rec_ram' => 16, 'rec_storage' => 15],
        ['name' => 'JetBrains IntelliJ IDEA', 'min_cpu' => 'Intel i5-8400', 'min_gpu' => 'Integrated', 'min_ram' => 4, 'min_storage' => 3, 'min_os' => 'Windows, macOS, or Linux', 'rec_cpu' => 'Intel i7-10700K', 'rec_gpu' => 'Integrated', 'rec_ram' => 8, 'rec_storage' => 5],
        ['name' => 'DaVinci Resolve Studio', 'min_cpu' => 'Intel i7-8700K', 'min_gpu' => 'GTX 1080', 'min_ram' => 16, 'min_storage' => 20, 'min_os' => 'Windows, macOS, or Linux', 'rec_cpu' => 'Intel i9-12900K', 'rec_gpu' => 'RTX 3090 Ti', 'rec_ram' => 32, 'rec_storage' => 50],
        ['name' => 'Adobe Illustrator 2024', 'min_cpu' => 'Intel i5-8400', 'min_gpu' => 'GTX 1050', 'min_ram' => 8, 'min_storage' => 4, 'min_os' => 'Windows 10/11 or macOS', 'rec_cpu' => 'Intel i9-11900K', 'rec_gpu' => 'RTX 3080', 'rec_ram' => 16, 'rec_storage' => 8],
        ['name' => 'Figma', 'min_cpu' => 'Intel i5-6300U', 'min_gpu' => 'Integrated', 'min_ram' => 4, 'min_storage' => 1, 'min_os' => 'Windows, macOS, or Linux', 'rec_cpu' => 'Intel i7-10700K', 'rec_gpu' => 'Integrated', 'rec_ram' => 8, 'rec_storage' => 2],
        ['name' => 'Ableton Live 12', 'min_cpu' => 'Intel i5-8400', 'min_gpu' => 'Integrated', 'min_ram' => 8, 'min_storage' => 5, 'min_os' => 'Windows 10/11 or macOS', 'rec_cpu' => 'Intel i7-10700K', 'rec_gpu' => 'Integrated', 'rec_ram' => 16, 'rec_storage' => 10],
        ['name' => 'Pro Tools Ultimate', 'min_cpu' => 'Intel i7-8700K', 'min_gpu' => 'Integrated', 'min_ram' => 16, 'min_storage' => 10, 'min_os' => 'Windows 10/11 or macOS', 'rec_cpu' => 'Intel i9-12900K', 'rec_gpu' => 'Integrated', 'rec_ram' => 32, 'rec_storage' => 20],
        ['name' => 'Lightroom Classic', 'min_cpu' => 'Intel i5-8400', 'min_gpu' => 'Integrated', 'min_ram' => 8, 'min_storage' => 3, 'min_os' => 'Windows 10/11 or macOS', 'rec_cpu' => 'Intel i7-10700K', 'rec_gpu' => 'GTX 1050', 'rec_ram' => 16, 'rec_storage' => 8],
        ['name' => 'Microsoft Office 365', 'min_cpu' => 'Intel i3-6100', 'min_gpu' => 'Integrated', 'min_ram' => 4, 'min_storage' => 3, 'min_os' => 'Windows 10/11 or macOS', 'rec_cpu' => 'Intel i5-8400', 'rec_gpu' => 'Integrated', 'rec_ram' => 8, 'rec_storage' => 5],
        ['name' => 'Obsidian', 'min_cpu' => 'Intel i3-6100', 'min_gpu' => 'Integrated', 'min_ram' => 2, 'min_storage' => 1, 'min_os' => 'Windows, macOS, or Linux', 'rec_cpu' => 'Intel i5-8400', 'rec_gpu' => 'Integrated', 'rec_ram' => 4, 'rec_storage' => 2],
        ['name' => '7-Zip', 'min_cpu' => 'Intel Pentium 4', 'min_gpu' => 'Integrated', 'min_ram' => 1, 'min_storage' => 1, 'min_os' => 'Windows', 'rec_cpu' => 'Intel i5-8400', 'rec_gpu' => 'Integrated', 'rec_ram' => 4, 'rec_storage' => 1],
        ['name' => 'VLC Media Player', 'min_cpu' => 'Intel Pentium 4', 'min_gpu' => 'Integrated', 'min_ram' => 1, 'min_storage' => 1, 'min_os' => 'Windows, macOS, or Linux', 'rec_cpu' => 'Intel i5-8400', 'rec_gpu' => 'Integrated', 'rec_ram' => 4, 'rec_storage' => 1],
        ['name' => 'Nuke (Foundry)', 'min_cpu' => 'Intel i7-10700K', 'min_gpu' => 'RTX 2080', 'min_ram' => 16, 'min_storage' => 10, 'min_os' => 'Windows, macOS, or Linux', 'rec_cpu' => 'Intel i9-12900K', 'rec_gpu' => 'RTX 3090 Ti', 'rec_ram' => 64, 'rec_storage' => 30],
        ['name' => 'Rhino 7', 'min_cpu' => 'Intel i5-8400', 'min_gpu' => 'GTX 1050', 'min_ram' => 8, 'min_storage' => 5, 'min_os' => 'Windows 10/11 or macOS', 'rec_cpu' => 'Intel i9-11900K', 'rec_gpu' => 'RTX 3080', 'rec_ram' => 32, 'rec_storage' => 15],
        ['name' => 'Slack', 'min_cpu' => 'Intel i3-6100', 'min_gpu' => 'Integrated', 'min_ram' => 2, 'min_storage' => 1, 'min_os' => 'Windows, macOS, or Linux', 'rec_cpu' => 'Intel i5-8400', 'rec_gpu' => 'Integrated', 'rec_ram' => 4, 'rec_storage' => 2],
        ['name' => 'Godot Engine', 'min_cpu' => 'Intel i5-8400', 'min_gpu' => 'Integrated', 'min_ram' => 4, 'min_storage' => 2, 'min_os' => 'Windows, macOS, or Linux', 'rec_cpu' => 'Intel i7-10700K', 'rec_gpu' => 'GTX 1050', 'rec_ram' => 8, 'rec_storage' => 5],
        ['name' => 'Google Chrome', 'min_cpu' => 'Intel i3-6100', 'min_gpu' => 'Integrated', 'min_ram' => 2, 'min_storage' => 1, 'min_os' => 'Windows, macOS, or Linux', 'rec_cpu' => 'Intel i5-8400', 'rec_gpu' => 'Integrated', 'rec_ram' => 8, 'rec_storage' => 2],
        ['name' => 'Zoom Workplace', 'min_cpu' => 'Intel i3-6100', 'min_gpu' => 'Integrated', 'min_ram' => 4, 'min_storage' => 1, 'min_os' => 'Windows, macOS, or Linux', 'rec_cpu' => 'Intel i5-8400', 'rec_gpu' => 'Integrated', 'rec_ram' => 8, 'rec_storage' => 2],
        ['name' => 'Discord', 'min_cpu' => 'Intel i3-6100', 'min_gpu' => 'Integrated', 'min_ram' => 2, 'min_storage' => 1, 'min_os' => 'Windows, macOS, or Linux', 'rec_cpu' => 'Intel i5-8400', 'rec_gpu' => 'Integrated', 'rec_ram' => 4, 'rec_storage' => 2],
        ['name' => 'Spotify', 'min_cpu' => 'Intel i3-6100', 'min_gpu' => 'Integrated', 'min_ram' => 2, 'min_storage' => 1, 'min_os' => 'Windows, macOS, or Linux', 'rec_cpu' => 'Intel i5-8400', 'rec_gpu' => 'Integrated', 'rec_ram' => 4, 'rec_storage' => 2],
    ];
}

function compatix_find_app(string $query, ?array $apps = null): ?array {
    $query = compatix_normalize_title($query);
    if ($query === '') return null;
    $aliases = ['photoshop' => 'adobe photoshop 2024', 'premiere' => 'adobe premiere pro 2024', 'vs code' => 'visual studio code', 'vscode' => 'visual studio code', 'office' => 'microsoft office 365', 'davinci' => 'davinci resolve studio'];
    $query = $aliases[$query] ?? $query;
    $matches = [];
    foreach ($apps ?? compatix_app_catalog() as $app) {
        $name = compatix_normalize_title($app['name']);
        if (strpos($name, $query) !== false || strpos($query, $name) !== false) $matches[] = $app;
    }
    usort($matches, fn($a, $b) => strlen($a['name']) <=> strlen($b['name']));
    return $matches[0] ?? null;
}
