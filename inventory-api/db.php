<?php
// Set CORS headers to allow your React frontend to connect
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight requests from the browser
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

session_start();

// Get the connection string from the environment variable set by Render
$database_url = getenv('DATABASE_URL');
$conn = null; // Initialize connection variable

if ($database_url) {
    // --- FIX: Use a regular expression to reliably parse the Neon URL ---
    preg_match("/postgres:\/\/(.*):(.*)@(.*):(.*)\/(.*)/", $database_url, $matches);
    $user = $matches[1];
    $pass = $matches[2];
    $host = $matches[3];
    $port = $matches[4];
    $dbname = $matches[5];
    
    // Create the connection string for pg_connect
    $conn_str = "host=$host port=$port dbname=$dbname user=$user password=$pass";
    
    // Connect to the PostgreSQL database
    $conn = pg_connect($conn_str);

} else {
    // Fallback for local development
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "inventory_db";
    $conn = new mysqli($servername, $username, $password, $dbname);
}

// Check the connection for errors
if (!$conn) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed."]);
    exit();
}
?>