<?php
require_once '../config/database.php';

requireAdmin();
$user = getCurrentUser();
$database = new Database();
$db = $database->getConnection();

// Get date range
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-d'); // Today

// Validate dates
if (!strtotime($date_from) || !strtotime($date_to)) {
    $date_from = date('Y-m-01');
    $date_to = date('Y-m-d');
}

// Sales Report
$query = "SELECT 
            DATE(created_at) as date,
            COUNT(*) as orders,
            SUM(total_amount) as revenue,
            AVG(total_amount) as avg_order_value
          FROM orders 
          WHERE created_at BETWEEN :date_from AND :date_to 
            AND status IN ('paid', 'shipped', 'delivered')
          GROUP BY DATE(created_at)
          ORDER BY date DESC";
$stmt = $db->prepare($query);
$stmt->execute([':date_from' => $date_from, ':date_to' => $date_to]);
$sales_data = $stmt->fetchAll();

// Product Performance
$query = "SELECT 
            p.name,
            p.category,
            p.tribe,
            SUM(oi.quantity) as total_sold,
            SUM(oi.quantity * oi.price) as revenue,
            COUNT(DISTINCT oi.order_id) as orders
          FROM products p
          JOIN order_items oi ON p.id = oi.product_id
          JOIN orders o ON oi.order_id = o.id
          WHERE o.created_at BETWEEN :date_from AND :date_to 
            AND o.status IN ('paid', 'shipped', 'delivered')
          GROUP BY p.id
          ORDER BY total_sold DESC
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute([':date_from' => $date_from, ':date_to' => $date_to]);
$product_performance = $stmt->fetchAll();

// Customer Analytics
$query = "SELECT 
            COUNT(DISTINCT u.id) as total_customers,
            COUNT(DISTINCT CASE WHEN u.is_premium = TRUE THEN u.id END) as premium_customers,
            COUNT(DISTINCT CASE WHEN u.created_at BETWEEN :date_from AND :date_to THEN u.id END) as new_customers,
            AVG(customer_stats.total_spent) as avg_customer_value
          FROM users u
          LEFT JOIN (
            SELECT user_id, SUM(total_amount) as total_spent
            FROM orders 
            WHERE status IN ('paid', 'shipped', 'delivered')
            GROUP BY user_id
          ) customer_stats ON u.id = customer_stats.user_id";
$stmt = $db->prepare($query);
$stmt->execute([':date_from' => $date_from, ':date_to' => $date_to]);
$customer_analytics = $stmt->fetch();

// Payment Methods
$query = "SELECT 
            payment_method,
            COUNT(*) as count,
            SUM(total_amount) as total_amount
          FROM orders 
          WHERE created_at BETWEEN :date_from AND :date_to 
            AND status IN ('paid', 'shipped', 'delivered')
          GROUP BY payment_method
          ORDER BY count DESC";
$stmt = $db->prepare($query);
$stmt->execute([':date_from' => $date_from, ':date_to' => $date_to]);
$payment_methods = $stmt->fetchAll();

// Order Status Distribution
$query = "SELECT 
            status,
            COUNT(*) as count,
            SUM(total_amount) as total_amount
          FROM orders 
          WHERE created_at BETWEEN :date_from AND :date_to
          GROUP BY status
          ORDER BY count DESC";
$stmt = $db->prepare($query);
$stmt->execute([':date_from' => $date_from, ':date_to' => $date_to]);
$order_status = $stmt->fetchAll();

