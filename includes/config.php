<?php
/**
 * Configuration Loader
 * Loads environment variables from .env file and defines application constants
 */

// Load environment variables from .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        die("Environment file not found. Please copy .env.example to .env and configure it.");
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Set as environment variable and make available via getenv()
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

// Load .env file
$envPath = __DIR__ . '/../.env';
loadEnv($envPath);

// Helper function to get environment variables with default
function env($key, $default = null) {
    $value = getenv($key);
    return $value !== false ? $value : $default;
}

// Define application constants
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'event_platform'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

define('APP_ENV', env('APP_ENV', 'development'));
define('APP_URL', env('APP_URL', 'http://localhost'));
define('APP_NAME', env('APP_NAME', 'Event Management Platform'));

define('SESSION_LIFETIME', (int)env('SESSION_LIFETIME', 3600));
define('SESSION_NAME', env('SESSION_NAME', 'EVENT_PLATFORM_SESSION'));

define('MAX_FILE_SIZE', (int)env('MAX_FILE_SIZE', 5242880)); // 5MB default
define('ALLOWED_IMAGE_TYPES', explode(',', env('ALLOWED_IMAGE_TYPES', 'image/jpeg,image/png,image/gif')));

define('MAIL_FROM_ADDRESS', env('MAIL_FROM_ADDRESS', 'noreply@eventplatform.local'));
define('MAIL_FROM_NAME', env('MAIL_FROM_NAME', 'Event Platform'));

define('CSRF_TOKEN_NAME', env('CSRF_TOKEN_NAME', 'csrf_token'));
define('PASSWORD_MIN_LENGTH', (int)env('PASSWORD_MIN_LENGTH', 8));

// Error reporting based on environment
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}
?>
