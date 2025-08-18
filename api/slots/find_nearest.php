<?php
include_once '../config/database.php';
include_once '../algorithms/DijkstraPathfinding.php';

$database = new Database();
$db = $database->getConnection();

$pathfinder = new DijkstraPathfinding($db);

// Get request data
$data = json_decode(file_get_contents("php://input"));

if (isset($data->latitude) && isset($data->longitude)) {
    $user_lat = $data->latitude;
    $user_lng = $data->longitude;
    
    $preferences = [
        'slot_type' => isset($data->slot_type) ? $data->slot_type : 'regular'
    ];

    // Get slot recommendations
    $recommendations = $pathfinder->getSlotRecommendations($user_lat, $user_lng, $preferences);

    if (!empty($recommendations)) {
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "recommendations" => $recommendations,
            "user_location" => [
                "latitude" => $user_lat,
                "longitude" => $user_lng
            ]
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "No available parking slots found"
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Latitude and longitude are required"
    ]);
}
?>