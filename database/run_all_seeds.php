<?php
/**
 * Run All Seeds - Seeds All Regions
 * 
 * This script runs all seed files. It requires you to be logged in first.
 * 
 * Steps:
 * 1. Login to your application
 * 2. Visit this page: http://localhost/AfroMarry/database/run_all_seeds.php
 * 
 * OR run seed files individually while logged in.
 */

require_once '../config/database.php';

// Check if user is logged in (seed files require auth)
if (!isLoggedIn()) {
    if (php_sapi_name() === 'cli') {
        die("ERROR: You must be logged in to run seeds. Please login via browser first.\n");
    } else {
        // Redirect to login
        header('Location: ' . base_url('auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'])));
        exit;
    }
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
        $results[$regionName] = ['success' => false, 'message' => "File not found: $file", 'added' => 0];
        continue;
    }
    
    try {
        ob_start();
        include $filePath;
        $output = ob_get_clean();
        
        $jsonResponse = json_decode($output, true);
        if ($jsonResponse && isset($jsonResponse['success'])) {
            $results[$regionName] = $jsonResponse;
            $totalAdded += $jsonResponse['added'] ?? 0;
        } else {
            $results[$regionName] = ['success' => false, 'message' => 'Unexpected response format', 'added' => 0];
        }
    } catch (Exception $e) {
        $results[$regionName] = ['success' => false, 'message' => $e->getMessage(), 'added' => 0];
    }
}

// Output results
if (php_sapi_name() === 'cli') {
    echo "=== AfroMarry Seed Results ===\n\n";
    foreach ($results as $region => $result) {
        $status = $result['success'] ? '✅' : '❌';
        $added = $result['added'] ?? 0;
        echo "$status $region: {$result['message']} ({$added} tribes added)\n";
    }
    echo "\n✅ Total: $totalAdded tribes added to database\n";
    echo "✅ Seeding complete!\n";
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'All regions seeded successfully',
        'total_added' => $totalAdded,
        'regions' => $results
    ], JSON_PRETTY_PRINT);
}
?>

