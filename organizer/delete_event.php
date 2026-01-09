<?php
require '../includes/config.php';
require '../includes/session.php';
require '../includes/csrf.php';
require '../includes/db.php';

// Initialize secure session
initSecureSession();

// Require organizer role
requireRole('organizer');

$user = $_SESSION['user'];
$eventId = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$eventId) {
  header("Location: ../dashboard_organizer.php");
  exit;
}

// Verify event belongs to this organizer
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND organizer_id = ?");
$stmt->execute([$eventId, $user['id']]);
$event = $stmt->fetch();

if (!$event) {
  header("Location: ../dashboard_organizer.php?error=event_not_found");
  exit;
}

// Handle POST request (actual deletion)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrfToken();
  
  try {
    // Delete event image if exists
    if ($event['image'] && file_exists("../uploads/" . $event['image'])) {
      unlink("../uploads/" . $event['image']);
    }
    
    // Delete event (RSVPs will be deleted automatically due to CASCADE)
    $deleteStmt = $pdo->prepare("DELETE FROM events WHERE id = ? AND organizer_id = ?");
    $deleteStmt->execute([$eventId, $user['id']]);
    
    header("Location: ../dashboard_organizer.php?success=event_deleted");
    exit;
  } catch (PDOException $e) {
    error_log("Event deletion error: " . $e->getMessage());
    header("Location: ../dashboard_organizer.php?error=delete_failed");
    exit;
  }
}

// Show confirmation page for GET request
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Delete Event - <?= APP_NAME ?></title>
  <link rel="stylesheet" href="../assets/css/edit_event.css">
</head>
<body>
  <div class="container">
    <header class="headbar">
      <h2>Delete Event</h2>
      <a class="btn btn-secondary" href="../dashboard_organizer.php">← Back to Dashboard</a>
    </header>

    <div class="confirm-delete">
      <h3>Are you sure you want to delete this event?</h3>
      
      <div class="event-preview">
        <h4><?= htmlspecialchars($event['title']) ?></h4>
        <p><?= htmlspecialchars($event['description']) ?></p>
        <p><strong>Date:</strong> <?= date('F j, Y', strtotime($event['date'])) ?></p>
        <p><strong>Location:</strong> <?= htmlspecialchars($event['location']) ?></p>
        
        <?php
        $rsvpStmt = $pdo->prepare("SELECT COUNT(*) FROM rsvps WHERE event_id = ?");
        $rsvpStmt->execute([$eventId]);
        $rsvpCount = $rsvpStmt->fetchColumn();
        ?>
        <p><strong>Current RSVPs:</strong> <?= $rsvpCount ?></p>
      </div>
      
      <?php if ($rsvpCount > 0): ?>
        <div class="warning-box">
          <strong>⚠️ Warning:</strong> This event has <?= $rsvpCount ?> RSVP(s). 
          Deleting it will remove all attendee registrations.
        </div>
      <?php endif; ?>
      
      <p class="warning-text">This action cannot be undone.</p>
      
      <form method="POST" style="display: inline;">
        <?php csrfField(); ?>
        <button type="submit" class="btn btn-danger">Yes, Delete Event</button>
      </form>
      
      <a href="../dashboard_organizer.php" class="btn btn-secondary">Cancel</a>
    </div>
  </div>
</body>
</html>