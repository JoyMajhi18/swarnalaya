<?php
require_once __DIR__ . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Create users table
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        phone VARCHAR(20),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );");
    
    // Create admin table
    $db->exec("CREATE TABLE IF NOT EXISTS admin (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );");

    // Insert admin if not exists
    $stmt = $db->query("SELECT * FROM admin WHERE username = 'admin'");
    if ($stmt->rowCount() == 0) {
        $db->exec("INSERT INTO admin (username, password) VALUES ('admin', 'admin123')");
        echo "Admin created (admin / admin123)\n";
    }

    // Insert test user if not exists
    $stmt = $db->query("SELECT * FROM users WHERE email = 'user@test.com'");
    if ($stmt->rowCount() == 0) {
        $hash = password_hash('user123', PASSWORD_BCRYPT);
        $db->exec("INSERT INTO users (name, email, password) VALUES ('Test User', 'user@test.com', '$hash')");
        echo "User created (user@test.com / user123)\n";
    }

    echo "Initialization complete!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
