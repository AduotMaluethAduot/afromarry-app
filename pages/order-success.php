<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : null;
$user = getCurrentUser();

if (!$order_id) {
    redirect('index.php');
}

$database = new Database();
$db = $database->getConnection();

// Get order details
$query = "SELECT * FROM orders WHERE id = :order_id AND user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->execute([':order_id' => $order_id, ':user_id' => $user['id']]);
$order = $stmt->fetch();

if (!$order) {
    redirect('index.php');
}

// Get order items
$query = "SELECT oi.*, p.name, p.image 
         FROM order_items oi 
         JOIN products p ON oi.product_id = p.id 
         WHERE oi.order_id = :order_id";
$stmt = $db->prepare($query);
$stmt->execute([':order_id' => $order_id]);
$order_items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed - AfroMarry</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="../index.php">
                    <i class="fas fa-heart"></i>
                    <span>AfroMarry</span>
                </a>
            </div>
            <div class="nav-menu">
                <a href="../index.php" class="nav-link">Home</a>
                <a href="profile.php" class="nav-link">Profile</a>
                <a href="../auth/logout.php" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <div class="order-success-container">
        <div class="order-success-content">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            
            <h1>Order Confirmed!</h1>
            <p class="success-message">
                Thank you for your order! We've received your payment and will begin processing your items shortly.
            </p>
            
            <div class="order-details">
                <div class="order-info">
                    <h3>Order Information</h3>
                    <div class="info-row">
                        <span>Order Number:</span>
                        <span class="order-number">#<?php echo $order['id']; ?></span>
                    </div>
                    <div class="info-row">
                        <span>Order Date:</span>
                        <span><?php echo date('F j, Y', strtotime($order['created_at'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span>Total Amount:</span>
                        <span class="total-amount">$ <?php echo number_format($order['total_amount']); ?></span>
                    </div>
                    <div class="info-row">
                        <span>Payment Method:</span>
                        <span><?php echo ucfirst($order['payment_method']); ?></span>
                    </div>
                    <div class="info-row">
                        <span>Status:</span>
                        <span class="status <?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span>
                    </div>
                </div>
                
                <div class="order-items">
                    <h3>Order Items</h3>
                    <?php foreach ($order_items as $item): ?>
                    <div class="order-item">
                        <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="item-image">
                        <div class="item-details">
                            <h4><?php echo $item['name']; ?></h4>
                            <p>Quantity: <?php echo $item['quantity']; ?></p>
                            <p class="item-price">$ <?php echo number_format($item['price'] * $item['quantity']); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="next-steps">
                <h3>What's Next?</h3>
                <div class="steps">
                    <div class="step">
                        <div class="step-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="step-content">
                            <h4>Processing</h4>
                            <p>We're preparing your items for shipment</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-icon">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <div class="step-content">
                            <h4>Shipping</h4>
                            <p>Your items will be shipped within 2-3 business days</p>
                        </div>
                    </div>
                    <div class="step">
                        <div class="step-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <div class="step-content">
                            <h4>Delivery</h4>
                            <p>You'll receive your items within 5-7 business days</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="../index.php" class="btn-primary">
                    <i class="fas fa-home"></i>
                    Continue Shopping
                </a>
                <a href="profile.php" class="btn-secondary">
                    <i class="fas fa-user"></i>
                    View My Orders
                </a>
            </div>
        </div>
    </div>

    <style>
        .order-success-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: linear-gradient(135deg, #f8fafc, #e0e7ff);
        }
        
        .order-success-content {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            max-width: 800px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .success-icon {
            font-size: 4rem;
            color: #10B981;
            margin-bottom: 1rem;
        }
        
        .order-success-content h1 {
            color: #1f2937;
            margin-bottom: 1rem;
        }
        
        .success-message {
            color: #6b7280;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }
        
        .order-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
            text-align: left;
        }
        
        .order-info, .order-items {
            background: #f9fafb;
            padding: 1.5rem;
            border-radius: 15px;
        }
        
        .order-info h3, .order-items h3 {
            color: #1f2937;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .order-number {
            font-weight: 700;
            color: #8B5CF6;
        }
        
        .total-amount {
            font-weight: 700;
            color: #10B981;
        }
        
        .status {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status.paid {
            background: #d1fae5;
            color: #065f46;
        }
        
        .order-item {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 1rem;
            background: white;
            border-radius: 10px;
        }
        
        .item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .item-details h4 {
            margin-bottom: 0.5rem;
            color: #1f2937;
        }
        
        .item-price {
            font-weight: 700;
            color: #8B5CF6;
        }
        
        .next-steps {
            margin-bottom: 2rem;
        }
        
        .next-steps h3 {
            color: #1f2937;
            margin-bottom: 1.5rem;
        }
        
        .steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .step {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 10px;
        }
        
        .step-icon {
            width: 40px;
            height: 40px;
            background: #8B5CF6;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .step-content h4 {
            margin-bottom: 0.3rem;
            color: #1f2937;
        }
        
        .step-content p {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        @media (max-width: 768px) {
            .order-details {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</body>
</html>
