<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../backend/src/services/GraphService.php';
require_once __DIR__ . '/../../backend/src/repositories/Slots.php';

require_method('GET');
$startSlotId = isset($_GET['start_slot_id']) ? intval($_GET['start_slot_id']) : 0;
$start = $_GET['starts_at'] ?? '';
$end = $_GET['ends_at'] ?? '';
if ($startSlotId <= 0 || !$start || !$end) {
    json_response(['error' => 'Missing parameters'], 422);
}

$slotsRepo = new SlotsRepository();
$available = $slotsRepo->findAvailableBetween($start, $end);
$availableSet = [];
foreach ($available as $s) { $availableSet[intval($s['id'])] = true; }

$g = new GraphService();
$res = $g->dijkstra($startSlotId, function (int $slotId) use ($availableSet) {
    return isset($availableSet[$slotId]);
});

json_response(['result' => $res]);