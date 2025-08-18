<?php
require_once __DIR__ . '/../repositories/Bookings.php';
require_once __DIR__ . '/../repositories/QRTokens.php';
require_once __DIR__ . '/../repositories/Slots.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../config.php';

class BookingService {
    private BookingsRepository $bookings;
    private QRTokensRepository $qrTokens;
    private SlotsRepository $slots;

    public function __construct() {
        $this->bookings = new BookingsRepository();
        $this->qrTokens = new QRTokensRepository();
        $this->slots = new SlotsRepository();
    }

    public function createBooking(int $userId, int $slotId, string $start, string $end): array {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            if ($this->bookings->conflicts($slotId, $start, $end)) {
                throw new RuntimeException('Slot is not available in the requested time window');
            }
            $bookingId = $this->bookings->create($userId, $slotId, $start, $end);
            $token = $this->issueQrToken($bookingId, $userId, $slotId, $start, $end);
            $pdo->commit();
            return ['booking_id' => $bookingId, 'qr_token' => $token];
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private function issueQrToken(int $bookingId, int $userId, int $slotId, string $start, string $end): string {
        $secret = AppConfig::get('APP_SECRET', 'dev_secret');
        $payload = json_encode([
            'bid' => $bookingId,
            'uid' => $userId,
            'sid' => $slotId,
            'st' => $start,
            'et' => $end,
            'iat' => time(),
        ]);
        $sig = hash_hmac('sha256', $payload, $secret);
        $token = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=') . '.' . $sig;
        $issuedAt = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
        $expiresAt = (new DateTimeImmutable('now +2 hours'))->format('Y-m-d H:i:s');
        $this->qrTokens->create($bookingId, $token, $issuedAt, $expiresAt);
        return $token;
    }
}