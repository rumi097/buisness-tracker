<?php
include 'db.php'; // Your PostgreSQL connection

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'daily';
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

if ($user_id > 0) {
    // --- Sales Report ---
    $start_date = new DateTime();
    $end_date = new DateTime();
    
    if ($filter == 'daily') {
        $start_date->setTime(0, 0, 0);
        $end_date->setTime(23, 59, 59);
    } elseif ($filter == 'weekly') {
        $start_date->modify('monday this week')->setTime(0, 0, 0);
        $end_date = (clone $start_date)->modify('+6 days')->setTime(23, 59, 59);
    } else { // monthly
        $start_date = new DateTime($month . '-01');
        $end_date = (clone $start_date)->modify('last day of this month')->setTime(23, 59, 59);
    }

    $sales_query = "SELECT SUM(total_amount) as total_sales, SUM(total_investment) as total_investment, SUM(profit) as total_profit FROM sales WHERE user_id = $1 AND sale_date BETWEEN $2 AND $3";
    pg_prepare($conn, "sales_report_query", $sales_query);
    $sales_result = pg_execute($conn, "sales_report_query", array($user_id, $start_date->format('Y-m-d H:i:s'), $end_date->format('Y-m-d H:i:s')));
    $sales_report = pg_fetch_assoc($sales_result);

    // --- Value of Current Stock ---
    $stock_query = "SELECT SUM(quantity * wholesale_price) as current_stock_value FROM products WHERE user_id = $1 AND is_active = 1";
    pg_prepare($conn, "stock_value_query", $stock_query);
    $stock_result = pg_execute($conn, "stock_value_query", array($user_id));
    $stock_value = pg_fetch_assoc($stock_result);

    // --- Monthly Investment ---
    $monthly_start = new DateTime($month . '-01');
    $monthly_end = (clone $monthly_start)->modify('last day of this month')->setTime(23, 59, 59);
    
    $monthly_investment_query = "SELECT SUM(quantity_added * wholesale_price_each) as monthly_investment FROM stock_additions WHERE user_id = $1 AND addition_date BETWEEN $2 AND $3";
    pg_prepare($conn, "monthly_invest_query", $monthly_investment_query);
    $monthly_investment_result = pg_execute($conn, "monthly_invest_query", array($user_id, $monthly_start->format('Y-m-d H:i:s'), $monthly_end->format('Y-m-d H:i:s')));
    $monthly_investment = pg_fetch_assoc($monthly_investment_result);

    // --- Total Investment ---
    $total_investment_query = "SELECT SUM(quantity_added * wholesale_price_each) as total_investment FROM stock_additions WHERE user_id = $1";
    pg_prepare($conn, "total_invest_query", $total_investment_query);
    $total_investment_result = pg_execute($conn, "total_invest_query", array($user_id));
    $total_investment = pg_fetch_assoc($total_investment_result);

    // --- Final Output ---
    echo json_encode([
        "sales_report" => $sales_report,
        "current_stock_value" => $stock_value['current_stock_value'] ?? 0,
        "monthly_investment" => $monthly_investment['monthly_investment'] ?? 0,
        "total_investment" => $total_investment['total_investment'] ?? 0
    ]);
}
pg_close($conn);
?>