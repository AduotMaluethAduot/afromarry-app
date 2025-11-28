<?php
require_once '../config/database.php';

requireAuth();
$user = getCurrentUser();
$token = $_GET['token'] ?? '';

if (empty($token)) {
    http_response_code(400);
    die('Download token required');
}

$database = new Database();
$db = $database->getConnection();

try {
    // Verify download token
    $query = "SELECT d.*, dp.file_path, dp.download_limit, p.name as product_name
              FROM digital_product_downloads d
              JOIN digital_products dp ON d.digital_product_id = dp.id
              JOIN products p ON dp.product_id = p.id
              WHERE d.download_token = :token 
              AND d.user_id = :user_id
              AND (d.expires_at IS NULL OR d.expires_at > NOW())
              AND d.download_count < dp.download_limit";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':token' => $token, ':user_id' => $user['id']]);
    $download = $stmt->fetch();
    
    if (!$download) {
        http_response_code(404);
        die('Download not found or expired');
    }
    
    // Check if file exists
    $file_path = __DIR__ . '/../' . $download['file_path'];
    if (!file_exists($file_path)) {
        http_response_code(404);
        die('File not found');
    }
    
    // Update download count
    $query = "UPDATE digital_product_downloads 
             SET download_count = download_count + 1, downloaded_at = NOW() 
             WHERE id = :download_id";
    $stmt = $db->prepare($query);
    $stmt->execute([':download_id' => $download['id']]);
    
    // Serve file
    $file_name = basename($file_path);
    $file_size = filesize($file_path);
    $file_type = mime_content_type($file_path) ?: 'application/octet-stream';
    
    header('Content-Type: ' . $file_type);
    header('Content-Disposition: attachment; filename="' . $download['product_name'] . '.' . pathinfo($file_name, PATHINFO_EXTENSION) . '"');
    header('Content-Length: ' . $file_size);
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    readfile($file_path);
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    die('Error downloading file: ' . $e->getMessage());
}

