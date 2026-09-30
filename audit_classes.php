<?php
/**
 * Development-only CSS class audit utility.
 * Inputs: none; reads project HTML and CSS files from this directory.
 * Output: plain-text report of class names for local maintenance.
 */
$css = file_get_contents('style.css');
preg_match_all('/\.([a-zA-Z0-9_\-]+)/', $css, $matches);
$cssClasses = array_flip($matches[1]);

$htmlFiles = glob('*.html');
foreach ($htmlFiles as $file) {
    $text = file_get_contents($file);

    // Ignore JavaScript/template literals that include text like class="status-badge ${statusBadgeClass}"
    $text = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $text);

    preg_match_all('/class=["\'][^"\']+["\']/', $text, $classMatches);
    $used = [];
    foreach ($classMatches[0] as $classAttr) {
        preg_match('/class=["\']([^"\']+)["\']/', $classAttr, $token);
        if (!isset($token[1])) {
            continue;
        }
        foreach (explode(' ', $token[1]) as $class) {
            $class = trim($class);
            if ($class === '') {
                continue;
            }
            $used[$class] = 1;
        }
    }

    $missing = array_diff_key($used, $cssClasses);
    if ($missing) {
        echo $file . ' missing ' . implode(',', array_keys($missing)) . PHP_EOL;
    }
}
