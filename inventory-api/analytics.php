<?php
include 'db.php';

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$period = isset($_GET['period']) ? $_GET['period'] : 'daily'; // daily, weekly, monthly

if ($user_id <= 0) {
    http_response_code(403);
    exit("User not specified.");
}

$period_condition = "";
switch ($period) {
    case 'weekly':
        $period_condition = "AND WEEK(sale_date) = WEEK(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())";
        break;
    case 'monthly':
        $period_condition = "AND DATE_FORMAT(sale_date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
        break;
    default: // daily
        $period_condition = "AND DATE(sale_date) = CURDATE()";
        break;
}

// Chart Data: Sum of quantities sold for each product in the period
$chart_sql = "SELECT product_name, SUM(quantity_sold) as total_quantity 
              FROM sales_transactions 
              WHERE user_id = ? $period_condition 
              GROUP BY product_name 
              ORDER BY total_quantity DESC";
$chart_stmt = $conn->prepare($chart_sql);
$chart_stmt->bind_param("i", $user_id);
$chart_stmt->execute();
$chart_data = $chart_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$chart_stmt->close();

// Highest Selling Product
$highest_sql = "SELECT product_name, SUM(quantity_sold) as total_quantity 
                FROM sales_transactions 
                WHERE user_id = ? $period_condition 
                GROUP BY product_name 
                ORDER BY total_quantity DESC 
                LIMIT 1";
$highest_stmt = $conn->prepare($highest_sql);
$highest_stmt->bind_param("i", $user_id);
$highest_stmt->execute();
$highest_product = $highest_stmt->get_result()->fetch_assoc();
$highest_stmt->close();

// Lowest Selling Product
$lowest_sql = "SELECT product_name, SUM(quantity_sold) as total_quantity 
               FROM sales_transactions 
               WHERE user_id = ? $period_condition 
               GROUP BY product_name 
               ORDER BY total_quantity ASC 
               LIMIT 1";
$lowest_stmt = $conn->prepare($lowest_sql);
$lowest_stmt->bind_param("i", $user_id);
$lowest_stmt->execute();
$lowest_product = $lowest_stmt->get_result()->fetch_assoc();
$lowest_stmt->close();

echo json_encode([
    'chartData' => $chart_data,
    'highestProduct' => $highest_product,
    'lowestProduct' => $lowest_product
]);

$conn->close();
?>