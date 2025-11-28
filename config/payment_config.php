<?php
/**
 * Payment Configuration
 * 
 * Loads payment gateway settings from database or environment variables
 * Falls back to default/test values if not set
 */

// Load settings helper
require_once __DIR__ . '/settings.php';

// Load environment variables first (for backward compatibility)
if (!function_exists('env')) {
    require_once __DIR__ . '/env_loader.php';
}

// Load all settings
$settings = getAllSettings();

// Paystack API Keys (loaded from database settings, with environment fallback, then default test values)
define('PAYSTACK_SECRET_KEY', 
    $settings['paystack_secret_key'] ?? 
    env('PAYSTACK_SECRET_KEY', 'sk_test_340977d65daa6709c7d00a5fbbbb42f6316b61c5')
);

define('PAYSTACK_PUBLIC_KEY', 
    $settings['paystack_public_key'] ?? 
    env('PAYSTACK_PUBLIC_KEY', 'pk_test_4543f3b9d82837aacfb26eb4815c6bea2e498554')
);

// Flutterwave API Keys (loaded from database settings, with environment fallback, then default test values)
define('FLUTTERWAVE_SECRET_KEY', 
    $settings['flutterwave_secret_key'] ?? 
    env('FLUTTERWAVE_SECRET_KEY', 'FLWSECK_TEST_your_flutterwave_secret_key_here')
);

define('FLUTTERWAVE_PUBLIC_KEY', 
    $settings['flutterwave_public_key'] ?? 
    env('FLUTTERWAVE_PUBLIC_KEY', 'FLWPUBK_TEST_your_flutterwave_public_key_here')
);

// Paystack URLs
define('PAYSTACK_API_URL', 'https://api.paystack.co');
define('PAYSTACK_INIT_ENDPOINT', PAYSTACK_API_URL . '/transaction/initialize');
define('PAYSTACK_VERIFY_ENDPOINT', PAYSTACK_API_URL . '/transaction/verify/');

// Payment verification URL (for redirect after payment)
define('PAYMENT_SUCCESS_URL', env('PAYMENT_SUCCESS_URL', '/pages/payment-verification.php'));

?>