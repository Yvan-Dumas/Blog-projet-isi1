<?php

require_once __DIR__ . '/../models/Blog.php';

require_once __DIR__ . '/../Logger.php';

class AuthController
{
    private \Twig\Environment $twig;
    private Blog $blogModel;

    public function __construct(\Twig\Environment $twig)
    {
        $this->twig = $twig;
        $this->blogModel = new Blog();
    }

    // Méthode pour la page 
    public function auth(): void
    {
        echo $this->twig->render('auth.twig', [
            'titre_doc' => "Blog - Authentification",
            'titre_page' => 'Connectez-vous',
        ]);
    }

    // Méthode pour la connexion au blog
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

                // Ajout message de log
                Logger::getInstance()->security(
                    "Echec de connexion",
                    [
                        'email' => $email,
                        'ip' => $_SERVER['REMOTE_ADDR']
                    ]
                );
            }
        } else {
            // Si ce n'est pas un POST, on redirige vers le formulaire
            header('Location: auth');
            exit;
        }
    }

    // Méthode pour la déconnexion
    public function logout(): void
    {
        // On détruit la session
        session_destroy();
        // Redirection vers l'accueil
        header('Location: ' . $this->twig->getGlobals()['base_url']);
        exit;
    }

    // Méthode pour l'inscription d'un utilisateur
    public function register(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($username) || empty($email) || empty($password)) {
                $this->renderRegister("Tous les champs sont obligatoires.");
                return;
            }

            if ($password !== $confirmPassword) {
                $this->renderRegister("Les mots de passe ne correspondent pas.");
                return;
            }

            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            if ($this->blogModel->createUser($username, $email, $hashedPassword)) {
                // Succès -> Redirection vers login
                header('Location: ' . $this->twig->getGlobals()['base_url'] . 'auth');
                exit;
            } else {
                $this->renderRegister("Erreur lors de l'inscription (Email ou Pseudo déjà pris ?).");
            }
        } else {
            $this->renderRegister();
        }
    }

    // Méthode pour la page d'inscription
    private function renderRegister(?string $error = null): void
    {
        echo $this->twig->render('register.twig', [
            'titre_doc' => "Blog - Inscription",
            'titre_page' => "Créer un compte",
            'error' => $error
        ]);
    }
}
