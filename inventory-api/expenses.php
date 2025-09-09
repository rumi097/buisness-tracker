<?php
include 'db.php';

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$user_id = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    $user_id = isset($data->user_id) ? intval($data->user_id) : 0;
} else {
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
}

if ($user_id <= 0) {
    http_response_code(403);
    echo json_encode(["message" => "User not specified."]);
    exit();
}

switch ($action) {
    case 'add_expense':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $data) {
            $title = $data->title;
            $amount = $data->amount;

            if (empty($title) || !is_numeric($amount) || $amount <= 0) {
                http_response_code(400);
                echo json_encode(["message" => "Invalid title or amount."]);
                exit();
            }

            $stmt = $conn->prepare("INSERT INTO expenses (user_id, title, amount) VALUES (?, ?, ?)");
            $stmt->bind_param("isd", $user_id, $title, $amount);
            
            if ($stmt->execute()) {
                echo json_encode(["message" => "Expense added successfully."]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Failed to add expense."]);
            }
            $stmt->close();
        }
        break;

    case 'get_summary':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Daily Total
            $daily_stmt = $conn->prepare("SELECT SUM(amount) as total FROM expenses WHERE user_id = ? AND DATE(expense_date) = CURDATE()");
            $daily_stmt->bind_param("i", $user_id);
            $daily_stmt->execute();
            $daily_total = $daily_stmt->get_result()->fetch_assoc()['total'] ?? 0;
            $daily_stmt->close();

            // Weekly Total
            $weekly_stmt = $conn->prepare("SELECT SUM(amount) as total FROM expenses WHERE user_id = ? AND WEEK(expense_date) = WEEK(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())");
            $weekly_stmt->bind_param("i", $user_id);
            $weekly_stmt->execute();
            $weekly_total = $weekly_stmt->get_result()->fetch_assoc()['total'] ?? 0;
            $weekly_stmt->close();
            
            // Monthly Total
            $monthly_stmt = $conn->prepare("SELECT SUM(amount) as total FROM expenses WHERE user_id = ? AND DATE_FORMAT(expense_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')");
            $monthly_stmt->bind_param("i", $user_id);
            $monthly_stmt->execute();
            $monthly_total = $monthly_stmt->get_result()->fetch_assoc()['total'] ?? 0;
            $monthly_stmt->close();

            echo json_encode([
                "daily" => $daily_total,
                "weekly" => $weekly_total,
                "monthly" => $monthly_total
            ]);
        }
        break;
}

$conn->close();
?>