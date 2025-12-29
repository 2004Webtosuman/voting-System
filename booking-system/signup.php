<?php
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/includes/functions.php';

    $errors  = [];
    $success = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name             = trim($_POST['name'] ?? '');
        $email            = trim($_POST['email'] ?? '');
        $password         = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $phone            = trim($_POST['phone'] ?? '');
        $role             = $_POST['role'] ?? 'customer';

        // Validation
        if (empty($name)) {
            $errors[] = 'Name is required';
        }

        if (empty($email)) {
            $errors[] = 'Email is required';
        }

        if (empty($password)) {
            $errors[] = 'Password is required';
        }

        if ($password !== $password_confirm) {
            $errors[] = 'Passwords do not match';
        }

        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        }

        if (! in_array($role, ['customer', 'provider'])) {
            $errors[] = 'Invalid role';
        }

        if (empty($errors)) {
            try {
                // Check if email exists
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);

                if ($stmt->rowCount() > 0) {
                    $errors[] = 'Email already registered';
                } else {
                    // Insert user
                    $hashed_password = hashPassword($password);
                    $stmt            = $conn->prepare("
          INSERT INTO users (name, email, password, phone, role)
          VALUES (?, ?, ?, ?, ?)
        ");

                    if ($stmt->execute([$name, $email, $hashed_password, $phone, $role])) {
                        $user_id = $conn->lastInsertId();

                        // If provider, create provider profile
                        if ($role === 'provider') {
                            $stmt = $conn->prepare("
              INSERT INTO providers (user_id, service_type, hourly_rate)
              VALUES (?, 'plumber', 50)
            ");
                            $stmt->execute([$user_id]);
                        }

                        $success = 'Account created successfully. Please <a href="login.php">login here</a>';
                    }
                }
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up - Professional Service Booking</title>
  <link rel="stylesheet" href="public/style.css">
  <style>

    .signup-container {
      max-width: 500px;
      margin: 40px auto;
      padding: 0 20px;
    }

    .signup-card {
      background: white;
      padding: 2rem;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .signup-card h1 {
      text-align: center;
      color: var(--primary);
      margin-bottom: 2rem;
    }

    .login-link {
      text-align: center;
      margin-top: 1.5rem;
      padding-top: 1.5rem;
      border-top: 1px solid var(--border);
    }

    .login-link a {
      color: var(--primary);
      text-decoration: none;
      font-weight: 600;
    }


    /*pass*/
     .input-wrap {
  position: relative;
}

.input-wrap input {
  width: 100%;
  padding: 10px 38px 10px 10px; /* space for icon */
}

.input-wrap .eye {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
  font-size: 16px;
  opacity: 0.7;
}

.input-wrap .eye:hover {
  opacity: 1;
}


  </style>
 <script>
function togglePass(id, icon) {
  const input = document.getElementById(id);
  const isPass = input.type === "password";

  input.type = isPass ? "text" : "password";
  icon.textContent = isPass ? "👁" : "👁";
}
</script>

</head>
<body>
  <div class="signup-container">
    <div class="signup-card">
      <h1>Create Account</h1>

      <?php if ($errors): ?>
        <?php foreach ($errors as $error): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endforeach; ?>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
      <?php else: ?>
        <form method="POST" action="">
          <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
          </div>

          <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
          </div>

          <div class="form-group">
            <label>Phone Number</label>
            <input type="tel" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
          </div>

          <div class="form-group">
            <label>Account Type</label>
            <select name="role" required>
              <option value="customer">Customer</option>
              <option value="provider">Service Provider</option>
            </select>
          </div>

      <div class="form-group">
  <label>Password</label>
  <div class="input-wrap">
    <input type="password" id="password" name="password" required>
    <span class="eye" onclick="togglePass('password', this)">👁</span>
  </div>
</div>

<div class="form-group">
  <label>Confirm Password</label>
  <div class="input-wrap">
    <input type="password" id="confirm" name="password_confirm" required>
    <span class="eye" onclick="togglePass('confirm', this)">👁</span>
  </div>
</div>



          <button type="submit" class="btn btn-primary" style="width: 100%;">Sign Up</button>
        </form>

        <div class="login-link">
          Already have an account? <a href="login.php">Login here</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
