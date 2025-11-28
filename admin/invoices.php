<?php
require_once '../config/database.php';

requireAdmin();
$user = getCurrentUser();
$database = new Database();
$db = $database->getConnection();

// Handle invoice actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    try {
        if ($action === 'generate') {
            $order_id = intval($_POST['order_id']);
            
            // Get order details
            $query = "SELECT * FROM orders WHERE id = :order_id";
            $stmt = $db->prepare($query);
            $stmt->execute([':order_id' => $order_id]);
            $order = $stmt->fetch();
            
            if (!$order) {
                throw new Exception("Order not found");
            }
            
            // Check if invoice already exists
            $query = "SELECT * FROM invoices WHERE order_id = :order_id";
            $stmt = $db->prepare($query);
            $stmt->execute([':order_id' => $order_id]);
            $existing_invoice = $stmt->fetch();
            
            if ($existing_invoice) {
                throw new Exception("Invoice already exists for this order");
            }
            
            // Generate invoice number
            $invoice_number = generateInvoiceNumber();
            
            // Calculate tax (5% of total amount)
            $tax_amount = $order['total_amount'] * 0.05;
            $total_amount = $order['total_amount'] + $tax_amount;
            
            // Create invoice
            $query = "INSERT INTO invoices (order_id, invoice_number, total_amount, tax_amount, status, due_date) 
                     VALUES (:order_id, :invoice_number, :total_amount, :tax_amount, 'draft', DATE_ADD(NOW(), INTERVAL 30 DAY))";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':order_id' => $order_id,
                ':invoice_number' => $invoice_number,
                ':total_amount' => $total_amount,
                ':tax_amount' => $tax_amount
            ]);
            
            logAdminAction('Invoice Generated', 'invoices', $db->lastInsertId(), null, [
                'order_id' => $order_id,
                'invoice_number' => $invoice_number
            ]);
            
            $success_message = "Invoice generated successfully!";
            
        } elseif ($action === 'update_status') {
            $invoice_id = intval($_POST['invoice_id']);
            $new_status = sanitize($_POST['status']);
            
            $query = "UPDATE invoices SET status = :status WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':status' => $new_status,
                ':id' => $invoice_id
            ]);
            
            if ($new_status === 'sent') {
                $query = "UPDATE invoices SET sent_at = NOW() WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->execute([':id' => $invoice_id]);
            } elseif ($new_status === 'paid') {
                $query = "UPDATE invoices SET paid_at = NOW() WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->execute([':id' => $invoice_id]);
            }
            
            logAdminAction('Invoice Status Updated', 'invoices', $invoice_id, null, ['status' => $new_status]);
            
            $success_message = "Invoice status updated successfully!";
        }
        
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($status !== 'all') {
    $where_conditions[] = "i.status = :status";
    $params[':status'] = $status;
}

