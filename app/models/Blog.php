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
        $result = $query->fetch(PDO::FETCH_ASSOC);

        return $result ?: [];
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

    /* Fonctions pour l'onglet Mes Articles (création, édition, suppression) */
    public function getArticlesByUser(int $userId): array
    {
        $query = $this->db->prepare("SELECT * FROM articles WHERE utilisateur_id = :id ORDER BY date_mise_a_jour DESC");
        $query->bindParam(':id', $userId);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllTags(): array
    {
        $query = $this->db->prepare("SELECT * FROM tags ORDER BY nom_tag");
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function slugExists(string $slug): bool
    {
        $query = $this->db->prepare("SELECT COUNT(*) FROM articles WHERE slug = :slug");
        $query->execute([':slug' => $slug]);
        return $query->fetchColumn() > 0;
    }


    public function createArticle(array $data): int
    {
        $query = $this->db->prepare("
        INSERT INTO Articles (utilisateur_id, titre, slug, contenu, image_une, statut, date_creation, date_mise_a_jour)
        VALUES (:id_utilisateur, :titre, :slug, :contenu, :image_une, :statut, NOW(), NOW())
    ");

        $query->execute([
            ':id_utilisateur' => $data['id_utilisateur'],
            ':titre' => $data['titre'],
            ':slug' => $data['slug'],
            ':contenu' => $data['contenu'],
            ':image_une' => $data['image_une'] ?? null, // si pas d'image
            ':statut' => $data['statut']
        ]);

        return (int) $this->db->lastInsertId();
    }

    // Ajoute un tag à un article
    public function addTagToArticle(int $articleId, int $tagId): void
    {
        $query = $this->db->prepare("
            INSERT INTO article_tag (article_id, tag_id) VALUES (:articleId, :tagId)
        ");
        $query->execute([
            ':articleId' => $articleId,
            ':tagId' => $tagId
        ]);
    }

    // Récupère les tags associés à un article
    public function getTagsByArticle(int $articleId): array
    {
        $query = $this->db->prepare("
        SELECT t.id, t.nom_tag
        FROM tags t
        INNER JOIN article_tag at ON t.id = at.tag_id
        WHERE at.article_id = :articleId
    ");
        $query->execute([':articleId' => $articleId]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Supprime tous les tags liés à un article
     */
    public function removeTagsFromArticle(int $articleId): void
    {
        $query = $this->db->prepare("
        DELETE FROM article_tag WHERE article_id = :articleId
    ");
        $query->execute([
            ':articleId' => $articleId
        ]);
    }


    // Met à jour un article existant
    public function updateArticle(int $articleId, array $data): void
    {
        $query = $this->db->prepare("
        UPDATE articles
        SET titre = :titre,
            slug = :slug,
            contenu = :contenu,
            image_une = :image_une,
            statut = :statut,
            date_mise_a_jour = NOW()
        WHERE id = :articleId
    ");

        $query->execute([
            ':titre' => $data['titre'],
            ':slug' => $data['slug'],
            ':contenu' => $data['contenu'],
            ':image_une' => $data['image_une'] ?? null,
            ':statut' => $data['statut'],
            ':articleId' => $articleId
        ]);

        // Supprime les anciens tags
        $del = $this->db->prepare("DELETE FROM article_tag WHERE article_id = :articleId");
        $del->execute([':articleId' => $articleId]);

        // Réassocie les tags sélectionnés
        if (!empty($data['tags'])) {
            $insertTag = $this->db->prepare("
            INSERT INTO article_tag (article_id, tag_id)
            VALUES (:articleId, :tagId)
        ");
            foreach ($data['tags'] as $tagId) {
                $insertTag->execute([
                    ':articleId' => $articleId,
                    ':tagId' => $tagId
                ]);
            }
        }
    }


    public function deleteArticle(int $articleId): bool
    {
        // Supprime d'abord les associations tags
        $queryTags = $this->db->prepare("DELETE FROM article_tag WHERE article_id = :id");
        $queryTags->execute([':id' => $articleId]);

        // Supprime ensuite les commentaires associés
        $queryComments = $this->db->prepare("DELETE FROM commentaires WHERE article_id = :id");
        $queryComments->execute([':id' => $articleId]);

        // Supprime l'article
        $queryArticle = $this->db->prepare("DELETE FROM articles WHERE id = :id");
        return $queryArticle->execute([':id' => $articleId]);
    }


    public function getCommentsByArticleId(int $articleId): array
    {
        $query = $this->db->prepare("
            SELECT * FROM Commentaires 
            WHERE article_id = :id 
            AND statut = 'Approuvé' 
            ORDER BY date_commentaire DESC
        ");
        $query->bindParam(':id', $articleId);
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    public function createComment(int $articleId, string $authorName, ?string $authorEmail, string $content): bool
    {
        $query = $this->db->prepare("
            INSERT INTO Commentaires (article_id, nom_auteur, email_auteur, contenu, statut, date_commentaire)
            VALUES (:articleId, :nom, :email, :contenu, 'En attente', NOW())
        ");
        return $query->execute([
            ':articleId' => $articleId,
            ':nom' => $authorName,
            ':email' => $authorEmail,
            ':contenu' => $content
        ]);
    }
}
