<?php
require_once 'BaseController.php';
require_once __DIR__ . '/../helpers/cache.php';
require_once __DIR__ . '/../helpers/performance.php';

class TribeController extends BaseController {
    
    public function index() {
        perf_start_timer('tribe_controller_index');
        perf_increment_counter('tribe_controller_calls');
        
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $region = isset($_GET['region']) ? $_GET['region'] : '';
        
        // Create cache key based on parameters
        $cache_key = 'tribes_' . md5($search . $region);
        
        // Try to get from cache first
        perf_start_timer('cache_lookup');
        $cached_tribes = cache_get($cache_key, 1800); // Cache for 30 minutes
        perf_stop_timer('cache_lookup');
        
        if ($cached_tribes !== null) {
            perf_increment_counter('cache_hits');
            perf_log_metrics('TribeController::index - Cache hit');
            $this->sendResponse(true, 'Tribes retrieved successfully (cached)', $cached_tribes);
            return;
        }
        
        perf_increment_counter('cache_misses');
        
        $query = "SELECT * FROM tribes WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $query .= " AND (name LIKE :search OR country LIKE :search OR region LIKE :search OR dowry_type LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        if (!empty($region)) {
            $query .= " AND region = :region";
            $params[':region'] = $region;
        }
        
        $query .= " ORDER BY name ASC";
        
        try {
            perf_start_timer('database_query');
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $tribes = $stmt->fetchAll();
            perf_stop_timer('database_query');
            
            // Decode JSON customs for each tribe
            foreach ($tribes as &$tribe) {
                $tribe['customs'] = json_decode($tribe['customs'], true);
            }
            
            // Cache the results
            cache_set($cache_key, $tribes);
            
            perf_log_metrics('TribeController::index - Cache miss, data retrieved');
            $this->sendResponse(true, 'Tribes retrieved successfully', $tribes);
        } catch (Exception $e) {
            perf_log_metrics('TribeController::index - Error: ' . $e->getMessage());
            $this->sendResponse(false, $e->getMessage(), null, 500);
        } finally {
            $elapsed = perf_stop_timer('tribe_controller_index');
            if ($elapsed !== null) {
                perf_increment_counter('total_execution_time', $elapsed * 1000); // Convert to milliseconds
            }
        }
    }
    
    public function store() {
        $this->requireAuth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $this->validateRequired($data, ['name', 'country', 'region', 'customs', 'dowry_type', 'dowry_details']);
            
            $query = "INSERT INTO tribes (name, country, region, customs, dowry_type, dowry_details, image) 
                     VALUES (:name, :country, :region, :customs, :dowry_type, :dowry_details, :image)";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':name' => $this->sanitizeInput($data['name']),
                ':country' => $this->sanitizeInput($data['country']),
                ':region' => $this->sanitizeInput($data['region']),
                ':customs' => json_encode($data['customs']),
                ':dowry_type' => $this->sanitizeInput($data['dowry_type']),
                ':dowry_details' => $this->sanitizeInput($data['dowry_details']),
                ':image' => $this->sanitizeInput($data['image'])
            ]);
            
            $this->sendResponse(true, 'Tribe added successfully', ['id' => $this->db->lastInsertId()]);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
    
    public function show($id) {
        try {
            $query = "SELECT * FROM tribes WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':id' => $id]);
            $tribe = $stmt->fetch();
            
            if (!$tribe) {
                $this->sendResponse(false, 'Tribe not found', null, 404);
            }
            
            $tribe['customs'] = json_decode($tribe['customs'], true);
            $this->sendResponse(true, 'Tribe retrieved successfully', $tribe);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
}
?>
