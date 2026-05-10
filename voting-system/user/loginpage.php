<?php require_once '../config/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Login</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../theme.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a3c5e 0%, #2c5282 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header img {
            height: 60px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

    <div class="form-container">
        <div class="login-header">
            <img src="../photo/logo1.png" alt="Logo">
            <h2>Voter Login</h2>
            <p style="color: var(--text-secondary);">Sign in to cast your vote</p>
        </div>

        <form action="logincheck.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Username</label>
                <input type="text" id="username" name="username" class="form-control" required 
                       value="<?php echo isset($_COOKIE['username']) ? htmlspecialchars($_COOKIE['username']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            
            <div class="form-group" style="display: flex; justify-content: space-between; align-items: center;">
                <label style="font-weight: normal; margin: 0; display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="remember" <?php echo isset($_COOKIE['username']) ? 'checked' : ''; ?>>
                    Remember me
                </label>
                <a href="forgotpassword.php" style="color: var(--primary); text-decoration: none; font-size: 0.875rem;">Forgot password?</a>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Login</button>
            
            <div style="text-align: center; margin-top: 1.5rem; font-size: 0.875rem;">
                Don't have an account? <a href="newaccountregistration.php" style="color: var(--accent); font-weight: 600; text-decoration: none;">Register here</a>
            </div>
            <div style="text-align: center; margin-top: 1rem; font-size: 0.875rem;">
                <a href="../home.php" style="color: var(--text-secondary); text-decoration: none;"><i class="fas fa-arrow-left"></i> Back to Home</a>
            </div>
        </form>
    </div>

</body>
</html>
