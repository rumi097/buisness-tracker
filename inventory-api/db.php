<?php
// Set CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

session_start();

$database_url = getenv('DATABASE_URL');
$conn = null;

if ($database_url) {
    // Use the standard parse_url function
    $url_parts = parse_url($database_url);
    $host = $url_parts['host'];
    $port = $url_parts['port'];
    $user = $url_parts['user'];
    $pass = $url_parts['pass'];
    $dbname = ltrim($url_parts['path'], '/');
    
    // --- FINAL FIX: Add the required sslmode parameter ---
    $conn_str = "host=$host port=$port dbname=$dbname user=$user password=$pass sslmode=require";
    
    // Connect to PostgreSQL
    $conn = pg_connect($conn_str);

} else {
    // Fallback for local development
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "inventory_db";
    $conn = new mysqli($servername, $username, $password, $dbname);
}

if (!$conn) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed."]);
    exit();
}
?>