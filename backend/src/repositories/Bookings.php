<?php
require_once __DIR__ . '/../db.php';

class BookingsRepository {
    public function create(int $userId, int $slotId, string $start, string $end): int {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO bookings (user_id, slot_id, starts_at, ends_at) VALUES (?, ?, ?, ?)');
        $stmt->execute([$userId, $slotId, $start, $end]);
        return intval($pdo->lastInsertId());
    }

    public function userBookings(int $userId): array {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT b.*, s.name AS slot_name FROM bookings b JOIN slots s ON s.id = b.slot_id WHERE b.user_id = ? ORDER BY b.starts_at DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function conflicts(int $slotId, string $start, string $end): bool {
        $pdo = Database::connection();
        $sql = "SELECT 1 FROM bookings b
                WHERE b.slot_id = ? AND b.status = 'active'
                AND NOT (b.ends_at <= ? OR b.starts_at >= ?) LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$slotId, $start, $end]);
        return (bool)$stmt->fetchColumn();
    }

    public function findById(int $id): ?array {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function cancel(int $id, int $userId): bool {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE bookings SET status = "cancelled" WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() > 0;
    }
}