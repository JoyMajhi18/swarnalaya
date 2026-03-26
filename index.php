<?php
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
} else {
    header("Access-Control-Allow-Origin: *");
}
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS, GET, POST, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$apiKeyPos = strpos($uri, '/api/');
if ($apiKeyPos === false) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Not Found. API must be accessed via /api/."]);
    exit();
}

$routePath = substr($uri, $apiKeyPos); 
$routeParts = explode('/', trim($routePath, '/')); 

$module = isset($routeParts[1]) ? $routeParts[1] : null;

$moduleFile = __DIR__ . "/api/" . $module . ".php";

if ($module && file_exists($moduleFile)) {
    require_once $moduleFile;
} else {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Module '$module' not found"]);
}
