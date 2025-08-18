<?php

function send_json($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: ' . CORS_ALLOW_ORIGIN);
    header('Access-Control-Allow-Methods: ' . CORS_ALLOW_METHODS);
    header('Access-Control-Allow-Headers: ' . CORS_ALLOW_HEADERS);
    echo json_encode($data);
}

function parse_json_body(): array {
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);
    return is_array($data) ? $data : [];
}

function require_fields(array $data, array $fields): array {
    $missing = [];
    foreach ($fields as $f) {
        if (!array_key_exists($f, $data)) {
            $missing[] = $f;
        }
    }
    return $missing;
}

function hash_password(string $password): string {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verify_password(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

function generate_token(): string {
    return bin2hex(random_bytes(24));
}

function with_cors_preflight(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header('Access-Control-Allow-Origin: ' . CORS_ALLOW_ORIGIN);
        header('Access-Control-Allow-Methods: ' . CORS_ALLOW_METHODS);
        header('Access-Control-Allow-Headers: ' . CORS_ALLOW_HEADERS);
        http_response_code(204);
        exit;
    }
}

