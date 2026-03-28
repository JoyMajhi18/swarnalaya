<?php
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    echo "Dropping old tables...\n";
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $db->exec("DROP TABLE IF EXISTS order_items;");
    $db->exec("DROP TABLE IF EXISTS payments;");
    $db->exec("DROP TABLE IF EXISTS orders;");
    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "Applying new schema from database/schema.sql...\n";
    $schema = file_get_contents(__DIR__ . '/../database/schema.sql');
    
    // Split by semicolon to execute one by one (basic approach)
    $queries = explode(';', $schema);
    foreach ($queries as $query) {
        $q = trim($query);
        if (!empty($q)) {
            $db->exec($q);
        }
    }

    echo "Database sync complete!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
