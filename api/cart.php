<?php
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$routeParts = explode('/', trim(substr($uri, strpos($uri, '/api/')), '/'));

$authHeader = '';
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $authHeader = trim($_SERVER['HTTP_AUTHORIZATION']);
} elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $authHeader = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
} elseif (function_exists('apache_request_headers')) {
    $requestHeaders = apache_request_headers();
    if (isset($requestHeaders['Authorization'])) {
        $authHeader = trim($requestHeaders['Authorization']);
    }
}

$user_id = null;
if (preg_match('/Bearer\s+(.*?)-(.*?)-(.*)/', $authHeader, $matches)) {
    $user_id = intval($matches[2]); 
}

if (!$user_id) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized. Invalid or missing token."]);
    exit();
}

$cart_item_id = isset($routeParts[2]) ? intval($routeParts[2]) : null;
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $query = "SELECT c.id as cart_item_id, p.id as product_id, p.name as product_name, p.price, c.quantity, (p.price * c.quantity) as total_item_price 
              FROM cart c 
              JOIN products p ON c.product_id = p.id 
              WHERE c.user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    
    $cart_arr = array();
    $subtotal = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['price'] = (float)$row['price'];
        $row['quantity'] = (int)$row['quantity'];
        $row['total_item_price'] = (float)$row['total_item_price'];
        $subtotal += $row['total_item_price'];
        array_push($cart_arr, $row);
    }
    http_response_code(200);
    echo json_encode(["status" => "success", "data" => $cart_arr, "cart_subtotal" => $subtotal]);

} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    if (!empty($data->product_id) && !empty($data->quantity)) {
        $check_query = "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$user_id, $data->product_id]);
        
        if ($check_stmt->rowCount() > 0) {
            $row = $check_stmt->fetch(PDO::FETCH_ASSOC);
            $new_quantity = $row['quantity'] + $data->quantity;
            $update_query = "UPDATE cart SET quantity = ? WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->execute([$new_quantity, $row['id']]);
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Cart updated", "cart_item_id" => $row['id']]);
        } else {
            $insert_query = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
            $insert_stmt = $db->prepare($insert_query);
            if ($insert_stmt->execute([$user_id, $data->product_id, $data->quantity])) {
                http_response_code(201);
                echo json_encode(["status" => "success", "message" => "Product added to cart", "cart_item_id" => $db->lastInsertId()]);
            } else {
                http_response_code(500); echo json_encode(["status" => "error", "message" => "Database error."]);
            }
        }
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Product ID and quantity required."]);
    }

} elseif ($method === 'PUT') {
    if ($cart_item_id) {
        $data = json_decode(file_get_contents("php://input"));
        if (!empty($data->quantity)) {
            $query = "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?";
            $stmt = $db->prepare($query);
            if ($stmt->execute([$data->quantity, $cart_item_id, $user_id]) && $stmt->rowCount() > 0) {
                http_response_code(200);
                echo json_encode(["status" => "success", "message" => "Cart item updated successfully"]);
            } else {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "Cart item not found or you don't have permission."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Quantity payload required."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Cart item ID required in URL."]);
    }

} elseif ($method === 'DELETE') {
    if ($cart_item_id) {
        $query = "DELETE FROM cart WHERE id = ? AND user_id = ?";
        $stmt = $db->prepare($query);
        if ($stmt->execute([$cart_item_id, $user_id]) && $stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Item removed from cart"]);
        } else {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Cart item not found or you don't have permission."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Cart item ID required in URL."]);
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
?>
