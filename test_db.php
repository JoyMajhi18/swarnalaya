<?php
$host = "127.0.0.1";
$db_name = "jewellery_db";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=" . $host . ";dbname=" . $db_name, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully\n";
} catch(PDOException $exception) {
    echo "Connection failed: " . $exception->getMessage() . "\n";
}
?>
