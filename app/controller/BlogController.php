<?php
require_once __DIR__ . '/../models/Blog.php';

class BlogController
{
    private \Twig\Environment $twig;
    private Blog $BlogModel;

    public function __construct(\Twig\Environment $twig)
    {
        $this->twig = $twig;          // on garde l'instance Twig
        $this->BlogModel = new Blog();
    }

    public function index(): void
    {
        $articles = $this->BlogModel->getAllArticles();
        echo $this->twig->render('index.twig', [
            'articles' => $articles,
            'titre_doc' => 'Blog - Accueil',
            'titre_page' => 'Liste des articles',
        ]);
    }

    public function contact(): void
    {
        echo $this->twig->render('contact.twig', [
            'titre_doc' => "Blog - Contact",
            'titre_page' => 'Contactez-nous',
        ]);

    }

    public function article($slug): void
    {

        $article = $this->BlogModel->getArticleBySlug($slug);
        echo $this->twig->render('article.twig', [

            'article' => $article,
            'titre_doc' => 'Article',
            'titre_page' => 'Détail de l\'article',

        ]);

    }


    /* Fonctions pour l'onglet Mes Articles (création, édition, suppression) */
    public function myArticles(): void {
        // Vérifie que l'utilisateur est connecté
        if (!isset($_SESSION['user'])) {
            header('Location: ' . $this->twig->getGlobals()['base_url']); //redirection vers l'accueil
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $articles = $this->BlogModel->getArticlesByUser($userId);
        echo $this->twig->render('myArticles/myArticles.twig', [
            'titre_doc' => "Blog - Mes Articles",
            'titre_page' => 'Mes Articles',
            'articles' => $articles
        ]);
    }
}
