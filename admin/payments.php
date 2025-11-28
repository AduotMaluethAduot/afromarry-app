<?php
require_once '../config/database.php';

requireAdmin();
$user = getCurrentUser();
$database = new Database();
$db = $database->getConnection();

// Handle payment verification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $payment_id = $_POST['payment_id'];
    $notes = $_POST['notes'] ?? '';
    
    try {
        if ($action === 'verify') {
            // Verify payment
            $query = "UPDATE payment_verifications SET status = 'verified', verified_by = :admin_id, 
                     verification_notes = :notes, verified_at = NOW() WHERE id = :payment_id";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':admin_id' => $user['id'],
                ':notes' => $notes,
                ':payment_id' => $payment_id
            ]);
            
            // Update order status
            $query = "UPDATE orders SET status = 'paid', payment_verified = TRUE 
                     WHERE id = (SELECT order_id FROM payment_verifications WHERE id = :payment_id)";
            $stmt = $db->prepare($query);
            $stmt->execute([':payment_id' => $payment_id]);
            
            // Create notification for user
            $query = "INSERT INTO notifications (user_id, title, message, type) 
                     SELECT o.user_id, 'Payment Verified', 'Your payment has been verified and your order is being processed.', 'success'
                     FROM orders o 
                     JOIN payment_verifications pv ON o.id = pv.order_id 
                     WHERE pv.id = :payment_id";
            $stmt = $db->prepare($query);
            $stmt->execute([':payment_id' => $payment_id]);
            
            logAdminAction('Payment Verified', 'payment_verifications', $payment_id, null, ['status' => 'verified']);
            
            $success_message = "Payment verified successfully!";
            
        } elseif ($action === 'reject') {
            // Reject payment
            $query = "UPDATE payment_verifications SET status = 'rejected', verified_by = :admin_id, 
                     verification_notes = :notes, verified_at = NOW() WHERE id = :payment_id";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':admin_id' => $user['id'],
                ':notes' => $notes,
                ':payment_id' => $payment_id
            ]);
            
            // Create notification for user
            $query = "INSERT INTO notifications (user_id, title, message, type) 
                     SELECT o.user_id, 'Payment Rejected', 'Your payment proof was rejected. Please contact support for assistance.', 'error'
                     FROM orders o 
                     JOIN payment_verifications pv ON o.id = pv.order_id 
                     WHERE pv.id = :payment_id";
            $stmt = $db->prepare($query);
            $stmt->execute([':payment_id' => $payment_id]);
            
            logAdminAction('Payment Rejected', 'payment_verifications', $payment_id, null, ['status' => 'rejected']);
            
            $success_message = "Payment rejected successfully!";
        }
        
    } catch (Exception $e) {
        $error_message = "Error processing payment: " . $e->getMessage();
    }
}

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($status !== 'all') {
    $where_conditions[] = "pv.status = :status";
    $params[':status'] = $status;
}

