<?php
// config/database.php
// MySQL 8.0 Connection with password: 252526

class Database {
    private $host = "localhost";
    private $username = "root";
    private $password = "252526";
    private $database = "pharma_db";
    private $port = "3306";
    public $connection;

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        $this->connection = new mysqli(
            $this->host,
            $this->username,
            $this->password,
            $this->database,
            $this->port
        );

        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }

        // Set charset to UTF-8
        $this->connection->set_charset("utf8mb4");
        
        return $this->connection;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }

    public function escapeString($string) {
        return $this->connection->real_escape_string($string);
    }

    public function __destruct() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}

// Create global database instance
$database = new Database();
$db = $database->getConnection();
?>