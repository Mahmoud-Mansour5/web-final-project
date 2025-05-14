<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection configuration
$host = 'localhost';
$dbname = 'galaxy_x';
$username = 'root';
$password = 'Hoda552005';

try {
    // Establish database connection
    $conn = @mysqli_connect($host, $username, $password, $dbname);
    
    // Check if connection was successful
    if (!$conn) {
        throw new Exception("Connection failed: " . mysqli_connect_error() . 
            "\nHost: " . $host .
            "\nDatabase: " . $dbname .
            "\nUsername: " . $username);
    }
    
    // Set UTF-8 charset for database connection
    if (!mysqli_set_charset($conn, "utf8mb4")) {
        throw new Exception("Error setting charset: " . mysqli_error($conn));
    }
    
} catch (Exception $e) {
    // Log error and display it
    error_log("Database Connection Error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    exit();
}
?>