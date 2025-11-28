<?php
// Start output buffering to prevent any accidental output
ob_start();

require_once '../config/database.php';
require_once '../config/admin_config.php';
require_once '../config/admin_security.php';

// Clear any output that might have been generated
ob_clean();

// Redirect already logged-in admins
if (isLoggedIn() && isAdmin() && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(admin_url('dashboard.php'));
}

// Block non-admin users from accessing this page
if (isLoggedIn() && !isAdmin()) {
    redirect(base_url('index.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Always ensure JSON response
    header('Content-Type: application/json');
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $ip_address = getClientIpAddress();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Validate input
        if (empty($email) || empty($password)) {
            logAdminLoginAttempt($email, $ip_address, $user_agent, false, null, 'Missing email or password');
            throw new Exception('Email and password are required');
        }
        
        // Check rate limiting
        if (!checkAdminLoginRateLimit($email, $ip_address)) {
            logAdminLoginAttempt($email, $ip_address, $user_agent, false, null, 'Rate limit exceeded');
            throw new Exception('Too many login attempts. Please try again later.');
        }
        
        // Get user - only admin accounts
        $query = "SELECT * FROM users WHERE email = :email AND role = 'admin'";
        $stmt = $db->prepare($query);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password'])) {
            $reason = !$user ? 'Admin account not found' : 'Invalid password';
            logAdminLoginAttempt($email, $ip_address, $user_agent, false, null, $reason);
            throw new Exception('Invalid admin credentials');
        }
        
        // Set session with admin timeout tracking
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['admin_last_activity'] = time(); // Track admin session activity
        $_SESSION['user'] = [
            'id' => $user['id'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'role' => $user['role'],
            'is_premium' => (bool)($user['is_premium'] ?? false),
            'premium_expires_at' => $user['premium_expires_at'] ?? null
        ];
        
        // Log successful login
        logAdminLoginAttempt($email, $ip_address, $user_agent, true, $user['id'], null);
        
        // Redirect to admin dashboard
        $redirect = admin_url('dashboard.php');
        
        echo json_encode([
            'success' => true,
            'message' => 'Admin login successful!',
            'redirect' => $redirect
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    } finally {
        // Ensure no additional output
        ob_end_flush();
        exit;
    }
} else {
    // Show admin login form
    // Clear output buffer before showing HTML
    ob_end_clean();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - AfroMarry</title>
        <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <style>
            .admin-login-warning {
                background: #fee2e2;
                border: 1px solid #fecaca;
                color: #991b1b;
                padding: 1rem;
                border-radius: 10px;
                margin-bottom: 1.5rem;
                text-align: center;
            }
            .admin-login-warning i {
                margin-right: 0.5rem;
            }
        </style>
    </head>
    <body>
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <h2><i class="fas fa-shield-alt"></i> Admin Access</h2>
                    <p>Administrator login only</p>
                </div>
                
                <div class="admin-login-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Authorized Personnel Only</strong>
                </div>
                
                <?php if (isset($_GET['timeout'])): ?>
                <div style="background: #fef3c7; border: 1px solid #fde68a; color: #92400e; padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; text-align: center;">
                    <i class="fas fa-clock"></i>
                    <strong>Session Timeout</strong><br>
                    <small>Your session has expired. Please login again.</small>
                </div>
                <?php endif; ?>
                
                <form id="admin-login-form" method="post" action="<?php echo admin_url('login.php'); ?>" class="auth-form">
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Admin Email" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <input type="password" name="password" placeholder="Admin Password" required autocomplete="off">
                    </div>
                    <button type="submit" class="btn-primary btn-large">Sign In as Admin</button>
                </form>
                
                <div class="auth-footer" style="text-align: center; margin-top: 1rem;">
                    <p style="color: #6b7280; font-size: 0.9rem;">
                        <a href="<?php echo base_url('index.php'); ?>" style="color: #8B5CF6;">← Back to Home</a>
                    </p>
                </div>
            </div>
        </div>
        
        <script src="<?php echo BASE_PATH; ?>/assets/js/config.js"></script>
        <script>
            // Handle admin login form
            document.getElementById('admin-login-form').addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData
                });
                
                // Check if response is JSON
                const contentType = response.headers.get("content-type");
                if (!contentType || !contentType.includes("application/json")) {
                    alert("Server returned unexpected response. Please try again.");
                    return;
                }
                
                const data = await response.json();
                
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    alert(data.message || 'Login failed');
                }
            });
        </script>
    </body>
    </html>
    <?php
}
?>