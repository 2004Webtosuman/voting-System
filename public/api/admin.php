<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../backend/src/auth.php';
require_once __DIR__ . '/../../backend/src/utils.php';
require_once __DIR__ . '/../../backend/src/repositories/Zones.php';
require_once __DIR__ . '/../../backend/src/repositories/Slots.php';
require_once __DIR__ . '/../../backend/src/repositories/Edges.php';

require_admin();
$entity = $_GET['entity'] ?? '';
$action = $_GET['action'] ?? '';

$zones = new ZonesRepository();
$slots = new SlotsRepository();
$edges = new EdgesRepository();

try {
	switch ($entity) {
		case 'zones':
			switch ($action) {
				case 'create':
					require_method('POST');
					$in = read_json_input();
					require_fields($in, ['name','x','y']);
					$id = $zones->create($in['name'], floatval($in['x']), floatval($in['y']));
					json_response(['id' => $id]);
				case 'update':
					require_method('POST');
					$in = read_json_input();
					require_fields($in, ['id','name','x','y']);
					$ok = $zones->update(intval($in['id']), $in['name'], floatval($in['x']), floatval($in['y']));
					json_response(['ok' => $ok]);
				case 'delete':
					require_method('POST');
					$in = read_json_input();
					require_fields($in, ['id']);
					$ok = $zones->delete(intval($in['id']));
					json_response(['ok' => $ok]);
				default:
					json_response(['error' => 'Unknown zones action'], 400);
			}
		case 'slots':
			switch ($action) {
				case 'create':
					require_method('POST');
					$in = read_json_input();
					require_fields($in, ['zone_id','name','x','y','status']);
					$id = $slots->create(intval($in['zone_id']), $in['name'], floatval($in['x']), floatval($in['y']), $in['status']);
					json_response(['id' => $id]);
				case 'update':
					require_method('POST');
					$in = read_json_input();
					require_fields($in, ['id','zone_id','name','x','y','status']);
					$ok = $slots->update(intval($in['id']), intval($in['zone_id']), $in['name'], floatval($in['x']), floatval($in['y']), $in['status']);
					json_response(['ok' => $ok]);
				case 'delete':
					require_method('POST');
					$in = read_json_input();
					require_fields($in, ['id']);
					$ok = $slots->delete(intval($in['id']));
					json_response(['ok' => $ok]);
				default:
					json_response(['error' => 'Unknown slots action'], 400);
			}
		case 'edges':
			switch ($action) {
				case 'create':
					require_method('POST');
					$in = read_json_input();
					require_fields($in, ['from_slot_id','to_slot_id','weight']);
					$id = $edges->create(intval($in['from_slot_id']), intval($in['to_slot_id']), floatval($in['weight']));
					json_response(['id' => $id]);
				case 'delete':
					require_method('POST');
					$in = read_json_input();
					require_fields($in, ['id']);
					$ok = $edges->delete(intval($in['id']));
					json_response(['ok' => $ok]);
				default:
					json_response(['error' => 'Unknown edges action'], 400);
			}
		default:
			json_response(['error' => 'Unknown entity'], 400);
	}
} catch (Throwable $e) {
	json_response(['error' => $e->getMessage()], 400);
}