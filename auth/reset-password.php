<?php
// Start output buffering to prevent any accidental output
ob_start();

require_once '../config/database.php';

// Clear any output that might have been generated
ob_clean();

$token = $_GET['token'] ?? '';
$message = '';
$error = '';
$valid_token = false;

if (empty($token)) {
    $error = 'Invalid or missing reset token';
} else {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Verify token
        $query = "SELECT email FROM password_resets WHERE token = :token AND expires_at > NOW()";
        $stmt = $db->prepare($query);
        $stmt->execute([':token' => $token]);
        $reset = $stmt->fetch();
        
        if ($reset) {
            $valid_token = true;
            
            // Handle password reset
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Check if request is AJAX
                $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                
                if ($is_ajax) {
                    header('Content-Type: application/json');
                }
                
                $password = $_POST['password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                
                if (empty($password) || empty($confirm_password)) {
                    $error = 'Please fill in all fields';
                } elseif ($password !== $confirm_password) {
                    $error = 'Passwords do not match';
                } elseif (strlen($password) < 6) {
                    $error = 'Password must be at least 6 characters';
                } else {
                    // Update password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $updateQuery = "UPDATE users SET password = :password WHERE email = :email";
                    $updateStmt = $db->prepare($updateQuery);
                    $updateStmt->execute([
                        ':password' => $hashed_password,
                        ':email' => $reset['email']
                    ]);
                    
                    // Delete used token
                    $deleteQuery = "DELETE FROM password_resets WHERE token = :token";
                    $deleteStmt = $db->prepare($deleteQuery);
                    $deleteStmt->execute([':token' => $token]);
                    
                    $message = 'Password reset successful! You can now login with your new password.';
                    $valid_token = false; // Hide form after success
                    
                    if ($is_ajax) {
                        echo json_encode([
                            'success' => true,
                            'message' => $message,
                            'redirect' => 'login.php'
                        ]);
                        ob_end_flush();
                        exit;
                    }
                }
                
                if ($is_ajax && $error) {
                    echo json_encode([
                        'success' => false,
                        'message' => $error
                    ]);
                    ob_end_flush();
                    exit;
                }
            }
        } else {
            $error = 'Invalid or expired reset token';
        }
        
    } catch (Exception $e) {
        $error = 'An error occurred: ' . $e->getMessage();
    }
}

// Clear output buffer before showing HTML
ob_end_clean();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - AfroMarry</title>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Reset Password</h2>
                <p>Enter your new password</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success" style="background:#d1fae5;color:#065f46;padding:1rem;border-radius:8px;margin-bottom:1rem;">
                    <?php echo $message; ?>
                    <br><br>
                    <a href="login.php" class="btn-primary">Go to Login</a>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error" style="background:#fee2e2;color:#991b1b;padding:1rem;border-radius:8px;margin-bottom:1rem;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($valid_token && !$message): ?>
            <form method="post" class="auth-form">
                <div class="form-group">
                    <input type="password" name="password" placeholder="New Password" required minlength="6">
                </div>
                <div class="form-group">
                    <input type="password" name="confirm_password" placeholder="Confirm New Password" required minlength="6">
                </div>
                <button type="submit" class="btn-primary btn-large">Reset Password</button>
            </form>
            <?php elseif (!$valid_token && !$message): ?>
                <div style="text-align:center;">
                    <a href="forgot-password.php" class="btn-primary">Request New Reset Link</a>
                </div>
            <?php endif; ?>
            
            <div class="auth-footer" style="text-align:center;margin-top:1rem;">
                <a href="login.php" style="color:#8B5CF6;text-decoration:none;">← Back to Login</a>
            </div>
        </div>
    </div>
    
    <script src="<?php echo BASE_PATH; ?>/assets/js/config.js"></script>
    <script src="<?php echo BASE_PATH; ?>/assets/js/auth.js"></script>
</body>
</html>