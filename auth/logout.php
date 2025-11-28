<?php
// Start output buffering to prevent any accidental output
ob_start();

session_start();

// Check if request is AJAX
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($is_ajax) {
    header('Content-Type: application/json');
}

// Destroy session
session_destroy();

if ($is_ajax) {
    echo json_encode([
        'success' => true,
        'message' => 'Logged out successfully',
        'redirect' => '../index.php'
    ]);
    ob_end_flush();
    exit;
} else {
    header("Location: ../index.php");
    exit();
}
?>