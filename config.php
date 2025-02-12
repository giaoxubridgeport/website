<?php
// config.php

$host = 'localhost';
$dbName = 'hoangsit_mysql_giaoxu';
$username = 'hoangsit_giaoxu_sql_user';
$password = 'Bridgeport2020!'; // Replace with real password

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $username, $password);
    // Set PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}
?>
