<?php
require_once '../config/database.php';

requireAdmin();
$user = getCurrentUser();
$database = new Database();
$db = $database->getConnection();

// Handle expert actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    try {
        if ($action === 'update_percentage') {
            $expert_id = intval($_POST['expert_id']);
            $percentage = floatval($_POST['percentage']);
            
            if ($percentage < 0 || $percentage > 100) {
                throw new Exception('Percentage must be between 0 and 100');
            }
            
            // Check if column exists, if not add it
            try {
                $query = "UPDATE experts SET payment_percentage = :percentage WHERE id = :expert_id";
                $stmt = $db->prepare($query);
                $stmt->execute([
                    ':percentage' => $percentage,
                    ':expert_id' => $expert_id
                ]);
            } catch (PDOException $e) {
                // Column might not exist, try to add it
                if (strpos($e->getMessage(), 'payment_percentage') !== false) {
                    $alterQuery = "ALTER TABLE experts ADD COLUMN payment_percentage DECIMAL(5,2) DEFAULT 70.00";
                    $db->exec($alterQuery);
                    // Retry update
                    $stmt = $db->prepare($query);
                    $stmt->execute([
                        ':percentage' => $percentage,
                        ':expert_id' => $expert_id
                    ]);
                } else {
                    throw $e;
                }
            }
            
            $success_message = "Payment percentage updated successfully!";
            
        } elseif ($action === 'pay_expert') {
            $expert_id = intval($_POST['expert_id']);
            $booking_ids = isset($_POST['booking_ids']) ? $_POST['booking_ids'] : [];
            $payment_method = sanitize($_POST['payment_method']);
            $payment_reference = sanitize($_POST['payment_reference']);
            $notes = sanitize($_POST['notes'] ?? '');
            
            // Get unpaid bookings for this expert
            if (empty($booking_ids)) {
                $query = "SELECT * FROM expert_bookings 
                         WHERE expert_id = :id 
                         AND status IN ('confirmed', 'completed') 
                         AND id NOT IN (
                             SELECT booking_id FROM expert_payments 
                             WHERE FIND_IN_SET(booking_id, booking_ids)
                         )";
                $stmt = $db->prepare($query);
                $stmt->execute([':id' => $expert_id]);
                $bookings = $stmt->fetchAll();
            } else {
                $placeholders = implode(',', array_fill(0, count($booking_ids), '?'));
                $query = "SELECT * FROM expert_bookings WHERE id IN ($placeholders) AND expert_id = ?";
                $params = array_merge($booking_ids, [$expert_id]);
                $stmt = $db->prepare($query);
                $stmt->execute($params);
                $bookings = $stmt->fetchAll();
            }
            
            if (empty($bookings)) {
                throw new Exception('No unpaid bookings found for this expert');
            }
            
            // Calculate totals
            $total_earnings = 0;
            $booking_id_list = [];
            foreach ($bookings as $booking) {
                $total_earnings += $booking['total_amount'];
                $booking_id_list[] = $booking['id'];
            }
            
            // Get expert payment percentage
            $query = "SELECT payment_percentage FROM experts WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([':id' => $expert_id]);
            $expert = $stmt->fetch();
            $payment_percentage = floatval($expert['payment_percentage'] ?? 85.00);
            
            // Calculate amounts
            $expert_amount = ($total_earnings * $payment_percentage) / 100;
            $admin_commission = $total_earnings - $expert_amount;
            
            // Check if expert_payments table exists, create if not
            try {
                $checkTable = "SHOW TABLES LIKE 'expert_payments'";
                $result = $db->query($checkTable);
                if ($result->rowCount() === 0) {
                    $createTable = "CREATE TABLE expert_payments (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        expert_id INT NOT NULL,
                        booking_ids TEXT,
                        total_earnings DECIMAL(10,2) NOT NULL,
                        payment_percentage DECIMAL(5,2) NOT NULL,
                        expert_amount DECIMAL(10,2) NOT NULL,
                        admin_commission DECIMAL(10,2) NOT NULL,
                        payment_method VARCHAR(100),
                        payment_reference VARCHAR(255),
                        payment_date DATE NOT NULL,
                        paid_by INT NOT NULL,
                        notes TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (expert_id) REFERENCES experts(id) ON DELETE CASCADE,
                        FOREIGN KEY (paid_by) REFERENCES users(id)
                    )";
                    $db->exec($createTable);
                }
            } catch (PDOException $e) {
                // Table might already exist, continue
            }
            
            // Record payment
            $query = "INSERT INTO expert_payments 
                     (expert_id, booking_ids, total_earnings, payment_percentage, expert_amount, 
                      admin_commission, payment_method, payment_reference, payment_date, paid_by, notes) 
                     VALUES 
                     (:expert_id, :booking_ids, :total_earnings, :payment_percentage, :expert_amount, 
                      :admin_commission, :payment_method, :payment_reference, CURDATE(), :paid_by, :notes)";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':expert_id' => $expert_id,
                ':booking_ids' => implode(',', $booking_id_list),
                ':total_earnings' => $total_earnings,
                ':payment_percentage' => $payment_percentage,
                ':expert_amount' => $expert_amount,
                ':admin_commission' => $admin_commission,
                ':payment_method' => $payment_method,
                ':payment_reference' => $payment_reference,
                ':paid_by' => $user['id'],
                ':notes' => $notes
            ]);
            
            $success_message = "Payment recorded successfully! Expert: $".number_format($expert_amount, 2).", Admin Commission: $".number_format($admin_commission, 2);
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get all experts with booking statistics
try {
    // Check if payment_percentage column exists
    $columnExists = false;
    try {
        $checkColumn = "SHOW COLUMNS FROM experts LIKE 'payment_percentage'";
        $result = $db->query($checkColumn);
        $columnExists = $result->rowCount() > 0;
    } catch (PDOException $e) {
        $columnExists = false;
    }
    
    // Check if expert_payments table exists
    $paymentsTableExists = false;
    try {
        $checkTable = "SHOW TABLES LIKE 'expert_payments'";
        $result = $db->query($checkTable);
        $paymentsTableExists = $result->rowCount() > 0;
    } catch (PDOException $e) {
        $paymentsTableExists = false;
    }
    
    // Build query based on whether expert_payments table exists
    if ($paymentsTableExists) {
        // Use a simpler approach for unpaid earnings calculation
        $query = "SELECT e.*, 
                  COUNT(eb.id) as total_bookings,
                  COALESCE(SUM(CASE WHEN eb.status IN ('confirmed', 'completed') THEN eb.total_amount ELSE 0 END), 0) as total_earnings,
                  COALESCE(SUM(CASE WHEN eb.status IN ('confirmed', 'completed') THEN eb.total_amount ELSE 0 END), 0) as unpaid_earnings
                  FROM experts e
                  LEFT JOIN expert_bookings eb ON e.id = eb.expert_id
                  GROUP BY e.id
                  ORDER BY e.name ASC";
    } else {
        // If table doesn't exist, all confirmed/completed bookings are unpaid
        $query = "SELECT e.*, 
                  COUNT(eb.id) as total_bookings,
                  COALESCE(SUM(CASE WHEN eb.status IN ('confirmed', 'completed') THEN eb.total_amount ELSE 0 END), 0) as total_earnings,
                  COALESCE(SUM(CASE WHEN eb.status IN ('confirmed', 'completed') THEN eb.total_amount ELSE 0 END), 0) as unpaid_earnings
                  FROM experts e
                  LEFT JOIN expert_bookings eb ON e.id = eb.expert_id
                  GROUP BY e.id
                  ORDER BY e.name ASC";
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $experts = $stmt->fetchAll();
    
    // For each expert, get client details
    foreach ($experts as &$expert) {
        // Get unique clients
        $clientQuery = "SELECT DISTINCT u.id, u.full_name, u.email, COUNT(eb.id) as booking_count
                       FROM expert_bookings eb
                       JOIN users u ON eb.user_id = u.id
                       WHERE eb.expert_id = :expert_id
                       GROUP BY u.id";
        $clientStmt = $db->prepare($clientQuery);
        $clientStmt->execute([':expert_id' => $expert['id']]);
        $expert['clients'] = $clientStmt->fetchAll();
        
        // Get recent bookings
        $bookingQuery = "SELECT eb.*, u.full_name, u.email 
                        FROM expert_bookings eb
                        JOIN users u ON eb.user_id = u.id
                        WHERE eb.expert_id = :expert_id
                        ORDER BY eb.created_at DESC
                        LIMIT 10";
        $bookingStmt = $db->prepare($bookingQuery);
        $bookingStmt->execute([':expert_id' => $expert['id']]);
        $expert['recent_bookings'] = $bookingStmt->fetchAll();
        
        // Set default payment percentage if not set
        if (!$columnExists || !isset($expert['payment_percentage'])) {
            $expert['payment_percentage'] = 85.00;
        }
    }
    
    // Calculate overall stats
    $total_experts = count($experts);
    $total_bookings = array_sum(array_column($experts, 'total_bookings'));
    $total_earnings = array_sum(array_column($experts, 'total_earnings'));
    $total_unpaid = array_sum(array_column($experts, 'unpaid_earnings'));
    
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
    $experts = [];
    $total_experts = 0;
    $total_bookings = 0;
    $total_earnings = 0;
    $total_unpaid = 0;
}

// Get payment history
$payment_history = [];
if ($paymentsTableExists) {
    try {
        $paymentQuery = "SELECT ep.*, e.name as expert_name, u.full_name as paid_by_name
                        FROM expert_payments ep
                        JOIN experts e ON ep.expert_id = e.id
                        JOIN users u ON ep.paid_by = u.id
                        ORDER BY ep.created_at DESC
                        LIMIT 50";
        $paymentStmt = $db->prepare($paymentQuery);
        $paymentStmt->execute();
        $payment_history = $paymentStmt->fetchAll();
    } catch (PDOException $e) {
        // Error fetching payment history
        $payment_history = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Experts Management - AfroMarry Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Expert Cards */
        .expert-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .expert-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .expert-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f3f4f6;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .expert-info {
            flex: 1;
            min-width: 200px;
        }
        
        .expert-info h3 {
            margin: 0 0 0.5rem 0;
            color: #1f2937;
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .expert-info p {
            margin: 0.5rem 0;
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .expert-info i {
            margin-right: 0.5rem;
            color: #8B5CF6;
        }
        
        .expert-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        
        .expert-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin: 1.5rem 0;
        }
        
        .stat-box {
            text-align: center;
            padding: 1.25rem;
            background: #f9fafb;
            border-radius: 10px;
            transition: background 0.2s, transform 0.2s;
        }
        
        .stat-box:hover {
            background: #f3f4f6;
            transform: scale(1.02);
        }
        
        .stat-box.warning {
            background: #fef3c7;
        }
        
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        /* Clients Grid */
        .clients-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 0.75rem;
            margin-top: 1rem;
        }
        
        .client-card {
            padding: 1rem;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            transition: all 0.2s;
        }
        
        .client-card:hover {
            border-color: #8B5CF6;
            background: #faf5ff;
            transform: translateY(-2px);
        }
        
        .client-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }
        
        .client-email {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
        }
        
        .client-bookings {
            font-size: 0.875rem;
            color: #8B5CF6;
            font-weight: 600;
        }
        
        /* Status Badges */
        .status-badge {
            padding: 0.35rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-badge.status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-badge.status-confirmed {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-badge.status-completed {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-badge.status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }
        
        /* Buttons */
        .btn-sm {
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-sm i {
            font-size: 0.875rem;
        }
        
        .btn-sm:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .btn-sm:active {
            transform: translateY(0);
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            animation: fadeIn 0.2s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-height: 90vh;
            overflow-y: auto;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 2px solid #f3f4f6;
        }
        
        .modal-header h2 {
            margin: 0;
            color: #1f2937;
            font-size: 1.5rem;
        }
        
        .modal-content form {
            padding: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
        }
        
        .form-group input[type="number"],
        .form-group input[type="text"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #8B5CF6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }
        
        .form-group small {
            display: block;
            margin-top: 0.5rem;
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .close {
            color: #6b7280;
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
            position: absolute;
            right: 1.5rem;
            top: 1.5rem;
            transition: color 0.2s, transform 0.2s;
            line-height: 1;
        }
        
        .close:hover {
            color: #1f2937;
            transform: rotate(90deg);
        }
        
        /* Payment Breakdown */
        .payment-breakdown {
            padding: 1.25rem;
            background: #f9fafb;
            border-radius: 10px;
            margin-top: 0.5rem;
        }
        
        .breakdown-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
        }
        
        .breakdown-row.divider {
            border-top: 2px solid #e5e7eb;
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(-20px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .expert-header {
                flex-direction: column;
            }
            
            .expert-actions {
                width: 100%;
            }
            
            .expert-actions .btn-sm {
                flex: 1;
                justify-content: center;
            }
            
            .expert-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .clients-grid {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                width: 95%;
                margin: 10% auto;
            }
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
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
    </style>
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
                <a href="experts.php" class="nav-link active">Experts</a>
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
                <a href="experts.php" class="nav-item active">
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
                <a href="reports.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    Reports
                </a>
            </nav>
        </div>

        <div class="admin-content">
            <div class="admin-header">
                <h1>Experts Management</h1>
                <p>Manage expert bookings, payments, and commissions</p>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #8B5CF6, #EC4899);">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $total_experts; ?></h3>
                        <p>Total Experts</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #10B981, #059669);">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $total_bookings; ?></h3>
                        <p>Total Bookings</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #F59E0B, #D97706);">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-content">
                        <h3>$<?php echo number_format($total_earnings, 2); ?></h3>
                        <p>Total Earnings</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #EF4444, #DC2626);">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3>$<?php echo number_format($total_unpaid, 2); ?></h3>
                        <p>Unpaid Amount</p>
                    </div>
                </div>
            </div>

            <!-- Experts List -->
            <div class="admin-section">
                <h2>Experts & Bookings</h2>
                
                <?php if (empty($experts)): ?>
                    <div class="empty-state">
                        <i class="fas fa-user-tie"></i>
                        <h3>No experts found</h3>
                        <p>Add experts to get started</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($experts as $expert): ?>
                        <div class="expert-card">
                            <div class="expert-header">
                                <div class="expert-info">
                                    <h3><?php echo htmlspecialchars($expert['name']); ?></h3>
                                    <p style="color: #6b7280; margin: 0.25rem 0;">
                                        <i class="fas fa-map-marker-alt"></i> 
                                        <?php echo htmlspecialchars($expert['tribe']); ?> Expert
                                    </p>
                                    <p style="color: #6b7280; margin: 0.25rem 0;">
                                        <i class="fas fa-dollar-sign"></i> 
                                        $<?php echo number_format($expert['hourly_rate'], 2); ?>/hour
                                    </p>
                                </div>
                                <div class="expert-actions">
                                    <button class="btn-secondary btn-sm" onclick="openPercentageModal(<?php echo $expert['id']; ?>, <?php echo $expert['payment_percentage']; ?>)">
                                        <i class="fas fa-percent"></i> Set Payment %
                                    </button>
                                    <?php if ($expert['unpaid_earnings'] > 0): ?>
                                        <button class="btn-primary btn-sm" onclick="openPaymentModal(<?php echo $expert['id']; ?>, '<?php echo htmlspecialchars($expert['name']); ?>', <?php echo $expert['unpaid_earnings']; ?>, <?php echo $expert['payment_percentage']; ?>)">
                                            <i class="fas fa-money-bill-wave"></i> Pay Expert
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="expert-stats">
                                <div class="stat-box">
                                    <div class="stat-value" style="color: #1f2937;">
                                        <?php echo $expert['total_bookings']; ?>
                                    </div>
                                    <div class="stat-label">Total Bookings</div>
                                </div>
                                <div class="stat-box">
                                    <div class="stat-value" style="color: #059669;">
                                        $<?php echo number_format($expert['total_earnings'], 2); ?>
                                    </div>
                                    <div class="stat-label">Total Earnings</div>
                                </div>
                                <div class="stat-box warning">
                                    <div class="stat-value" style="color: #d97706;">
                                        $<?php echo number_format($expert['unpaid_earnings'], 2); ?>
                                    </div>
                                    <div class="stat-label">Unpaid Amount</div>
                                </div>
                                <div class="stat-box">
                                    <div class="stat-value" style="color: #8B5CF6;">
                                        <?php echo $expert['payment_percentage']; ?>%
                                    </div>
                                    <div class="stat-label">Expert Share</div>
                                </div>
                            </div>
                            
                            <!-- Clients Section -->
                            <?php if (!empty($expert['clients'])): ?>
                                <div style="margin-top: 1.5rem;">
                                    <h4 style="margin-bottom: 0.75rem; color: #1f2937;">
                                        <i class="fas fa-users"></i> Clients (<?php echo count($expert['clients']); ?>)
                                    </h4>
                                    <div class="clients-grid">
                                        <?php foreach ($expert['clients'] as $client): ?>
                                            <div class="client-card">
                                                <div class="client-name">
                                                    <?php echo htmlspecialchars($client['full_name']); ?>
                                                </div>
                                                <div class="client-email">
                                                    <?php echo htmlspecialchars($client['email']); ?>
                                                </div>
                                                <div class="client-bookings">
                                                    <i class="fas fa-calendar"></i> <?php echo $client['booking_count']; ?> booking(s)
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Recent Bookings -->
                            <?php if (!empty($expert['recent_bookings'])): ?>
                                <div style="margin-top: 1.5rem;">
                                    <h4 style="margin-bottom: 0.75rem; color: #1f2937;">
                                        <i class="fas fa-history"></i> Recent Bookings
                                    </h4>
                                    <div class="table-container">
                                        <table class="admin-table" style="font-size: 0.875rem;">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Client</th>
                                                    <th>Duration</th>
                                                    <th>Amount</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (array_slice($expert['recent_bookings'], 0, 5) as $booking): ?>
                                                    <tr>
                                                        <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                                                        <td><?php echo htmlspecialchars($booking['full_name']); ?></td>
                                                        <td><?php echo $booking['duration_hours']; ?> hr(s)</td>
                                                        <td>$<?php echo number_format($booking['total_amount'], 2); ?></td>
                                                        <td>
                                                            <span class="status-badge status-<?php echo $booking['status']; ?>">
                                                                <?php echo ucfirst($booking['status']); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Payment History -->
            <?php if (!empty($payment_history)): ?>
                <div class="admin-section">
                    <h2>Payment History</h2>
                    <div class="table-container">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Expert</th>
                                    <th>Total Earnings</th>
                                    <th>Expert Amount</th>
                                    <th>Admin Commission</th>
                                    <th>Payment Method</th>
                                    <th>Paid By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payment_history as $payment): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($payment['expert_name']); ?></td>
                                        <td>$<?php echo number_format($payment['total_earnings'], 2); ?></td>
                                        <td style="color: #059669; font-weight: 600;">
                                            $<?php echo number_format($payment['expert_amount'], 2); ?>
                                        </td>
                                        <td style="color: #DC2626; font-weight: 600;">
                                            $<?php echo number_format($payment['admin_commission'], 2); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($payment['payment_method'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($payment['paid_by_name']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Payment Percentage Modal -->
    <div id="percentage-modal" class="modal">
        <div class="modal-content" style="max-width: 500px; position: relative;">
            <div class="modal-header">
                <h2>Set Payment Percentage</h2>
                <span class="close" onclick="closeModal('percentage-modal')">&times;</span>
            </div>
            <form method="POST" id="percentage-form">
                <input type="hidden" name="action" value="update_percentage">
                <input type="hidden" name="expert_id" id="percentage-expert-id">
                
                <div class="form-group">
                    <label>Expert receives:</label>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="number" name="percentage" id="percentage-value" min="0" max="100" step="0.01" required
                               style="flex: 1; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px;">
                        <span style="font-size: 1.25rem; font-weight: 600; color: #8B5CF6;">%</span>
                    </div>
                    <small style="color: #6b7280; display: block; margin-top: 0.5rem;">
                        Admin keeps: <span id="admin-percentage">30%</span>
                    </small>
                </div>
                
                <div style="margin-top: 1.5rem; display: flex; gap: 0.75rem;">
                    <button type="submit" class="btn-primary" style="flex: 1;">Update Percentage</button>
                    <button type="button" class="btn-secondary" onclick="closeModal('percentage-modal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Payment Modal -->
    <div id="payment-modal" class="modal">
        <div class="modal-content" style="max-width: 600px; position: relative;">
            <div class="modal-header">
                <h2>Pay Expert</h2>
                <span class="close" onclick="closeModal('payment-modal')">&times;</span>
            </div>
            <form method="POST" id="payment-form">
                <input type="hidden" name="action" value="pay_expert">
                <input type="hidden" name="expert_id" id="payment-expert-id">
                
                <div class="form-group">
                    <label>Expert:</label>
                    <div style="padding: 0.75rem; background: #f9fafb; border-radius: 8px; font-weight: 600;">
                        <span id="payment-expert-name"></span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Unpaid Amount:</label>
                    <div style="padding: 0.75rem; background: #fef3c7; border-radius: 8px; font-weight: 600; color: #d97706; font-size: 1.25rem;">
                        $<span id="payment-unpaid-amount"></span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Payment Breakdown:</label>
                    <div class="payment-breakdown">
                        <div class="breakdown-row">
                            <span>Expert receives (<span id="payment-percentage-display"></span>%):</span>
                            <span style="font-weight: 600; color: #059669; font-size: 1.1rem;" id="payment-expert-amount">$0.00</span>
                        </div>
                        <div class="breakdown-row divider">
                            <span>Admin commission:</span>
                            <span style="font-weight: 700; color: #DC2626; font-size: 1.1rem;" id="payment-admin-commission">$0.00</span>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="payment_method">Payment Method:</label>
                    <select name="payment_method" id="payment_method" required style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px;">
                        <option value="">Select payment method</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Mobile Money">Mobile Money</option>
                        <option value="PayPal">PayPal</option>
                        <option value="Cash">Cash</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="payment_reference">Payment Reference/Transaction ID:</label>
                    <input type="text" name="payment_reference" id="payment_reference" 
                           placeholder="Enter transaction reference" 
                           style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px;">
                </div>
                
                <div class="form-group">
                    <label for="payment_notes">Notes (optional):</label>
                    <textarea name="notes" id="payment_notes" rows="3" 
                              placeholder="Additional notes about this payment"
                              style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px;"></textarea>
                </div>
                
                <div style="margin-top: 1.5rem; display: flex; gap: 0.75rem;">
                    <button type="submit" class="btn-primary" style="flex: 1;">
                        <i class="fas fa-money-bill-wave"></i> Record Payment
                    </button>
                    <button type="button" class="btn-secondary" onclick="closeModal('payment-modal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openPercentageModal(expertId, currentPercentage) {
            document.getElementById('percentage-expert-id').value = expertId;
            document.getElementById('percentage-value').value = currentPercentage;
            updateAdminPercentage();
            document.getElementById('percentage-modal').style.display = 'block';
        }
        
        function updateAdminPercentage() {
            const expertPercentage = parseFloat(document.getElementById('percentage-value').value) || 0;
            const adminPercentage = 100 - expertPercentage;
            document.getElementById('admin-percentage').textContent = adminPercentage.toFixed(2) + '%';
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s, transform 0.5s';
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateX(20px)';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            });
            
            const percentageInput = document.getElementById('percentage-value');
            if (percentageInput) {
                percentageInput.addEventListener('input', updateAdminPercentage);
            }
            
            // Form validation
            const percentageForm = document.getElementById('percentage-form');
            if (percentageForm) {
                percentageForm.addEventListener('submit', function(e) {
                    const percentage = parseFloat(document.getElementById('percentage-value').value);
                    if (percentage < 0 || percentage > 100) {
                        e.preventDefault();
                        alert('Percentage must be between 0 and 100');
                        document.getElementById('percentage-value').focus();
                        return false;
                    }
                });
            }
            
            const paymentForm = document.getElementById('payment-form');
            if (paymentForm) {
                paymentForm.addEventListener('submit', function(e) {
                    const paymentMethod = document.getElementById('payment_method').value;
                    if (!paymentMethod) {
                        e.preventDefault();
                        alert('Please select a payment method');
                        document.getElementById('payment_method').focus();
                        return false;
                    }
                    // Add loading state
                    const submitBtn = paymentForm.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    }
                });
            }
        });
        
        function openPaymentModal(expertId, expertName, unpaidAmount, paymentPercentage) {
            const modal = document.getElementById('payment-modal');
            const expertIdInput = document.getElementById('payment-expert-id');
            const expertNameSpan = document.getElementById('payment-expert-name');
            const unpaidAmountSpan = document.getElementById('payment-unpaid-amount');
            const percentageDisplaySpan = document.getElementById('payment-percentage-display');
            const expertAmountSpan = document.getElementById('payment-expert-amount');
            const adminCommissionSpan = document.getElementById('payment-admin-commission');
            
            expertIdInput.value = expertId;
            expertNameSpan.textContent = expertName;
            unpaidAmountSpan.textContent = parseFloat(unpaidAmount).toFixed(2);
            percentageDisplaySpan.textContent = paymentPercentage;
            
            const unpaid = parseFloat(unpaidAmount);
            const percentage = parseFloat(paymentPercentage);
            const expertAmount = (unpaid * percentage) / 100;
            const adminCommission = unpaid - expertAmount;
            
            expertAmountSpan.textContent = '$' + expertAmount.toFixed(2);
            adminCommissionSpan.textContent = '$' + adminCommission.toFixed(2);
            
            const form = document.getElementById('payment-form');
            if (form) {
                form.reset();
                expertIdInput.value = expertId;
            }
            
            modal.style.display = 'block';
            
            setTimeout(() => {
                const paymentMethod = document.getElementById('payment_method');
                if (paymentMethod) paymentMethod.focus();
            }, 100);
        }
        
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
            }
        }
        
        // Close modal when clicking outside or pressing ESC
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    closeModal(modal.id);
                }
            });
        };
        
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modals = document.querySelectorAll('.modal');
                modals.forEach(modal => {
                    if (modal.style.display === 'block') {
                        closeModal(modal.id);
                    }
                });
            }
        });
    </script>
</body>
</html>

