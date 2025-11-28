<?php
// Seed Africa experts into the database
require_once '../config/database.php';

// Require customer authentication
requireAuth();

try {
    $db = (new Database())->getConnection();

$experts = [
    // East Africa (South Sudan)
    ['Aduot Jok','Dinka','Dinka marriage customs, bridewealth negotiations, cattle transfers',['Dinka','Arabic','English'],5.00,150.00,'https://d64gsuwffb70l.cloudfront.net/experts/dinka.png','Available this month'],
    ['Tut Riek','Nuer','Nuer marriage rites and negotiations, bridewealth (cattle)',['Nuer','Arabic','English'],4.90,150.00,'https://d64gsuwffb70l.cloudfront.net/experts/nuer.png','Available next week'],
    // North Africa
    ['Fatima El-Hassan','Arab (Egypt)','Nikah/mahr process, zaffa & henna guidance',['Arabic','English'],4.85,150.00,'https://d64gsuwffb70l.cloudfront.net/experts/egypt.png','Weekends only'],
    // West Africa
    ['Baba Sidi Kouyate','Mande','Mande/Bambara traditional marriage protocols',['Bambara','French','English'],4.95,150.00,'https://d64gsuwffb70l.cloudfront.net/experts/mande.png','Available today'],
    // Central Africa
    ['Mama Kiala','Kongo','Kongo matrilineal marriage customs & gift protocol',['Kikongo','French','English'],4.80,150.00,'https://d64gsuwffb70l.cloudfront.net/experts/kongo.png','Available this week'],
    // Southern Africa
    ['Umkhulu Zanele','Zulu','Lobola negotiations and ceremony order',['Zulu','English'],5.00,150.00,'https://d64gsuwffb70l.cloudfront.net/experts/zulu.png','Available next month'],
];

$insert = $db->prepare("INSERT INTO experts (name, tribe, specialization, languages, rating, hourly_rate, image, availability) VALUES (:name,:tribe,:specialization,:languages,:rating,:hourly_rate,:image,:availability)");
$check = $db->prepare("SELECT id FROM experts WHERE name = :name LIMIT 1");

$added = 0;
foreach ($experts as $e) {
    [$name,$tribe,$spec,$langs,$rating,$rate,$image,$avail] = $e;
    $check->execute([':name'=>$name]);
    if ($check->fetch()) continue;
    $insert->execute([
        ':name'=>$name,
        ':tribe'=>$tribe,
        ':specialization'=>$spec,
        ':languages'=>json_encode($langs),
        ':rating'=>$rating,
        ':hourly_rate'=>$rate,
        ':image'=>$image,
        ':availability'=>$avail
    ]);
    $added++;
}

header('Content-Type: application/json');
echo json_encode(['success'=>true,'message'=>'Experts seed complete','added'=>$added]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Error: ' . $e->getMessage()]);
}
?>


