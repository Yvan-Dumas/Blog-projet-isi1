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

    // Fonction pour vérifier si l'utilisateur est connecté et est un admin
    private function checkAdminAccess(): void
    {
        // 1. L'utilisateur doit être connecté
        if (!isset($_SESSION['user'])) {
            header('Location: ' . $this->twig->getGlobals()['base_url'] . 'auth');
            exit;
        }

        // 2. L'utilisateur doit avoir le rôle Admin (ID = 1)
        if (!in_array(1, $_SESSION['user']['roles'])) {
            header('HTTP/1.1 403 Forbidden');
            echo "Accès refusé. Vous n'êtes pas administrateur.";
            exit;
        }
    }

    // Fonction pour le tableau de bord admin
    public function AdminBoard(): void
    {
        // Vérification permission
        $this->checkAdminAccess();

        // Récupération des stats
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

    // Fonction pour le fil d'activité
    public function activity(): void
    {
        // Vérification permission
        $this->checkAdminAccess();

        // récupération des données
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

    // Méthode pour l'affichage de la gestion des utilisateurs
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

    // Méthode pour modifier les rôles des utilisateurs
    public function editUserRoles(int $id): void
    {
        // Vérification permission
        $this->checkAdminAccess();

        // Méthode POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $roles = $_POST['roles'] ?? []; // tableau des rôles

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

        // Méthode GET
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

    // Méthode pour la page avec la liste des commentaires
    public function commentsList(): void
    {
        // Vérification permission
        $this->checkAdminAccess();
        $comments = $this->adminModel->getAllComments();
        echo $this->twig->render('adminComments.twig', [
            'titre_doc' => "Blog - Modération Commentaires",
            'titre_page' => "Gestion des commentaires",
            'comments' => $comments
        ]);
    }

    // Méthode pour modifier le statut d'un commentaire
    public function updateCommentStatusAction(int $id, string $status): void
    {
        // Vérification permission
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

    // Méthode pour supprimer un commentaire
    public function deleteCommentAction(int $id): void
    {
        // Vérification permission
        $this->checkAdminAccess();
        try {
            $this->adminModel->deleteComment($id);
        } catch (Exception $e) {
            Logger::getInstance()->error("Erreur suppression commentaire : " . $e->getMessage(), ['id' => $id]);
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

    // Méthode pour la page avec la liste des articles
    public function articlesList(): void
    {
        // Vérification permission
        $this->checkAdminAccess();
        $articles = $this->adminModel->getAllArticlesWithAuthors();
        echo $this->twig->render('adminArticles.twig', [
            'titre_doc' => "Blog - Gestion Articles",
            'titre_page' => "Gestion des articles",
            'articles' => $articles
        ]);
    }

    // Méthode pour mettre à jour le statut d'un article
    public function updateArticleStatusAction(int $id, string $status): void
    {
        // Vérification permission
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

    // Méthode pour supprimer un article
    public function deleteArticleAction(int $id): void
    {
        // Vérification permission
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

    // Méthoe pour créer un tag
    public function addTagAction(): void
    {
        // Vérification permission
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

    // Méthode pour la page de modifier d'un tag
    public function editTag(): void
    {
        // Vérification permission
        $this->checkAdminAccess();

        echo $this->twig->render('adminBoardTagEdit.twig', [
            'tag_id' => $_POST['tag_id'],
            'tag_name' => $_POST['tag_name']
        ]);
    }


    // Méthode pour mettre à jour un tag
    public function updateTag(): void
    {
        // Vérification permission
        $this->checkAdminAccess();

        $id = (int) $_POST['tag_id'];
        $name = trim($_POST['tag_name']);

        if ($name !== '') {
            $this->adminModel->updateTag($id, $name);

            Logger::getInstance()->info('Tag modifié', [
                'tag_id' => $id,
                'nom' => $name,
                'admin' => $_SESSION['user']['id']
            ]);
        }

        header('Location: ' . $this->twig->getGlobals()['base_url'] . 'AdminBoard');
        exit;
    }

    // Méthode pour supprimer un tag
    public function deleteTag(): void
    {
        // Vérification permission
        $this->checkAdminAccess();

        $id = (int) $_POST['tag_id'];

        $this->adminModel->deleteTag($id);

        Logger::getInstance()->info('Tag supprimé', [
            'tag_id' => $id,
            'admin' => $_SESSION['user']['id']
        ]);

        header('Location: ' . $this->twig->getGlobals()['base_url'] . 'AdminBoard');
        exit;
    }
}
