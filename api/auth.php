<?php
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$routeParts = explode('/', trim(substr($uri, strpos($uri, '/api/')), '/'));
$action = isset($routeParts[2]) ? $routeParts[2] : null;
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if ($action === 'register') {
    if (!empty($data->name) && !empty($data->email) && !empty($data->password)) {
        // Table: users
        $query = "INSERT INTO users (name, email, password, phone, address) VALUES (:name, :email, :password, :phone, :address)";
        $stmt = $db->prepare($query);
        
        $name = htmlspecialchars(strip_tags($data->name));
        $email = htmlspecialchars(strip_tags($data->email));
        $password = password_hash($data->password, PASSWORD_BCRYPT);
        $phone = isset($data->phone) ? htmlspecialchars(strip_tags($data->phone)) : null;
        $address = isset($data->address) ? htmlspecialchars(strip_tags($data->address)) : null;

        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password", $password);
        $stmt->bindParam(":phone", $phone);
        $stmt->bindParam(":address", $address);

        try {
            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(["status" => "success", "message" => "User registered successfully", "user_id" => $db->lastInsertId()]);
            } else {
                http_response_code(503);
                echo json_encode(["status" => "error", "message" => "Unable to register user."]);
            }
        } catch (PDOException $e) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Email may already exist."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Incomplete data. Required: name, email, password."]);
    }

} else if ($action === 'login') {
    if (!empty($data->email) && !empty($data->password)) {
        // Table: users
        $query = "SELECT id, name, password FROM users WHERE email = ? LIMIT 0,1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $data->email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $id = $row['id'];
            $password_hash = $row['password'];

            if (password_verify($data->password, $password_hash)) {
                $token = bin2hex(random_bytes(16));
                http_response_code(200);
                echo json_encode([
                    "status" => "success",
                    "message" => "Successful login.",
                    "token" => $token . "-" . $id . "-user", 
                    "role" => "user",
                    "user_id" => $id
                ]);
            } else {
                http_response_code(401);
                echo json_encode(["status" => "error", "message" => "Login failed. Incorrect password."]);
            }
        } else {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Login failed. User not found."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Incomplete data. Required: email, password."]);
    }

} else if ($action === 'admin_login') {
    if (!empty($data->username) && !empty($data->password)) {
        // Table: admin
        $query = "SELECT id, username, password FROM admin WHERE username = ? LIMIT 0,1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $data->username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $id = $row['id'];
            $db_password = $row['password'];

            // Since inserted via straight SQL, it's plaintext
            if ($data->password === $db_password) {
                $token = bin2hex(random_bytes(16));
                http_response_code(200);
                echo json_encode([
                    "status" => "success",
                    "message" => "Successful admin login.",
                    "token" => $token . "-" . $id . "-admin",
                    "role" => "admin",
                    "admin_id" => $id
                ]);
            } else {
                http_response_code(401);
                echo json_encode(["status" => "error", "message" => "Login failed. Incorrect password."]);
            }
        } else {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Login failed. Admin not found."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Incomplete data. Required: username, password."]);
    }
} else if ($action === 'logout') {
    http_response_code(200);
    echo json_encode(["status" => "success", "message" => "Logged out successfully."]);
} else {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Auth action not found."]);
}
?>
