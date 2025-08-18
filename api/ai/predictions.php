<?php
include_once '../config/database.php';
include_once 'ParkingPredictor.php';

$database = new Database();
$db = $database->getConnection();

$predictor = new ParkingPredictor($db);

$data = json_decode(file_get_contents("php://input"));
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $zone_id = isset($_GET['zone_id']) ? $_GET['zone_id'] : null;
    $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    $hour = isset($_GET['hour']) ? $_GET['hour'] : date('H');
    $type = isset($_GET['type']) ? $_GET['type'] : 'single';

    if ($type === 'all_zones') {
        $predictions = $predictor->getPredictionsForAllZones($date, $hour);
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "predictions" => $predictions,
            "query" => [
                "date" => $date,
                "hour" => $hour,
                "type" => "all_zones"
            ]
        ]);
    } elseif ($type === 'peak_hours' && $zone_id) {
        $peakPrediction = $predictor->getPeakHoursPrediction($zone_id, $date);
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "peak_analysis" => $peakPrediction
        ]);
    } elseif ($zone_id) {
        $prediction = $predictor->predictOccupancy($zone_id, $date, $hour);
        
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "prediction" => $prediction
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "zone_id is required for single predictions"
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed"
    ]);
}
?>