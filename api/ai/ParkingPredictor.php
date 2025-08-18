<?php
class ParkingPredictor {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Predict parking occupancy using linear regression
     */
    public function predictOccupancy($zone_id, $target_date, $target_hour) {
        // Get historical data for training
        $trainingData = $this->getTrainingData($zone_id);
        
        if (count($trainingData) < 10) {
            return $this->getAverageOccupancy($zone_id, $target_hour);
        }

        // Prepare features and target variables
        $features = [];
        $targets = [];
        
        foreach ($trainingData as $data) {
            $features[] = $this->extractFeatures($data, $target_date, $target_hour);
            $targets[] = $data['occupancy_rate'];
        }

        // Perform linear regression
        $coefficients = $this->linearRegression($features, $targets);
        
        // Make prediction
        $targetFeatures = $this->extractFeatures([
            'hour' => $target_hour,
            'is_weekend' => date('N', strtotime($target_date)) >= 6,
            'is_holiday' => $this->isHoliday($target_date),
            'weather' => $this->getWeatherForecast($target_date)
        ], $target_date, $target_hour);

        $prediction = $this->predict($coefficients, $targetFeatures);
        
        // Ensure prediction is within valid range
        $prediction = max(0, min(100, $prediction));

        return [
            'zone_id' => $zone_id,
            'date' => $target_date,
            'hour' => $target_hour,
            'predicted_occupancy' => round($prediction, 2),
            'confidence' => $this->calculateConfidence($trainingData, $prediction),
            'peak_probability' => $this->calculatePeakProbability($prediction)
        ];
    }

