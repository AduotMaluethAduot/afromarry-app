<?php
require_once '../controllers/PostController.php';

$method = $_SERVER['REQUEST_METHOD'];
$controller = new PostController();

// Parse URL to get ID if present
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', trim($path, '/'));
$id = isset($path_parts[2]) ? $path_parts[2] : null;
$action = isset($path_parts[3]) ? $path_parts[3] : null;

switch ($method) {
    case 'GET':
        if ($id) {
            if ($action === 'comments') {
                // Get comments for a post
                $controller->show($id);
            } else {
                // Get specific post
                $controller->show($id);
            }
        } else {
            // Get all posts
            $controller->index();
        }
        break;
        
    case 'POST':
        if ($id && $action === 'comments') {
            // Add comment to post
            $controller->addComment($id);
        } elseif ($id && $action === 'like') {
            // Like/unlike post
            $controller->likePost($id);
        } elseif ($id && $action === 'comments' && isset($path_parts[4]) && $path_parts[4] === 'like') {
            // Like/unlike comment
            $controller->likeComment($path_parts[3]);
        } else {
            // Create new post
            $controller->store();
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
?>