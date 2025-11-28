<?php
require_once '../config/database.php';
require_once '../helpers/uploads.php';

requireAdmin();
$user = getCurrentUser();
$database = new Database();
$db = $database->getConnection();

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    try {
        if ($action === 'create') {
            if (!has_uploaded_file_field('product_image')) {
                throw new Exception('Please upload a product image.');
            }
            
            $name = sanitize($_POST['name']);
            $price = floatval($_POST['price']);
            $currency = sanitize($_POST['currency']);
            $category = sanitize($_POST['category']);
            $tribe = sanitize($_POST['tribe']);
            $description = sanitize($_POST['description']);
            $stock_quantity = intval($_POST['stock_quantity']);
            
            $db->beginTransaction();
            
            // Check if vendor_id column exists
            $columns = "name, price, currency, category, tribe, description, image, stock_quantity";
            $placeholders = ":name, :price, :currency, :category, :tribe, :description, :image, :stock_quantity";
            $params = [
                ':name' => $name,
                ':price' => $price,
                ':currency' => $currency,
                ':category' => $category,
                ':tribe' => $tribe,
                ':description' => $description,
                ':image' => null,
                ':stock_quantity' => $stock_quantity
            ];
            
            try {
                $checkCol = "SHOW COLUMNS FROM products LIKE 'vendor_id'";
                $colResult = $db->query($checkCol);
                if ($colResult->rowCount() > 0) {
                    $columns = "vendor_id, " . $columns;
                    $placeholders = ":vendor_id, " . $placeholders;
                    $params[':vendor_id'] = $user['id']; // Admin creating product is the vendor
                }
            } catch (PDOException $e) {
                // Column doesn't exist yet
            }
            
            $query = "INSERT INTO products ($columns) VALUES ($placeholders)";
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            
            $product_id = $db->lastInsertId();
            $uploaded_image_path = upload_product_image($_FILES['product_image'], $user['id'], $product_id);
            
            $updateStmt = $db->prepare("UPDATE products SET image = :image WHERE id = :id");
            $updateStmt->execute([
                ':image' => $uploaded_image_path,
                ':id' => $product_id
            ]);
            
            $db->commit();
            
            logAdminAction('Product Created', 'products', $product_id, null, [
                'name' => $name,
                'price' => $price,
                'category' => $category,
                'image' => $uploaded_image_path
            ]);
            
            $success_message = "Product created successfully!";
            
        } elseif ($action === 'update') {
            $id = intval($_POST['id']);
            $name = sanitize($_POST['name']);
            $price = floatval($_POST['price']);
            $currency = sanitize($_POST['currency']);
            $category = sanitize($_POST['category']);
            $tribe = sanitize($_POST['tribe']);
            $description = sanitize($_POST['description']);
            $stock_quantity = intval($_POST['stock_quantity']);
            
            // Get old values for logging
            $query = "SELECT * FROM products WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([':id' => $id]);
            $old_values = $stmt->fetch();
            
            if (!$old_values) {
                throw new Exception('Product not found.');
            }
            
            $image_path = $old_values['image'];
            
            if (has_uploaded_file_field('product_image')) {
                $image_path = upload_product_image($_FILES['product_image'], $user['id'], $id);
                delete_product_image($old_values['image']);
            }
            
            $db->beginTransaction();
            
            $query = "UPDATE products SET name = :name, price = :price, currency = :currency, 
                     category = :category, tribe = :tribe, description = :description, 
                     image = :image, stock_quantity = :stock_quantity WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':id' => $id,
                ':name' => $name,
                ':price' => $price,
                ':currency' => $currency,
                ':category' => $category,
                ':tribe' => $tribe,
                ':description' => $description,
                ':image' => $image_path,
                ':stock_quantity' => $stock_quantity
            ]);
            
            $db->commit();
            
            logAdminAction('Product Updated', 'products', $id, $old_values, [
                'name' => $name,
                'price' => $price,
                'category' => $category,
                'image' => $image_path
            ]);
            
            $success_message = "Product updated successfully!";
            
        } elseif ($action === 'delete') {
            $id = intval($_POST['id']);
            
            // Get product info for logging
            $query = "SELECT * FROM products WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([':id' => $id]);
            $product = $stmt->fetch();
            
            if (!$product) {
                throw new Exception('Product not found.');
            }
            
            $query = "DELETE FROM products WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([':id' => $id]);
            
            delete_product_image_directory($product['image']);
            
            logAdminAction('Product Deleted', 'products', $id, $product, null);
            
            $success_message = "Product deleted successfully!";
        }
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get filter parameters
$category = $_GET['category'] ?? 'all';
$tribe = $_GET['tribe'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($category !== 'all') {
    $where_conditions[] = "category = :category";
    $params[':category'] = $category;
}

if ($tribe !== 'all') {
    $where_conditions[] = "tribe = :tribe";
    $params[':tribe'] = $tribe;
}

if (!empty($search)) {
    $where_conditions[] = "(name LIKE :search OR description LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get products
$query = "SELECT * FROM products $where_clause ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories and tribes for filters
$query = "SELECT DISTINCT category FROM products ORDER BY category";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

$query = "SELECT DISTINCT tribe FROM products ORDER BY tribe";
$stmt = $db->prepare($query);
$stmt->execute();
$tribes = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get statistics
$stats = [];
$query = "SELECT COUNT(*) as count FROM products";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total'] = $stmt->fetch()['count'];

$query = "SELECT SUM(stock_quantity) as total_stock FROM products";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_stock'] = $stmt->fetch()['total_stock'] ?? 0;

$query = "SELECT COUNT(*) as count FROM products WHERE stock_quantity = 0";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['out_of_stock'] = $stmt->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Admin</title>
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
                <a href="products.php" class="nav-link active">Products</a>
                <a href="payments.php" class="nav-link">Payments</a>
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
                <a href="products.php" class="nav-item active">
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
                <a href="reports.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    Reports
                </a>
            </nav>
        </div>

        <div class="admin-content">
            <div class="admin-header">
                <h1>Product Management</h1>
                <p>Manage your product catalog and inventory</p>
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

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total']; ?></h3>
                        <p>Total Products</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-warehouse"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_stock']; ?></h3>
                        <p>Total Stock</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['out_of_stock']; ?></h3>
                        <p>Out of Stock</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="btn-primary" onclick="openProductModal()">
                    <i class="fas fa-plus"></i>
                    Add New Product
                </button>
            </div>

            <!-- Filters -->
            <div class="filters-section">
                <form method="GET" class="filters-form">
                    <div class="filter-group">
                        <label for="category">Category:</label>
                        <select name="category" id="category">
                            <option value="all" <?php echo $category === 'all' ? 'selected' : ''; ?>>All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat; ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="tribe">Tribe:</label>
                        <select name="tribe" id="tribe">
                            <option value="all" <?php echo $tribe === 'all' ? 'selected' : ''; ?>>All Tribes</option>
                            <?php foreach ($tribes as $t): ?>
                                <option value="<?php echo $t; ?>" <?php echo $tribe === $t ? 'selected' : ''; ?>>
                                    <?php echo $t; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="search">Search:</label>
                        <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Product name or description">
                    </div>
                    
                    <button type="submit" class="btn-primary">Filter</button>
                    <a href="products.php" class="btn-secondary">Clear</a>
                </form>
            </div>

            <!-- Products Table -->
            <div class="admin-section">
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Price</th>
                                <th>Category</th>
                                <th>Tribe</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" 
                                         class="product-thumbnail">
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo $product['name']; ?></strong>
                                        <br>
                                        <small><?php echo substr($product['description'], 0, 50) . '...'; ?></small>
                                    </div>
                                </td>
                                <td><?php echo $product['currency'] . ' ' . number_format($product['price']); ?></td>
                                <td><?php echo ucfirst($product['category']); ?></td>
                                <td><?php echo $product['tribe']; ?></td>
                                <td><?php echo $product['stock_quantity']; ?></td>
                                <td>
                                    <?php if ($product['stock_quantity'] > 0): ?>
                                        <span class="status in-stock">In Stock</span>
                                    <?php else: ?>
                                        <span class="status out-of-stock">Out of Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn-small btn-primary" onclick="editProduct(<?php echo $product['id']; ?>)">
                                        Edit
                                    </button>
                                    <button class="btn-small btn-danger" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Product</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" id="productForm" enctype="multipart/form-data">
                <input type="hidden" name="action" id="actionInput" value="create">
                <input type="hidden" name="id" id="productIdInput">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Product Name *</label>
                        <input type="text" name="name" id="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price *</label>
                        <input type="number" name="price" id="price" step="0.01" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="currency">Currency *</label>
                        <select name="currency" id="currency" required>
                            <option value="USD" selected>USD</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category *</label>
                        <select name="category" id="category" required>
                            <option value="Attire">Attire</option>
                            <option value="Accessories">Accessories</option>
                            <option value="Ceremonial">Ceremonial</option>
                            <option value="Jewelry">Jewelry</option>
                            <option value="Home Decor">Home Decor</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="tribe">Tribe *</label>
                        <input type="text" name="tribe" id="tribe" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="stock_quantity">Stock Quantity *</label>
                        <input type="number" name="stock_quantity" id="stock_quantity" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="product_image">Product Image *</label>
                    <input type="file" name="product_image" id="product_image" accept="image/*">
                    <small>
                        Images are stored automatically at
                        <code>/images/u&lt;user_id&gt;/p&lt;product_id&gt;/</code>
                        with a unique filename.
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea name="description" id="description" rows="4" required></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn-primary" id="submitBtn">Save Product</button>
                    <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm Delete</h3>
                <span class="close" onclick="closeDeleteModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this product? This action cannot be undone.</p>
            </div>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteProductId">
                <div class="modal-actions">
                    <button type="submit" class="btn-danger">Delete Product</button>
                    <button type="button" class="btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                </div>
            </form>
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .stat-content h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }
        
        .stat-content p {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .action-buttons {
            margin-bottom: 2rem;
        }
        
        .filters-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .filters-form {
            display: flex;
            gap: 1rem;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .filter-group label {
            font-weight: 600;
            color: #374151;
        }
        
        .filter-group input,
        .filter-group select {
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .admin-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .admin-table th,
        .admin-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .admin-table th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        
        .admin-table td {
            color: #6b7280;
        }
        
        .product-thumbnail {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .status {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status.in-stock {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status.out-of-stock {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .btn-small {
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 600;
            margin-right: 0.5rem;
            border: none;
            cursor: pointer;
        }
        
        .btn-small.btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-small.btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .modal-header h3 {
            color: #1f2937;
            margin: 0;
        }
        
        .close {
            color: #6b7280;
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #374151;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .form-group {
            padding: 0 1.5rem;
            margin-bottom: 1rem;
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
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
        }
        
        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            padding: 1.5rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #8B5CF6, #EC4899);
            color: white;
            padding: 0.75rem 1.5rem;
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
            padding: 0.75rem 1.5rem;
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
        
        .btn-danger {
            background: #ef4444;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .admin-sidebar {
                width: 100%;
            }
            
            .filters-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        function openProductModal() {
            document.getElementById('productModal').style.display = 'block';
            document.getElementById('actionInput').value = 'create';
            document.getElementById('modalTitle').textContent = 'Add New Product';
            document.getElementById('submitBtn').textContent = 'Save Product';
            document.getElementById('productForm').reset();
        }
        
        function editProduct(id) {
            // In a real implementation, you would fetch product data via AJAX
            // For now, we'll just open the modal
            document.getElementById('productModal').style.display = 'block';
            document.getElementById('actionInput').value = 'update';
            document.getElementById('productIdInput').value = id;
            document.getElementById('modalTitle').textContent = 'Edit Product';
            document.getElementById('submitBtn').textContent = 'Update Product';
        }
        
        function deleteProduct(id) {
            document.getElementById('deleteModal').style.display = 'block';
            document.getElementById('deleteProductId').value = id;
        }
        
        function closeModal() {
            document.getElementById('productModal').style.display = 'none';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const productModal = document.getElementById('productModal');
            const deleteModal = document.getElementById('deleteModal');
            if (event.target === productModal) {
                closeModal();
            }
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        }
    </script>
</body>
</html>
