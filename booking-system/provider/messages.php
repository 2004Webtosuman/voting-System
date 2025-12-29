<?php
$page_title = 'Messages';
require_once __DIR__ . '/header.php';

global $conn;

$selected_user_id = $_GET['user_id'] ?? null;

// Get list of customers the provider has interacted with
$stmt = $conn->prepare("
  SELECT DISTINCT u.id, u.name, u.email,
  (SELECT message FROM messages WHERE (sender_id = ? AND receiver_id = u.id) OR (sender_id = u.id AND receiver_id = ?) ORDER BY created_at DESC LIMIT 1) as last_message,
  (SELECT created_at FROM messages WHERE (sender_id = ? AND receiver_id = u.id) OR (sender_id = u.id AND receiver_id = ?) ORDER BY created_at DESC LIMIT 1) as last_message_time,
  (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND receiver_id = ? AND is_read = FALSE) as unread_count
  FROM users u
  JOIN bookings b ON u.id = b.customer_id
  WHERE b.provider_id = ?
  ORDER BY last_message_time DESC
");
$stmt->execute([$user['id'], $user['id'], $user['id'], $user['id'], $user['id'], $user['id']]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receiver_id'])) {
  $message_text = trim($_POST['message'] ?? '');
  $receiver_id = $_POST['receiver_id'];
  
  if (!empty($message_text)) {
    try {
      $stmt = $conn->prepare("
        INSERT INTO messages (sender_id, receiver_id, message)
        VALUES (?, ?, ?)
      ");
      $stmt->execute([$user['id'], $receiver_id, $message_text]);
      $success = 'Message sent!';
    } catch (PDOException $e) {
      $error = 'Error sending message: ' . $e->getMessage();
    }
  }
}

// Get messages for selected user
$messages = [];
if ($selected_user_id) {
  $stmt = $conn->prepare("
    SELECT * FROM messages 
    WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
    ORDER BY created_at ASC
  ");
  $stmt->execute([$user['id'], $selected_user_id, $selected_user_id, $user['id']]);
  $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  // Mark messages as read
  $stmt = $conn->prepare("UPDATE messages SET is_read = TRUE WHERE sender_id = ? AND receiver_id = ?");
  $stmt->execute([$selected_user_id, $user['id']]);
}
?>

<style>
  .messages-container {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 2rem;
    min-height: 600px;
  }
  
  .conversation-list {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  }
  
  .conversation-item {
    padding: 1rem;
    border-bottom: 1px solid var(--border);
    cursor: pointer;
    transition: all 0.3s ease;
  }
  
  .conversation-item:hover {
    background: var(--light);
  }
  
  .conversation-item.active {
    background: var(--secondary);
    color: white;
    border-bottom-color: var(--secondary);
  }
  
  .conversation-item.active a {
    color: white;
    text-decoration: none;
  }
  
  .conversation-name {
    font-weight: 600;
    margin-bottom: 0.25rem;
  }
  
  .conversation-preview {
    font-size: 0.9rem;
    color: #666;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  
  .conversation-item.active .conversation-preview {
    color: rgba(255, 255, 255, 0.7);
  }
  
  .chat-container {
    background: white;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  }
  
  .messages-list {
    flex: 1;
    padding: 1.5rem;
    overflow-y: auto;
  }
  
  .message {
    margin-bottom: 1rem;
    display: flex;
  }
  
  .message.sent {
    justify-content: flex-end;
  }
  
  .message-bubble {
    max-width: 70%;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    word-wrap: break-word;
  }
  
  .message.sent .message-bubble {
    background: var(--secondary);
    color: white;
  }
  
  .message.received .message-bubble {
    background: var(--light);
    color: var(--dark);
  }
  
  .message-time {
    font-size: 0.75rem;
    color: #999;
    margin-top: 0.25rem;
  }
  
  .message-form {
    padding: 1.5rem;
    border-top: 1px solid var(--border);
  }
  
  .message-input-group {
    display: flex;
    gap: 0.5rem;
  }
  
  .message-input-group input {
    flex: 1;
  }
  
  .message-input-group button {
    flex-shrink: 0;
  }
  
  .no-conversation {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #999;
    text-align: center;
  }
  
  @media (max-width: 768px) {
    .messages-container {
      grid-template-columns: 1fr;
    }
    
    .conversation-list {
      display: none;
    }
  }
</style>

<h1>Messages</h1>

<div class="messages-container">
  <div class="conversation-list">
    <?php if ($conversations): ?>
      <?php foreach ($conversations as $conv): ?>
        <a href="?user_id=<?php echo $conv['id']; ?>" style="text-decoration: none; color: inherit;">
          <div class="conversation-item <?php echo $selected_user_id === $conv['id'] ? 'active' : ''; ?>">
            <div class="conversation-name"><?php echo htmlspecialchars($conv['name']); ?></div>
            <div class="conversation-preview"><?php echo htmlspecialchars(substr($conv['last_message'], 0, 50)); ?></div>
            <?php if ($conv['unread_count'] > 0): ?>
              <span class="badge badge-warning" style="margin-top: 0.25rem;"><?php echo $conv['unread_count']; ?> unread</span>
            <?php endif; ?>
          </div>
        </a>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="conversation-item">
        <p style="margin: 0; color: #999;">No conversations yet</p>
      </div>
    <?php endif; ?>
  </div>
  
  <div class="chat-container">
    <?php if ($selected_user_id): ?>
      <?php
      // Get receiver info
      $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
      $stmt->execute([$selected_user_id]);
      $receiver = $stmt->fetch(PDO::FETCH_ASSOC);
      ?>
      
      <div style="padding: 1.5rem; border-bottom: 1px solid var(--border);">
        <h3 style="margin: 0;"><?php echo htmlspecialchars($receiver['name']); ?></h3>
      </div>
      
      <div class="messages-list">
        <?php foreach ($messages as $msg): ?>
          <div class="message <?php echo $msg['sender_id'] === $user['id'] ? 'sent' : 'received'; ?>">
            <div>
              <div class="message-bubble"><?php echo htmlspecialchars($msg['message']); ?></div>
              <div class="message-time"><?php echo date('g:i A', strtotime($msg['created_at'])); ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      
      <form method="POST" class="message-form">
        <input type="hidden" name="receiver_id" value="<?php echo $selected_user_id; ?>">
        <div class="message-input-group">
          <input type="text" name="message" placeholder="Type your message..." required>
          <button type="submit" class="btn btn-primary">Send</button>
        </div>
      </form>
    <?php else: ?>
      <div class="no-conversation">
        <div>
          <p>Select a conversation to start messaging</p>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
