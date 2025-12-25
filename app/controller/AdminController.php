<?php

require_once __DIR__ . '/../models/Admin.php';

require_once __DIR__ . '/../Logger.php';
class AdminController
{
    private \Twig\Environment $twig;
    private Admin $adminModel;
    public function __construct(\Twig\Environment $twig)
    {
        $this->twig = $twig;
        $this->adminModel = new Admin();
    }

    private function checkAdminAccess(): void
    {
        // 1. L'utilisateur doit être connecté
        if (!isset($_SESSION['user'])) {
            header('Location: ' . $this->twig->getGlobals()['base_url'] . 'auth');
            exit;
        }

        // 2. L'utilisateur doit avoir le rôle Admin (ID = 1)
        // On suppose que $_SESSION['user']['roles'] est un tableau d'IDs
        if (!in_array(1, $_SESSION['user']['roles'])) {
            header('HTTP/1.1 403 Forbidden');
            echo "Accès refusé. Vous n'êtes pas administrateur.";
            exit;
        }
    }

    public function AdminBoard(): void
    {
        $this->checkAdminAccess();
        $stats = [
            'nbArticles' => $this->adminModel->getArticleCount(),
            'nbCommentaires' => $this->adminModel->getPendingCommentCount(),
            'nbUtilisateurs' => $this->adminModel->getActiveUserCount(),
        ];

        echo $this->twig->render('adminBoard.twig', [
            'titre_doc' => "Blog - AdminBoard",
            'titre_page' => 'Tableau de bord',
            'stats' => $stats,
            'tags' => $this->adminModel->getTagsStats()
        ]);
    }

    public function activity(): void
    {
        $this->checkAdminAccess();
        $data = [
            'articles' => $this->adminModel->getLastArticles(),
            'comments' => $this->adminModel->getLastComments(),
            'users' => $this->adminModel->getLastUsers(),
        ];

        echo $this->twig->render('adminActivity.twig', [
            'titre_doc' => "Blog - Activité",
            'titre_page' => "Fil d'activité",
            'activity' => $data
        ]);
    }
    public function usersList(): void
    {
        $this->checkAdminAccess();
        $users = $this->adminModel->getAllUsersWithRoles();
        echo $this->twig->render('adminUsers.twig', [
            'titre_doc' => "Blog - Gestion Utilisateurs",
            'titre_page' => "Gestion des utilisateurs",
            'users' => $users
        ]);
    }

