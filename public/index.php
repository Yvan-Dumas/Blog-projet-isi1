<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/controller/BlogController.php';
require_once __DIR__ . '/../app/controller/AuthController.php';
require_once __DIR__ . '/../app/controller/AdminController.php';
require_once __DIR__ . '/../app/Logger.php';

// Twig
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../app/views');
$twig = new \Twig\Environment($loader, ['cache' => false]);

$controller = new BlogController($twig);
$authController = new AuthController($twig);
$AdminController = new AdminController($twig);

$basePath = dirname($_SERVER['SCRIPT_NAME']); // /Blog-projet-isi1/public
$twig->addGlobal('base_url', $basePath . '/');
$twig->addGlobal('session', $_SESSION);

// Récupération de l'URL demandée
$requestUri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)); #urldecode() permet de décoder les caractères spéciaux

// On enlève tout jusqu'à /public inclus
$requestUri = preg_replace('#^/.*/public#', '', $requestUri);


// Si l'url est vide, on met '/'
if (empty($requestUri)) {
    $requestUri = '/';
}

// Normalisation pour s'assurer qu'il y a un seul / au début
$requestUri = '/' . ltrim($requestUri, '/');



// Si on veut afficher un article
if (preg_match('#^/article/(.+)$#', $requestUri, $matches)) {
    $slug = $matches[1];
    $controller->article($slug);
    exit;
}

if (preg_match('#^/AdminUser/Edit/([0-9]+)$#', $requestUri, $matches)) {
    $id = (int) $matches[1];
    $AdminController->editUserRoles($id);
    exit;
}

// Routes Modération Commentaires
if (preg_match('#^/AdminComment/Approve/([0-9]+)$#', $requestUri, $matches)) {
    $AdminController->updateCommentStatusAction((int) $matches[1], 'Approuvé');
    exit;
}
if (preg_match('#^/AdminComment/Reject/([0-9]+)$#', $requestUri, $matches)) {
    $AdminController->updateCommentStatusAction((int) $matches[1], 'Rejeté');
    exit;
}
if (preg_match('#^/AdminComment/Delete/([0-9]+)$#', $requestUri, $matches)) {
    $AdminController->deleteCommentAction((int) $matches[1]);
    exit;
}

// Routes Gestion Articles
if (preg_match('#^/AdminArticle/Status/([0-9]+)/([a-zA-Zéèà]+)$#', $requestUri, $matches)) {
    $AdminController->updateArticleStatusAction((int) $matches[1], $matches[2]);
    exit;
}
if (preg_match('#^/AdminArticle/Delete/([0-9]+)$#', $requestUri, $matches)) {
    $AdminController->deleteArticleAction((int) $matches[1]);
    exit;
}

// Route Ajout Tag
if ($requestUri === '/AdminTag/Add') {
    $AdminController->addTagAction();
    exit;
}



// Route pour supprimer un article par slug
if (preg_match('#^/myArticles/delete/(.+)$#', $requestUri, $matches)) {
    $slug = $matches[1];
    $controller->deleteArticleBySlug($slug);
    exit;
}

// Route pour modifier un article par slug
if (preg_match('#^/myArticles/edit/(.+)$#', $requestUri, $matches)) {
    $slug = $matches[1];
    $controller->renderArticleBySlug($slug);
    exit;
}

// Route pour modifier un article une fois le formulaire soumis
if (preg_match('#^/myArticles/updateArticle/(.+)$#', $requestUri, $matches)) {
    $slug = $matches[1];
    $controller->updateArticleBySlug($slug);
    exit;
}



// Sinon
switch ($requestUri) {
    case '/':
    case '/index':
        $controller->index();
        break;
    case '/auth':
        $authController->auth();
        break;
    case '/login':
        $authController->login();
        break;
    case '/register':
        $authController->register();
        break;
    case '/logout':
        $authController->logout();
        break;
    case '/AdminBoard':
        $AdminController->AdminBoard();
        break;
    case '/activity':
        $AdminController->activity();
        break;
    case '/AdminUsers':
        $AdminController->usersList();
        break;
    case '/AdminCommentSs':
        $AdminController->commentsList();
        break;
    case '/AdminArticles':
        $AdminController->articlesList();
        break;
    case '/myArticles':
        $controller->renderMyArticles();
        break;
    case '/myArticles/create':
        $controller->renderCreateArticle();
        break;
    case '/myArticles/storeArticle':
        $controller->storeArticle();
        break;
    case '/comment/submit':
        $controller->postComment();
        break;
    default:
        http_response_code(404);
        Logger::getInstance()->error("404 Page Not Found", ['uri' => $requestUri, 'ip' => $_SERVER['REMOTE_ADDR']]);
        echo "Page non trouvée - URI : $requestUri";
        break;
}

