<?php
require_once '../config/database.php';
require_once '../helpers/performance.php';
require_once '../helpers/cache.php';

requireAdmin();
$user = getCurrentUser();
$database = new Database();
$db = $database->getConnection();

// Get admin statistics
$stats = [];

// Total users
$query = "SELECT COUNT(*) as count FROM users";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['users'] = $stmt->fetch()['count'];

// Total orders
$query = "SELECT COUNT(*) as count FROM orders";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['orders'] = $stmt->fetch()['count'];

// Total revenue (gross from all paid orders)
$query = "SELECT SUM(total_amount) as revenue FROM orders WHERE status = 'paid'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['revenue'] = $stmt->fetch()['revenue'] ?? 0;

// Marketplace commission (5% of sales) - from platform_commission column
$query = "SELECT SUM(platform_commission) as commission FROM orders WHERE status = 'paid'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['marketplace_commission'] = $stmt->fetch()['commission'] ?? 0;

// Expert consultation commission (15% of bookings)
$query = "SELECT SUM(total_amount * 0.15) as commission FROM expert_bookings WHERE status IN ('confirmed', 'completed')";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['expert_commission'] = $stmt->fetch()['commission'] ?? 0;

// Premium subscription revenue (from users with active premium)
// Note: This is an estimate based on active premium users
// Actual revenue would come from payment records
$query = "SELECT COUNT(*) as count FROM users WHERE is_premium = 1 AND (premium_expires_at IS NULL OR premium_expires_at > NOW())";
$stmt = $db->prepare($query);
$stmt->execute();
$premium_users = $stmt->fetch()['count'];
// Estimate: $20/month per user (or $168/year divided by 12)
$stats['premium_revenue_estimate'] = $premium_users * 20; // Monthly estimate

// Ad revenue (if tracked - from ad_placements clicks/impressions)
// This would need to be calculated based on ad pricing model
try {
    $query = "SELECT SUM(clicks_count * price) as revenue FROM ad_placements WHERE is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch();
    $stats['ad_revenue'] = $result['revenue'] ?? 0;
} catch (PDOException $e) {
    // If table doesn't exist or query fails, set to 0
    $stats['ad_revenue'] = 0;
}

// Total platform revenue (sum of all commission streams)
$stats['total_platform_revenue'] = ($stats['marketplace_commission'] ?? 0) + 
                                    ($stats['expert_commission'] ?? 0) + 
                                    ($stats['premium_revenue_estimate'] ?? 0) + 
                                    ($stats['ad_revenue'] ?? 0);

// Pending payments
$query = "SELECT COUNT(*) as count FROM payment_verifications WHERE status = 'pending'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['pending_payments'] = $stmt->fetch()['count'];

// Recent orders
$query = "SELECT o.*, u.full_name, u.email FROM orders o 
          JOIN users u ON o.user_id = u.id 
          ORDER BY o.created_at DESC LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_orders = $stmt->fetchAll();

// Recent users
$query = "SELECT * FROM users ORDER BY created_at DESC LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_users = $stmt->fetchAll();

