<?php
require '../includes/config.php';
require '../includes/session.php';
require '../includes/csrf.php';
require '../includes/db.php';
require '../includes/mailer.php';

// Initialize secure session
initSecureSession();

// Require admin role
requireRole('admin');

$user = $_SESSION['user'];
$message = '';
$errors = [];

// Handle event approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrfToken();
  
  $eventId = (int)$_POST['event_id'];
  $action = $_POST['action']; // 'approve' or 'reject'
  $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
  
  if (in_array($action, ['approve', 'reject'])) {
    // Get event details
    $stmt = $pdo->prepare("
      SELECT e.*, u.name as organizer_name, u.email as organizer_email
      FROM events e
      JOIN users u ON e.organizer_id = u.id
      WHERE e.id = ?
    ");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();
    
    if ($event) {
      $newStatus = $action === 'approve' ? 'approved' : 'rejected';
      
      // Update event status
      $updateStmt = $pdo->prepare("UPDATE events SET status = ? WHERE id = ?");
      $updateStmt->execute([$newStatus, $eventId]);
      
      // Send email notification
      if ($action === 'approve') {
        sendEventApprovedNotification($event['organizer_email'], $event['organizer_name'], $event);
        $message = "Event '{$event['title']}' has been approved.";
      } else {
        sendEventRejectedNotification($event['organizer_email'], $event['organizer_name'], $event, $reason);
        $message = "Event '{$event['title']}' has been rejected.";
      }
    }
  }
}

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'pending';
$validFilters = ['pending', 'approved', 'rejected', 'all'];
if (!in_array($filter, $validFilters)) {
  $filter = 'pending';
}

// Build query
$query = "
  SELECT e.*, u.name as organizer_name, u.email as organizer_email,
         (SELECT COUNT(*) FROM rsvps WHERE event_id = e.id) as rsvp_count
  FROM events e
  JOIN users u ON e.organizer_id = u.id
";

if ($filter !== 'all') {
  $query .= " WHERE e.status = ?";
}

$query .= " ORDER BY e.created_at DESC";

// Execute query
if ($filter !== 'all') {
  $stmt = $pdo->prepare($query);
  $stmt->execute([$filter]);
} else {
  $stmt = $pdo->query($query);
}

$events = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Approve Events - Admin</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
  <header class="navbar">
    <h1>Event Approval</h1>
    <div class="nav-right">
      <a href="../dashboard_admin.php" class="btn btn-secondary">← Back to Dashboard</a>
      <a href="../logout.php" class="btn btn-logout">Logout</a>
    </div>
  </header>

  <main class="container">
    <?php if ($message): ?>
      <div class="success-message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
      <a href="?filter=pending" class="tab <?= $filter === 'pending' ? 'active' : '' ?>">Pending</a>
      <a href="?filter=approved" class="tab <?= $filter === 'approved' ? 'active' : '' ?>">Approved</a>
      <a href="?filter=rejected" class="tab <?= $filter === 'rejected' ? 'active' : '' ?>">Rejected</a>
      <a href="?filter=all" class="tab <?= $filter === 'all' ? 'active' : '' ?>">All</a>
    </div>

    <?php if (count($events) > 0): ?>
      <div class="events-grid">
        <?php foreach ($events as $event): ?>
          <div class="event-card status-<?= $event['status'] ?>">
            <?php if ($event['image']): ?>
              <img src="../uploads/<?= urlencode($event['image']) ?>" alt="Event Image" class="event-img">
            <?php endif; ?>
            
            <div class="event-body">
              <div class="event-header">
                <h3><?= htmlspecialchars($event['title']) ?></h3>
                <span class="status-badge status-<?= $event['status'] ?>">
                  <?= ucfirst($event['status']) ?>
                </span>
              </div>
              
              <p class="event-desc"><?= htmlspecialchars($event['description']) ?></p>
              
              <div class="event-meta">
                <p><strong>Organizer:</strong> <?= htmlspecialchars($event['organizer_name']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($event['organizer_email']) ?></p>
                <p><strong>Date:</strong> <?= date('F j, Y', strtotime($event['date'])) ?></p>
                <p><strong>Location:</strong> <?= htmlspecialchars($event['location']) ?></p>
                <p><strong>Capacity:</strong> <?= $event['rsvp_count'] ?> / <?= $event['max_attendees'] ?></p>
                <p><strong>Submitted:</strong> <?= date('M j, Y g:i A', strtotime($event['created_at'])) ?></p>
              </div>
              
              <?php if ($event['status'] === 'pending'): ?>
                <div class="event-actions">
                  <form method="POST" style="display: inline;">
                    <?php csrfField(); ?>
                    <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="btn btn-success" onclick="return confirm('Approve this event?')">
                      ✓ Approve
                    </button>
                  </form>
                  
                  <button class="btn btn-danger" onclick="showRejectModal(<?= $event['id'] ?>, '<?= htmlspecialchars($event['title'], ENT_QUOTES) ?>')">
                    ✗ Reject
                  </button>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="empty-state">No <?= $filter !== 'all' ? $filter : '' ?> events found.</p>
    <?php endif; ?>
  </main>

  <!-- Reject Modal -->
  <div id="rejectModal" class="modal">
    <div class="modal-content">
      <h2>Reject Event</h2>
      <p id="rejectEventTitle"></p>
      
      <form method="POST" id="rejectForm">
        <?php csrfField(); ?>
        <input type="hidden" name="event_id" id="rejectEventId">
        <input type="hidden" name="action" value="reject">
        
        <div class="form-group">
          <label for="reason">Reason for Rejection (Optional):</label>
          <textarea name="reason" id="reason" rows="4" placeholder="Provide a reason for rejection..."></textarea>
        </div>
        
        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" onclick="closeRejectModal()">Cancel</button>
          <button type="submit" class="btn btn-danger">Reject Event</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function showRejectModal(eventId, eventTitle) {
      document.getElementById('rejectEventId').value = eventId;
      document.getElementById('rejectEventTitle').textContent = 'Are you sure you want to reject "' + eventTitle + '"?';
      document.getElementById('rejectModal').style.display = 'flex';
    }
    
    function closeRejectModal() {
      document.getElementById('rejectModal').style.display = 'none';
      document.getElementById('reason').value = '';
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
      const modal = document.getElementById('rejectModal');
      if (event.target === modal) {
        closeRejectModal();
      }
    }
  </script>
</body>
</html>
