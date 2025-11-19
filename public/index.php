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
// ex: /BLOG-PROJET-ISI1/public/contact

// On enlève tout jusqu'à /public
$requestUri = preg_replace('#^/.*/public#', '', $requestUri);
// ex: devient /contact

// Si vide → /
if ($requestUri === '' || $requestUri === false) {
    $requestUri = '/';
}

// Normalisation (au cas où) : s'assurer qu'il y a un seul / au début
$requestUri = '/' . ltrim($requestUri, '/');
// ex: /contact ou /



if (preg_match('#^/article/(.+)$#', $requestUri, $matches)) {
    $slug = $matches[1];
    $controller->article($slug);
    exit; // on s'arrête là, pas besoin de passer dans le switch
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
        if (preg_match('#^/article/(.+)$#', $requestUri, $matches)) {
            $slug = $matches[1];
            $controller->article($slug);
            break;
        }

        break;

    case '/auth':
        $controller->auth();
        break;

    default:
        http_response_code(404);
        echo "Page non trouvée - URI : $requestUri";
        break;
}

