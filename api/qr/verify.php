<?php
include_once '../config/database.php';
include_once '../models/Booking.php';

$database = new Database();
$db = $database->getConnection();

$booking = new Booking($db);

$data = json_decode(file_get_contents("php://input"));

if (isset($data->qr_token)) {
    $qrData = $booking->verifyQRCode($data->qr_token);
    
    if ($qrData) {
        // Determine action based on booking status
        $action = '';
        $newStatus = '';
        
        if ($qrData['status'] === 'confirmed') {
            // Entry
            $action = 'entry';
            $newStatus = 'active';
            $booking->entry_time = date('Y-m-d H:i:s');
        } elseif ($qrData['status'] === 'active') {
            // Exit
            $action = 'exit';
            $newStatus = 'completed';
            $booking->exit_time = date('Y-m-d H:i:s');
        } else {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Invalid booking status for QR scan"
            ]);
            exit();
        }

        // Update booking status
        $booking->id = $qrData['booking_id'];
        $booking->status = $newStatus;
        
        if ($booking->updateStatus()) {
            // Mark QR code as used
            $booking->useQRCode($data->qr_token);
            
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "action" => $action,
                "message" => ucfirst($action) . " successful",
                "booking_details" => [
                    "booking_id" => $qrData['booking_id'],
                    "slot_number" => $qrData['slot_number'] ?? 'N/A',
                    "user_id" => $qrData['user_id'],
                    "timestamp" => date('Y-m-d H:i:s')
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Failed to update booking status"
            ]);
        }
    } else {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Invalid or expired QR code"
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "QR token is required"
    ]);
}
?>