<?php
require_once __DIR__ . '/../models/Blog.php';
use League\CommonMark\CommonMarkConverter;


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

        $converter = new CommonMarkConverter([
        'html_input' => 'strip',   // sécurité
        'allow_unsafe_links' => false,
        ]);

        $article['contenu_html'] = $converter->convert($article['contenu'])->getContent();

        $comments = $this->BlogModel->getCommentsByArticleId($article['id']);

        echo $this->twig->render('article.twig', [
            'article' => $article,
            'comments' => $comments,
            'titre_doc' => 'Blog - Article'
        ]);
    }


    /* Fonctions pour l'onglet Mes Articles (création, édition, suppression) */
    public function renderMyArticles(): void
    {
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

    public function renderCreateArticle(): void
    {
        // Vérifie que l'utilisateur est connecté
        if (!isset($_SESSION['user'])) {
            header('Location: ' . $this->twig->getGlobals()['base_url']); //redirection vers l'accueil
            exit;
        }

        $tags = $this->BlogModel->getAllTags();
        echo $this->twig->render('myArticles/create.twig', [
            'titre_doc' => "Blog - Nouvel article",
            'titre_page' => 'Nouvel article',
            'tags' => $tags
        ]);
    }

    //Traite le formulaire et stocke l'article en base
    public function storeArticle()
    {
        // Vérifie que l'utilisateur est connecté
        if (!isset($_SESSION['user'])) {
            header('Location: ' . $this->twig->getGlobals()['base_url']); //redirection vers l'accueil
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $titre = $_POST['titre'];
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $titre)));
        $contenu = $_POST['contenu'];
        $statut = 'Brouillon';
        $tags = $_POST['tags'] ?? [];

        $imagePath = null;
        if (!empty($_FILES['image']['name'])) {
            $uploadDir = __DIR__ . '/../../public/uploads/';
            $fileName = uniqid() . '-' . basename($_FILES['image']['name']);
            $filePath = $uploadDir . $fileName;
            move_uploaded_file($_FILES['image']['tmp_name'], $filePath);
            $imagePath = 'uploads/' . $fileName; // ok maintenant
        }

        // Crée l'article
        $articleId = $this->BlogModel->createArticle([
            'titre' => $titre,
            'slug' => $slug,
            'contenu' => $contenu,
            'id_utilisateur' => $userId,
            'image_une' => $imagePath,
            'statut' => $statut
        ]);

        // Ajoute les tags
        foreach ($tags as $tagId) {
            $this->BlogModel->addTagToArticle($articleId, (int) $tagId);
        }

        header('Location: ' . $this->twig->getGlobals()['base_url']); //redirection vers l'accueil
        exit;
    }
    public function postComment()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $articleId = $_POST['article_id'] ?? null;
            $slug = $_POST['article_slug'] ?? '';
            $content = trim($_POST['content'] ?? '');

            // Validation simple
            if (!$articleId || empty($content)) {
                // Pour simplifier, on redirige juste (idéalement : message d'erreur en session)
                header('Location: ' . $this->twig->getGlobals()['base_url'] . 'article/' . $slug);
                exit;
            }

            // Gestion Utilisateur Connecté vs Invité
            if (isset($_SESSION['user'])) {
                $authorName = $_SESSION['user']['nom_utilisateur'];
                $authorEmail = $_SESSION['user']['email'];
            } else {
                $authorName = trim($_POST['author_name'] ?? '');
                $authorEmail = trim($_POST['author_email'] ?? '');

                if (empty($authorName) || empty($authorEmail)) {
                    header('Location: ' . $this->twig->getGlobals()['base_url'] . 'article/' . $slug);
                    exit;
                }
            }

            // Enregistrement
            $this->BlogModel->createComment($articleId, $authorName, $authorEmail, $content);

            // Redirection
            header('Location: ' . $this->twig->getGlobals()['base_url'] . 'article/' . $slug);
            exit;
        }
    }
}
