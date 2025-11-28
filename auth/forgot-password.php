<?php
// Start output buffering to prevent any accidental output
ob_start();

require_once '../config/database.php';

// Clear any output that might have been generated
ob_clean();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if request is AJAX
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    if ($is_ajax) {
        header('Content-Type: application/json');
    }
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        $email = sanitize($_POST['email'] ?? '');
        
        if (empty($email)) {
            throw new Exception('Email is required');
        }
        
        // Check if user exists
        $query = "SELECT id, email, full_name FROM users WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Delete old tokens for this email
            $deleteQuery = "DELETE FROM password_resets WHERE email = :email";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->execute([':email' => $email]);
            
            // Insert new token
            $insertQuery = "INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires_at)";
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->execute([
                ':email' => $email,
                ':token' => $token,
                ':expires_at' => $expires_at
            ]);
            
            // Generate reset link
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . base_url('auth/reset-password.php') . "?token=" . $token;
            
            // In production, send email here
            // For now, we'll show the link (remove in production)
            $message = "Password reset link has been generated. Click the link below to reset your password:<br><a href='$reset_link' style='color:#8B5CF6;'>$reset_link</a><br><br>Note: This link expires in 1 hour.";
        } else {
            // Don't reveal if email exists (security best practice)
            $message = "If that email exists, a password reset link has been sent.";
        }
        
        if ($is_ajax) {
            echo json_encode([
                'success' => true,
                'message' => $message
            ]);
            ob_end_flush();
            exit;
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        
        if ($is_ajax) {
            echo json_encode([
                'success' => false,
                'message' => $error
            ]);
            ob_end_flush();
            exit;
        }
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
    <title>Forgot Password - AfroMarry</title>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Forgot Password</h2>
                <p>Enter your email to receive a password reset link</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success" style="background:#d1fae5;color:#065f46;padding:1rem;border-radius:8px;margin-bottom:1rem;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error" style="background:#fee2e2;color:#991b1b;padding:1rem;border-radius:8px;margin-bottom:1rem;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" class="auth-form">
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email Address" required>
                </div>
                <button type="submit" class="btn-primary btn-large">Send Reset Link</button>
            </form>
            
            <div class="auth-footer" style="text-align:center;margin-top:1rem;">
                <a href="login.php" style="color:#8B5CF6;text-decoration:none;">← Back to Login</a>
            </div>
        </div>
    
    <script src="<?php echo BASE_PATH; ?>/assets/js/config.js"></script>
    <script src="<?php echo BASE_PATH; ?>/assets/js/auth.js"></script>
</body>
</html>