<?php
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    render_error_page("Invalid request method.");
}

validate_csrf_token();

try {
    $uname = trim($_POST['username']);
    $email = trim($_POST['email']);
    $pass = $_POST['password'];
    $re = $_POST['retype_password'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $voters_id_number = trim($_POST['voters_id_number']);
    $status = "pending";

    // 1. Validations
    if (strlen($uname) < 4 || strlen($uname) > 8) {
        render_error_page("Username must be between 4 and 8 characters.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        render_error_page("Invalid email format.");
    }

    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        render_error_page("Phone number must be exactly 10 digits.");
    }

    if ($pass !== $re) {
        render_error_page("Passwords do not match.");
    }

    // Voter ID Format Validation
    if (!preg_match('/^[0-9]{10}$/', $voters_id_number)) {
        render_error_page("Voter ID number must be exactly 10 digits.");
    }

    // Age Verification (Must be 18 or older)
    $dobDate = new DateTime($dob);
    $now = new DateTime();
    $age = $now->diff($dobDate)->y;
    if ($age < 18) {
        render_error_page("You must be at least 18 years old to register.");
    }

    // 2. Handle File Upload
    $voters_id_data = null;
    $voters_id_mime = null;

    if (isset($_FILES['voters_id']) && $_FILES['voters_id']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['voters_id']['tmp_name'];
        $fileName = $_FILES['voters_id']['name'];
        $fileSize = $_FILES['voters_id']['size'];
        $fileType = $_FILES['voters_id']['type'];
        
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            render_error_page("Invalid file type. Only JPG, PNG, and PDF are allowed.");
        }
        
        if ($fileSize > $maxSize) {
            render_error_page("File size exceeds 2MB limit.");
        }
        
        // Read file contents and encrypt
        $fileContent = file_get_contents($fileTmpPath);
        if ($fileContent === false) {
            render_error_page("There was an error reading the uploaded file.");
        }
        
        $voters_id_data = encrypt_data($fileContent);
        $voters_id_mime = $fileType;

    } else {
        render_error_page("Voter ID file upload is required.");
    }

    // 3. Database Check and Insert
    // Check if voter ID already exists
    $stmt = $pdo->prepare("SELECT id FROM newaccountregistration WHERE voters_id_number = :vid");
    $stmt->execute(['vid' => $voters_id_number]);
    if ($stmt->fetch()) {
        render_error_page("Voter ID number already exists. Please use a different ID.");
    }
    
    // Check if username already exists
    $stmt = $pdo->prepare("SELECT id FROM newaccountregistration WHERE username = :uname");
    $stmt->execute(['uname' => $uname]);
    if ($stmt->fetch()) {
        render_error_page("Username already exists. Please choose a different one.");
    }

    // Hash the password
    $password_hash = password_hash($pass, PASSWORD_BCRYPT);

    $sql = "INSERT INTO newaccountregistration 
            (username, email, password_hash, date_of_birth, gender, address, phone, voters_id_number, voters_id_data, voters_id_mime, status) 
            VALUES 
            (:uname, :email, :phash, :dob, :gender, :address, :phone, :vid, :vdata, :vmime, :status)";
            
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        'uname' => $uname,
        'email' => $email,
        'phash' => $password_hash,
        'dob' => $dob,
        'gender' => $gender,
        'address' => $address,
        'phone' => $phone,
        'vid' => $voters_id_number,
        'vdata' => $voters_id_data,
        'vmime' => $voters_id_mime,
        'status' => $status
    ]);

    if($result) {
        header("Location: home.php?registered=1");
        exit();
    } else {
        render_error_page("Sorry, data could not be inserted.");
    }

} catch(PDOException $e) {
    // Handle specific constraint errors if needed, otherwise generic error
    render_error_page("Database error occurred during registration. " . $e->getMessage());
} catch(Exception $ex) {
    render_error_page($ex->getMessage());
}
?>
