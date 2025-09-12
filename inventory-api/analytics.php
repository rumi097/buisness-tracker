<?php
include 'db.php'; // Your PostgreSQL database connection file

// --- Input Validation ---
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$period = isset($_GET['period']) ? $_GET['period'] : 'daily'; // daily, weekly, monthly

if ($user_id <= 0) {
    http_response_code(403);
    echo json_encode(['error' => 'User not specified.']);
    exit();
}

// --- Date Range Calculation ---
// Calculate the date range in PHP. This is more portable than using database-specific functions.
$start_date = new DateTime();
$end_date = new DateTime();

switch ($period) {
    case 'weekly':
        // Set the start to the Monday of the current week.
        $start_date->modify('monday this week');
        break;
    case 'monthly':
        // Set the start to the first day of the current month.
        $start_date->modify('first day of this month');
        break;
}

// Format dates for the SQL query.
$start_date_str = $start_date->format('Y-m-d 00:00:00');
$end_date_str = $end_date->format('Y-m-d 23:59:59');


// --- Database Query (Optimized for PostgreSQL) ---
// We use numbered placeholders ($1, $2, $3) for PostgreSQL parameters.
$sql = "SELECT 
            product_name, 
            SUM(quantity_sold) as total_quantity 
        FROM 
            sales_transactions 
        WHERE 
            user_id = $1 AND sale_date BETWEEN $2 AND $3
        GROUP BY 
            product_name 
        ORDER BY 
            total_quantity DESC";

try {
    // Prepare and execute the query using pgsql functions
    $stmt = pg_prepare($conn, "analytics_query", $sql);
    $result = pg_execute($conn, "analytics_query", array($user_id, $start_date_str, $end_date_str));
    
    if (!$result) {
        throw new Exception(pg_last_error($conn));
    }

    $sales_data = pg_fetch_all($result);
    // If there are no results, pg_fetch_all returns false. Convert to an empty array.
    if ($sales_data === false) {
        $sales_data = [];
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
    exit();
}


// --- Data Processing (in PHP) ---
// This logic remains the same.

// The chart data is the full result set.
$chart_data = $sales_data;

// The highest selling product is the first item.
$highest_product = $sales_data[0] ?? null;

// The lowest selling product is the last item.
$lowest_product = end($sales_data) ?: null;


// --- Final Output ---
// Send the final data as a JSON response.
echo json_encode([
    'chartData' => $chart_data,
    'highestProduct' => $highest_product,
    'lowestProduct' => $lowest_product
]);

pg_close($conn);
?>