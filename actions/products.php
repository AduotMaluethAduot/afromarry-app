<?php
require_once '../controllers/ProductController.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$controller = new ProductController();

// Parse URL to get ID if present
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', trim($path, '/'));
$last = end($path_parts);
$id = (is_numeric($last) ? $last : null);

switch ($method) {
    case 'GET':
        if ($id) {
            $controller->show($id);
        } else {
            $controller->index();
        }
        break;
    case 'POST':
        $controller->store();
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
?>
