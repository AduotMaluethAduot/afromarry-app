<?php
require_once '../config/database.php';

requireAuth();
$user = getCurrentUser();
$database = new Database();
$db = $database->getConnection();

// Handle notification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    try {
        if ($action === 'mark_read') {
            $notification_id = intval($_POST['notification_id']);
            
            $query = "UPDATE notifications SET is_read = TRUE WHERE id = :id AND user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':id' => $notification_id,
                ':user_id' => $user['id']
            ]);
            
            echo json_encode(['success' => true]);
            exit();
            
        } elseif ($action === 'mark_all_read') {
            $query = "UPDATE notifications SET is_read = TRUE WHERE user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->execute([':user_id' => $user['id']]);
            
            echo json_encode(['success' => true]);
            exit();
            
        } elseif ($action === 'delete') {
            $notification_id = intval($_POST['notification_id']);
            
            $query = "DELETE FROM notifications WHERE id = :id AND user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':id' => $notification_id,
                ':user_id' => $user['id']
            ]);
            
            echo json_encode(['success' => true]);
            exit();
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

// Get filter parameters
$type = $_GET['type'] ?? 'all';
$status = $_GET['status'] ?? 'all';

// Build query
$where_conditions = ["user_id = :user_id"];
$params = [':user_id' => $user['id']];

if ($type !== 'all') {
    $where_conditions[] = "type = :type";
    $params[':type'] = $type;
}

