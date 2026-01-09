<?php
require '../includes/config.php';
require '../includes/session.php';
require '../includes/csrf.php';
require '../includes/db.php';

// Initialize secure session
initSecureSession();

// Require admin role
requireRole('admin');

$user = $_SESSION['user'];
$message = '';
$errors = [];

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
  verifyCsrfToken();
  
  $userId = (int)$_POST['user_id'];
  
  // Prevent deleting yourself
  if ($userId === $user['id']) {
    $errors[] = "You cannot delete your own account.";
  } else {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $message = "User deleted successfully.";
  }
}

// Handle role change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
  verifyCsrfToken();
  
  $userId = (int)$_POST['user_id'];
  $newRole = $_POST['new_role'];
  
  if (in_array($newRole, ['organizer', 'attendee', 'admin'])) {
    // Prevent changing your own role
    if ($userId === $user['id']) {
      $errors[] = "You cannot change your own role.";
    } else {
      $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
      $stmt->execute([$newRole, $userId]);
      $message = "User role updated successfully.";
    }
  }
}

// Get filter
$roleFilter = isset($_GET['role']) ? $_GET['role'] : 'all';
$validRoles = ['all', 'organizer', 'attendee', 'admin'];
if (!in_array($roleFilter, $validRoles)) {
  $roleFilter = 'all';
}

// Build query
$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM events WHERE organizer_id = u.id) as events_count,
          (SELECT COUNT(*) FROM rsvps WHERE attendee_id = u.id) as rsvps_count
          FROM users u";

if ($roleFilter !== 'all') {
  $query .= " WHERE u.role = ?";
}

$query .= " ORDER BY u.created_at DESC";

// Execute query
if ($roleFilter !== 'all') {
  $stmt = $pdo->prepare($query);
  $stmt->execute([$roleFilter]);
} else {
  $stmt = $pdo->query($query);
}

$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Users - Admin</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body>
  <header class="navbar">
    <h1>User Management</h1>
    <div class="nav-right">
      <a href="../dashboard_admin.php" class="btn btn-secondary">← Back to Dashboard</a>
      <a href="../logout.php" class="btn btn-logout">Logout</a>
    </div>
  </header>

  <main class="container">
    <?php if ($message): ?>
      <div class="success-message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
      <div class="error-message">
        <?php foreach ($errors as $error): ?>
          <p><?= htmlspecialchars($error) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
      <a href="?role=all" class="tab <?= $roleFilter === 'all' ? 'active' : '' ?>">
        All Users (<?= count($users) ?>)
      </a>
      <a href="?role=organizer" class="tab <?= $roleFilter === 'organizer' ? 'active' : '' ?>">Organizers</a>
      <a href="?role=attendee" class="tab <?= $roleFilter === 'attendee' ? 'active' : '' ?>">Attendees</a>
      <a href="?role=admin" class="tab <?= $roleFilter === 'admin' ? 'active' : '' ?>">Admins</a>
    </div>

    <!-- Users Table -->
    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Events Created</th>
            <th>RSVPs</th>
            <th>Registered</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr>
            <td><?= $u['id'] ?></td>
            <td><?= htmlspecialchars($u['name']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td>
              <span class="role-badge role-<?= $u['role'] ?>">
                <?= ucfirst($u['role']) ?>
              </span>
            </td>
            <td><?= $u['events_count'] ?></td>
            <td><?= $u['rsvps_count'] ?></td>
            <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
            <td>
              <?php if ($u['id'] !== $user['id']): ?>
                <button class="btn-small btn-secondary" onclick="showRoleModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>', '<?= $u['role'] ?>')">
                  Change Role
                </button>
                <button class="btn-small btn-danger" onclick="showDeleteModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['name'], ENT_QUOTES) ?>')">
                  Delete
                </button>
              <?php else: ?>
                <span class="text-muted">You</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>

  <!-- Change Role Modal -->
  <div id="roleModal" class="modal">
    <div class="modal-content">
      <h2>Change User Role</h2>
      <p id="roleUserName"></p>
      
      <form method="POST" id="roleForm">
        <?php csrfField(); ?>
        <input type="hidden" name="user_id" id="roleUserId">
        <input type="hidden" name="change_role" value="1">
        
        <div class="form-group">
          <label for="new_role">Select New Role:</label>
          <select name="new_role" id="new_role" required>
            <option value="attendee">Attendee</option>
            <option value="organizer">Organizer</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        
        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" onclick="closeRoleModal()">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Role</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Delete User Modal -->
  <div id="deleteModal" class="modal">
    <div class="modal-content">
      <h2>Delete User</h2>
      <p id="deleteUserName"></p>
      <p class="warning-text">This action cannot be undone. All events and RSVPs associated with this user will also be deleted.</p>
      
      <form method="POST" id="deleteForm">
        <?php csrfField(); ?>
        <input type="hidden" name="user_id" id="deleteUserId">
        <input type="hidden" name="delete_user" value="1">
        
        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete User</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function showRoleModal(userId, userName, currentRole) {
      document.getElementById('roleUserId').value = userId;
      document.getElementById('roleUserName').textContent = 'Change role for: ' + userName;
      document.getElementById('new_role').value = currentRole;
      document.getElementById('roleModal').style.display = 'flex';
    }
    
    function closeRoleModal() {
      document.getElementById('roleModal').style.display = 'none';
    }
    
    function showDeleteModal(userId, userName) {
      document.getElementById('deleteUserId').value = userId;
      document.getElementById('deleteUserName').textContent = 'Are you sure you want to delete: ' + userName + '?';
      document.getElementById('deleteModal').style.display = 'flex';
    }
    
    function closeDeleteModal() {
      document.getElementById('deleteModal').style.display = 'none';
    }
    
    // Close modals when clicking outside
    window.onclick = function(event) {
      const roleModal = document.getElementById('roleModal');
      const deleteModal = document.getElementById('deleteModal');
      
      if (event.target === roleModal) {
        closeRoleModal();
      }
      if (event.target === deleteModal) {
        closeDeleteModal();
      }
    }
  </script>
</body>
</html>
