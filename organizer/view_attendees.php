<?php
require '../includes/config.php';
require '../includes/session.php';
require '../includes/db.php';

// Initialize secure session
initSecureSession();

// Require organizer role
requireRole('organizer');

$user = $_SESSION['user'];
$eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : null;

if (!$eventId) {
  header("Location: ../dashboard_organizer.php");
  exit;
}

// Verify event ownership
$stmt = $pdo->prepare("SELECT title FROM events WHERE id = ? AND organizer_id = ?");
$stmt->execute([$eventId, $user['id']]);
$event = $stmt->fetch();

if (!$event) {
  header("Location: ../dashboard_organizer.php?error=event_not_found");
  exit;
}

// Fetch attendees
$attendeeStmt = $pdo->prepare("
  SELECT users.name, users.email, rsvps.created_at
  FROM rsvps
  JOIN users ON rsvps.attendee_id = users.id
  WHERE rsvps.event_id = ?
  ORDER BY rsvps.created_at DESC
");
$attendeeStmt->execute([$eventId]);
$attendees = $attendeeStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attendees - <?= htmlspecialchars($event['title']) ?></title>
  <link rel="stylesheet" href="../assets/css/view_attendees.css">
</head>
<body>
  <div class="container">
    <h2>Attendees for "<?= htmlspecialchars($event['title']) ?>"</h2>

    <?php if (count($attendees) > 0): ?>
      <p class="attendee-count">Total RSVPs: <strong><?= count($attendees) ?></strong></p>
      
      <table class="attendee-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Email</th>
            <th>RSVP Date</th>
          </tr>
        </thead>
        <tbody>
          <?php $counter = 1; ?>
          <?php foreach ($attendees as $a): ?>
            <tr>
              <td><?= $counter++ ?></td>
              <td><?= htmlspecialchars($a['name']) ?></td>
              <td><?= htmlspecialchars($a['email']) ?></td>
              <td><?= date('M j, Y g:i A', strtotime($a['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p class="no-attendees">No attendees have RSVP'd yet.</p>
    <?php endif; ?>

    <p class="back-link">
      <a href="../dashboard_organizer.php" class="back-btn">← Back to Dashboard</a>
    </p>
  </div>
</body>
</html>
