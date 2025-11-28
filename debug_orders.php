<?php
// Debug script to check what the orders endpoint returns
header('Content-Type: text/plain');

echo "=== Debug Orders Endpoint ===\n\n";

// Start output buffering to capture any output
ob_start();

try {
    echo "1. Including required files...\n";
    
    // Include the orders controller
    require_once 'controllers/OrderController.php';
    echo "   ✓ Controller included successfully\n";
    
    echo "\n2. Creating controller instance...\n";
    $controller = new OrderController();
    echo "   ✓ Controller instance created\n";
    
    echo "\n3. Testing method calls...\n";
    // We can't directly access protected properties, but we can test method calls
    
    echo "   ✓ Controller created without errors\n";
    
    // Get any output that was generated
    $buffer_output = ob_get_contents();
    ob_end_clean();
    
    echo "\n=== Buffer Output ===\n";
    if (!empty($buffer_output)) {
        echo $buffer_output;
    } else {
        echo "No buffer output\n";
    }
    
    echo "\n=== Test Completed ===\n";
    
} catch (Exception $e) {
    // Get any output that was generated
    $buffer_output = ob_get_contents();
    ob_end_clean();
    
    echo "\n=== Buffer Output ===\n";
    if (!empty($buffer_output)) {
        echo $buffer_output;
    }
    
    echo "\n=== Exception ===\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    // Get any output that was generated
    $buffer_output = ob_get_contents();
    ob_end_clean();
    
    echo "\n=== Buffer Output ===\n";
    if (!empty($buffer_output)) {
        echo $buffer_output;
    }
    
    echo "\n=== Error ===\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
?>