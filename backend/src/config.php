<?php

class AppConfig {
    public static function get(string $key, ?string $default = null): ?string {
        self::loadEnv();
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }

    private static bool $envLoaded = false;

    private static function loadEnv(): void {
        if (self::$envLoaded) return;
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (str_starts_with(trim($line), '#')) continue;
                $parts = explode('=', $line, 2);
                if (count($parts) === 2) {
                    $k = trim($parts[0]);
                    $v = trim($parts[1]);
                    $_ENV[$k] = $v;
                    putenv("$k=$v");
                }
            }
        }
        self::$envLoaded = true;
    }
}