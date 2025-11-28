<?php
require_once '../config/database.php';
require_once '../config/payment_config.php';

if (!isLoggedIn()) {
    redirect(auth_url('login.php'));
}

$user = getCurrentUser();
$database = new Database();
$db = $database->getConnection();

// Get cart items
$query = "SELECT c.*, p.name, p.price, p.currency, p.image, p.description 
         FROM cart c 
         JOIN products p ON c.product_id = p.id 
         WHERE c.user_id = :user_id 
         ORDER BY c.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute([':user_id' => $user['id']]);
$cart_items = $stmt->fetchAll();

// Calculate total
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

if (empty($cart_items)) {
    redirect(base_url('index.php'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - AfroMarry</title>
    <base href="<?php echo BASE_PATH; ?>/">
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                <a href="<?php echo page_url('dashboard.php'); ?>" class="nav-link">Dashboard</a>
                <a href="<?php echo page_url('cart.php'); ?>" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    Cart
                </a>
                <a href="<?php echo auth_url('logout.php'); ?>" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <div class="checkout-container">
        <div class="checkout-content">
            <!-- Order Summary -->
            <div class="checkout-sidebar">
                <h3>Order Summary</h3>
                <div class="order-items">
                    <?php foreach ($cart_items as $item): ?>
                    <div class="order-item">
                        <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="order-item-image">
                        <div class="order-item-details">
                            <h4><?php echo $item['name']; ?></h4>
                            <p>Quantity: <?php echo $item['quantity']; ?></p>
                            <p class="order-item-price">$ <?php echo number_format($item['price'] * $item['quantity']); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="order-totals">
                    <div class="total-line">
                        <span>Subtotal:</span>
                        <span>$ <?php echo number_format($total); ?></span>
                    </div>
                    <div class="total-line">
                        <span>Shipping:</span>
                        <span>$ 2,000</span>
                    </div>
                    <div class="total-line">
                        <span>Tax:</span>
                        <span>$ <?php echo number_format($total * 0.05); ?></span>
                    </div>
                    <div class="total-line total">
                        <span>Total:</span>
                        <span>$ <?php echo number_format($total + 2000 + ($total * 0.05)); ?></span>
                    </div>
                </div>
            </div>

            <!-- Checkout Form -->
            <div class="checkout-form-container">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;">
                    <h2>Checkout</h2>
                    <div style="display:flex;gap:.5rem;align-items:center;">
                        <a href="<?php echo page_url('cart.php'); ?>" class="btn-secondary">Back to Cart</a>
                        <a href="<?php echo page_url('dashboard.php'); ?>" class="btn-secondary">Back to Dashboard</a>
                    </div>
                </div>
                
                <form id="checkout-form" class="checkout-form">
                    <!-- Shipping Information -->
                    <div class="form-section">
                        <h3>Shipping Information</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo explode(' ', $user['full_name'])[0]; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo explode(' ', $user['full_name'])[1] ?? ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo $user['phone']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" rows="3" required placeholder="Enter your full address"></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">City</label>
                                <input type="text" id="city" name="city" required>
                            </div>
                            <div class="form-group">
                                <label for="state">State</label>
                                <input type="text" id="state" name="state" required>
                            </div>
                            <div class="form-group">
                                <label for="zip_code">ZIP Code</label>
                                <input type="text" id="zip_code" name="zip_code" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="country">Country</label>
                            <select id="country" name="country" required>
                                <option value="Nigeria">Nigeria</option>
                                <option value="Ghana">Ghana</option>
                                <option value="Kenya">Kenya</option>
                                <option value="South Africa">South Africa</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="form-section">
                        <h3>Payment Method</h3>
                        <div class="payment-methods">
                            <div class="payment-method">
                                <input type="radio" id="paystack" name="payment_method" value="paystack" checked>
                                <label for="paystack">
                                    <i class="fas fa-credit-card"></i>
                                    <span>Paystack (Card/Bank Transfer)</span>
                                </label>
                            </div>
                            <div class="payment-method">
                                <input type="radio" id="flutterwave" name="payment_method" value="flutterwave">
                                <label for="flutterwave">
                                    <i class="fas fa-mobile-alt"></i>
                                    <span>Flutterwave (Mobile Money)</span>
                                </label>
                            </div>
                            <div class="payment-method">
                                <input type="radio" id="bank_transfer" name="payment_method" value="bank_transfer">
                                <label for="bank_transfer">
                                    <i class="fas fa-university"></i>
                                    <span>Bank Transfer</span>
                                </label>
                            </div>
                            <div class="payment-method">
                                <input type="radio" id="mtn_momo" name="payment_method" value="mtn_momo">
                                <label for="mtn_momo">
                                    <i class="fas fa-mobile-alt"></i>
                                    <span>MTN Mobile Money</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Order Notes -->
                    <div class="form-section">
                        <h3>Order Notes</h3>
                        <div class="form-group">
                            <label for="notes">Special Instructions (Optional)</label>
                            <textarea id="notes" name="notes" rows="3" placeholder="Any special instructions for your order..."></textarea>
                        </div>
                    </div>

                    <!-- Terms and Conditions -->
                    <div class="form-section">
                        <div class="checkbox-group">
                            <input type="checkbox" id="terms" name="terms" required>
                            <label for="terms">I agree to the <a href="terms.php" target="_blank">Terms and Conditions</a> and <a href="privacy.php" target="_blank">Privacy Policy</a></label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-primary btn-large checkout-btn">
                        <i class="fas fa-lock"></i>
                        Complete Order - $ <?php echo number_format($total + 2000 + ($total * 0.05)); ?>
                    </button>
                    <a href="<?php echo page_url('cart.php'); ?>" class="btn-secondary" style="margin-left:0.75rem;">Back to Cart</a>
                </form>
            </div>
        </div>
    </div>

    <!-- Payment Processing Modal -->
    <div id="payment-modal" class="modal">
        <div class="modal-content">
            <div class="payment-processing">
                <div class="loading-spinner"></div>
                <h3>Processing Payment...</h3>
                <p>Please wait while we process your payment. Do not close this window.</p>
            </div>
        </div>
    </div>

    <?php $ver = time(); ?>
    <script>
        // Inject BASE_PATH from PHP into JavaScript before config.js loads
        window.PHP_BASE_PATH = '<?php echo BASE_PATH; ?>';
        // Inject payment gateway keys from PHP
        window.PAYSTACK_PUBLIC_KEY = '<?php echo PAYSTACK_PUBLIC_KEY; ?>';
    </script>
    <script src="<?php echo BASE_PATH; ?>/assets/js/config.js?v=<?php echo $ver; ?>"></script>
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script src="<?php echo BASE_PATH; ?>/assets/js/checkout.js?v=<?php echo $ver; ?>"></script>
</body>
</html>