// Pending payments
$query = "SELECT pv.*, o.total_amount, u.full_name, u.email 
          FROM payment_verifications pv 
          JOIN orders o ON pv.order_id = o.id 
          JOIN users u ON o.user_id = u.id 
          WHERE pv.status = 'pending' 
          ORDER BY pv.created_at DESC LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$pending_payments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - AfroMarry</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="../index.php">
                    <i class="fas fa-heart"></i>
                    <span>AfroMarry Admin</span>
                </a>
            </div>
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-link active">Dashboard</a>
                <a href="users.php" class="nav-link">Users</a>
                <a href="orders.php" class="nav-link">Orders</a>
                <a href="products.php" class="nav-link">Products</a>
                <a href="payments.php" class="nav-link">Payments</a>
                <a href="../auth/logout.php" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <div class="admin-container">
        <div class="admin-sidebar">
            <div class="admin-profile">
                <div class="profile-avatar">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h3><?php echo $user['full_name']; ?></h3>
                <p><?php echo ucfirst($user['role']); ?></p>
            </div>
            
            <nav class="admin-nav">
                <a href="dashboard.php" class="nav-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="users.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
                <a href="orders.php" class="nav-item">
                    <i class="fas fa-shopping-bag"></i>
                    <span>Orders</span>
                </a>
                <a href="products.php" class="nav-item">
                    <i class="fas fa-box"></i>
                    <span>Products</span>
                </a>
                <a href="experts.php" class="nav-item">
                    <i class="fas fa-user-tie"></i>
                    <span>Experts</span>
                </a>
                <a href="payments.php" class="nav-item">
                    <i class="fas fa-credit-card"></i>
                    <span>Payments</span>
                </a>
                <a href="invoices.php" class="nav-item">
                    <i class="fas fa-file-invoice"></i>
                    <span>Invoices</span>
                </a>
                <a href="coupons.php" class="nav-item">
                    <i class="fas fa-tags"></i>
                    <span>Coupons</span>
                </a>
                <a href="reports.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
                <div class="nav-divider"></div>
                <a href="settings.php" class="nav-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="../auth/logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </div>

        <div class="admin-content">
            <div class="admin-header">
                <h1>Admin Dashboard</h1>
                <p>Welcome back, <?php echo explode(' ', $user['full_name'])[0]; ?>!</p>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['users']; ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>
                
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
                        <h3>$ <?php echo number_format($stats['total_platform_revenue']); ?></h3>
                        <p>Total Platform Revenue</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['pending_payments']; ?></h3>
                        <p>Pending Payments</p>
                    </div>
                </div>
                
                <!-- Performance Stats -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php 
                            $hits = perf_get_counter('cache_hits');
                            $misses = perf_get_counter('cache_misses');
                            $total = $hits + $misses;
                            $rate = $total > 0 ? round(($hits / $total) * 100, 1) : 0;
                            echo $rate . '%';
                        ?></h3>
                        <p>Cache Hit Rate</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-microchip"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php 
                            $calls = perf_get_counter('tribe_controller_calls') + perf_get_counter('product_controller_calls');
                            echo number_format($calls);
                        ?></h3>
                        <p>Controller Calls</p>
                    </div>
                </div>
            </div>

            <!-- Revenue Breakdown -->
            <div class="admin-section">
                <div class="section-header">
                    <h2>Revenue Breakdown</h2>
                    <p style="color: #6b7280; font-size: 0.9rem;">Platform performance across all revenue streams</p>
                </div>
                
                <div class="revenue-grid">
                    <div class="revenue-card">
                        <div class="revenue-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                            <i class="fas fa-store"></i>
                        </div>
                        <div class="revenue-content">
                            <h3>$ <?php echo number_format($stats['marketplace_commission'], 2); ?></h3>
                            <p>Marketplace Commission</p>
                            <small>5% of all product sales</small>
                        </div>
                    </div>
                    
                    <div class="revenue-card">
                        <div class="revenue-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="revenue-content">
                            <h3>$ <?php echo number_format($stats['expert_commission'], 2); ?></h3>
                            <p>Expert Consultation Commission</p>
                            <small>15% of all expert bookings</small>
                        </div>
                    </div>
                    
                    <div class="revenue-card">
                        <div class="revenue-icon" style="background: linear-gradient(135deg, #8B5CF6, #7c3aed);">
                            <i class="fas fa-crown"></i>
                        </div>
                        <div class="revenue-content">
                            <h3>$ <?php echo number_format($stats['premium_revenue_estimate'], 2); ?></h3>
                            <p>Premium Subscriptions</p>
                            <small>Estimated monthly revenue</small>
                        </div>
                    </div>
                    
                    <div class="revenue-card">
                        <div class="revenue-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                            <i class="fas fa-ad"></i>
                        </div>
                        <div class="revenue-content">
                            <h3>$ <?php echo number_format($stats['ad_revenue'], 2); ?></h3>
                            <p>Advertising Revenue</p>
                            <small>From ad clicks and impressions</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Metrics -->
            <div class="admin-section">
                <div class="section-header">
                    <h2>Performance Metrics</h2>
                    <p style="color: #6b7280; font-size: 0.9rem;">Application performance and caching statistics</p>
                </div>
                
                <div class="revenue-grid">
                    <div class="revenue-card">
                        <div class="revenue-icon" style="background: linear-gradient(135deg, #ec4899, #db2777);">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div class="revenue-content">
                            <h3><?php echo number_format(perf_get_counter('cache_hits')); ?></h3>
                            <p>Cache Hits</p>
                        </div>
                    </div>
                    
                    <div class="revenue-card">
                        <div class="revenue-icon" style="background: linear-gradient(135deg, #f97316, #ea580c);">
                            <i class="fas fa-database"></i>
                        </div>
                        <div class="revenue-content">
                            <h3><?php echo number_format(perf_get_counter('cache_misses')); ?></h3>
                            <p>Cache Misses</p>
                        </div>
                    </div>
                    
                    <div class="revenue-card">
                        <div class="revenue-icon" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                        <div class="revenue-content">
                            <h3><?php 
                                $total_time = perf_get_counter('total_execution_time');
                                $total_calls = perf_get_counter('tribe_controller_calls') + perf_get_counter('product_controller_calls');
                                echo $total_calls > 0 ? number_format($total_time / $total_calls, 2) : '0.00';
                            ?> ms</h3>
                            <p>Avg Execution Time</p>
                        </div>
                    </div>
                    
                    <div class="revenue-card">
                        <div class="revenue-icon" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                            <i class="fas fa-memory"></i>
                        </div>
                        <div class="revenue-content">
                            <h3><?php 
                                $cache_stats = cache_stats();
                                echo $cache_stats['memory_items'] + $cache_stats['file_items'];
                            ?></h3>
                            <p>Active Cache Items</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="admin-section">
                <div class="section-header">
                    <h2>Recent Orders</h2>
                    <a href="orders.php" class="btn-secondary">View All</a>
                </div>
                
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td>
                                    <div>
                                        <strong><?php echo $order['full_name']; ?></strong>
                                        <br>
                                        <small><?php echo $order['email']; ?></small>
                                    </div>
                                </td>
                                <td>$ <?php echo number_format($order['total_amount']); ?></td>
                                <td>
                                    <span class="status <?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <a href="orders.php?view=<?php echo $order['id']; ?>" class="btn-small">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pending Payments -->
            <div class="admin-section">
                <div class="section-header">
                    <h2>Pending Payment Verifications</h2>
                    <a href="payments.php" class="btn-secondary">View All</a>
                </div>
                
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_payments as $payment): ?>
                            <tr>
                                <td>#<?php echo $payment['order_id']; ?></td>
                                <td>
                                    <div>
                                        <strong><?php echo $payment['full_name']; ?></strong>
                                        <br>
                                        <small><?php echo $payment['email']; ?></small>
                                    </div>
                                </td>
                                <td>$ <?php echo number_format($payment['amount']); ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                <td><?php echo $payment['payment_reference']; ?></td>
                                <td><?php echo date('M j, Y', strtotime($payment['created_at'])); ?></td>
                                <td>
                                    <a href="payments.php?verify=<?php echo $payment['id']; ?>" class="btn-small btn-success">Verify</a>
                                    <a href="payments.php?reject=<?php echo $payment['id']; ?>" class="btn-small btn-danger">Reject</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="admin-section">
                <div class="section-header">
                    <h2>Recent Users</h2>
                    <a href="users.php" class="btn-secondary">View All</a>
                </div>
                
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Premium</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_users as $user): ?>
                            <tr>
                                <td><?php echo $user['full_name']; ?></td>
                                <td><?php echo $user['email']; ?></td>
                                <td>
                                    <span class="role-badge <?php echo $user['role']; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['is_premium']): ?>
                                        <span class="premium-badge">Premium</span>
                                    <?php else: ?>
                                        <span class="free-badge">Free</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <a href="users.php?view=<?php echo $user['id']; ?>" class="btn-small">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <style>
        .admin-container {
            display: flex;
            min-height: 100vh;
            background: #f8fafc;
        }
        
        .admin-sidebar {
            width: 250px;
            background: white;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }
        
        .admin-profile {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .profile-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.75rem;
            color: white;
            font-size: 1.5rem;
        }
        
        .admin-nav {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            border-radius: 8px;
            text-decoration: none;
            color: #6b7280;
            transition: all 0.2s ease;
            font-size: 0.95rem;
        }
        
        .nav-item:hover,
        .nav-item.active {
            background: #f3f4f6;
            color: #dc2626;
        }
        
        .admin-content {
            flex: 1;
            padding: 1.5rem;
        }
        
        .admin-header {
            margin-bottom: 2rem;
        }
        
        .admin-header h1 {
            color: #1f2937;
            margin-bottom: 0.25rem;
            font-size: 1.75rem;
        }
        
        .admin-header p {
            color: #6b7280;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.25rem;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
        }
        
        .stat-content h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.1rem;
        }
        
        .stat-content p {
            color: #6b7280;
            font-size: 0.85rem;
        }
        
        .stat-content p {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .admin-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .section-header h2 {
            color: #1f2937;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .admin-table th,
        .admin-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            font-size: 0.9rem;
        }
        
        .admin-table th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        
        .admin-table td {
            color: #6b7280;
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
        
        .status.shipped {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status.delivered {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status.cancelled {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .role-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .role-badge.customer {
            background: #e0e7ff;
            color: #3730a3;
        }
        
        .role-badge.admin {
            background: #fef3c7;
            color: #92400e;
        }
        
        
        .premium-badge {
            background: #d1fae5;
            color: #065f46;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .free-badge {
            background: #f3f4f6;
            color: #6b7280;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .btn-small {
            padding: 0.4rem 0.8rem;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 0.25rem;
        }
        
        .btn-small {
            background: #6b7280;
            color: white;
        }
        
        .btn-small.btn-success {
            background: #10b981;
        }
        
        .btn-small.btn-danger {
            background: #ef4444;
        }
        
        .revenue-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .revenue-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s ease;
        }
        
        .revenue-card:hover {
            border-color: #8B5CF6;
            box-shadow: 0 5px 20px rgba(139, 92, 246, 0.2);
            transform: translateY(-2px);
        }
        
        .revenue-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        
        .revenue-content {
            flex: 1;
        }
        
        .revenue-content h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }
        
        .revenue-content p {
            color: #374151;
            font-weight: 600;
            margin-bottom: 0.1rem;
            font-size: 0.9rem;
        }
        
        .revenue-content small {
            color: #6b7280;
            font-size: 0.8rem;
        }
        
        .nav-divider {
            height: 1px;
            background: #e5e7eb;
            margin: 0.5rem 0;
        }
        
        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .admin-sidebar {
                width: 100%;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }
    </style>
</body>
</html>
