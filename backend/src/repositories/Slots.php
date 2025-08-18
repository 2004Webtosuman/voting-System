<?php
require_once __DIR__ . '/../db.php';

class SlotsRepository {
    public function all(): array {
        $pdo = Database::connection();
        $stmt = $pdo->query('SELECT * FROM slots ORDER BY id');
        return $stmt->fetchAll();
    }

    public function findAvailableBetween(string $start, string $end): array {
        $pdo = Database::connection();
        $sql = "SELECT s.* FROM slots s
                WHERE s.status = 'available'
                AND NOT EXISTS (
                    SELECT 1 FROM bookings b
                    WHERE b.slot_id = s.id AND b.status = 'active'
                    AND NOT (b.ends_at <= ? OR b.starts_at >= ?)
                )";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$start, $end]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM slots WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(int $zoneId, string $name, float $x, float $y, string $status = 'available'): int {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO slots (zone_id, name, x, y, status) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$zoneId, $name, $x, $y, $status]);
        return intval($pdo->lastInsertId());
    }

    public function update(int $id, int $zoneId, string $name, float $x, float $y, string $status): bool {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE slots SET zone_id = ?, name = ?, x = ?, y = ?, status = ? WHERE id = ?');
        $stmt->execute([$zoneId, $name, $x, $y, $status, $id]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('DELETE FROM slots WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}