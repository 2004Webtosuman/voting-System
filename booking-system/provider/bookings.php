<?php
$page_title = 'My Bookings';
require_once __DIR__ . '/header.php';

global $conn;

$status_filter = $_GET['status'] ?? 'pending';

// Handle accept/reject/complete booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $booking_id = $_POST['booking_id'] ?? null;
  $action = $_POST['action'] ?? null;
  
  if ($booking_id && $action) {
    try {
      if ($action === 'accept') {
        $stmt = $conn->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ? AND provider_id = ?");
        $stmt->execute([$booking_id, $user['id']]);
        
        // Get booking details for notification
        $stmt = $conn->prepare("SELECT customer_id FROM bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Notify customer
        createNotification($booking['customer_id'], 'booking_accepted', 'Booking Confirmed', "{$user['name']} accepted your booking request", $booking_id);
        
        $success = 'Booking accepted!';
      } elseif ($action === 'complete') {
        $charge = $_POST['charge'] ?? 0;
        $stmt = $conn->prepare("
          UPDATE bookings 
          SET status = 'completed', actual_charge = ?, completed_date = NOW()
          WHERE id = ? AND provider_id = ?
        ");
        $stmt->execute([$charge, $booking_id, $user['id']]);
        
        // Update provider earnings
        $stmt = $conn->prepare("
          UPDATE providers 
          SET total_earnings = total_earnings + ?, total_jobs = total_jobs + 1
          WHERE user_id = ?
        ");
        $stmt->execute([$charge, $user['id']]);
        
        // Get booking details for notification
        $stmt = $conn->prepare("SELECT customer_id FROM bookings WHERE id = ?");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Notify customer
        createNotification($booking['customer_id'], 'service_completed', 'Service Completed', 'Your service has been completed', $booking_id);
        
        $success = 'Booking marked as completed!';
      } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND provider_id = ?");
        $stmt->execute([$booking_id, $user['id']]);
        $success = 'Booking rejected';
      }
    } catch (PDOException $e) {
      $error = 'Error: ' . $e->getMessage();
    }
  }
}

// Get bookings filtered by status
$stmt = $conn->prepare("
  SELECT b.*, u.name as customer_name
  FROM bookings b
  JOIN users u ON b.customer_id = u.id
  WHERE b.provider_id = ? AND b.status = ?
  ORDER BY b.booking_date DESC
");
$stmt->execute([$user['id'], $status_filter]);
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
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
  }
</style>

<div class="filter-tabs">
  <a href="?status=pending" class="<?php echo $status_filter === 'pending' ? 'active' : ''; ?>">Pending (<?php echo $conn->query("SELECT COUNT(*) FROM bookings WHERE provider_id = {$user['id']} AND status = 'pending'")->fetchColumn(); ?>)</a>
  <a href="?status=confirmed" class="<?php echo $status_filter === 'confirmed' ? 'active' : ''; ?>">Confirmed (<?php echo $conn->query("SELECT COUNT(*) FROM bookings WHERE provider_id = {$user['id']} AND status = 'confirmed'")->fetchColumn(); ?>)</a>
  <a href="?status=completed" class="<?php echo $status_filter === 'completed' ? 'active' : ''; ?>">Completed (<?php echo $conn->query("SELECT COUNT(*) FROM bookings WHERE provider_id = {$user['id']} AND status = 'completed'")->fetchColumn(); ?>)</a>
</div>

<?php if (isset($success)): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($bookings): ?>
  <?php foreach ($bookings as $booking): ?>
    <div class="booking-item">
      <div class="booking-header">
        <div>
          <h3 style="margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($booking['customer_name']); ?></h3>
          <p style="margin: 0; color: #666;"><?php echo htmlspecialchars($booking['service_type']); ?></p>
          <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem; color: #999;">
            <?php echo date('M d, Y g:i A', strtotime($booking['service_date'])); ?>
          </p>
        </div>
        <span class="badge badge-<?php echo $booking['status'] === 'completed' ? 'success' : 'warning'; ?>"><?php echo ucfirst($booking['status']); ?></span>
      </div>
      
      <p><?php echo htmlspecialchars($booking['description']); ?></p>
      <p><strong>Estimated Price:</strong> $<?php echo number_format($booking['estimated_price'], 2); ?></p>
      
      <?php if ($booking['actual_charge']): ?>
        <p><strong>Actual Charge:</strong> $<?php echo number_format($booking['actual_charge'], 2); ?></p>
      <?php endif; ?>
      
      <div class="booking-actions">
        <?php if ($booking['status'] === 'pending'): ?>
          <form method="POST" style="display: inline;">
            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
            <input type="hidden" name="action" value="accept">
            <button type="submit" class="btn btn-success" style="padding: 0.5rem 1rem;">Accept</button>
          </form>
          <form method="POST" style="display: inline;">
            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
            <input type="hidden" name="action" value="reject">
            <button type="submit" class="btn btn-danger" style="padding: 0.5rem 1rem;">Reject</button>
          </form>
        <?php elseif ($booking['status'] === 'confirmed'): ?>
          <button onclick="showChargeModal(<?php echo $booking['id']; ?>)" class="btn btn-success" style="padding: 0.5rem 1rem;">Mark Complete</button>
        <?php endif; ?>
        
        <a href="<?php echo SITE_URL; ?>provider/booking-details.php?id=<?php echo $booking['id']; ?>" class="btn btn-primary" style="padding: 0.5rem 1rem;">Details</a>
      </div>
    </div>
  <?php endforeach; ?>
<?php else: ?>
  <div class="card">
    <p>No bookings with status: <?php echo htmlspecialchars($status_filter); ?></p>
  </div>
<?php endif; ?>

<dialog id="chargeModal" style="padding: 2rem; border-radius: 8px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
  <h2>Enter Service Charge</h2>
  <form method="POST">
    <input type="hidden" id="modalBookingId" name="booking_id">
    <input type="hidden" name="action" value="complete">
    
    <div class="form-group">
      <label>Charge Amount ($)</label>
      <input type="number" name="charge" step="0.01" min="0" required>
    </div>
    
    <div style="display: flex; gap: 1rem;">
      <button type="submit" class="btn btn-success">Complete & Charge</button>
      <button type="button" onclick="document.getElementById('chargeModal').close()" class="btn btn-primary" style="background: #999;">Cancel</button>
    </div>
  </form>
</dialog>

<script>
  function showChargeModal(bookingId) {
    document.getElementById('modalBookingId').value = bookingId;
    document.getElementById('chargeModal').showModal();
  }
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
