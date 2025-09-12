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
    // If the URL is present (on Render), parse it for PostgreSQL credentials
    $url_parts = parse_url($database_url);
    $host = $url_parts['host'];
    $port = $url_parts['port'];
    $user = $url_parts['user'];
    $pass = $url_parts['pass'];
    $dbname = ltrim($url_parts['path'], '/');
    
    // Create the connection string specifically for pg_connect
    $conn_str = "host=$host port=$port dbname=$dbname user=$user password=$pass";
    
    // Connect to the PostgreSQL database
    $conn = pg_connect($conn_str);

} else {
    // If running locally on XAMPP (MySQL), use your local credentials
    // Note: You'll need to update your local PHP files to use pg_ functions
    // or create a condition to use mysqli functions here.
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "inventory_db";
    $conn = new mysqli($servername, $username, $password, $dbname);
}

// Check the connection for errors
if (!$conn) {
    http_response_code(500);
    // Use pg_last_error() for PostgreSQL errors
    echo json_encode(["error" => "Database connection failed: " . pg_last_error()]);
    exit();
}
?>