<?php
require 'includes/config.php';
require 'includes/session.php';
require 'includes/db.php';

// Initialize secure session
initSecureSession();

// Require admin role
requireRole('admin');

$user = $_SESSION['user'];

// Get statistics
$statsQuery = "
  SELECT 
    (SELECT COUNT(*) FROM users WHERE role = 'organizer') as total_organizers,
    (SELECT COUNT(*) FROM users WHERE role = 'attendee') as total_attendees,
    (SELECT COUNT(*) FROM events WHERE status = 'approved') as approved_events,
    (SELECT COUNT(*) FROM events WHERE status = 'pending') as pending_events,
    (SELECT COUNT(*) FROM events WHERE status = 'rejected') as rejected_events,
    (SELECT COUNT(*) FROM rsvps) as total_rsvps
";
$stats = $pdo->query($statsQuery)->fetch();

// Get recent pending events
$pendingEvents = $pdo->query("
  SELECT e.*, u.name as organizer_name, u.email as organizer_email
  FROM events e
  JOIN users u ON e.organizer_id = u.id
  WHERE e.status = 'pending'
  ORDER BY e.created_at DESC
  LIMIT 5
")->fetchAll();

// Get recent users
$recentUsers = $pdo->query("
  SELECT id, name, email, role, created_at
  FROM users
  ORDER BY created_at DESC
  LIMIT 5
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - <?= APP_NAME ?></title>
  <link rel="stylesheet" href="assets/css/admin.css">
</head>

<body>
  <header class="navbar">
    <h1>Admin Dashboard</h1>
    <div class="nav-right">
      <span class="user-name">Welcome, <?= htmlspecialchars($user['name']) ?></span>
      <a href="logout.php" class="btn btn-logout">Logout</a>
    </div>
  </header>

  <main class="container">
    <!-- Statistics Cards -->
    <section class="stats-grid">
      <div class="stat-card">
        <h3>Total Organizers</h3>
        <p class="stat-number"><?= $stats['total_organizers'] ?></p>
      </div>
      
      <div class="stat-card">
        <h3>Total Attendees</h3>
        <p class="stat-number"><?= $stats['total_attendees'] ?></p>
      </div>
      
      <div class="stat-card stat-success">
        <h3>Approved Events</h3>
        <p class="stat-number"><?= $stats['approved_events'] ?></p>
      </div>
      
      <div class="stat-card stat-warning">
        <h3>Pending Events</h3>
        <p class="stat-number"><?= $stats['pending_events'] ?></p>
      </div>
      
      <div class="stat-card stat-danger">
        <h3>Rejected Events</h3>
        <p class="stat-number"><?= $stats['rejected_events'] ?></p>
      </div>
      
      <div class="stat-card">
        <h3>Total RSVPs</h3>
        <p class="stat-number"><?= $stats['total_rsvps'] ?></p>
      </div>
    </section>

    <!-- Quick Actions -->
    <section class="quick-actions">
      <h2>Quick Actions</h2>
      <div class="action-buttons">
        <a href="admin/approve_events.php" class="btn btn-primary">
          Approve Events <?php if ($stats['pending_events'] > 0): ?>(<?= $stats['pending_events'] ?>)<?php endif; ?>
        </a>
        <a href="admin/manage_users.php" class="btn btn-secondary">Manage Users</a>
        <a href="admin/statistics.php" class="btn btn-secondary">View Detailed Statistics</a>
      </div>
    </section>

    <!-- Pending Events -->
    <?php if (count($pendingEvents) > 0): ?>
    <section class="recent-section">
      <h2>Recent Pending Events</h2>
      <table class="data-table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Organizer</th>
            <th>Date</th>
            <th>Location</th>
            <th>Submitted</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pendingEvents as $event): ?>
          <tr>
            <td><?= htmlspecialchars($event['title']) ?></td>
            <td><?= htmlspecialchars($event['organizer_name']) ?></td>
            <td><?= date('M j, Y', strtotime($event['date'])) ?></td>
            <td><?= htmlspecialchars($event['location']) ?></td>
            <td><?= date('M j, Y', strtotime($event['created_at'])) ?></td>
            <td>
              <a href="admin/approve_events.php?event_id=<?= $event['id'] ?>" class="btn-small btn-primary">Review</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <a href="admin/approve_events.php" class="view-all-link">View All Pending Events →</a>
    </section>
    <?php endif; ?>

    <!-- Recent Users -->
    <section class="recent-section">
      <h2>Recent Users</h2>
      <table class="data-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Registered</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentUsers as $recentUser): ?>
          <tr>
            <td><?= htmlspecialchars($recentUser['name']) ?></td>
            <td><?= htmlspecialchars($recentUser['email']) ?></td>
            <td><span class="role-badge role-<?= $recentUser['role'] ?>"><?= ucfirst($recentUser['role']) ?></span></td>
            <td><?= date('M j, Y', strtotime($recentUser['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <a href="admin/manage_users.php" class="view-all-link">View All Users →</a>
    </section>
  </main>
</body>
</html>
