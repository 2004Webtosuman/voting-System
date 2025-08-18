<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/utils.php';
require_once __DIR__ . '/../models/auth.php';
require_once __DIR__ . '/../models/parking.php';
require_once __DIR__ . '/../models/predict.php';
require_once __DIR__ . '/../models/admin.php';

with_cors_preflight();

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

function not_found() { send_json(['error' => 'Not found'], 404); }
function bad_request($msg) { send_json(['error' => $msg], 400); }
function unauthorized() { send_json(['error' => 'Unauthorized'], 401); }

if ($path === '/api/ping') {
    send_json(['ok' => true, 'app' => APP_NAME]);
    exit;
}

if ($path === '/api/register' && $method === 'POST') {
    $data = parse_json_body();
    $missing = require_fields($data, ['name', 'email', 'password']);
    if ($missing) bad_request('Missing: ' . implode(',', $missing));
    try {
        $pdo = DatabaseConnection::get();
        $countAdmins = (int)($pdo->query("SELECT COUNT(*) AS c FROM users WHERE role = 'admin'")->fetch()['c'] ?? 0);
        $role = $countAdmins === 0 ? 'admin' : 'user';
        $uid = AuthService::register($data['name'], $data['email'], $data['password'], $role);
        send_json(['user_id' => $uid, 'role' => $role]);
    } catch (Throwable $e) {
        bad_request('Registration failed');
    }
    exit;
}

if ($path === '/api/login' && $method === 'POST') {
    $data = parse_json_body();
    $missing = require_fields($data, ['email', 'password']);
    if ($missing) bad_request('Missing: ' . implode(',', $missing));
    $res = AuthService::login($data['email'], $data['password']);
    if (!$res) unauthorized(); else send_json($res);
    exit;
}

// Authenticated endpoints
$user = AuthService::authenticate();
if (!$user) {
    if (str_starts_with($path, '/api/')) unauthorized();
}

if ($path === '/api/whoami' && $method === 'GET') {
    send_json(['user' => $user]);
    exit;
}

if ($path === '/api/zones' && $method === 'GET') {
    send_json(ParkingModel::listZones());
    exit;
}

if (preg_match('#^/api/zones/(\d+)/slots$#', $path, $m) && $method === 'GET') {
    send_json(ParkingModel::listSlotsByZone(intval($m[1])));
    exit;
}

if (preg_match('#^/api/zones/(\d+)/entrances$#', $path, $m) && $method === 'GET') {
    send_json(ParkingModel::listEntrances(intval($m[1])));
    exit;
}

if ($path === '/api/bookings' && $method === 'POST') {
    $data = parse_json_body();
    $missing = require_fields($data, ['slot_id', 'start_time', 'end_time']);
    if ($missing) bad_request('Missing: ' . implode(',', $missing));
    try {
        $bookingId = ParkingModel::createBooking(intval($user['id']), intval($data['slot_id']), $data['start_time'], $data['end_time']);
        $qr = base64_encode('BOOKING:' . $bookingId);
        send_json(['booking_id' => $bookingId, 'qr_code' => $qr]);
    } catch (Throwable $e) {
        bad_request($e->getMessage());
    }
    exit;
}

if (preg_match('#^/api/bookings/(\d+)/release$#', $path, $m) && $method === 'POST') {
    $data = parse_json_body();
    $qr = $data['exit_qr'] ?? null;
    try {
        ParkingModel::releaseBooking(intval($m[1]), $qr);
        send_json(['released' => true]);
    } catch (Throwable $e) {
        bad_request($e->getMessage());
    }
    exit;
}

if (preg_match('#^/api/zones/(\d+)/nearest-slot$#', $path, $m) && $method === 'GET') {
    $zoneId = intval($m[1]);
    $entranceId = isset($_GET['entrance_id']) ? intval($_GET['entrance_id']) : 0;
    if ($entranceId <= 0) bad_request('entrance_id is required');
    $slot = ParkingModel::nearestAvailableSlotByDijkstra($zoneId, $entranceId);
    if (!$slot) send_json(['message' => 'No available slots'], 200); else send_json($slot);
    exit;
}

