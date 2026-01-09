<?php
require '../includes/config.php';
require '../includes/session.php';
require '../includes/csrf.php';
require '../includes/validation.php';
require '../includes/db.php';

// Initialize secure session
initSecureSession();

// Redirect if already logged in
if (isLoggedIn()) {
  $role = $_SESSION['user']['role'];
  header("Location: ../dashboard_{$role}.php");
  exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Verify CSRF token
  verifyCsrfToken();
  
  $name = trim($_POST['name']);
  $email = trim($_POST['email']);
  $password = $_POST['password'];
  $confirmPassword = $_POST['confirm_password'];
  $role = $_POST['role'];
  
  // Validate inputs
  if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
    $errors[] = "All fields are required.";
  }
  
  if (!validateEmail($email)) {
    $errors[] = "Invalid email format.";
  }
  
  if ($password !== $confirmPassword) {
    $errors[] = "Passwords do not match.";
  }
  
  if (!validatePassword($password, $errors)) {
    // Errors already added by validatePassword
  }
  
  if (!in_array($role, ['organizer', 'attendee'])) {
    $errors[] = "Invalid role selected.";
  }
  
  // Check if email already exists
  if (empty($errors)) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
      $errors[] = "Email address is already registered.";
    }
  }
  
  // Register user if no errors
  if (empty($errors)) {
    try {
      $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
      $stmt->execute([$name, $email, $hashedPassword, $role]);
      
      // Redirect to login page with success message
      header("Location: ../index.php?registered=1");
      exit;
    } catch (PDOException $e) {
      $errors[] = "Registration failed. Please try again.";
      error_log("Registration error: " . $e->getMessage());
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register - <?= APP_NAME ?></title>
  <link rel="stylesheet" href="../assets/css/auth.css">
</head>

<body>
  <div class="auth-container">
    <h2>Register for <?= APP_NAME ?></h2>
    
    <?= displayErrors($errors) ?>
    
    <form method="POST" action="">
      <?php csrfField(); ?>
      
      <div class="form-group">
        <label for="name">Full Name</label>
        <input 
          type="text" 
          id="name"
          name="name" 
          placeholder="Enter your full name" 
          value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>"
          required 
          autofocus
        />
      </div>
      
      <div class="form-group">
        <label for="email">Email Address</label>
        <input 
          type="email" 
          id="email"
          name="email" 
          placeholder="Enter your email" 
          value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
          required 
        />
      </div>
      
      <div class="form-group">
        <label for="password">Password</label>
        <input 
          type="password" 
          id="password"
          name="password" 
          placeholder="Enter password" 
          required 
        />
        <small style="color: #666; font-size: 0.85em;">
          Must be at least <?= PASSWORD_MIN_LENGTH ?> characters with uppercase, lowercase, and number
        </small>
      </div>
      
      <div class="form-group">
        <label for="confirm_password">Confirm Password</label>
        <input 
          type="password" 
          id="confirm_password"
          name="confirm_password" 
          placeholder="Confirm password" 
          required 
        />
      </div>
      
      <div class="form-group">
        <label for="role">Select Role</label>
        <select name="role" id="role" required>
          <option value="attendee" <?= (isset($_POST['role']) && $_POST['role'] === 'attendee') ? 'selected' : '' ?>>
            Attendee (Browse and RSVP to events)
          </option>
          <option value="organizer" <?= (isset($_POST['role']) && $_POST['role'] === 'organizer') ? 'selected' : '' ?>>
            Organizer (Create and manage events)
          </option>
        </select>
      </div>
      
      <button type="submit" class="btn-primary">Register</button>
    </form>
    
    <p class="auth-link">
      Already registered? <a href="../index.php">Login here</a>
    </p>
  </div>
</body>
</html>
