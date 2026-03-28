<?php
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->query("DESCRIBE orders");
    echo "Orders table structure:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['Field']} | {$row['Type']} | {$row['Null']} | {$row['Key']} | {$row['Default']} | {$row['Extra']}\n";
    }
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = 'joy'");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "\nTotal tables in 'joy' database: $count\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
