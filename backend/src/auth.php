<?php

function start_session_if_needed(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function current_user_id(): ?int {
    start_session_if_needed();
    return isset($_SESSION['uid']) ? intval($_SESSION['uid']) : null;
}

function require_auth(): int {
    $uid = current_user_id();
    if ($uid === null) {
        json_response(['error' => 'Unauthorized'], 401);
    }
    return $uid;
}

function set_user_session(int $userId): void {
    start_session_if_needed();
    $_SESSION['uid'] = $userId;
}

function clear_user_session(): void {
    start_session_if_needed();
    session_unset();
    session_destroy();
}

function is_admin(): bool {
    require_once __DIR__ . '/repositories/Users.php';
    $uid = current_user_id();
    if ($uid === null) return false;
    $repo = new UsersRepository();
    $u = $repo->findById($uid);
    return $u && ($u['role'] ?? 'user') === 'admin';
}

function require_admin(): void {
    if (!is_admin()) {
        json_response(['error' => 'Forbidden'], 403);
    }
}