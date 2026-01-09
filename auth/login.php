<?php
session_start();
require '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email']);
  $password = $_POST['password'];

  $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
  $stmt->execute([$email]);
  $user = $stmt->fetch();

  if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user'] = $user;

    // Redirect based on role
    if ($user['role'] === 'organizer') {
      header("Location: ../dashboard_organizer.php");
    } elseif ($user['role'] === 'attendee') {
      header("Location: ../dashboard_attendee.php");
    } else {
      echo "Unknown role. Please contact admin.";
    }
    exit;
  } else {
    echo "Invalid credentials";
  }
}
?>

<form method="POST">
  <input type="email" name="email" placeholder="Email" required />
  <input type="password" name="password" placeholder="Password" required />
  <button type="submit">Login</button>
</form>