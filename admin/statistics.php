<?php
require '../includes/config.php';
require '../includes/session.php';
require '../includes/db.php';

// Initialize secure session
initSecureSession();

// Require admin role
requireRole('admin');

$user = $_SESSION['user'];

// Get comprehensive statistics
$stats = [];

// User statistics
$userStats = $pdo->query("
  SELECT 
    role,
    COUNT(*) as count
  FROM users
  GROUP BY role
")->fetchAll(PDO::FETCH_KEY_PAIR);

$stats['total_users'] = array_sum($userStats);
$stats['organizers'] = $userStats['organizer'] ?? 0;
$stats['attendees'] = $userStats['attendee'] ?? 0;
$stats['admins'] = $userStats['admin'] ?? 0;

// Event statistics
$eventStats = $pdo->query("
  SELECT 
    status,
    COUNT(*) as count
  FROM events
  GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

$stats['total_events'] = array_sum($eventStats);
$stats['approved_events'] = $eventStats['approved'] ?? 0;
$stats['pending_events'] = $eventStats['pending'] ?? 0;
$stats['rejected_events'] = $eventStats['rejected'] ?? 0;

// RSVP statistics
$rsvpStats = $pdo->query("
  SELECT COUNT(*) as total_rsvps
  FROM rsvps
")->fetch();

$stats['total_rsvps'] = $rsvpStats['total_rsvps'];

// Average RSVPs per event
$avgRsvps = $pdo->query("
  SELECT AVG(rsvp_count) as avg_rsvps
  FROM (
    SELECT COUNT(*) as rsvp_count
    FROM rsvps
    GROUP BY event_id
  ) as counts
")->fetch();

$stats['avg_rsvps_per_event'] = round($avgRsvps['avg_rsvps'] ?? 0, 1);

// Most popular events
$popularEvents = $pdo->query("
  SELECT e.title, e.location, e.date, COUNT(r.attendee_id) as rsvp_count
  FROM events e
  LEFT JOIN rsvps r ON e.id = r.event_id
  WHERE e.status = 'approved'
  GROUP BY e.id
  ORDER BY rsvp_count DESC
  LIMIT 5
")->fetchAll();

// Most active organizers
$activeOrganizers = $pdo->query("
  SELECT u.name, u.email, COUNT(e.id) as event_count
  FROM users u
  LEFT JOIN events e ON u.id = e.organizer_id
  WHERE u.role = 'organizer'
  GROUP BY u.id
  ORDER BY event_count DESC
  LIMIT 5
")->fetchAll();

// Recent activity
$recentActivity = $pdo->query("
  SELECT 'event_created' as type, e.title as description, e.created_at as timestamp
  FROM events e
  UNION ALL
  SELECT 'rsvp' as type, CONCAT(u.name, ' RSVP\'d to ', e.title) as description, r.created_at as timestamp
  FROM rsvps r
  JOIN users u ON r.attendee_id = u.id
  JOIN events e ON r.event_id = e.id
  UNION ALL
  SELECT 'user_registered' as type, CONCAT(u.name, ' registered as ', u.role) as description, u.created_at as timestamp
  FROM users u
  ORDER BY timestamp DESC
  LIMIT 10
")->fetchAll();

// Events by month (last 6 months)
$eventsByMonth = $pdo->query("
  SELECT 
    DATE_FORMAT(created_at, '%Y-%m') as month,
    COUNT(*) as count
  FROM events
  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
  GROUP BY month
  ORDER BY month ASC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Statistics - Admin</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
  <header class="navbar">
    <h1>System Statistics</h1>
    <div class="nav-right">
      <a href="../dashboard_admin.php" class="btn btn-secondary">← Back to Dashboard</a>
      <a href="../logout.php" class="btn btn-logout">Logout</a>
    </div>
  </header>

  <main class="container">
    <!-- Overview Statistics -->
    <section>
      <h2>Overview</h2>
      <div class="stats-grid">
        <div class="stat-card">
          <h3>Total Users</h3>
          <p class="stat-number"><?= $stats['total_users'] ?></p>
          <p class="stat-detail">
            <?= $stats['organizers'] ?> Organizers, 
            <?= $stats['attendees'] ?> Attendees, 
            <?= $stats['admins'] ?> Admins
          </p>
        </div>
        
        <div class="stat-card">
          <h3>Total Events</h3>
          <p class="stat-number"><?= $stats['total_events'] ?></p>
          <p class="stat-detail">
            <?= $stats['approved_events'] ?> Approved, 
            <?= $stats['pending_events'] ?> Pending, 
            <?= $stats['rejected_events'] ?> Rejected
          </p>
        </div>
        
        <div class="stat-card">
          <h3>Total RSVPs</h3>
          <p class="stat-number"><?= $stats['total_rsvps'] ?></p>
          <p class="stat-detail">
            Avg <?= $stats['avg_rsvps_per_event'] ?> per event
          </p>
        </div>
      </div>
    </section>

    <!-- Popular Events -->
    <section>
      <h2>Most Popular Events</h2>
      <table class="data-table">
        <thead>
          <tr>
            <th>Event Title</th>
            <th>Location</th>
            <th>Date</th>
            <th>RSVPs</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($popularEvents as $event): ?>
          <tr>
            <td><?= htmlspecialchars($event['title']) ?></td>
            <td><?= htmlspecialchars($event['location']) ?></td>
            <td><?= date('M j, Y', strtotime($event['date'])) ?></td>
            <td><?= $event['rsvp_count'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>

    <!-- Active Organizers -->
    <section>
      <h2>Most Active Organizers</h2>
      <table class="data-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Events Created</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($activeOrganizers as $organizer): ?>
          <tr>
            <td><?= htmlspecialchars($organizer['name']) ?></td>
            <td><?= htmlspecialchars($organizer['email']) ?></td>
            <td><?= $organizer['event_count'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </section>

    <!-- Events by Month -->
    <?php if (count($eventsByMonth) > 0): ?>
    <section>
      <h2>Events Created (Last 6 Months)</h2>
      <div class="chart-container">
        <table class="data-table">
          <thead>
            <tr>
              <th>Month</th>
              <th>Events Created</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($eventsByMonth as $month): ?>
            <tr>
              <td><?= date('F Y', strtotime($month['month'] . '-01')) ?></td>
              <td><?= $month['count'] ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
    <?php endif; ?>

    <!-- Recent Activity -->
    <section>
      <h2>Recent Activity</h2>
      <div class="activity-feed">
        <?php foreach ($recentActivity as $activity): ?>
        <div class="activity-item">
          <span class="activity-type type-<?= $activity['type'] ?>">
            <?= str_replace('_', ' ', ucfirst($activity['type'])) ?>
          </span>
          <span class="activity-description"><?= htmlspecialchars($activity['description']) ?></span>
          <span class="activity-time"><?= date('M j, Y g:i A', strtotime($activity['timestamp'])) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </section>
  </main>
</body>
</html>
