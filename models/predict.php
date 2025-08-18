<?php
require_once __DIR__ . '/db.php';

class Predictor {
    // Simple linear regression y = a + b x on past 14 days hourly occupancy
    public static function predictZoneHourly(int $zoneId, int $hours = 24): array {
        $pdo = DatabaseConnection::get();
        $stmt = $pdo->prepare('
            SELECT DATE_FORMAT(hh, "%Y-%m-%d %H:00:00") AS hour, occupied
            FROM zone_hourly_occupancy
            WHERE zone_id = ? AND hh >= (NOW() - INTERVAL 14 DAY)
            ORDER BY hh ASC
        ');
        $stmt->execute([$zoneId]);
        $rows = $stmt->fetchAll();
        if (count($rows) < 4) {
            return [];
        }

        $xs = [];
        $ys = [];
        $i = 0;
        foreach ($rows as $r) {
            $xs[] = $i;
            $ys[] = floatval($r['occupied']);
            $i++;
        }
        $n = count($xs);
        $sumX = array_sum($xs);
        $sumY = array_sum($ys);
        $sumXX = 0.0;
        $sumXY = 0.0;
        for ($k = 0; $k < $n; $k++) {
            $sumXX += $xs[$k] * $xs[$k];
            $sumXY += $xs[$k] * $ys[$k];
        }
        $den = ($n * $sumXX - $sumX * $sumX);
        $b = $den != 0 ? ($n * $sumXY - $sumX * $sumY) / $den : 0.0;
        $a = ($sumY - $b * $sumX) / $n;

        $result = [];
        $startIdx = $n; // continue the timeline
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        for ($h = 0; $h < $hours; $h++) {
            $x = $startIdx + $h;
            $y = max(0.0, $a + $b * $x);
            $ts = $now->modify("+{$h} hour")->format('Y-m-d H:00:00');
            $result[] = [
                'hour' => $ts,
                'predicted_occupied' => round($y, 2)
            ];
        }
        return $result;
    }
}

