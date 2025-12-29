<?php
    require_once __DIR__ . '/config/config.php';
    if (! isset($conn)) {
        die("Database not connected");
    }

    $errors  = [];
    $success = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email'] ?? '');

        if (empty($email)) {
            $errors[] = "Email is required";
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                header("Location: reset_password.php?email=" . urlencode($email));
                exit();
            } else {
                $errors[] = "Email not found";
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password</title>

<!-- Google Font -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">

<style>
:root {
  --primary: #007aff;
  --secondary: rgba(255, 255, 255, 0.25);
  --radius: 20px;
  --shadow: rgba(0, 0, 0, 0.15);
  --text: #111;
}

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
  font-family: 'Inter', sans-serif;
}

body {
  height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  background: linear-gradient(135deg, #d0e6ff, #f0f4ff);
}

.forgot-container {
  width: 100%;
  max-width: 400px;
  padding: 2.5rem 2rem;
  border-radius: var(--radius);
  background: rgba(255, 255, 255, 0.25);
  backdrop-filter: blur(20px);
  box-shadow: 0 8px 32px var(--shadow);
  border: 1px solid rgba(255, 255, 255, 0.2);
  text-align: center;
  animation: fadeIn 0.6s ease forwards;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

.forgot-container h2 {
  font-weight: 600;
  color: var(--text);
  margin-bottom: 2rem;
}

.forgot-container form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.forgot-container input[type="email"] {
  padding: 14px 16px;
  border-radius: 14px;
  border: none;
  background: rgba(255, 255, 255, 0.4);
  color: #111;
  font-size: 1rem;
  outline: none;
  box-shadow: inset 2px 2px 8px rgba(0,0,0,0.05), inset -2px -2px 8px rgba(255,255,255,0.6);
  transition: all 0.3s ease;
  backdrop-filter: blur(10px);
}

.forgot-container input[type="email"]::placeholder {
  color: rgba(17,17,17,0.6);
}

.forgot-container input[type="email"]:focus {
  box-shadow: 0 0 0 3px var(--primary);
}

.forgot-container button {
  padding: 14px 16px;
  border-radius: 14px;
  border: none;
  font-weight: 600;
  font-size: 1rem;
  color: white;
  background: linear-gradient(90deg, #007aff, #0a84ff);
  cursor: pointer;
  transition: all 0.3s ease;
  backdrop-filter: blur(5px);
  box-shadow: 0 8px 16px rgba(0,0,0,0.12);
}

.forgot-container button:hover {
  background: linear-gradient(90deg, #0a84ff, #007aff);
  transform: translateY(-2px);
}

.error-message {
  color: #ff3b30;
  font-size: 0.9rem;
  text-align: left;
}

@media (max-width: 480px) {
  .forgot-container {
    margin: 1rem;
    padding: 2rem 1.5rem;
  }
}
</style>
</head>
<body>

<div class="forgot-container">
  <h2>Forgot Password</h2>

  <?php foreach ($errors as $e): ?>
    <div class="error-message"><?php echo htmlspecialchars($e); ?></div>
  <?php endforeach; ?>

  <form method="POST">
    <input type="email" name="email" placeholder="Enter your email" required>
    <button type="submit">Continue</button>
  </form>
</div>

</body>
</html>
