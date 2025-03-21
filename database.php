<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'movie_db');

// Create database connection
function connectDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Function to sanitize input
function sanitize($data) {
    $conn = connectDB();
    return mysqli_real_escape_string($conn, trim($data));
}
?>