<?php
// Get the live frontend URL from Render's automatic environment variable
$allowed_origin = getenv('RENDER_EXTERNAL_URL');

// For local development, if the above is not set, fallback to localhost
if (!$allowed_origin) {
    $allowed_origin = 'http://localhost:3000';
}

// Set CORS headers
header("Access-Control-Allow-Origin: " . $allowed_origin);
// ... rest of your db.php file ...
?>