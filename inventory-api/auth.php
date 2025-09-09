<?php
include 'db.php';

$data = json_decode(file_get_contents("php://input"));
$action = isset($_GET['action']) ? $_GET['action'] : '';

// --- ACTION: REGISTER (Directly, no OTP) ---
if ($action == 'register') {
    $username = $data->username;
    $password = password_hash($data->password, PASSWORD_BCRYPT);
    $store_name = $data->store_name;
    $email = $data->email;
    $location = $data->location;

    $sql = "INSERT INTO users (username, password, store_name, email, location) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $username, $password, $store_name, $email, $location);

    if ($stmt->execute()) {
        echo json_encode(["message" => "User registered successfully."]);
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Registration failed. Username or email might already be taken.", "error" => $stmt->error]);
    }
    $stmt->close();
}

// --- ACTION: LOGIN (No changes) ---
if ($action == 'login') {
    $username = $data->username;
    $password = $data->password;
    $sql = "SELECT id, password, store_name FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        echo json_encode(["message" => "Login successful.", "user_id" => $user['id'], "store_name" => $user['store_name']]);
    } else {
        http_response_code(401);
        echo json_encode(["message" => "Invalid credentials."]);
    }
    $stmt->close();
}

$conn->close();
?>