<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../backend/src/services/BookingService.php';
require_once __DIR__ . '/../../backend/src/repositories/Bookings.php';

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            require_method('GET');
            $uid = require_auth();
            $repo = new BookingsRepository();
            $items = $repo->userBookings($uid);
            json_response(['bookings' => $items]);
        case 'create':
            require_method('POST');
            $uid = require_auth();
            $in = read_json_input();
            require_fields($in, ['slot_id', 'starts_at', 'ends_at']);
            $svc = new BookingService();
            $result = $svc->createBooking($uid, intval($in['slot_id']), $in['starts_at'], $in['ends_at']);
            json_response(['booking' => $result]);
        case 'cancel':
            require_method('POST');
            $uid = require_auth();
            $in = read_json_input();
            require_fields($in, ['booking_id']);
            $repo = new BookingsRepository();
            $ok = $repo->cancel(intval($in['booking_id']), $uid);
            json_response(['ok' => $ok]);
        default:
            json_response(['error' => 'Unknown action'], 400);
    }
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 400);
}