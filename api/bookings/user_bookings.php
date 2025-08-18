<?php
include_once '../config/database.php';
include_once '../models/Booking.php';

$database = new Database();
$db = $database->getConnection();

$booking = new Booking($db);

// Verify authentication token
$headers = getallheaders();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;

if (!$token) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Authorization token required"]);
    exit();
}

$tokenData = json_decode(base64_decode($token), true);
if (!$tokenData || $tokenData['exp'] < time()) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Invalid or expired token"]);
    exit();
}

$stmt = $booking->getUserBookings($tokenData['user_id']);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

http_response_code(200);
echo json_encode([
    "success" => true,
    "bookings" => $bookings
]);
?>