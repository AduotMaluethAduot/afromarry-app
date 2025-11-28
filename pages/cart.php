<?php
require_once '../config/database.php';

if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - AfroMarry</title>
    <base href="/AfroMarry/">
    <link rel="stylesheet" href="assets/css/style.css">
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
                <a href="auth/logout.php" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <div class="cart-container">
        <div class="cart-content">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;">
                <h2>Shopping Cart</h2>
                <div style="display:flex;gap:.5rem;align-items:center;">
                    <a href="/AfroMarry/pages/dashboard.php" class="btn-secondary">Back to Dashboard</a>
                    <a href="/AfroMarry/index.php#marketplace" class="btn-secondary">Browse Products</a>
                </div>
            </div>
            
            <div id="cart-items" class="cart-items">
                <!-- Cart items will be loaded here via JavaScript -->
            </div>
            
            <div class="cart-summary">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span id="cart-subtotal">$ 0</span>
                </div>
                <div class="summary-row">
                    <span>Shipping:</span>
                    <span>$ 2,000</span>
                </div>
                <div class="summary-row">
                    <span>Tax:</span>
                    <span id="cart-tax">$ 0</span>
                </div>
                <div class="summary-row total">
                    <span>Total:</span>
                    <span id="cart-total">$ 0</span>
                </div>
                
                <button class="btn-primary btn-large" onclick="proceedToCheckout()">
                    <i class="fas fa-credit-card"></i>
                    Proceed to Checkout
                </button>
            </div>
        </div>
    </div>

    <script>
        // Ensure cart JS thinks user is logged in
        window.currentUser = true;
    </script>
    <script src="assets/js/marketplace.js"></script>
    <script>
        // Load cart when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadCart();
        });
        
        // Override proceedToCheckout to redirect to checkout page
        function proceedToCheckout() {
            window.location.href = '/AfroMarry/pages/checkout.php';
        }
    </script>
</body>
</html>
