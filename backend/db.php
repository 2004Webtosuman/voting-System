<?php
class Database {
    private $host = "localhost";
    private $db_name = "smart_parking";
    private $username = "root";
    private $password = ""; // TODO: replace with secure password or use env vars

    public $conn;

    /**
     * Get the database connection using PDO.
     * @return PDO|null
     */
    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            http_response_code(500);
            echo json_encode(["error" => "Database connection error: " . $exception->getMessage()]);
        }
        return $this->conn;
    }
}
?>