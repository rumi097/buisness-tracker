<?php
// This line must be at the very top of the file
include 'db.php';

// Set a plain text header for clear output
header("Content-Type: text/plain; charset=utf-8");

echo "DATABASE CONNECTION TEST\n";
echo "========================\n\n";

// Check if the connection object exists and is alive
if (isset($conn) && pg_ping($conn)) { // Use pg_ping for PostgreSQL
    echo "✅ SUCCESS: Connection to the Neon database was successful.\n\n";
    echo "Host information: " . pg_host($conn) . "\n";
} else {
    echo "❌ FAILED: Could not connect to the database.\n\n";
    // Display the specific error from the last connection attempt
    echo "Error Details: " . pg_last_error($conn) . "\n";
}

// Close the connection if it exists
if (isset($conn)) {
    pg_close($conn);
}
?>