    public function editUserRoles(int $id): void
    {
        $this->checkAdminAccess();
        // Handle POST update
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $roles = $_POST['roles'] ?? []; // Array of role IDs
                // Security: Ensure at least one role is assigned or handle empty? 
                // Better allow empty if that's desired, but usually a user needs a role.

                if ($this->adminModel->updateUserRoles($id, $roles)) {
                    Logger::getInstance()->security(
                        'Mise à jour des rôles utilisateur',
                        [
                            'admin_id' => $_SESSION['user']['id'],
                            'user_id' => $id,
                            'new_roles' => $roles
                        ]
                    );
                    header('Location: ' . $this->twig->getGlobals()['base_url'] . 'AdminUsers');
                    exit;
                } else {
                    echo "Erreur lors de la mise à jour.";
                }
            } catch (Exception $e) {
                Logger::getInstance()->error("Erreur lors de la modification des rôles : " . $e->getMessage(), ['user_id' => $id]);
                echo "Une erreur est survenue.";
            }
        }

        // Handle GET display
        $user = $this->adminModel->getUserById($id);
        $userRoles = $this->adminModel->getUserRoles($id);
        $allRoles = $this->adminModel->getAllRoles();

        if (!$user) {
            echo "Utilisateur introuvable.";
            return;
        }

        echo $this->twig->render('adminUserEdit.twig', [
            'titre_doc' => "Blog - Modifier Rôles",
            'titre_page' => "Modifier les rôles : " . $user['nom_utilisateur'],
            'user' => $user,
            'userRoles' => $userRoles,
            'allRoles' => $allRoles
        ]);
    }
    public function commentsList(): void
    {
        $this->checkAdminAccess();
        $comments = $this->adminModel->getAllComments();
        echo $this->twig->render('adminComments.twig', [
            'titre_doc' => "Blog - Modération Commentaires",
            'titre_page' => "Gestion des commentaires",
            'comments' => $comments
        ]);
    }

    public function updateCommentStatusAction(int $id, string $status): void
    {
        $this->checkAdminAccess();
        try {
            // Sécurité : vérifier que le statut est valide
            $validStatuses = ['Approuvé', 'Rejeté', 'En attente'];
            if (in_array($status, $validStatuses)) {
                $this->adminModel->updateCommentStatus($id, $status);
            }
        } catch (Exception $e) {
            Logger::getInstance()->error("Erreur modération commentaire : " . $e->getMessage(), ['id' => $id]);
        }

        // Log de modification
        $logger = Logger::getInstance();
        $logger->info("Statut de commentaire modifié", [
            'comment_id' => $id,
            'article_id' => $comment['article_id'] ?? null,
            'auteur' => $comment['nom_auteur'] ?? 'inconnu',
            'ancien_statut' => $comment['statut'] ?? 'inconnu',
            'nouveau_statut' => $status,
            'admin' => [
                'id' => $_SESSION['user']['id'] ?? null,
                'nom' => $_SESSION['user']['nom_utilisateur'] ?? 'inconnu'
            ]
        ]);

        // Redirection vers la liste
        header('Location: ' . $this->twig->getGlobals()['base_url'] . 'AdminComments');
        exit;
    }

    public function deleteCommentAction(int $id): void
    {
        $this->checkAdminAccess();
        try {
            $this->adminModel->deleteComment($id);
        } catch (Exception $e) {
            Logger::getInstance()->error("Erreur suppression commentaire : " . $e->getMessage(), ['id' => $id]);
            // On peut rediriger avec flash ou laisser continuer pour le log de suppression (qui pourrait échouer si record deleted ?)
            // Idéalement on arrête si erreur critique. Mais continuons pour garder la structure.
        }

        // Log de suppression
        $logger = Logger::getInstance();
        $logger->info("Commentaire supprimé", [
            'comment_id' => $id,
            'article_id' => $comment['article_id'] ?? null,
            'auteur' => $comment['nom_auteur'] ?? 'inconnu',
            'contenu' => $comment['contenu'] ?? '',
            'admin' => [
                'id' => $_SESSION['user']['id'] ?? null,
                'nom' => $_SESSION['user']['nom_utilisateur'] ?? 'inconnu'
            ]
        ]);

        header('Location: ' . $this->twig->getGlobals()['base_url'] . 'AdminComments');
        exit;
    }

    public function articlesList(): void
    {
        $this->checkAdminAccess();
        $articles = $this->adminModel->getAllArticlesWithAuthors();
        echo $this->twig->render('adminArticles.twig', [
            'titre_doc' => "Blog - Gestion Articles",
            'titre_page' => "Gestion des articles",
            'articles' => $articles
        ]);
    }

    public function updateArticleStatusAction(int $id, string $status): void
    {
        $this->checkAdminAccess();
        try {
            $validStatuses = ['Publié', 'Brouillon', 'Archivé'];
            if (in_array($status, $validStatuses)) {
                $this->adminModel->updateArticleStatus($id, $status);
            }
        } catch (Exception $e) {
            Logger::getInstance()->error("Erreur modification statut article : " . $e->getMessage(), ['id' => $id]);
        }

        // Log de modification de statut
        $logger = Logger::getInstance();
        $logger->info("Statut d'article modifié", [
            'article_id' => $id,
            'nouveau_statut' => $status,
            'admin' => [
                'id' => $_SESSION['user']['id'] ?? null,
                'nom' => $_SESSION['user']['nom_utilisateur'] ?? 'inconnu'
            ]
        ]);

        header('Location: ' . $this->twig->getGlobals()['base_url'] . 'AdminArticles');
        exit;
    }

    public function deleteArticleAction(int $id): void
    {
        $this->checkAdminAccess();
        try {
            $this->adminModel->deleteArticle($id);
        } catch (Exception $e) {
            Logger::getInstance()->error("Erreur suppression article : " . $e->getMessage(), ['id' => $id]);
        }

        // Log de suppression
        $logger = Logger::getInstance();
        $logger->info("Article supprimé", [
            'article_id' => $id,
            'admin' => [
                'id' => $_SESSION['user']['id'] ?? null,
                'nom' => $_SESSION['user']['nom_utilisateur'] ?? 'inconnu'
            ]
        ]);

        header('Location: ' . $this->twig->getGlobals()['base_url'] . 'AdminArticles');
        exit;
    }

    public function addTagAction(): void
    {
        $this->checkAdminAccess();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tagName = trim($_POST['tag_name'] ?? '');
            if (!empty($tagName)) {
                try {
                    $this->adminModel->createTag($tagName);
                } catch (Exception $e) {
                    Logger::getInstance()->error("Erreur création tag : " . $e->getMessage(), ['tag' => $tagName]);
                }
            }
        }
        header('Location: ' . $this->twig->getGlobals()['base_url'] . 'AdminBoard');
        exit;
    }
}
