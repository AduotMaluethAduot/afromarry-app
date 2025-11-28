<?php
require_once 'BaseController.php';

class OrderController extends BaseController {
    
    public function index() {
        $this->requireAuth();
        
        $order_id = isset($_GET['order_id']) ? $_GET['order_id'] : null;
        
        try {
            if ($order_id) {
                // Get specific order with items
                $query = "SELECT o.*, oi.*, p.name as product_name, p.image as product_image 
                         FROM orders o 
                         JOIN order_items oi ON o.id = oi.order_id 
                         JOIN products p ON oi.product_id = p.id 
                         WHERE o.id = :order_id AND o.user_id = :user_id";
                
                $stmt = $this->db->prepare($query);
                $stmt->execute([':order_id' => $order_id, ':user_id' => $this->user['id']]);
                $order_items = $stmt->fetchAll();
                
                if (empty($order_items)) {
                    $this->sendResponse(false, 'Order not found', null, 404);
                }
                
                // Get order details
                $query = "SELECT * FROM orders WHERE id = :order_id AND user_id = :user_id";
                $stmt = $this->db->prepare($query);
                $stmt->execute([':order_id' => $order_id, ':user_id' => $this->user['id']]);
                $order = $stmt->fetch();
                
                $this->sendResponse(true, 'Order retrieved successfully', [
                    'order' => $order,
                    'items' => $order_items
                ]);
            } else {
                // Get all orders for user
                $query = "SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC";
                $stmt = $this->db->prepare($query);
                $stmt->execute([':user_id' => $this->user['id']]);
                $orders = $stmt->fetchAll();
                
                $this->sendResponse(true, 'Orders retrieved successfully', $orders);
            }
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
    
    public function store() {
        // Start output buffering to prevent any accidental output
        ob_start();
        
        try {
            $this->requireAuth();
            
            // Clear any output that might have been generated
            ob_clean();
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Check if JSON was parsed correctly
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON data: ' . json_last_error_msg());
            }
            
            $this->validateRequired($data, ['total_amount', 'items']);
            
            // Start transaction
            $this->db->beginTransaction();
            
            // Create order (commission will be calculated after items are added)
            $order_reference = $this->generateOrderReference();
            
            // Check if commission columns exist, use appropriate query
            $columns = "user_id, total_amount, currency, status, payment_method, payment_reference, shipping_address";
            $values = ":user_id, :total_amount, :currency, :status, :payment_method, :payment_reference, :shipping_address";
            
            try {
                $checkCol = "SHOW COLUMNS FROM orders LIKE 'platform_commission'";
                $colResult = $this->db->query($checkCol);
                if ($colResult->rowCount() > 0) {
                    $columns .= ", platform_commission, vendor_amount";
                    $values .= ", :platform_commission, :vendor_amount";
                }
            } catch (PDOException $e) {
                // Columns don't exist yet, use basic query
            }
            
            $query = "INSERT INTO orders ($columns) VALUES ($values)";
            
            $params = [
                ':user_id' => $this->user['id'],
                ':total_amount' => $data['total_amount'],
                ':currency' => $data['currency'] ?? 'USD',
                ':status' => 'pending',
                ':payment_method' => $data['payment_method'],
                ':payment_reference' => $data['payment_reference'],
                ':shipping_address' => $data['shipping_address']
            ];
            
            // Add commission fields if columns exist (will be updated after items)
            if (strpos($columns, 'platform_commission') !== false) {
                $params[':platform_commission'] = 0; // Will be updated
                $params[':vendor_amount'] = 0; // Will be updated
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            $order_id = $this->db->lastInsertId();
            
            // Check if order requires escrow (high-value items)
            $escrow_threshold = 50000.00; // $50,000 threshold
            $requires_escrow = $data['total_amount'] >= $escrow_threshold;
            $escrow_id = null;
            
            if ($requires_escrow) {
                // Create escrow transaction
                $query = "INSERT INTO escrow_transactions (order_id, amount, currency, status, is_high_value, threshold_amount) 
                         VALUES (:order_id, :amount, :currency, 'held', TRUE, :threshold_amount)";
                $stmt = $this->db->prepare($query);
                $stmt->execute([
                    ':order_id' => $order_id,
                    ':amount' => $data['total_amount'],
                    ':currency' => $data['currency'] ?? 'USD',
                    ':threshold_amount' => $escrow_threshold
                ]);
                $escrow_id = $this->db->lastInsertId();
                
                // Update order with escrow info
                $query = "UPDATE orders SET requires_escrow = TRUE, escrow_id = :escrow_id WHERE id = :order_id";
                $stmt = $this->db->prepare($query);
                $stmt->execute([':escrow_id' => $escrow_id, ':order_id' => $order_id]);
            }
            
            // Calculate total commission and vendor amounts
            $total_platform_commission = 0;
            $total_vendor_amount = 0;
            $marketplace_commission_rate = 5.00; // 5% platform commission
            
            // Add order items with commission calculation
            foreach ($data['items'] as $item) {
                $item_total = $item['price'] * $item['quantity'];
                $item_commission = ($item_total * $marketplace_commission_rate) / 100;
                $item_vendor_amount = $item_total - $item_commission;
                
                $total_platform_commission += $item_commission;
                $total_vendor_amount += $item_vendor_amount;
                
                // Get product vendor_id
                $query = "SELECT vendor_id FROM products WHERE id = :product_id";
                $stmt = $this->db->prepare($query);
                $stmt->execute([':product_id' => $item['product_id']]);
                $product = $stmt->fetch();
                $vendor_id = $product['vendor_id'] ?? null;
                
                $query = "INSERT INTO order_items (order_id, product_id, quantity, price, platform_commission, vendor_amount) 
                         VALUES (:order_id, :product_id, :quantity, :price, :platform_commission, :vendor_amount)";
                
                $stmt = $this->db->prepare($query);
                $stmt->execute([
                    ':order_id' => $order_id,
                    ':product_id' => $item['product_id'],
                    ':quantity' => $item['quantity'],
                    ':price' => $item['price'],
                    ':platform_commission' => $item_commission,
                    ':vendor_amount' => $item_vendor_amount
                ]);
                
                // Check if product is digital and create download record
                $query = "SELECT p.id, p.is_digital, dp.id as digital_product_id 
                         FROM products p 
                         LEFT JOIN digital_products dp ON p.digital_product_id = dp.id
                         WHERE p.id = :product_id";
                $stmt = $this->db->prepare($query);
                $stmt->execute([':product_id' => $item['product_id']]);
                $product = $stmt->fetch();
                
                if ($product && $product['is_digital'] && $product['digital_product_id']) {
                    // Create download token
                    $download_token = bin2hex(random_bytes(32));
                    $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));
                    
                    $query = "INSERT INTO digital_product_downloads 
                             (order_id, digital_product_id, user_id, download_token, expires_at) 
                             VALUES (:order_id, :digital_product_id, :user_id, :download_token, :expires_at)";
                    $stmt = $this->db->prepare($query);
                    $stmt->execute([
                        ':order_id' => $order_id,
                        ':digital_product_id' => $product['digital_product_id'],
                        ':user_id' => $this->user['id'],
                        ':download_token' => $download_token,
                        ':expires_at' => $expires_at
                    ]);
                }
            }
            
            // Update order with calculated commission totals
            if ($total_platform_commission > 0) {
                try {
                    $updateQuery = "UPDATE orders SET platform_commission = :commission, vendor_amount = :vendor_amount WHERE id = :order_id";
                    $updateStmt = $this->db->prepare($updateQuery);
                    $updateStmt->execute([
                        ':commission' => $total_platform_commission,
                        ':vendor_amount' => $total_vendor_amount,
                        ':order_id' => $order_id
                    ]);
                } catch (PDOException $e) {
                    // Commission columns might not exist yet, skip update
                }
            }
            
            // Clear cart
            $query = "DELETE FROM cart WHERE user_id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':user_id' => $this->user['id']]);
            
            $this->db->commit();
            
            // Clear output buffer before sending response
            ob_clean();
            
            $this->sendResponse(true, 'Order created successfully', [
                'order_id' => $order_id,
                'order_reference' => $order_reference
            ]);
            
        } catch (Exception $e) {
            // Clear output buffer before sending error response
            ob_clean();
            $this->db->rollback();
            $this->sendResponse(false, $e->getMessage(), null, 500);
        } finally {
            // End output buffering
            ob_end_flush();
        }
    }
    
    public function update($id) {
        $this->requireAuth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        $status = $data['status'];
        
        try {
            $query = "UPDATE orders SET status = :status WHERE id = :order_id AND user_id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':status' => $status, ':order_id' => $id, ':user_id' => $this->user['id']]);
            
            $this->sendResponse(true, 'Order status updated');
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
    
    private function generateOrderReference() {
        return 'AFM' . date('Ymd') . rand(1000, 9999);
    }
}
?>