    /**
     * Get training data for the model
     */
    private function getTrainingData($zone_id, $limit = 1000) {
        $query = "SELECT * FROM parking_history 
                  WHERE zone_id = :zone_id 
                  ORDER BY date DESC, hour DESC 
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':zone_id', $zone_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Extract features from historical data
     */
    private function extractFeatures($data, $target_date, $target_hour) {
        return [
            1, // bias term
            $data['hour'] ?? $target_hour,
            ($data['is_weekend'] ?? (date('N', strtotime($target_date)) >= 6)) ? 1 : 0,
            ($data['is_holiday'] ?? $this->isHoliday($target_date)) ? 1 : 0,
            $this->getWeatherScore($data['weather'] ?? 'sunny'),
            $this->getHourScore($data['hour'] ?? $target_hour),
            $this->getSeasonScore($target_date)
        ];
    }

    /**
     * Perform linear regression using normal equation
     */
    private function linearRegression($X, $y) {
        $n = count($X);
        $m = count($X[0]);

        // Create matrices
        $X_matrix = $X;
        $y_vector = $y;

        // Calculate X^T * X
        $XtX = [];
        for ($i = 0; $i < $m; $i++) {
            $XtX[$i] = [];
            for ($j = 0; $j < $m; $j++) {
                $sum = 0;
                for ($k = 0; $k < $n; $k++) {
                    $sum += $X_matrix[$k][$i] * $X_matrix[$k][$j];
                }
                $XtX[$i][$j] = $sum;
            }
        }

        // Calculate X^T * y
        $Xty = [];
        for ($i = 0; $i < $m; $i++) {
            $sum = 0;
            for ($k = 0; $k < $n; $k++) {
                $sum += $X_matrix[$k][$i] * $y_vector[$k];
            }
            $Xty[$i] = $sum;
        }

        // Solve using simplified approach (for demonstration)
        // In production, use proper matrix inversion
        $coefficients = $this->solveLinearSystem($XtX, $Xty);

        return $coefficients;
    }

    /**
     * Simplified linear system solver
     */
    private function solveLinearSystem($A, $b) {
        $n = count($A);
        $x = array_fill(0, $n, 0);

        // Simplified Gaussian elimination
        for ($i = 0; $i < $n; $i++) {
            // Find pivot
            $maxRow = $i;
            for ($k = $i + 1; $k < $n; $k++) {
                if (abs($A[$k][$i]) > abs($A[$maxRow][$i])) {
                    $maxRow = $k;
                }
            }

            // Swap rows
            if ($maxRow != $i) {
                $temp = $A[$i];
                $A[$i] = $A[$maxRow];
                $A[$maxRow] = $temp;

                $temp = $b[$i];
                $b[$i] = $b[$maxRow];
                $b[$maxRow] = $temp;
            }

            // Make all rows below this one 0 in current column
            for ($k = $i + 1; $k < $n; $k++) {
                if ($A[$i][$i] != 0) {
                    $c = $A[$k][$i] / $A[$i][$i];
                    for ($j = $i; $j < $n; $j++) {
                        $A[$k][$j] -= $c * $A[$i][$j];
                    }
                    $b[$k] -= $c * $b[$i];
                }
            }
        }

        // Back substitution
        for ($i = $n - 1; $i >= 0; $i--) {
            $x[$i] = $b[$i];
            for ($j = $i + 1; $j < $n; $j++) {
                $x[$i] -= $A[$i][$j] * $x[$j];
            }
            if ($A[$i][$i] != 0) {
                $x[$i] /= $A[$i][$i];
            }
        }

        return $x;
    }

    /**
     * Make prediction using trained coefficients
     */
    private function predict($coefficients, $features) {
        $prediction = 0;
        for ($i = 0; $i < count($coefficients) && $i < count($features); $i++) {
            $prediction += $coefficients[$i] * $features[$i];
        }
        return $prediction;
    }

    /**
     * Get weather score for prediction
     */
    private function getWeatherScore($weather) {
        $scores = [
            'sunny' => 1.0,
            'cloudy' => 0.9,
            'rainy' => 0.7,
            'stormy' => 0.5,
            'snowy' => 0.6
        ];
        
        return $scores[$weather] ?? 0.8;
    }

    /**
     * Get hour score based on typical patterns
     */
    private function getHourScore($hour) {
        // Peak hours get higher scores
        if ($hour >= 8 && $hour <= 10) return 1.0; // Morning peak
        if ($hour >= 17 && $hour <= 19) return 1.0; // Evening peak
        if ($hour >= 12 && $hour <= 14) return 0.8; // Lunch time
        if ($hour >= 20 && $hour <= 22) return 0.7; // Evening
        return 0.5; // Off-peak
    }

    /**
     * Get season score
     */
    private function getSeasonScore($date) {
        $month = date('n', strtotime($date));
        
        // Summer months typically have higher parking usage
        if ($month >= 6 && $month <= 8) return 1.0;
        if ($month >= 3 && $month <= 5) return 0.9; // Spring
        if ($month >= 9 && $month <= 11) return 0.8; // Fall
        return 0.7; // Winter
    }

    /**
     * Check if date is a holiday
     */
    private function isHoliday($date) {
        // Simplified holiday check - in production, use a proper holiday API
        $holidays = [
            '01-01', '07-04', '12-25', '11-24' // New Year, July 4th, Christmas, Thanksgiving
        ];
        
        $dateFormat = date('m-d', strtotime($date));
        return in_array($dateFormat, $holidays);
    }

    /**
     * Get weather forecast (simplified - in production use weather API)
     */
    private function getWeatherForecast($date) {
        // Simplified weather prediction
        $weather_types = ['sunny', 'cloudy', 'rainy'];
        return $weather_types[array_rand($weather_types)];
    }

    /**
     * Calculate prediction confidence
     */
    private function calculateConfidence($trainingData, $prediction) {
        if (count($trainingData) < 5) return 0.5;
        
        $variance = 0;
        $mean = array_sum(array_column($trainingData, 'occupancy_rate')) / count($trainingData);
        
        foreach ($trainingData as $data) {
            $variance += pow($data['occupancy_rate'] - $mean, 2);
        }
        $variance /= count($trainingData);
        
        $confidence = max(0.3, min(0.95, 1 - ($variance / 1000)));
        return round($confidence, 2);
    }

    /**
     * Calculate peak probability
     */
    private function calculatePeakProbability($occupancy) {
        if ($occupancy >= 85) return 'very_high';
        if ($occupancy >= 70) return 'high';
        if ($occupancy >= 50) return 'medium';
        if ($occupancy >= 30) return 'low';
        return 'very_low';
    }

    /**
     * Get average occupancy as fallback
     */
    private function getAverageOccupancy($zone_id, $hour) {
        $query = "SELECT AVG(occupancy_rate) as avg_occupancy 
                  FROM parking_history 
                  WHERE zone_id = :zone_id AND hour = :hour";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':zone_id', $zone_id);
        $stmt->bindParam(':hour', $hour);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['avg_occupancy'] ?? 50.0;
    }

    /**
     * Get predictions for all zones for a specific time
     */
    public function getPredictionsForAllZones($target_date, $target_hour) {
        $query = "SELECT id, name FROM parking_zones WHERE is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $zones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $predictions = [];
        foreach ($zones as $zone) {
            $prediction = $this->predictOccupancy($zone['id'], $target_date, $target_hour);
            $prediction['zone_name'] = $zone['name'];
            $predictions[] = $prediction;
        }

        return $predictions;
    }

    /**
     * Get peak hours prediction for a zone
     */
    public function getPeakHoursPrediction($zone_id, $date) {
        $predictions = [];
        
        for ($hour = 6; $hour <= 23; $hour++) {
            $prediction = $this->predictOccupancy($zone_id, $date, $hour);
            $predictions[] = $prediction;
        }

        // Sort by predicted occupancy
        usort($predictions, function($a, $b) {
            return $b['predicted_occupancy'] <=> $a['predicted_occupancy'];
        });

        return [
            'zone_id' => $zone_id,
            'date' => $date,
            'peak_hours' => array_slice($predictions, 0, 5),
            'off_peak_hours' => array_slice($predictions, -5)
        ];
    }
}
?>