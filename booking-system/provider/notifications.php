<?php
    $page_title = 'Notifications';
    require_once __DIR__ . '/header.php';

    global $conn;

    // Mark notification as read
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notification_id'])) {
        $notification_id = $_POST['notification_id'];
        $stmt            = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
        $stmt->execute([$notification_id, $user['id']]);
    }

    // Mark all as read
    $action = $_GET['action'] ?? '';

    if ($action === 'mark_all_read') {
        $stmt = $conn->prepare(
            "UPDATE notifications SET is_read = TRUE WHERE user_id = ?"
        );
        $stmt->execute([$user['id']]);
    }

    // Get notifications
    $stmt = $conn->prepare("
  SELECT * FROM notifications
  WHERE user_id = ?
  ORDER BY created_at DESC
  LIMIT 50
");
    $stmt->execute([$user['id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
  .notification-item {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    border-left: 4px solid var(--secondary);
    display: flex;
    justify-content: space-between;
    align-items: start;
  }

  .notification-item.unread {
    background: #fff5f0;
  }

  .notification-item.unread::before {
    content: '';
    display: inline-block;
    width: 8px;
    height: 8px;
    background: var(--secondary);
    border-radius: 50%;
    margin-right: 0.5rem;
  }

  .notification-content h3 {
    margin: 0 0 0.25rem 0;
    font-size: 1rem;
  }

  .notification-content p {
    margin: 0;
    color: #666;
    font-size: 0.95rem;
  }

  .notification-time {
    color: #999;
    font-size: 0.85rem;
    white-space: nowrap;
  }
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
  <h1>Notifications</h1>
  <a href="?action=mark_all_read" class="btn btn-primary">Mark All as Read</a>
</div>

<?php if ($notifications): ?>
  <?php foreach ($notifications as $notif): ?>
    <div class="notification-item<?php echo ! $notif['is_read'] ? 'unread' : ''; ?>">
      <div class="notification-content" style="flex: 1;">
        <h3><?php echo htmlspecialchars($notif['title']); ?></h3>
        <p><?php echo htmlspecialchars($notif['message']); ?></p>
        <div class="notification-time"><?php echo date('M d, Y g:i A', strtotime($notif['created_at'])); ?></div>
      </div>

      <?php if (! $notif['is_read']): ?>
        <form method="POST" style="margin-left: 1rem;">
          <input type="hidden" name="notification_id" value="<?php echo $notif['id']; ?>">
          <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">Mark Read</button>
        </form>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
<?php else: ?>
  <div class="card">
    <p>No notifications yet.</p>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
