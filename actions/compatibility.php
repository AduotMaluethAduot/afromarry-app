<?php
require_once '../config/database.php';
require_once '../controllers/BaseController.php';

class CompatibilityController extends BaseController {
    
    public function store() {
        $this->requireAuth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $this->validateRequired($data, ['tribe_1_id', 'tribe_2_id', 'compatibility_score']);
            
            $query = "INSERT INTO compatibility_matches 
                     (user_id, tribe_1_id, tribe_2_id, compatibility_score, dowry_fusion, recommendations, challenges, solutions) 
                     VALUES (:user_id, :tribe_1_id, :tribe_2_id, :compatibility_score, :dowry_fusion, :recommendations, :challenges, :solutions)";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':user_id' => $this->user['id'],
                ':tribe_1_id' => $data['tribe_1_id'],
                ':tribe_2_id' => $data['tribe_2_id'],
                ':compatibility_score' => $data['compatibility_score'],
                ':dowry_fusion' => json_encode($data['dowry_fusion'] ?? []),
                ':recommendations' => is_array($data['recommendations']) ? implode("\n", $data['recommendations']) : ($data['recommendations'] ?? ''),
                ':challenges' => json_encode($data['challenges'] ?? []),
                ':solutions' => json_encode($data['solutions'] ?? [])
            ]);
            
            $match_id = $this->db->lastInsertId();
            
            $this->sendResponse(true, 'Compatibility match saved successfully', [
                'match_id' => $match_id
            ]);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
    
    public function index() {
        $this->requireAuth();
        
        try {
            $query = "SELECT cm.*, t1.name as tribe1_name, t2.name as tribe2_name 
                     FROM compatibility_matches cm
                     JOIN tribes t1 ON cm.tribe_1_id = t1.id
                     JOIN tribes t2 ON cm.tribe_2_id = t2.id
                     WHERE cm.user_id = :user_id
                     ORDER BY cm.created_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([':user_id' => $this->user['id']]);
            $matches = $stmt->fetchAll();
            
            // Decode JSON fields
            foreach ($matches as &$match) {
                $match['dowry_fusion'] = json_decode($match['dowry_fusion'], true);
                $match['challenges'] = json_decode($match['challenges'], true);
                $match['solutions'] = json_decode($match['solutions'], true);
            }
            
            $this->sendResponse(true, 'Compatibility matches retrieved successfully', $matches);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
}

// Handle request
$controller = new CompatibilityController();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $controller->store();
} elseif ($method === 'GET') {
    $controller->index();
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

