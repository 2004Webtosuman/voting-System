<?php
require_once '../config/config.php';

if (!isset($_SESSION['admin'])) {
    header("Location: adminloginpage.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM newaccountregistration WHERE id = :id");
        if ($stmt->execute(['id' => $id])) {
            header("Location: adminpage.php?msg=deleted");
            exit();
        } else {
            render_error_page("Failed to delete user.");
        }
    } catch (PDOException $e) {
        render_error_page("Database error: " . $e->getMessage());
    }
} else {
    header("Location: adminpage.php");
    exit();
}
?>
