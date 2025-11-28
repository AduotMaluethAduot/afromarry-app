<?php
require_once '../config/database.php';
require_once '../config/settings.php';

requireAdmin();

$user = getCurrentUser();
$database = new Database();
$db = $database->getConnection();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    try {
        if ($action === 'general_settings') {
            // Save settings to database
            setSetting('site_name', sanitize($_POST['site_name']), 'string');
            setSetting('site_email', sanitize($_POST['site_email']), 'string');
            setSetting('site_phone', sanitize($_POST['site_phone']), 'string');
            setSetting('site_address', sanitize($_POST['site_address']), 'string');
            setSetting('currency', sanitize($_POST['currency']), 'string');
            setSetting('timezone', sanitize($_POST['timezone']), 'string');
            setSetting('maintenance_mode', isset($_POST['maintenance_mode']) ? 1 : 0, 'boolean');
            
            logAdminAction('General Settings Updated', 'settings', null, null, [
                'site_name' => sanitize($_POST['site_name']),
                'site_email' => sanitize($_POST['site_email']),
                'currency' => sanitize($_POST['currency'])
            ]);
            
            $success_message = "General settings updated successfully!";
            
        } elseif ($action === 'email_settings') {
            setSetting('smtp_host', sanitize($_POST['smtp_host']), 'string');
            setSetting('smtp_port', intval($_POST['smtp_port']), 'integer');
            setSetting('smtp_username', sanitize($_POST['smtp_username']), 'string');
            setSetting('smtp_password', sanitize($_POST['smtp_password']), 'string');
            setSetting('smtp_encryption', sanitize($_POST['smtp_encryption']), 'string');
            setSetting('from_email', sanitize($_POST['from_email']), 'string');
            setSetting('from_name', sanitize($_POST['from_name']), 'string');
            
            logAdminAction('Email Settings Updated', 'settings', null, null, [
                'smtp_host' => sanitize($_POST['smtp_host']),
                'from_email' => sanitize($_POST['from_email'])
            ]);
            
            $success_message = "Email settings updated successfully!";
            
        } elseif ($action === 'payment_settings') {
            setSetting('paystack_public_key', sanitize($_POST['paystack_public_key']), 'string');
            setSetting('paystack_secret_key', sanitize($_POST['paystack_secret_key']), 'string');
            setSetting('flutterwave_public_key', sanitize($_POST['flutterwave_public_key']), 'string');
            setSetting('flutterwave_secret_key', sanitize($_POST['flutterwave_secret_key']), 'string');
            setSetting('bank_account_name', sanitize($_POST['bank_account_name']), 'string');
            setSetting('bank_account_number', sanitize($_POST['bank_account_number']), 'string');
            setSetting('bank_name', sanitize($_POST['bank_name']), 'string');
            
            logAdminAction('Payment Settings Updated', 'settings', null, null, [
                'payment_gateways' => 'updated'
            ]);
            
            $success_message = "Payment settings updated successfully!";
            
        } elseif ($action === 'security_settings') {
            setSetting('session_timeout', intval($_POST['session_timeout']), 'integer');
            setSetting('max_login_attempts', intval($_POST['max_login_attempts']), 'integer');
            setSetting('password_min_length', intval($_POST['password_min_length']), 'integer');
            setSetting('require_email_verification', isset($_POST['require_email_verification']) ? 1 : 0, 'boolean');
            setSetting('enable_two_factor', isset($_POST['enable_two_factor']) ? 1 : 0, 'boolean');
            
            logAdminAction('Security Settings Updated', 'settings', null, null, [
                'session_timeout' => intval($_POST['session_timeout']),
                'max_login_attempts' => intval($_POST['max_login_attempts'])
            ]);
            
            $success_message = "Security settings updated successfully!";
        }
        
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Load settings from database
$settings = getAllSettings();

// Provide default values for settings that don't exist yet
$default_settings = [
    'site_name' => 'AfroMarry',
    'site_email' => 'admin@afromarry.com',
    'site_phone' => '+1234567890',
    'site_address' => '',
    'currency' => 'USD',
    'timezone' => 'UTC',
    'maintenance_mode' => false,
    'smtp_host' => '',
    'smtp_port' => 587,
    'smtp_username' => '',
    'smtp_password' => '',
    'smtp_encryption' => 'tls',
    'from_email' => 'noreply@afromarry.com',
    'from_name' => 'AfroMarry',
    'paystack_public_key' => '',
    'paystack_secret_key' => '',
    'flutterwave_public_key' => '',
    'flutterwave_secret_key' => '',
    'bank_account_name' => '',
    'bank_account_number' => '',
    'bank_name' => '',
    'session_timeout' => 3600,
    'max_login_attempts' => 5,
    'password_min_length' => 6,
    'require_email_verification' => false,
    'enable_two_factor' => false
];

// Merge database settings with defaults
$settings = array_merge($default_settings, $settings);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Admin</title>
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
                <a href="settings.php" class="nav-link active">Settings</a>
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
                <a href="coupons.php" class="nav-item">
                    <i class="fas fa-tags"></i>
                    Coupons
                </a>
                <a href="reports.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    Reports
                </a>
                <a href="settings.php" class="nav-item active">
                    <i class="fas fa-cog"></i>
                    System Settings
                </a>
            </nav>
        </div>

        <div class="admin-content">
            <div class="admin-header">
                <h1>System Settings</h1>
                <p>Configure system-wide settings and preferences</p>
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

            <!-- Settings Tabs -->
            <div class="settings-tabs">
                <button class="tab-button active" onclick="showTab('general')">
                    <i class="fas fa-cog"></i>
                    General
                </button>
                <button class="tab-button" onclick="showTab('email')">
                    <i class="fas fa-envelope"></i>
                    Email
                </button>
                <button class="tab-button" onclick="showTab('payment')">
                    <i class="fas fa-credit-card"></i>
                    Payment
                </button>
                <button class="tab-button" onclick="showTab('security')">
                    <i class="fas fa-shield-alt"></i>
                    Security
                </button>
            </div>

            <!-- General Settings -->
            <div id="general-tab" class="settings-tab-content active">
                <div class="admin-section">
                    <h2>General Settings</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="general_settings">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="site_name">Site Name *</label>
                                <input type="text" name="site_name" id="site_name" value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="site_email">Site Email *</label>
                                <input type="email" name="site_email" id="site_email" value="<?php echo htmlspecialchars($settings['site_email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="site_phone">Site Phone</label>
                                <input type="text" name="site_phone" id="site_phone" value="<?php echo htmlspecialchars($settings['site_phone']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="currency">Default Currency *</label>
                                <select name="currency" id="currency" required>
                                    <option value="USD" <?php echo $settings['currency'] === 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                                    <option value="EUR" <?php echo $settings['currency'] === 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                                    <option value="NGN" <?php echo $settings['currency'] === 'NGN' ? 'selected' : ''; ?>>NGN (₦)</option>
                                    <option value="GBP" <?php echo $settings['currency'] === 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="site_address">Site Address</label>
                            <textarea name="site_address" id="site_address" rows="3"><?php echo htmlspecialchars($settings['site_address']); ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="timezone">Timezone *</label>
                                <select name="timezone" id="timezone" required>
                                    <option value="UTC" <?php echo $settings['timezone'] === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                    <option value="Africa/Lagos" <?php echo $settings['timezone'] === 'Africa/Lagos' ? 'selected' : ''; ?>>Africa/Lagos</option>
                                    <option value="Africa/Johannesburg" <?php echo $settings['timezone'] === 'Africa/Johannesburg' ? 'selected' : ''; ?>>Africa/Johannesburg</option>
                                    <option value="America/New_York" <?php echo $settings['timezone'] === 'America/New_York' ? 'selected' : ''; ?>>America/New_York</option>
                                    <option value="Europe/London" <?php echo $settings['timezone'] === 'Europe/London' ? 'selected' : ''; ?>>Europe/London</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="maintenance_mode" value="1" <?php echo $settings['maintenance_mode'] ? 'checked' : ''; ?>>
                                    Maintenance Mode
                                </label>
                                <small>When enabled, only admins can access the site</small>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">Save General Settings</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Email Settings -->
            <div id="email-tab" class="settings-tab-content">
                <div class="admin-section">
                    <h2>Email Settings</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="email_settings">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="smtp_host">SMTP Host *</label>
                                <input type="text" name="smtp_host" id="smtp_host" value="<?php echo htmlspecialchars($settings['smtp_host']); ?>" required placeholder="smtp.example.com">
                            </div>
                            
                            <div class="form-group">
                                <label for="smtp_port">SMTP Port *</label>
                                <input type="number" name="smtp_port" id="smtp_port" value="<?php echo $settings['smtp_port']; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="smtp_username">SMTP Username *</label>
                                <input type="text" name="smtp_username" id="smtp_username" value="<?php echo htmlspecialchars($settings['smtp_username']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="smtp_password">SMTP Password *</label>
                                <input type="password" name="smtp_password" id="smtp_password" value="<?php echo htmlspecialchars($settings['smtp_password']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="smtp_encryption">SMTP Encryption *</label>
                                <select name="smtp_encryption" id="smtp_encryption" required>
                                    <option value="tls" <?php echo $settings['smtp_encryption'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                    <option value="ssl" <?php echo $settings['smtp_encryption'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                    <option value="none" <?php echo $settings['smtp_encryption'] === 'none' ? 'selected' : ''; ?>>None</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="from_email">From Email *</label>
                                <input type="email" name="from_email" id="from_email" value="<?php echo htmlspecialchars($settings['from_email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="from_name">From Name *</label>
                            <input type="text" name="from_name" id="from_name" value="<?php echo htmlspecialchars($settings['from_name']); ?>" required>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">Save Email Settings</button>
                            <button type="button" class="btn-secondary" onclick="testEmail()">Test Email</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Payment Settings -->
            <div id="payment-tab" class="settings-tab-content">
                <div class="admin-section">
                    <h2>Payment Gateway Settings</h2>
                    
                    <div class="settings-subsection">
                        <h3>Paystack</h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="payment_settings">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="paystack_public_key">Paystack Public Key</label>
                                    <input type="text" name="paystack_public_key" id="paystack_public_key" value="<?php echo htmlspecialchars($settings['paystack_public_key']); ?>" placeholder="pk_live_...">
                                </div>
                                
                                <div class="form-group">
                                    <label for="paystack_secret_key">Paystack Secret Key</label>
                                    <input type="password" name="paystack_secret_key" id="paystack_secret_key" value="<?php echo htmlspecialchars($settings['paystack_secret_key']); ?>" placeholder="sk_live_...">
                                </div>
                            </div>
                            
                            <h3>Flutterwave</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="flutterwave_public_key">Flutterwave Public Key</label>
                                    <input type="text" name="flutterwave_public_key" id="flutterwave_public_key" value="<?php echo htmlspecialchars($settings['flutterwave_public_key']); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="flutterwave_secret_key">Flutterwave Secret Key</label>
                                    <input type="password" name="flutterwave_secret_key" id="flutterwave_secret_key" value="<?php echo htmlspecialchars($settings['flutterwave_secret_key']); ?>">
                                </div>
                            </div>
                            
                            <h3>Bank Transfer</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="bank_name">Bank Name</label>
                                    <input type="text" name="bank_name" id="bank_name" value="<?php echo htmlspecialchars($settings['bank_name']); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="bank_account_name">Account Name</label>
                                    <input type="text" name="bank_account_name" id="bank_account_name" value="<?php echo htmlspecialchars($settings['bank_account_name']); ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="bank_account_number">Account Number</label>
                                <input type="text" name="bank_account_number" id="bank_account_number" value="<?php echo htmlspecialchars($settings['bank_account_number']); ?>">
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Save Payment Settings</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Security Settings -->
            <div id="security-tab" class="settings-tab-content">
                <div class="admin-section">
                    <h2>Security Settings</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="security_settings">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="session_timeout">Session Timeout (seconds) *</label>
                                <input type="number" name="session_timeout" id="session_timeout" value="<?php echo $settings['session_timeout']; ?>" required min="300">
                                <small>Default: 3600 (1 hour)</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="max_login_attempts">Max Login Attempts *</label>
                                <input type="number" name="max_login_attempts" id="max_login_attempts" value="<?php echo $settings['max_login_attempts']; ?>" required min="3" max="10">
                                <small>Account locked after max attempts</small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="password_min_length">Minimum Password Length *</label>
                                <input type="number" name="password_min_length" id="password_min_length" value="<?php echo $settings['password_min_length']; ?>" required min="6" max="20">
                            </div>
                            
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="require_email_verification" value="1" <?php echo $settings['require_email_verification'] ? 'checked' : ''; ?>>
                                    Require Email Verification
                                </label>
                                <small>Users must verify email before accessing account</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="enable_two_factor" value="1" <?php echo $settings['enable_two_factor'] ? 'checked' : ''; ?>>
                                Enable Two-Factor Authentication
                            </label>
                            <small>Adds an extra layer of security for admin accounts</small>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">Save Security Settings</button>
                        </div>
                    </form>
                </div>
            </div>
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
        
        .settings-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .tab-button {
            padding: 1rem 2rem;
            border: none;
            background: transparent;
            color: #6b7280;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .tab-button:hover {
            color: #dc2626;
            background: #f9fafb;
        }
        
        .tab-button.active {
            color: #dc2626;
            border-bottom-color: #dc2626;
        }
        
        .settings-tab-content {
            display: none;
        }
        
        .settings-tab-content.active {
            display: block;
        }
        
        .admin-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .admin-section h2 {
            color: #1f2937;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .settings-subsection {
            margin-bottom: 2rem;
        }
        
        .settings-subsection h3 {
            color: #374151;
            margin-bottom: 1rem;
            margin-top: 2rem;
        }
        
        .settings-subsection h3:first-child {
            margin-top: 0;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
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
            transition: all 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #8B5CF6;
        }
        
        .form-group small {
            display: block;
            margin-top: 0.5rem;
            color: #6b7280;
            font-size: 0.85rem;
        }
        
        .form-group input[type="checkbox"] {
            width: auto;
            margin-right: 0.5rem;
        }
        
        .form-actions {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid #e5e7eb;
            display: flex;
            gap: 1rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #8B5CF6, #EC4899);
            color: white;
            padding: 0.75rem 2rem;
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
            padding: 0.75rem 2rem;
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
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .settings-tabs {
                flex-wrap: wrap;
            }
        }
    </style>

    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.settings-tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
        
        function testEmail() {
            alert('Email test functionality would be implemented here. This would send a test email to verify SMTP settings.');
        }
    </script>
</body>
</html>

