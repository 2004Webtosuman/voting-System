<?php
require_once __DIR__ . '/../db.php';

class EdgesRepository {
    public function neighbors(int $fromSlotId): array {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT to_slot_id as toSlotId, weight FROM edges WHERE from_slot_id = ?');
        $stmt->execute([$fromSlotId]);
        return $stmt->fetchAll();
    }

    public function create(int $from, int $to, float $weight): int {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO edges (from_slot_id, to_slot_id, weight) VALUES (?, ?, ?)');
        $stmt->execute([$from, $to, $weight]);
        return intval($pdo->lastInsertId());
    }

    public function delete(int $id): bool {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('DELETE FROM edges WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}