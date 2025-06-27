<?php
require_once __DIR__ . '/../includes/functions.php';
/**
 * Database Configuration
 * 
 * This file contains the database connection settings for the Barangay Biga MIS
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'barangay_biga');

// Create database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8mb4
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    // Log error and display user-friendly message
    error_log("Database Connection Error: " . $e->getMessage());
    die("Sorry, there was a problem connecting to the database. Please try again later.");
}

// Function to close database connection
function close_connection() {
    global $conn;
    if ($conn) {
        $conn->close();
    }
}

// Register shutdown function to ensure connection is closed
register_shutdown_function('close_connection');
?> 