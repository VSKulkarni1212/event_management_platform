<?php
/**
 * Database Connection
 * Uses environment variables from config.php
 */

require_once __DIR__ . '/config.php';

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
  $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (\PDOException $e) {
  // Don't expose database details in production
  if (APP_ENV === 'development') {
    die("Database connection failed: " . $e->getMessage());
  } else {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please contact support.");
  }
}
?>