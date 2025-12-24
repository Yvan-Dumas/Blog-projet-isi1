<?php

require_once __DIR__ . '/../models/Admin.php';
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
}
