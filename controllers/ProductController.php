<?php
require_once 'BaseController.php';
require_once __DIR__ . '/../helpers/uploads.php';
require_once __DIR__ . '/../helpers/cache.php';
require_once __DIR__ . '/../helpers/performance.php';

class ProductController extends BaseController {
    
    public function index() {
        perf_start_timer('product_controller_index');
        perf_increment_counter('product_controller_calls');
        
        $category = isset($_GET['category']) ? $_GET['category'] : '';
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        
        // Create cache key based on parameters
        $cache_key = 'products_' . md5($category . $search);
        
        // Try to get from cache first
        perf_start_timer('cache_lookup');
        $cached_products = cache_get($cache_key, 1800); // Cache for 30 minutes
        perf_stop_timer('cache_lookup');
        
        if ($cached_products !== null) {
            perf_increment_counter('cache_hits');
            perf_log_metrics('ProductController::index - Cache hit');
            $this->sendResponse(true, 'Products retrieved successfully (cached)', $cached_products);
            return;
        }
        
        perf_increment_counter('cache_misses');
        
        $query = "SELECT * FROM products WHERE 1=1";
        $params = [];
        
        if (!empty($category) && $category !== 'all') {
            $query .= " AND category = :category";
            $params[':category'] = $category;
        }
        
        if (!empty($search)) {
            $query .= " AND (name LIKE :search OR description LIKE :search OR tribe LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        $query .= " ORDER BY name ASC";
        
        try {
            perf_start_timer('database_query');
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $products = $stmt->fetchAll();
            perf_stop_timer('database_query');
            
            // Cache the results
            cache_set($cache_key, $products);
            
            perf_log_metrics('ProductController::index - Cache miss, data retrieved');
            $this->sendResponse(true, 'Products retrieved successfully', $products);
        } catch (Exception $e) {
            perf_log_metrics('ProductController::index - Error: ' . $e->getMessage());
            $this->sendResponse(false, $e->getMessage(), null, 500);
        } finally {
            $elapsed = perf_stop_timer('product_controller_index');
            if ($elapsed !== null) {
                perf_increment_counter('total_execution_time', $elapsed * 1000); // Convert to milliseconds
            }
        }
    }
    
    public function store() {
        $this->requireAuth();
        
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
        } else {
            $data = $_POST;
        }
        
        try {
            $this->validateRequired($data, ['name', 'price', 'category', 'tribe', 'description']);
            
            $image_from_payload = isset($data['image']) ? $this->sanitizeInput($data['image']) : null;
            $has_upload = has_uploaded_file_field('image');
            
            if (!$has_upload && empty($image_from_payload)) {
                throw new Exception('Product image is required.');
            }
            
            // Check if vendor_id column exists
            $columns = "name, price, currency, category, tribe, image, description, stock_quantity";
            $placeholders = ":name, :price, :currency, :category, :tribe, :image, :description, :stock_quantity";
            $params = [
                ':name' => $this->sanitizeInput($data['name']),
                ':price' => $data['price'],
                ':currency' => $data['currency'] ?? 'USD',
                ':category' => $this->sanitizeInput($data['category']),
                ':tribe' => $this->sanitizeInput($data['tribe']),
                ':image' => null, // Will be set after upload
                ':description' => $this->sanitizeInput($data['description']),
                ':stock_quantity' => $data['stock_quantity'] ?? 0
            ];
            
            try {
                $checkCol = "SHOW COLUMNS FROM products LIKE 'vendor_id'";
                $colResult = $this->db->query($checkCol);
                if ($colResult->rowCount() > 0) {
                    $columns = "vendor_id, " . $columns;
                    $placeholders = ":vendor_id, " . $placeholders;
                    $params[':vendor_id'] = $this->user['id']; // Set vendor to current user
                }
            } catch (PDOException $e) {
                // Column doesn't exist yet, continue without it
            }
            
            $query = "INSERT INTO products ($columns) VALUES ($placeholders)";
            
            $image_placeholder = $has_upload ? null : $image_from_payload;
            $params[':image'] = $image_placeholder;
            
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            $product_id = $this->db->lastInsertId();
            $final_image_path = $image_placeholder;
            
            if ($has_upload) {
                $final_image_path = upload_product_image($_FILES['image'], $this->user['id'], $product_id);
                
                $updateStmt = $this->db->prepare("UPDATE products SET image = :image WHERE id = :id");
                $updateStmt->execute([
                    ':image' => $final_image_path,
                    ':id' => $product_id
                ]);
            }
            
            $this->db->commit();
            
            $this->sendResponse(true, 'Product added successfully', [
                'id' => $product_id,
                'image' => $final_image_path
            ]);
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
    
    public function show($id) {
        try {
            $query = "SELECT * FROM products WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':id' => $id]);
            $product = $stmt->fetch();
            
            if (!$product) {
                $this->sendResponse(false, 'Product not found', null, 404);
            }
            
            $this->sendResponse(true, 'Product retrieved successfully', $product);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
}
?>
