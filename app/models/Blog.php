<?php // app/models/Blog.php

require_once __DIR__ . '/../../config/Database.php';

class Blog {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAllArticles(){
    $query = $this->db->prepare("SELECT * FROM articles");
    $query->execute();
    return $query->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getArticleBySlug($Slug): array {
        $query = $this->db->prepare("SELECT * FROM articles WHERE slug = :slug");
        $query->bindParam(':slug', $Slug);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }
}