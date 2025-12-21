<?php

class Database {
    private $db;
    private static ?Database $instance = null;
    private function __construct(){
        $this->db = new PDO(
            "mysql:host=localhost;dbname=blog_db;charset=utf8mb4",
            "root",
            ""
        );
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->db;
    }
}
