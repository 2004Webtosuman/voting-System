<?php
require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $pdo = null;

    public static function connection(): PDO {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }
        $host = AppConfig::get('DB_HOST', '127.0.0.1');
        $db   = AppConfig::get('DB_NAME', 'smart_parking');
        $user = AppConfig::get('DB_USER', 'root');
        $pass = AppConfig::get('DB_PASS', '');
        $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        self::$pdo = new PDO($dsn, $user, $pass, $options);
        return self::$pdo;
    }
}