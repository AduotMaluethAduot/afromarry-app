<?php
require_once '../config/database.php';

requireAuth();
// Redirect to dashboard instead of showing orders page
redirect(page_url('dashboard.php'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - AfroMarry</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .container { max-width: 1100px; margin: 2rem auto; padding: 0 1rem; }
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; }
        .orders-table { width:100%; border-collapse:collapse; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 5px 20px rgba(0,0,0,.08); }
        .orders-table th, .orders-table td { padding:1rem; border-bottom:1px solid #e5e7eb; text-align:left; vertical-align: top; }
        .orders-table th { background:#f9fafb; color:#374151; font-weight:600; }
        .orders-table td { max-width: 300px; }
        .status { padding:.3rem .8rem; border-radius:20px; font-size:.8rem; font-weight:600; }
        .status.paid { background:#d1fae5; color:#065f46; }
        .status.pending { background:#fef3c7; color:#92400e; }
        .status.cancelled { background:#fee2e2; color:#991b1b; }
        .empty { text-align:center; padding:3rem; color:#6b7280; background:#fff; border-radius:12px; box-shadow:0 5px 20px rgba(0,0,0,.08); }
        .back { text-decoration:none; color:#dc2626; }
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
    if ($user['is_premium'] ?? false) {
        $premium_expires = $user['premium_expires_at'] ?? null;
    }
    ?>

    <div class="dashboard-container">
        <?php include 'includes/dashboard-sidebar.php'; ?>
        
        <div class="dashboard-content">
            <div class="dashboard-header">
                <h1>My Orders</h1>
                <p>View and track your order history</p>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h2 style="margin: 0; color: #1f2937;">My Orders</h2>
                    <p style="margin: 0.5rem 0 0 0; color: #6b7280;">View and track your order history</p>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <a href="<?php echo base_url('index.php#marketplace'); ?>" class="btn-primary">
                        <i class="fas fa-shopping-cart"></i> Browse Marketplace
                    </a>
                    <a href="<?php echo page_url('cart.php'); ?>" class="btn-secondary">
                        <i class="fas fa-shopping-bag"></i> View Cart
                    </a>
                </div>
            </div>

            <!-- Marketplace Products Section -->
            <div style="background: white; border-radius: 15px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);">
                <h3 style="margin: 0 0 1.5rem 0; color: #1f2937;">
                    <i class="fas fa-store"></i> Browse Products
                </h3>
                <div id="products-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1.5rem;">
                    <!-- Products will be loaded here -->
                </div>
            </div>

            <?php if (empty($orders)): ?>
                <div class="empty">
                    <i class="fas fa-shopping-bag" style="font-size:3rem; color:#d1d5db;"></i>
                    <h3>No orders yet</h3>
                    <p>Browse the marketplace to place your first order.</p>
                    <a href="<?php echo base_url('index.php#marketplace'); ?>" class="btn-primary">Shop Now</a>
                </div>
            <?php else: ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        foreach ($orders as $o): 
                            // Get order items
                            $itemsQuery = "SELECT oi.*, p.name as product_name, p.image as product_image 
                                         FROM order_items oi 
                                         JOIN products p ON oi.product_id = p.id 
                                         WHERE oi.order_id = :order_id";
                            $itemsStmt = $db->prepare($itemsQuery);
                            $itemsStmt->execute([':order_id' => $o['id']]);
                            $orderItems = $itemsStmt->fetchAll();
                        ?>
                            <tr>
                                <td>#<?php echo $o['id']; ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($o['created_at'])); ?></td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                        <?php foreach ($orderItems as $item): ?>
                                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                <?php if ($item['product_image']): ?>
                                                    <img src="<?php echo htmlspecialchars($item['product_image']); ?>" 
                                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                                         style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px;">
                                                <?php endif; ?>
                                                <span><?php echo htmlspecialchars($item['product_name']); ?> (x<?php echo $item['quantity']; ?>)</span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td><span class="status <?php echo $o['status']; ?>"><?php echo ucfirst($o['status']); ?></span></td>
                                <td><strong>$ <?php echo number_format($o['total_amount'], 2); ?></strong></td>
                                <td>
                                    <?php if ($o['status'] === 'pending'): ?>
                                        <a href="<?php echo page_url('checkout.php?order_id=' . $o['id']); ?>" class="btn-primary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                                            Complete Payment
                                        </a>
                                    <?php else: ?>
                                        <a href="<?php echo page_url('order-success.php?order_id=' . $o['id']); ?>" class="btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                                            View Details
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script src="<?php echo BASE_PATH; ?>/assets/js/config.js"></script>
    <script>
        // Load products
        async function loadProducts() {
            try {
                const response = await fetch(actionUrl('products.php'));
                const data = await response.json();
                
                if (data.success && data.data) {
                    displayProducts(data.data.slice(0, 8)); // Show first 8 products
                }
            } catch (error) {
                console.error('Error loading products:', error);
            }
        }

        function displayProducts(products) {
            const container = document.getElementById('products-grid');
            if (!container || products.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: #6b7280; grid-column: 1 / -1;">No products available at the moment.</p>';
                return;
            }

            container.innerHTML = products.map(product => `
                <div class="product-card" style="background: #f9fafb; border-radius: 10px; overflow: hidden; transition: transform 0.3s ease;">
                    ${product.image ? `
                        <img src="${product.image}" alt="${product.name}" 
                             style="width: 100%; height: 150px; object-fit: cover;"
                             onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'200\' height=\'150\'%3E%3Crect fill=\'%23e5e7eb\' width=\'200\' height=\'150\'/%3E%3Ctext fill=\'%236b7280\' font-family=\'Arial\' font-size=\'14\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3E${encodeURIComponent(product.name)}%3C/text%3E%3C/svg%3E';">
                    ` : `
                        <div style="width: 100%; height: 150px; background: #e5e7eb; display: flex; align-items: center; justify-content: center; color: #6b7280;">
                            <i class="fas fa-image" style="font-size: 2rem;"></i>
                        </div>
                    `}
                    <div style="padding: 1rem;">
                        <h4 style="margin: 0 0 0.5rem 0; color: #1f2937; font-size: 0.9rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${product.name}</h4>
                        <p style="margin: 0 0 0.5rem 0; color: #8B5CF6; font-weight: 700; font-size: 1.1rem;">$ ${parseFloat(product.price).toLocaleString()}</p>
                        <button onclick="addToCart(${product.id})" 
                                style="width: 100%; padding: 0.5rem; background: linear-gradient(135deg, #8B5CF6, #EC4899); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: transform 0.2s ease;"
                                onmouseover="this.style.transform='translateY(-2px)'"
                                onmouseout="this.style.transform='translateY(0)'">
                            <i class="fas fa-cart-plus"></i> Add to Cart
                        </button>
                    </div>
                </div>
            `).join('');
        }

        async function addToCart(productId) {
            try {
                const response = await fetch(actionUrl('cart.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: 1
                    })
                });

                const data = await response.json();
                if (data.success) {
                    alert('Product added to cart!');
                    // Optionally redirect to cart
                    // window.location.href = pageUrl('cart.php');
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error adding to cart:', error);
                alert('Error adding product to cart. Please try again.');
            }
        }

        // Load products on page load
        loadProducts();
    </script>
</body>
</html>

