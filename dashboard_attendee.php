<?php
require 'includes/config.php';
require 'includes/session.php';
require 'includes/csrf.php';
require 'includes/db.php';

// Initialize secure session
initSecureSession();

// Require attendee role
requireRole('attendee');

$user = $_SESSION['user'];
$message = '';
$errors = [];

// Handle success/error messages
if (isset($_GET['success'])) {
  switch ($_GET['success']) {
    case 'rsvp_confirmed':
      $message = "RSVP confirmed! You will receive a confirmation email.";
      break;
    case 'rsvp_cancelled':
      $message = "RSVP cancelled successfully.";
      break;
  }
}

if (isset($_GET['error'])) {
  switch ($_GET['error']) {
    case 'event_not_found':
      $errors[] = "Event not found.";
      break;
    case 'event_passed':
      $errors[] = "Cannot RSVP — event has already passed.";
      break;
    case 'event_full':
      $errors[] = "Cannot RSVP — event is full.";
      break;
    case 'already_registered':
      $errors[] = "You have already RSVP'd to this event.";
      break;
    case 'cancel_locked':
      $errors[] = "Cannot cancel — event has passed.";
      break;
    case 'rsvp_failed':
      $errors[] = "RSVP failed. Please try again.";
      break;
  }
}

// Fetch only APPROVED events with organizer info
$stmt = $pdo->query("
  SELECT events.*, users.email AS organizer_email, users.name AS organizer_name
  FROM events 
  JOIN users ON events.organizer_id = users.id 
  WHERE events.status = 'approved'
  ORDER BY events.date ASC
");
$events = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attendee Dashboard - <?= APP_NAME ?></title>
  <link rel="stylesheet" href="assets/css/attendee.css?v=2">
</head>
<body>
  <div class="dashboard-container">
    <a href="logout.php" class="logout-link">Logout</a>

    <h2>Welcome, <?= htmlspecialchars($user['name']) ?> (Attendee)</h2>

    <?php if ($message): ?>
      <p class="success-msg">✓ <?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
      <div class="error-msg">
        <?php foreach ($errors as $error): ?>
          <p>✗ <?= htmlspecialchars($error) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <h3>Available Events</h3>
    <div class="events-grid">
      <?php if (count($events) > 0): ?>
        <?php foreach ($events as $event): ?>
          <?php
            $rsvpStmt = $pdo->prepare("SELECT COUNT(*) FROM rsvps WHERE event_id = ?");
            $rsvpStmt->execute([$event['id']]);
            $currentCount = $rsvpStmt->fetchColumn();

            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM rsvps WHERE event_id = ? AND attendee_id = ?");
            $checkStmt->execute([$event['id'], $user['id']]);
            $alreadyRSVPed = $checkStmt->fetchColumn() > 0;

            $eventDate = new DateTime($event['date']);
            $now = new DateTime();
            $isPast = $eventDate < $now;
            $interval = $now->diff($eventDate);
            $eventDateOnly = $eventDate->format('Y-m-d');
          ?>
          <div class="event-card">
            <h4><?= htmlspecialchars($event['title']) ?></h4>
            <p><?= htmlspecialchars($event['description']) ?></p>
            
            <?php if ($event['image']): ?>
              <img src="uploads/<?= urlencode($event['image']) ?>" alt="Event Image">
            <?php endif; ?>
            
            <p><strong>Date:</strong> <?= $eventDateOnly ?></p>
            <p><strong>Location:</strong> <?= htmlspecialchars($event['location']) ?></p>
            <p><strong>Organizer:</strong> <?= htmlspecialchars($event['organizer_name']) ?></p>
            <p><strong>Contact:</strong> 
              <a href="mailto:<?= htmlspecialchars($event['organizer_email']) ?>">
                <?= htmlspecialchars($event['organizer_email']) ?>
              </a>
            </p>
            <p><strong>Spots:</strong> <?= $currentCount ?> / <?= $event['max_attendees'] ?></p>

            <?php if (!$isPast): ?>
              <p class="countdown">📅 <?= $interval->days ?> day(s) left</p>
            <?php else: ?>
              <p class="past-event">Event was <?= $interval->days ?> day(s) ago</p>
            <?php endif; ?>

            <?php if ($alreadyRSVPed): ?>
              <p class="rsvp-status">✓ You have RSVP'd</p>
              <?php if (!$isPast): ?>
                <form method="POST" action="attendee/cancel_rsvp.php">
                  <?php csrfField(); ?>
                  <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                  <button type="submit" class="cancel-btn" onclick="return confirm('Cancel your RSVP?')">Cancel RSVP</button>
                </form>
              <?php else: ?>
                <p class="locked">RSVP locked — event has passed</p>
              <?php endif; ?>
            <?php elseif ($isPast): ?>
              <p class="past-event">Event has passed</p>
            <?php elseif ($currentCount >= $event['max_attendees']): ?>
              <p class="full-event">Event is full</p>
            <?php else: ?>
              <form method="POST" action="attendee/rsvp.php">
                <?php csrfField(); ?>
                <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                <button type="submit" class="rsvp-btn">RSVP Now</button>
              </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No approved events available at this time.</p>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
