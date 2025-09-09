<?php
include 'db.php';

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$user_id = 0;
$data = null;

// Correctly determine user_id and data based on request type
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['user_id'])) { // FormData for file uploads (add_product)
        $user_id = intval($_POST['user_id']);
    } else { // JSON data for other actions (update_quantity, delete_product)
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
            // UPDATED: Join with product_types to get the type_name and order by it
            $sql = "SELECT p.id, p.user_id, p.type_id, pt.type_name, p.name, p.quantity, p.wholesale_price, p.sale_price, p.image_url 
                    FROM products p
                    JOIN product_types pt ON p.type_id = pt.id
                    WHERE p.user_id = ? AND p.is_active = 1 
                    ORDER BY pt.type_name ASC, p.name ASC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            echo json_encode($products);
            $stmt->close();
        }
        break;

    case 'add_product':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $type_id = intval($_POST['type_id']); // New field
            $name = $_POST['name'];
            $quantity = intval($_POST['quantity']);
            $wholesale_price = $_POST['wholesale_price'];
            $sale_price = $_POST['sale_price'];
            $image_url = null;

            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $target_dir = "uploads/";
                $image_name = time() . '_' . basename($_FILES["image"]["name"]);
                $target_file = $target_dir . $image_name;
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $image_url = $target_file;
                }
            }

            $conn->begin_transaction();
            try {
                // UPDATED: Add type_id to the INSERT statement
                $sql = "INSERT INTO products (user_id, type_id, name, quantity, wholesale_price, sale_price, image_url, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iisidds", $user_id, $type_id, $name, $quantity, $wholesale_price, $sale_price, $image_url);
                $stmt->execute();
                $product_id = $conn->insert_id;
                $stmt->close();

                $log_sql = "INSERT INTO stock_additions (user_id, product_id, quantity_added, wholesale_price_each) VALUES (?, ?, ?, ?)";
                $log_stmt = $conn->prepare($log_sql);
                $log_stmt->bind_param("iiid", $user_id, $product_id, $quantity, $wholesale_price);
                $log_stmt->execute();
                $log_stmt->close();

                $conn->commit();
                echo json_encode(["message" => "Product added successfully."]);
            } catch (Exception $e) {
                $conn->rollback();
                http_response_code(400);
                echo json_encode(["message" => "Failed to add product.", "error" => $e->getMessage()]);
            }
        }
        break;

    case 'update_quantity':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $data) {
            $product_id = intval($data->product_id);
            $quantity_to_add = intval($data->quantity_to_add);

            $conn->begin_transaction();
            try {
                $price_sql = "SELECT wholesale_price FROM products WHERE id = ? AND user_id = ?";
                $price_stmt = $conn->prepare($price_sql);
                $price_stmt->bind_param("ii", $product_id, $user_id);
                $price_stmt->execute();
                $product = $price_stmt->get_result()->fetch_assoc();
                if (!$product) throw new Exception("Product not found.");
                $wholesale_price = $product['wholesale_price'];
                $price_stmt->close();

                $update_sql = "UPDATE products SET quantity = quantity + ? WHERE id = ? AND user_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("iii", $quantity_to_add, $product_id, $user_id);
                $update_stmt->execute();
                $update_stmt->close();

                $log_sql = "INSERT INTO stock_additions (user_id, product_id, quantity_added, wholesale_price_each) VALUES (?, ?, ?, ?)";
                $log_stmt = $conn->prepare($log_sql);
                $log_stmt->bind_param("iiid", $user_id, $product_id, $quantity_to_add, $wholesale_price);
                $log_stmt->execute();
                $log_stmt->close();

                $conn->commit();
                echo json_encode(["message" => "Quantity updated."]);
            } catch (Exception $e) {
                $conn->rollback();
                http_response_code(400);
                echo json_encode(["message" => "Failed to update quantity.", "error" => $e->getMessage()]);
            }
        }
        break;

    case 'delete_product':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $data) {
            $product_id = intval($data->product_id);
            $user_id_from_data = intval($data->user_id);

            $stmt = $conn->prepare("UPDATE products SET is_active = 0 WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $product_id, $user_id_from_data);
            
            if ($stmt->execute()) {
                echo json_encode(["message" => "Product archived successfully."]);
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Failed to archive product."]);
            }
            $stmt->close();
        }
        break;
}

$conn->close();
?>