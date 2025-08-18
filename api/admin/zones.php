<?php
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Verify admin authentication
$headers = getallheaders();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;

if (!$token) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Authorization token required"]);
    exit();
}

$tokenData = json_decode(base64_decode($token), true);
if (!$tokenData || $tokenData['exp'] < time() || $tokenData['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Admin access required"]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getAllZones($db);
        break;
    case 'POST':
        createZone($db);
        break;
    case 'PUT':
        updateZone($db);
        break;
    case 'DELETE':
        deleteZone($db);
        break;
    default:
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Method not allowed"]);
}

function getAllZones($db) {
    $query = "SELECT pz.*, 
              (SELECT COUNT(*) FROM parking_slots ps WHERE ps.zone_id = pz.id) as total_slots,
              (SELECT COUNT(*) FROM parking_slots ps WHERE ps.zone_id = pz.id AND ps.status = 'available') as available_slots
              FROM parking_zones pz 
              ORDER BY pz.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $zones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "zones" => $zones
    ]);
}

function createZone($db) {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->name) || !isset($data->x_coordinate) || !isset($data->y_coordinate) || 
        !isset($data->total_slots) || !isset($data->hourly_rate)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Missing required fields"]);
        return;
    }
    
    $query = "INSERT INTO parking_zones 
              SET name=:name, description=:description, x_coordinate=:x_coordinate, 
                  y_coordinate=:y_coordinate, total_slots=:total_slots, hourly_rate=:hourly_rate";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':name', $data->name);
    $stmt->bindParam(':description', $data->description);
    $stmt->bindParam(':x_coordinate', $data->x_coordinate);
    $stmt->bindParam(':y_coordinate', $data->y_coordinate);
    $stmt->bindParam(':total_slots', $data->total_slots);
    $stmt->bindParam(':hourly_rate', $data->hourly_rate);
    
    if ($stmt->execute()) {
        $zoneId = $db->lastInsertId();
        
        // Create parking slots for the zone
        createSlotsForZone($db, $zoneId, $data->total_slots, $data->x_coordinate, $data->y_coordinate);
        
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "Zone created successfully",
            "zone_id" => $zoneId
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Failed to create zone"]);
    }
}

function updateZone($db) {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->id)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Zone ID required"]);
        return;
    }
    
    $query = "UPDATE parking_zones 
              SET name=:name, description=:description, x_coordinate=:x_coordinate, 
                  y_coordinate=:y_coordinate, total_slots=:total_slots, hourly_rate=:hourly_rate,
                  is_active=:is_active
              WHERE id=:id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $data->id);
    $stmt->bindParam(':name', $data->name);
    $stmt->bindParam(':description', $data->description);
    $stmt->bindParam(':x_coordinate', $data->x_coordinate);
    $stmt->bindParam(':y_coordinate', $data->y_coordinate);
    $stmt->bindParam(':total_slots', $data->total_slots);
    $stmt->bindParam(':hourly_rate', $data->hourly_rate);
    $stmt->bindParam(':is_active', $data->is_active, PDO::PARAM_BOOL);
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Zone updated successfully"
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Failed to update zone"]);
    }
}

function deleteZone($db) {
    $zoneId = $_GET['id'] ?? null;
    
    if (!$zoneId) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Zone ID required"]);
        return;
    }
    
    // Check if zone has active bookings
    $checkQuery = "SELECT COUNT(*) as count FROM bookings b 
                   JOIN parking_slots ps ON b.slot_id = ps.id 
                   WHERE ps.zone_id = :zone_id AND b.status IN ('confirmed', 'active')";
    
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':zone_id', $zoneId);
    $checkStmt->execute();
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        http_response_code(400);
        echo json_encode([
            "success" => false, 
            "message" => "Cannot delete zone with active bookings"
        ]);
        return;
    }
    
    $query = "DELETE FROM parking_zones WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $zoneId);
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Zone deleted successfully"
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Failed to delete zone"]);
    }
}

function createSlotsForZone($db, $zoneId, $totalSlots, $baseLat, $baseLng) {
    $slotsPerRow = 10;
    $slotSpacing = 0.00001; // Approximate spacing between slots
    
    for ($i = 1; $i <= $totalSlots; $i++) {
        $row = ceil($i / $slotsPerRow);
        $col = (($i - 1) % $slotsPerRow) + 1;
        
        $slotLat = $baseLat + ($row * $slotSpacing);
        $slotLng = $baseLng + ($col * $slotSpacing);
        
        $slotNumber = chr(65 + ($zoneId - 1)) . sprintf('%02d', $i);
        
        // Determine slot type (90% regular, 5% disabled, 5% electric)
        $rand = mt_rand(1, 100);
        if ($rand <= 5) {
            $slotType = 'disabled';
        } elseif ($rand <= 10) {
            $slotType = 'electric';
        } else {
            $slotType = 'regular';
        }
        
        $query = "INSERT INTO parking_slots 
                  SET zone_id=:zone_id, slot_number=:slot_number, 
                      x_coordinate=:x_coordinate, y_coordinate=:y_coordinate, 
                      slot_type=:slot_type";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':zone_id', $zoneId);
        $stmt->bindParam(':slot_number', $slotNumber);
        $stmt->bindParam(':x_coordinate', $slotLat);
        $stmt->bindParam(':y_coordinate', $slotLng);
        $stmt->bindParam(':slot_type', $slotType);
        $stmt->execute();
    }
}
?>