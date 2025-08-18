<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../backend/src/repositories/Zones.php';
require_once __DIR__ . '/../../backend/src/repositories/Slots.php';

require_method('GET');
$zonesRepo = new ZonesRepository();
$slotsRepo = new SlotsRepository();
$zones = $zonesRepo->all();
$slots = $slotsRepo->all();
json_response(['zones' => $zones, 'slots' => $slots]);