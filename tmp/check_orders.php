<?php
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();
$stmt = $db->query('DESCRIBE orders');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
?>
