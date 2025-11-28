<?php
// Start output buffering to prevent any accidental output
ob_start();

require_once '../config/database.php';

// Clear any output that might have been generated
ob_clean();

// Redirect already logged-in users to their dashboard
if (isLoggedIn() && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (isAdmin()) {
        redirect(admin_url('dashboard.php'));
    } else {
        redirect(page_url('dashboard.php'));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Validate input
        if (empty($email) || empty($password)) {
            throw new Exception('Email and password are required');
        }
        
        // Get user
        $query = "SELECT * FROM users WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password'])) {
            throw new Exception('Invalid email or password');
        }
        
        // Set session (include role/premium fields used by helpers)
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = [
            'id' => $user['id'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'role' => $user['role'] ?? 'customer',
            'is_premium' => (bool)($user['is_premium'] ?? false),
            'premium_expires_at' => $user['premium_expires_at'] ?? null
        ];
        
        // Block admin access from customer login page
        if ($user['role'] === 'admin') {
            throw new Exception('Admin accounts must use the admin login page');
        }
        
        // Redirect to dashboard directly instead of returning JSON
        header('Location: ' . page_url('dashboard.php'));
        exit;
        
    } catch (Exception $e) {
        // Store error message in session to display on the form
        $_SESSION['login_error'] = $e->getMessage();
        // Redirect back to login page
        header('Location: ' . auth_url('login.php'));
        exit;
    } finally {
        // Ensure no additional output
        ob_end_flush();
        exit;
    }
} else {
    // Show login form
    // Clear output buffer before showing HTML
    ob_end_clean();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login - AfroMarry</title>
        <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <h2>Welcome Back</h2>
                    <p>Sign in to your AfroMarry account</p>
                </div>
                
                <?php if (isset($_SESSION['login_error'])): ?>
                    <div class="alert alert-error" style="background:#fee2e2;color:#991b1b;padding:1rem;border-radius:8px;margin-bottom:1rem;">
                        <?php 
                        echo htmlspecialchars($_SESSION['login_error']);
                        unset($_SESSION['login_error']); // Clear the error message
                        ?>
                    </div>
                <?php endif; ?>
                
                <form id="login-form" method="post" action="<?php echo auth_url('login.php'); ?>" class="auth-form">
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Email Address" required>
                    </div>
                    <div class="form-group">
                        <input type="password" name="password" placeholder="Password" required>
                        <a href="forgot-password.php" style="display:block;text-align:right;margin-top:0.5rem;color:#8B5CF6;font-size:0.9rem;text-decoration:none;">Forgot Password?</a>
                    </div>
                    <button type="submit" class="btn-primary btn-large">Sign In</button>
                </form>
                
                <div class="go-back-home" style="text-align:center;margin-top:0.5rem;margin-bottom:0.5rem;">
                    <a href="<?php echo base_url('index.php'); ?>" class="btn-primary">&larr; Go Back to Home</a>
                </div>
                
                <div class="auth-footer">
                    <p>Don't have an account? <a href="register.php">Create Account</a></p>
                </div>
            </div>
        </div>
        <script src="<?php echo BASE_PATH; ?>/assets/js/config.js"></script>
        <script src="<?php echo BASE_PATH; ?>/assets/js/auth.js"></script>
    </body>
    </html>
    <?php
}
?>