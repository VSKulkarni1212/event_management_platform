<?php
require '../includes/config.php';
require '../includes/session.php';
require '../includes/csrf.php';
require '../includes/db.php';
require '../includes/mailer.php';

// Initialize secure session
initSecureSession();

// Require attendee role
requireRole('attendee');

$attendeeId = $_SESSION['user']['id'];

// Verify CSRF token
verifyCsrfToken();

$eventId = isset($_POST['event_id']) ? (int)$_POST['event_id'] : null;

if (!$eventId) {
  header("Location: ../dashboard_attendee.php?error=invalid_request");
  exit;
}

// Use transaction to prevent race conditions
try {
  $pdo->beginTransaction();
  
  // Fetch event details with lock
  $stmt = $pdo->prepare("
    SELECT e.*, u.name as organizer_name, u.email as organizer_email
    FROM events e
    JOIN users u ON e.organizer_id = u.id
    WHERE e.id = ? AND e.status = 'approved'
    FOR UPDATE
  ");
  $stmt->execute([$eventId]);
  $event = $stmt->fetch();
  
  if (!$event) {
    $pdo->rollBack();
    header("Location: ../dashboard_attendee.php?error=event_not_found");
    exit;
  }
  
  // Check if event is in the past
  $eventDate = new DateTime($event['date']);
  $now = new DateTime();
  if ($eventDate < $now) {
    $pdo->rollBack();
    header("Location: ../dashboard_attendee.php?error=event_passed");
    exit;
  }
  
  // Check if event is full
  $rsvpStmt = $pdo->prepare("SELECT COUNT(*) FROM rsvps WHERE event_id = ?");
  $rsvpStmt->execute([$eventId]);
  $currentCount = $rsvpStmt->fetchColumn();
  
  if ($currentCount >= $event['max_attendees']) {
    $pdo->rollBack();
    header("Location: ../dashboard_attendee.php?error=event_full");
    exit;
  }
  
  // Insert RSVP
  $insertStmt = $pdo->prepare("INSERT INTO rsvps (event_id, attendee_id) VALUES (?, ?)");
  $insertStmt->execute([$eventId, $attendeeId]);
  
  $pdo->commit();
  
  // Send confirmation email
  sendRSVPConfirmation($_SESSION['user']['email'], $_SESSION['user']['name'], $event);
  
  header("Location: ../dashboard_attendee.php?success=rsvp_confirmed");
  exit;
  
} catch (PDOException $e) {
  $pdo->rollBack();
  
  // Check if it's a duplicate entry error
  if ($e->getCode() == 23000) {
    header("Location: ../dashboard_attendee.php?error=already_registered");
  } else {
    error_log("RSVP error: " . $e->getMessage());
    header("Location: ../dashboard_attendee.php?error=rsvp_failed");
  }
  exit;
}
?>