<?php
include 'db.php';
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id > 0) {
    $sql = "SELECT invoice_id, product_name, quantity_sold, sale_price_each, total_amount, sale_date 
            FROM sales_transactions 
            WHERE user_id = ? 
            ORDER BY sale_date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sales_history = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($sales_history);
    $stmt->close();
}

$conn->close();
?>