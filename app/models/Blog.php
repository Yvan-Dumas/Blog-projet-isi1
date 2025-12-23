<?php // app/models/Blog.php

require_once __DIR__ . '/../../config/Database.php';

class Blog
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAllArticles()
    {
        $query = $this->db->prepare("SELECT * FROM articles WHERE statut = 'Publié'");
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getArticleBySlug($Slug): array
    {
        $query = $this->db->prepare("SELECT * FROM articles WHERE slug = :slug");
        $query->bindParam(':slug', $Slug);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserByEmail($email): ?array
    {
        $query = $this->db->prepare("SELECT * FROM utilisateurs WHERE email = :email");
        $query->bindParam(':email', $email);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getUserRoles(int $userId): array
    {
        $query = $this->db->prepare("SELECT role_id FROM Role_User WHERE user_id = :id");
        $query->bindParam(':id', $userId);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_COLUMN);
    }
    public function createUser(string $username, string $email, string $password): bool
    {
        try {
            $this->db->beginTransaction();

            $query = $this->db->prepare("INSERT INTO Utilisateurs (nom_utilisateur, email, mot_de_passe) VALUES (:nom, :email, :mdp)");
            $query->bindParam(':nom', $username);
            $query->bindParam(':email', $email);
            $query->bindParam(':mdp', $password);

            if ($query->execute()) {
                $userId = $this->db->lastInsertId();
                // Assign Default Role 3 (Contributor)
                $roleQuery = $this->db->prepare("INSERT INTO Role_User (user_id, role_id) VALUES (:uid, 3)");
                $roleQuery->bindParam(':uid', $userId);
                $roleQuery->execute();

                $this->db->commit();
                return true;
            }

            $this->db->rollBack();
            return false;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}