if (preg_match('#^/api/zones/(\d+)/predict$#', $path, $m) && $method === 'GET') {
    $zoneId = intval($m[1]);
    $hours = isset($_GET['hours']) ? intval($_GET['hours']) : 24;
    $pred = Predictor::predictZoneHourly($zoneId, $hours);
    send_json($pred);
    exit;
}

// Simple bookings list for current user
if ($path === '/api/bookings' && $method === 'GET') {
    $pdo = DatabaseConnection::get();
    $stmt = $pdo->prepare('SELECT b.id, b.slot_id, s.label, b.start_time, b.end_time, b.status FROM bookings b JOIN slots s ON s.id = b.slot_id WHERE b.user_id = ? ORDER BY b.id DESC LIMIT 50');
    $stmt->execute([$user['id']]);
    send_json($stmt->fetchAll());
    exit;
}

// Admin CRUD
if (str_starts_with($path, '/api/admin/')) {
    try { AdminModel::requireAdmin($user); } catch (Throwable $e) { send_json(['error' => 'Forbidden'], 403); exit; }

    if ($path === '/api/admin/zones' && $method === 'POST') {
        $data = parse_json_body();
        $missing = require_fields($data, ['name']); if ($missing) bad_request('Missing: name');
        $id = AdminModel::createZone($data['name'], $data['description'] ?? null);
        send_json(['id' => $id]); exit;
    }
    if (preg_match('#^/api/admin/zones/(\d+)$#', $path, $m) && $method === 'PUT') {
        $data = parse_json_body();
        AdminModel::updateZone(intval($m[1]), $data['name'] ?? '', $data['description'] ?? null);
        send_json(['ok' => true]); exit;
    }
    if (preg_match('#^/api/admin/zones/(\d+)$#', $path, $m) && $method === 'DELETE') {
        AdminModel::deleteZone(intval($m[1])); send_json(['ok' => true]); exit;
    }

    if ($path === '/api/admin/slots' && $method === 'POST') {
        $data = parse_json_body();
        $missing = require_fields($data, ['zone_id','label']); if ($missing) bad_request('Missing fields');
        $id = AdminModel::createSlot(intval($data['zone_id']), $data['label'], $data['x'] ?? null, $data['y'] ?? null);
        send_json(['id' => $id]); exit;
    }
    if (preg_match('#^/api/admin/slots/(\d+)$#', $path, $m) && $method === 'PUT') {
        $data = parse_json_body();
        AdminModel::updateSlot(intval($m[1]), $data['label'] ?? '', intval($data['is_available'] ?? 1), $data['x'] ?? null, $data['y'] ?? null);
        send_json(['ok' => true]); exit;
    }
    if (preg_match('#^/api/admin/slots/(\d+)$#', $path, $m) && $method === 'DELETE') {
        AdminModel::deleteSlot(intval($m[1])); send_json(['ok' => true]); exit;
    }

    if ($path === '/api/admin/entrances' && $method === 'POST') {
        $data = parse_json_body();
        $missing = require_fields($data, ['zone_id','name']); if ($missing) bad_request('Missing fields');
        $id = AdminModel::createEntrance(intval($data['zone_id']), $data['name']);
        send_json(['id' => $id]); exit;
    }
    if (preg_match('#^/api/admin/entrances/(\d+)$#', $path, $m) && $method === 'PUT') {
        $data = parse_json_body(); AdminModel::updateEntrance(intval($m[1]), $data['name'] ?? ''); send_json(['ok' => true]); exit;
    }
    if (preg_match('#^/api/admin/entrances/(\d+)$#', $path, $m) && $method === 'DELETE') {
        AdminModel::deleteEntrance(intval($m[1])); send_json(['ok' => true]); exit;
    }

    if ($path === '/api/admin/edges' && $method === 'POST') {
        $data = parse_json_body();
        $missing = require_fields($data, ['zone_id','from_type','from_id','to_type','to_id','weight']); if ($missing) bad_request('Missing fields');
        $id = AdminModel::createEdge(intval($data['zone_id']), $data['from_type'], intval($data['from_id']), $data['to_type'], intval($data['to_id']), floatval($data['weight']));
        send_json(['id' => $id]); exit;
    }
    if (preg_match('#^/api/admin/edges/(\d+)$#', $path, $m) && $method === 'DELETE') {
        AdminModel::deleteEdge(intval($m[1])); send_json(['ok' => true]); exit;
    }
}

not_found();

