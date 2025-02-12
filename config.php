<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load environment variables from different sources
$database_host = ${{ secrets.DB_HOST }};
$database_name = ${{ secrets.DB_NAME }};
$database_user = ${{ secrets.DB_USER }};
$database_password = ${{ secrets.DB_PASSWORD }};

// Ensure credentials are properly loaded
if (!$database_host || !$database_name || !$database_user || !$database_password) {
    die("Error: Database credentials are not set.");
}

// Database connection
try {
    $pdo = new PDO("mysql:host=$database_host;dbname=$database_name;charset=utf8", $database_user, $database_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
