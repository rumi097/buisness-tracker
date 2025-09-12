<?php
// Set CORS headers
header("Access--Control-Allow-Origin: *");
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
    // --- FINAL FIX: More robust regex to handle the 'pooler' hostname ---
    preg_match("/postgres:\/\/(.*):(.*)@(ep-.*-pooler)\.(.*)\/(.*)/", $database_url, $matches);
    
    $user = $matches[1];
    $pass = $matches[2];
    $host = $matches[3] . '.' . $matches[4]; // Reconstruct the full host
    $dbname = $matches[5];
    $port = 5432; // Default PostgreSQL port

    $conn_str = "host=$host port=$port dbname=$dbname user=$user password=$pass";
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