<?php
// Load environment variables from different sources
$database_host = getenv('DB_HOST') ?: $_SERVER['DB_HOST'] ?? null;
$database_name = getenv('DB_NAME') ?: $_SERVER['DB_NAME'] ?? null;
$database_user = getenv('DB_USER') ?: $_SERVER['DB_USER'] ?? null;
$database_password = getenv('DB_PASSWORD') ?: $_SERVER['DB_PASSWORD'] ?? null;

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
