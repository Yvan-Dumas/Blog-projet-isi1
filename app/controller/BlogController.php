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
            'articles'   => $articles,
            'titre_doc'  => 'Blog - Accueil',
            'titre_page' => 'Liste des articles',
        ]);
    }

    public function contact(): void
    {
        echo $this->twig->render('contact.twig', [
            'titre_doc'  => "Blog - Contact",
            'titre_page' => 'Liste des articles',
        ]);

    }

    public function article($id): void
    {

        $article = $this->BlogModel->getArticleById($id);
        echo $this->twig->render('article.twig', [

            'article'    => $article,
            'titre_doc'  => 'Article',
            'titre_page' => 'Détail de l\'article',

        ]);

    }
}
