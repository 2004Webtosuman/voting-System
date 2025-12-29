<?php
$page_title = 'Provider Management';
require_once __DIR__ . '/header.php';

global $conn;

// Handle provider status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['provider_id'])) {
  $provider_id = $_POST['provider_id'];
  $new_status = $_POST['new_status'] ?? null;
  
  if ($new_status && in_array($new_status, ['active', 'suspended'])) {
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $provider_id]);
  }
}

// Get all providers with their info
$stmt = $conn->query("
  SELECT u.*, p.service_type, p.hourly_rate, p.average_rating, p.total_jobs, p.total_earnings
  FROM users u
  LEFT JOIN providers p ON u.id = p.user_id
  WHERE u.role = 'provider'
  ORDER BY u.created_at DESC
");
$providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
  <h1>Provider Management</h1>
  
  <table>
    <thead>
      <tr>
        <th>Name</th>
        <th>Email</th>
        <th>Service Type</th>
        <th>Rating</th>
        <th>Jobs</th>
        <th>Status</th>
        <th>KYC Status</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($providers as $prov): ?>
        <tr>
          <td><?php echo htmlspecialchars($prov['name']); ?></td>
          <td><?php echo htmlspecialchars($prov['email']); ?></td>
          <td><?php echo ucfirst($prov['service_type'] ?? '-'); ?></td>
          <td><?php echo number_format($prov['average_rating'] ?? 0, 1); ?> ⭐</td>
          <td><?php echo $prov['total_jobs'] ?? 0; ?></td>
          <td><span class="badge badge-<?php echo $prov['status'] === 'active' ? 'success' : 'danger'; ?>"><?php echo ucfirst($prov['status']); ?></span></td>
          <td><span class="badge badge-<?php echo $prov['kyc_status'] === 'verified' ? 'success' : ($prov['kyc_status'] === 'pending' ? 'warning' : 'danger'); ?>"><?php echo ucfirst($prov['kyc_status']); ?></span></td>
          <td>
            <form method="POST" style="display: inline;">
              <input type="hidden" name="provider_id" value="<?php echo $prov['id']; ?>">
              <input type="hidden" name="new_status" value="<?php echo $prov['status'] === 'active' ? 'suspended' : 'active'; ?>">
              <button type="submit" class="btn btn-<?php echo $prov['status'] === 'active' ? 'danger' : 'success'; ?>" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                <?php echo $prov['status'] === 'active' ? 'Suspend' : 'Activate'; ?>
              </button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
