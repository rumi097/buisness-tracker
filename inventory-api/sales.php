<?php
include 'db.php';

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

// Start a database transaction. All queries must succeed, or none will be saved.
$conn->begin_transaction();

try {
    foreach ($data->cart as $item) {
        // Get the current product details from the database to ensure stock and get wholesale price
        $prod_sql = "SELECT name, quantity, wholesale_price FROM products WHERE id = ? AND user_id = ? FOR UPDATE";
        $prod_stmt = $conn->prepare($prod_sql);
        $prod_stmt->bind_param("ii", $item->id, $user_id);
        $prod_stmt->execute();
        $product = $prod_stmt->get_result()->fetch_assoc();
        $prod_stmt->close();
        
        // Check for sufficient stock
        if (!$product || $product['quantity'] < $item->quantity) {
            throw new Exception("Insufficient stock for " . ($item->name ?? 'a product in your cart.'));
        }

        // 1. Record the sale in the main `sales` table (for aggregated reports)
        $total_amount = $item->sale_price * $item->quantity;
        $total_investment = $product['wholesale_price'] * $item->quantity;
        $profit = $total_amount - $total_investment;
        $sale_sql = "INSERT INTO sales (user_id, product_id, quantity_sold, total_amount, total_investment, profit) VALUES (?, ?, ?, ?, ?, ?)";
        $sale_stmt = $conn->prepare($sale_sql);
        $sale_stmt->bind_param("iiiddd", $user_id, $item->id, $item->quantity, $total_amount, $total_investment, $profit);
        $sale_stmt->execute();
        $sale_stmt->close();
        
        // 2. Record the detailed line item in the new `sales_transactions` table
        $trans_sql = "INSERT INTO sales_transactions (user_id, invoice_id, product_id, product_name, quantity_sold, sale_price_each, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $trans_stmt = $conn->prepare($trans_sql);
        $trans_stmt->bind_param("isisidd", $user_id, $invoice_id, $item->id, $item->name, $item->quantity, $item->sale_price, $total_amount);
        $trans_stmt->execute();
        $trans_stmt->close();

        // 3. Update the product quantity in the `products` table
        $update_sql = "UPDATE products SET quantity = quantity - ? WHERE id = ? AND user_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("iii", $item->quantity, $item->id, $user_id);
        $update_stmt->execute();
        $update_stmt->close();
    }
    
    // If all queries were successful, commit the changes to the database
    $conn->commit();
    echo json_encode(["message" => "Sale recorded successfully.", "invoice_id" => $invoice_id]);

} catch (Exception $e) {
    // If any query failed, roll back all changes
    $conn->rollback();
    http_response_code(400); // Use 400 for client-side errors like insufficient stock
    echo json_encode(["message" => "Sale failed: " . $e->getMessage()]);
}

$conn->close();
?>