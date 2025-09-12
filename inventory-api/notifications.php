<?php
include 'db.php'; // Your PostgreSQL database connection

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : 'get_all'; // get_all, get_count

if ($user_id <= 0) {
    http_response_code(403);
    exit("User not specified.");
}

// You can change this threshold value
define('LOW_STOCK_THRESHOLD', 5);
$threshold = LOW_STOCK_THRESHOLD;

if ($action === 'get_count') {
    // Use numbered placeholders ($1, $2) for PostgreSQL
    $sql = "SELECT COUNT(*) as low_stock_count FROM products WHERE user_id = $1 AND is_active = 1 AND quantity <= $2";
    
    pg_prepare($conn, "get_low_stock_count", $sql);
    $result = pg_execute($conn, "get_low_stock_count", array($user_id, $threshold));
    
    $count_data = pg_fetch_assoc($result);
    echo json_encode($count_data);

} else { // get_all
    // Use numbered placeholders ($1, $2) for PostgreSQL
    $sql = "SELECT name, quantity FROM products WHERE user_id = $1 AND is_active = 1 AND quantity <= $2 ORDER BY quantity ASC";
    
    pg_prepare($conn, "get_low_stock_products", $sql);
    $result = pg_execute($conn, "get_low_stock_products", array($user_id, $threshold));
    
    // pg_fetch_all returns an array of results, or false if there are none.
    $products_data = pg_fetch_all($result);
    echo json_encode($products_data ?: []); // Return empty array if no results
}

pg_close($conn);
?>