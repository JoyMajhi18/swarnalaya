<?php
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $database = new Database();
    $db_status = "disconnected";
    
    try {
        $db = $database->getConnection();
        if ($db) {
            $db_status = "connected";
        }
    } catch (Exception $e) {
        $db_status = "error";
    }

    $response = [
        "status" => $db_status === "error" ? "error" : "success",
        "message" => "API health status.",
        "services" => [
            "database" => $db_status
        ],
        "server" => [
            "php_version" => phpversion(),
            "memory_usage" => round(memory_get_usage() / 1024 / 1024, 2) . " MB"
        ],
        "timestamp" => date("Y-m-d H:i:s")
    ];

    if ($db_status === "error") {
        http_response_code(500);
    } else {
        http_response_code(200);
    }

    echo json_encode($response);
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}
?>
