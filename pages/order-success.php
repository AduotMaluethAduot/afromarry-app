<?php
require_once '../config/database.php';
require_once '../config/paths.php';

requireAuth();
$user = getCurrentUser();
$database = new Database();
$db = $database->getConnection();

// Get order ID from URL
$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    redirect('dashboard.php');
}

// Get order details
$query = "SELECT * FROM orders WHERE id = :order_id AND user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->execute([':order_id' => $order_id, ':user_id' => $user['id']]);
$order = $stmt->fetch();

if (!$order) {
    redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success - AfroMarry</title>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="<?php echo base_url('index.php'); ?>">
                    <i class="fas fa-heart"></i>
                    <span>AfroMarry</span>
                </a>
            </div>
            <div class="nav-menu">
                <a href="<?php echo page_url('dashboard.php'); ?>" class="nav-link">Dashboard</a>
                <a href="<?php echo page_url('orders.php'); ?>" class="nav-link">My Orders</a>
                <a href="<?php echo auth_url('logout.php'); ?>" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <div class="order-success-container">
        <div class="success-content">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1>Order Placed Successfully!</h1>
            <p>Thank you for your purchase. Your order has been placed successfully.</p>
            
            <div class="order-details">
                <h3>Order Summary</h3>
                <div class="detail-item">
                    <span>Order ID:</span>
                    <span>#<?php echo $order['id']; ?></span>
                </div>
                <div class="detail-item">
                    <span>Order Reference:</span>
                    <span><?php echo $order['order_reference']; ?></span>
                </div>
                <div class="detail-item">
                    <span>Total Amount:</span>
                    <span>$ <?php echo number_format($order['total_amount']); ?></span>
                </div>
                <div class="detail-item">
                    <span>Payment Status:</span>
                    <span class="status paid">Paid</span>
                </div>
                <div class="detail-item">
                    <span>Order Date:</span>
                    <span><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></span>
                </div>
            </div>
            
            <div class="success-actions">
                <a href="<?php echo page_url('orders.php'); ?>" class="btn-primary">
                    <i class="fas fa-shopping-bag"></i>
                    View My Orders
                </a>
                <a href="<?php echo base_url('index.php'); ?>" class="btn-secondary">
                    <i class="fas fa-home"></i>
                    Continue Shopping
                </a>
            </div>
        </div>
    </div>

    <style>
        .order-success-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .success-content {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .success-icon {
            font-size: 4rem;
            color: #10B981;
            margin-bottom: 1rem;
        }
        
        .success-content h1 {
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        
        .success-content p {
            color: #6b7280;
            margin-bottom: 2rem;
        }
        
        .order-details {
            background: #f8fafc;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: left;
        }
        
        .order-details h3 {
            color: #1f2937;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-item span:first-child {
            color: #6b7280;
        }
        
        .detail-item span:last-child {
            font-weight: 600;
            color: #1f2937;
        }
        
        .status {
            padding: 0.25rem 0.5rem;
            border-radius: 5px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status.paid {
            background: #d1fae5;
            color: #065f46;
        }
        
        .success-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-primary, .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #8B5CF6, #EC4899);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 92, 246, 0.3);
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        @media (max-width: 768px) {
            .order-success-container {
                margin: 1rem;
            }
            
            .success-content {
                padding: 1rem;
            }
            
            .success-actions {
                flex-direction: column;
            }
        }
    </style>
</body>
</html>