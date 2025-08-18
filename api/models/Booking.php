<?php
class Booking {
    private $conn;
    private $table_name = "bookings";

    public $id;
    public $user_id;
    public $slot_id;
    public $start_time;
    public $end_time;
    public $total_amount;
    public $status;
    public $qr_code;
    public $entry_time;
    public $exit_time;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create booking
    public function create() {
        // Check if slot is available
        if (!$this->isSlotAvailable()) {
            return false;
        }

        $query = "INSERT INTO " . $this->table_name . " 
                  SET user_id=:user_id, slot_id=:slot_id, start_time=:start_time, 
                      end_time=:end_time, total_amount=:total_amount, 
                      status=:status, qr_code=:qr_code";

        $stmt = $this->conn->prepare($query);

        // Generate QR code token
        $this->qr_code = $this->generateQRToken();

        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':slot_id', $this->slot_id);
        $stmt->bindParam(':start_time', $this->start_time);
        $stmt->bindParam(':end_time', $this->end_time);
        $stmt->bindParam(':total_amount', $this->total_amount);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':qr_code', $this->qr_code);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            
            // Update slot status to reserved
            $this->updateSlotStatus('reserved');
            
            // Create QR code entry
            $this->createQRCode();
            
            return true;
        }
        return false;
    }

    // Get user bookings
    public function getUserBookings($user_id) {
        $query = "SELECT b.*, ps.slot_number, pz.name as zone_name, 
                         ps.x_coordinate, ps.y_coordinate
                  FROM " . $this->table_name . " b
                  JOIN parking_slots ps ON b.slot_id = ps.id
                  JOIN parking_zones pz ON ps.zone_id = pz.id
                  WHERE b.user_id = :user_id
                  ORDER BY b.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt;
    }

    // Get booking by ID
    public function readOne() {
        $query = "SELECT b.*, ps.slot_number, pz.name as zone_name, 
                         ps.x_coordinate, ps.y_coordinate, u.full_name, u.email
                  FROM " . $this->table_name . " b
                  JOIN parking_slots ps ON b.slot_id = ps.id
                  JOIN parking_zones pz ON ps.zone_id = pz.id
                  JOIN users u ON b.user_id = u.id
                  WHERE b.id = :id LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update booking status
    public function updateStatus() {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = :status";
        
        if ($this->entry_time) {
            $query .= ", entry_time = :entry_time";
        }
        
        if ($this->exit_time) {
            $query .= ", exit_time = :exit_time";
        }
        
        $query .= " WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':id', $this->id);
        
        if ($this->entry_time) {
            $stmt->bindParam(':entry_time', $this->entry_time);
        }
        
        if ($this->exit_time) {
            $stmt->bindParam(':exit_time', $this->exit_time);
        }

        if($stmt->execute()) {
            // Update slot status based on booking status
            if ($this->status === 'active') {
                $this->updateSlotStatus('occupied');
            } elseif ($this->status === 'completed' || $this->status === 'cancelled') {
                $this->updateSlotStatus('available');
            }
            return true;
        }
        return false;
    }

    // Cancel booking
    public function cancel() {
        $this->status = 'cancelled';
        if ($this->updateStatus()) {
            $this->updateSlotStatus('available');
            return true;
        }
        return false;
    }

    // Check if slot is available for the requested time
    private function isSlotAvailable() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                  WHERE slot_id = :slot_id 
                  AND status IN ('confirmed', 'active', 'reserved')
                  AND (
                      (start_time <= :start_time AND end_time > :start_time) OR
                      (start_time < :end_time AND end_time >= :end_time) OR
                      (start_time >= :start_time AND end_time <= :end_time)
                  )";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':slot_id', $this->slot_id);
        $stmt->bindParam(':start_time', $this->start_time);
        $stmt->bindParam(':end_time', $this->end_time);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'] == 0;
    }

    // Update slot status
    private function updateSlotStatus($status) {
        $query = "UPDATE parking_slots SET status = :status WHERE id = :slot_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':slot_id', $this->slot_id);
        return $stmt->execute();
    }

    // Generate QR token
    private function generateQRToken() {
        return hash('sha256', uniqid() . time() . $this->user_id . $this->slot_id);
    }

    // Create QR code entry
    private function createQRCode() {
        $expires_at = date('Y-m-d H:i:s', strtotime($this->end_time . ' +1 hour'));
        
        $query = "INSERT INTO qr_codes (booking_id, qr_token, expires_at) 
                  VALUES (:booking_id, :qr_token, :expires_at)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':booking_id', $this->id);
        $stmt->bindParam(':qr_token', $this->qr_code);
        $stmt->bindParam(':expires_at', $expires_at);
        
        return $stmt->execute();
    }

    // Verify QR code
    public function verifyQRCode($qr_token) {
        $query = "SELECT qc.*, b.* FROM qr_codes qc
                  JOIN bookings b ON qc.booking_id = b.id
                  WHERE qc.qr_token = :qr_token 
                  AND qc.expires_at > NOW() 
                  AND qc.is_used = FALSE";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':qr_token', $qr_token);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Mark QR code as used
    public function useQRCode($qr_token) {
        $query = "UPDATE qr_codes SET is_used = TRUE WHERE qr_token = :qr_token";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':qr_token', $qr_token);
        return $stmt->execute();
    }
}
?>