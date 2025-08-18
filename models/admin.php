<?php
require_once __DIR__ . '/db.php';

class AdminModel {
    public static function requireAdmin(array $user): void {
        if (($user['role'] ?? 'user') !== 'admin') {
            throw new RuntimeException('Forbidden');
        }
    }

    public static function createZone(string $name, ?string $description): int {
        $pdo = DatabaseConnection::get();
        $pdo->prepare('INSERT INTO zones (name, description) VALUES (?, ?)')->execute([$name, $description]);
        return intval($pdo->lastInsertId());
    }

    public static function updateZone(int $id, string $name, ?string $description): void {
        $pdo = DatabaseConnection::get();
        $pdo->prepare('UPDATE zones SET name = ?, description = ? WHERE id = ?')->execute([$name, $description, $id]);
    }

    public static function deleteZone(int $id): void {
        $pdo = DatabaseConnection::get();
        $pdo->prepare('DELETE FROM zones WHERE id = ?')->execute([$id]);
    }

    public static function createSlot(int $zoneId, string $label, int $x = null, int $y = null): int {
        $pdo = DatabaseConnection::get();
        $pdo->prepare('INSERT INTO slots (zone_id, label, is_available, x, y) VALUES (?, ?, 1, ?, ?)')->execute([$zoneId, $label, $x, $y]);
        return intval($pdo->lastInsertId());
    }

    public static function updateSlot(int $id, string $label, int $isAvailable, int $x = null, int $y = null): void {
        $pdo = DatabaseConnection::get();
        $pdo->prepare('UPDATE slots SET label = ?, is_available = ?, x = ?, y = ? WHERE id = ?')->execute([$label, $isAvailable, $x, $y, $id]);
    }

    public static function deleteSlot(int $id): void {
        $pdo = DatabaseConnection::get();
        $pdo->prepare('DELETE FROM slots WHERE id = ?')->execute([$id]);
    }

    public static function createEntrance(int $zoneId, string $name): int {
        $pdo = DatabaseConnection::get();
        $pdo->prepare('INSERT INTO entrances (zone_id, name) VALUES (?, ?)')->execute([$zoneId, $name]);
        return intval($pdo->lastInsertId());
    }

    public static function updateEntrance(int $id, string $name): void {
        $pdo = DatabaseConnection::get();
        $pdo->prepare('UPDATE entrances SET name = ? WHERE id = ?')->execute([$name, $id]);
    }

    public static function deleteEntrance(int $id): void {
        $pdo = DatabaseConnection::get();
        $pdo->prepare('DELETE FROM entrances WHERE id = ?')->execute([$id]);
    }

    public static function createEdge(int $zoneId, string $fromType, int $fromId, string $toType, int $toId, float $weight): int {
        $pdo = DatabaseConnection::get();
        $pdo->prepare('INSERT INTO graph_edges (zone_id, from_node_type, from_node_id, to_node_type, to_node_id, weight) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$zoneId, $fromType, $fromId, $toType, $toId, $weight]);
        return intval($pdo->lastInsertId());
    }

    public static function deleteEdge(int $id): void {
        $pdo = DatabaseConnection::get();
        $pdo->prepare('DELETE FROM graph_edges WHERE id = ?')->execute([$id]);
    }
}

