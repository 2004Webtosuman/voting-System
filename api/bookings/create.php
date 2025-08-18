<?php
include_once '../config/database.php';
include_once '../models/Booking.php';
include_once '../models/ParkingSlot.php';

$database = new Database();
$db = $database->getConnection();

$booking = new Booking($db);
$slot = new ParkingSlot($db);

$data = json_decode(file_get_contents("php://input"));

// Verify authentication token (simplified)
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

if (isset($data->slot_id) && isset($data->start_time) && isset($data->end_time)) {
    
    // Get slot details to calculate amount
    $slot->id = $data->slot_id;
    $slotDetails = $slot->readOne();
    
    if (!$slotDetails) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Slot not found"]);
        exit();
    }

    // Calculate total amount
    $start = new DateTime($data->start_time);
    $end = new DateTime($data->end_time);
    $duration = $end->diff($start)->h + ($end->diff($start)->i / 60);
    $total_amount = $duration * $slotDetails['hourly_rate'];

    $booking->user_id = $tokenData['user_id'];
    $booking->slot_id = $data->slot_id;
    $booking->start_time = $data->start_time;
    $booking->end_time = $data->end_time;
    $booking->total_amount = $total_amount;
    $booking->status = 'confirmed';

    if ($booking->create()) {
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Booking created successfully",
            "booking" => [
                "id" => $booking->id,
                "qr_code" => $booking->qr_code,
                "total_amount" => $total_amount,
                "slot_details" => $slotDetails
            ]
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Unable to create booking. Slot may not be available."
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Required fields: slot_id, start_time, end_time"
    ]);
}
?>