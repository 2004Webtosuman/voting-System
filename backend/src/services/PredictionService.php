<?php
require_once __DIR__ . '/../db.php';

class PredictionService {
    public function hourlyOccupancyNext24h(): array {
        $pdo = Database::connection();
        // Aggregate past 30 days occupancy by hour of day
        $sql = "SELECT HOUR(ts) as hour_of_day, AVG(occupied) as avg_occ
                FROM occupancy_log
                WHERE ts >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY HOUR(ts)";
        $rows = $pdo->query($sql)->fetchAll();
        $hourToAvg = array_fill(0, 24, 0.2);
        foreach ($rows as $r) {
            $hourToAvg[intval($r['hour_of_day'])] = floatval($r['avg_occ']);
        }
        $result = [];
        $now = new DateTimeImmutable('now');
        for ($i = 0; $i < 24; $i++) {
            $t = $now->modify("+$i hour");
            $result[] = [
                'ts' => $t->format('Y-m-d H:00:00'),
                'predicted_occupancy' => $hourToAvg[intval($t->format('G'))]
            ];
        }
        return $result;
    }
}