if (!empty($search)) {
    $where_conditions[] = "(u.full_name LIKE :search OR u.email LIKE :search OR i.invoice_number LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get invoices
$query = "SELECT i.*, o.id as order_id, o.total_amount as order_total, o.created_at as order_date,
                 u.full_name, u.email, u.phone
          FROM invoices i 
          JOIN orders o ON i.order_id = o.id 
          JOIN users u ON o.user_id = u.id
          $where_clause
          ORDER BY i.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$invoices = $stmt->fetchAll();

// Get statistics
$stats = [];
$query = "SELECT status, COUNT(*) as count FROM invoices GROUP BY status";
$stmt = $db->prepare($query);
$stmt->execute();
$status_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$stats['draft'] = $status_counts['draft'] ?? 0;
$stats['sent'] = $status_counts['sent'] ?? 0;
$stats['paid'] = $status_counts['paid'] ?? 0;
$stats['overdue'] = $status_counts['overdue'] ?? 0;
$stats['total'] = array_sum($status_counts);

$query = "SELECT SUM(total_amount) as total_amount FROM invoices WHERE status = 'paid'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['paid_amount'] = $stmt->fetch()['total_amount'] ?? 0;

$query = "SELECT SUM(total_amount) as total_amount FROM invoices WHERE status IN ('draft', 'sent')";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['pending_amount'] = $stmt->fetch()['total_amount'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Management - Admin</title>
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
                <a href="payments.php" class="nav-link">Payments</a>
                <a href="invoices.php" class="nav-link active">Invoices</a>
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
                <a href="invoices.php" class="nav-item active">
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
                <h1>Invoice Management</h1>
                <p>Generate and manage customer invoices</p>
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
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total']; ?></h3>
                        <p>Total Invoices</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['paid']; ?></h3>
                        <p>Paid Invoices</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['sent']; ?></h3>
                        <p>Sent Invoices</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['overdue']; ?></h3>
                        <p>Overdue</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-content">
                        <h3>$ <?php echo number_format($stats['paid_amount']); ?></h3>
                        <p>Paid Amount</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="stat-content">
                        <h3>$ <?php echo number_format($stats['pending_amount']); ?></h3>
                        <p>Pending Amount</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="btn-primary" onclick="openGenerateModal()">
                    <i class="fas fa-plus"></i>
                    Generate Invoice
                </button>
            </div>

            <!-- Filters -->
            <div class="filters-section">
                <form method="GET" class="filters-form">
                    <div class="filter-group">
                        <label for="status">Status:</label>
                        <select name="status" id="status">
                            <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="sent" <?php echo $status === 'sent' ? 'selected' : ''; ?>>Sent</option>
                            <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="overdue" <?php echo $status === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="search">Search:</label>
                        <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Customer name, email, or invoice number">
                    </div>
                    
                    <button type="submit" class="btn-primary">Filter</button>
                    <a href="invoices.php" class="btn-secondary">Clear</a>
                </form>
            </div>

            <!-- Invoices Table -->
            <div class="admin-section">
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Tax</th>
                                <th>Status</th>
                                <th>Due Date</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $invoice): ?>
                            <tr>
                                <td><?php echo $invoice['invoice_number']; ?></td>
                                <td>#<?php echo $invoice['order_id']; ?></td>
                                <td>
                                    <div>
                                        <strong><?php echo $invoice['full_name']; ?></strong>
                                        <br>
                                        <small><?php echo $invoice['email']; ?></small>
                                    </div>
                                </td>
                                <td>$ <?php echo number_format($invoice['total_amount']); ?></td>
                                <td>$ <?php echo number_format($invoice['tax_amount']); ?></td>
                                <td>
                                    <span class="status <?php echo $invoice['status']; ?>">
                                        <?php echo ucfirst($invoice['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($invoice['due_date']): ?>
                                        <?php echo date('M j, Y', strtotime($invoice['due_date'])); ?>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($invoice['created_at'])); ?></td>
                                <td>
                                    <button class="btn-small btn-primary" onclick="viewInvoice(<?php echo $invoice['id']; ?>)">
                                        View
                                    </button>
                                    <button class="btn-small btn-secondary" onclick="updateInvoiceStatus(<?php echo $invoice['id']; ?>, '<?php echo $invoice['status']; ?>')">
                                        Update
                                    </button>
                                    <button type="button" class="btn-small btn-success" onclick="alert('PDF export feature coming soon!')">
                                        PDF
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Generate Invoice Modal -->
    <div id="generateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Generate Invoice</h3>
                <span class="close" onclick="closeGenerateModal()">&times;</span>
            </div>
            <form method="POST" id="generateForm">
                <input type="hidden" name="action" value="generate">
                
                <div class="form-group">
                    <label for="order_id">Select Order:</label>
                    <select name="order_id" id="order_id" required>
                        <option value="">Select an order...</option>
                        <?php
                        // Get orders without invoices
                        $query = "SELECT o.id, o.total_amount, o.created_at, u.full_name 
                                 FROM orders o 
                                 JOIN users u ON o.user_id = u.id 
                                 LEFT JOIN invoices i ON o.id = i.order_id 
                                 WHERE i.id IS NULL AND o.status = 'paid'
                                 ORDER BY o.created_at DESC";
                        $stmt = $db->prepare($query);
                        $stmt->execute();
                        $available_orders = $stmt->fetchAll();
                        
                        foreach ($available_orders as $order):
                        ?>
                            <option value="<?php echo $order['id']; ?>">
                                Order #<?php echo $order['id']; ?> - <?php echo $order['full_name']; ?> 
                                ($ <?php echo number_format($order['total_amount']); ?>) - 
                                <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn-primary">Generate Invoice</button>
                    <button type="button" class="btn-secondary" onclick="closeGenerateModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Update Invoice Status</h3>
                <span class="close" onclick="closeStatusModal()">&times;</span>
            </div>
            <form method="POST" id="statusForm">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="invoice_id" id="statusInvoiceId">
                
                <div class="form-group">
                    <label for="status">New Status:</label>
                    <select name="status" id="statusSelect" required>
                        <option value="draft">Draft</option>
                        <option value="sent">Sent</option>
                        <option value="paid">Paid</option>
                        <option value="overdue">Overdue</option>
                    </select>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn-primary">Update Status</button>
                    <button type="button" class="btn-secondary" onclick="closeStatusModal()">Cancel</button>
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
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
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
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }
        
        .stat-content p {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .action-buttons {
            margin-bottom: 2rem;
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
        
        .status.draft {
            background: #f3f4f6;
            color: #6b7280;
        }
        
        .status.sent {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status.paid {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status.overdue {
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
            display: inline-block;
        }
        
        .btn-small.btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-small.btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-small.btn-success {
            background: #10b981;
            color: white;
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
            margin: 10% auto;
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
            padding: 0 1.5rem;
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
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
        function openGenerateModal() {
            document.getElementById('generateModal').style.display = 'block';
        }
        
        function closeGenerateModal() {
            document.getElementById('generateModal').style.display = 'none';
        }
        
        function updateInvoiceStatus(invoiceId, currentStatus) {
            document.getElementById('statusInvoiceId').value = invoiceId;
            document.getElementById('statusSelect').value = currentStatus;
            document.getElementById('statusModal').style.display = 'block';
        }
        
        function closeStatusModal() {
            document.getElementById('statusModal').style.display = 'none';
        }
        
        function viewInvoice(invoiceId) {
            // In a real implementation, you would show invoice details
            alert('Invoice details would be shown here for invoice #' + invoiceId);
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const generateModal = document.getElementById('generateModal');
            const statusModal = document.getElementById('statusModal');
            if (event.target === generateModal) {
                closeGenerateModal();
            }
            if (event.target === statusModal) {
                closeStatusModal();
            }
        }
    </script>
</body>
</html>
