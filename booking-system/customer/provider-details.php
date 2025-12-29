<?php
$page_title = 'Provider Details';
require_once __DIR__ . '/header.php';

global $conn;

$provider_id = $_GET['id'] ?? null;
if (!$provider_id) {
  header('Location: ' . SITE_URL . 'customer/find-providers.php');
  exit();
}

$provider = getProviderInfo($provider_id);
if (!$provider) {
  die('Provider not found');
}

// Handle booking submission
$booking_success = '';
$booking_errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book'])) {
  $description = trim($_POST['description'] ?? '');
  $service_type = trim($_POST['service_type'] ?? '');
  $service_date = $_POST['service_date'] ?? '';
  
  if (empty($description) || empty($service_type) || empty($service_date)) {
    $booking_errors[] = 'All fields are required';
  } else {
    try {
      $estimated_price = $provider['hourly_rate'] * 2; // Default 2 hours
      
      $stmt = $conn->prepare("
        INSERT INTO bookings (customer_id, provider_id, service_type, description, estimated_price, service_date, status)
        VALUES (?, ?, ?, ?, ?, ?, 'pending')
      ");
      
      if ($stmt->execute([$user['id'], $provider_id, $service_type, $description, $estimated_price, $service_date])) {
        $booking_id = $conn->lastInsertId();
        
        // Create notification for provider
        createNotification($provider_id, 'new_booking', 'New Booking Request', "New booking from {$user['name']}", $booking_id);
        
        $booking_success = 'Booking request sent successfully!';
      }
    } catch (PDOException $e) {
      $booking_errors[] = 'Error creating booking: ' . $e->getMessage();
    }
  }
}

// Get provider reviews
$stmt = $conn->prepare("
  SELECT r.*, u.name as customer_name
  FROM reviews r
  JOIN users u ON r.customer_id = u.id
  WHERE r.provider_id = ?
  ORDER BY r.created_at DESC
");
$stmt->execute([$provider_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
  .provider-header {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 2rem;
    align-items: start;
  }
  
  .provider-avatar {
    width: 200px;
    height: 200px;
    background: var(--light);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 5rem;
  }
  
  .rating {
    display: flex;
    gap: 0.25rem;
    margin: 1rem 0;
  }
  
  .star {
    color: #ffc107;
    font-size: 1.5rem;
  }
  
  .two-column {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
  }
  
  .booking-form {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    border: 2px solid var(--primary);
  }
</style>

<div class="provider-header">
  <div class="provider-avatar">👨‍🔧</div>
  <div>
    <h1><?php echo htmlspecialchars($provider['name']); ?></h1>
    <p><?php echo ucfirst($provider['service_type']); ?> • <?php echo $provider['years_experience']; ?> years experience</p>
    
    <div class="rating">
      <?php
      $rating = intval($provider['average_rating']);
      for ($i = 0; $i < 5; $i++) {
        echo '<span class="star">' . ($i < $rating ? '★' : '☆') . '</span>';
      }
      ?>
      <span>(<?php echo number_format($provider['average_rating'], 1); ?>)</span>
    </div>
    
    <p><strong>Hourly Rate:</strong> $<?php echo $provider['hourly_rate']; ?></p>
    <p><strong>Total Jobs:</strong> <?php echo $provider['total_jobs']; ?></p>
    <p><strong>Total Earnings:</strong> $<?php echo number_format($provider['total_earnings'], 2); ?></p>
    
    <?php if ($provider['bio']): ?>
      <p><strong>About:</strong> <?php echo htmlspecialchars($provider['bio']); ?></p>
    <?php endif; ?>
  </div>
</div>

<div class="two-column">
  <div>
    <div class="card">
      <h2>Customer Reviews</h2>
      <?php if ($reviews): ?>
        <?php foreach ($reviews as $review): ?>
          <div style="padding-bottom: 1.5rem; border-bottom: 1px solid var(--border);">
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
              <strong><?php echo htmlspecialchars($review['customer_name']); ?></strong>
              <div class="rating">
                <?php
                for ($i = 0; $i < 5; $i++) {
                  echo '<span class="star" style="font-size: 1rem;">' . ($i < $review['rating'] ? '★' : '☆') . '</span>';
                }
                ?>
              </div>
            </div>
            <p><?php echo htmlspecialchars($review['comment']); ?></p>
            <small style="color: #999;"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></small>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No reviews yet.</p>
      <?php endif; ?>
    </div>
  </div>
  
  <div>
    <div class="booking-form">
      <h2>Book This Provider</h2>
      
      <?php if ($booking_errors): ?>
        <?php foreach ($booking_errors as $error): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endforeach; ?>
      <?php endif; ?>
      
      <?php if ($booking_success): ?>
        <div class="alert alert-success"><?php echo $booking_success; ?></div>
      <?php else: ?>
        <form method="POST" action="">
          <div class="form-group">
            <label>Service Type</label>
            <input type="text" name="service_type" required value="<?php echo htmlspecialchars($provider['service_type']); ?>" readonly>
          </div>
          
          <div class="form-group">
            <label>Description</label>
            <textarea name="description" required placeholder="Describe your service needs..."></textarea>
          </div>
          
          <div class="form-group">
            <label>Preferred Date & Time</label>
            <input type="datetime-local" name="service_date" required>
          </div>
          
          <button type="submit" name="book" class="btn btn-primary" style="width: 100%;">Send Booking Request</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
