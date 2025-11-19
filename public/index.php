<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/controller/BlogController.php';

// Twig
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../app/views');
$twig = new \Twig\Environment($loader, ['cache' => false]);

$basePath = dirname($_SERVER['SCRIPT_NAME']); // /BLOGMVC/public
$twig->addGlobal('base_url', $basePath . '/');

$controller = new BlogController($twig);

// Récupération de l'URL demandée
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// ➤ Normalisation automatique avec SCRIPT_NAME
$basePath = dirname($_SERVER['SCRIPT_NAME']); // => /BLOGMVC/public

// On retire le préfixe
if (strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

// Si vide → /
if ($requestUri === '' || $requestUri === false) {
    $requestUri = '/';
}

switch ($requestUri) {
    case '/':
    case '/index':
        $controller->index();
        break;

    case '/contact':
        $controller->contact();
        break;

    case '/article':
        $controller->article($_GET['id']);
        break;

    default:
        http_response_code(404);
        echo "Page non trouvée - URI : $requestUri";
        break;
}
