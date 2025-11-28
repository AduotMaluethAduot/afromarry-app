<?php
require_once '../config/database.php';

requireAuth();
$user = getCurrentUser();
$database = new Database();
$db = $database->getConnection();

// Get user statistics
$stats = [];

// Get order count
$query = "SELECT COUNT(*) as order_count FROM orders WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->execute([':user_id' => $user['id']]);
$stats['orders'] = $stmt->fetch()['order_count'];

// Get total spent
$query = "SELECT SUM(total_amount) as total_spent FROM orders WHERE user_id = :user_id AND status = 'paid'";
$stmt = $db->prepare($query);
$stmt->execute([':user_id' => $user['id']]);
$stats['total_spent'] = $stmt->fetch()['total_spent'] ?? 0;

// Get expert bookings count
$query = "SELECT COUNT(*) as booking_count FROM expert_bookings WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->execute([':user_id' => $user['id']]);
$stats['expert_bookings'] = $stmt->fetch()['booking_count'];

// Get premium expiration date (if premium)
$premium_expires = null;
if ($user['is_premium'] ?? false) {
    $premium_expires = $user['premium_expires_at'] ?? null;
}

// Get recent orders
$query = "SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute([':user_id' => $user['id']]);
$recent_orders = $stmt->fetchAll();

// Get notifications
$query = "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute([':user_id' => $user['id']]);
$notifications = $stmt->fetchAll();

