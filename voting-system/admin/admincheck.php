<?php
require_once '../config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    validate_csrf_token();

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM admincheck WHERE username = :uname");
    $stmt->execute(['uname' => $username]);
    $admin = $stmt->fetch();

    // Use password_verify as requested (ensure admin passwords in DB are hashed)
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin'] = $username;
        
        if (isset($_POST['remember'])) {
            setcookie("admin_username", $username, time() + (86400 * 30), "/"); 
        }

        header("Location: adminpage.php");
        exit();
    } else {
        render_error_page("Invalid admin username or password.");
    }
} else {
    header("Location: adminloginpage.php");
    exit();
}
?>
