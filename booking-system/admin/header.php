<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
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
  <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Admin Dashboard'; ?></title>
  <link rel="stylesheet" href="<?php echo SITE_URL; ?>public/style.css">
  <style>
    .sidebar {
      background: #1a1a1a;
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
      background: rgba(255, 255, 255, 0.1);
      border-left-color: var(--primary);
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
      <h2 style="padding: 0 1.5rem; margin-bottom: 2rem; color: white;">Admin Panel</h2>
      <nav>
        <a href="<?php echo SITE_URL; ?>admin/dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
        <a href="<?php echo SITE_URL; ?>admin/users.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>">Users</a>
        <a href="<?php echo SITE_URL; ?>admin/providers.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'providers.php' ? 'active' : ''; ?>">Providers</a>
        <a href="<?php echo SITE_URL; ?>admin/bookings.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'bookings.php' ? 'active' : ''; ?>">Bookings</a>
        <a href="<?php echo SITE_URL; ?>admin/kyc.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'kyc.php' ? 'active' : ''; ?>">KYC Verification</a>
        <a href="<?php echo SITE_URL; ?>logout.php" style="color: var(--danger);">Logout</a>
      </nav>
    </aside>
    
    <div class="content">
      <div class="header">
        <h1><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Dashboard'; ?></h1>
        <div class="user-info">
          <span><?php echo htmlspecialchars($user['name']); ?> (Admin)</span>
          <a href="<?php echo SITE_URL; ?>logout.php">Logout</a>
        </div>
      </div>
