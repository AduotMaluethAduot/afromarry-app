<?php
require_once '../config/database.php';

requireAuth();
$currentUser = getCurrentUser();
$database = new Database();
$db = $database->getConnection();

$message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name'] ?? $currentUser['full_name']);
    $phone = sanitize($_POST['phone'] ?? $currentUser['phone']);

    $stmt = $db->prepare("UPDATE users SET full_name = :full_name, phone = :phone WHERE id = :id");
    $stmt->execute([
        ':full_name' => $full_name,
        ':phone' => $phone,
        ':id' => $currentUser['id']
    ]);

    // Update session
    $_SESSION['user']['full_name'] = $full_name;
    $_SESSION['user']['phone'] = $phone;
    $currentUser = getCurrentUser();
    $message = 'Profile updated successfully';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - AfroMarry</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .container { max-width: 700px; margin: 2rem auto; padding: 0 1rem; }
        .card { background:#fff; border-radius:12px; box-shadow:0 5px 20px rgba(0,0,0,.08); padding:1.5rem; }
        .header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; }
        .form-group { margin-bottom:1rem; }
        label { display:block; margin-bottom:.4rem; color:#374151; font-weight:600; }
        input { width:100%; padding:.8rem 1rem; border:1px solid #e5e7eb; border-radius:8px; }
        .row { display:grid; grid-template-columns: 1fr; gap:1rem; }
        .actions { display:flex; gap:.75rem; margin-top:1rem; }
        .link { text-decoration:none; color:#dc2626; }
        .success { background:#d1fae5; color:#065f46; padding:.75rem 1rem; border-radius:8px; margin-bottom:1rem; }
        @media(min-width:640px){ .row { grid-template-columns: 1fr 1fr; } }
    </style>
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
                <a href="<?php echo base_url('index.php'); ?>" class="nav-link">Dashboard</a>
                <a href="<?php echo page_url('cart.php'); ?>" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    Cart
                </a>
                <a href="<?php echo auth_url('logout.php'); ?>" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <?php
    // Get premium expiration for sidebar
    $premium_expires = null;
    if ($currentUser['is_premium'] ?? false) {
        $premium_expires = $currentUser['premium_expires_at'] ?? null;
    }
    $user = $currentUser; // For sidebar compatibility
    ?>

    <div class="dashboard-container">
        <?php include 'includes/dashboard-sidebar.php'; ?>
        
        <div class="dashboard-content">
            <div class="container" style="max-width: 700px; margin: 0 auto;">
                <div class="dashboard-header">
                    <h1>Profile Settings</h1>
                    <p>Manage your account information</p>
                </div>
        <div class="card">
            <?php if ($message): ?>
                <div class="success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
            <?php endif; ?>
            <form method="post" action="/AfroMarry/pages/profile.php">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($currentUser['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($currentUser['phone']); ?>">
                </div>
                <div class="actions">
                    <button type="submit" class="btn-primary">Save Changes</button>
                    <a href="/AfroMarry/auth/logout.php" class="btn-secondary">Logout</a>
                </div>
            </form>
        </div>
            </div>
        </div>
    </div>
</body>
</html>

