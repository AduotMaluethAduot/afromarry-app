<?php
header('Content-Type: application/json');
require_once '../controllers/ExpertBookingController.php';

$method = $_SERVER['REQUEST_METHOD'];
$controller = new ExpertBookingController();

// Parse URL to get ID if present
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', trim($path, '/'));
$last = end($path_parts);
$id = (is_numeric($last)) ? (int)$last : null;

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
