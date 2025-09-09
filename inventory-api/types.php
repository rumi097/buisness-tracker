<?php
include 'db.php';

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
        $stmt = $conn->prepare("SELECT id, type_name FROM product_types WHERE user_id = ? ORDER BY type_name ASC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode($result);
        $stmt->close();
        break;

    case 'add_type':
        if ($data && !empty($data->type_name)) {
            $stmt = $conn->prepare("INSERT INTO product_types (user_id, type_name) VALUES (?, ?)");
            $stmt->bind_param("is", $user_id, $data->type_name);
            if ($stmt->execute()) {
                $new_id = $conn->insert_id;
                echo json_encode(["message" => "Type added successfully.", "new_type" => ["id" => $new_id, "type_name" => $data->type_name]]);
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Failed to add type. It might already exist."]);
            }
            $stmt->close();
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Type name cannot be empty."]);
        }
        break;
}

$conn->close();
?>