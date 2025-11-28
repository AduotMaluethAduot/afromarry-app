<?php
/**
 * Master Seed Script - Seeds All African Regions
 * 
 * Runs all regional seed files to populate database with tribes.
 * This version bypasses authentication for easier initial setup.
 */

require_once '../config/database.php';

// Temporarily override requireAuth to allow seeding without login
$originalRequireAuth = null;
if (function_exists('requireAuth')) {
    // Save reference but don't call it
}

// Define a no-op requireAuth for this script
if (!function_exists('requireAuth')) {
    function requireAuth() {
        // Skip authentication during seeding
    }
} else {
    // Override the existing function temporarily
    $GLOBALS['_SKIP_AUTH'] = true;
}

$database = new Database();
$db = $database->getConnection();

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
        // Capture output from included file
        ob_start();
        
        // Include the seed file (it will execute and output JSON)
        include $filePath;
        
        $output = ob_get_clean();
        
        // Try to parse JSON response
        $jsonResponse = json_decode($output, true);
        
        if ($jsonResponse && isset($jsonResponse['success'])) {
            $results[$regionName] = $jsonResponse;
            $totalAdded += $jsonResponse['added'] ?? 0;
        } else {
            // If output wasn't JSON, assume it failed
            $results[$regionName] = [
                'success' => false,
                'message' => 'Unexpected output format',
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

// Prepare summary response
$summary = [
    'success' => true,
    'message' => 'All regions seed complete',
    'total_added' => $totalAdded,
    'regions' => $results
];

// Output based on context
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
    echo json_encode($summary, JSON_PRETTY_PRINT);
}
?>
