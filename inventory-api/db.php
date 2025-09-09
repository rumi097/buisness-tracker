<?php
// Set CORS headers to allow your React frontend to connect
header("Access-Control-Allow-Origin: *"); // For Render, a wildcard is often simplest initially.
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight requests from the browser
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Include Composer's autoloader if you use libraries like PHPMailer
// require 'vendor/autoload.php';

session_start();

// Get the connection string from the environment variable set by Render
$database_url = getenv('DATABASE_URL');

if ($database_url) {
    // If the URL is present (on Render), parse it to get the credentials
    $url_parts = parse_url($database_url);
    $servername = $url_parts['host'];
    $username = $url_parts['user'];
    $password = $url_parts['pass'];
    // The database name is the path, but without the leading '/'
    $dbname = ltrim($url_parts['path'], '/');
} else {
    // If the URL is not found (running locally on XAMPP), use your local credentials
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "inventory_db";
}

// Create the database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection for errors
if ($conn->connect_error) {
    // Stop the script and report the error
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed: " . $conn->connect_error]);
    exit();
}
?>