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

    public function AdminBoard(): void
    {
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
        $users = $this->adminModel->getAllUsersWithRoles();
        echo $this->twig->render('adminUsers.twig', [
            'titre_doc' => "Blog - Gestion Utilisateurs",
            'titre_page' => "Gestion des utilisateurs",
            'users' => $users
        ]);
    }

    public function editUserRoles(int $id): void
    {
        // Handle POST update
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $roles = $_POST['roles'] ?? []; // Array of role IDs

            // Security: Ensure at least one role is assigned or handle empty? 
            // Better allow empty if that's desired, but usually a user needs a role.
            // Let's assume passed array is correct.

            if ($this->adminModel->updateUserRoles($id, $roles)) {
                Logger::getInstance()->security(
                    'Mise à jour des rôles utilisateur',
                    [
                        'admin_id' => $_SESSION['user']['id'],
                        'user_id' => $id,
                        'new_roles' => $roles
                    ]
                );

                // Redirect back to list
                header('Location: ' . $this->twig->getGlobals()['base_url'] . 'AdminUsers');
                exit;
            } else {
                echo "Erreur lors de la mise à jour.";
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
        $comments = $this->adminModel->getAllComments();
        echo $this->twig->render('adminComments.twig', [
            'titre_doc' => "Blog - Modération Commentaires",
            'titre_page' => "Gestion des commentaires",
            'comments' => $comments
        ]);
    }

    public function updateCommentStatusAction(int $id, string $status): void
    {
        // Sécurité : vérifier que le statut est valide
        $validStatuses = ['Approuvé', 'Rejeté', 'En attente'];
        if (in_array($status, $validStatuses)) {
            $this->adminModel->updateCommentStatus($id, $status);
        }

        // Log de modification
        $logger = Logger::getInstance();
        $logger->info("Statut de commentaire modifié", [
            'comment_id'    => $id,
            'article_id'    => $comment['article_id'] ?? null,
            'auteur'        => $comment['nom_auteur'] ?? 'inconnu',
            'ancien_statut' => $comment['statut'] ?? 'inconnu',
            'nouveau_statut' => $status,
            'admin'         => [
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
        $this->adminModel->deleteComment($id);

        // Log de suppression
        $logger = Logger::getInstance();
        $logger->info("Commentaire supprimé", [
            'comment_id' => $id,
            'article_id' => $comment['article_id'] ?? null,
            'auteur'     => $comment['nom_auteur'] ?? 'inconnu',
            'contenu'    => $comment['contenu'] ?? '',
            'admin'      => [
                'id' => $_SESSION['user']['id'] ?? null,
                'nom' => $_SESSION['user']['nom_utilisateur'] ?? 'inconnu'
            ]
        ]);

        header('Location: ' . $this->twig->getGlobals()['base_url'] . 'AdminComments');
        exit;
    }

    public function articlesList(): void
    {
        $articles = $this->adminModel->getAllArticlesWithAuthors();
        echo $this->twig->render('adminArticles.twig', [
            'titre_doc' => "Blog - Gestion Articles",
            'titre_page' => "Gestion des articles",
            'articles' => $articles
        ]);
    }

    public function updateArticleStatusAction(int $id, string $status): void
    {
        $validStatuses = ['Publié', 'Brouillon', 'Archivé'];
        if (in_array($status, $validStatuses)) {
            $this->adminModel->updateArticleStatus($id, $status);
        }

        // Log de modification de statut
        $logger = Logger::getInstance();
        $logger->info("Statut d'article modifié", [
            'article_id'    => $id,
            'nouveau_statut' => $status,
            'admin'         => [
                'id'  => $_SESSION['user']['id'] ?? null,
                'nom' => $_SESSION['user']['nom_utilisateur'] ?? 'inconnu'
            ]
        ]);

        header('Location: ' . $this->twig->getGlobals()['base_url'] . 'AdminArticles');
        exit;
    }

    public function deleteArticleAction(int $id): void
    {
        $this->adminModel->deleteArticle($id);

        // Log de suppression
        $logger = Logger::getInstance();
        $logger->info("Article supprimé", [
            'article_id' => $id,
            'admin'      => [
                'id'  => $_SESSION['user']['id'] ?? null,
                'nom' => $_SESSION['user']['nom_utilisateur'] ?? 'inconnu'
            ]
        ]);

        header('Location: ' . $this->twig->getGlobals()['base_url'] . 'AdminArticles');
        exit;
    }

    public function addTagAction(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tagName = trim($_POST['tag_name'] ?? '');
            if (!empty($tagName)) {
                $this->adminModel->createTag($tagName);
            }
        }
        header('Location: ' . $this->twig->getGlobals()['base_url'] . 'AdminBoard');
        exit;
    }
}
