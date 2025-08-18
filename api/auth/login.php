<?php
include_once '../config/database.php';
include_once '../models/User.php';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);

$data = json_decode(file_get_contents("php://input"));

if (isset($data->email) && isset($data->password)) {
    $user->email = $data->email;
    $user->password = $data->password;

    if ($user->login()) {
        // Generate JWT token (simplified version)
        $token = base64_encode(json_encode([
            'user_id' => $user->id,
            'username' => $user->username,
            'role' => $user->role,
            'exp' => time() + (24 * 60 * 60) // 24 hours
        ]));

        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Login successful",
            "token" => $token,
            "user" => [
                "id" => $user->id,
                "username" => $user->username,
                "email" => $user->email,
                "full_name" => $user->full_name,
                "role" => $user->role
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "Invalid email or password"
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Email and password are required"
    ]);
}
?>