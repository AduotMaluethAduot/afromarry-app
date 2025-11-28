<?php
require_once '../controllers/OrderController.php';

$method = $_SERVER['REQUEST_METHOD'];
$controller = new OrderController();

// Parse URL to get ID if present
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', trim($path, '/'));
$id = isset($path_parts[2]) ? $path_parts[2] : null;

switch ($method) {
    case 'GET':
        $controller->index();
        break;
    case 'POST':
        $controller->store();
        break;
    case 'PUT':
        if ($id) {
            $controller->update($id);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID required for update']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
?>