if (!empty($search)) {
    $where_conditions[] = "(u.full_name LIKE :search OR u.email LIKE :search OR pv.payment_reference LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get payment verifications
$query = "SELECT pv.*, o.total_amount, o.id as order_id, u.full_name, u.email, u.phone,
                 admin.full_name as verified_by_name
          FROM payment_verifications pv 
          JOIN orders o ON pv.order_id = o.id 
          JOIN users u ON o.user_id = u.id 
          LEFT JOIN users admin ON pv.verified_by = admin.id
          $where_clause
          ORDER BY pv.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Get statistics
$stats = [];
$query = "SELECT status, COUNT(*) as count FROM payment_verifications GROUP BY status";
$stmt = $db->prepare($query);
$stmt->execute();
$status_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$stats['pending'] = $status_counts['pending'] ?? 0;
$stats['verified'] = $status_counts['verified'] ?? 0;
$stats['rejected'] = $status_counts['rejected'] ?? 0;
$stats['total'] = array_sum($status_counts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Verification - Admin</title>
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
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="users.php" class="nav-link">Users</a>
                <a href="orders.php" class="nav-link">Orders</a>
                <a href="products.php" class="nav-link">Products</a>
                <a href="payments.php" class="nav-link active">Payments</a>
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
                <a href="payments.php" class="nav-item active">
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
                <h1>Payment Verification</h1>
                <p>Review and verify customer payment proofs</p>
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
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['pending']; ?></h3>
                        <p>Pending</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['verified']; ?></h3>
                        <p>Verified</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['rejected']; ?></h3>
                        <p>Rejected</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-list"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total']; ?></h3>
                        <p>Total</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters-section">
                <form method="GET" class="filters-form">
                    <div class="filter-group">
                        <label for="status">Status:</label>
                        <select name="status" id="status">
                            <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All</option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="verified" <?php echo $status === 'verified' ? 'selected' : ''; ?>>Verified</option>
                            <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="search">Search:</label>
                        <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Customer name, email, or reference">
                    </div>
                    
                    <button type="submit" class="btn-primary">Filter</button>
                    <a href="payments.php" class="btn-secondary">Clear</a>
                </form>
            </div>

            <!-- Payments Table -->
            <div class="admin-section">
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Reference</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td>#<?php echo $payment['id']; ?></td>
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
                                <td>
                                    <span class="status <?php echo $payment['status']; ?>">
                                        <?php echo ucfirst($payment['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($payment['created_at'])); ?></td>
                                <td>
                                    <?php if ($payment['status'] === 'pending'): ?>
                                        <button class="btn-small btn-success" onclick="openVerificationModal(<?php echo $payment['id']; ?>, 'verify')">
                                            Verify
                                        </button>
                                        <button class="btn-small btn-danger" onclick="openVerificationModal(<?php echo $payment['id']; ?>, 'reject')">
                                            Reject
                                        </button>
                                    <?php else: ?>
                                        <span class="verified-by">
                                            <?php echo $payment['verified_by_name'] ? 'By: ' . $payment['verified_by_name'] : ''; ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($payment['proof_image']): ?>
                                        <a href="../<?php echo $payment['proof_image']; ?>" target="_blank" class="btn-small">
                                            View Proof
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Verification Modal -->
    <div id="verificationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Verify Payment</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="verificationForm">
                <input type="hidden" name="action" id="actionInput">
                <input type="hidden" name="payment_id" id="paymentIdInput">
                
                <div class="form-group">
                    <label for="notes">Notes (Optional):</label>
                    <textarea name="notes" id="notes" rows="4" placeholder="Add any notes about this verification..."></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn-primary" id="submitBtn">Confirm</button>
                    <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                </div>
            </form>
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
        
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
            background: linear-gradient(135deg, #dc2626, #b91c1c);
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
        
        .filter-group input,
        .filter-group select {
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .admin-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
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
        
        .status {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status.pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status.verified {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status.rejected {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .btn-small {
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 600;
            margin-right: 0.5rem;
            border: none;
            cursor: pointer;
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
        
        .verified-by {
            font-size: 0.8rem;
            color: #6b7280;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .modal-header h3 {
            color: #1f2937;
            margin: 0;
        }
        
        .close {
            color: #6b7280;
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #374151;
        }
        
        .form-group {
            padding: 1.5rem;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            resize: vertical;
        }
        
        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            padding: 1.5rem;
            border-top: 1px solid #e5e7eb;
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
            
            .filters-form {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>

    <script>
        function openVerificationModal(paymentId, action) {
            document.getElementById('paymentIdInput').value = paymentId;
            document.getElementById('actionInput').value = action;
            
            const modal = document.getElementById('verificationModal');
            const title = document.getElementById('modalTitle');
            const submitBtn = document.getElementById('submitBtn');
            
            if (action === 'verify') {
                title.textContent = 'Verify Payment';
                submitBtn.textContent = 'Verify Payment';
                submitBtn.className = 'btn-primary';
            } else {
                title.textContent = 'Reject Payment';
                submitBtn.textContent = 'Reject Payment';
                submitBtn.className = 'btn-primary btn-danger';
            }
            
            modal.style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('verificationModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('verificationModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
