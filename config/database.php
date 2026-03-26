<?php
class Database {
    private $host = "127.0.0.1";
    private $db_name = "joy";
    private $username = "root";
    private $password = "admin123";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Database connection error."]);
            exit();
        }
        return $this->conn;
    }
}
?>
