<?php
require_once __DIR__ . '/../config/database.php';

// Safe route extraction
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$routeParts = explode('/', trim(substr($uri, strpos($uri, '/api/')), '/'));

// Check authorization header safely
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
$role = null;
if (preg_match('/Bearer\s+(.*?)-(.*?)-(.*)/', $authHeader, $matches)) {
    $user_id = intval($matches[2]); 
    $role = $matches[3];
}

if (!$user_id || $role !== 'admin') {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Forbidden. Admin access required."]);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failed."]);
    exit();
}

$resource = isset($routeParts[2]) ? $routeParts[2] : null; 
$id = isset($routeParts[3]) ? intval($routeParts[3]) : null;
$action = isset($routeParts[4]) ? $routeParts[4] : null;
$method = $_SERVER['REQUEST_METHOD'];

if ($resource === 'dashboard') {
    if ($method === 'GET') {
        $stats = [];
        
        $stmt = $db->query("SELECT COUNT(*) as total FROM products");
        $stats['total_products'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $db->query("SELECT COUNT(*) as total FROM users");
        $stats['total_users'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $db->query("SELECT COUNT(*) as total_orders, SUM(total_amount) as total_revenue FROM orders");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_orders'] = (int)$row['total_orders'];
        $stats['total_revenue'] = (float)$row['total_revenue'];
        
        $stmt = $db->query("SELECT o.order_id, o.total_amount, o.order_date, o.payment_status FROM orders o ORDER BY o.order_date DESC LIMIT 5");
        $stats['recent_orders'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode(["status" => "success", "data" => $stats]);
    } else {
        http_response_code(405); echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    }
} elseif ($resource === 'products') {
    // ... products logic remains same ...
} elseif ($resource === 'orders') {
    if ($method === 'GET' && !$id) {
        $query = "SELECT o.order_id, u.id as user_id, u.name as customer_name, o.order_date as date, o.payment_status, o.total_amount, o.order_status 
                  FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.order_date DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode(["status" => "success", "data" => $orders]);

    } elseif ($method === 'GET' && $id) {
        $query = "SELECT o.order_id, u.name as customer_name, u.email as customer_email, u.phone as customer_phone, o.payment_status, o.total_amount, o.order_status, pr.name as product_name, o.quantity 
                  FROM orders o 
                  JOIN users u ON o.user_id = u.id 
                  JOIN products pr ON o.product_id = pr.id
                  WHERE o.order_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $order]);
        } else {
             http_response_code(404); echo json_encode(["status" => "error", "message" => "Order not found"]);
        }

    } elseif ($method === 'PUT' && $id && $action === 'payment_status') {
        $data = json_decode(file_get_contents("php://input"));
        if (!empty($data->payment_status)) {
            $query = "UPDATE orders SET payment_status = ? WHERE order_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([htmlspecialchars(strip_tags($data->payment_status)), $id]);
            
            // Sync with payments table if it exists
            $sync_query = "UPDATE payments SET payment_status = ? WHERE order_id = ?";
            $sync_stmt = $db->prepare($sync_query);
            $sync_stmt->execute([htmlspecialchars(strip_tags($data->payment_status)), $id]);
            
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Payment status updated successfully"]);
        } else {
            http_response_code(400); echo json_encode(["status" => "error", "message" => "payment_status payload required"]);
        }

    } elseif ($method === 'PUT' && $id && $action === 'order_status') {
        $data = json_decode(file_get_contents("php://input"));
        if (!empty($data->order_status)) {
            $query = "UPDATE orders SET order_status = ? WHERE order_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([htmlspecialchars(strip_tags($data->order_status)), $id]);
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Order status updated successfully"]);
        } else {
            http_response_code(400); echo json_encode(["status" => "error", "message" => "order_status payload required"]);
        }

    } else {
          http_response_code(405); echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    }
} else {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Admin resource not found."]);
}

?>
