<?php

require_once __DIR__ . '/../models/Blog.php';

class AuthController
{
    private \Twig\Environment $twig;
    private Blog $blogModel;

    public function __construct(\Twig\Environment $twig)
    {
        $this->twig = $twig;
        $this->blogModel = new Blog();
    }

    public function auth(): void
    {
        echo $this->twig->render('auth.twig', [
            'titre_doc' => "Blog - Authentification",
            'titre_page' => 'Connectez-vous',
        ]);
    }

    public function login(): void
    {
        // Récupérer les données du formulaire
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            $user = $this->blogModel->getUserByEmail($email);

            if ($user && password_verify($password, $user['mot_de_passe'])) {
                // Connexion réussie
                $roles = $this->blogModel->getUserRoles($user['id']);

                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'nom_utilisateur' => $user['nom_utilisateur'],
                    'email' => $user['email'],
                    'roles' => $roles
                ];

                // Redirection vers l'accueil
                header('Location: ' . $this->twig->getGlobals()['base_url']);
                exit;
            } else {
                // Echec
                echo "Identifiants incorrects.";
            }

        } else {
            // Si ce n'est pas un POST, on redirige vers le formulaire
            header('Location: auth');
            exit;
        }
    }

    public function logout(): void
    {
        // On détruit la session
        session_destroy();
        // Redirection vers l'accueil
        header('Location: ' . $this->twig->getGlobals()['base_url']);
        exit;
    }
}
