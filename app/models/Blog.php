<?php // app/models/Blog.php
class Blog {
    private $db;

    public function __construct() {
        $this->db = new PDO('mysql:host=localhost;dbname=blog_db', 'root', '');
    }

    public function getAllArticles(){
    $query = $this->db->prepare("SELECT * FROM articles");
    $query->execute();
    return $query->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getArticleById($id): array {
        $query = $this->db->prepare("SELECT * FROM articles WHERE id = :id");
        $query->bindParam(':id', $id);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }



     

    public function addTask($taskName) {
        
        $query = $this->db->prepare("INSERT INTO tasks (taskName) VALUES (:taskName)");
        $query->bindParam(':taskName', $taskName);
        $query->execute();
    }

    public function deleteTask($taskId) {
        $query = $this->db->prepare("DELETE FROM tasks WHERE id = :id");
        $query->bindParam(":id", $taskId);
        $query->execute();
    }
}