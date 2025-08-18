<?php
class DijkstraPathfinding {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Find the nearest available parking slot using Dijkstra's algorithm
     * considering traffic conditions and distance
     */
    public function findNearestSlot($user_lat, $user_lng, $preferences = []) {
        // Get all available slots with their coordinates
        $slots = $this->getAvailableSlots($preferences);
        
        if (empty($slots)) {
            return null;
        }

        // Create a graph with parking zones as nodes
        $graph = $this->buildParkingGraph();
        
        // Find the nearest zone using Dijkstra's algorithm
        $nearestZone = $this->dijkstra($graph, $user_lat, $user_lng);
        
        // Get the best slot from the nearest zone
        $bestSlot = $this->getBestSlotFromZone($nearestZone, $slots, $user_lat, $user_lng);
        
        return $bestSlot;
    }

    /**
     * Get all available parking slots
     */
    private function getAvailableSlots($preferences) {
        $slot_type_filter = isset($preferences['slot_type']) ? $preferences['slot_type'] : 'regular';
        
        $query = "SELECT ps.*, pz.name as zone_name, pz.hourly_rate, pz.x_coordinate as zone_x, pz.y_coordinate as zone_y
                  FROM parking_slots ps
                  JOIN parking_zones pz ON ps.zone_id = pz.id
                  WHERE ps.status = 'available' AND pz.is_active = 1";
        
        if ($slot_type_filter !== 'any') {
            $query .= " AND ps.slot_type = :slot_type";
        }
        
        $query .= " ORDER BY pz.id, ps.slot_number";

        $stmt = $this->conn->prepare($query);
        
        if ($slot_type_filter !== 'any') {
            $stmt->bindParam(':slot_type', $slot_type_filter);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Build a graph representation of parking zones
     */
    private function buildParkingGraph() {
        $query = "SELECT id, name, x_coordinate, y_coordinate, hourly_rate 
                  FROM parking_zones WHERE is_active = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $zones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $graph = [];
        
        // Create nodes for each zone
        foreach ($zones as $zone) {
            $graph[$zone['id']] = [
                'name' => $zone['name'],
                'lat' => $zone['x_coordinate'],
                'lng' => $zone['y_coordinate'],
                'rate' => $zone['hourly_rate'],
                'connections' => []
            ];
        }

        // Create connections between zones (simulate road network)
        foreach ($zones as $zone1) {
            foreach ($zones as $zone2) {
                if ($zone1['id'] !== $zone2['id']) {
                    $distance = $this->calculateDistance(
                        $zone1['x_coordinate'], $zone1['y_coordinate'],
                        $zone2['x_coordinate'], $zone2['y_coordinate']
                    );
                    
                    // Add traffic factor (simulate varying traffic conditions)
                    $trafficFactor = $this->getTrafficFactor($zone1['id'], $zone2['id']);
                    $weight = $distance * $trafficFactor;
                    
                    $graph[$zone1['id']]['connections'][$zone2['id']] = $weight;
                }
            }
        }

        return $graph;
    }

    /**
     * Dijkstra's algorithm implementation
     */
    private function dijkstra($graph, $user_lat, $user_lng) {
        $distances = [];
        $previous = [];
        $unvisited = [];

        // Initialize distances
        foreach ($graph as $zoneId => $zone) {
            $distances[$zoneId] = INF;
            $previous[$zoneId] = null;
            $unvisited[$zoneId] = true;
        }

        // Find the closest zone to user as starting point
        $startZone = $this->findClosestZone($graph, $user_lat, $user_lng);
        $distances[$startZone] = 0;

        while (!empty($unvisited)) {
            // Find unvisited node with minimum distance
            $currentZone = null;
            $minDistance = INF;
            
            foreach ($unvisited as $zoneId => $value) {
                if ($distances[$zoneId] < $minDistance) {
                    $minDistance = $distances[$zoneId];
                    $currentZone = $zoneId;
                }
            }

            if ($currentZone === null || $minDistance === INF) {
                break;
            }

            unset($unvisited[$currentZone]);

            // Update distances to neighbors
            foreach ($graph[$currentZone]['connections'] as $neighborId => $weight) {
                if (isset($unvisited[$neighborId])) {
                    $altDistance = $distances[$currentZone] + $weight;
                    
                    if ($altDistance < $distances[$neighborId]) {
                        $distances[$neighborId] = $altDistance;
                        $previous[$neighborId] = $currentZone;
                    }
                }
            }
        }

        // Find the zone with minimum distance that has available slots
        $bestZone = null;
        $minDist = INF;
        
        foreach ($distances as $zoneId => $distance) {
            if ($distance < $minDist && $this->hasAvailableSlots($zoneId)) {
                $minDist = $distance;
                $bestZone = $zoneId;
            }
        }

        return $bestZone;
    }

    /**
     * Find the closest zone to user location
     */
    private function findClosestZone($graph, $user_lat, $user_lng) {
        $closestZone = null;
        $minDistance = INF;

        foreach ($graph as $zoneId => $zone) {
            $distance = $this->calculateDistance($user_lat, $user_lng, $zone['lat'], $zone['lng']);
            
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $closestZone = $zoneId;
            }
        }

        return $closestZone;
    }

    /**
     * Get the best slot from the selected zone
     */
    private function getBestSlotFromZone($zoneId, $slots, $user_lat, $user_lng) {
        $zoneSlots = array_filter($slots, function($slot) use ($zoneId) {
            return $slot['zone_id'] == $zoneId;
        });

        if (empty($zoneSlots)) {
            return null;
        }

        // Sort slots by distance from user within the zone
        usort($zoneSlots, function($a, $b) use ($user_lat, $user_lng) {
            $distA = $this->calculateDistance($user_lat, $user_lng, $a['x_coordinate'], $a['y_coordinate']);
            $distB = $this->calculateDistance($user_lat, $user_lng, $b['x_coordinate'], $b['y_coordinate']);
            
            return $distA <=> $distB;
        });

        return $zoneSlots[0];
    }

    /**
     * Calculate distance between two points using Haversine formula
     */
    private function calculateDistance($lat1, $lng1, $lat2, $lng2) {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng/2) * sin($dLng/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;

        return $distance;
    }

    /**
     * Get traffic factor between two zones (simulate traffic conditions)
     */
    private function getTrafficFactor($zone1Id, $zone2Id) {
        $currentHour = date('H');
        $isWeekend = date('N') >= 6;

        // Simulate traffic patterns
        $baseFactor = 1.0;

        // Rush hour traffic
        if (($currentHour >= 7 && $currentHour <= 9) || ($currentHour >= 17 && $currentHour <= 19)) {
            $baseFactor = $isWeekend ? 1.2 : 1.8;
        }
        // Evening traffic
        elseif ($currentHour >= 20 && $currentHour <= 22) {
            $baseFactor = 1.3;
        }
        // Night time - less traffic
        elseif ($currentHour >= 23 || $currentHour <= 6) {
            $baseFactor = 0.7;
        }

        // Add some randomness to simulate real-time conditions
        $randomFactor = mt_rand(80, 120) / 100; // 0.8 to 1.2
        
        return $baseFactor * $randomFactor;
    }

    /**
     * Check if a zone has available slots
     */
    private function hasAvailableSlots($zoneId) {
        $query = "SELECT COUNT(*) as count FROM parking_slots 
                  WHERE zone_id = :zone_id AND status = 'available'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':zone_id', $zoneId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    /**
     * Get multiple slot recommendations with different criteria
     */
    public function getSlotRecommendations($user_lat, $user_lng, $preferences = []) {
        $recommendations = [];
        
        // Nearest slot
        $nearest = $this->findNearestSlot($user_lat, $user_lng, $preferences);
        if ($nearest) {
            $recommendations['nearest'] = $nearest;
        }

        // Cheapest slot
        $cheapest = $this->findCheapestSlot($user_lat, $user_lng, $preferences);
        if ($cheapest) {
            $recommendations['cheapest'] = $cheapest;
        }

        // Best value (balance of distance and price)
        $bestValue = $this->findBestValueSlot($user_lat, $user_lng, $preferences);
        if ($bestValue) {
            $recommendations['best_value'] = $bestValue;
        }

        return $recommendations;
    }

    /**
     * Find the cheapest available slot
     */
    private function findCheapestSlot($user_lat, $user_lng, $preferences) {
        $slots = $this->getAvailableSlots($preferences);
        
        if (empty($slots)) {
            return null;
        }

        // Sort by hourly rate
        usort($slots, function($a, $b) {
            return $a['hourly_rate'] <=> $b['hourly_rate'];
        });

        return $slots[0];
    }

    /**
     * Find the best value slot (considering both distance and price)
     */
    private function findBestValueSlot($user_lat, $user_lng, $preferences) {
        $slots = $this->getAvailableSlots($preferences);
        
        if (empty($slots)) {
            return null;
        }

        // Calculate value score for each slot
        foreach ($slots as &$slot) {
            $distance = $this->calculateDistance($user_lat, $user_lng, $slot['x_coordinate'], $slot['y_coordinate']);
            
            // Normalize distance and price (lower is better)
            $distanceScore = $distance; // km
            $priceScore = $slot['hourly_rate']; // price per hour
            
            // Combined score (weighted average)
            $slot['value_score'] = ($distanceScore * 0.6) + ($priceScore * 0.4);
        }

        // Sort by value score
        usort($slots, function($a, $b) {
            return $a['value_score'] <=> $b['value_score'];
        });

        return $slots[0];
    }
}
?>