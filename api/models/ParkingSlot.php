<?php
class ParkingSlot {
    private $conn;
    private $table_name = "parking_slots";

    public $id;
    public $zone_id;
    public $slot_number;
    public $x_coordinate;
    public $y_coordinate;
    public $status;
    public $slot_type;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get all available slots
    public function getAvailableSlots() {
        $query = "SELECT ps.*, pz.name as zone_name, pz.hourly_rate 
                  FROM " . $this->table_name . " ps
                  JOIN parking_zones pz ON ps.zone_id = pz.id
                  WHERE ps.status = 'available' AND pz.is_active = 1
                  ORDER BY pz.name, ps.slot_number";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Get slots by zone
    public function getSlotsByZone($zone_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE zone_id = :zone_id 
                  ORDER BY slot_number";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':zone_id', $zone_id);
        $stmt->execute();
        return $stmt;
    }

    // Update slot status
    public function updateStatus() {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = :status 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':id', $this->id);

        return $stmt->execute();
    }

    // Find nearest available slot using coordinates
    public function findNearestSlot($user_x, $user_y, $slot_type = 'regular') {
        $query = "SELECT ps.*, pz.name as zone_name, pz.hourly_rate,
                  (6371 * acos(cos(radians(:user_y)) * cos(radians(ps.y_coordinate)) * 
                  cos(radians(ps.x_coordinate) - radians(:user_x)) + 
                  sin(radians(:user_y)) * sin(radians(ps.y_coordinate)))) AS distance
                  FROM " . $this->table_name . " ps
                  JOIN parking_zones pz ON ps.zone_id = pz.id
                  WHERE ps.status = 'available' AND pz.is_active = 1";
        
        if ($slot_type !== 'any') {
            $query .= " AND ps.slot_type = :slot_type";
        }
        
        $query .= " ORDER BY distance LIMIT 10";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_x', $user_x);
        $stmt->bindParam(':user_y', $user_y);
        
        if ($slot_type !== 'any') {
            $stmt->bindParam(':slot_type', $slot_type);
        }

        $stmt->execute();
        return $stmt;
    }

    // Get slot details
    public function readOne() {
        $query = "SELECT ps.*, pz.name as zone_name, pz.hourly_rate 
                  FROM " . $this->table_name . " ps
                  JOIN parking_zones pz ON ps.zone_id = pz.id
                  WHERE ps.id = :id LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->zone_id = $row['zone_id'];
            $this->slot_number = $row['slot_number'];
            $this->x_coordinate = $row['x_coordinate'];
            $this->y_coordinate = $row['y_coordinate'];
            $this->status = $row['status'];
            $this->slot_type = $row['slot_type'];
            return $row;
        }
        return false;
    }

    // Create new slot (admin only)
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET zone_id=:zone_id, slot_number=:slot_number, 
                      x_coordinate=:x_coordinate, y_coordinate=:y_coordinate, 
                      slot_type=:slot_type";

        $stmt = $this->conn->prepare($query);

        $this->zone_id = htmlspecialchars(strip_tags($this->zone_id));
        $this->slot_number = htmlspecialchars(strip_tags($this->slot_number));
        $this->x_coordinate = htmlspecialchars(strip_tags($this->x_coordinate));
        $this->y_coordinate = htmlspecialchars(strip_tags($this->y_coordinate));
        $this->slot_type = htmlspecialchars(strip_tags($this->slot_type));

        $stmt->bindParam(':zone_id', $this->zone_id);
        $stmt->bindParam(':slot_number', $this->slot_number);
        $stmt->bindParam(':x_coordinate', $this->x_coordinate);
        $stmt->bindParam(':y_coordinate', $this->y_coordinate);
        $stmt->bindParam(':slot_type', $this->slot_type);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }
}
?>