<?php
/**
 * Standalone Seed Script - Seeds All African Regions
 * 
 * This script seeds all five regions without requiring authentication.
 * Run from command line: php seed_all_regions_standalone.php
 */

// Set a flag to skip authentication
define('SKIP_AUTH', true);

require_once __DIR__ . '/../config/database.php';

// Override requireAuth function if it exists
if (!function_exists('requireAuth')) {
    function requireAuth() {
        if (!defined('SKIP_AUTH') || !SKIP_AUTH) {
            // Only enforce auth if SKIP_AUTH is not set
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                die(json_encode(['success' => false, 'message' => 'Authentication required']));
            }
        }
        // Skip authentication when SKIP_AUTH is true
    }
}

$database = new Database();
$db = $database->getConnection();

// Include all region seed files and extract their tribe data
$seedFiles = [
    'seed_central_africa.php' => 'Central Africa',
    'seed_east_africa.php' => 'East Africa',
    'seed_west_africa.php' => 'West Africa',
    'seed_southern_africa.php' => 'Southern Africa',
    'seed_north_africa.php' => 'North Africa'
];

$results = [];
$totalAdded = 0;

foreach ($seedFiles as $file => $regionName) {
    $filePath = __DIR__ . '/' . $file;
    
    if (!file_exists($filePath)) {
        $results[$regionName] = [
            'success' => false,
            'message' => "Seed file not found: $file",
            'added' => 0
        ];
        continue;
    }
    
    try {
        // Read the file and extract tribe data
        $fileContent = file_get_contents($filePath);
        
        // Extract the $tribes array from the file
        // Look for the pattern: $tribes = [ ... ];
        if (preg_match('/\$tribes\s*=\s*\[(.*?)\];/s', $fileContent, $matches)) {
            // Evaluate the tribes array in a safe way
            $tribesCode = '$tribes = ' . $matches[0];
            eval($tribesCode);
            
            // Prepare insert and check statements
            $insert = $db->prepare("INSERT INTO tribes (name, country, region, customs, dowry_type, dowry_details, image) VALUES (:name,:country,:region,:customs,:dowry_type,:dowry_details,:image)");
            $check = $db->prepare("SELECT id FROM tribes WHERE name = :name AND country = :country LIMIT 1");
            
            $added = 0;
            foreach ($tribes as $t) {
                [$name, $country, $region, $customs, $dowryType, $dowryDetails, $image] = $t;
                
                // Check if tribe already exists
                $check->execute([':name' => $name, ':country' => $country]);
                if ($check->fetch()) {
                    continue; // skip existing
                }
                
                // Insert new tribe
                $insert->execute([
                    ':name' => $name,
                    ':country' => $country,
                    ':region' => $region,
                    ':customs' => json_encode($customs),
                    ':dowry_type' => $dowryType,
                    ':dowry_details' => $dowryDetails,
                    ':image' => $image
                ]);
                $added++;
            }
            
            $results[$regionName] = [
                'success' => true,
                'message' => "Seeded successfully",
                'added' => $added
            ];
            $totalAdded += $added;
        } else {
            $results[$regionName] = [
                'success' => false,
                'message' => 'Could not extract tribe data from file',
                'added' => 0
            ];
        }
        
    } catch (Exception $e) {
        $results[$regionName] = [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
            'added' => 0
        ];
    }
}

// Output results
if (php_sapi_name() === 'cli') {
    echo "=== AfroMarry Database Seeding Results ===\n\n";
    foreach ($results as $region => $result) {
        $status = $result['success'] ? '✅' : '❌';
        $added = $result['added'] ?? 0;
        echo "$status $region: {$result['message']} (Added: $added tribes)\n";
    }
    echo "\n✅ Total Added: $totalAdded tribes\n";
    echo "✅ Database seeding complete!\n";
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'All regions seed complete',
        'total_added' => $totalAdded,
        'regions' => $results
    ], JSON_PRETTY_PRINT);
}
?>

