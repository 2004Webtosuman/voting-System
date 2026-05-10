<?php
require_once '../config/config.php';

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    validate_csrf_token();
    
    $email = trim($_POST['email']);
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT id, username FROM newaccountregistration WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Generate Token
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", time() + 3600); // 1 hour expiry
        
        // Insert into password_resets
        $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires)");
        $stmt->execute([
            'email' => $email,
            'token' => $token,
            'expires' => $expires
        ]);
        
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/resetpassword.php?token=" . $token;
        
        // In a real scenario, use PHPMailer to send the email.
        /*
        require 'path/to/PHPMailer/src/Exception.php';
        require 'path/to/PHPMailer/src/PHPMailer.php';
        require 'path/to/PHPMailer/src/SMTP.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            //Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.example.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'user@example.com';
            $mail->Password   = 'secret';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            //Recipients
            $mail->setFrom('noreply@votingsystem.com', 'Voting System');
            $mail->addAddress($email, $user['username']);

            //Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body    = "Click the following link to reset your password: <a href='$reset_link'>$reset_link</a><br>This link expires in 1 hour.";

            $mail->send();
        } catch (Exception $e) {
            // Log error
        }
        */
        
        // For demonstration, we'll just display it (REMOVE IN PRODUCTION)
        $message = "If an account with that email exists, a password reset link has been sent. <br><br> <small>(Dev Note: Mock link -> <a href='$reset_link'>$reset_link</a>)</small>";
        $message_type = "success";
    } else {
        // Security: Don't reveal if email exists or not. Show same success message.
        $message = "If an account with that email exists, a password reset link has been sent.";
        $message_type = "success";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
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
            <i class="fas fa-key"></i>
            <h2>Forgot Password</h2>
            <p style="color: var(--text-secondary);">Enter your email to receive a reset link.</p>
        </div>

        <?php if ($message): ?>
            <div style="background: <?php echo $message_type === 'success' ? '#dcfce7' : '#fee2e2'; ?>; color: <?php echo $message_type === 'success' ? '#166534' : '#991b1b'; ?>; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: center;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="forgotpassword.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Registered Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Send Reset Link</button>
            
            <div style="text-align: center; margin-top: 1.5rem; font-size: 0.875rem;">
                Remembered your password? <a href="loginpage.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">Login here</a>
            </div>
        </form>
    </div>

</body>
</html>
