<?php
require_once '../config/database.php';

requireAuth();
$user = getCurrentUser();
$database = new Database();
$db = $database->getConnection();

// Get order ID from URL
$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    redirect('dashboard.php');
}

// Get order details
$query = "SELECT * FROM orders WHERE id = :order_id AND user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->execute([':order_id' => $order_id, ':user_id' => $user['id']]);
$order = $stmt->fetch();

if (!$order) {
    redirect('dashboard.php');
}

// Handle payment verification submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'];
    $payment_reference = $_POST['payment_reference'];
    $amount = $_POST['amount'];
    
    // Handle file upload
    $proof_image = null;
    if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/payment_proofs/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = pathinfo($_FILES['proof_image']['name'], PATHINFO_EXTENSION);
        $filename = 'proof_' . $order_id . '_' . time() . '.' . $file_extension;
        $file_path = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['proof_image']['tmp_name'], $file_path)) {
            $proof_image = 'uploads/payment_proofs/' . $filename;
        }
    }
    
    try {
        // Insert payment verification record
        $query = "INSERT INTO payment_verifications (order_id, payment_method, payment_reference, amount, proof_image) 
                 VALUES (:order_id, :payment_method, :payment_reference, :amount, :proof_image)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':order_id' => $order_id,
            ':payment_method' => $payment_method,
            ':payment_reference' => $payment_reference,
            ':amount' => $amount,
            ':proof_image' => $proof_image
        ]);
        
        // Update order status
        $query = "UPDATE orders SET payment_proof = :proof_image WHERE id = :order_id";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':proof_image' => $proof_image,
            ':order_id' => $order_id
        ]);
        
        // Create notification
        $query = "INSERT INTO notifications (user_id, title, message, type) 
                 VALUES (:user_id, 'Payment Submitted', 'Your payment proof has been submitted and is under review.', 'info')";
        $stmt = $db->prepare($query);
        $stmt->execute([':user_id' => $user['id']]);
        
        $success_message = "Payment proof submitted successfully! We'll verify it within 24 hours.";
        
    } catch (Exception $e) {
        $error_message = "Error submitting payment proof: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Verification - AfroMarry</title>
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

    <div class="payment-verification-container">
        <div class="verification-content">
            <div class="verification-header">
                <h1>Payment Verification</h1>
                <p>Submit your payment proof to complete your order</p>
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

            <div class="order-summary">
                <h3>Order Summary</h3>
                <div class="summary-details">
                    <div class="detail-item">
                        <span>Order ID:</span>
                        <span>#<?php echo $order['id']; ?></span>
                    </div>
                    <div class="detail-item">
                        <span>Total Amount:</span>
                        <span>$ <?php echo number_format($order['total_amount']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span>Payment Method:</span>
                        <span><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></span>
                    </div>
                    <div class="detail-item">
                        <span>Order Date:</span>
                        <span><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></span>
                    </div>
                </div>
            </div>

            <form class="verification-form" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="payment_method">Payment Method</label>
                    <select name="payment_method" id="payment_method" required>
                        <option value="">Select Payment Method</option>
                        <option value="paystack">Paystack (Card/Bank Transfer)</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="receipt_upload">Receipt Upload</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="payment_reference">Payment Reference/Transaction ID</label>
                    <input type="text" name="payment_reference" id="payment_reference" 
                           placeholder="Enter your transaction reference" required>
                </div>

                <div class="form-group">
                    <label for="amount">Amount Paid</label>
                    <input type="number" name="amount" id="amount" 
                           value="<?php echo $order['total_amount']; ?>" 
                           step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="proof_image">Payment Proof (Screenshot/Receipt)</label>
                    <input type="file" name="proof_image" id="proof_image" 
                           accept="image/*" required>
                    <small>Upload a clear screenshot or receipt of your payment</small>
                </div>

                <div class="payment-instructions">
                    <h4>Payment Instructions</h4>
                    <div class="instructions-grid">
                        <div class="instruction-card">
                            <h5>Mobile Money</h5>
                            <p>Send payment to: <strong>+234 812 345 6789</strong></p>
                            <p>Reference: <strong>AFM<?php echo $order['id']; ?></strong></p>
                        </div>
                        
                        <div class="instruction-card">
                            <h5>Bank Transfer</h5>
                            <p>Account: <strong>AfroMarry Ltd</strong></p>
                            <p>Account Number: <strong>1234567890</strong></p>
                            <p>Bank: <strong>First Bank</strong></p>
                            <p>Reference: <strong>AFM<?php echo $order['id']; ?></strong></p>
                        </div>
                        
                        <div class="instruction-card">
                            <h5>Receipt Upload</h5>
                            <p>If you've already made payment through other means, upload your receipt or proof of payment.</p>
                        </div>
                        
                        <div class="instruction-card">
                            <h5>Paystack Payment</h5>
                            <p>If you paid using Paystack, the payment should already be verified automatically. If you're seeing this page, please contact support with your transaction reference.</p>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        Submit Payment Proof
                    </button>
                    <a href="dashboard.php" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <style>
        .payment-verification-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .verification-content {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .verification-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .verification-header h1 {
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        
        .verification-header p {
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
        
        .order-summary {
            background: #f8fafc;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .order-summary h3 {
            color: #1f2937;
            margin-bottom: 1rem;
        }
        
        .summary-details {
            display: grid;
            gap: 0.5rem;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-item span:first-child {
            color: #6b7280;
        }
        
        .detail-item span:last-child {
            font-weight: 600;
            color: #1f2937;
        }
        
        .verification-form {
            display: grid;
            gap: 1.5rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .form-group input,
        .form-group select {
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #8B5CF6;
        }
        
        .form-group small {
            color: #6b7280;
            margin-top: 0.5rem;
        }
        
        .payment-instructions {
            background: #f0f9ff;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 1rem 0;
        }
        
        .payment-instructions h4 {
            color: #1e40af;
            margin-bottom: 1rem;
        }
        
        .instructions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .instruction-card {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            border: 1px solid #bfdbfe;
        }
        
        .instruction-card h5 {
            color: #1e40af;
            margin-bottom: 0.5rem;
        }
        
        .instruction-card p {
            color: #374151;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        
        .instruction-card strong {
            color: #1e40af;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #8B5CF6, #EC4899);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 92, 246, 0.3);
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        @media (max-width: 768px) {
            .payment-verification-container {
                margin: 1rem;
            }
            
            .verification-content {
                padding: 1rem;
            }
            
            .instructions-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</body>
</html>
