<?php
// Seed East Africa tribes into the database
require_once '../config/database.php';

// Require customer authentication
requireAuth();

try {
    $db = (new Database())->getConnection();

// Entries: name, country, region, customs(array), dowry_type, dowry_details, image(optional)
$tribes = [
    // Burundi
    ['Hutu (Burundi)','Burundi','East Africa',['Gufata irembo','Gusaba','Three ceremonies'],'Cattle','Cows as bride wealth',null],
    ['Tutsi (Burundi)','Burundi','East Africa',['Pastoral alliances','Cattle emphasis'],'Cattle','Cattle transfers',null],
    ['Twa (Burundi)','Burundi','East Africa',['Forest-based','Community sharing'],'Goods','Shared resources',null],

    // Comoros
    ['Comorians','Comoros','East Africa',['Grand marriage (Anda)','Djaliko day','Procession'],'Mahr/Gifts','Gold mahr and gifts',null],
    ['Makua (Comoros)','Comoros','East Africa',['Matrilineal African blends'],'Goods','Household goods',null],

    // Djibouti
    ['Somali/Issa (Djibouti)','Djibouti','East Africa',['Wedding chanting','Qat chewing','Polygyny allowed'],'Mahr','Cash mahr',null],
    ['Afar (Djibouti)','Djibouti','East Africa',['Nomadic veiling','Desert rites'],'Livestock','Camels and goats',null],

    // Eritrea
    ['Tigrinya (Eritrea)','Eritrea','East Africa',['Arranged early','Church/mosque','Unique dances'],'Livestock/Cash','Cattle and money',null],
    ['Tigre (Eritrea)','Eritrea','East Africa',['Female consent','Traditional ceremony'],'Cattle','Cattle transfers',null],
    ['Afar (Eritrea)','Eritrea','East Africa',['Elders propose','Three ways'],'Camels','Camel dowry',null],

    // Eswatini (often classed Southern; included per request)
    ['Swazi','Eswatini','East Africa',['Lobola','Umemulo','Polygynous options'],'Cattle/Money','Cows and cash',null],

    // Ethiopia
    ['Oromo','Ethiopia','East Africa',['Gada proposal','Arfan ritual','Feasts'],'Goods','Cloth and animals',null],
    ['Amhara','Ethiopia','East Africa',['Elders propose','Church monogamy','Kal kidan'],'Cash/Goods','Cash and gifts',null],
    ['Somali (Ethiopia)','Ethiopia','East Africa',['Islamic nikah','Endogamy'],'Mahr','Gold and money',null],

    // Kenya
    ['Kikuyu','Kenya','East Africa',['Ruracio','Goat slaughter','Family meetings'],'Goats/Cash','Goats and cash',null],
    ['Luhya','Kenya','East Africa',['Clan-based','Communal feasts'],'Livestock','Cows and goats',null],
    ['Kalenjin','Kenya','East Africa',['Koito proposal','Girls preparation'],'Cattle','Cattle payments',null],
    ['Luo','Kenya','East Africa',['Ayie introduction','Endogamy'],'Cattle','Cattle wealth',null],
    ['Maasai (Kenya)','Kenya','East Africa',['Warrior dances','Cow thigh ritual'],'Cattle','Cattle transfers',null],

    // Madagascar
    ['Merina','Madagascar','East Africa',['Vodi-ondry (sheep rump gift)','Arranged multi-day'],'Sheep/Goods','Sheep and gifts',null],
    ['Betsimisaraka','Madagascar','East Africa',['Coastal; family involvement'],'Livestock','Goats/cattle',null],
    ['Betsileo','Madagascar','East Africa',['Highland arranged','Cow sacrifice'],'Cattle','Cow sacrifice and transfers',null],

    // Malawi (UN geoscheme places Malawi in Eastern Africa)
    ['Chewa','Malawi','East Africa',['Chilanga mulilo food test','Matrilineal'],'Lobola','Cows as lobola',null],
    ['Lomwe','Malawi','East Africa',['Chitengwa communal'],'Livestock','Livestock payments',null],
    ['Yao (Malawi)','Malawi','East Africa',['Chikamwini','Islamic influence'],'Cash','Monetary dowry',null],

    // Rwanda
    ['Hutu (Rwanda)','Rwanda','East Africa',['Gufata irembo','Gusaba','Three ceremonies'],'Cattle','Cows as bride wealth',null],
    ['Tutsi (Rwanda)','Rwanda','East Africa',['Pastoral alliances'],'Cattle','Cattle transfers',null],
    ['Twa (Rwanda)','Rwanda','East Africa',['Community sharing'],'Goods','Shared resources',null],

    // Somalia
    ['Somali (Somalia)','Somalia','East Africa',['Clan endogamy','Gold adornment','Veiling'],'Mahr','Gold and money',null],
    ['Bantu (Somalia)','Somalia','East Africa',['Agricultural alliances'],'Livestock','Cattle/goats',null],

    // Tanzania
    ['Sukuma','Tanzania','East Africa',['Dowry negotiations','Vibrant attire'],'Cash/Livestock','Cash and animals',null],
    ['Chagga','Tanzania','East Africa',['Beer offerings','Kilimanjaro rituals'],'Beer/Cash','Beer and cash',null],
    ['Maasai (Tanzania)','Tanzania','East Africa',['Warrior dances','Bride handover'],'Cattle','Cattle transfers',null],

    // Uganda
    ['Baganda','Uganda','East Africa',['Kwanjula','Senga aunt','Kukyala'],'Money/Drinks/Cloth','Cash, drinks and cloth',null],
    ['Banyankole','Uganda','East Africa',['Kuhingira giveaway','Introduction'],'Cattle','Cows as bride wealth',null],
    ['Basoga','Uganda','East Africa',['Clan exogamy','Similar to Baganda'],'Livestock/Cash','Animals and cash',null],
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
echo json_encode(['success'=>true,'message'=>'East Africa seed complete','added'=>$added]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Error: ' . $e->getMessage()]);
}
?>


