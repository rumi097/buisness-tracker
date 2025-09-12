<?php
include 'db.php'; // Your PostgreSQL database connection file

$data = json_decode(file_get_contents("php://input"));
$action = isset($_GET['action']) ? $_GET['action'] : '';

// --- ACTION: REGISTER ---
if ($action == 'register') {
    $username = $data->username;
    $password = password_hash($data->password, PASSWORD_BCRYPT);
    $store_name = $data->store_name;
    $email = $data->email;
    $location = $data->location;

    // Use numbered placeholders ($1, $2, etc.) for PostgreSQL
    $sql = "INSERT INTO users (username, password, store_name, email, location) VALUES ($1, $2, $3, $4, $5)";
    
    // Prepare the statement with a unique name
    $stmt = pg_prepare($conn, "register_user", $sql);
    
    // Execute the prepared statement with an array of parameters
    $result = pg_execute($conn, "register_user", array($username, $password, $store_name, $email, $location));

    if ($result) {
        echo json_encode(["message" => "User registered successfully."]);
    } else {
        http_response_code(400);
        // Get the specific PostgreSQL error message
        echo json_encode(["message" => "Registration failed. Username or email might already be taken.", "error" => pg_last_error($conn)]);
    }
}

// --- ACTION: LOGIN ---
if ($action == 'login') {
    $username = $data->username;
    $password = $data->password;

    // Use a numbered placeholder ($1) for the username
    $sql = "SELECT id, password, store_name FROM users WHERE username = $1";
    
    $stmt = pg_prepare($conn, "login_user", $sql);
    $result = pg_execute($conn, "login_user", array($username));
    
    // Fetch the single user row
    $user = pg_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])) {
        echo json_encode(["message" => "Login successful.", "user_id" => $user['id'], "store_name" => $user['store_name']]);
    } else {
        http_response_code(401);
        echo json_encode(["message" => "Invalid credentials."]);
    }
}

pg_close($conn);
?>