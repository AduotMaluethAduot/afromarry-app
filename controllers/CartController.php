<?php
require_once 'BaseController.php';

class CartController extends BaseController {
    
    public function index() {
        $this->requireAuth();
        
        try {
            // Get cart items with product details
            $query = "SELECT c.*, p.name, p.price, p.currency, p.image, p.description 
                     FROM cart c 
                     JOIN products p ON c.product_id = p.id 
                     WHERE c.user_id = :user_id 
                     ORDER BY c.created_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([':user_id' => $this->user['id']]);
            $cart_items = $stmt->fetchAll();
            
            // Calculate total
            $total = 0;
            foreach ($cart_items as $item) {
                $total += $item['price'] * $item['quantity'];
            }
            
            $this->sendResponse(true, 'Cart retrieved successfully', [
                'items' => $cart_items,
                'total' => $total,
                'count' => count($cart_items)
            ]);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
    
    public function store() {
        $this->requireAuth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $this->validateRequired($data, ['product_id']);
            
            $product_id = $data['product_id'];
            $quantity = $data['quantity'] ?? 1;
            
            // Check if item already exists in cart
            $query = "SELECT * FROM cart WHERE user_id = :user_id AND product_id = :product_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':user_id' => $this->user['id'], ':product_id' => $product_id]);
            $existing_item = $stmt->fetch();
            
            if ($existing_item) {
                // Update quantity
                $query = "UPDATE cart SET quantity = quantity + :quantity WHERE id = :id";
                $stmt = $this->db->prepare($query);
                $stmt->execute([':quantity' => $quantity, ':id' => $existing_item['id']]);
            } else {
                // Add new item
                $query = "INSERT INTO cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, :quantity)";
                $stmt = $this->db->prepare($query);
                $stmt->execute([':user_id' => $this->user['id'], ':product_id' => $product_id, ':quantity' => $quantity]);
            }
            
            $this->sendResponse(true, 'Item added to cart');
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
    
    public function update($id) {
        $this->requireAuth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        $quantity = $data['quantity'];
        
        try {
            if ($quantity <= 0) {
                // Remove item
                $query = "DELETE FROM cart WHERE id = :id AND user_id = :user_id";
                $stmt = $this->db->prepare($query);
                $stmt->execute([':id' => $id, ':user_id' => $this->user['id']]);
            } else {
                // Update quantity
                $query = "UPDATE cart SET quantity = :quantity WHERE id = :id AND user_id = :user_id";
                $stmt = $this->db->prepare($query);
                $stmt->execute([':quantity' => $quantity, ':id' => $id, ':user_id' => $this->user['id']]);
            }
            
            $this->sendResponse(true, 'Cart updated');
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
    
    public function destroy($id) {
        $this->requireAuth();
        
        try {
            $query = "DELETE FROM cart WHERE id = :id AND user_id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':id' => $id, ':user_id' => $this->user['id']]);
            
            $this->sendResponse(true, 'Item removed from cart');
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
}
?>
