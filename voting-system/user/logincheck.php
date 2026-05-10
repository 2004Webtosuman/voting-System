<?php
require_once '../config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    validate_csrf_token();

    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // Rate Limiting Check (5 attempts per 15 minutes)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = :ip AND attempted_at > (NOW() - INTERVAL 15 MINUTE)");
    $stmt->execute(['ip' => $ip_address]);
    $attempts = $stmt->fetchColumn();

    if ($attempts >= 5) {
        render_error_page("Too many failed login attempts. Please try again after 15 minutes.");
    }

    // Fetch user
    $stmt = $pdo->prepare("SELECT * FROM newaccountregistration WHERE username = :uname");
    $stmt->execute(['uname' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Successful login
        if ($user['status'] !== 'active') {
             render_error_page("Your account is currently " . htmlspecialchars($user['status']) . ". Please contact the administrator.");
        }

        // Clear previous failed attempts
        $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = :ip");
        $stmt->execute(['ip' => $ip_address]);

        $_SESSION['username'] = $user['username'];
        $_SESSION['user_id'] = $user['id'];
        
        if (isset($_POST['remember'])) {
            setcookie("username", $username, time() + (86400 * 30), "/"); 
        }

        header("Location: voting.php");
        exit();
    } else {
        // Record failed attempt
        $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, username) VALUES (:ip, :uname)");
        $stmt->execute(['ip' => $ip_address, 'uname' => $username]);

        render_error_page("Invalid username or password.");
    }
} else {
    header("Location: loginpage.php");
    exit();
}
?>
