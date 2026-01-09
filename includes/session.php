<?php
/**
 * Secure Session Management
 * Handles session initialization, regeneration, and timeout
 */

/**
 * Initialize a secure session
 */
function initSecureSession() {
    // Session configuration
    ini_set('session.cookie_httponly', 1); // Prevent JavaScript access to session cookie
    ini_set('session.use_only_cookies', 1); // Only use cookies for session ID
    ini_set('session.cookie_samesite', 'Strict'); // CSRF protection
    
    // Set secure flag in production
    if (APP_ENV === 'production') {
        ini_set('session.cookie_secure', 1); // Only send cookie over HTTPS
    }
    
    // Set session name
    session_name(SESSION_NAME);
    
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check for session timeout
    checkSessionTimeout();
}

/**
 * Regenerate session ID to prevent session fixation attacks
 * Should be called after successful login
 */
function regenerateSession() {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

/**
 * Check if session has timed out
 */
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity'])) {
        $elapsed = time() - $_SESSION['last_activity'];
        
        if ($elapsed > SESSION_LIFETIME) {
            // Session has expired
            session_unset();
            session_destroy();
            header('Location: ' . getBaseUrl() . '/index.php?error=session_expired');
            exit;
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
}

/**
 * Destroy session completely
 */
function destroySession() {
    $_SESSION = array();
    
    // Delete session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user']) && isset($_SESSION['user']['id']);
}

/**
 * Check if user has a specific role
 * @param string $role The role to check
 * @return bool
 */
function hasRole($role) {
    return isLoggedIn() && $_SESSION['user']['role'] === $role;
}

/**
 * Require login - redirect to index if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . getBaseUrl() . '/index.php');
        exit;
    }
}

/**
 * Require specific role - redirect if user doesn't have it
 * @param string $role Required role
 */
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: ' . getBaseUrl() . '/index.php?error=access_denied');
        exit;
    }
}

/**
 * Get base URL for the application
 * @return string
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = dirname($_SERVER['SCRIPT_NAME']);
    return $protocol . '://' . $host . rtrim($script, '/');
}
?>
