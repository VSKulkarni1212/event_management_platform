<?php
// Test script to debug login issue
require 'includes/config.php';
require 'includes/db.php';

echo "<h2>Database Connection Test</h2>";

// Test 1: Check database connection
try {
    echo "✅ Database connected successfully<br>";
    echo "Database: " . DB_NAME . "<br><br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    exit;
}

// Test 2: Check if admin user exists
echo "<h3>Test 2: Check Admin User</h3>";
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute(['admin@eventplatform.local']);
$user = $stmt->fetch();

if ($user) {
    echo "✅ Admin user found<br>";
    echo "Name: " . htmlspecialchars($user['name']) . "<br>";
    echo "Email: " . htmlspecialchars($user['email']) . "<br>";
    echo "Role: " . htmlspecialchars($user['role']) . "<br>";
    echo "Password hash: " . substr($user['password'], 0, 20) . "...<br><br>";
} else {
    echo "❌ Admin user NOT found<br><br>";
}

// Test 3: Test password verification
echo "<h3>Test 3: Password Verification</h3>";
$testPassword = 'Admin@123';
$correctHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

if ($user) {
    $result = password_verify($testPassword, $user['password']);
    echo "Testing password: 'Admin@123'<br>";
    echo "Against hash in database<br>";
    echo "Result: " . ($result ? "✅ MATCH" : "❌ NO MATCH") . "<br><br>";
    
    // Test with correct hash
    $result2 = password_verify($testPassword, $correctHash);
    echo "Testing against correct hash<br>";
    echo "Result: " . ($result2 ? "✅ MATCH" : "❌ NO MATCH") . "<br><br>";
    
    // Show if hashes match
    echo "Database hash matches correct hash: " . ($user['password'] === $correctHash ? "✅ YES" : "❌ NO") . "<br>";
    echo "Database hash: " . $user['password'] . "<br>";
    echo "Correct hash:  " . $correctHash . "<br>";
}

// Test 4: Create a fresh password hash
echo "<h3>Test 4: Generate Fresh Hash</h3>";
$freshHash = password_hash('Admin@123', PASSWORD_DEFAULT);
echo "Fresh hash for 'Admin@123': " . $freshHash . "<br>";
$verify = password_verify('Admin@123', $freshHash);
echo "Verification: " . ($verify ? "✅ WORKS" : "❌ FAILED") . "<br>";
?>
