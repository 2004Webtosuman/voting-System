<?php
$page_title = 'KYC Verification Management';
require_once __DIR__ . '/header.php';

global $conn;

// Handle approval/rejection
$action_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $user_id = $_POST['user_id'] ?? null;
  $action = $_POST['action'] ?? null;
  $rejection_reason = $_POST['rejection_reason'] ?? '';
  
  if ($user_id && in_array($action, ['approve', 'reject'])) {
    try {
      if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE users SET kyc_status = 'verified' WHERE id = ?");
        $stmt->execute([$user_id]);
        createNotification($user_id, 'kyc_approved', 'KYC Approved', 'Your KYC verification has been approved!', null);
        $action_message = 'KYC approved successfully!';
      } else {
        $stmt = $conn->prepare("UPDATE users SET kyc_status = 'rejected', kyc_rejection_reason = ? WHERE id = ?");
        $stmt->execute([$rejection_reason, $user_id]);
        createNotification($user_id, 'kyc_rejected', 'KYC Rejected', "Your KYC was rejected: $rejection_reason", null);
        $action_message = 'KYC rejected!';
      }
    } catch (PDOException $e) {
      $action_message = 'Error: ' . $e->getMessage();
    }
  }
}

// Get pending KYC submissions
$stmt = $conn->query("
  SELECT u.*, 
  (SELECT COUNT(*) FROM kyc_documents WHERE user_id = u.id) as doc_count
  FROM users u
  WHERE u.kyc_status = 'pending'
  ORDER BY u.created_at ASC
");
$pending_kyc = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get verified users
$stmt = $conn->query("
  SELECT u.* FROM users u
  WHERE u.kyc_status = 'verified'
  ORDER BY u.created_at DESC
  LIMIT 5
");
$verified_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
  .kyc-item {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    border-left: 4px solid var(--warning);
  }
  
  .kyc-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 1rem;
  }
  
  .document-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin: 1rem 0;
  }
  
  .document-item {
    background: var(--light);
    padding: 1rem;
    border-radius: 8px;
    text-align: center;
  }
  
  .document-item img {
    max-width: 100%;
    max-height: 120px;
    border-radius: 4px;
    margin-bottom: 0.5rem;
  }
</style>

<?php if ($action_message): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($action_message); ?></div>
<?php endif; ?>

<h2>Pending KYC Verifications (<?php echo count($pending_kyc); ?>)</h2>

<?php if ($pending_kyc): ?>
  <?php foreach ($pending_kyc as $user): ?>
    <div class="kyc-item">
      <div class="kyc-header">
        <div>
          <h3 style="margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($user['name']); ?></h3>
          <p style="margin: 0; color: #666;"><?php echo htmlspecialchars($user['email']); ?></p>
          <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem; color: #999;">
            Role: <strong><?php echo ucfirst($user['role']); ?></strong> | 
            Documents: <strong><?php echo $user['doc_count']; ?></strong>
          </p>
        </div>
      </div>
      
      <!-- Display uploaded documents -->
      <?php
      $stmt = $conn->prepare("SELECT * FROM kyc_documents WHERE user_id = ?");
      $stmt->execute([$user['id']]);
      $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
      ?>
      
      <div class="document-grid">
        <?php foreach ($docs as $doc): ?>
          <div class="document-item">
            <div style="font-size: 2rem; margin-bottom: 0.5rem;">📄</div>
            <p style="margin: 0 0 0.5rem 0; font-size: 0.9rem; font-weight: 600;">
              <?php echo ucfirst($doc['document_type']); ?>
            </p>
            <a href="<?php echo SITE_URL; ?>uploads/<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="btn btn-primary" style="padding: 0.25rem 0.75rem; font-size: 0.85rem;">View</a>
          </div>
        <?php endforeach; ?>
      </div>
      
      <div style="background: #f0f0f0; padding: 1rem; border-radius: 4px; margin-top: 1rem;">
        <form method="POST" style="display: grid; gap: 1rem;">
          <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
          
          <div>
            <label style="display: flex; gap: 0.5rem;">
              <input type="radio" name="action" value="approve" required> <strong>Approve KYC</strong>
            </label>
          </div>
          
          <div>
            <label style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
              <input type="radio" name="action" value="reject"> <strong>Reject KYC</strong>
            </label>
            <textarea name="rejection_reason" placeholder="Reason for rejection..." style="width: 100%; padding: 0.5rem; border-radius: 4px; border: 1px solid var(--border);"></textarea>
          </div>
          
          <div style="display: flex; gap: 1rem;">
            <button type="submit" name="action" value="approve" class="btn btn-success">Approve</button>
            <button type="submit" name="action" value="reject" class="btn btn-danger">Reject</button>
          </div>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
<?php else: ?>
  <div class="card">
    <p>No pending KYC verifications.</p>
  </div>
<?php endif; ?>

<h2 style="margin-top: 3rem;">Recently Verified Users</h2>
<table>
  <thead>
    <tr>
      <th>Name</th>
      <th>Email</th>
      <th>Role</th>
      <th>Verified Date</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($verified_users as $vuser): ?>
      <tr>
        <td><?php echo htmlspecialchars($vuser['name']); ?></td>
        <td><?php echo htmlspecialchars($vuser['email']); ?></td>
        <td><?php echo ucfirst($vuser['role']); ?></td>
        <td><?php echo date('M d, Y', strtotime($vuser['updated_at'])); ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php require_once __DIR__ . '/footer.php'; ?>
