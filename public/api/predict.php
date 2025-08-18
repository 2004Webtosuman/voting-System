<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../backend/src/services/PredictionService.php';
require_once __DIR__ . '/../../backend/src/utils.php';

require_method('GET');
$svc = new PredictionService();
json_response(['predictions' => $svc->hourlyOccupancyNext24h()]);