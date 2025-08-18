<?php
require_once __DIR__ . '/../db.php';

class ZonesRepository {
    public function all(): array {
        $pdo = Database::connection();
        $stmt = $pdo->query('SELECT * FROM zones ORDER BY id');
        return $stmt->fetchAll();
    }

    public function create(string $name, float $x, float $y): int {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO zones (name, x, y) VALUES (?, ?, ?)');
        $stmt->execute([$name, $x, $y]);
        return intval($pdo->lastInsertId());
    }

    public function update(int $id, string $name, float $x, float $y): bool {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE zones SET name = ?, x = ?, y = ? WHERE id = ?');
        $stmt->execute([$name, $x, $y, $id]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('DELETE FROM zones WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}