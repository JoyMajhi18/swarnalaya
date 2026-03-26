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

$action_or_id = isset($routeParts[2]) ? $routeParts[2] : null;
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST' && $action_or_id === 'checkout') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->address) && !empty($data->payment_method)) {
        $cart_query = "SELECT c.product_id, c.quantity, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?";
        $cart_stmt = $db->prepare($cart_query);
        $cart_stmt->execute([$user_id]);
        
        if ($cart_stmt->rowCount() > 0) {
            $total_amount = 0;
            $items = array();
            while ($row = $cart_stmt->fetch(PDO::FETCH_ASSOC)) {
                $items[] = $row;
                $total_amount += ($row['price'] * $row['quantity']);
            }
            
            try {
                $db->beginTransaction();
                
                // insert order
                $order_query = "INSERT INTO orders (user_id, total_amount, address) VALUES (?, ?, ?)";
                $order_stmt = $db->prepare($order_query);
                $order_stmt->execute([$user_id, $total_amount, htmlspecialchars(strip_tags($data->address))]);
                $order_id = $db->lastInsertId();
                
                // insert items
                foreach ($items as $item) {
                    $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
                    $item_stmt = $db->prepare($item_query);
                    $item_stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
                }
                
                // insert payment
                $payment_query = "INSERT INTO payments (order_id, amount, payment_method, payment_status) VALUES (?, ?, ?, 'Pending')";
                $payment_stmt = $db->prepare($payment_query);
                $payment_stmt->execute([$order_id, $total_amount, htmlspecialchars(strip_tags($data->payment_method))]);
                
                // clear cart
                $clear_query = "DELETE FROM cart WHERE user_id = ?";
                $clear_stmt = $db->prepare($clear_query);
                $clear_stmt->execute([$user_id]);
                
                $db->commit();
                
                http_response_code(201);
                echo json_encode(["status" => "success", "message" => "Order placed successfully", "order_id" => (int)$order_id]);
            } catch (Exception $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "Failed to place order."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Cart is empty."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Address and payment_method are required."]);
    }

} elseif ($method === 'GET') {
    if ($action_or_id && is_numeric($action_or_id)) {
        $order_id = intval($action_or_id);
        $order_query = "SELECT o.id as order_id, o.address, o.total_amount, o.created_at as order_date, p.payment_method, p.payment_status 
                        FROM orders o LEFT JOIN payments p ON o.id = p.order_id 
                        WHERE o.id = ? AND o.user_id = ?";
        $order_stmt = $db->prepare($order_query);
        $order_stmt->execute([$order_id, $user_id]);
        
        if ($order_stmt->rowCount() > 0) {
            $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
            $order['total_amount'] = (float)$order['total_amount'];
            
            $items_query = "SELECT oi.product_id, p.name as product_name, oi.quantity, oi.price FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?";
            $items_stmt = $db->prepare($items_query);
            $items_stmt->execute([$order_id]);
            
            $order['items'] = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $order]);
        } else {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Order not found."]);
        }
    } else {
        $order_query = "SELECT o.id as order_id, o.total_amount, o.created_at as order_date, p.payment_status 
                        FROM orders o LEFT JOIN payments p ON o.id = p.order_id 
                        WHERE o.user_id = ? ORDER BY o.created_at DESC";
        $order_stmt = $db->prepare($order_query);
        $order_stmt->execute([$user_id]);
        
        $orders_arr = array();
        while ($row = $order_stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['total_amount'] = (float)$row['total_amount'];
            array_push($orders_arr, $row);
        }
        
        http_response_code(200);
        echo json_encode(["status" => "success", "data" => $orders_arr]);
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
?>
