<?php
/**
 * CompatiX Configuration File
 * 
 * Defines global API keys, contact settings, and environment error reporting flags
 * shared across CompatiX JSON API endpoints.
 * 
 * Inputs: None.
 * Output Format: None directly; defines PHP constants.
 */

// Load local development secrets only when the host environment has not set them.
// .env is gitignored; production environment variables still take precedence.
function compatix_load_env_file(string $path): void {
    if (!is_file($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
        [$name, $value] = array_map('trim', explode('=', $line, 2));
        if ($name !== '' && getenv($name) === false) {
            putenv($name . '=' . trim($value, "\\\"'"));
        }
    }
}

compatix_load_env_file(__DIR__ . '/.env');

// RAWG.io API Configuration (for game data)
define('RAWG_API_KEY', getenv('RAWG_API_KEY') ?: '');
define('RAWG_API_URL', 'https://api.rawg.io/api/games');

// Groq AI Configuration
define('GROQ_API_KEY', getenv('GROQ_API_KEY') ?: '');

// Contact Form Configuration
define('CONTACT_EMAIL', 'support@compatix.local');
define('CONTACT_LOG_FILE', __DIR__ . '/contact_log.json');

// Error reporting (set to false in production)
define('DEBUG_MODE', false);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
