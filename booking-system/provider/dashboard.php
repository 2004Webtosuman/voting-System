<?php
$page_title = 'Provider Dashboard';
require_once __DIR__ . '/header.php';

global $conn;

// Get provider stats
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE provider_id = ? AND status = 'pending'");
$stmt->execute([$user['id']]);
$pending_bookings = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE provider_id = ? AND status = 'completed'");
$stmt->execute([$user['id']]);
$completed_bookings = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $conn->prepare("SELECT SUM(actual_charge) as total FROM bookings WHERE provider_id = ? AND status = 'completed'");
$stmt->execute([$user['id']]);
$total_earnings = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Get recent bookings
$stmt = $conn->prepare("
  SELECT b.*, u.name as customer_name
  FROM bookings b
  JOIN users u ON b.customer_id = u.id
  WHERE b.provider_id = ?
  ORDER BY b.booking_date DESC
  LIMIT 5
");
$stmt->execute([$user['id']]);
$recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unread notifications
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
$stmt->execute([$user['id']]);
$notification_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
?>

<style>
  .dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
  }
  
  .stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border-top: 4px solid var(--secondary);
  }
  
  .stat-card h3 {
    margin: 0 0 0.5rem 0;
    color: #999;
    font-size: 0.9rem;
    text-transform: uppercase;
  }
  
  .stat-card .value {
    font-size: 2rem;
    font-weight: bold;
    color: var(--secondary);
  }
</style>

<div class="dashboard-grid">
  <div class="stat-card">
    <h3>Pending Bookings</h3>
    <div class="value"><?php echo $pending_bookings; ?></div>
  </div>
  
  <div class="stat-card">
    <h3>Completed Services</h3>
    <div class="value"><?php echo $completed_bookings; ?></div>
  </div>
  
  <div class="stat-card">
    <h3>Total Earnings</h3>
    <div class="value">$<?php echo number_format($total_earnings, 2); ?></div>
  </div>
  
  <div class="stat-card">
    <h3>Average Rating</h3>
    <div class="value"><?php echo number_format($provider['average_rating'], 1); ?></div>
  </div>
</div>

<div class="card">
  <h2>Recent Bookings</h2>
  <?php if ($recent_bookings): ?>
    <table>
      <thead>
        <tr>
          <th>Customer</th>
          <th>Service Type</th>
          <th>Status</th>
          <th>Charge</th>
          <th>Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recent_bookings as $booking): ?>
          <tr>
            <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
            <td><?php echo htmlspecialchars($booking['service_type']); ?></td>
            <td><span class="badge badge-<?php echo $booking['status'] === 'completed' ? 'success' : 'warning'; ?>"><?php echo ucfirst($booking['status']); ?></span></td>
            <td><?php echo $booking['actual_charge'] ? '$' . number_format($booking['actual_charge'], 2) : '-'; ?></td>
            <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
            <td><a href="<?php echo SITE_URL; ?>provider/booking-details.php?id=<?php echo $booking['id']; ?>" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">View</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p>No bookings yet. <a href="<?php echo SITE_URL; ?>provider/profile.php">Complete your profile</a></p>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
