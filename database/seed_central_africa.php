<?php
// Seed Central Africa tribes into the database
require_once '../config/database.php';

// Require customer authentication
requireAuth();

try {
    $db = (new Database())->getConnection();

// Entries: name, country, region, customs(array), dowry_type, dowry_details, image(optional)
$tribes = [
    // Angola
    ['Ovimbundu','Angola','Central Africa',['Preservation of values','Family selection','Modern freedom'], 'Livestock', 'Cattle and goats used as bride wealth', null],
    ['Ambundu','Angola','Central Africa',['Urban Portuguese influence','Mutual consent'], 'Cash/Goods', 'Cash and household goods', null],
    ['Bakongo (Angola)','Angola','Central Africa',['Matrilineal elements','Civil code bans polygyny'], 'Livestock', 'Cattle/goats per family ability', null],
    ['Lunda-Chokwe','Angola','Central Africa',['Initiation before marriage'], 'Goods', 'Animals, cloth and symbolic gifts', null],
    ['Nganguela','Angola','Central Africa',['Bantu rites'], 'Livestock', 'Livestock transfers', null],
    ['Ovambo (Angola)','Angola','Central Africa',['Lobola practice'], 'Cattle', 'Cattle bride wealth', null],
    ['Herero (Angola)','Angola','Central Africa',['Cattle exchanges'], 'Cattle', 'Stock transfers among lineages', null],
    ['Chokwe (Angola)','Angola','Central Africa',['Initiation rites'], 'Goods', 'Household goods and livestock', null],
    ['Luchazi','Angola','Central Africa',['Matrilineal tendencies'], 'Livestock', 'Livestock contribution', null],
    ['Mbunda','Angola','Central Africa',['Blended customs'], 'Cash', 'Cash plus gifts', null],
    ['Nyaneka','Angola','Central Africa',['Local rites'], 'TBD', 'To be documented', null],
    ['Kwanyama','Angola','Central Africa',['Ovambo customs'], 'Cattle', 'Cattle herds', null],
    ['Himba','Angola','Central Africa',['Use of ochre paint','Pastoral traditions'], 'Livestock', 'Goats/cattle', null],
    ['San (Angola)','Angola','Central Africa',['Hunter-gatherer sharing'], 'Shared', 'Resource sharing in kin networks', null],
    ['Portuguese (Angola)','Angola','Central Africa',['Catholic ceremony'], 'Money', 'Cash dowry/gifts', null],

    // Cameroon
    ['Bamileke','Cameroon','Central Africa',['Dotting gifts','Lavish feasts','Polygamy option'], 'Goods', 'Cloth and money gifts', null],
    ['Beti-Pahuin','Cameroon','Central Africa',['Elder mediation','Land marriage variants'], 'Livestock', 'Goats/cattle with gifts', null],
    ['Duala','Cameroon','Central Africa',['Coastal dances'], 'Cash/Goods', 'Cash and gifts to family', null],
    ['Kirdi','Cameroon','Central Africa',['Animist goods'], 'Goods', 'Tools/animals', null],
    ['Fulani (Cameroon)','Cameroon','Central Africa',['Cattle emphasis','Polygyny allowed'], 'Cattle', 'Cattle as mahr-like wealth', null],
    ['Bassa','Cameroon','Central Africa',['Secret societies elements'], 'Livestock', 'Livestock payments', null],
    ['Bakweri','Cameroon','Central Africa',['Mount Cameroon rites'], 'Goods', 'Household goods', null],
    ['Tikar','Cameroon','Central Africa',['Highland customs'], 'Cash', 'Monetary gifts', null],
    ['Maka (Cameroon)','Cameroon','Central Africa',['Forest traditions'], 'Livestock', 'Goats', null],
    ['Baka/Pygmy (Cameroon)','Cameroon','Central Africa',['Hunter-gatherer sharing'], 'Shared', 'Shared resources', null],
    ['Hausa (Cameroon)','Cameroon','Central Africa',['Islamic nikah'], 'Mahr', 'Money per Islamic law', null],
    ['Kanuri (Cameroon)','Cameroon','Central Africa',['Sahelian influence'], 'Goods', 'Livestock/goods', null],
    ['Gbaya (Cameroon)','Cameroon','Central Africa',['Local rites'], 'TBD', 'To be documented', null],
    ['Mandja (Cameroon)','Cameroon','Central Africa',['Local rites'], 'TBD', 'To be documented', null],
    ['Zande (Cameroon)','Cameroon','Central Africa',['State alliance history'], 'Livestock', 'Cattle/goats', null],

    // Central African Republic
    ['Gbaya','Central African Republic','Central Africa',['Ngbwa ritual','Arranged lineage links'], 'Goods', 'Cloth and money gifts', null],
    ['Banda','Central African Republic','Central Africa',['High child marriage rate historically'], 'Livestock', 'Livestock payments', null],
    ['Mandja (CAR)','Central African Republic','Central Africa',['Animist feasts'], 'Cash/Animals', 'Cash and animals', null],
    ['Zande (CAR)','Central African Republic','Central Africa',['State-forming alliances'], 'Livestock', 'Cattle/goats', null],
    ['Aka/Pygmy (CAR)','Central African Republic','Central Africa',['Hunter-gatherer sharing'], 'Shared', 'Resource sharing', null],
    ['Sara (CAR)','Central African Republic','Central Africa',['Consent-based unions'], 'Livestock', 'Cattle/goats', null],

    // Chad
    ['Sara (Chad)','Chad','Central Africa',['Consent-based','French code influences'], 'Livestock', 'Cattle/goats', null],
    ['Arab (Chad)','Chad','Central Africa',['Islamic mahr','Gender segregation'], 'Mahr', 'Money as mahr', null],
    ['Kanembu','Chad','Central Africa',['Livestock transfers'], 'Livestock', 'Cows', null],
    ['Ouaddai','Chad','Central Africa',['Tribal negotiations'], 'Cash/Goods', 'Cash and gifts', null],
    ['Bagirmi','Chad','Central Africa',['River customs'], 'Goods', 'Cloth and goods', null],
    ['Hadjerai','Chad','Central Africa',['Highland traditions'], 'Livestock', 'Goats', null],
    ['Zaghawa','Chad','Central Africa',['Nomadic camel culture'], 'Camels', 'Camel transfers', null],
    ['Kanuri (Chad)','Chad','Central Africa',['Islamic nikah'], 'Mahr', 'Money per Islamic law', null],

    // DRC
    ['Luba','Congo, Democratic Republic of the','Central Africa',['Polygamy with first wife pre-eminent','Bride wealth'], 'Livestock/Money', 'Cattle and money', null],
    ['Kongo (DRC)','Congo, Democratic Republic of the','Central Africa',['Matrilineal elements','Festive dances'], 'Goods', 'Cloth and cash', null],
    ['Mongo','Congo, Democratic Republic of the','Central Africa',['Clan alliances'], 'Livestock', 'Cattle/goats', null],
    ['Rundi','Congo, Democratic Republic of the','Central Africa',['Urban blends'], 'Cash/Goods', 'Cash and goods', null],
    ['Lunda (DRC)','Congo, Democratic Republic of the','Central Africa',['Initiation'], 'Goods', 'Symbolic goods', null],

    // Republic of the Congo
    ['Bakongo (RoC)','Congo, Republic of the','Central Africa',['Matrilineal','Festive dances'], 'Goods', 'Cloth and cash', null],
    ['Bateke','Congo, Republic of the','Central Africa',['River negotiations'], 'Livestock', 'Cattle/goats', null],
    ['M\'Bochi','Congo, Republic of the','Central Africa',['Clan structures'], 'Money', 'Cash transfers', null],
    ['Sangha','Congo, Republic of the','Central Africa',['Forest rites'], 'Goods', 'Household goods', null],
    ['Babongo/Pygmy (RoC)','Congo, Republic of the','Central Africa',['Sharing economy'], 'Shared', 'Resources shared', null],

    // Equatorial Guinea
    ['Fang (EG)','Equatorial Guinea','Central Africa',['Dowry and groom service','Polygyny common'], 'Livestock/Cash', 'Cattle with cash gifts', null],
    ['Bubi','Equatorial Guinea','Central Africa',['Island rituals'], 'Goods', 'Gifts and goods', null],
    ['Ndowe','Equatorial Guinea','Central Africa',['Coastal traditions'], 'Cash', 'Cash and gifts', null],

    // Gabon
    ['Fang (Gabon)','Gabon','Central Africa',['Inter-ethnic common','Arranged options'], 'Livestock/Cash', 'Cattle and cash', null],
    ['Myene','Gabon','Central Africa',['Coastal religious/customary mix'], 'Goods', 'Household goods', null],
    ['Bateke (Gabon)','Gabon','Central Africa',['River customs'], 'Livestock', 'Goats/cattle', null],

    // São Tomé and Príncipe
    ['Forros/Creole','São Tomé and Príncipe','Central Africa',['Catholic with ethnic rituals','Monogamy common'], 'Money/Gifts', 'Cash and gifts', null],
    ['Angolares','São Tomé and Príncipe','Central Africa',['Communal island customs'], 'Goods', 'Household goods', null],
    ['Tonga (STP)','São Tomé and Príncipe','Central Africa',['Feasts and gatherings'], 'Livestock', 'Goats', null],
];

$insert = $db->prepare("INSERT INTO tribes (name, country, region, customs, dowry_type, dowry_details, image) VALUES (:name,:country,:region,:customs,:dowry_type,:dowry_details,:image)");
$check = $db->prepare("SELECT id FROM tribes WHERE name = :name AND country = :country LIMIT 1");

$added = 0;
foreach ($tribes as $t) {
    [$name,$country,$region,$customs,$dowryType,$dowryDetails,$image] = $t;
    $check->execute([':name'=>$name, ':country'=>$country]);
    if ($check->fetch()) {
        continue; // skip existing
    }
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

header('Content-Type: application/json');
echo json_encode(['success'=>true,'message'=>"Central Africa seed complete","added"=>$added]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Error: ' . $e->getMessage()]);
}
?>


