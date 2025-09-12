<?php
// Set a plain text header for clear output
header("Content-Type: text/plain; charset=utf-8");

echo "DATABASE CONNECTION TEST\n";
echo "========================\n\n";

// Include your standard database connection file
require 'db.php';

// Check if the connection object exists and is alive
if (isset($conn) && $conn->ping()) {
    echo "✅ SUCCESS: Connection to the Neon database was successful.\n\n";
    echo "Host information: " . $conn->host_info . "\n";
} else {
    echo "❌ FAILED: Could not connect to the database.\n\n";
    // Display the specific error from the last connection attempt
    echo "Error Details: " . mysqli_connect_error() . "\n";
}

// Close the connection if it exists
if (isset($conn)) {
    $conn->close();
}
?>