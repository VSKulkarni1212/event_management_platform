<?php
require '../includes/config.php';
require '../includes/session.php';
require '../includes/csrf.php';
require '../includes/validation.php';
require '../includes/db.php';
require '../includes/mailer.php';

// Initialize secure session
initSecureSession();

// Require organizer role
requireRole('organizer');

$user = $_SESSION['user'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Verify CSRF token
  verifyCsrfToken();
  
  $title = trim($_POST['title']);
  $description = trim($_POST['description']);
  $date = $_POST['date'];
  $maxAttendees = (int)$_POST['max_attendees'];
  $location = trim($_POST['location']);
  
  // Validate inputs
  if (empty($title) || empty($description) || empty($date) || empty($location)) {
    $errors[] = "All fields are required.";
  }
  
  if (!validateDate($date)) {
    $errors[] = "Invalid date. Event date must be in the future.";
  }
  
  if (!validateInteger($maxAttendees, 1, 10000)) {
    $errors[] = "Max attendees must be between 1 and 10,000.";
  }
  
  // Handle image upload
  $imageName = null;
  if (!empty($_FILES['image']['name'])) {
    if (validateImageUpload($_FILES['image'], $errors)) {
      $imageName = sanitizeFilename($_FILES['image']['name']);
      $targetPath = '../uploads/' . $imageName;
      
      if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
        $errors[] = "Failed to save uploaded image.";
      }
    }
  }
  
  // Save event if no errors
  if (empty($errors)) {
    try {
      // Events are now created with 'pending' status for admin approval
      $stmt = $pdo->prepare("
        INSERT INTO events (title, description, date, image, organizer_id, max_attendees, location, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
      ");
      $stmt->execute([$title, $description, $date, $imageName, $user['id'], $maxAttendees, $location]);
      
      // Get the created event
      $eventId = $pdo->lastInsertId();
      $event = [
        'id' => $eventId,
        'title' => $title,
        'description' => $description,
        'date' => $date,
        'location' => $location,
        'max_attendees' => $maxAttendees
      ];
      
      // Send email notification
      sendEventCreatedNotification($user['email'], $user['name'], $event);
      
      // Redirect with success message
      header("Location: ../dashboard_organizer.php?success=event_created");
      exit;
    } catch (PDOException $e) {
      $errors[] = "Failed to create event. Please try again.";
      error_log("Event creation error: " . $e->getMessage());
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Event - <?= APP_NAME ?></title>
  <link rel="stylesheet" href="../assets/css/create_event.css">
</head>

<body>
  <div class="form-container">
    <h2>Create New Event</h2>
    <p class="info-text">Your event will be submitted for admin approval before it becomes visible to attendees.</p>
    
    <?= displayErrors($errors) ?>
    
    <form method="POST" enctype="multipart/form-data">
      <?php csrfField(); ?>
      
      <div class="form-group">
        <label for="title">Event Title *</label>
        <input 
          type="text" 
          id="title"
          name="title" 
          placeholder="Enter event title" 
          value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>"
          required
          maxlength="255"
        />
      </div>
      
      <div class="form-group">
        <label for="description">Event Description *</label>
        <textarea 
          id="description"
          name="description" 
          placeholder="Describe your event..." 
          required
          rows="5"
        ><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label for="date">Event Date *</label>
          <input 
            type="date" 
            id="date"
            name="date" 
            value="<?= isset($_POST['date']) ? htmlspecialchars($_POST['date']) : '' ?>"
            min="<?= date('Y-m-d') ?>"
            required
          />
        </div>
        
        <div class="form-group">
          <label for="max_attendees">Max Attendees *</label>
          <input 
            type="number" 
            id="max_attendees"
            name="max_attendees" 
            placeholder="e.g., 100" 
            value="<?= isset($_POST['max_attendees']) ? htmlspecialchars($_POST['max_attendees']) : '50' ?>"
            min="1" 
            max="10000"
            required
          />
        </div>
      </div>
      
      <div class="form-group">
        <label for="location">Event Location *</label>
        <input 
          type="text" 
          id="location"
          name="location" 
          placeholder="Enter event location" 
          value="<?= isset($_POST['location']) ? htmlspecialchars($_POST['location']) : '' ?>"
          required
          maxlength="255"
        />
      </div>
      
      <div class="form-group">
        <label for="image">Event Image (Optional)</label>
        <input 
          type="file" 
          id="image"
          name="image" 
          accept="image/jpeg,image/png,image/gif"
        />
        <small class="help-text">
          Max file size: <?= MAX_FILE_SIZE / 1048576 ?>MB. Allowed formats: JPG, PNG, GIF
        </small>
      </div>
      
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Create Event</button>
        <a href="../dashboard_organizer.php" class="btn btn-secondary">Cancel</a>
      </div>
    </form>
  </div>
</body>
</html>
