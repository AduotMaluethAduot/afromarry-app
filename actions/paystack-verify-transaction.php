<?php
require_once '../config/database.php';
require_once '../config/payment_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['reference'])) {
        throw new Exception('Transaction reference is required');
    }
    
    $reference = $data['reference'];
    
    // Verify transaction with Paystack API
    $curl = curl_init();
    
    curl_setopt_array($curl, array(
        CURLOPT_URL => PAYSTACK_VERIFY_ENDPOINT . $reference,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer " . PAYSTACK_SECRET_KEY,
            "Cache-Control: no-cache",
        ),
    ));
    
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    
    if ($err) {
        throw new Exception("cURL Error: " . $err);
    }
    
    $transaction = json_decode($response, true);
    
    if (!$transaction['status']) {
        throw new Exception('Transaction verification failed: ' . $transaction['message']);
    }
    
    $transactionData = $transaction['data'];
    
    // Check if transaction was successful
    if ($transactionData['status'] !== 'success') {
        throw new Exception('Transaction was not successful');
    }
    
    // Update order status in database
    $query = "UPDATE orders SET status = 'paid', payment_verified = TRUE, 
              payment_reference = :reference, updated_at = NOW() 
              WHERE payment_reference = :reference";
    $stmt = $db->prepare($query);
    $stmt->execute([':reference' => $reference]);
    
    $rowCount = $stmt->rowCount();
    
    if ($rowCount === 0) {
        throw new Exception('Order not found for this transaction reference');
    }
    
    // Create notification for user
    $query = "SELECT user_id FROM orders WHERE payment_reference = :reference LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([':reference' => $reference]);
    $order = $stmt->fetch();
    
    if ($order) {
        $notificationQuery = "INSERT INTO notifications (user_id, title, message, type) 
                             VALUES (:user_id, 'Payment Successful', 'Your payment has been verified and your order is being processed.', 'success')";
        $notificationStmt = $db->prepare($notificationQuery);
        $notificationStmt->execute([':user_id' => $order['user_id']]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Transaction verified successfully',
        'data' => $transactionData
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>