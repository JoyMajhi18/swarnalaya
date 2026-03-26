<?php
class Database {
    private $host = "127.0.0.1";
    private $db_name = "jewellery_db";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo json_encode(["status" => "error", "message" => "Database connection error."]);
            exit();
        }
        return $this->conn;
    }
}
?>
