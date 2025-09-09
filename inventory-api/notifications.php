<?php
include 'db.php';

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : 'get_all'; // get_all, get_count

if ($user_id <= 0) {
    http_response_code(403);
    exit("User not specified.");
}

// You can change this threshold value
define('LOW_STOCK_THRESHOLD', 5);

if ($action === 'get_count') {
    $stmt = $conn->prepare("SELECT COUNT(*) as low_stock_count FROM products WHERE user_id = ? AND is_active = 1 AND quantity <= ?");
    $threshold = LOW_STOCK_THRESHOLD;
    $stmt->bind_param("ii", $user_id, $threshold);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    echo json_encode($result);
    $stmt->close();
} else { // get_all
    $stmt = $conn->prepare("SELECT name, quantity FROM products WHERE user_id = ? AND is_active = 1 AND quantity <= ? ORDER BY quantity ASC");
    $threshold = LOW_STOCK_THRESHOLD;
    $stmt->bind_param("ii", $user_id, $threshold);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode($result);
    $stmt->close();
}

$conn->close();
?>