if ($status !== 'all') {
    if ($status === 'unread') {
        $where_conditions[] = "is_read = FALSE";
    } else {
        $where_conditions[] = "is_read = TRUE";
    }
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Get notifications
$query = "SELECT * FROM notifications $where_clause ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$notifications = $stmt->fetchAll();

// Get statistics
$stats = [];
$query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->execute([':user_id' => $user['id']]);
$stats['total'] = $stmt->fetch()['count'];

$query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND is_read = FALSE";
$stmt = $db->prepare($query);
$stmt->execute([':user_id' => $user['id']]);
$stats['unread'] = $stmt->fetch()['count'];

$query = "SELECT type, COUNT(*) as count FROM notifications WHERE user_id = :user_id GROUP BY type";
$stmt = $db->prepare($query);
$stmt->execute([':user_id' => $user['id']]);
$type_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$stats['info'] = $type_counts['info'] ?? 0;
$stats['success'] = $type_counts['success'] ?? 0;
$stats['warning'] = $type_counts['warning'] ?? 0;
$stats['error'] = $type_counts['error'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - AfroMarry</title>
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
                    <span>AfroMarry</span>
                </a>
            </div>
            <div class="nav-menu">
                <a href="../index.php" class="nav-link">Home</a>
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="../index.php#marketplace" class="nav-link">Marketplace</a>
                <a href="cart.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    Cart
                </a>
                <a href="../auth/logout.php" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <div class="notifications-container">
        <div class="notifications-header">
            <h1>Notifications</h1>
            <p>Stay updated with your latest activities</p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['total']; ?></h3>
                    <p>Total Notifications</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-bell-slash"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['unread']; ?></h3>
                    <p>Unread</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['info']; ?></h3>
                    <p>Info</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['success']; ?></h3>
                    <p>Success</p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button class="btn-primary" onclick="markAllAsRead()">
                <i class="fas fa-check-double"></i>
                Mark All as Read
            </button>
            <button class="btn-secondary" onclick="refreshNotifications()">
                <i class="fas fa-sync"></i>
                Refresh
            </button>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <form method="GET" class="filters-form">
                <div class="filter-group">
                    <label for="type">Type:</label>
                    <select name="type" id="type">
                        <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>All Types</option>
                        <option value="info" <?php echo $type === 'info' ? 'selected' : ''; ?>>Info</option>
                        <option value="success" <?php echo $type === 'success' ? 'selected' : ''; ?>>Success</option>
                        <option value="warning" <?php echo $type === 'warning' ? 'selected' : ''; ?>>Warning</option>
                        <option value="error" <?php echo $type === 'error' ? 'selected' : ''; ?>>Error</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="status">Status:</label>
                    <select name="status" id="status">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="unread" <?php echo $status === 'unread' ? 'selected' : ''; ?>>Unread</option>
                        <option value="read" <?php echo $status === 'read' ? 'selected' : ''; ?>>Read</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-primary">Filter</button>
                <a href="notifications.php" class="btn-secondary">Clear</a>
            </form>
        </div>

        <!-- Notifications List -->
        <div class="notifications-list">
            <?php if (empty($notifications)): ?>
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <h3>No notifications</h3>
                    <p>You're all caught up! Check back later for updates.</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>" 
                     data-id="<?php echo $notification['id']; ?>">
                    <div class="notification-icon">
                        <i class="fas fa-<?php echo $notification['type'] === 'success' ? 'check-circle' : 
                                               ($notification['type'] === 'error' ? 'exclamation-circle' : 
                                               ($notification['type'] === 'warning' ? 'exclamation-triangle' : 'info-circle')); ?>"></i>
                    </div>
                    
                    <div class="notification-content">
                        <div class="notification-header">
                            <h4><?php echo $notification['title']; ?></h4>
                            <div class="notification-actions">
                                <?php if (!$notification['is_read']): ?>
                                    <button class="btn-small btn-primary" onclick="markAsRead(<?php echo $notification['id']; ?>)">
                                        Mark Read
                                    </button>
                                <?php endif; ?>
                                <button class="btn-small btn-danger" onclick="deleteNotification(<?php echo $notification['id']; ?>)">
                                    Delete
                                </button>
                            </div>
                        </div>
                        
                        <p class="notification-message"><?php echo $notification['message']; ?></p>
                        
                        <div class="notification-footer">
                            <span class="notification-time">
                                <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                            </span>
                            <span class="notification-type <?php echo $notification['type']; ?>">
                                <?php echo ucfirst($notification['type']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .notifications-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .notifications-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .notifications-header h1 {
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        
        .notifications-header p {
            color: #6b7280;
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
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            justify-content: center;
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
            justify-content: center;
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
        
        .filter-group select {
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .notifications-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .notification-item {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            display: flex;
            gap: 1rem;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .notification-item.unread {
            border-left-color: #8B5CF6;
            background: #f8fafc;
        }
        
        .notification-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .notification-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        
        .notification-item.unread .notification-icon {
            background: linear-gradient(135deg, #8B5CF6, #EC4899);
        }
        
        .notification-item.read .notification-icon {
            background: #6b7280;
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }
        
        .notification-header h4 {
            color: #1f2937;
            margin: 0;
            font-size: 1.1rem;
        }
        
        .notification-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .notification-message {
            color: #6b7280;
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        
        .notification-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .notification-time {
            color: #9ca3af;
            font-size: 0.9rem;
        }
        
        .notification-type {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .notification-type.info {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .notification-type.success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .notification-type.warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .notification-type.error {
            background: #fee2e2;
            color: #991b1b;
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
        
        .btn-small {
            padding: 0.5rem 1rem;
            border-radius: 5px;
            font-size: 0.8rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-small.btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-small.btn-primary:hover {
            background: #2563eb;
        }
        
        .btn-small.btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-small.btn-danger:hover {
            background: #dc2626;
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
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
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
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        @media (max-width: 768px) {
            .notifications-container {
                margin: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            
            .filters-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .notification-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .notification-actions {
                align-self: flex-start;
            }
        }
    </style>

    <script>
        function markAsRead(notificationId) {
            fetch('notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=mark_read&notification_id=${notificationId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const notification = document.querySelector(`[data-id="${notificationId}"]`);
                    notification.classList.remove('unread');
                    notification.classList.add('read');
                    notification.querySelector('.notification-actions .btn-primary').remove();
                    updateStats();
                }
            })
            .catch(error => console.error('Error:', error));
        }
        
        function markAllAsRead() {
            fetch('notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=mark_all_read'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelectorAll('.notification-item.unread').forEach(item => {
                        item.classList.remove('unread');
                        item.classList.add('read');
                        const markReadBtn = item.querySelector('.notification-actions .btn-primary');
                        if (markReadBtn) markReadBtn.remove();
                    });
                    updateStats();
                }
            })
            .catch(error => console.error('Error:', error));
        }
        
        function deleteNotification(notificationId) {
            if (confirm('Are you sure you want to delete this notification?')) {
                fetch('notifications.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete&notification_id=${notificationId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelector(`[data-id="${notificationId}"]`).remove();
                        updateStats();
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        }
        
        function refreshNotifications() {
            location.reload();
        }
        
        function updateStats() {
            // In a real implementation, you would update the stats via AJAX
            // For now, we'll just reload the page
            setTimeout(() => {
                location.reload();
            }, 1000);
        }
        
        // Auto-refresh every 30 seconds for real-time updates
        setInterval(() => {
            // In a real implementation, you would check for new notifications via AJAX
            // and update the UI without reloading the page
        }, 30000);
    </script>
</body>
</html>
