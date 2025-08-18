<?php

function json_response(mixed $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function read_json_input(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function require_fields(array $data, array $fields): void {
    foreach ($fields as $f) {
        if (!array_key_exists($f, $data)) {
            json_response(['error' => "Missing field: $f"], 422);
        }
    }
}

function overlaps(string $aStart, string $aEnd, string $bStart, string $bEnd): bool {
    return max(strtotime($aStart), strtotime($bStart)) < min(strtotime($aEnd), strtotime($bEnd));
}

function require_method(string $method): void {
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        json_response(['error' => 'Method Not Allowed'], 405);
    }
}

function allow_cors(): void {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}