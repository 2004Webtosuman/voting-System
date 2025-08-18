<?php
// PHP built-in server router to serve static files and route /api/* to API

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . $uri;

// Serve static files if they exist
if ($uri !== '/' && file_exists($file) && is_file($file)) {
    return false;
}

// Route API requests
if (str_starts_with($uri, '/api')) {
    require __DIR__ . '/../api/index.php';
    return true;
}

// Fallback to index.html (SPA)
readfile(__DIR__ . '/index.html');
return true;

