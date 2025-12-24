<?php // app/models/Blog.php

require_once __DIR__ . '/../../config/Database.php';

class Admin
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getArticleCount(): int
    {
        $query = $this->db->query("SELECT COUNT(*) FROM Articles");
        return (int) $query->fetchColumn();
    }

    public function getPendingCommentCount(): int
    {
        $query = $this->db->query("SELECT COUNT(*) FROM Commentaires WHERE statut = 'En attente'");
        return (int) $query->fetchColumn();
    }

    public function getActiveUserCount(): int
    {
        $query = $this->db->query("SELECT COUNT(*) FROM Utilisateurs WHERE est_actif = 1");
        return (int) $query->fetchColumn();
    }
    public function getLastArticles(int $limit = 10): array
    {
        $query = $this->db->prepare("SELECT id, titre, statut, date_creation FROM Articles ORDER BY date_creation DESC LIMIT :limit");
        $query->bindValue(':limit', $limit, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLastComments(int $limit = 10): array
    {
        $query = $this->db->prepare("
            SELECT c.id, c.contenu, c.nom_auteur, c.statut, c.date_commentaire, a.titre as article_titre 
            FROM Commentaires c 
            JOIN Articles a ON c.article_id = a.id 
            ORDER BY c.date_commentaire DESC LIMIT :limit
        ");
        $query->bindValue(':limit', $limit, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLastUsers(int $limit = 10): array
    {
        $query = $this->db->prepare("SELECT id, nom_utilisateur, email, date_inscription FROM Utilisateurs ORDER BY date_inscription DESC LIMIT :limit");
        $query->bindValue(':limit', $limit, PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllUsersWithRoles(): array
    {
        $sql = "SELECT u.id, u.nom_utilisateur, u.email, 
                GROUP_CONCAT(r.nom_role SEPARATOR ', ') as roles_names
                FROM Utilisateurs u
                LEFT JOIN Role_User ru ON u.id = ru.user_id
                LEFT JOIN Roles r ON ru.role_id = r.id
                GROUP BY u.id
                ORDER BY u.id ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserById($id): ?array
    {
        $query = $this->db->prepare("SELECT * FROM Utilisateurs WHERE id = :id");
        $query->bindParam(':id', $id);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserRoles($userId): array
    {
        $query = $this->db->prepare("SELECT role_id FROM Role_User WHERE user_id = :id");
        $query->bindParam(':id', $userId);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_COLUMN);
    }

    public function updateUserRoles(int $userId, array $roleIds): bool
    {
        try {
            $this->db->beginTransaction();

            // 1. Delete existing roles
            $delete = $this->db->prepare("DELETE FROM Role_User WHERE user_id = :uid");
            $delete->bindParam(':uid', $userId);
            $delete->execute();

            // 2. Insert new roles
            $insert = $this->db->prepare("INSERT INTO Role_User (user_id, role_id) VALUES (:uid, :rid)");
            foreach ($roleIds as $rid) {
                $insert->bindValue(':uid', $userId);
                $insert->bindValue(':rid', $rid);
                $insert->execute();
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getAllRoles(): array
    {
        return $this->db->query("SELECT * FROM Roles")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTagsStats(): array
    {
        $sql = "SELECT t.id, t.nom_tag, COUNT(at.article_id) as article_count
                FROM Tags t
                LEFT JOIN Article_Tag at ON t.id = at.tag_id
                GROUP BY t.id
                ORDER BY article_count DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}