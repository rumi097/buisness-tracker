<?php
include 'db.php'; // Your PostgreSQL connection

// Get the posted data.
$data = json_decode(file_get_contents("php://input"));

// Basic validation
if (!isset($data->user_id) || !isset($data->cart) || !is_array($data->cart) || empty($data->cart)) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid sale data provided."]);
    exit();
}

$user_id = intval($data->user_id);
$invoice_id = "INV-" . $user_id . "-" . time();

// Start a database transaction.
pg_query($conn, "BEGIN");

try {
    foreach ($data->cart as $item) {
        // Get current product details and lock the row for the transaction.
        $prod_sql = "SELECT name, quantity, wholesale_price FROM products WHERE id = $1 AND user_id = $2 FOR UPDATE";
        pg_prepare($conn, "get_product_for_sale", $prod_sql);
        $prod_result = pg_execute($conn, "get_product_for_sale", array($item->id, $user_id));
        $product = pg_fetch_assoc($prod_result);
        
        // Check for sufficient stock
        if (!$product || $product['quantity'] < $item->quantity) {
            throw new Exception("Insufficient stock for " . ($item->name ?? 'a product in your cart.'));
        }

        // 1. Record the sale in the main `sales` table
        $total_amount = $item->sale_price * $item->quantity;
        $total_investment = $product['wholesale_price'] * $item->quantity;
        $profit = $total_amount - $total_investment;
        $sale_sql = "INSERT INTO sales (user_id, product_id, quantity_sold, total_amount, total_investment, profit) VALUES ($1, $2, $3, $4, $5, $6)";
        pg_prepare($conn, "insert_sale", $sale_sql);
        pg_execute($conn, "insert_sale", array($user_id, $item->id, $item->quantity, $total_amount, $total_investment, $profit));
        
        // 2. Record the detailed line item in the `sales_transactions` table
        $trans_sql = "INSERT INTO sales_transactions (user_id, invoice_id, product_id, product_name, quantity_sold, sale_price_each, total_amount) VALUES ($1, $2, $3, $4, $5, $6, $7)";
        pg_prepare($conn, "insert_transaction", $trans_sql);
        pg_execute($conn, "insert_transaction", array($user_id, $invoice_id, $item->id, $item->name, $item->quantity, $item->sale_price, $total_amount));

        // 3. Update the product quantity in the `products` table
        $update_sql = "UPDATE products SET quantity = quantity - $1 WHERE id = $2 AND user_id = $3";
        pg_prepare($conn, "update_stock", $update_sql);
        pg_execute($conn, "update_stock", array($item->quantity, $item->id, $user_id));
    }
    
    // If all queries were successful, commit the changes.
    pg_query($conn, "COMMIT");
    echo json_encode(["message" => "Sale recorded successfully.", "invoice_id" => $invoice_id]);

} catch (Exception $e) {
    // If any query failed, roll back all changes.
    pg_query($conn, "ROLLBACK");
    http_response_code(400);
    echo json_encode(["message" => "Sale failed: " . $e->getMessage()]);
}

pg_close($conn);
?>