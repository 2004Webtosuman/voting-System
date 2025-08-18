<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../config/config.php';

class AuthService {
    public static function register(string $name, string $email, string $password, string $role = 'user'): int {
        $pdo = DatabaseConnection::get();
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
        return intval($pdo->lastInsertId());
    }

    public static function login(string $email, string $password): ?array {
        $pdo = DatabaseConnection::get();
        $stmt = $pdo->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user) return null;
        if (!password_verify($password, $user['password_hash'])) return null;

        $token = bin2hex(random_bytes(24));
        $expiresAt = (new DateTimeImmutable('now', new DateTimeZone('UTC'))) 
            ->modify('+' . TOKEN_TTL_HOURS . ' hours')
            ->format('Y-m-d H:i:s');
        $pdo->prepare('INSERT INTO auth_tokens (user_id, token, expires_at, created_at) VALUES (?, ?, ?, NOW())')
            ->execute([$user['id'], $token, $expiresAt]);

        return [
            'token' => $token,
            'user' => [
                'id' => intval($user['id']),
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
            ]
        ];
    }

    public static function authenticate(): ?array {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }
        $token = substr($authHeader, 7);
        $pdo = DatabaseConnection::get();
        $stmt = $pdo->prepare('SELECT t.user_id, u.name, u.email, u.role, t.expires_at FROM auth_tokens t JOIN users u ON t.user_id = u.id WHERE t.token = ?');
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        if (!$row) return null;
        if (strtotime($row['expires_at']) < time()) return null;
        return [
            'id' => intval($row['user_id']),
            'name' => $row['name'],
            'email' => $row['email'],
            'role' => $row['role']
        ];
    }
}

