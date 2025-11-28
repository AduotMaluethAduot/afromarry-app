<?php
require_once '../config/database.php';

requireAdmin();
$user = getCurrentUser();
$database = new Database();
$db = $database->getConnection();

// Handle coupon actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    try {
        if ($action === 'create') {
            $code = strtoupper(sanitize($_POST['code']));
            $description = sanitize($_POST['description']);
            $discount_type = sanitize($_POST['discount_type']);
            $discount_value = floatval($_POST['discount_value']);
            $minimum_amount = floatval($_POST['minimum_amount'] ?? 0);
            $maximum_discount = !empty($_POST['maximum_discount']) ? floatval($_POST['maximum_discount']) : null;
            $usage_limit = !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null;
            $valid_until = !empty($_POST['valid_until']) ? sanitize($_POST['valid_until']) : null;
            
            // Check if code already exists
            $query = "SELECT id FROM coupons WHERE code = :code";
            $stmt = $db->prepare($query);
            $stmt->execute([':code' => $code]);
            if ($stmt->fetch()) {
                throw new Exception("Coupon code already exists");
            }
            
            $query = "INSERT INTO coupons (code, description, discount_type, discount_value, minimum_amount, 
                     maximum_discount, usage_limit, valid_until, created_by) 
                     VALUES (:code, :description, :discount_type, :discount_value, :minimum_amount, 
                     :maximum_discount, :usage_limit, :valid_until, :created_by)";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':code' => $code,
                ':description' => $description,
                ':discount_type' => $discount_type,
                ':discount_value' => $discount_value,
                ':minimum_amount' => $minimum_amount,
                ':maximum_discount' => $maximum_discount,
                ':usage_limit' => $usage_limit,
                ':valid_until' => $valid_until,
                ':created_by' => $user['id']
            ]);
            
            logAdminAction('Coupon Created', 'coupons', $db->lastInsertId(), null, ['code' => $code]);
            
            $success_message = "Coupon created successfully!";
            
        } elseif ($action === 'update') {
            $id = intval($_POST['id']);
            $code = strtoupper(sanitize($_POST['code']));
            $description = sanitize($_POST['description']);
            $discount_type = sanitize($_POST['discount_type']);
            $discount_value = floatval($_POST['discount_value']);
            $minimum_amount = floatval($_POST['minimum_amount'] ?? 0);
            $maximum_discount = !empty($_POST['maximum_discount']) ? floatval($_POST['maximum_discount']) : null;
            $usage_limit = !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null;
            $valid_until = !empty($_POST['valid_until']) ? sanitize($_POST['valid_until']) : null;
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // Get old values for logging
            $query = "SELECT * FROM coupons WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([':id' => $id]);
            $old_values = $stmt->fetch();
            
            $query = "UPDATE coupons SET code = :code, description = :description, discount_type = :discount_type,
                     discount_value = :discount_value, minimum_amount = :minimum_amount,
                     maximum_discount = :maximum_discount, usage_limit = :usage_limit,
                     valid_until = :valid_until, is_active = :is_active WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':id' => $id,
                ':code' => $code,
                ':description' => $description,
                ':discount_type' => $discount_type,
                ':discount_value' => $discount_value,
                ':minimum_amount' => $minimum_amount,
                ':maximum_discount' => $maximum_discount,
                ':usage_limit' => $usage_limit,
                ':valid_until' => $valid_until,
                ':is_active' => $is_active
            ]);
            
            logAdminAction('Coupon Updated', 'coupons', $id, $old_values, [
                'code' => $code,
                'discount_type' => $discount_type,
                'discount_value' => $discount_value
            ]);
            
            $success_message = "Coupon updated successfully!";
            
        } elseif ($action === 'delete') {
            $id = intval($_POST['id']);
            
            // Get coupon info for logging
            $query = "SELECT * FROM coupons WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([':id' => $id]);
            $coupon = $stmt->fetch();
            
            $query = "DELETE FROM coupons WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([':id' => $id]);
            
            logAdminAction('Coupon Deleted', 'coupons', $id, $coupon, null);
            
            $success_message = "Coupon deleted successfully!";
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

if ($status === 'active') {
    $where_conditions[] = "is_active = TRUE AND (valid_until IS NULL OR valid_until >= CURDATE())";
} elseif ($status === 'inactive') {
    $where_conditions[] = "is_active = FALSE";
} elseif ($status === 'expired') {
    $where_conditions[] = "valid_until < CURDATE()";
}

if (!empty($search)) {
    $where_conditions[] = "(code LIKE :search OR description LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get coupons
$query = "SELECT c.*, u.full_name as created_by_name 
          FROM coupons c
          LEFT JOIN users u ON c.created_by = u.id
          $where_clause
          ORDER BY c.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$coupons = $stmt->fetchAll();

// Get statistics
$stats = [];
$query = "SELECT COUNT(*) as count FROM coupons";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total'] = $stmt->fetch()['count'];

$query = "SELECT COUNT(*) as count FROM coupons WHERE is_active = TRUE AND (valid_until IS NULL OR valid_until >= CURDATE())";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['active'] = $stmt->fetch()['count'];

$query = "SELECT COUNT(*) as count FROM coupons WHERE used_count > 0";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['used'] = $stmt->fetch()['count'];

$query = "SELECT SUM(used_count) as total_usage FROM coupons";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_usage'] = $stmt->fetch()['total_usage'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coupon Management - Admin</title>
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
                <a href="coupons.php" class="nav-link active">Coupons</a>
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
                <a href="coupons.php" class="nav-item active">
                    <i class="fas fa-tags"></i>
                    Coupons
                </a>
                <a href="reports.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    Reports
                </a>
                <?php if (isAdmin()): ?>
                <a href="settings.php" class="nav-item">
                    <i class="fas fa-cog"></i>
                    System Settings
                </a>
                <?php endif; ?>
            </nav>
        </div>

        <div class="admin-content">
            <div class="admin-header">
                <h1>Coupon Management</h1>
                <p>Create and manage discount coupons</p>
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
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total']; ?></h3>
                        <p>Total Coupons</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['active']; ?></h3>
                        <p>Active Coupons</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['used']; ?></h3>
                        <p>Used Coupons</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_usage']; ?></h3>
                        <p>Total Usage</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="btn-primary" onclick="openCouponModal()">
                    <i class="fas fa-plus"></i>
                    Create New Coupon
                </button>
            </div>

            <!-- Filters -->
            <div class="filters-section">
                <form method="GET" class="filters-form">
                    <div class="filter-group">
                        <label for="status">Status:</label>
                        <select name="status" id="status">
                            <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All</option>
                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="expired" <?php echo $status === 'expired' ? 'selected' : ''; ?>>Expired</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="search">Search:</label>
                        <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Coupon code or description">
                    </div>
                    
                    <button type="submit" class="btn-primary">Filter</button>
                    <a href="coupons.php" class="btn-secondary">Clear</a>
                </form>
            </div>

            <!-- Coupons Table -->
            <div class="admin-section">
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Description</th>
                                <th>Discount</th>
                                <th>Min. Amount</th>
                                <th>Usage</th>
                                <th>Valid Until</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($coupons as $coupon): ?>
                            <tr>
                                <td><strong><?php echo $coupon['code']; ?></strong></td>
                                <td><?php echo $coupon['description']; ?></td>
                                <td>
                                    <?php if ($coupon['discount_type'] === 'percentage'): ?>
                                        <?php echo $coupon['discount_value']; ?>%
                                    <?php else: ?>
                                        $ <?php echo number_format($coupon['discount_value']); ?>
                                    <?php endif; ?>
                                    <?php if ($coupon['maximum_discount']): ?>
                                        <br><small>Max: $<?php echo number_format($coupon['maximum_discount']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>$ <?php echo number_format($coupon['minimum_amount']); ?></td>
                                <td>
                                    <?php echo $coupon['used_count']; ?>
                                    <?php if ($coupon['usage_limit']): ?>
                                        / <?php echo $coupon['usage_limit']; ?>
                                    <?php else: ?>
                                        / ∞
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($coupon['valid_until']): ?>
                                        <?php echo date('M j, Y', strtotime($coupon['valid_until'])); ?>
                                    <?php else: ?>
                                        No expiry
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($coupon['is_active'] && (!$coupon['valid_until'] || strtotime($coupon['valid_until']) >= time())): ?>
                                        <span class="status active">Active</span>
                                    <?php elseif (!$coupon['is_active']): ?>
                                        <span class="status inactive">Inactive</span>
                                    <?php else: ?>
                                        <span class="status expired">Expired</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $coupon['created_by_name'] ?? 'N/A'; ?></td>
                                <td>
                                    <button class="btn-small btn-primary" onclick="editCoupon(<?php echo $coupon['id']; ?>)">
                                        Edit
                                    </button>
                                    <button class="btn-small btn-danger" onclick="deleteCoupon(<?php echo $coupon['id']; ?>)">
                                        Delete
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

    <!-- Coupon Modal -->
    <div id="couponModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Create New Coupon</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="couponForm">
                <input type="hidden" name="action" id="actionInput" value="create">
                <input type="hidden" name="id" id="couponIdInput">
                
                <div class="form-group">
                    <label for="code">Coupon Code *</label>
                    <input type="text" name="code" id="code" required pattern="[A-Z0-9]+" 
                           placeholder="e.g., SAVE20" style="text-transform: uppercase;">
                    <small>Only uppercase letters and numbers</small>
                </div>
                
                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea name="description" id="description" rows="3" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="discount_type">Discount Type *</label>
                        <select name="discount_type" id="discount_type" required>
                            <option value="percentage">Percentage</option>
                            <option value="fixed">Fixed Amount</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="discount_value">Discount Value *</label>
                        <input type="number" name="discount_value" id="discount_value" step="0.01" required min="0">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="minimum_amount">Minimum Amount</label>
                        <input type="number" name="minimum_amount" id="minimum_amount" step="0.01" min="0" value="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="maximum_discount">Maximum Discount (Optional)</label>
                        <input type="number" name="maximum_discount" id="maximum_discount" step="0.01" min="0">
                        <small>Only applies to percentage discounts</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="usage_limit">Usage Limit (Optional)</label>
                        <input type="number" name="usage_limit" id="usage_limit" min="1">
                        <small>Leave empty for unlimited</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="valid_until">Valid Until (Optional)</label>
                        <input type="date" name="valid_until" id="valid_until">
                        <small>Leave empty for no expiry</small>
                    </div>
                </div>
                
                <div class="form-group" id="activeField" style="display: none;">
                    <label>
                        <input type="checkbox" name="is_active" id="is_active" value="1" checked>
                        Active
                    </label>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn-primary" id="submitBtn">Create Coupon</button>
                    <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm Delete</h3>
                <span class="close" onclick="closeDeleteModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this coupon? This action cannot be undone.</p>
            </div>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteCouponId">
                <div class="modal-actions">
                    <button type="submit" class="btn-danger">Delete Coupon</button>
                    <button type="button" class="btn-secondary" onclick="closeDeleteModal()">Cancel</button>
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
        
        .status.active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status.inactive {
            background: #f3f4f6;
            color: #6b7280;
        }
        
        .status.expired {
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
        
        .btn-small.btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-small.btn-danger {
            background: #ef4444;
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
            margin: 5% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            max-height: 90vh;
            overflow-y: auto;
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
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
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
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
        }
        
        .form-group small {
            display: block;
            margin-top: 0.25rem;
            color: #6b7280;
            font-size: 0.8rem;
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
        
        .btn-danger {
            background: #ef4444;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .modal-body {
            padding: 1.5rem;
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
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        function openCouponModal() {
            document.getElementById('couponModal').style.display = 'block';
            document.getElementById('actionInput').value = 'create';
            document.getElementById('modalTitle').textContent = 'Create New Coupon';
            document.getElementById('submitBtn').textContent = 'Create Coupon';
            document.getElementById('couponForm').reset();
            document.getElementById('activeField').style.display = 'none';
        }
        
        function editCoupon(id) {
            // In a real implementation, you would fetch coupon data via AJAX
            document.getElementById('couponModal').style.display = 'block';
            document.getElementById('actionInput').value = 'update';
            document.getElementById('couponIdInput').value = id;
            document.getElementById('modalTitle').textContent = 'Edit Coupon';
            document.getElementById('submitBtn').textContent = 'Update Coupon';
            document.getElementById('activeField').style.display = 'block';
        }
        
        function deleteCoupon(id) {
            document.getElementById('deleteModal').style.display = 'block';
            document.getElementById('deleteCouponId').value = id;
        }
        
        function closeModal() {
            document.getElementById('couponModal').style.display = 'none';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const couponModal = document.getElementById('couponModal');
            const deleteModal = document.getElementById('deleteModal');
            if (event.target === couponModal) {
                closeModal();
            }
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        }
    </script>
</body>
</html>

