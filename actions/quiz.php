<?php
require_once '../config/database.php';
require_once '../controllers/BaseController.php';

class QuizController extends BaseController {
    
    public function store() {
        $this->requireAuth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $this->validateRequired($data, ['quiz_type', 'answers']);
            
            $query = "INSERT INTO quiz_results (user_id, quiz_type, answers, result_tribe_id, result_data, score) 
                     VALUES (:user_id, :quiz_type, :answers, :result_tribe_id, :result_data, :score)";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':user_id' => $this->user['id'],
                ':quiz_type' => $data['quiz_type'],
                ':answers' => json_encode($data['answers']),
                ':result_tribe_id' => $data['result_tribe_id'] ?? null,
                ':result_data' => json_encode($data['result_data'] ?? []),
                ':score' => $data['score'] ?? null
            ]);
            
            $result_id = $this->db->lastInsertId();
            
            $this->sendResponse(true, 'Quiz results saved successfully', [
                'result_id' => $result_id
            ]);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
    
    public function index() {
        $this->requireAuth();
        
        try {
            $query = "SELECT qr.*, t.name as tribe_name, t.country, t.region 
                     FROM quiz_results qr
                     LEFT JOIN tribes t ON qr.result_tribe_id = t.id
                     WHERE qr.user_id = :user_id
                     ORDER BY qr.created_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([':user_id' => $this->user['id']]);
            $results = $stmt->fetchAll();
            
            // Decode JSON fields
            foreach ($results as &$result) {
                $result['answers'] = json_decode($result['answers'], true);
                $result['result_data'] = json_decode($result['result_data'], true);
            }
            
            $this->sendResponse(true, 'Quiz results retrieved successfully', $results);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
}

// Handle request
$controller = new QuizController();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $controller->store();
} elseif ($method === 'GET') {
    $controller->index();
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

