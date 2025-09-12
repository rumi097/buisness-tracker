<?php
// Get the live frontend URL from an environment variable set in Render
$frontend_url = getenv('FRONTEND_URL');

// If the variable isn't set (e.g., local testing), fallback to a default
if (!$frontend_url) {
    // This allows your local React dev server to connect
    $frontend_url = 'http://localhost:3000';
}

// Set CORS headers to allow your React frontend to connect
header("Access-Control-Allow-Origin: " . $frontend_url);
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight requests from the browser
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

session_start();

// Get individual database credentials from environment variables set in Render
$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$dbname = getenv('DB_NAME');

$conn = null;

// Proceed only if all environment variables are set
if ($host && $port && $user && $pass && $dbname) {
    // Create the connection string for pg_connect, including the required SSL mode
    $conn_str = "host=$host port=$port dbname=$dbname user=$user password=$pass sslmode=require";
    
    // Connect to the PostgreSQL database
    $conn = pg_connect($conn_str);
}

// Check the connection for errors
if (!$conn) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed. Please check environment variables."]);
    exit();
}
?>