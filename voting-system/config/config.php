<?php
session_start();

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'sddproject');
define('DB_USER', 'root');
define('DB_PASS', '');

// Set up PDO Connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Connection failed: " . $e->getMessage());
}

// Ensure CSRF token is generated
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Validate CSRF token from POST request
 */
function validate_csrf_token() {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        render_error_page("Invalid CSRF token. Please try submitting the form again.");
        exit();
    }
}

/**
 * Render a styled error page
 */
function render_error_page($message, $back_url = "javascript:history.back()") {
    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Software Development and Design Project/theme.css">
</head>
<body class="error-body">
    <div class="error-container">
        <i class="fas fa-exclamation-triangle error-icon"></i>
        <h1>An Error Occurred</h1>
        <p>' . htmlspecialchars($message) . '</p>
        <a href="' . htmlspecialchars($back_url) . '" class="btn btn-primary">Go Back</a>
    </div>
</body>
</html>';
    exit();
}

// Encryption Settings
define('ENCRYPTION_KEY', '8d7f6a5b4c3d2e1f0a9b8c7d6e5f4a3b');

function encrypt_data($data) {
    $ivLength = openssl_cipher_iv_length('aes-256-cbc');
    $iv = openssl_random_pseudo_bytes($ivLength);
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', ENCRYPTION_KEY, OPENSSL_RAW_DATA, $iv);
    return $iv . $encrypted;
}

function decrypt_data($data) {
    $ivLength = openssl_cipher_iv_length('aes-256-cbc');
    $iv = substr($data, 0, $ivLength);
    $encrypted = substr($data, $ivLength);
    return openssl_decrypt($encrypted, 'aes-256-cbc', ENCRYPTION_KEY, OPENSSL_RAW_DATA, $iv);
}
?>
