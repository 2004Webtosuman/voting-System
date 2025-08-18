<?php
require_once __DIR__ . '/../repositories/QRTokens.php';
require_once __DIR__ . '/../repositories/Bookings.php';
require_once __DIR__ . '/../config.php';

class QRService {
    private QRTokensRepository $qr;
    private BookingsRepository $bookings;

    public function __construct() {
        $this->qr = new QRTokensRepository();
        $this->bookings = new BookingsRepository();
    }

    public function verify(string $token): array {
        $record = $this->qr->findValid($token);
        if (!$record) {
            throw new RuntimeException('Invalid or expired token');
        }
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) throw new RuntimeException('Malformed token');
        [$payloadB64, $sig] = $parts;
        $payload = base64_decode(strtr($payloadB64, '-_', '+/'));
        $expected = hash_hmac('sha256', $payload, AppConfig::get('APP_SECRET', 'dev_secret'));
        if (!hash_equals($expected, $sig)) {
            throw new RuntimeException('Signature mismatch');
        }
        $data = json_decode($payload, true);
        return ['qr' => $record, 'payload' => $data];
    }

    public function markUsed(int $id): void {
        $this->qr->markUsed($id);
    }
}