<?php
require_once __DIR__ . '/db.php';

class ParkingModel {
    public static function listZones(): array {
        $pdo = DatabaseConnection::get();
        $stmt = $pdo->query('SELECT id, name, description FROM zones ORDER BY id');
        return $stmt->fetchAll();
    }

    public static function listSlotsByZone(int $zoneId): array {
        $pdo = DatabaseConnection::get();
        $stmt = $pdo->prepare('SELECT id, zone_id, label, is_available, x, y FROM slots WHERE zone_id = ? ORDER BY id');
        $stmt->execute([$zoneId]);
        return $stmt->fetchAll();
    }

    public static function listEntrances(int $zoneId): array {
        $pdo = DatabaseConnection::get();
        $stmt = $pdo->prepare('SELECT id, zone_id, name FROM entrances WHERE zone_id = ? ORDER BY id');
        $stmt->execute([$zoneId]);
        return $stmt->fetchAll();
    }

    public static function listEdges(int $zoneId): array {
        $pdo = DatabaseConnection::get();
        $stmt = $pdo->prepare('SELECT id, zone_id, from_node_type, from_node_id, to_node_type, to_node_id, weight FROM graph_edges WHERE zone_id = ?');
        $stmt->execute([$zoneId]);
        return $stmt->fetchAll();
    }

    public static function createBooking(int $userId, int $slotId, string $startTime, string $endTime): int {
        $pdo = DatabaseConnection::get();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT is_available FROM slots WHERE id = ? FOR UPDATE');
            $stmt->execute([$slotId]);
            $slot = $stmt->fetch();
            if (!$slot || !$slot['is_available']) {
                $pdo->rollBack();
                throw new RuntimeException('Slot not available');
            }

            $pdo->prepare('INSERT INTO bookings (user_id, slot_id, start_time, end_time, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())')
                ->execute([$userId, $slotId, $startTime, $endTime, 'booked']);
            $bookingId = intval($pdo->lastInsertId());

            $pdo->prepare('UPDATE slots SET is_available = 0 WHERE id = ?')->execute([$slotId]);
            $pdo->commit();
            return $bookingId;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public static function releaseBooking(int $bookingId, ?string $exitQr = null): void {
        $pdo = DatabaseConnection::get();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT slot_id FROM bookings WHERE id = ? AND status = "booked" FOR UPDATE');
            $stmt->execute([$bookingId]);
            $row = $stmt->fetch();
            if (!$row) {
                $pdo->rollBack();
                throw new RuntimeException('Booking not found or already closed');
            }
            $slotId = intval($row['slot_id']);
            $pdo->prepare('UPDATE bookings SET status = "completed", exit_qr = ?, completed_at = NOW() WHERE id = ?')->execute([$exitQr, $bookingId]);
            $pdo->prepare('UPDATE slots SET is_available = 1 WHERE id = ?')->execute([$slotId]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public static function nearestAvailableSlotByDijkstra(int $zoneId, int $entranceId): ?array {
        $pdo = DatabaseConnection::get();
        $entranceKey = 'entrance:' . $entranceId;
        $nodes = [];
        $edges = [];

        // Nodes: entrances and slots of zone
        foreach (self::listEntrances($zoneId) as $e) {
            $nodes['entrance:' . $e['id']] = true;
        }
        foreach (self::listSlotsByZone($zoneId) as $s) {
            $nodes['slot:' . $s['id']] = $s; // store slot info
        }
        foreach (self::listEdges($zoneId) as $edge) {
            $from = $edge['from_node_type'] . ':' . $edge['from_node_id'];
            $to = $edge['to_node_type'] . ':' . $edge['to_node_id'];
            $w = floatval($edge['weight']);
            $edges[$from][] = [$to, $w];
            $edges[$to][] = [$from, $w]; // undirected
        }

        if (!isset($nodes[$entranceKey])) {
            return null;
        }

        // Dijkstra
        $dist = [];
        $prev = [];
        $visited = [];
        foreach ($nodes as $nodeKey => $_) {
            $dist[$nodeKey] = INF;
            $prev[$nodeKey] = null;
        }
        $dist[$entranceKey] = 0.0;

        // Simple O(V^2) implementation
        while (true) {
            $u = null;
            $min = INF;
            foreach ($dist as $node => $d) {
                if (!($visited[$node] ?? false)) {
                    if ($d < $min) { $min = $d; $u = $node; }
                }
            }
            if ($u === null) break;
            $visited[$u] = true;
            if (!isset($edges[$u])) continue;
            foreach ($edges[$u] as [$v, $w]) {
                if (($visited[$v] ?? false)) continue;
                $alt = $dist[$u] + $w;
                if ($alt < $dist[$v]) {
                    $dist[$v] = $alt;
                    $prev[$v] = $u;
                }
            }
        }

        // Choose nearest available slot
        $bestSlot = null;
        $bestDist = INF;
        foreach ($nodes as $key => $val) {
            if (!str_starts_with($key, 'slot:')) continue;
            $slot = $val;
            if (!$slot['is_available']) continue;
            $d = $dist[$key] ?? INF;
            if ($d < $bestDist) { $bestDist = $d; $bestSlot = $slot; }
        }

        if ($bestSlot === null || $bestDist === INF) return null;
        $bestSlot['distance'] = $bestDist;
        return $bestSlot;
    }
}

