<?php
// Start output buffering to prevent any accidental output
ob_start();

require_once '../config/database.php';

// Clear any output that might have been generated
ob_clean();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $full_name = sanitize($_POST['full_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $phone = sanitize($_POST['phone'] ?? '');
        
        // Validate input
        if (empty($full_name) || empty($email) || empty($password)) {
            throw new Exception('All required fields must be filled');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        if (strlen($password) < 6) {
            throw new Exception('Password must be at least 6 characters long');
        }
        
        // Check if email already exists
        $query = "SELECT id FROM users WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->execute([':email' => $email]);
        
        if ($stmt->fetch()) {
            // According to project specification, we should show a generalized error message
            throw new Exception('Registration failed. Please complete all fields correctly.');
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user (always as customer)
        $query = "INSERT INTO users (full_name, email, password, phone, role) VALUES (:full_name, :email, :password, :phone, 'customer')";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':full_name' => $full_name,
            ':email' => $email,
            ':password' => $hashed_password,
            ':phone' => $phone
        ]);
        
        // Set success message in session
        $_SESSION['registration_success'] = 'Registration successful! Please login to continue.';
        
        // Redirect to login page directly instead of returning JSON
        header('Location: ' . auth_url('login.php'));
        exit;
        
    } catch (Exception $e) {
        // Store error message in session to display on the form
        $_SESSION['register_error'] = $e->getMessage();
        // Redirect back to register page
        header('Location: ' . auth_url('register.php'));
        exit;
    } finally {
        // Ensure no additional output
        ob_end_flush();
        exit;
    }
} else {
    // Show registration form
    // Clear output buffer before showing HTML
    ob_end_clean();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Register - AfroMarry</title>
        <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <h2>Create Account</h2>
                    <p>Join AfroMarry to explore African traditions</p>
                </div>
                
                <?php if (isset($_SESSION['register_error'])): ?>
                    <div class="alert alert-error" style="background:#fee2e2;color:#991b1b;padding:1rem;border-radius:8px;margin-bottom:1rem;">
                        <?php 
                        // Apply project specification for error messaging
                        echo htmlspecialchars($_SESSION['register_error']);
                        unset($_SESSION['register_error']); // Clear the error message
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['registration_success'])): ?>
                    <div class="alert alert-success" style="background:#d1fae5;color:#065f46;padding:1rem;border-radius:8px;margin-bottom:1rem;">
                        <?php 
                        echo htmlspecialchars($_SESSION['registration_success']);
                        unset($_SESSION['registration_success']); // Clear the success message
                        ?>
                    </div>
                <?php endif; ?>
                
                <form id="register-form" method="post" action="<?php echo auth_url('register.php'); ?>" class="auth-form">
                    <div class="form-group">
                        <input type="text" name="full_name" placeholder="Full Name" required>
                    </div>
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Email Address" required>
                    </div>
                    <div class="form-group">
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                    <div class="form-group">
                        <input type="tel" name="phone" placeholder="Phone Number">
                    </div>
                    <button type="submit" class="btn-primary btn-large">Create Account</button>
                </form>
                
                <div class="go-back-home" style="text-align:center;margin-top:0.5rem;margin-bottom:0.5rem;">
                    <a href="<?php echo base_url('index.php'); ?>" class="btn-primary">&larr; Go Back to Home</a>
                </div>
                
                <div class="auth-footer">
                    <p>Already have an account? <a href="login.php">Sign In</a></p>
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