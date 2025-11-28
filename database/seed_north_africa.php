<?php
// Seed North Africa tribes into the database
require_once '../config/database.php';

// Require customer authentication
requireAuth();

try {
    $db = (new Database())->getConnection();

// Entries: name, country, region, customs(array), dowry_type, dowry_details, image(optional)
$tribes = [
    // Algeria
    ['Arabs (Algeria)','Algeria','North Africa',['Henna night','El Khouara proposal','Multi-day feasts','Family-headed obedience'],'Mahr','Gold and money mahr',null],
    ['Berbers/Amazigh (Algeria)','Algeria','North Africa',['Negaffa guides bride','Traditional silk dress'],'Sheep/Jewelry','Sheep and jewelry as dowry',null],
    ['Tuareg (Algeria)','Algeria','North Africa',['Nomadic veiling','Saharan customs'],'Camels','Camel transfers as dowry',null],
    ['Mozabites','Algeria','North Africa',['Ibadi community customs'],'TBD','To be documented',null],
    ['Chaoui (Aures)','Algeria','North Africa',['Mountain rituals with music'],'Goods','Jewelry and livestock gifts',null],
    ['Sahrawi (Algeria)','Algeria','North Africa',['Desert nomadic rituals'],'Camels/Goods','Camels and gifts',null],
    ['Turks (Algeria)','Algeria','North Africa',['Ottoman attire influences'],'Gold/Cash','Gold and cash',null],
    ['French (Algeria)','Algeria','North Africa',['Western-influenced ceremonies'],'Money/Gifts','Monetary gifts',null],

    // Egypt
    ['Egyptian Arabs','Egypt','North Africa',['Henna party','Zaffa procession','Mahlabiya cake','Family central'],'Mahr','Gold and money mahr',null],
    ['Nubians','Egypt','North Africa',['Lelet el Hena','River dances','Three-day events'],'Gold/Gifts','Gold and gifts to bride',null],
    ['Bedouin (Egypt)','Egypt','North Africa',['Tribal alliances','Black embroidered attire'],'Livestock/Money','Camels and cash',null],
    ['Copts','Egypt','North Africa',['Church ceremony','Henna and zaffa'],'Mahr/Gold','Gold and mahr variants',null],
    ['Berbers (Siwa/Siwi)','Egypt','North Africa',['Oasis/Siwi rituals','Matrilineal elements'],'Jewelry/Livestock','Jewelry and animals',null],
    ['Beja','Egypt','North Africa',['Nomadic alliances'],'Camels','Camel transfers',null],
    ['Domari','Egypt','North Africa',['Nomad customs'],'Goods','Household goods',null],
    ['Armenians (Egypt)','Egypt','North Africa',['Western-Christian blends'],'Money/Gifts','Cash and gifts',null],
    ['Greeks (Egypt)','Egypt','North Africa',['Orthodox traditions'],'Gold','Gold gifts',null],
    ['Jews (Egypt)','Egypt','North Africa',['Synagogue rites'],'Mahr','Mahr per tradition',null],
    ['Sudanese (Egypt)','Egypt','North Africa',['Nile valley blends'],'Gold/Cloth','Gold and cloth gifts',null],

    // Libya
    ['Arabs (Libya)','Libya','North Africa',['Hammam Bukhari bath','Pink ethnic dress','Patrilineal cousin preference'],'Mahr','Gold and money mahr',null],
    ['Berbers (Libya)','Libya','North Africa',['Matrilineal elements','Veiling'],'Jewelry/Livestock','Jewelry and animals',null],
    ['Tuareg (Libya)','Libya','North Africa',['Desert camel rituals'],'Camels','Camel transfers',null],
    ['Tebu/Toubou','Libya','North Africa',['Nomadic alliances'],'Livestock','Goats/camels',null],
    ['Greeks (Libya)','Libya','North Africa',['Orthodox wedding'],'Gold','Gold gifts',null],
    ['Italians (Libya)','Libya','North Africa',['Catholic ceremonies'],'Money/Gifts','Cash and gifts',null],
    ['Amazigh (Nafusi/Kabyle)','Libya','North Africa',['Mountain rituals','Veiling; jewelry'],'Jewelry/Goods','Jewelry and goods',null],
    ['Warfalla (Arab tribe)','Libya','North Africa',['Tribal negotiations'],'Livestock','Livestock payments',null],

    // Mauritania
    ['Arab-Berbers (Mauritania)','Mauritania','North Africa',['Arranged marriages','Cousin preference','Polygyny permitted'],'Mahr','Money and livestock',null],
    ['Haratines','Mauritania','North Africa',['Traditional Islamic rites','Intermarriage common'],'Livestock','Livestock contributions',null],
    ['Soninke (Mauritania)','Mauritania','North Africa',['Matrilineal elements','Arranged unions'],'Goods','Cloth and animals',null],
    ['Wolof (Mauritania)','Mauritania','North Africa',['Endogamous cousin marriages'],'Mahr (Gold)','Gold dowry',null],
    ['Fulani/Peul (Mauritania)','Mauritania','North Africa',['Cattle alliances','Polygyny'],'Cattle','Cattle payments',null],
    ['Tukulor','Mauritania','North Africa',['Islamic feasts'],'Money','Cash dowry',null],
    ['Bambara (Mauritania)','Mauritania','North Africa',['Kin-based alliances'],'Money/Goods','Cash and gifts',null],
    ['Moors','Mauritania','North Africa',['Nomadic customs'],'Livestock','Animal wealth',null],

    // Morocco
    ['Arabs (Morocco)','Morocco','North Africa',['Henna night','Amariya procession','Multi-day feasts','Green/gold caftan'],'Mahr/Jewelry','Gold, money and jewelry',null],
    ['Amazigh/Berber (Morocco)','Morocco','North Africa',['Negaffa guides bride','Silk dress','Sheep/jewelry dowry'],'Sheep/Jewelry','Sheep and jewelry',null],
    ['Sahrawi (Morocco)','Morocco','North Africa',['Desert nomadic rituals'],'Camels/Goods','Camels and goods',null],
    ['Rifians (Morocco)','Morocco','North Africa',['Mountain rituals'],'Goods','Jewelry and livestock',null],
    ['Sous (Berber)','Morocco','North Africa',['Matrilineal tendencies'],'Jewelry','Jewelry as dowry',null],
    ['Chleuh (Berber)','Morocco','North Africa',['Veiling customs'],'Livestock','Goats/cattle',null],
    ['Jews (Morocco)','Morocco','North Africa',['Synagogue rites','Henna'],'Mahr','Mahr per rite',null],
    ['French (Morocco)','Morocco','North Africa',['Western blends'],'Money/Gifts','Cash and gifts',null],
    ['Spanish (Morocco)','Morocco','North Africa',['Catholic ceremonies'],'Gold','Gold gifts',null],

    // Tunisia
    ['Arab-Berbers (Tunisia)','Tunisia','North Africa',['Five-event weddings','Henna and feasts','White or traditional attire'],'Mahr/Money/Jewelry','Money and jewelry',null],
    ['Turkish descent (Tunisia)','Tunisia','North Africa',['Ottoman attire; multi-day'],'Gold/Cash','Gold and cash',null],
    ['Jews (Tunisia)','Tunisia','North Africa',['Synagogue rites; henna'],'Mahr','Mahr per law',null],
    ['Berbers (Kabyle, Tunisia)','Tunisia','North Africa',['Matrilineal; veiling'],'Jewelry','Jewelry transfer',null],
    ['French (Tunisia)','Tunisia','North Africa',['Western-Catholic'],'Money','Monetary gifts',null],
    ['Italians (Tunisia)','Tunisia','North Africa',['Mediterranean customs'],'Gold','Gold gifts',null],
    ['Maltese (Tunisia)','Tunisia','North Africa',['Island blends'],'Goods','Household goods',null],
];

$insert = $db->prepare("INSERT INTO tribes (name, country, region, customs, dowry_type, dowry_details, image) VALUES (:name,:country,:region,:customs,:dowry_type,:dowry_details,:image)");
$check = $db->prepare("SELECT id FROM tribes WHERE name = :name AND country = :country LIMIT 1");

$added = 0;
foreach ($tribes as $t) {
    [$name,$country,$region,$customs,$dowryType,$dowryDetails,$image] = $t;
    $check->execute([':name'=>$name, ':country'=>$country]);
    if ($check->fetch()) {
        continue;
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
echo json_encode(['success'=>true,'message'=>'North Africa seed complete','added'=>$added]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Error: ' . $e->getMessage()]);
}
?>


