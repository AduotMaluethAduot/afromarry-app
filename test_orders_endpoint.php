<?php
// Test the orders endpoint directly
header('Content-Type: text/plain');

echo "=== Testing Orders Endpoint ===\n\n";

// Simulate a POST request
$_SERVER['REQUEST_METHOD'] = 'POST';

// Capture any output
ob_start();

try {
    echo "1. Including orders endpoint...\n";
    include 'actions/orders.php';
    echo "   ✓ Orders endpoint included\n";
    
    $output = ob_get_contents();
    ob_end_clean();
    
    echo "\n=== Captured Output ===\n";
    if (!empty($output)) {
        echo $output;
    } else {
        echo "No output captured\n";
    }
    
} catch (Exception $e) {
    $output = ob_get_contents();
    ob_end_clean();
    
    echo "\n=== Captured Output ===\n";
    if (!empty($output)) {
        echo $output;
    }
    
    echo "\n=== Exception ===\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
} catch (Error $e) {
    $output = ob_get_contents();
    ob_end_clean();
    
    echo "\n=== Captured Output ===\n";
    if (!empty($output)) {
        echo $output;
    }
    
    echo "\n=== Error ===\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>