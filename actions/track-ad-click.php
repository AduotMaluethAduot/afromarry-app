<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    $ad_id = intval($input['ad_id'] ?? 0);
    
    if (!$ad_id) {
        throw new Exception('Invalid ad ID');
    }
    
    // Record click
    $user_id = $_SESSION['user_id'] ?? null;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $query = "INSERT INTO ad_interactions (user_id, ad_placement_id, interaction_type, ip_address, user_agent) 
              VALUES (:user_id, :ad_placement_id, 'click', :ip_address, :user_agent)";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':user_id' => $user_id,
        ':ad_placement_id' => $ad_id,
        ':ip_address' => $ip_address,
        ':user_agent' => $user_agent
    ]);
    
    // Update click count
    $updateQuery = "UPDATE ad_placements SET clicks_count = clicks_count + 1 WHERE id = :id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->execute([':id' => $ad_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Click tracked successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>