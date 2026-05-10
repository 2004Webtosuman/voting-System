<?php
require_once '../config/config.php';

$message = "";
$message_type = "";
$valid_token = false;
$email = "";

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Check if token exists and is not expired
    $stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = :token AND expires_at > NOW()");
    $stmt->execute(['token' => $token]);
    $result = $stmt->fetch();
    
    if ($result) {
        $valid_token = true;
        $email = $result['email'];
    } else {
        $message = "Invalid or expired password reset token.";
        $message_type = "error";
    }
} else {
    header("Location: loginpage.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $valid_token) {
    validate_csrf_token();
    
    $password = $_POST['password'];
    $retype_password = $_POST['retype_password'];
    
    if ($password === $retype_password) {
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        
        try {
            $pdo->beginTransaction();
            
            // Update password
            $stmt = $pdo->prepare("UPDATE newaccountregistration SET password_hash = :hash WHERE email = :email");
            $stmt->execute(['hash' => $password_hash, 'email' => $email]);
            
            // Delete used token
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = :email");
            $stmt->execute(['email' => $email]);
            
            $pdo->commit();
            
            $message = "Password has been successfully reset. You can now login.";
            $message_type = "success";
            $valid_token = false; // Hide form
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = "Database error. Failed to reset password.";
            $message_type = "error";
        }
    } else {
        $message = "Passwords do not match.";
        $message_type = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../theme.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a3c5e 0%, #2c5282 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .header i {
            font-size: 3rem;
            color: var(--accent);
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

    <div class="form-container">
        <div class="header">
            <i class="fas fa-lock-open"></i>
            <h2>Reset Password</h2>
        </div>

        <?php if ($message): ?>
            <div style="background: <?php echo $message_type === 'success' ? '#dcfce7' : '#fee2e2'; ?>; color: <?php echo $message_type === 'success' ? '#166534' : '#991b1b'; ?>; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: center;">
                <?php echo htmlspecialchars($message); ?>
            </div>
            
            <?php if ($message_type === 'success'): ?>
                <div style="text-align: center;">
                    <a href="loginpage.php" class="btn btn-primary">Go to Login</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($valid_token): ?>
            <form action="resetpassword.php?token=<?php echo htmlspecialchars($token); ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="retype_password">Confirm New Password</label>
                    <input type="password" id="retype_password" name="retype_password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-accent" style="width: 100%; margin-top: 1rem;">Set New Password</button>
            </form>
        <?php endif; ?>
        
        <?php if (!$valid_token && $message_type !== 'success'): ?>
            <div style="text-align: center;">
                <a href="forgotpassword.php" class="btn btn-outline">Request New Link</a>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
