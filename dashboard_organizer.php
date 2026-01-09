<?php
require 'includes/config.php';
require 'includes/session.php';
require 'includes/db.php';

// Initialize secure session
initSecureSession();

// Require organizer role
requireRole('organizer');

$user = $_SESSION['user'];
$message = '';

// Handle success messages
if (isset($_GET['success'])) {
  switch ($_GET['success']) {
    case 'event_created':
      $message = "Event created successfully! It is pending admin approval.";
      break;
    case 'event_updated':
      $message = "Event updated successfully!";
      break;
    case 'event_deleted':
      $message = "Event deleted successfully!";
      break;
  }
}

// Fetch organizer's events with status
$stmt = $pdo->prepare("
  SELECT * FROM events 
  WHERE organizer_id = ? 
  ORDER BY 
    CASE status 
      WHEN 'pending' THEN 1
      WHEN 'approved' THEN 2
      WHEN 'rejected' THEN 3
    END,
    date ASC
");
$stmt->execute([$user['id']]);
$events = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Organizer Dashboard - <?= APP_NAME ?></title>
  <link rel="stylesheet" href="assets/css/organizer.css?v=3">
</head>
<body>
  <header class="navbar">
    <h1>Welcome, <?= htmlspecialchars($user['name']) ?> <span class="role-badge">Organizer</span></h1>
    <a href="logout.php" class="btn btn-logout">Logout</a>
  </header>

  <main class="container">
    <?php if ($message): ?>
      <div class="success-message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <div class="container-head">
      <h2>Your Events</h2>
      <a href="organizer/create_event.php" class="btn btn-primary">+ Create New Event</a>
    </div>

    <?php if (count($events) > 0): ?>
      <section class="event-grid">
        <?php foreach ($events as $event): ?>
          <?php
            $rsvpStmt = $pdo->prepare("SELECT COUNT(*) FROM rsvps WHERE event_id = ?");
            $rsvpStmt->execute([$event['id']]);
            $attendeeCount = $rsvpStmt->fetchColumn();
            $eventDateOnly = (new DateTime($event['date']))->format('Y-m-d');
          ?>
          <article class="event-card status-<?= $event['status'] ?>">
            <?php if ($event['image']): ?>
              <img src="uploads/<?= urlencode($event['image']) ?>" alt="Event Image" class="event-img">
            <?php endif; ?>

            <div class="event-body">
              <div class="event-header">
                <h3 class="event-title"><?= htmlspecialchars($event['title']) ?></h3>
                <span class="status-badge status-<?= $event['status'] ?>">
                  <?= ucfirst($event['status']) ?>
                </span>
              </div>
              
              <p class="event-desc"><?= htmlspecialchars($event['description']) ?></p>
              
              <div class="event-meta">
                <p><strong>Date:</strong> <?= $eventDateOnly ?></p>
                <p><strong>Location:</strong> <?= htmlspecialchars($event['location']) ?></p>
                <p><strong>Attendees:</strong> <?= $attendeeCount ?> / <?= $event['max_attendees'] ?></p>
              </div>
              
              <?php if ($event['status'] === 'pending'): ?>
                <div class="pending-notice">
                  ⏳ Awaiting admin approval
                </div>
              <?php elseif ($event['status'] === 'rejected'): ?>
                <div class="rejected-notice">
                  ❌ Event was not approved
                </div>
              <?php endif; ?>
            </div>

            <div class="event-actions">
              <?php if ($event['status'] === 'approved'): ?>
                <form method="GET" action="organizer/view_attendees.php" style="display: inline;">
                  <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                  <button type="submit" class="btn btn-neutral">👥 View Attendees</button>
                </form>
              <?php endif; ?>
              
              <a class="btn btn-neutral" href="organizer/edit_event.php?id=<?= $event['id'] ?>">✏️ Edit</a>
              <a class="btn btn-danger" href="organizer/delete_event.php?id=<?= $event['id'] ?>" 
                 onclick="return confirm('Delete this event? This action cannot be undone.')">🗑️ Delete</a>
            </div>
          </article>
        <?php endforeach; ?>
      </section>
    <?php else: ?>
      <p class="empty-state">No events created yet. Click "Create New Event" to get started!</p>
    <?php endif; ?>
  </main>
</body>
</html>
