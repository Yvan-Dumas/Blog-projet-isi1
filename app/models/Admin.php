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
}