<?php
$page_title = 'User Management';
require_once __DIR__ . '/header.php';

global $conn;

// Handle user status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
  $user_id = $_POST['user_id'];
  $new_status = $_POST['new_status'] ?? null;
  
  if ($new_status && in_array($new_status, ['active', 'suspended'])) {
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $user_id]);
  }
}

// Get all customers
$stmt = $conn->query("SELECT * FROM users WHERE role = 'customer' ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
  <h1>Customer Management</h1>
  
  <table>
    <thead>
      <tr>
        <th>Name</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Status</th>
        <th>KYC Status</th>
        <th>Joined</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $cust): ?>
        <tr>
          <td><?php echo htmlspecialchars($cust['name']); ?></td>
          <td><?php echo htmlspecialchars($cust['email']); ?></td>
          <td><?php echo htmlspecialchars($cust['phone'] ?? '-'); ?></td>
          <td><span class="badge badge-<?php echo $cust['status'] === 'active' ? 'success' : 'danger'; ?>"><?php echo ucfirst($cust['status']); ?></span></td>
          <td><span class="badge badge-<?php echo $cust['kyc_status'] === 'verified' ? 'success' : ($cust['kyc_status'] === 'pending' ? 'warning' : 'danger'); ?>"><?php echo ucfirst($cust['kyc_status']); ?></span></td>
          <td><?php echo date('M d, Y', strtotime($cust['created_at'])); ?></td>
          <td>
            <form method="POST" style="display: inline;">
              <input type="hidden" name="user_id" value="<?php echo $cust['id']; ?>">
              <input type="hidden" name="new_status" value="<?php echo $cust['status'] === 'active' ? 'suspended' : 'active'; ?>">
              <button type="submit" class="btn btn-<?php echo $cust['status'] === 'active' ? 'danger' : 'success'; ?>" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                <?php echo $cust['status'] === 'active' ? 'Suspend' : 'Activate'; ?>
              </button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
