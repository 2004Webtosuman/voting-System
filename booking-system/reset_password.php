<?php
    require_once __DIR__ . '/config/config.php';

    $errors  = [];
    $success = '';
    $email   = $_GET['email'] ?? '';

    if (empty($email)) {
        die("Invalid request");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm'] ?? '';

        if (empty($password) || empty($confirm)) {
            $errors[] = "All fields required";
        } elseif ($password !== $confirm) {
            $errors[] = "Passwords do not match";
        } else {
            // 🔥 replace old password
            $hash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $conn->prepare(
                "UPDATE users SET password = ? WHERE email = ?"
            );
            $stmt->execute([$hash, $email]);

            $success = "Password reset successful. <a href='login.php'>Login now</a>";
        }
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password</title>

<!-- Google Font -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">

<style>
:root {
  --primary: #007aff;
  --glass: rgba(255, 255, 255, 0.25);
  --radius: 20px;
  --shadow: rgba(0,0,0,0.15);
  --text: #111;
}

body {
  margin: 0;
  font-family: 'Inter', sans-serif;
  background: linear-gradient(135deg, #d0e6ff, #f0f4ff);
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
}

.reset-container {
  width: 100%;
  max-width: 400px;
  padding: 2.5rem 2rem;
  border-radius: var(--radius);
  background: var(--glass);
  backdrop-filter: blur(20px);
  box-shadow: 0 10px 32px var(--shadow);
  border: 1px solid rgba(255,255,255,0.2);
  text-align: center;
  animation: fadeIn 0.6s ease forwards;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

.reset-container h2 {
  font-weight: 600;
  color: var(--text);
  margin-bottom: 2rem;
}

.reset-container form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* Liquid input with eye toggle */
.input-wrap {
  position: relative;
}

.input-wrap input {
  width: 100%;
  padding: 14px 40px 14px 16px; /* right padding enough for eye */
  border-radius: 14px;
  border: none;
  background: rgba(255,255,255,0.4);
  color: #111;
  font-size: 1rem;
  outline: none;
  box-shadow: inset 2px 2px 8px rgba(0,0,0,0.05), inset -2px -2px 8px rgba(255,255,255,0.6);
  backdrop-filter: blur(10px);
  transition: all 0.3s ease;
  box-sizing: border-box; /* ✅ This fixes the overflow */
}


.input-wrap input::placeholder {
  color: rgba(17,17,17,0.6);
}

.input-wrap input:focus {
  box-shadow: 0 0 0 3px var(--primary);
}

.input-wrap .eye {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
  font-size: 16px;
  color: #666;
  user-select: none;
}

.input-wrap .eye:hover {
  color: #000;
}

.reset-container button {
  padding: 14px 16px;
  border-radius: 14px;
  border: none;
  font-weight: 600;
  font-size: 1rem;
  color: white;
  background: linear-gradient(90deg, #007aff, #0a84ff);
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 8px 16px rgba(0,0,0,0.12);
}

.reset-container button:hover {
  background: linear-gradient(90deg, #0a84ff, #007aff);
  transform: translateY(-2px);
}

.error-message {
  color: #ff3b30;
  font-size: 0.9rem;
  text-align: left;
}

.success-message {
  color: #0aab00;
  font-size: 1rem;
  margin-bottom: 1rem;
}

@media (max-width: 480px) {
  .reset-container {
    margin: 1rem;
    padding: 2rem 1.5rem;
  }
}
</style>

<script>
function togglePass(id, icon) {
  const input = document.getElementById(id);
  if (input.type === 'password') {
    input.type = 'text';
    icon.textContent = '🙈';
  } else {
    input.type = 'password';
    icon.textContent = '👁';
  }
}
</script>
</head>
<body>

<div class="reset-container">
  <h2>Reset Password</h2>

  <?php foreach ($errors as $e): ?>
    <div class="error-message"><?php echo htmlspecialchars($e); ?></div>
  <?php endforeach; ?>

  <?php if ($success): ?>
    <div class="success-message"><?php echo $success; ?></div>
  <?php else: ?>
  <form method="POST">
    <div class="input-wrap">
      <input type="password" id="password" name="password" placeholder="New password" required>
      <span class="eye" onclick="togglePass('password', this)">👁</span>
    </div>

    <div class="input-wrap">
      <input type="password" id="confirm" name="confirm" placeholder="Confirm password" required>
      <span class="eye" onclick="togglePass('confirm', this)">👁</span>
    </div>

    <button type="submit">Reset Password</button>
  </form>
  <?php endif; ?>
</div>

</body>
</html>
