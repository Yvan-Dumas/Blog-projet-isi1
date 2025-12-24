<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/controller/BlogController.php';
require_once __DIR__ . '/../app/controller/AuthController.php';
require_once __DIR__ . '/../app/controller/AdminController.php';

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
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// ex: /BLOG-PROJET-ISI1/public/contact

// On enlève tout jusqu'à /public inclus
$requestUri = preg_replace('#^/.*/public#', '', $requestUri);
// ex: devient /contact

// Si l'url est vide, on met '/'
if (empty($requestUri)) {
    $requestUri = '/';
}

// Normalisation pour s'assurer qu'il y a un seul / au début
$requestUri = '/' . ltrim($requestUri, '/');
// ex: /contact ou /


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
// Sinon
switch ($requestUri) {
    case '/':
    case '/index':
        $controller->index();
        break;
    case '/contact':
        $controller->contact();
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
    case '/myArticles':
        $controller->renderMyArticles();
        break;
    case '/myArticles/create':
        $controller->renderCreateArticle();
        break;
    case '/myArticles/storeArticle':
        $controller->storeArticle();
        break;
    default:
        http_response_code(404);
        echo "Page non trouvée - URI : $requestUri";
        break;
}

