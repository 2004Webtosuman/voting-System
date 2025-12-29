<?php
require_once __DIR__ . '/../config/database.php';

// Hash password
function hashPassword($password) {
  return password_hash($password, PASSWORD_BCRYPT);
}

// Verify password
function verifyPassword($password, $hash) {
  return password_verify($password, $hash);
}

// Check if user is logged in
function isLoggedIn() {
  return isset($_SESSION['user_id']);
}

// Get current user
function getCurrentUser() {
  global $conn;
  if (!isLoggedIn()) return null;
  
  $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
  $stmt->execute([$_SESSION['user_id']]);
  return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get user by ID
function getUserById($id) {
  global $conn;
  $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
  $stmt->execute([$id]);
  return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get provider info
function getProviderInfo($provider_id) {
  global $conn;
  $stmt = $conn->prepare("SELECT u.*, p.* FROM users u LEFT JOIN providers p ON u.id = p.user_id WHERE u.id = ? AND u.role = 'provider'");
  $stmt->execute([$provider_id]);
  return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get distance using haversine formula (in miles)
function getDistance($lat1, $lon1, $lat2, $lon2) {
  $earthRadius = 3959; // Earth's radius in miles
  
  $lat1Rad = deg2rad($lat1);
  $lat2Rad = deg2rad($lat2);
  $deltaLat = deg2rad($lat2 - $lat1);
  $deltaLon = deg2rad($lon2 - $lon1);
  
  $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
       cos($lat1Rad) * cos($lat2Rad) * sin($deltaLon / 2) * sin($deltaLon / 2);
  $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
  
  return $earthRadius * $c;
}

// Get nearby providers (using haversine algorithm)
function getNearbyProviders($latitude, $longitude, $service_type, $radius = 25) {
  global $conn;
  
  $stmt = $conn->prepare("
    SELECT u.*, p.* FROM users u
    LEFT JOIN providers p ON u.id = p.user_id
    WHERE u.role = 'provider' 
    AND p.service_type = ? 
    AND u.status = 'active'
    AND u.kyc_status = 'verified'
  ");
  $stmt->execute([$service_type]);
  
  $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  // Filter by distance
  $nearbyProviders = [];
  foreach ($providers as $provider) {
    $distance = getDistance($latitude, $longitude, $provider['latitude'], $provider['longitude']);
    if ($distance <= $radius) {
      $provider['distance'] = round($distance, 2);
      $nearbyProviders[] = $provider;
    }
  }
  
  // Sort by rating
  usort($nearbyProviders, function($a, $b) {
    return $b['average_rating'] <=> $a['average_rating'];
  });
  
  return $nearbyProviders;
}

// Handle file upload for KYC documents
function uploadKYCDocument($user_id, $document_type) {
  if (!isset($_FILES['document'])) {
    return ['success' => false, 'message' => 'No file uploaded'];
  }
  
  $file = $_FILES['document'];
  
  // Validate file
  $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, ALLOWED_EXTENSIONS)) {
    return ['success' => false, 'message' => 'Invalid file type'];
  }
  
  if ($file['size'] > MAX_FILE_SIZE) {
    return ['success' => false, 'message' => 'File too large'];
  }
  
  // Create unique filename
  $filename = $user_id . '_' . $document_type . '_' . time() . '.' . $ext;
  $filepath = UPLOAD_DIR . $filename;
  
  // Move uploaded file
  if (move_uploaded_file($file['tmp_name'], $filepath)) {
    return ['success' => true, 'filename' => $filename, 'path' => $filepath];
  }
  
  return ['success' => false, 'message' => 'Failed to upload file'];
}

// Create notification
function createNotification($user_id, $type, $title, $message, $booking_id = null) {
  global $conn;
  
  $stmt = $conn->prepare("
    INSERT INTO notifications (user_id, type, title, message, booking_id)
    VALUES (?, ?, ?, ?, ?)
  ");
  return $stmt->execute([$user_id, $type, $title, $message, $booking_id]);
}

// Get user notifications
function getUserNotifications($user_id, $limit = 20) {
  global $conn;
  
  $stmt = $conn->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT ?
  ");
  $stmt->execute([$user_id, $limit]);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
