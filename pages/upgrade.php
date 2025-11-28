<?php
require_once '../config/database.php';

requireAuth();
$user = getCurrentUser();
$database = new Database();
$db = $database->getConnection();

// Handle premium upgrade
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan = $_POST['plan'] ?? 'premium_monthly';
    $payment_method = $_POST['payment_method'] ?? 'mobile_money';
    
    try {
        // Calculate premium expiry based on plan
        if ($plan === 'premium_annual') {
            // Annual plan: 1 year from now (30% discount)
            $premium_expires = date('Y-m-d H:i:s', strtotime('+1 year'));
            $plan_duration = '1 year';
            $amount = 168;
        } else {
            // Monthly plan: 1 month from now
            $premium_expires = date('Y-m-d H:i:s', strtotime('+1 month'));
            $plan_duration = '1 month';
            $amount = 20;
        }
        
        // Update user to premium
        $query = "UPDATE users SET is_premium = TRUE, premium_expires_at = :expires_at WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':expires_at' => $premium_expires,
            ':user_id' => $user['id']
        ]);
        
        // Create notification
        $query = "INSERT INTO notifications (user_id, title, message, type) 
                 VALUES (:user_id, 'Premium Activated', 'Welcome to AfroMarry Premium! Enjoy exclusive features and benefits.', 'success')";
        $stmt = $db->prepare($query);
        $stmt->execute([':user_id' => $user['id']]);
        
        // Log admin action
        logAdminAction('User Upgraded to Premium', 'users', $user['id'], 
                      ['is_premium' => false], ['is_premium' => true]);
        
        $success_message = "Congratulations! You're now a Premium member for $plan_duration!";
        
    } catch (Exception $e) {
        $error_message = "Error upgrading to premium: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upgrade to Premium - AfroMarry</title>
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
                <a href="../auth/logout.php" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <?php
    // Get premium expiration for sidebar
    $premium_expires = null;
    if ($user['is_premium'] ?? false) {
        $premium_expires = $user['premium_expires_at'] ?? null;
    }
    ?>

    <div class="dashboard-container">
        <?php include 'includes/dashboard-sidebar.php'; ?>
        
        <div class="dashboard-content">
            <div class="upgrade-container">
        <div class="upgrade-content">
            <div class="upgrade-header">
                <h1>Upgrade to Premium</h1>
                <p>Unlock exclusive features and enjoy the full AfroMarry experience</p>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                    <a href="dashboard.php" class="btn-primary">Go to Dashboard</a>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="plans-grid">
                <div class="plan-card free">
                    <div class="plan-header">
                        <h3>Free Plan</h3>
                        <div class="plan-price">
                            <span class="currency">$</span>
                            <span class="amount">0</span>
                            <span class="period">/month</span>
                        </div>
                    </div>
                    <div class="plan-features">
                        <ul>
                            <li><i class="fas fa-check"></i> Browse marketplace (free)</li>
                            <li><i class="fas fa-check"></i> Basic dowry calculator</li>
                            <li><i class="fas fa-check"></i> Limited expert consultations</li>
                            <li><i class="fas fa-check"></i> Learn and explore all content</li>
                            <li><i class="fas fa-check"></i> See all experts and products</li>
                            <li><i class="fas fa-times"></i> <strong>See advertisements</strong></li>
                            <li><i class="fas fa-times"></i> Premium products</li>
                            <li><i class="fas fa-times"></i> Priority support</li>
                            <li><i class="fas fa-times"></i> Advanced tools</li>
                        </ul>
                    </div>
                    <div class="plan-status">
                        <span class="current-plan">Current Plan</span>
                    </div>
                </div>

                <div class="plan-card premium featured">
                    <div class="plan-badge">Most Popular</div>
                    <div class="plan-header">
                        <h3>Premium Plan</h3>
                        <div class="plan-price">
                            <span class="currency">$</span>
                            <span class="amount">20</span>
                            <span class="period">/month</span>
                        </div>
                        <p style="color: #6b7280; font-size: 0.9rem; margin-top: 0.5rem;">
                            or <strong style="color: #8B5CF6;">$168/year</strong> 
                            <span style="text-decoration: line-through; opacity: 0.7;">$240</span> 
                            <span style="background: #10b981; color: white; padding: 0.2rem 0.5rem; border-radius: 5px; font-size: 0.8rem; margin-left: 0.5rem;">Save 30%</span>
                        </p>
                    </div>
                    <div class="plan-features">
                        <ul>
                            <li><i class="fas fa-check"></i> Everything in Free</li>
                            <li><i class="fas fa-check"></i> <strong>Ad-free experience</strong> - No advertisements</li>
                            <li><i class="fas fa-check"></i> Access to premium products</li>
                            <li><i class="fas fa-check"></i> Unlimited expert consultations</li>
                            <li><i class="fas fa-check"></i> Advanced dowry calculator</li>
                            <li><i class="fas fa-check"></i> Priority customer support</li>
                            <li><i class="fas fa-check"></i> Exclusive cultural guides</li>
                            <li><i class="fas fa-check"></i> Wedding planning tools</li>
                            <li><i class="fas fa-check"></i> Custom recommendations</li>
                            <li><i class="fas fa-check"></i> Deeper insights and cultural content</li>
                        </ul>
                    </div>
                    <div class="plan-actions">
                        <form method="POST" class="upgrade-form" style="margin-bottom: 0.5rem;">
                            <input type="hidden" name="plan" value="premium_monthly">
                            <input type="hidden" name="payment_method" value="mobile_money">
                            <button type="submit" class="btn-premium" style="width: 100%;">
                                <i class="fas fa-crown"></i>
                                Monthly - $20/month
                            </button>
                        </form>
                        <form method="POST" class="upgrade-form">
                            <input type="hidden" name="plan" value="premium_annual">
                            <input type="hidden" name="payment_method" value="mobile_money">
                            <button type="submit" class="btn-premium" style="width: 100%; background: linear-gradient(135deg, #10b981, #059669);">
                                <i class="fas fa-gift"></i>
                                Annual - $168/year <span style="font-size: 0.85em; opacity: 0.9;">(Save $72)</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="premium-benefits">
                <h2>Premium Benefits</h2>
                <div class="benefits-grid">
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-ban"></i>
                        </div>
                        <h3>Ad-Free Experience</h3>
                        <p>Enjoy a clean, uninterrupted browsing experience with no advertisements</p>
                    </div>
                    
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-gem"></i>
                        </div>
                        <h3>Premium Products</h3>
                        <p>Access to exclusive cultural items and premium wedding accessories</p>
                    </div>
                    
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <h3>Expert Consultations</h3>
                        <p>Unlimited access to cultural experts and wedding consultants</p>
                    </div>
                    
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <h3>Advanced Tools</h3>
                        <p>Enhanced dowry calculator with detailed breakdowns and recommendations</p>
                    </div>
                    
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h3>Priority Support</h3>
                        <p>24/7 priority customer support with faster response times</p>
                    </div>
                    
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <h3>Cultural Guides</h3>
                        <p>Exclusive access to detailed cultural guides and traditions</p>
                    </div>
                    
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h3>Wedding Planning</h3>
                        <p>Comprehensive wedding planning tools and checklists</p>
                    </div>
                </div>
            </div>

            <div class="payment-methods">
                <h2>Payment Methods</h2>
                <div class="methods-grid">
                    <div class="method-card">
                        <i class="fas fa-mobile-alt"></i>
                        <h3>Mobile Money</h3>
                        <p>Pay with MTN, Airtel, or 9mobile</p>
                    </div>
                    
                    <div class="method-card">
                        <i class="fas fa-university"></i>
                        <h3>Bank Transfer</h3>
                        <p>Direct bank transfer to our account</p>
                    </div>
                    
                    <div class="method-card">
                        <i class="fas fa-credit-card"></i>
                        <h3>Card Payment</h3>
                        <p>Pay with Visa, Mastercard, or Verve</p>
                    </div>
                </div>
            </div>

            <div class="faq-section">
                <h2>Frequently Asked Questions</h2>
                <div class="faq-list">
                    <div class="faq-item">
                        <h3>What happens after I upgrade?</h3>
                        <p>You'll immediately get access to all premium features. Your premium status is valid for one year from the date of upgrade.</p>
                    </div>
                    
                    <div class="faq-item">
                        <h3>Can I cancel my premium subscription?</h3>
                        <p>Yes, you can cancel anytime. However, refunds are not available for the current billing period.</p>
                    </div>
                    
                    <div class="faq-item">
                        <h3>Do I get a discount for annual payment?</h3>
                        <p>Yes! The annual plan offers a 30% discount. Pay $168/year instead of $240 ($20/month x 12 months) and save $72!</p>
                    </div>
                    
                    <div class="faq-item">
                        <h3>What payment methods do you accept?</h3>
                        <p>We accept mobile money, bank transfers, and card payments. All payments are secure and encrypted.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .upgrade-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .upgrade-content {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .upgrade-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .upgrade-header h1 {
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        
        .upgrade-header p {
            color: #6b7280;
            font-size: 1.1rem;
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
        
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .plan-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 15px;
            padding: 2rem;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .plan-card.featured {
            border-color: #8B5CF6;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        }
        
        .plan-badge {
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #8B5CF6, #EC4899);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .plan-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .plan-header h3 {
            color: #1f2937;
            margin-bottom: 1rem;
        }
        
        .plan-price {
            display: flex;
            align-items: baseline;
            justify-content: center;
            gap: 0.25rem;
        }
        
        .plan-price .currency {
            color: #6b7280;
            font-size: 1rem;
        }
        
        .plan-price .amount {
            color: #8B5CF6;
            font-size: 3rem;
            font-weight: 700;
        }
        
        .plan-price .period {
            color: #6b7280;
            font-size: 1rem;
        }
        
        .plan-features ul {
            list-style: none;
            padding: 0;
            margin-bottom: 2rem;
        }
        
        .plan-features li {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 0;
            color: #374151;
        }
        
        .plan-features .fa-check {
            color: #10b981;
        }
        
        .plan-features .fa-times {
            color: #ef4444;
        }
        
        .plan-status {
            text-align: center;
        }
        
        .current-plan {
            background: #e5e7eb;
            color: #6b7280;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .plan-actions {
            text-align: center;
        }
        
        .btn-premium {
            background: linear-gradient(135deg, #8B5CF6, #EC4899);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            width: 100%;
            justify-content: center;
        }
        
        .btn-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 92, 246, 0.3);
        }
        
        .premium-benefits {
            margin-bottom: 3rem;
        }
        
        .premium-benefits h2 {
            text-align: center;
            color: #1f2937;
            margin-bottom: 2rem;
        }
        
        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .benefit-card {
            text-align: center;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 15px;
            transition: all 0.3s ease;
        }
        
        .benefit-card:hover {
            background: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .benefit-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #8B5CF6, #EC4899);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 1.5rem;
        }
        
        .benefit-card h3 {
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        
        .benefit-card p {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .payment-methods {
            margin-bottom: 3rem;
        }
        
        .payment-methods h2 {
            text-align: center;
            color: #1f2937;
            margin-bottom: 2rem;
        }
        
        .methods-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .method-card {
            text-align: center;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 15px;
            border: 2px solid #e5e7eb;
        }
        
        .method-card i {
            font-size: 2rem;
            color: #8B5CF6;
            margin-bottom: 1rem;
        }
        
        .method-card h3 {
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        
        .method-card p {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .faq-section h2 {
            text-align: center;
            color: #1f2937;
            margin-bottom: 2rem;
        }
        
        .faq-list {
            display: grid;
            gap: 1rem;
        }
        
        .faq-item {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 10px;
        }
        
        .faq-item h3 {
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        
        .faq-item p {
            color: #6b7280;
        }
        
        @media (max-width: 768px) {
            .upgrade-container {
                margin: 1rem;
            }
            
            .upgrade-content {
                padding: 1rem;
            }
            
            .plans-grid {
                grid-template-columns: 1fr;
            }
            
            .benefits-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
            </div>
        </div>
</body>
</html>
