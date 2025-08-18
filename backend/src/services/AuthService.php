<?php
require_once __DIR__ . '/../repositories/Users.php';
require_once __DIR__ . '/../auth.php';

class AuthService {
    private UsersRepository $users;

    public function __construct() {
        $this->users = new UsersRepository();
    }

    public function register(string $name, string $email, string $password): array {
        $existing = $this->users->findByEmail($email);
        if ($existing) {
            throw new RuntimeException('Email already registered');
        }
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $userId = $this->users->create($name, $email, $hash);
        set_user_session($userId);
        return ['id' => $userId, 'name' => $name, 'email' => $email];
    }

    public function login(string $email, string $password): array {
        $user = $this->users->findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            throw new RuntimeException('Invalid credentials');
        }
        set_user_session(intval($user['id']));
        return ['id' => intval($user['id']), 'name' => $user['name'], 'email' => $user['email']];
    }
}