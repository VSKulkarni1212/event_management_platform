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

// Fetch event details
$stmt = $pdo->prepare("
  SELECT e.*, u.name as organizer_name, u.email as organizer_email
  FROM events e
  JOIN users u ON e.organizer_id = u.id
  WHERE e.id = ?
");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
  header("Location: ../dashboard_attendee.php?error=event_not_found");
  exit;
}

// Check if event has passed
$eventDate = new DateTime($event['date']);
$now = new DateTime();
if ($eventDate < $now) {
  header("Location: ../dashboard_attendee.php?error=cancel_locked");
  exit;
}

// Delete RSVP
try {
  $deleteStmt = $pdo->prepare("DELETE FROM rsvps WHERE event_id = ? AND attendee_id = ?");
  $deleteStmt->execute([$eventId, $attendeeId]);
  
  // Send cancellation email
  sendRSVPCancellation($_SESSION['user']['email'], $_SESSION['user']['name'], $event);
  
  header("Location: ../dashboard_attendee.php?success=rsvp_cancelled");
  exit;
  
} catch (PDOException $e) {
  error_log("Cancel RSVP error: " . $e->getMessage());
  header("Location: ../dashboard_attendee.php?error=cancel_failed");
  exit;
}
?>