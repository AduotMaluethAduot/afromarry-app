<?php
require_once '../config/database.php';
require_once '../controllers/BaseController.php';

class UserContentController extends BaseController {
    
    public function store() {
        $this->requireAuth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $this->validateRequired($data, ['submission_type', 'title', 'content']);
            
            $query = "INSERT INTO user_content_submissions 
                     (user_id, submission_type, title, content, tribe_id, country, region, status) 
                     VALUES (:user_id, :submission_type, :title, :content, :tribe_id, :country, :region, 'pending')";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':user_id' => $this->user['id'],
                ':submission_type' => $data['submission_type'],
                ':title' => $data['title'],
                ':content' => $data['content'],
                ':tribe_id' => $data['tribe_id'] ?? null,
                ':country' => $data['country'] ?? null,
                ':region' => $data['region'] ?? null
            ]);
            
            $submission_id = $this->db->lastInsertId();
            
            $this->sendResponse(true, 'Content submitted successfully. It will be reviewed by our team.', [
                'submission_id' => $submission_id
            ]);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
    
    public function index() {
        $this->requireAuth();
        
        try {
            $query = "SELECT uc.*, t.name as tribe_name 
                     FROM user_content_submissions uc
                     LEFT JOIN tribes t ON uc.tribe_id = t.id
                     WHERE uc.user_id = :user_id
                     ORDER BY uc.created_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([':user_id' => $this->user['id']]);
            $submissions = $stmt->fetchAll();
            
            $this->sendResponse(true, 'Submissions retrieved successfully', $submissions);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
}

// Handle request
$controller = new UserContentController();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $controller->store();
} elseif ($method === 'GET') {
    $controller->index();
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