// Calculate totals
$total_revenue = array_sum(array_column($sales_data, 'revenue'));
$total_orders = array_sum(array_column($sales_data, 'orders'));
$avg_order_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="users.php" class="nav-link">Users</a>
                <a href="orders.php" class="nav-link">Orders</a>
                <a href="products.php" class="nav-link">Products</a>
                <a href="payments.php" class="nav-link">Payments</a>
                <a href="reports.php" class="nav-link active">Reports</a>
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
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                <a href="users.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    Users Management
                </a>
                <a href="orders.php" class="nav-item">
                    <i class="fas fa-shopping-bag"></i>
                    Orders Management
                </a>
                <a href="products.php" class="nav-item">
                    <i class="fas fa-box"></i>
                    Products Management
                </a>
                <a href="experts.php" class="nav-item">
                    <i class="fas fa-user-tie"></i>
                    Experts Management
                </a>
                <a href="payments.php" class="nav-item">
                    <i class="fas fa-credit-card"></i>
                    Payment Verification
                </a>
                <a href="invoices.php" class="nav-item">
                    <i class="fas fa-file-invoice"></i>
                    Invoices
                </a>
                <a href="reports.php" class="nav-item active">
                    <i class="fas fa-chart-bar"></i>
                    Reports
                </a>
            </nav>
        </div>

        <div class="admin-content">
            <div class="admin-header">
                <h1>Analytics & Reports</h1>
                <p>Comprehensive business insights and performance metrics</p>
            </div>

            <!-- Date Range Filter -->
            <div class="filters-section">
                <form method="GET" class="filters-form">
                    <div class="filter-group">
                        <label for="date_from">From Date:</label>
                        <input type="date" name="date_from" id="date_from" value="<?php echo $date_from; ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="date_to">To Date:</label>
                        <input type="date" name="date_to" id="date_to" value="<?php echo $date_to; ?>">
                    </div>
                    
                    <button type="submit" class="btn-primary">Update Report</button>
                    <button type="button" class="btn-secondary" onclick="exportReport()">Export PDF</button>
                </form>
            </div>

            <!-- Key Metrics -->
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="metric-content">
                        <h3>$ <?php echo number_format($total_revenue); ?></h3>
                        <p>Total Revenue</p>
                        <small>Period: <?php echo date('M j', strtotime($date_from)); ?> - <?php echo date('M j, Y', strtotime($date_to)); ?></small>
                    </div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="metric-content">
                        <h3><?php echo number_format($total_orders); ?></h3>
                        <p>Total Orders</p>
                        <small><?php echo $total_orders > 0 ? round($total_orders / max(1, (strtotime($date_to) - strtotime($date_from)) / 86400), 1) : 0; ?> orders/day</small>
                    </div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-icon">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <div class="metric-content">
                        <h3>$ <?php echo number_format($avg_order_value); ?></h3>
                        <p>Average Order Value</p>
                        <small>Per order</small>
                    </div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="metric-content">
                        <h3><?php echo number_format($customer_analytics['total_customers']); ?></h3>
                        <p>Total Customers</p>
                        <small><?php echo number_format($customer_analytics['new_customers']); ?> new this period</small>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="charts-section">
                <div class="chart-container">
                    <h3>Revenue Trend</h3>
                    <canvas id="revenueChart"></canvas>
                </div>
                
                <div class="chart-container">
                    <h3>Order Status Distribution</h3>
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <!-- Product Performance -->
            <div class="admin-section">
                <h2>Top Performing Products</h2>
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Tribe</th>
                                <th>Units Sold</th>
                                <th>Revenue</th>
                                <th>Orders</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($product_performance as $product): ?>
                            <tr>
                                <td><?php echo $product['name']; ?></td>
                                <td><?php echo ucfirst($product['category']); ?></td>
                                <td><?php echo $product['tribe']; ?></td>
                                <td><?php echo number_format($product['total_sold']); ?></td>
                                <td>$ <?php echo number_format($product['revenue']); ?></td>
                                <td><?php echo number_format($product['orders']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="admin-section">
                <h2>Payment Methods</h2>
                <div class="payment-methods-grid">
                    <?php foreach ($payment_methods as $method): ?>
                    <div class="payment-method-card">
                        <h4><?php echo ucfirst(str_replace('_', ' ', $method['payment_method'])); ?></h4>
                        <div class="method-stats">
                            <div class="stat">
                                <span class="value"><?php echo number_format($method['count']); ?></span>
                                <span class="label">Orders</span>
                            </div>
                            <div class="stat">
                                <span class="value">$ <?php echo number_format($method['total_amount']); ?></span>
                                <span class="label">Revenue</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Customer Analytics -->
            <div class="admin-section">
                <h2>Customer Analytics</h2>
                <div class="customer-stats-grid">
                    <div class="customer-stat-card">
                        <h4>Total Customers</h4>
                        <div class="stat-value"><?php echo number_format($customer_analytics['total_customers']); ?></div>
                    </div>
                    
                    <div class="customer-stat-card">
                        <h4>Premium Customers</h4>
                        <div class="stat-value"><?php echo number_format($customer_analytics['premium_customers']); ?></div>
                        <div class="stat-percentage">
                            <?php echo $customer_analytics['total_customers'] > 0 ? 
                                round(($customer_analytics['premium_customers'] / $customer_analytics['total_customers']) * 100, 1) : 0; ?>%
                        </div>
                    </div>
                    
                    <div class="customer-stat-card">
                        <h4>New Customers</h4>
                        <div class="stat-value"><?php echo number_format($customer_analytics['new_customers']); ?></div>
                    </div>
                    
                    <div class="customer-stat-card">
                        <h4>Average Customer Value</h4>
                        <div class="stat-value">$ <?php echo number_format($customer_analytics['avg_customer_value'] ?? 0); ?></div>
                    </div>
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
            width: 300px;
            background: white;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        
        .admin-profile {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .profile-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 2rem;
        }
        
        .admin-nav {
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
            color: #dc2626;
        }
        
        .admin-content {
            flex: 1;
            padding: 2rem;
        }
        
        .admin-header {
            margin-bottom: 2rem;
        }
        
        .admin-header h1 {
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        
        .admin-header p {
            color: #6b7280;
        }
        
        .filters-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .filters-form {
            display: flex;
            gap: 1rem;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .filter-group label {
            font-weight: 600;
            color: #374151;
        }
        
        .filter-group input {
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .metric-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .metric-icon {
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
        
        .metric-content h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }
        
        .metric-content p {
            color: #6b7280;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .metric-content small {
            color: #9ca3af;
            font-size: 0.8rem;
        }
        
        .charts-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .chart-container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .chart-container h3 {
            color: #1f2937;
            margin-bottom: 1rem;
        }
        
        .admin-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .admin-section h2 {
            color: #1f2937;
            margin-bottom: 1.5rem;
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
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .admin-table th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        
        .admin-table td {
            color: #6b7280;
        }
        
        .payment-methods-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .payment-method-card {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
        }
        
        .payment-method-card h4 {
            color: #1f2937;
            margin-bottom: 1rem;
        }
        
        .method-stats {
            display: flex;
            justify-content: space-around;
        }
        
        .stat {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .stat .value {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1f2937;
        }
        
        .stat .label {
            font-size: 0.8rem;
            color: #6b7280;
        }
        
        .customer-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .customer-stat-card {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
        }
        
        .customer-stat-card h4 {
            color: #1f2937;
            margin-bottom: 1rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #8B5CF6;
            margin-bottom: 0.5rem;
        }
        
        .stat-percentage {
            color: #10b981;
            font-weight: 600;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #8B5CF6, #EC4899);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 92, 246, 0.3);
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .admin-sidebar {
                width: 100%;
            }
            
            .charts-section {
                grid-template-columns: 1fr;
            }
            
            .filters-form {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>

    <script>
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: [<?php echo "'" . implode("','", array_reverse(array_column($sales_data, 'date'))) . "'"; ?>],
                datasets: [{
                    label: 'Revenue ($)',
                    data: [<?php echo implode(',', array_reverse(array_column($sales_data, 'revenue'))); ?>],
                    borderColor: '#8B5CF6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php echo "'" . implode("','", array_column($order_status, 'status')) . "'"; ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_column($order_status, 'count')); ?>],
                    backgroundColor: [
                        '#8B5CF6',
                        '#10b981',
                        '#3b82f6',
                        '#f59e0b',
                        '#ef4444'
                    ]
                }]
            },
            options: {
                responsive: true
            }
        });

        function exportReport() {
            // In a real implementation, this would generate a PDF report
            alert('PDF export functionality would be implemented here');
        }
    </script>
</body>
</html>
