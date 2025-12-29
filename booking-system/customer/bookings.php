<?php
    $page_title = 'My Bookings';
    require_once __DIR__ . '/header.php';

    global $conn;

    // Filter by status if needed
    $status_filter = $_GET['status'] ?? 'all';

    // Get bookings for this customer
    $sql = "
  SELECT b.*, u.name as provider_name, u.email as provider_email
  FROM bookings b
  JOIN users u ON b.provider_id = u.id
  WHERE b.customer_id = ?
";

    $params = [$user['id']];

    if ($status_filter !== 'all') {
        $sql .= " AND b.status = ?";
        $params[] = $status_filter;
    }

    $sql .= " ORDER BY b.booking_date DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.filter-tabs {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
  border-bottom: 2px solid var(--border);
}

.filter-tabs a {
  padding: 1rem;
  text-decoration: none;
  color: #666;
  border-bottom: 3px solid transparent;
  transition: all 0.3s ease;
}

.filter-tabs a.active {
  color: var(--secondary);
  border-bottom-color: var(--secondary);
}

.booking-item {
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  margin-bottom: 1rem;
  border-left: 4px solid var(--secondary);
}

.booking-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  margin-bottom: 1rem;
}

.booking-actions {
  margin-top: 1rem;
}
</style>

<div class="filter-tabs">
  <a href="?status=all" class="<?php echo $status_filter === 'all' ? 'active' : ''; ?>">All</a>
  <a href="?status=pending" class="<?php echo $status_filter === 'pending' ? 'active' : ''; ?>">Pending</a>
  <a href="?status=confirmed" class="<?php echo $status_filter === 'confirmed' ? 'active' : ''; ?>">Confirmed</a>
  <a href="?status=completed" class="<?php echo $status_filter === 'completed' ? 'active' : ''; ?>">Completed</a>
  <a href="?status=cancelled" class="<?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
</div>

<?php if ($bookings): ?>
  <?php foreach ($bookings as $booking): ?>
    <div class="booking-item">
      <div class="booking-header">
        <div>
          <h3 style="margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($booking['provider_name']); ?></h3>
          <p style="margin: 0; color: #666;"><?php echo htmlspecialchars($booking['service_type']); ?></p>
          <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem; color: #999;">
            <?php echo date('M d, Y g:i A', strtotime($booking['service_date'])); ?>
          </p>
        </div>
        <span class="badge badge-<?php
                                 echo $booking['status'] === 'completed' ? 'success' : ($booking['status'] === 'cancelled' ? 'danger' : 'warning');
                                 ?>"><?php echo ucfirst($booking['status']); ?></span>
      </div>

      <p><?php echo htmlspecialchars($booking['description']); ?></p>
      <p><strong>Estimated Price:</strong> $<?php echo number_format($booking['estimated_price'], 2); ?></p>
      <?php if ($booking['actual_charge']): ?>
        <p><strong>Actual Charge:</strong> $<?php echo number_format($booking['actual_charge'], 2); ?></p>
      <?php endif; ?>

      <div class="booking-actions">
        <a href="<?php echo SITE_URL; ?>customer/booking-details.php?id=<?php echo $booking['id']; ?>" class="btn btn-primary" style="padding: 0.5rem 1rem;">View Details</a>
      </div>
    </div>
  <?php endforeach; ?>
<?php else: ?>
  <div class="card">
    <p>No bookings found for status:                                                                         <?php echo htmlspecialchars($status_filter); ?></p>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
