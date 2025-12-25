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

    // Fonction pour la page d'accueil
    public function index(): void
    {
        $articles = $this->BlogModel->getAllArticles();
        echo $this->twig->render('index.twig', [
            'articles' => $articles,
            'titre_doc' => 'Blog - Accueil',
            'titre_page' => 'Liste des articles',
        ]);
    }

    // Fonction pour la page d'un article
    public function article($slug): void
    {
        $article = $this->BlogModel->getArticleBySlug($slug); // récupération de l'article par son slug

        $converter = new CommonMarkConverter([ // Converteur markdown
            'html_input' => 'strip',   // sécurité
            'allow_unsafe_links' => false,
        ]);
        $article['contenu_html'] = $converter->convert($article['contenu'])->getContent(); // conversion du contenu markdown

        $comments = $this->BlogModel->getCommentsByArticleId($article['id']); // récupération des commentaires d'un article

        echo $this->twig->render('article.twig', [
            'article' => $article,
            'comments' => $comments,
            'titre_doc' => 'Blog - Article'
        ]);
    }


    /* ====== Fonctions pour l'onglet Mes Articles (création, édition, suppression) ====== */

    // Fonction permettant de savoir si l'utilisateur est connecté et si c'est un éditeur ou un contributeur
    private function userCanCreateArticle(): bool
    {
        if (!isset($_SESSION['user'])) {
            return false; // pas connecté
        }

        $userRoles = $_SESSION['user']['roles'] ?? [];

        if (in_array(2, $userRoles) || in_array(3, $userRoles)) {
            return true;
        }
        return false;
    }

    // Fonction pour la page mes articles
    public function renderMyArticles(): void
    {
        // Vérifie que l'utilisateur est connecté et peut accéder à la page
        if (!$this->userCanCreateArticle()) {
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

    // Fonction pour la page de création d'article
    public function renderCreateArticle(): void
    {
        // Vérifie que l'utilisateur est connecté et peut accéder à la page
        if (!$this->userCanCreateArticle()) {
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

    //Traite le formulaire de création d'article et stocke l'article en base
    public function storeArticle()
    {
        // Vérifie que l'utilisateur est connecté et peut accéder à la page
        if (!$this->userCanCreateArticle()) {
            header('Location: ' . $this->twig->getGlobals()['base_url']); //redirection vers l'accueil
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $titre = $_POST['titre'];
        $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $titre)));
        $slug = $baseSlug;
        $counter = 1;
        // Si le slug existe deja, on rajoute -1, -2, -3 ...
        while ($this->BlogModel->slugExists($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $contenu = $_POST['contenu'];
        // Définition des statuts autorisés selon le rôle
        if (in_array(2, $_SESSION['user']['roles'])) {
            // Éditeur
            $validStatuses = ['Brouillon', 'Publié', 'Archivé'];
        } else {
            // Contributeur
            $validStatuses = ['Brouillon', 'Archivé'];
        }

        // Statut demandé
        $statut = $_POST['statut'] ?? 'Brouillon';

        // Sécurisation finale
        if (!in_array($statut, $validStatuses)) {
            $statut = 'Brouillon';
        }

        $tags = $_POST['tags'] ?? [];

        // gestion de l'image
        $imagePath = null;
        if (!empty($_FILES['image']['name'])) {
            $uploadDir = __DIR__ . '/../../public/image/articles/';
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid('article_') . '.' . $extension;
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName);
            $imagePath = 'image/articles/' . $fileName; // chemin stocké en base
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

        $logger = Logger::getInstance();
        $logger->info("Nouvel article créé", [
            'article_id'   => $articleId,
            'titre'        => $titre,
            'slug'         => $slug,
            'utilisateur'  => [
                'id' => $userId,
                'nom' => $_SESSION['user']['nom_utilisateur'] ?? 'inconnu'
            ],
            'tags' => $tags,
            'image' => $imagePath,
            'statut' => $statut
        ]);

        header('Location: ' . $this->twig->getGlobals()['base_url'] . '/myArticles'); //redirection
        exit;
    }

    // Fonction pour la page de modification d'article
    public function renderArticleBySlug($slug): void
    {
        // Vérifie que l'utilisateur est connecté et peut accéder à la page
        if (!$this->userCanCreateArticle()) {
            header('Location: ' . $this->twig->getGlobals()['base_url']); //redirection vers l'accueil
            exit;
        }

        // Récupère l'article par slug
        $article = $this->BlogModel->getArticleBySlug($slug);
        if (!$article) {
            header('Location: ' . $this->twig->getGlobals()['base_url'] . '/myArticles'); //redirection
            exit;
        }

        // Vérifie que l'utilisateur est le propriétaire
        $userId = $_SESSION['user']['id'];
        if ($article['utilisateur_id'] != $userId) {
            header('HTTP/1.1 403 Forbidden');
            echo "Vous n'avez pas le droit de modifier cet article.";
            exit;
        }

        $tags = $this->BlogModel->getAllTags();

        // Récupère les tags associés à l'article
        $articleTags = $this->BlogModel->getTagsByArticle($article['id']);
        $article['tags'] = array_column($articleTags, 'id');

        echo $this->twig->render('myArticles/edit.twig', [
            'titre_doc' => "Blog - Modifier l'article",
            'titre_page' => 'Modifier l\'article',
            'tags' => $tags,
            'article' => $article
        ]);
    }

    // Traite le formulaire de modification d'article
    public function updateArticleBySlug(string $slug)
    {
        // Vérifie que l'utilisateur est connecté et peut modifier des articles
        if (!$this->userCanCreateArticle()) {
            header('Location: ' . $this->twig->getGlobals()['base_url']);
            exit;
        }

        $userId = $_SESSION['user']['id'];

        // Récupère l'article existant
        $article = $this->BlogModel->getArticleBySlug($slug);
        if (!$article) {
            header('Location: ' . $this->twig->getGlobals()['base_url'] . '/myArticles');
            exit;
        }

        // Vérifie que l'utilisateur est le propriétaire
        if ($article['utilisateur_id'] != $userId) {
            header('HTTP/1.1 403 Forbidden');
            echo "Vous n'avez pas le droit de modifier cet article.";
            exit;
        }

        // Récupère les données du formulaire
        $titre = $_POST['titre'];
        $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $titre)));
        $newSlug = $baseSlug;
        $counter = 1;

        // Génère un slug unique (ignore l'article actuel)
        while ($this->BlogModel->slugExists($newSlug) && $newSlug !== $article['slug']) {
            $newSlug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $contenu = $_POST['contenu'];
        $tags = $_POST['tags'] ?? [];

        // Gestion de l'image
        $imagePath = $article['image_une']; // garde l'image actuelle
        if (!empty($_FILES['image']['name'])) {
            $uploadDir = __DIR__ . '/../../public/image/articles/';
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid('article_') . '.' . $extension;
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName);
            $imagePath = 'image/articles/' . $fileName;
        }

        // Définition des statuts autorisés selon le rôle
        if (in_array(2, $_SESSION['user']['roles'])) {
            // Éditeur
            $validStatuses = ['Brouillon', 'Publié', 'Archivé'];
        } else {
            // Contributeur
            $validStatuses = ['Brouillon', 'Archivé'];
        }

        // Statut demandé
        $statut = $_POST['statut'] ?? 'Brouillon';

        // Sécurisation finale
        if (!in_array($statut, $validStatuses)) {
            $statut = 'Brouillon';
        }


        // Mise à jour de l'article en base
        $this->BlogModel->updateArticle($article['id'], [
            'titre' => $titre,
            'slug' => $newSlug,
            'contenu' => $contenu,
            'image_une' => $imagePath,
            'statut' => $statut
        ]);

        // Mise à jour des tags
        $this->BlogModel->removeTagsFromArticle($article['id']);
        foreach ($tags as $tagId) {
            $this->BlogModel->addTagToArticle($article['id'], (int) $tagId);
        }

        // Log de modification
        $logger = Logger::getInstance();
        $logger->info("Article modifié", [
            'article_id'  => $article['id'],
            'titre'       => $titre,
            'slug'        => $newSlug,
            'utilisateur' => [
                'id' => $userId,
                'nom' => $_SESSION['user']['nom_utilisateur'] ?? 'inconnu'
            ],
            'tags'        => $tags,
            'image'       => $imagePath,
            'statut' => $statut
        ]);

        // Redirection après modification
        header('Location: ' . $this->twig->getGlobals()['base_url'] . '/myArticles');
        exit;
    }



    // Fonction pour supprimer un article
    public function deleteArticleBySlug(string $slug)
    {
        // Vérifie que l'utilisateur est connecté et peut accéder à la page
        if (!$this->userCanCreateArticle()) {
            header('Location: ' . $this->twig->getGlobals()['base_url']); //redirection vers l'accueil
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $userRoles = $_SESSION['user']['roles'];

        $article = $this->BlogModel->getArticleBySlug($slug);
        if (!$article) {
            header('Location: ' . $this->twig->getGlobals()['base_url'] . '/myArticles'); //redirection
            exit;
        }

        if ($article['utilisateur_id'] != $userId && !in_array(1, $userRoles)) {
            header('HTTP/1.1 403 Forbidden');
            echo "Vous n'avez pas le droit de supprimer cet article.";
            exit;
        }

        $this->BlogModel->deleteArticle($article['id']);

        $logger = Logger::getInstance();
        $logger->info("Article supprimé", [
            'article_id' => $article['id'],
            'slug' => $slug,
            'titre' => $article['titre'],
            'utilisateur' => [
                'id' => $userId,
                'nom' => $_SESSION['user']['nom_utilisateur']
            ]
        ]);

        header('Location: ' . $this->twig->getGlobals()['base_url'] . '/myArticles'); //redirection
        exit;
    }
    /* =============================================================== */


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

            // Log du commentaire
            $logger = Logger::getInstance();
            $logger->info("Nouveau commentaire ajouté", [
                'article_id'   => $articleId,
                'article_slug' => $slug,
                'auteur'       => [
                    'nom'   => $authorName,
                    'email' => $authorEmail
                ],
                'contenu'      => $content
            ]);
            // Redirection
            header('Location: ' . $this->twig->getGlobals()['base_url'] . 'article/' . $slug);
            exit;
        }
    }
}
