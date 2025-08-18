<?php
require_once __DIR__ . '/../db.php';

class QRTokensRepository {
    public function create(int $bookingId, string $token, string $issuedAt, string $expiresAt): int {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO qr_tokens (booking_id, token, issued_at, expires_at) VALUES (?, ?, ?, ?)');
        $stmt->execute([$bookingId, $token, $issuedAt, $expiresAt]);
        return intval($pdo->lastInsertId());
    }

    public function findValid(string $token): ?array {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM qr_tokens WHERE token = ? AND used = 0 AND expires_at > NOW()');
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function markUsed(int $id): void {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('UPDATE qr_tokens SET used = 1 WHERE id = ?');
        $stmt->execute([$id]);
    }
}