// Get featured products for shopping
$query = "SELECT * FROM products ORDER BY created_at DESC LIMIT 8";
$stmt = $db->prepare($query);
$stmt->execute();
$featured_products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - AfroMarry</title>
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
                <a href="<?php echo base_url('index.php'); ?>" class="nav-link">Home</a>
                <a href="<?php echo page_url('cart.php'); ?>" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    Cart
                </a>
                <a href="<?php echo auth_url('logout.php'); ?>" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-sidebar">
            <div class="user-profile">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <h3><?php echo $user['full_name']; ?></h3>
                <p><?php echo $user['email']; ?></p>
                <div class="premium-badge <?php echo ($user['is_premium'] ?? false) ? 'active' : ''; ?>">
                    <?php echo ($user['is_premium'] ?? false) ? 'Premium Member' : 'Free Member'; ?>
                </div>
                <?php if (($user['is_premium'] ?? false) && $premium_expires): ?>
                    <p style="font-size: 0.8rem; color: #6b7280; margin-top: 0.5rem;">
                        Expires: <?php echo date('M j, Y', strtotime($premium_expires)); ?>
                    </p>
                <?php elseif (($user['is_premium'] ?? false) && !$premium_expires): ?>
                    <p style="font-size: 0.8rem; color: #10b981; margin-top: 0.5rem;">
                        Lifetime Premium
                    </p>
                <?php endif; ?>
            </div>
            
            <nav class="dashboard-nav">
                <a href="dashboard.php" class="nav-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                <a href="regions.php" class="nav-item">
                    <i class="fas fa-map"></i>
                    Browse Regions
                </a>
                <a href="quiz.php" class="nav-item">
                    <i class="fas fa-question-circle"></i>
                    Tribe Discovery Quiz
                </a>
                <a href="timeline.php" class="nav-item">
                    <i class="fas fa-calendar-alt"></i>
                    Wedding Timeline
                </a>
                <a href="chatbot.php" class="nav-item">
                    <i class="fas fa-robot"></i>
                    AI Chatbot
                </a>
                <a href="compatibility-match.php" class="nav-item">
                    <i class="fas fa-heart"></i>
                    Compatibility Match
                </a>
                <a href="orders.php" class="nav-item">
                    <i class="fas fa-shopping-bag"></i>
                    My Orders
                </a>
                <a href="bookings.php" class="nav-item">
                    <i class="fas fa-calendar-check"></i>
                    Expert Bookings
                </a>
                <a href="submit-content.php" class="nav-item">
                    <i class="fas fa-paper-plane"></i>
                    Submit Content
                </a>
                <a href="profile.php" class="nav-item">
                    <i class="fas fa-user-edit"></i>
                    Profile Settings
                </a>
                <?php if (!($user['is_premium'] ?? false)): ?>
                <a href="upgrade.php" class="nav-item upgrade">
                    <i class="fas fa-crown"></i>
                    Upgrade to Premium
                </a>
                <?php endif; ?>
            </nav>
        </div>

        <div class="dashboard-content">
            <div class="dashboard-header">
                <h1>Welcome back, <?php echo explode(' ', $user['full_name'])[0]; ?>!</h1>
                <p>Here's what's happening with your account</p>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['orders']; ?></h3>
                        <p>Total Orders</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-content">
                        <h3>$ <?php echo number_format($stats['total_spent']); ?></h3>
                        <p>Total Spent</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['expert_bookings']; ?></h3>
                        <p>Expert Bookings</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-crown"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo ($user['is_premium'] ?? false) ? 'Premium' : 'Free'; ?></h3>
                        <p>Membership</p>
                        <?php if (($user['is_premium'] ?? false) && $premium_expires): ?>
                            <small style="color: #6b7280; font-size: 0.8rem; display: block; margin-top: 0.25rem;">
                                Expires: <?php echo date('M j, Y', strtotime($premium_expires)); ?>
                            </small>
                        <?php elseif (($user['is_premium'] ?? false) && !$premium_expires): ?>
                            <small style="color: #10b981; font-size: 0.8rem; display: block; margin-top: 0.25rem;">
                                Lifetime Premium
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo count($notifications); ?></h3>
                        <p>Notifications</p>
                    </div>
                </div>
            </div>

            <!-- Start Shopping - Featured Products -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Start Shopping</h2>
                    <a href="<?php echo base_url('index.php#marketplace'); ?>" class="btn-secondary">View All Products</a>
                </div>
                
                <?php if (empty($featured_products)): ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-bag"></i>
                        <h3>No products available</h3>
                        <p>Products will appear here once they're added to the marketplace.</p>
                    </div>
                <?php else: ?>
                    <div class="products-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1.5rem; margin-top: 1rem;">
                        <?php foreach ($featured_products as $product): ?>
                            <div class="product-card" style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: transform 0.3s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 5px 15px rgba(0,0,0,0.15)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
                                <?php if ($product['image']): ?>
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         style="width: 100%; height: 180px; object-fit: cover;"
                                         onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'200\' height=\'180\'%3E%3Crect fill=\'%23e5e7eb\' width=\'200\' height=\'180\'/%3E%3Ctext fill=\'%236b7280\' font-family=\'Arial\' font-size=\'14\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3E<?php echo urlencode($product['name']); ?>%3C/text%3E%3C/svg%3E';">
                                <?php else: ?>
                                    <div style="width: 100%; height: 180px; background: linear-gradient(135deg, #8B5CF6, #EC4899); display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                                <div style="padding: 1rem;">
                                    <h4 style="margin: 0 0 0.5rem 0; color: #1f2937; font-size: 0.95rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($product['name']); ?>">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </h4>
                                    <p style="margin: 0 0 0.5rem 0; color: #6b7280; font-size: 0.8rem;"><?php echo htmlspecialchars($product['category']); ?></p>
                                    <p style="margin: 0 0 1rem 0; color: #8B5CF6; font-weight: 700; font-size: 1.1rem;">$ <?php echo number_format($product['price'], 2); ?></p>
                                    <button onclick="addToCart(<?php echo $product['id']; ?>, this)" 
                                            style="width: 100%; padding: 0.6rem; background: linear-gradient(135deg, #8B5CF6, #EC4899); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 0.9rem; transition: all 0.2s ease;"
                                            onmouseover="this.style.transform='scale(1.02)'"
                                            onmouseout="this.style.transform='scale(1)'">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Orders -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Recent Orders</h2>
                    <?php if (!empty($recent_orders)): ?>
                        <a href="<?php echo base_url('index.php#marketplace'); ?>" class="btn-secondary">Shop More</a>
                    <?php endif; ?>
                </div>
                
                <div class="orders-list">
                    <?php if (empty($recent_orders)): ?>
                        <div class="empty-state">
                            <i class="fas fa-shopping-bag"></i>
                            <h3>No orders yet</h3>
                            <p>Start shopping above to see your orders here</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_orders as $order): ?>
                        <div class="order-item">
                            <div class="order-info">
                                <h4>Order #<?php echo $order['id']; ?></h4>
                                <p><?php echo date('M j, Y', strtotime($order['created_at'])); ?></p>
                            </div>
                            <div class="order-amount">
                                <span>$ <?php echo number_format($order['total_amount']); ?></span>
                            </div>
                            <div class="order-status">
                                <span class="status <?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Notifications -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Notifications</h2>
                    <a href="notifications.php" class="btn-secondary">View All</a>
                </div>
                
                <div class="notifications-list">
                    <?php if (empty($notifications)): ?>
                        <div class="empty-state">
                            <i class="fas fa-bell-slash"></i>
                            <h3>No notifications</h3>
                            <p>You're all caught up!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                            <div class="notification-icon">
                                <i class="fas fa-<?php echo $notification['type'] === 'success' ? 'check-circle' : ($notification['type'] === 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
                            </div>
                            <div class="notification-content">
                                <h4><?php echo $notification['title']; ?></h4>
                                <p><?php echo $notification['message']; ?></p>
                                <span class="notification-time"><?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="dashboard-section">
                <h2>Quick Actions</h2>
                <div class="quick-actions">
                    <a href="quiz.php" class="action-card">
                        <i class="fas fa-question-circle"></i>
                        <h3>Take Tribe Quiz</h3>
                        <p>Discover your partner's tribe</p>
                    </a>
                    
                    <a href="compatibility-match.php" class="action-card">
                        <i class="fas fa-heart"></i>
                        <h3>Compatibility Match</h3>
                        <p>Check inter-tribal compatibility</p>
                    </a>
                    
                    <a href="timeline.php" class="action-card">
                        <i class="fas fa-calendar-alt"></i>
                        <h3>Wedding Timeline</h3>
                        <p>Plan your traditional wedding</p>
                    </a>
                    
                    <a href="chatbot.php" class="action-card">
                        <i class="fas fa-robot"></i>
                        <h3>AI Chatbot</h3>
                        <p>24/7 cultural guidance</p>
                    </a>
                    
                    <a href="regions.php" class="action-card">
                        <i class="fas fa-map"></i>
                        <h3>Browse Regions</h3>
                        <p>Explore tribes by region</p>
                    </a>
                    
                    <a href="submit-content.php" class="action-card">
                        <i class="fas fa-paper-plane"></i>
                        <h3>Submit Content</h3>
                        <p>Share cultural knowledge</p>
                    </a>
                    
                    <a href="../index.php#marketplace" class="action-card">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>Shop Now</h3>
                        <p>Browse our marketplace</p>
                    </a>
                    
                    <a href="../index.php#experts" class="action-card">
                        <i class="fas fa-user-tie"></i>
                        <h3>Book Expert</h3>
                        <p>Consult with experts</p>
                    </a>
                    
                    <?php if (!($user['is_premium'] ?? false)): ?>
                    <a href="upgrade.php" class="action-card premium">
                        <i class="fas fa-crown"></i>
                        <h3>Upgrade Premium</h3>
                        <p>Unlock exclusive features</p>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <style>
        .dashboard-container {
            display: flex;
            min-height: 100vh;
            background: #f8fafc;
        }
        
        .dashboard-sidebar {
            width: 300px;
            background: white;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        
        .user-profile {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .profile-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #8B5CF6, #EC4899);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 2rem;
        }
        
        .premium-badge {
            background: #f3f4f6;
            color: #6b7280;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 0.5rem;
            display: inline-block;
        }
        
        .premium-badge.active {
            background: linear-gradient(135deg, #8B5CF6, #EC4899);
            color: white;
        }
        
        .dashboard-nav {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-radius: 10px;
            text-decoration: none;
            color: #6b7280;
            transition: all 0.3s ease;
        }
        
        .nav-item:hover,
        .nav-item.active {
            background: #f3f4f6;
            color: #8B5CF6;
        }
        
        .nav-item.upgrade {
            background: linear-gradient(135deg, #8B5CF6, #EC4899);
            color: white;
            margin-top: 1rem;
        }
        
        .nav-item.upgrade:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 92, 246, 0.3);
        }
        
        .dashboard-content {
            flex: 1;
            padding: 2rem;
        }
        
        .dashboard-header {
            margin-bottom: 2rem;
        }
        
        .dashboard-header h1 {
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        
        .dashboard-header p {
            color: #6b7280;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #8B5CF6, #EC4899);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .stat-content h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }
        
        .stat-content p {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .dashboard-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .section-header h2 {
            color: #1f2937;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }
        
        .empty-state h3 {
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .order-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-info {
            flex: 1;
        }
        
        .order-info h4 {
            color: #1f2937;
            margin-bottom: 0.25rem;
        }
        
        .order-info p {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .order-amount {
            font-weight: 700;
            color: #8B5CF6;
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
        
        .status.pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .notification-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .notification-item.unread {
            background: #f0f9ff;
        }
        
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-content h4 {
            color: #1f2937;
            margin-bottom: 0.25rem;
        }
        
        .notification-content p {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .notification-time {
            color: #9ca3af;
            font-size: 0.8rem;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .action-card {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 15px;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .action-card:hover {
            background: white;
            border-color: #8B5CF6;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 92, 246, 0.2);
        }
        
        .action-card.premium {
            background: linear-gradient(135deg, #8B5CF6, #EC4899);
            color: white;
        }
        
        .action-card i {
            font-size: 2rem;
            color: #8B5CF6;
            margin-bottom: 1rem;
        }
        
        .action-card.premium i {
            color: white;
        }
        
        .action-card h3 {
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        
        .action-card.premium h3 {
            color: white;
        }
        
        .action-card p {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .action-card.premium p {
            color: rgba(255, 255, 255, 0.8);
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }
            
            .dashboard-sidebar {
                width: 100%;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }
    </style>

    <script src="<?php echo BASE_PATH; ?>/assets/js/config.js"></script>
    <script>
        async function addToCart(productId, buttonElement) {
            try {
                // Disable button during request
                if (buttonElement) {
                    buttonElement.disabled = true;
                    buttonElement.style.opacity = '0.6';
                    buttonElement.style.cursor = 'wait';
                }

                const url = actionUrl('cart.php');
                console.log('[Dashboard] Adding to cart:', { productId, url });

                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        product_id: parseInt(productId),
                        quantity: 1
                    })
                });

                console.log('[Dashboard] Response status:', response.status);

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('[Dashboard] Error response:', errorText);
                    let errorData;
                    try {
                        errorData = JSON.parse(errorText);
                        throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
                    } catch (e) {
                        throw new Error(`HTTP error! status: ${response.status}. ${errorText.substring(0, 100)}`);
                    }
                }

                const data = await response.json();
                console.log('[Dashboard] Response data:', data);
                
                if (data.success) {
                    // Show success message
                    if (buttonElement) {
                        const originalText = buttonElement.innerHTML;
                        buttonElement.innerHTML = '<i class="fas fa-check"></i> Added!';
                        buttonElement.style.background = '#10b981';
                        buttonElement.disabled = false;
                        buttonElement.style.opacity = '1';
                        buttonElement.style.cursor = 'pointer';
                        
                        setTimeout(() => {
                            buttonElement.innerHTML = originalText;
                            buttonElement.style.background = '';
                        }, 2000);
                    }
                    
                    // Optionally update cart count if there's a cart badge
                    if (typeof updateCartCount === 'function') {
                        updateCartCount();
                    }
                } else {
                    throw new Error(data.message || 'Failed to add to cart');
                }
            } catch (error) {
                console.error('[Dashboard] Error adding to cart:', error);
                
                let errorMessage = 'Failed to add product to cart. Please try again.';
                if (error.message) {
                    errorMessage = error.message;
                }
                
                // Show user-friendly error
                alert('Error: ' + errorMessage + '\n\nPlease make sure you are logged in and try again.');
                
                // Re-enable button on error
                if (buttonElement) {
                    buttonElement.disabled = false;
                    buttonElement.style.opacity = '1';
                    buttonElement.style.cursor = 'pointer';
                }
            }
        }
    </script>
</body>
</html>
