<?php
/**
 * Seed Products - Add Sample Marketplace Products
 * 
 * This script adds sample products to the marketplace.
 * Products from database.sql are inserted here for easy seeding.
 */

require_once '../config/database.php';

// Temporarily override requireAuth to allow seeding without login
if (!function_exists('requireAuth')) {
    function requireAuth() {
        // Skip authentication during seeding
    }
}

$database = new Database();
$db = $database->getConnection();

// Sample products (from database.sql)
$products = [
    ['Royal Kente Cloth', 5200.00, 'USD', 'fabrics', 'Akan', 'https://d64gsuwffb70l.cloudfront.net/68fcfebef8ea68585421298c_1761410823359_9055dec3.webp', 'Authentic handwoven kente with gold patterns', 50],
    ['Mudcloth Wedding Fabric', 8000.00, 'USD', 'fabrics', 'Bambara', 'https://d64gsuwffb70l.cloudfront.net/68fcfebef8ea68585421298c_1761410825395_3e87fe8f.webp', 'Traditional Mali mudcloth for ceremonies', 30],
    ['Ankara Print Bundle', 6000.00, 'USD', 'fabrics', 'Yoruba', 'https://d64gsuwffb70l.cloudfront.net/68fcfebef8ea68585421298c_1761410827142_73be90d9.webp', 'Vibrant Ankara prints for Aso-Ebi', 100],
    ['Coral Bead Necklace', 45000.00, 'USD', 'jewelry', 'Edo', 'https://d64gsuwffb70l.cloudfront.net/68fcfebef8ea68585421298c_1761410827851_3cbd249f.webp', 'Traditional coral beads for brides', 25],
    ['Gold Wedding Necklace', 85000.00, 'USD', 'jewelry', 'Ashanti', 'https://d64gsuwffb70l.cloudfront.net/68fcfebef8ea68585421298c_1761410829558_c635f12d.webp', 'Handcrafted gold necklace with cultural motifs', 15],
    ['Beaded Bridal Set', 25000.00, 'USD', 'jewelry', 'Maasai', 'https://d64gsuwffb70l.cloudfront.net/68fcfebef8ea68585421298c_1761410831980_477c4163.webp', 'Colorful Maasai beadwork set', 40],
    ['Ceremonial Calabash', 3500.00, 'USD', 'ceremonial', 'Kikuyu', 'https://d64gsuwffb70l.cloudfront.net/68fcfebef8ea68585421298c_1761410833183_bc7fbf6b.webp', 'Carved calabash for traditional drinks', 60],
    ['Kola Nut Gift Box', 2500.00, 'USD', 'ceremonial', 'Yoruba', 'https://d64gsuwffb70l.cloudfront.net/68fcfebef8ea68585421298c_1761410835299_333d7708.webp', 'Premium kola nuts in decorative box', 80],
    ['Wedding Gourd Set', 4500.00, 'USD', 'ceremonial', 'Zulu', 'https://d64gsuwffb70l.cloudfront.net/68fcfebef8ea68585421298c_1761410837340_ddd1889b.webp', 'Traditional gourds for ceremonies', 35],
    ['Embroidered Dashiki', 15000.00, 'USD', 'attire', 'Hausa', 'https://d64gsuwffb70l.cloudfront.net/68fcfebef8ea68585421298c_1761410844483_d35b6cd7.webp', 'Premium embroidered dashiki for grooms', 45],
];

$insert = $db->prepare("INSERT INTO products (name, price, currency, category, tribe, image, description, stock_quantity) VALUES (:name, :price, :currency, :category, :tribe, :image, :description, :stock_quantity)");
$check = $db->prepare("SELECT id FROM products WHERE name = :name AND category = :category LIMIT 1");

$added = 0;
foreach ($products as $product) {
    [$name, $price, $currency, $category, $tribe, $image, $description, $stock] = $product;
    
    // Check if product already exists
    $check->execute([':name' => $name, ':category' => $category]);
    if ($check->fetch()) {
        continue;
    }
    
    $insert->execute([
        ':name' => $name,
        ':price' => $price,
        ':currency' => $currency,
        ':category' => $category,
        ':tribe' => $tribe,
        ':image' => $image,
        ':description' => $description,
        ':stock_quantity' => $stock
    ]);
    $added++;
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Products seed complete',
    'added' => $added
], JSON_PRETTY_PRINT);
?>

