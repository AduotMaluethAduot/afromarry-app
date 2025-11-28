<?php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Initiate MTN Mobile Money payment
    $data = json_decode(file_get_contents('php://input'), true);
    
    requireAuth();
    $user = getCurrentUser();
    
    try {
        $order_id = $data['order_id'] ?? null;
        $phone_number = $data['phone_number'] ?? '';
        $amount = $data['amount'] ?? 0;
        $currency = $data['currency'] ?? 'GHS';
        $order_reference = $data['order_reference'] ?? '';
        
        if (empty($order_id) || empty($phone_number) || empty($amount)) {
            throw new Exception('Missing required payment parameters');
        }
        
        // Get payment method configuration
        $query = "SELECT * FROM payment_methods WHERE method_name = 'mtn_momo' AND is_active = TRUE";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $payment_method = $stmt->fetch();
        
        if (!$payment_method) {
            throw new Exception('MTN Mobile Money is not configured');
        }
        
        // Extract API credentials from config (should be encrypted in production)
        $api_key = $payment_method['api_key'] ?? '';
        $api_secret = $payment_method['api_secret'] ?? '';
        $merchant_id = $payment_method['merchant_id'] ?? '';
        
        if (empty($api_key) || empty($api_secret)) {
            // Return placeholder response if not configured
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'MTN MoMo payment initiated (demo mode)',
                'phone_number' => $phone_number,
                'amount' => $amount,
                'transaction_id' => 'MOMO-' . time(),
                'order_reference' => $order_reference,
                'status' => 'pending',
                'demo_mode' => true
            ]);
            exit;
        }
        
        // MTN Mobile Money API Integration
        // Note: This is a placeholder. Actual implementation requires MTN MoMo API credentials
        $mtn_api_url = 'https://sandbox.momodeveloper.mtn.com/collection/v1_0/requesttopay';
        
        $request_data = [
            'amount' => (string)$amount,
            'currency' => $currency,
            'externalId' => $order_reference,
            'payer' => [
                'partyIdType' => 'MSISDN',
                'partyId' => preg_replace('/[^0-9]/', '', $phone_number)
            ],
            'payerMessage' => 'AfroMarry Order Payment',
            'payeeNote' => 'Order: ' . $order_reference
        ];
        
        // Create access token (requires OAuth2 flow in production)
        $token = generateMTNMoMoToken($api_key, $api_secret);
        
        // Make API request
        $ch = curl_init($mtn_api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'X-Target-Environment: sandbox', // Use 'production' in live
            'X-Reference-Id: ' . $order_reference
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_data));
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 202) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Payment request sent to your phone',
                'phone_number' => $phone_number,
                'amount' => $amount,
                'transaction_id' => $order_reference,
                'order_reference' => $order_reference,
                'status' => 'pending'
            ]);
        } else {
            throw new Exception('Failed to initiate payment: ' . $response);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check payment status
    $order_id = $_GET['order_id'] ?? null;
    
    if (empty($order_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Order ID required']);
        exit;
    }
    
    try {
        // Get order payment status
        $query = "SELECT payment_reference, payment_verified, status FROM orders WHERE id = :order_id";
        $stmt = $db->prepare($query);
        $stmt->execute([':order_id' => $order_id]);
        $order = $stmt->fetch();
        
        if (!$order) {
            throw new Exception('Order not found');
        }
        
        $status = 'pending';
        if ($order['payment_verified']) {
            $status = 'completed';
        } elseif ($order['status'] === 'cancelled') {
            $status = 'cancelled';
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'status' => $status,
            'payment_verified' => (bool)$order['payment_verified']
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function generateMTNMoMoToken($api_key, $api_secret) {
    // MTN MoMo OAuth2 token generation
    // This is a placeholder - actual implementation requires OAuth2 flow
    $token_url = 'https://sandbox.momodeveloper.mtn.com/collection/token/';
    
    $ch = curl_init($token_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_USERPWD, $api_key . ':' . $api_secret);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $token_data = json_decode($response, true);
        return $token_data['access_token'] ?? '';
    }
    
    return '';
}

