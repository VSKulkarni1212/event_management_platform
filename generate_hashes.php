<?php
// Generate correct password hashes
echo "<h2>Password Hash Generator</h2>";

$passwords = [
    'Admin@123' => password_hash('Admin@123', PASSWORD_DEFAULT),
    'Organizer@123' => password_hash('Organizer@123', PASSWORD_DEFAULT),
    'Attendee@123' => password_hash('Attendee@123', PASSWORD_DEFAULT),
];

foreach ($passwords as $password => $hash) {
    echo "<strong>$password:</strong><br>";
    echo "<code>$hash</code><br><br>";
    
    // Verify it works
    $verify = password_verify($password, $hash);
    echo "Verification: " . ($verify ? "✅ WORKS" : "❌ FAILED") . "<br><hr>";
}

echo "<h3>SQL to Fix Users Table:</h3>";
echo "<pre>";
echo "-- Run this in phpMyAdmin SQL tab:\n\n";
echo "UPDATE users SET password = '" . $passwords['Admin@123'] . "' WHERE email = 'admin@eventplatform.local';\n";
echo "UPDATE users SET password = '" . $passwords['Organizer@123'] . "' WHERE email = 'organizer@example.com';\n";
echo "UPDATE users SET password = '" . $passwords['Attendee@123'] . "' WHERE email = 'attendee@example.com';\n";
echo "</pre>";
?>
