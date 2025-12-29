<?php
$page_title = 'Find Providers';
require_once __DIR__ . '/header.php';

global $conn;

$service_type = $_GET['service_type'] ?? 'plumber';
$radius = $_GET['radius'] ?? 25;

// Default location (user's location or default)
$user_lat = $user['latitude'] ?? 40.7128;
$user_lon = $user['longitude'] ?? -74.0060;

// Get nearby providers
$providers = getNearbyProviders($user_lat, $user_lon, $service_type, $radius);
?>

<style>
  .filter-section {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    align-items: flex-end;
  }
  
  .provider-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
  }
  
  .provider-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  }
  
  .provider-image {
    width: 100%;
    height: 200px;
    background: var(--light);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
  }
  
  .provider-info {
    padding: 1.5rem;
  }
  
  .provider-name {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
  }
  
  .rating {
    display: flex;
    gap: 0.25rem;
    margin-bottom: 1rem;
  }
  
  .star {
    color: #ffc107;
    font-size: 1rem;
  }
  
  .distance {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 1rem;
  }
  
  .price {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--primary);
    margin-bottom: 1rem;
  }
</style>

<div class="filter-section">
  <form method="GET" action="" style="display: flex; gap: 1rem; flex-wrap: wrap; width: 100%; align-items: flex-end;">
    <div class="form-group" style="margin: 0; flex: 1; min-width: 200px;">
      <label>Service Type</label>
      <select name="service_type">
        <option value="plumber" <?php echo $service_type === 'plumber' ? 'selected' : ''; ?>>Plumber</option>
        <option value="electrician" <?php echo $service_type === 'electrician' ? 'selected' : ''; ?>>Electrician</option>
      </select>
    </div>
    
    <div class="form-group" style="margin: 0; flex: 1; min-width: 200px;">
      <label>Search Radius (miles)</label>
      <input type="number" name="radius" value="<?php echo htmlspecialchars($radius); ?>" min="1" max="100">
    </div>
    
    <button type="submit" class="btn btn-primary">Search</button>
  </form>
</div>

<?php if ($providers): ?>
  <div class="grid">
    <?php foreach ($providers as $provider): ?>
      <div class="provider-card">
        <div class="provider-image">👨‍🔧</div>
        <div class="provider-info">
          <h3 class="provider-name"><?php echo htmlspecialchars($provider['name']); ?></h3>
          
          <div class="rating">
            <?php
            $rating = intval($provider['average_rating']);
            for ($i = 0; $i < 5; $i++) {
              echo '<span class="star">' . ($i < $rating ? '★' : '☆') . '</span>';
            }
            ?>
          </div>
          
          <div class="distance">📍 <?php echo $provider['distance']; ?> miles away</div>
          <div class="price">$<?php echo htmlspecialchars($provider['hourly_rate']); ?>/hour</div>
          
          <a href="<?php echo SITE_URL; ?>customer/provider-details.php?id=<?php echo $provider['user_id']; ?>" class="btn btn-primary" style="width: 100%;">View Profile & Book</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="card">
    <p>No providers found in your area. Try increasing the search radius.</p>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
