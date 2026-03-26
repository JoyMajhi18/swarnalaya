<?php
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$routeParts = explode('/', trim(substr($uri, strpos($uri, '/api/')), '/'));
$id = isset($routeParts[2]) ? intval($routeParts[2]) : null;
$method = $_SERVER['REQUEST_METHOD'];

// Table: products
// Columns: id, name, category, price, description, image

if ($method === 'GET') {
    if ($id) {
        $query = "SELECT id, name, category, price, description, image_url FROM products WHERE id = ? LIMIT 0,1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $row['price'] = (float)$row['price'];
            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $row]);
        } else {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Product not found."]);
        }
    } else {
        $query = "SELECT id, name, category, price, description, image_url FROM products";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $products_arr = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['price'] = (float)$row['price'];
            array_push($products_arr, $row);
        }
        
        http_response_code(200);
        echo json_encode(["status" => "success", "data" => $products_arr]);
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
?>
