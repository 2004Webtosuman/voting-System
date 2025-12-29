<?php
session_start();

define('SITE_URL', 'http://localhost/booking-system/');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);
define('MAX_FILE_SIZE', 5242880); // 5MB

// Auto-create upload directory if it doesn't exist
if (! is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

/* ===========================
   DATABASE CONNECTION (PDO)
   =========================== */

try {
    $conn = new PDO(
        "mysql:host=localhost;dbname=booking_system;charset=utf8mb4",
        "root",
        "" // XAMPP default password is empty
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
