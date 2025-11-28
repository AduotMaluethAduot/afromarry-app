<?php
require_once '../controllers/CulturalContentController.php';

$method = $_SERVER['REQUEST_METHOD'];
$controller = new CulturalContentController();

// Parse URL to get ID if present
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', trim($path, '/'));
$endpoint = isset($path_parts[2]) ? $path_parts[2] : null;
$id = isset($path_parts[3]) ? $path_parts[3] : null;
$action = isset($path_parts[4]) ? $path_parts[4] : null;

switch ($method) {
    case 'GET':
        switch ($endpoint) {
            case 'categories':
                $controller->getCategories();
                break;
            case 'articles':
                if ($id && !$action) {
                    // Get article by slug
                    $controller->getArticleBySlug($id);
                } else {
                    // Get articles with optional filters
                    $controller->getArticles();
                }
                break;
            default:
                $controller->getArticles();
                break;
        }
        break;
        
    case 'POST':
        switch ($endpoint) {
            case 'categories':
                $controller->createCategory();
                break;
            case 'articles':
                if ($id && $action === 'like') {
                    // Like/unlike article
                    $controller->likeArticle($id);
                } else {
                    // Create new article
                    $controller->createArticle();
                }
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid endpoint']);
                break;
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
?>