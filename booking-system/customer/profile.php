<?php
    $page_title = 'Profile & Services';
    require_once __DIR__ . '/header.php';

    global $conn;

    $success = '';
    $errors  = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $service_type     = $_POST['service_type'] ?? 'plumber';
        $bio              = trim($_POST['bio'] ?? '');
        $hourly_rate      = $_POST['hourly_rate'] ?? 0;
        $years_experience = $_POST['years_experience'] ?? 0;

        try {
            $stmt = $conn->prepare("
      UPDATE providers
      SET service_type = ?, bio = ?, hourly_rate = ?, years_experience = ?
      WHERE user_id = ?
    ");

            if ($stmt->execute([$service_type, $bio, $hourly_rate, $years_experience, $user['id']])) {
                $success = 'Profile updated successfully!';
                // Refresh provider data
                $provider = getProviderInfo($user['id']);
            } else {
                $errors[] = 'Failed to update profile';
            }
        } catch (PDOException $e) {
            $errors[] = 'Error: ' . $e->getMessage();
        }
    }
?>

<div class="card">
  <h1>Your Profile</h1>

  <?php if ($errors): ?>
    <?php foreach ($errors as $error): ?>
      <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <div class="form-group">
      <label>Full Name</label>
      <input type="text" value="<?php echo htmlspecialchars($user['name']); ?>" readonly>
    </div>

    <div class="form-group">
      <label>Email</label>
      <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
    </div>

    <div class="form-group">
      <label>Phone</label>
      <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
    </div>




    <button type="submit" class="btn btn-primary">Update Profile</button>
  </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
