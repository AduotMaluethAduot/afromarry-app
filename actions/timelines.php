<?php
require_once '../config/database.php';
require_once '../controllers/BaseController.php';

class TimelineController extends BaseController {
    
    public function store() {
        $this->requireAuth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $this->validateRequired($data, ['title', 'wedding_date']);
            
            // Check if adding milestone
            if (isset($data['action']) && $data['action'] === 'add_milestone') {
                return $this->addMilestone($data);
            }
            
            $query = "INSERT INTO wedding_timelines (user_id, title, wedding_date, tribe_1_id, tribe_2_id, timeline_data) 
                     VALUES (:user_id, :title, :wedding_date, :tribe_1_id, :tribe_2_id, :timeline_data)";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':user_id' => $this->user['id'],
                ':title' => $data['title'],
                ':wedding_date' => $data['wedding_date'],
                ':tribe_1_id' => $data['tribe_1_id'] ?? null,
                ':tribe_2_id' => $data['tribe_2_id'] ?? null,
                ':timeline_data' => json_encode(['milestones' => []])
            ]);
            
            $timeline_id = $this->db->lastInsertId();
            
            $this->sendResponse(true, 'Timeline created successfully', [
                'timeline_id' => $timeline_id
            ]);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
    
    private function addMilestone($data) {
        $this->validateRequired($data, ['timeline_id', 'title', 'due_date']);
        
        $query = "INSERT INTO timeline_milestones (timeline_id, title, description, due_date, category, tribe_id) 
                 VALUES (:timeline_id, :title, :description, :due_date, :category, :tribe_id)";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':timeline_id' => $data['timeline_id'],
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':due_date' => $data['due_date'],
            ':category' => $data['category'] ?? 'other',
            ':tribe_id' => $data['tribe_id'] ?? null
        ]);
        
        $this->sendResponse(true, 'Milestone added successfully');
    }
    
    public function update() {
        $this->requireAuth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            if (isset($data['action']) && $data['action'] === 'toggle_milestone') {
                $query = "UPDATE timeline_milestones 
                         SET is_completed = :is_completed, completed_at = :completed_at
                         WHERE id = :milestone_id";
                
                $stmt = $this->db->prepare($query);
                $stmt->execute([
                    ':milestone_id' => $data['milestone_id'],
                    ':is_completed' => $data['is_completed'] ? 1 : 0,
                    ':completed_at' => $data['is_completed'] ? date('Y-m-d H:i:s') : null
                ]);
                
                $this->sendResponse(true, 'Milestone updated successfully');
            } elseif (isset($data['action']) && $data['action'] === 'update_timeline') {
                $query = "UPDATE wedding_timelines 
                         SET title = :title
                         WHERE id = :timeline_id AND user_id = :user_id";
                
                $stmt = $this->db->prepare($query);
                $stmt->execute([
                    ':timeline_id' => $data['timeline_id'],
                    ':title' => $data['title'],
                    ':user_id' => $this->user['id']
                ]);
                
                $this->sendResponse(true, 'Timeline updated successfully');
            } else {
                throw new Exception('Invalid action');
            }
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
    
    public function index() {
        $this->requireAuth();
        
        try {
            $query = "SELECT wt.*, t1.name as tribe1_name, t2.name as tribe2_name 
                     FROM wedding_timelines wt
                     LEFT JOIN tribes t1 ON wt.tribe_1_id = t1.id
                     LEFT JOIN tribes t2 ON wt.tribe_2_id = t2.id
                     WHERE wt.user_id = :user_id AND wt.is_active = TRUE
                     ORDER BY wt.wedding_date ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([':user_id' => $this->user['id']]);
            $timelines = $stmt->fetchAll();
            
            // Get milestones for each timeline
            foreach ($timelines as &$timeline) {
                $milestoneQuery = "SELECT * FROM timeline_milestones WHERE timeline_id = :timeline_id ORDER BY due_date ASC";
                $milestoneStmt = $this->db->prepare($milestoneQuery);
                $milestoneStmt->execute([':timeline_id' => $timeline['id']]);
                $timeline['milestones'] = $milestoneStmt->fetchAll();
            }
            
            $this->sendResponse(true, 'Timelines retrieved successfully', $timelines);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
}

// Handle request
$controller = new TimelineController();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $controller->store();
} elseif ($method === 'GET') {
    $controller->index();
} elseif ($method === 'PUT') {
    $controller->update();
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

