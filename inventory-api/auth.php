<?php
include 'db.php';

$data = json_decode(file_get_contents("php://input"));
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action == 'register') {
    $username = $data->username;
    $password = password_hash($data->password, PASSWORD_BCRYPT);
    $store_name = $data->store_name;
    $email = $data->email;
    $location = $data->location;

    $sql = "INSERT INTO users (username, password, store_name, email, location) VALUES ($1, $2, $3, $4, $5)";
    
    // DEBUG: Check if the prepare statement works
    $stmt = pg_prepare($conn, "register_user", $sql);
    if (!$stmt) {
        http_response_code(500);
        die(json_encode(["message" => "SQL Prepare Failed: " . pg_last_error($conn)]));
    }
    
    // DEBUG: Check if the execute statement works
    $result = pg_execute($conn, "register_user", array($username, $password, $store_name, $email, $location));

    if ($result) {
        echo json_encode(["message" => "User registered successfully."]);
    } else {
        http_response_code(400);
        // This will now give the specific reason for failure
        echo json_encode(["message" => "Registration failed.", "error" => pg_last_error($conn)]);
    }
}

// ... (your login code remains the same) ...

pg_close($conn);
?>