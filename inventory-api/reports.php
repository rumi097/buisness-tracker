<?php
include 'db.php';
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'daily';
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

if ($user_id > 0) {
    // Sales Report (This part is correct)
    $sales_query = "";
    if ($filter == 'daily') $sales_query = "SELECT SUM(total_amount) as total_sales, SUM(total_investment) as total_investment, SUM(profit) as total_profit FROM sales WHERE user_id = ? AND DATE(sale_date) = CURDATE()";
    elseif ($filter == 'weekly') $sales_query = "SELECT SUM(total_amount) as total_sales, SUM(total_investment) as total_investment, SUM(profit) as total_profit FROM sales WHERE user_id = ? AND WEEK(sale_date) = WEEK(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())";
    else $sales_query = "SELECT SUM(total_amount) as total_sales, SUM(total_investment) as total_investment, SUM(profit) as total_profit FROM sales WHERE user_id = ? AND DATE_FORMAT(sale_date, '%Y-%m') = ?";
    
    $stmt = $conn->prepare($sales_query);
    if ($filter == 'monthly') $stmt->bind_param("is", $user_id, $month);
    else $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $sales_report = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // FIX: Value of Current Stock now only calculates active products
    $stock_query = "SELECT SUM(quantity * wholesale_price) as current_stock_value 
                    FROM products 
                    WHERE user_id = ? AND is_active = 1";
    $stmt = $conn->prepare($stock_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stock_value = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Monthly Investment (This part is correct)
    $monthly_investment_query = "SELECT SUM(quantity_added * wholesale_price_each) as monthly_investment FROM stock_additions WHERE user_id = ? AND DATE_FORMAT(addition_date, '%Y-%m') = ?";
    $stmt = $conn->prepare($monthly_investment_query);
    $stmt->bind_param("is", $user_id, $month);
    $stmt->execute();
    $monthly_investment = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Total Investment (This part is correct)
    $total_investment_query = "SELECT SUM(quantity_added * wholesale_price_each) as total_investment FROM stock_additions WHERE user_id = ?";
    $stmt = $conn->prepare($total_investment_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $total_investment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    echo json_encode([
        "sales_report" => $sales_report,
        "current_stock_value" => $stock_value['current_stock_value'],
        "monthly_investment" => $monthly_investment['monthly_investment'],
        "total_investment" => $total_investment['total_investment']
    ]);
}
$conn->close();
?>