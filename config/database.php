<?php

require_once __DIR__ . '/../app/Logger.php';

class Database
{
    private $db;
    private static ?Database $instance = null;
    private function __construct()
    {
        try {
            $this->db = new PDO(
                "mysql:host=localhost;dbname=blog_db;charset=utf8mb4",
                "root",
                ""
            );
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            Logger::getInstance()->error("Erreur de connexion BDD : " . $e->getMessage());
            die("Erreur critique de base de données. Veuillez réessayer plus tard.");
        }
    }

    //Pour empêcher le clonage
    private function __clone()
    {
    }

    // Pour obtenir l'instance unique
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->db;
    }
}
