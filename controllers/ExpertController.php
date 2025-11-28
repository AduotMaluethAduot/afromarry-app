<?php
require_once 'BaseController.php';

class ExpertController extends BaseController {
    
    public function index() {
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $tribe = isset($_GET['tribe']) ? $_GET['tribe'] : '';
        
        $query = "SELECT * FROM experts WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $query .= " AND (name LIKE :search OR specialization LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        if (!empty($tribe)) {
            $query .= " AND tribe = :tribe";
            $params[':tribe'] = $tribe;
        }
        
        $query .= " ORDER BY rating DESC, name ASC";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $experts = $stmt->fetchAll();
            
            // Decode JSON languages for each expert
            foreach ($experts as &$expert) {
                $expert['languages'] = json_decode($expert['languages'], true);
            }
            
            $this->sendResponse(true, 'Experts retrieved successfully', $experts);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
    
    public function store() {
        $this->requireAuth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $this->validateRequired($data, ['name', 'tribe', 'specialization', 'languages', 'hourly_rate']);
            
            $query = "INSERT INTO experts (name, tribe, specialization, languages, rating, hourly_rate, image, availability) 
                     VALUES (:name, :tribe, :specialization, :languages, :rating, :hourly_rate, :image, :availability)";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':name' => $this->sanitizeInput($data['name']),
                ':tribe' => $this->sanitizeInput($data['tribe']),
                ':specialization' => $this->sanitizeInput($data['specialization']),
                ':languages' => json_encode($data['languages']),
                ':rating' => $data['rating'] ?? 0.00,
                ':hourly_rate' => $data['hourly_rate'],
                ':image' => $this->sanitizeInput($data['image']),
                ':availability' => $this->sanitizeInput($data['availability'])
            ]);
            
            $this->sendResponse(true, 'Expert added successfully', ['id' => $this->db->lastInsertId()]);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
    
    public function show($id) {
        try {
            $query = "SELECT * FROM experts WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':id' => $id]);
            $expert = $stmt->fetch();
            
            if (!$expert) {
                $this->sendResponse(false, 'Expert not found', null, 404);
            }
            
            $expert['languages'] = json_decode($expert['languages'], true);
            $this->sendResponse(true, 'Expert retrieved successfully', $expert);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
}
?>
