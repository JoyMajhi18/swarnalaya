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
        
        $stmt = $db->query("SELECT o.id, o.total_amount, o.order_date, p.payment_status FROM orders o LEFT JOIN payments p ON o.id = p.order_id ORDER BY o.order_date DESC LIMIT 5");
        $stats['recent_orders'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode(["status" => "success", "data" => $stats]);
    } else {
        http_response_code(405); echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    }
} elseif ($resource === 'products') {
    if ($method === 'POST') {
        $name = isset($_POST['name']) ? $_POST['name'] : '';
        $category = isset($_POST['category']) ? $_POST['category'] : '';
        $description = isset($_POST['description']) ? $_POST['description'] : '';
        $price = isset($_POST['price']) ? $_POST['price'] : 0;

        $image = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $image = '/uploads/' . basename($_FILES['image']['name']);
        }

        if (!empty($name) && !empty($price)) {
            $query = "INSERT INTO products (name, category, price, description, image) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([htmlspecialchars(strip_tags($name)), htmlspecialchars(strip_tags($category)), $price, htmlspecialchars(strip_tags($description)), $image]);
            http_response_code(201);
            echo json_encode(["status" => "success", "message" => "Product added successfully", "product_id" => $db->lastInsertId()]);
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Name and price required."]);
        }

    } elseif ($method === 'PUT' && $id) {
        $data = json_decode(file_get_contents("php://input"), true);
        if ($data) {
            $updates = array();
            $params = array();
            foreach ($data as $key => $value) {
                if (in_array($key, ['name', 'category', 'description', 'price', 'image'])) {
                    $updates[] = "{$key} = ?";
                    $params[] = $value;
                }
            }
            if (!empty($updates)) {
                $params[] = $id;
                $query = "UPDATE products SET " . implode(", ", $updates) . " WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute($params);
                http_response_code(200);
                echo json_encode(["status" => "success", "message" => "Product updated successfully"]);
            } else {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "No valid fields to update."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Invalid JSON payload."]);
        }
    } elseif ($method === 'DELETE' && $id) {
        $query = "DELETE FROM products WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "Product deleted successfully"]);
    } else {
         http_response_code(405); echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    }

} elseif ($resource === 'orders') {
    if ($method === 'GET' && !$id) {
        $query = "SELECT o.id as order_id, u.id as user_id, u.name as customer_name, o.order_date as date, p.payment_status, o.total_amount 
                  FROM orders o JOIN users u ON o.user_id = u.id LEFT JOIN payments p ON o.id = p.order_id ORDER BY o.order_date DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        http_response_code(200);
        echo json_encode(["status" => "success", "data" => $orders]);

    } elseif ($method === 'GET' && $id) {
        $query = "SELECT o.id as order_id, u.name as customer_name, u.email as customer_email, u.phone as customer_phone, o.address, p.payment_status, p.payment_method, o.total_amount 
                  FROM orders o JOIN users u ON o.user_id = u.id LEFT JOIN payments p ON o.id = p.order_id WHERE o.id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            $items_query = "SELECT oi.product_id, pr.name as product_name, oi.quantity, oi.price FROM order_items oi JOIN products pr ON oi.product_id = pr.id WHERE oi.order_id = ?";
            $items_stmt = $db->prepare($items_query);
            $items_stmt->execute([$id]);
            $order['items'] = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

            http_response_code(200);
            echo json_encode(["status" => "success", "data" => $order]);
        } else {
             http_response_code(404); echo json_encode(["status" => "error", "message" => "Order not found"]);
        }

    } elseif ($method === 'PUT' && $id && $action === 'payment_status') {
        $data = json_decode(file_get_contents("php://input"));
        if (!empty($data->payment_status)) {
            $query = "UPDATE payments SET payment_status = ? WHERE order_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([htmlspecialchars(strip_tags($data->payment_status)), $id]);
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "Payment status updated successfully"]);
        } else {
            http_response_code(400); echo json_encode(["status" => "error", "message" => "payment_status payload required"]);
        }

    } else {
         http_response_code(405); echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    }
} else {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Admin resource not found."]);
}
?>
