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
    
    // Validate required fields
    $required_fields = ['email', 'amount', 'reference', 'currency'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    $email = $data['email'];
    $amount = $data['amount'];
    $reference = $data['reference'];
    $currency = $data['currency'];
    
    // Initialize transaction with Paystack
    $curl = curl_init();
    
    curl_setopt_array($curl, array(
        CURLOPT_URL => PAYSTACK_INIT_ENDPOINT,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode([
            'email' => $email,
            'amount' => $amount * 100, // Convert to kobo
            'reference' => $reference,
            'currency' => $currency
        ]),
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer " . PAYSTACK_SECRET_KEY,
            "Content-Type: application/json",
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
        throw new Exception('Transaction initialization failed: ' . $transaction['message']);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Transaction initialized successfully',
        'data' => $transaction['data']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>