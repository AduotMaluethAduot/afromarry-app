<?php
// Seed Southern Africa tribes into the database
require_once '../config/database.php';

// Require customer authentication
requireAuth();

try {
    $db = (new Database())->getConnection();

// Entries: name, country, region, customs(array), dowry_type, dowry_details, image(optional)
$tribes = [
    // Botswana
    ['Tswana (Botswana)','Botswana','Southern Africa',['Bogwera/Bogadi initiation','Pre-arranged options','Polygyny possible'],'Cattle (Heifers)','Heifers as bogadi',null],
    ['Kalanga','Botswana','Southern Africa',['Similar Tswana rites','Polygyny'],'Livestock','Cattle/goats',null],
    ['Basarwa/San (Botswana)','Botswana','Southern Africa',['Egalitarian sharing','No formal lobola'],'Shared Goods','Community resource sharing',null],
    ['Herero (Botswana)','Botswana','Southern Africa',['Cattle economy','Victorian dresses'],'Cattle','Cattle exchanges',null],
    ['Wayeyi','Botswana','Southern Africa',['Matrilineal patterns'],'Livestock','Livestock transfers',null],

    // Lesotho
    ['Basotho','Lesotho','Southern Africa',['Mahlabiso celebration','Lobola','Polygynous elite'],'Cattle','Cow bride wealth',null],
    ['Nguni (Lesotho)','Lesotho','Southern Africa',['Zulu influence'],'Livestock','Cattle/goats',null],
    ['Xhosa (Lesotho)','Lesotho','Southern Africa',['Ukutwala customs'],'Cash','Negotiated cash gifts',null],

    // Mauritius
    ['Indo-Mauritians/Hindus','Mauritius','Southern Africa',['Vivaha fire ritual','Endogamous; kin-organized'],'Dowry (Goods)','Household goods and gold',null],
    ['Creoles (Mauritius)','Mauritius','Southern Africa',['Catholic ceremonies','Inter-ethnic unions'],'Money/Gifts','Cash and presents',null],
    ['Indo-Mauritians/Muslims','Mauritius','Southern Africa',['Nikah','Gender-segregated'],'Mahr','Islamic money mahr',null],

    // Mozambique
    ['Makua (Mozambique)','Mozambique','Southern Africa',['Matrilineal differentials'],'Livestock','Cattle/goats',null],
    ['Tsonga (Mozambique)','Mozambique','Southern Africa',['Patrilineal; lobola'],'Cattle','Cattle transfers',null],
    ['Lomwe (Mozambique)','Mozambique','Southern Africa',['Communal; inter-ethnic unions'],'Goods','Household goods',null],
    ['Sena (Mozambique)','Mozambique','Southern Africa',['Riverine patterns'],'Cash','Monetary bride price',null],
    ['Yao (Mozambique)','Mozambique','Southern Africa',['Islamic; arranged'],'Mahr','Islamic mahr',null],

    // Namibia
    ['Ovambo (Namibia)','Namibia','Southern Africa',['Lobola','Family consents'],'Cattle','Cattle wealth',null],
    ['Kavango','Namibia','Southern Africa',['River feasts'],'Livestock','Cattle/goats',null],
    ['Herero (Namibia)','Namibia','Southern Africa',['Cattle economy','Victorian dresses'],'Cattle','Cattle exchanges',null],
    ['Himba (Namibia)','Namibia','Southern Africa',['Ochre body paint','Arranged polygamy'],'Livestock','Goats/cattle',null],
    ['San (Namibia)','Namibia','Southern Africa',['Hunter-gatherer','No formal lobola'],'Shared','Shared resources',null],

    // Seychelles
    ['Seychellois/Creole','Seychelles','Southern Africa',['Sega drums','Tropical flowers','Matriarchal; civil/religious'],'Money/Gifts','Cash and gifts',null],
    ['Indian (Seychelles)','Seychelles','Southern Africa',['Hindu rites; endogamous'],'Dowry','Dowry goods',null],

    // South Africa
    ['Zulu (South Africa)','South Africa','Southern Africa',['Lobola','Umemulo','Polygyny allowed'],'Cattle/Money','Cows and cash',null],
    ['Xhosa (South Africa)','South Africa','Southern Africa',['Ukutwala; negotiations'],'Livestock/Cash','Cattle and money',null],
    ['Sotho (South Africa)','South Africa','Southern Africa',['Initiation; lobola'],'Cattle','Cattle bride wealth',null],
    ['Tswana (South Africa)','South Africa','Southern Africa',['Bogadi practice'],'Heifers','Heifers for bogadi',null],
    ['Venda (South Africa)','South Africa','Southern Africa',['Domba dance'],'Livestock','Cattle/goats',null],

    // Zambia
    ['Bemba','Zambia','Southern Africa',['Ichilanga mulilo cooking test','Multi-stage process'],'Cash/Goods','Cash and gifts',null],
    ['Tonga (Zambia)','Zambia','Southern Africa',['Matrilineal emphasis'],'Cattle','Cattle wealth',null],
    ['Nyanja (Zambia)','Zambia','Southern Africa',['Urban statutory/customary mix'],'Money/Livestock','Cash and animals',null],
    ['Lozi (Zambia)','Zambia','Southern Africa',['River negotiations'],'Goods','Cattle and cloth',null],

    // Zimbabwe
    ['Shona','Zimbabwe','Southern Africa',['Roora; kukundikana test','White wedding after'],'Cattle/Cash','Cattle and money',null],
    ['Ndebele (Zimbabwe)','Zimbabwe','Southern Africa',['Lobola; house paintings'],'Cattle','Cows as lobola',null],
    ['Tonga (Zimbabwe)','Zimbabwe','Southern Africa',['Matrilineal lines'],'Cattle','Cattle transfers',null],
    ['Venda (Zimbabwe)','Zimbabwe','Southern Africa',['Domba rites'],'Goods','Household goods',null],
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
echo json_encode(['success'=>true,'message'=>'Southern Africa seed complete','added'=>$added]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Error: ' . $e->getMessage()]);
}
?>


