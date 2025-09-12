<?php
include 'db.php'; // Your PostgreSQL database connection

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

            // Use numbered placeholders for PostgreSQL
            $sql = "INSERT INTO expenses (user_id, title, amount) VALUES ($1, $2, $3)";
            pg_prepare($conn, "add_expense_query", $sql);
            $result = pg_execute($conn, "add_expense_query", array($user_id, $title, $amount));
            
            if ($result) {
                echo json_encode(["message" => "Expense added successfully."]);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Failed to add expense."]);
            }
        }
        break;

    case 'get_summary':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Helper function to execute sum queries
            function get_total_expense($conn, $user_id, $interval_sql) {
                $sql = "SELECT SUM(amount) as total FROM expenses WHERE user_id = $1 AND expense_date >= $2 AND expense_date < $3";
                pg_prepare($conn, "sum_query_" . uniqid(), $sql);
                $result = pg_execute($conn, "sum_query_" . uniqid(), array($user_id, $interval_sql['start'], $interval_sql['end']));
                return pg_fetch_assoc($result)['total'] ?? 0;
            }

            // Calculate date ranges in PHP for portability
            $today = new DateTime();
            $daily_interval = ['start' => $today->format('Y-m-d'), 'end' => $today->modify('+1 day')->format('Y-m-d')];
            
            $monday = (new DateTime())->modify('monday this week');
            $next_monday = (clone $monday)->modify('+1 week');
            $weekly_interval = ['start' => $monday->format('Y-m-d'), 'end' => $next_monday->format('Y-m-d')];
            
            $first_of_month = (new DateTime())->modify('first day of this month');
            $first_of_next_month = (clone $first_of_month)->modify('+1 month');
            $monthly_interval = ['start' => $first_of_month->format('Y-m-d'), 'end' => $first_of_next_month->format('Y-m-d')];

            echo json_encode([
                "daily" => get_total_expense($conn, $user_id, $daily_interval),
                "weekly" => get_total_expense($conn, $user_id, $weekly_interval),
                "monthly" => get_total_expense($conn, $user_id, $monthly_interval)
            ]);
        }
        break;
}

pg_close($conn);
?>