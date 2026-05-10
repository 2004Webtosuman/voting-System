<?php require_once '../config/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../theme.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h2 {
            color: var(--accent);
        }
        .admin-icon {
            font-size: 3rem;
            color: var(--accent);
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

    <div class="form-container" style="border-top: 5px solid var(--accent);">
        <div class="login-header">
            <i class="fas fa-user-shield admin-icon"></i>
            <h2>Admin Login</h2>
            <p style="color: var(--text-secondary);">Secure access for administrators</p>
        </div>

        <form action="admincheck.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            
            <div class="form-group">
                <label for="username"><i class="fas fa-user-tie"></i> Admin Username</label>
                <input type="text" id="username" name="username" class="form-control" required
                       value="<?php echo isset($_COOKIE['admin_username']) ? htmlspecialchars($_COOKIE['admin_username']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-key"></i> Admin Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label style="font-weight: normal; margin: 0; display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="remember" <?php echo isset($_COOKIE['admin_username']) ? 'checked' : ''; ?>>
                    Remember me
                </label>
            </div>
            
            <button type="submit" class="btn btn-accent" style="width: 100%; margin-top: 1rem;">Login to Dashboard</button>
            
            <div style="text-align: center; margin-top: 1.5rem; font-size: 0.875rem;">
                <a href="../home.php" style="color: var(--text-secondary); text-decoration: none;"><i class="fas fa-arrow-left"></i> Back to Home</a>
            </div>
        </form>
    </div>

</body>
</html>
