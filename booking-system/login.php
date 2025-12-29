<?php
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/includes/functions.php';

    $errors  = [];
    $success = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $errors[] = 'Email and password are required';
        } else {
            try {
                $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && verifyPassword($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role']    = $user['role'];

                    // Redirect based on role
                    if ($user['role'] === 'admin') {
                        header('Location: admin/dashboard.php');
                    } elseif ($user['role'] === 'provider') {
                        header('Location: provider/dashboard.php');
                    } else {
                        header('Location: customer/dashboard.php');
                    }
                    exit();
                } else {
                    $errors[] = 'Invalid email or password';
                }
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }

    // Demo credentials message
    $demo_credentials = [
        ['email' => 'john@example.com', 'password' => 'password123', 'role' => 'Customer'],
        ['email' => 'jane@example.com', 'password' => 'password123', 'role' => 'Provider'],
        ['email' => 'admin@example.com', 'password' => 'admin123', 'role' => 'Admin'],
    ];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Professional Service Booking</title>
  <link rel="stylesheet" href="public/style.css">
  <style>
    .login-container {
      max-width: 400px;
      margin: 60px auto;
      padding: 0 20px;
    }

    .login-card {
      background: white;
      padding: 2rem;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .login-card h1 {
      text-align: center;
      color: var(--primary);
      margin-bottom: 2rem;
      font-size: 2rem;
    }

    .demo-creds {
      background: var(--light);
      border-left: 4px solid var(--warning);
      padding: 1rem;
      border-radius: var(--radius);
      margin-bottom: 2rem;
      font-size: 0.9rem;
    }

    .demo-creds h3 {
      margin-bottom: 0.5rem;
      color: var(--dark);
    }

    .demo-creds p {
      margin: 0.25rem 0;
    }

    .signup-link {
      text-align: center;
      margin-top: 1.5rem;
      padding-top: 1.5rem;
      border-top: 1px solid var(--border);
    }

    .signup-link a {
      color: var(--primary);
      text-decoration: none;
      font-weight: 600;
    }

    .signup-link a:hover {
      text-decoration: underline;
    }



/*pass*/
.input-wrap {
  position: relative;
  width: 100%;
}

.input-wrap input {
  width: 100%;
  padding: 10px 42px 10px 10px; /* room for eye */
  box-sizing: border-box;
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


  </style>

<script>
function togglePass(inputId, icon) {
  const input = document.getElementById(inputId);

  if (input.type === "password") {
    input.type = "text";
    icon.textContent = "👁";
  } else {
    input.type = "password";
    icon.textContent = "👁";
  }
}
</script>


</head>
<body>
  <div class="login-container">
    <div class="login-card">
      <h1>Professional Service Booking</h1>

      <?php if ($errors): ?>
        <?php foreach ($errors as $error): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endforeach; ?>
      <?php endif; ?>

      <div class="demo-creds">
        <h3>Demo Credentials:</h3>
        <?php foreach ($demo_credentials as $cred): ?>
          <p><strong><?php echo $cred['role']; ?>:</strong><?php echo $cred['email']; ?> /<?php echo $cred['password']; ?></p>
        <?php endforeach; ?>
      </div>

      <form method="POST" action="">
        <div class="form-group">
          <label>Email Address</label>
          <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>

          <div class="form-group">
  <label>Password</label>
  <div class="input-wrap">
    <input type="password" id="password" name="password" required>
    <span class="eye" onclick="togglePass('password', this)">👁</span>
  </div>
</div>

        <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
        <div style="text-align:center; margin-top:10px;">
  <a href="forgot_password.php">Forgot password?</a>
</div>

      </form>

      <div class="signup-link">
        Don't have an account? <a href="signup.php">Sign up here</a>
      </div>
    </div>
  </div>
</body>
</html>
