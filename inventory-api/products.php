<?php
include 'db.php'; // Your PostgreSQL connection

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$user_id = 0;
$data = null;

// Correctly determine user_id and data based on request type
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['user_id'])) { // FormData for file uploads (add_product)
        $user_id = intval($_POST['user_id']);
    } else { // JSON data for other actions
        $data = json_decode(file_get_contents("php://input"));
        $user_id = isset($data->user_id) ? intval($data->user_id) : 0;
    }
} else { // GET requests
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
}

// --- Main Logic Switch ---
switch ($action) {
    case 'get_products':
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && $user_id > 0) {
            $sql = "SELECT p.id, p.user_id, p.type_id, pt.type_name, p.name, p.quantity, p.wholesale_price, p.sale_price, p.image_url 
                    FROM products p
                    JOIN product_types pt ON p.type_id = pt.id
                    WHERE p.user_id = $1 AND p.is_active = 1 
                    ORDER BY pt.type_name ASC, p.name ASC";
            
            pg_prepare($conn, "get_products_query", $sql);
            $result = pg_execute($conn, "get_products_query", array($user_id));
            $products = pg_fetch_all($result) ?: []; // Return empty array if no results
            echo json_encode($products);
        }
        break;

    case 'add_product':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $type_id = intval($_POST['type_id']);
            $name = $_POST['name'];
            $quantity = intval($_POST['quantity']);
            $wholesale_price = $_POST['wholesale_price'];
            $sale_price = $_POST['sale_price'];
            $image_url = null;

            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                // ... (your file upload logic remains the same) ...
            }

            pg_query($conn, "BEGIN"); // Start transaction
            try {
                // Use RETURNING id to get the new product's ID
                $sql = "INSERT INTO products (user_id, type_id, name, quantity, wholesale_price, sale_price, image_url, is_active) VALUES ($1, $2, $3, $4, $5, $6, $7, 1) RETURNING id";
                pg_prepare($conn, "add_product_query", $sql);
                $result = pg_execute($conn, "add_product_query", array($user_id, $type_id, $name, $quantity, $wholesale_price, $sale_price, $image_url));
                $product_id = pg_fetch_assoc($result)['id'];

                $log_sql = "INSERT INTO stock_additions (user_id, product_id, quantity_added, wholesale_price_each) VALUES ($1, $2, $3, $4)";
                pg_prepare($conn, "log_stock_query", $log_sql);
                pg_execute($conn, "log_stock_query", array($user_id, $product_id, $quantity, $wholesale_price));
                
                pg_query($conn, "COMMIT"); // Commit transaction
                echo json_encode(["message" => "Product added successfully."]);
            } catch (Exception $e) {
                pg_query($conn, "ROLLBACK"); // Rollback on error
                http_response_code(400);
                echo json_encode(["message" => "Failed to add product.", "error" => $e->getMessage()]);
            }
        }
        break;

    case 'update_quantity':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $data) {
            $product_id = intval($data->product_id);
            $quantity_to_add = intval($data->quantity_to_add);

            pg_query($conn, "BEGIN");
            try {
                $price_sql = "SELECT wholesale_price FROM products WHERE id = $1 AND user_id = $2";
                pg_prepare($conn, "get_price_query", $price_sql);
                $price_result = pg_execute($conn, "get_price_query", array($product_id, $user_id));
                $product = pg_fetch_assoc($price_result);
                if (!$product) throw new Exception("Product not found.");
                $wholesale_price = $product['wholesale_price'];

                $update_sql = "UPDATE products SET quantity = quantity + $1 WHERE id = $2 AND user_id = $3";
                pg_prepare($conn, "update_qty_query", $update_sql);
                pg_execute($conn, "update_qty_query", array($quantity_to_add, $product_id, $user_id));

                $log_sql = "INSERT INTO stock_additions (user_id, product_id, quantity_added, wholesale_price_each) VALUES ($1, $2, $3, $4)";
                pg_prepare($conn, "log_update_query", $log_sql);
                pg_execute($conn, "log_update_query", array($user_id, $product_id, $quantity_to_add, $wholesale_price));

                pg_query($conn, "COMMIT");
                echo json_encode(["message" => "Quantity updated."]);
            } catch (Exception $e) {
                pg_query($conn, "ROLLBACK");
                http_response_code(400);
                echo json_encode(["message" => "Failed to update quantity.", "error" => $e->getMessage()]);
            }
        }
        break;

    case 'delete_product':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $data) {
            $product_id = intval($data->product_id);

            $sql = "UPDATE products SET is_active = 0 WHERE id = $1 AND user_id = $2";
            pg_prepare($conn, "delete_product_query", $sql);
            $result = pg_execute($conn, "delete_product_query", array($product_id, $user_id));
            
            if ($result) {
                echo json_encode(["message" => "Product archived successfully."]);
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Failed to archive product."]);
            }
        }
        break;
}

pg_close($conn);
?>