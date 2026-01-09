<?php
require '../includes/config.php';
require '../includes/session.php';
require '../includes/csrf.php';
require '../includes/validation.php';
require '../includes/db.php';

// Initialize secure session
initSecureSession();

// Require organizer role
requireRole('organizer');

$user = $_SESSION['user'];
$eventId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$errors = [];

if (!$eventId) {
  header("Location: ../dashboard_organizer.php");
  exit;
}

// Fetch event - ensure it belongs to this organizer
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND organizer_id = ?");
$stmt->execute([$eventId, $user['id']]);
$event = $stmt->fetch();

if (!$event) {
  header("Location: ../dashboard_organizer.php?error=event_not_found");
  exit;
}

// Get current RSVP count
$rsvpStmt = $pdo->prepare("SELECT COUNT(*) FROM rsvps WHERE event_id = ?");
$rsvpStmt->execute([$eventId]);
$currentRSVPs = $rsvpStmt->fetchColumn();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
  
  if (!validateDate($date, true)) { // Allow past dates for editing
    $errors[] = "Invalid date format.";
  }
  
  // Prevent reducing max attendees below current RSVPs
  if ($maxAttendees < $currentRSVPs) {
    $errors[] = "Cannot reduce max attendees below current RSVP count ($currentRSVPs).";
  }
  
  if (!validateInteger($maxAttendees, 1, 10000)) {
    $errors[] = "Max attendees must be between 1 and 10,000.";
  }
  
  // Handle image upload
  $imageName = $event['image'];
  if (!empty($_FILES['image']['name'])) {
    if (validateImageUpload($_FILES['image'], $errors)) {
      $newImageName = sanitizeFilename($_FILES['image']['name']);
      $targetPath = '../uploads/' . $newImageName;
      
      if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
        // Delete old image
        if ($imageName && file_exists("../uploads/$imageName")) {
          unlink("../uploads/$imageName");
        }
        $imageName = $newImageName;
      } else {
        $errors[] = "Failed to upload new image.";
      }
    }
  }
  
  // Update event if no errors
  if (empty($errors)) {
    try {
      $stmt = $pdo->prepare("
        UPDATE events
        SET title = ?, description = ?, date = ?, image = ?, max_attendees = ?, location = ?
        WHERE id = ? AND organizer_id = ?
      ");
      $stmt->execute([$title, $description, $date, $imageName, $maxAttendees, $location, $eventId, $user['id']]);
      
      header("Location: ../dashboard_organizer.php?success=event_updated");
      exit;
    } catch (PDOException $e) {
      $errors[] = "Failed to update event. Please try again.";
      error_log("Event update error: " . $e->getMessage());
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Event - <?= APP_NAME ?></title>
  <link rel="stylesheet" href="../assets/css/edit_event.css?v=2">
</head>
<body>
  <div class="container">
    <header class="headbar">
      <h2>Edit Event</h2>
      <a class="btn btn-secondary" href="../dashboard_organizer.php">← Back to Dashboard</a>
    </header>

    <?= displayErrors($errors) ?>
    
    <?php if ($event['status'] !== 'approved'): ?>
      <div class="info-message">
        <strong>Note:</strong> This event is currently <strong><?= $event['status'] ?></strong>. 
        Changes will <?= $event['status'] === 'pending' ? 'be reviewed by admin' : 'require admin re-approval' ?>.
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="edit-form">
      <?php csrfField(); ?>
      
      <label for="title">Title *</label>
      <input id="title" type="text" name="title" value="<?= htmlspecialchars($event['title']) ?>" required maxlength="255">

      <label for="description">Description *</label>
      <textarea id="description" name="description" required rows="5"><?= htmlspecialchars($event['description']) ?></textarea>

      <div class="row">
        <div class="col">
          <label for="date">Date *</label>
          <input id="date" type="date" name="date" value="<?= htmlspecialchars(substr($event['date'], 0, 10)) ?>" required>
        </div>
        <div class="col">
          <label for="max_attendees">Max Attendees *</label>
          <input id="max_attendees" type="number" name="max_attendees" value="<?= htmlspecialchars($event['max_attendees']) ?>" min="<?= $currentRSVPs ?>" max="10000" required>
          <small>Current RSVPs: <?= $currentRSVPs ?></small>
        </div>
      </div>

      <label for="location">Location *</label>
      <input id="location" type="text" name="location" value="<?= htmlspecialchars($event['location']) ?>" required maxlength="255">

      <div class="image-section">
        <p class="image-title">Current Image</p>
        <?php if (!empty($event['image']) && file_exists("../uploads/".$event['image'])): ?>
          <img class="preview" src="../uploads/<?= urlencode($event['image']) ?>" alt="Event Image">
        <?php else: ?>
          <p class="muted">No image available.</p>
        <?php endif; ?>

        <label class="file-label" for="image">Upload New Image (Optional)</label>
        <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/gif">
        <small class="help-text">Max file size: <?= MAX_FILE_SIZE / 1048576 ?>MB. Allowed: JPG, PNG, GIF</small>
      </div>

      <button type="submit" class="btn btn-primary">Update Event</button>
    </form>
  </div>
</body>
</html>
