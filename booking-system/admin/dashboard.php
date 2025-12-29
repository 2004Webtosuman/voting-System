<?php
$page_title = 'Admin Dashboard';
require_once __DIR__ . '/header.php';

global $conn;

// Get system statistics
$stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
$total_customers = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'provider'");
$total_providers = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM bookings");
$total_bookings = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'completed'");
$completed_bookings = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $conn->query("SELECT SUM(actual_charge) as total FROM bookings WHERE status = 'completed'");
$total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE kyc_status = 'pending'");
$pending_kyc = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
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
    border-top: 4px solid var(--primary);
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
    color: var(--primary);
  }
</style>

<div class="dashboard-grid">
  <div class="stat-card">
    <h3>Total Customers</h3>
    <div class="value"><?php echo $total_customers; ?></div>
  </div>
  
  <div class="stat-card">
    <h3>Total Providers</h3>
    <div class="value"><?php echo $total_providers; ?></div>
  </div>
  
  <div class="stat-card">
    <h3>Total Bookings</h3>
    <div class="value"><?php echo $total_bookings; ?></div>
  </div>
  
  <div class="stat-card">
    <h3>Total Revenue</h3>
    <div class="value">$<?php echo number_format($total_revenue, 0); ?></div>
  </div>
  
  <div class="stat-card">
    <h3>Completed Services</h3>
    <div class="value"><?php echo $completed_bookings; ?></div>
  </div>
  
  <div class="stat-card" style="border-top-color: var(--warning);">
    <h3>Pending KYC</h3>
    <div class="value" style="color: var(--warning);">
      <a href="<?php echo SITE_URL; ?>admin/kyc.php" style="color: var(--warning); text-decoration: none;">
        <?php echo $pending_kyc; ?>
      </a>
    </div>
  </div>
</div>

<div class="card">
  <h2>Recent Bookings</h2>
  <?php
  $stmt = $conn->query("
    SELECT b.*, u.name as customer_name, p.name as provider_name
    FROM bookings b
    JOIN users u ON b.customer_id = u.id
    JOIN users p ON b.provider_id = p.id
    ORDER BY b.booking_date DESC
    LIMIT 10
  ");
  $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
  ?>
  
  <table>
    <thead>
      <tr>
        <th>Customer</th>
        <th>Provider</th>
        <th>Service</th>
        <th>Status</th>
        <th>Amount</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($bookings as $booking): ?>
        <tr>
          <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
          <td><?php echo htmlspecialchars($booking['provider_name']); ?></td>
          <td><?php echo htmlspecialchars($booking['service_type']); ?></td>
          <td><span class="badge badge-<?php echo $booking['status'] === 'completed' ? 'success' : 'warning'; ?>"><?php echo ucfirst($booking['status']); ?></span></td>
          <td><?php echo $booking['actual_charge'] ? '$' . number_format($booking['actual_charge'], 2) : '-'; ?></td>
          <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
