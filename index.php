<?php
require 'includes/config.php';
require 'includes/session.php';
require 'includes/csrf.php';
require 'includes/validation.php';
require 'includes/db.php';

// Initialize secure session
initSecureSession();

// Redirect if already logged in
if (isLoggedIn()) {
  $role = $_SESSION['user']['role'];
  if ($role === 'admin') {
    header("Location: dashboard_admin.php");
  } elseif ($role === 'organizer') {
    header("Location: dashboard_organizer.php");
  } elseif ($role === 'attendee') {
    header("Location: dashboard_attendee.php");
  }
  exit;
}

$errors = [];
$success = '';

// Handle session expired message
if (isset($_GET['error']) && $_GET['error'] === 'session_expired') {
  $errors[] = "Your session has expired. Please log in again.";
}

if (isset($_GET['error']) && $_GET['error'] === 'access_denied') {
  $errors[] = "Access denied. Please log in with appropriate credentials.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Verify CSRF token
  verifyCsrfToken();
  
  $email = trim($_POST['email']);
  $password = $_POST['password'];
  
  // Validate inputs
  if (empty($email) || empty($password)) {
    $errors[] = "Email and password are required.";
  } elseif (!validateEmail($email)) {
    $errors[] = "Invalid email format.";
  } else {
    // Attempt login
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
      // Successful login
      $_SESSION['user'] = $user;
      
      // Regenerate session ID to prevent session fixation
      regenerateSession();
      
      // Redirect based on role
      if ($user['role'] === 'admin') {
        header("Location: dashboard_admin.php");
      } elseif ($user['role'] === 'organizer') {
        header("Location: dashboard_organizer.php");
      } elseif ($user['role'] === 'attendee') {
        header("Location: dashboard_attendee.php");
      } else {
        $errors[] = "Unknown role. Please contact admin.";
      }
      exit;
    } else {
      // Generic error message to prevent user enumeration
      $errors[] = "Invalid email or password.";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - <?= APP_NAME ?></title>
  <link rel="stylesheet" href="assets/css/auth.css">
</head>

<body>
  <div class="auth-container">
    <h2>Login to <?= APP_NAME ?></h2>
    
    <?= displayErrors($errors) ?>
    
    <form method="POST" action="">
      <?php csrfField(); ?>
      
      <div class="form-group">
        <label for="email">Email Address</label>
        <input 
          type="email" 
          id="email"
          name="email" 
          placeholder="Enter your email" 
          value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
          required 
          autofocus
        />
      </div>
      
      <div class="form-group">
        <label for="password">Password</label>
        <input 
          type="password" 
          id="password"
          name="password" 
          placeholder="Enter your password" 
          required 
        />
      </div>
      
      <button type="submit" class="btn-primary">Login</button>
    </form>
    
    <p class="auth-link">
      New user? <a href="auth/register.php">Register here</a>
    </p>
    
    <div class="demo-credentials" style="margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 4px; font-size: 0.9em;">
      <strong>Demo Credentials:</strong><br>
      Admin: admin@eventplatform.local / Admin@123<br>
      Organizer: organizer@example.com / Organizer@123<br>
      Attendee: attendee@example.com / Attendee@123
    </div>
  </div>
</body>
</html>