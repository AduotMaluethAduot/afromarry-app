<?php
// Start output buffering to prevent any accidental output
ob_start();

// Start session and load database config first
require_once '../config/database.php'; // This starts the session
require_once '../controllers/CartController.php';

// Clear any output that might have been generated
ob_clean();

// Set JSON header early
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$controller = new CartController();

// Parse URL to get ID if present
header('Content-Type: application/json');
// Derive ID from last path segment if present and numeric
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', trim($path, '/'));
$last = end($path_parts);
$id = (is_numeric($last) ? $last : null);

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
    case 'DELETE':
        if ($id) {
            $controller->destroy($id);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID required for delete']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
?>
