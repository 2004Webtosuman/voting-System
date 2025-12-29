<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'customer') {
  header('Location: ' . SITE_URL . 'login.php');
  exit();
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Customer Dashboard'; ?></title>
  <link rel="stylesheet" href="<?php echo SITE_URL; ?>public/style.css">
  <style>
    .sidebar {
      background: var(--primary);
      color: white;
      padding: 2rem 0;
      min-height: 100vh;
    }
    
    .sidebar a {
      display: block;
      color: white;
      text-decoration: none;
      padding: 1rem 1.5rem;
      border-left: 4px solid transparent;
      transition: all 0.3s ease;
    }
    
    .sidebar a:hover,
    .sidebar a.active {
      background: rgba(0, 0, 0, 0.1);
      border-left-color: white;
    }
    
    .main-layout {
      display: grid;
      grid-template-columns: 250px 1fr;
      min-height: 100vh;
    }
    
    .content {
      padding: 2rem;
    }
    
    .header {
      background: white;
      padding: 1.5rem 2rem;
      border-bottom: 2px solid var(--border);
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
    }
    
    .header h1 {
      color: var(--dark);
      margin: 0;
    }
    
    .user-info {
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    
    .user-info a {
      color: var(--primary);
      text-decoration: none;
      font-weight: 600;
    }
  </style>
</head>
<body>
  <div class="main-layout">
    <aside class="sidebar">
      <h2 style="padding: 0 1.5rem; margin-bottom: 2rem; color: white;">Service Booking</h2>
      <nav>
        <a href="<?php echo SITE_URL; ?>customer/dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
        <a href="<?php echo SITE_URL; ?>customer/find-providers.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'find-providers.php' ? 'active' : ''; ?>">Find Providers</a>
        <a href="<?php echo SITE_URL; ?>customer/bookings.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'bookings.php' ? 'active' : ''; ?>">My Bookings</a>
        <a href="<?php echo SITE_URL; ?>customer/notifications.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'notifications.php' ? 'active' : ''; ?>">Notifications</a>
        <a href="<?php echo SITE_URL; ?>customer/messages.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'messages.php' ? 'active' : ''; ?>">Messages</a>
        <a href="<?php echo SITE_URL; ?>customer/profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">Profile</a>
        <a href="<?php echo SITE_URL; ?>logout.php" style="color: var(--danger);">Logout</a>
      </nav>
    </aside>
    
    <div class="content">
      <div class="header">
        <h1><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Dashboard'; ?></h1>
        <div class="user-info">
          <span><?php echo htmlspecialchars($user['name']); ?></span>
          <a href="<?php echo SITE_URL; ?>logout.php">Logout</a>
        </div>
      </div>
