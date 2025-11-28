<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get parameters
    $tribe = $_GET['tribe'] ?? null;
    $region = $_GET['region'] ?? null;
    $limit = min(abs(intval($_GET['limit'] ?? 5)), 10); // Max 10 ads
    
    // Build query based on targeting
    $query = "SELECT ap.*, ac.name as campaign_name, a.company_name 
              FROM ad_placements ap
              JOIN ad_campaigns ac ON ap.campaign_id = ac.id
              JOIN advertisers a ON ac.advertiser_id = a.id
              WHERE ap.is_active = 1 AND ac.status = 'active' AND ac.start_date <= CURDATE() AND ac.end_date >= CURDATE()";
    
    $params = [];
    
    // Add targeting conditions
    if ($tribe) {
        $query .= " AND (ap.tribe_targeting IS NULL OR JSON_CONTAINS(ap.tribe_targeting, :tribe) OR JSON_CONTAINS(ap.tribe_targeting, JSON_QUOTE(:tribe2)))";
        $params[':tribe'] = json_encode($tribe);
        $params[':tribe2'] = $tribe;
    }
    
    if ($region) {
        $query .= " AND (ap.region_targeting IS NULL OR JSON_CONTAINS(ap.region_targeting, :region) OR JSON_CONTAINS(ap.region_targeting, JSON_QUOTE(:region2)))";
        $params[':region'] = json_encode($region);
        $params[':region2'] = $region;
    }
    
    $query .= " ORDER BY RAND() LIMIT :limit";
    $params[':limit'] = $limit;
    
    $stmt = $db->prepare($query);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        if ($key === ':limit') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();
    $ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Record impressions for each ad
    $user_id = $_SESSION['user_id'] ?? null;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    foreach ($ads as $ad) {
        $insertQuery = "INSERT INTO ad_interactions (user_id, ad_placement_id, interaction_type, ip_address, user_agent) 
                        VALUES (:user_id, :ad_placement_id, 'impression', :ip_address, :user_agent)";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->execute([
            ':user_id' => $user_id,
            ':ad_placement_id' => $ad['id'],
            ':ip_address' => $ip_address,
            ':user_agent' => $user_agent
        ]);
        
        // Update impression count
        $updateQuery = "UPDATE ad_placements SET impressions_count = impressions_count + 1 WHERE id = :id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([':id' => $ad['id']]);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $ads
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>