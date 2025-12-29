<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

// Simple router
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_uri = str_replace('/booking-system/', '', $request_uri);

// Route handling
if ($request_uri === '' || $request_uri === '/') {
  if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user['role'] === 'customer') {
      header('Location: ' . SITE_URL . 'customer/dashboard.php');
    } elseif ($user['role'] === 'provider') {
      header('Location: ' . SITE_URL . 'provider/dashboard.php');
    } elseif ($user['role'] === 'admin') {
      header('Location: ' . SITE_URL . 'admin/dashboard.php');
    }
  } else {
    header('Location: ' . SITE_URL . 'login.php');
  }
}
?>
