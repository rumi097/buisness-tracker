<?php
include 'db.php'; // Your PostgreSQL connection

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$user_id = 0;
$data = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    $user_id = isset($data->user_id) ? intval($data->user_id) : 0;
} else {
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
}

if ($user_id <= 0) {
    http_response_code(403);
    exit("User not specified.");
}

switch ($action) {
    case 'get_types':
        // Use a numbered placeholder ($1) for PostgreSQL
        $sql = "SELECT id, type_name FROM product_types WHERE user_id = $1 ORDER BY type_name ASC";
        
        pg_prepare($conn, "get_types_query", $sql);
        $result = pg_execute($conn, "get_types_query", array($user_id));
        
        $types = pg_fetch_all($result) ?: []; // Return empty array if no results
        echo json_encode($types);
        break;

    case 'add_type':
        if ($data && !empty($data->type_name)) {
            // Use RETURNING id to get the new ID back from the insert
            $sql = "INSERT INTO product_types (user_id, type_name) VALUES ($1, $2) RETURNING id";
            
            pg_prepare($conn, "add_type_query", $sql);
            $result = pg_execute($conn, "add_type_query", array($user_id, $data->type_name));
            
            if ($result) {
                $new_id = pg_fetch_assoc($result)['id'];
                echo json_encode(["message" => "Type added successfully.", "new_type" => ["id" => $new_id, "type_name" => $data->type_name]]);
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Failed to add type. It might already exist."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Type name cannot be empty."]);
        }
        break;
}

pg_close($conn);
?>