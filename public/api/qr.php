<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../backend/src/services/QRService.php';

$action = $_GET['action'] ?? 'verify';
$svc = new QRService();
try {
    switch ($action) {
        case 'verify':
            require_method('POST');
            $in = read_json_input();
            require_fields($in, ['token']);
            $res = $svc->verify($in['token']);
            json_response(['ok' => true, 'data' => $res]);
        case 'use':
            require_method('POST');
            $in = read_json_input();
            require_fields($in, ['id']);
            $svc->markUsed(intval($in['id']));
            json_response(['ok' => true]);
        default:
            json_response(['error' => 'Unknown action'], 400);
    }
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 400);
}