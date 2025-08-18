<?php
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../../backend/src/services/AuthService.php';
require_once __DIR__ . '/../../backend/src/repositories/Users.php';

$auth = new AuthService();
$method = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'register':
            require_method('POST');
            $in = read_json_input();
            require_fields($in, ['name', 'email', 'password']);
            $user = $auth->register($in['name'], $in['email'], $in['password']);
            json_response(['user' => $user]);
        case 'login':
            require_method('POST');
            $in = read_json_input();
            require_fields($in, ['email', 'password']);
            $user = $auth->login($in['email'], $in['password']);
            json_response(['user' => $user]);
        case 'me':
            require_method('GET');
            $uid = current_user_id();
            if (!$uid) json_response(['user' => null]);
            $repo = new UsersRepository();
            $u = $repo->findById($uid);
            json_response(['user' => ['id' => intval($u['id']), 'name' => $u['name'], 'email' => $u['email']]]);
        case 'logout':
            require_method('POST');
            clear_user_session();
            json_response(['ok' => true]);
        default:
            json_response(['error' => 'Unknown action'], 400);
    }
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 400);
}