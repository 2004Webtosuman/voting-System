<?php
require_once '../config/config.php';

// Ensure the user is logged in as an admin
if (!isset($_SESSION['admin'])) {
    header('HTTP/1.0 403 Forbidden');
    exit('Forbidden');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('HTTP/1.0 400 Bad Request');
    exit('Invalid ID');
}

$id = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT voters_id_data, voters_id_mime FROM newaccountregistration WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();

    if ($row && !empty($row['voters_id_data'])) {
        // Decrypt the data
        $decryptedData = decrypt_data($row['voters_id_data']);
        
        if ($decryptedData === false) {
            header('HTTP/1.0 500 Internal Server Error');
            exit('Failed to decrypt image data.');
        }

        // Set the appropriate Content-Type
        $mime = !empty($row['voters_id_mime']) ? $row['voters_id_mime'] : 'application/octet-stream';
        header("Content-Type: " . $mime);
        
        // Output the raw image data
        echo $decryptedData;
        exit();
    } else {
        header('HTTP/1.0 404 Not Found');
        exit('Image not found');
    }
} catch (Exception $e) {
    header('HTTP/1.0 500 Internal Server Error');
    exit('Internal Server Error');
}
?>
