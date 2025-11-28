<?php
require_once '../config/database.php';
require_once '../config/payment_config.php';

if (!isLoggedIn()) {
    redirect(auth_url('login.php'));
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Payment - AfroMarry</title>
    <base href="<?php echo BASE_PATH; ?>/">
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container" style="max-width: 800px; margin: 2rem auto; padding: 0 1rem;">
        <div class="auth-card">
            <div class="auth-header">
                <h2>Test Payment</h2>
                <p>Test the payment flow with Paystack</p>
            </div>
            
            <div class="form-group">
                <label>Paystack Public Key:</label>
                <div class="alert alert-info">
                    <?php echo PAYSTACK_PUBLIC_KEY ?: 'Not configured'; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label>Paystack Secret Key:</label>
                <div class="alert alert-info">
                    <?php echo PAYSTACK_SECRET_KEY ? 'Configured (hidden for security)' : 'Not configured'; ?>
                </div>
            </div>
            
            <button id="test-paystack" class="btn-primary btn-large" style="width: 100%; margin-top: 1rem;">
                <i class="fas fa-credit-card"></i>
                Test Paystack Payment
            </button>
            
            <div id="result" style="margin-top: 1rem;"></div>
        </div>
    </div>
    
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script>
        document.getElementById('test-paystack').addEventListener('click', function() {
            const publicKey = '<?php echo PAYSTACK_PUBLIC_KEY; ?>';
            
            if (!publicKey || publicKey === '') {
                document.getElementById('result').innerHTML = '<div class="alert alert-error">Paystack is not properly configured</div>';
                return;
            }
            
            // Initialize Paystack
            const handler = PaystackPop.setup({
                key: publicKey,
                email: '<?php echo $user['email']; ?>',
                amount: 10000, // 100 NGN in kobo
                currency: 'NGN',
                ref: 'TEST_' + Date.now(),
                callback: function(response) {
                    document.getElementById('result').innerHTML = 
                        '<div class="alert alert-success">' +
                        '<h4>Payment Successful!</h4>' +
                        '<p>Reference: ' + response.reference + '</p>' +
                        '<p>Transaction: ' + response.trans + '</p>' +
                        '</div>';
                },
                onClose: function() {
                    document.getElementById('result').innerHTML = 
                        '<div class="alert alert-info">Payment window closed</div>';
                }
            });
            
            handler.openIframe();
        });
    </script>
</body>
</html>