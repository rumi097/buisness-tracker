<?php
// Get the live frontend URL from an environment variable, fallback to localhost for development
$allowed_origin = getenv('FRONTEND_URL') ?: 'http://localhost:3000';

// Set CORS headers
header("Access-Control-Allow-Origin: " . $allowed_origin);
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Include Composer's autoloader for PHPMailer
require 'vendor/autoload.php';

session_start(); // Start session for OTP management

// --- DATABASE CREDENTIALS ---
// Get credentials from environment variables provided by Render
$servername = getenv('DB_HOST');
$username = getenv('DB_USER');
$password = getenv('DB_PASS');
$dbname = getenv('DB_NAME');

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    // In production, avoid exposing detailed error info
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed."]);
    exit();
}
?>