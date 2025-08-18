<?php
include_once '../config/database.php';
include_once '../models/User.php';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);

$data = json_decode(file_get_contents("php://input"));

if (isset($data->username) && isset($data->email) && isset($data->password) && isset($data->full_name)) {
    
    // Check if email already exists
    $user->email = $data->email;
    if ($user->emailExists()) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Email already exists"
        ]);
        exit();
    }

    $user->username = $data->username;
    $user->password = $data->password;
    $user->full_name = $data->full_name;
    $user->phone = isset($data->phone) ? $data->phone : '';
    $user->role = 'user';

    if ($user->create()) {
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "User registered successfully",
            "user_id" => $user->id
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Unable to register user"
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Required fields: username, email, password, full_name"
    ]);
}
?>