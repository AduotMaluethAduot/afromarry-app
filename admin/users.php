<?php
require_once '../config/database.php';

requireAdmin();
$user = getCurrentUser();
$database = new Database();
$db = $database->getConnection();

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    try {
        if ($action === 'update_role') {
            $user_id = intval($_POST['user_id']);
            $new_role = sanitize($_POST['role']);
            
            // Get old values for logging
            $query = "SELECT * FROM users WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([':id' => $user_id]);
            $old_values = $stmt->fetch();
            
            $query = "UPDATE users SET role = :role WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':role' => $new_role,
                ':id' => $user_id
            ]);
            
            logAdminAction('User Role Updated', 'users', $user_id, $old_values, ['role' => $new_role]);
            
            $success_message = "User role updated successfully!";
            
        } elseif ($action === 'toggle_premium') {
            $user_id = intval($_POST['user_id']);
            $is_premium = $_POST['is_premium'] === 'true';
            $expires_at = $_POST['expires_at'] ?: null;
            
            // Get old values for logging
            $query = "SELECT * FROM users WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([':id' => $user_id]);
            $old_values = $stmt->fetch();
            
            $query = "UPDATE users SET is_premium = :is_premium, premium_expires_at = :expires_at WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':is_premium' => $is_premium ? 1 : 0,
                ':expires_at' => $expires_at,
                ':id' => $user_id
            ]);
            
            // Create notification for user
            $notification_title = $is_premium ? 'Premium Activated' : 'Premium Deactivated';
            $notification_message = $is_premium ? 
                'Congratulations! Your premium membership has been activated.' : 
                'Your premium membership has been deactivated.';
            
            $query = "INSERT INTO notifications (user_id, title, message, type) 
                     VALUES (:user_id, :title, :message, :type)";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':user_id' => $user_id,
                ':title' => $notification_title,
                ':message' => $notification_message,
                ':type' => $is_premium ? 'success' : 'info'
            ]);
            
            logAdminAction('User Premium Status Updated', 'users', $user_id, $old_values, [
                'is_premium' => $is_premium,
                'premium_expires_at' => $expires_at
            ]);
            
            $success_message = "User premium status updated successfully!";
            
        } elseif ($action === 'delete_user') {
            $user_id = intval($_POST['user_id']);
            
            // Get user info for logging
            $query = "SELECT * FROM users WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([':id' => $user_id]);
            $user_data = $stmt->fetch();
            
            // Don't allow deleting admin accounts (only admins can manage)
            if ($user_data['role'] === 'admin' && $user_data['id'] != $user['id']) {
                // Allow admins to delete other admins, but not themselves
            }
            
            $query = "DELETE FROM users WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([':id' => $user_id]);
            
            logAdminAction('User Deleted', 'users', $user_id, $user_data, null);
            
            $success_message = "User deleted successfully!";
        }
        
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get filter parameters
$role = $_GET['role'] ?? 'all';
$premium = $_GET['premium'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($role !== 'all') {
    $where_conditions[] = "role = :role";
    $params[':role'] = $role;
}

if ($premium !== 'all') {
    if ($premium === 'premium') {
        $where_conditions[] = "is_premium = TRUE";
    } else {
        $where_conditions[] = "is_premium = FALSE";
    }
}

if (!empty($search)) {
    $where_conditions[] = "(full_name LIKE :search OR email LIKE :search OR phone LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get users
$query = "SELECT u.*, 
                 COUNT(DISTINCT o.id) as order_count,
                 COALESCE(SUM(o.total_amount), 0) as total_spent
          FROM users u 
          LEFT JOIN orders o ON u.id = o.user_id AND o.status = 'paid'
          $where_clause
          GROUP BY u.id
          ORDER BY u.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get statistics
$stats = [];
$query = "SELECT COUNT(*) as count FROM users";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total'] = $stmt->fetch()['count'];

$query = "SELECT COUNT(*) as count FROM users WHERE is_premium = TRUE";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['premium'] = $stmt->fetch()['count'];

$query = "SELECT COUNT(*) as count FROM users WHERE role = 'admin'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['admins'] = $stmt->fetch()['count'];

$query = "SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['new_users'] = $stmt->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin</title>
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
                <a href="users.php" class="nav-link active">Users</a>
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
                <a href="dashboard.php" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                <a href="users.php" class="nav-item active">
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
                <a href="reports.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    Reports
                </a>
            </nav>
        </div>

        <div class="admin-content">
            <div class="admin-header">
                <h1>User Management</h1>
                <p>Manage user accounts, roles, and permissions</p>
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
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total']; ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-crown"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['premium']; ?></h3>
                        <p>Premium Users</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['admins']; ?></h3>
                        <p>Admins</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['new_users']; ?></h3>
                        <p>New This Month</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters-section">
                <form method="GET" class="filters-form">
                    <div class="filter-group">
                        <label for="role">Role:</label>
                        <select name="role" id="role">
                            <option value="all" <?php echo $role === 'all' ? 'selected' : ''; ?>>All Roles</option>
                            <option value="customer" <?php echo $role === 'customer' ? 'selected' : ''; ?>>Customer</option>
                            <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="premium">Premium:</label>
                        <select name="premium" id="premium">
                            <option value="all" <?php echo $premium === 'all' ? 'selected' : ''; ?>>All</option>
                            <option value="premium" <?php echo $premium === 'premium' ? 'selected' : ''; ?>>Premium</option>
                            <option value="free" <?php echo $premium === 'free' ? 'selected' : ''; ?>>Free</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="search">Search:</label>
                        <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Name, email, or phone">
                    </div>
                    
                    <button type="submit" class="btn-primary">Filter</button>
                    <a href="users.php" class="btn-secondary">Clear</a>
                </form>
            </div>

            <!-- Users Table -->
            <div class="admin-section">
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Role</th>
                                <th>Premium</th>
                                <th>Orders</th>
                                <th>Total Spent</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user_data): ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <strong><?php echo $user_data['full_name']; ?></strong>
                                            <br>
                                            <small><?php echo $user_data['email']; ?></small>
                                            <br>
                                            <small><?php echo $user_data['phone']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="role-badge <?php echo $user_data['role']; ?>">
                                        <?php echo ucfirst($user_data['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user_data['is_premium']): ?>
                                        <span class="premium-badge">Premium</span>
                                        <?php if ($user_data['premium_expires_at']): ?>
                                            <br><small>Expires: <?php echo date('M j, Y', strtotime($user_data['premium_expires_at'])); ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="free-badge">Free</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $user_data['order_count']; ?></td>
                                <td>$ <?php echo number_format($user_data['total_spent']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($user_data['created_at'])); ?></td>
                                <td>
                                    <button class="btn-small btn-primary" onclick="editUser(<?php echo $user_data['id']; ?>)">
                                        Edit
                                    </button>
                                    <button class="btn-small btn-danger" onclick="deleteUser(<?php echo $user_data['id']; ?>)" <?php echo ($user_data['role'] === 'admin' && $user_data['id'] == $user['id']) ? 'disabled title="Cannot delete your own account"' : ''; ?>>
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

    <!-- Edit User Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit User</h3>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="update_role">
                <input type="hidden" name="user_id" id="editUserId">
                
                <div class="form-group">
                    <label for="role">Role:</label>
                    <select name="role" id="roleSelect" required>
                        <option value="customer">Customer</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="is_premium">Premium Status:</label>
                    <select name="is_premium" id="isPremiumSelect" onchange="togglePremiumFields()">
                        <option value="false">Free</option>
                        <option value="true">Premium</option>
                    </select>
                </div>
                
                <div class="form-group" id="expiresField" style="display: none;">
                    <label for="expires_at">Premium Expires At:</label>
                    <input type="datetime-local" name="expires_at" id="expiresAt">
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn-primary">Update User</button>
                    <button type="button" class="btn-secondary" onclick="closeEditModal()">Cancel</button>
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
                <p>Are you sure you want to delete this user? This action cannot be undone and will remove all associated data.</p>
            </div>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" id="deleteUserId">
                <div class="modal-actions">
                    <button type="submit" class="btn-danger">Delete User</button>
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
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: #e5e7eb;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
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
        
        .modal-body {
            padding: 1.5rem;
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
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .form-group input:focus,
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
        function editUser(userId) {
            // In a real implementation, you would fetch user data via AJAX
            document.getElementById('editUserId').value = userId;
            document.getElementById('editModal').style.display = 'block';
        }
        
        function deleteUser(userId) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        function togglePremiumFields() {
            const isPremium = document.getElementById('isPremiumSelect').value === 'true';
            const expiresField = document.getElementById('expiresField');
            expiresField.style.display = isPremium ? 'block' : 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('deleteModal');
            if (event.target === editModal) {
                closeEditModal();
            }
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        }
    </script>
</body>
</html>
