<?php
include 'db.php'; // Your PostgreSQL database connection

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id > 0) {
    // Use a numbered placeholder ($1) for PostgreSQL
    $sql = "SELECT invoice_id, product_name, quantity_sold, sale_price_each, total_amount, sale_date 
            FROM sales_transactions 
            WHERE user_id = $1 
            ORDER BY sale_date DESC";
    
    // Prepare and execute the query using pgsql functions
    pg_prepare($conn, "get_sales_history", $sql);
    $result = pg_execute($conn, "get_sales_history", array($user_id));
    
    // pg_fetch_all returns an array of results, or false if there are none.
    $sales_history = pg_fetch_all($result);
    
    // Return the data as JSON, or an empty array if no results were found.
    echo json_encode($sales_history ?: []);
}

pg_close($conn);
?>