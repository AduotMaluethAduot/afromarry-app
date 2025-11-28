<?php
// Seed West Africa tribes into the database
require_once '../config/database.php';

// Require customer authentication
requireAuth();

try {
    $db = (new Database())->getConnection();

// Entries: name, country, region, customs(array), dowry_type, dowry_details, image(optional)
$tribes = [
    // Benin
    ['Fon','Benin','West Africa',['Tasting four elements','Family consents','Fufu mashing'],'Cloth/Drinks/Money','Cloth, drinks and monetary gifts',null],
    ['Adja','Benin','West Africa',['Seafood offerings','Clan approvals'],'Seafood/Cash','Seafood gifts and cash',null],
    ['Yoruba (Benin)','Benin','West Africa',['Aso-ebi attire','Tasting elements'],'Cash/Cloth','Cash and cloth parcels',null],
    ['Bariba','Benin','West Africa',['Islamic influences','Livestock dowry'],'Goats/Yams','Goats and yam contributions',null],
    ['Fulani (Benin)','Benin','West Africa',['Cattle alliances','Polygyny possible'],'Cattle','Cattle bride wealth',null],
    ['Ottamari','Benin','West Africa',['Communal feasts'],'Goods','Rice and cloth gifts',null],
    ['Dendi','Benin','West Africa',['Riverine rituals'],'Fish/Cash','Fish offerings and cash',null],
    ['Somba','Benin','West Africa',['Animist bride price'],'Goods','Tools and animals',null],

    // Burkina Faso
    ['Mossi','Burkina Faso','West Africa',['Bãng-buudu presentation','Younger brother drink','Polygyny allowed'],'Livestock/Cash','Cattle and cash bride wealth',null],
    ['Fulani (Burkina)','Burkina Faso','West Africa',['Cousin marriages','Nomadic cattle exchanges'],'Cattle','Cattle transfers',null],
    ['Gurma','Burkina Faso','West Africa',['Matrilineal elements','Negotiated bride price'],'Livestock/Cash','Livestock and cash',null],
    ['Bobo (Burkina)','Burkina Faso','West Africa',['Joking relationships','Communal feasts'],'Goods','Cloth and tools',null],
    ['Gurunsi','Burkina Faso','West Africa',['Animist rites'],'Rice/Goods','Rice and gifts',null],
    ['Senufo (Burkina)','Burkina Faso','West Africa',['Mask dances'],'Goods','Cloth and tools',null],

    // Cape Verde
    ['Creole/Mulatto','Cape Verde','West Africa',['Catholic ceremonies','Pomp and circumstance'],'Money/Gifts','Monetary gifts and presents',null],
    ['African (Cape Verde)','Cape Verde','West Africa',['Traditional blends','Community feasts'],'Goods/Shared','Shared resources and goods',null],
    ['Portuguese/European','Cape Verde','West Africa',['Western-Christian church rites'],'Gold/Cash','Gold and cash',null],

    // Côte d\'Ivoire
    ["Akan/Baoulé","Côte d'Ivoire","West Africa",['Knocking ceremony','Fufu mashing','Family arranged'],'Cloth/Drinks/Money','Cloth, drinks and money',null],
    ['Bété','Côte d\'Ivoire','West Africa',['Clan exogamy','Communal feasts'],'Livestock/Cash','Livestock and cash',null],
    ['Senufo (CIV)','Côte d\'Ivoire','West Africa',['Mask dances','Introduction rites'],'Cash/Livestock','Cash and animals',null],
    ['Mandé (CIV)','Côte d\'Ivoire','West Africa',['Kin-based marriages','Polygyny'],'Money/Goods','Cash and gifts',null],
    ['Krou','Côte d\'Ivoire','West Africa',['Coastal rituals'],'Goods','Cloth and beads',null],

    // Gambia
    ['Mandinka (Gambia)','Gambia','West Africa',['Islamic walima','Segregated celebrations','Arranged unions'],'Mahr','Money and livestock as mahr',null],
    ['Fula (Gambia)','Gambia','West Africa',['Cattle alliances','Polygyny'],'Cattle','Cattle bride wealth',null],
    ['Wolof (Gambia)','Gambia','West Africa',['Endogamous cousin marriages','Lavish feasts'],'Mahr (Gold)','Gold mahr',null],
    ['Jola (Gambia)','Gambia','West Africa',['Rice-based customs','Wrestling rituals'],'Rice/Goods','Rice and gifts',null],

    // Ghana
    ['Akan (Ghana)','Ghana','West Africa',['Knocking ceremony','Fufu mashing','Consent three times'],'Cloth/Drinks/Money','Cloth, drinks and money',null],
    ['Mole-Dagbani','Ghana','West Africa',['Livestock dowry','Islamic influences'],'Goats/Yams','Goats and yams',null],
    ['Ewe (Ghana)','Ghana','West Africa',['Kola nuts','Family introductions'],'Kola/Drinks','Kola nuts and drinks',null],
    ['Ga-Adangbe','Ghana','West Africa',['Seafood offerings','Clan approvals'],'Seafood/Cash','Seafood and cash',null],

    // Guinea
    ['Fulani/Peul (Guinea)','Guinea','West Africa',['Cousin marriages','Nomadic cattle','Polygyny'],'Cattle','Cattle transfers',null],
    ['Malinke (Guinea)','Guinea','West Africa',['Arranged by elders','Farmer rites'],'Cash/Yams','Cash and yams',null],
    ['Susu','Guinea','West Africa',['Polygynous norms','Occupation-based roles'],'Goods','Cloth and gifts',null],

    // Guinea-Bissau
    ['Balanta','Guinea-Bissau','West Africa',['Kwâssi cleansing','B-Bâsti pregnancy rite','Matriarchal traits'],'Livestock','Cattle and goats',null],
    ['Fula (Guinea-Bissau)','Guinea-Bissau','West Africa',['Arranged with bride price','Groom service'],'Cattle','Cattle dowry',null],
    ['Manjaco','Guinea-Bissau','West Africa',['Coastal inter-ethnic unions'],'Goods','Rice and goods',null],
    ['Mandinka (GB)','Guinea-Bissau','West Africa',['Islamic; endogamous'],'Mahr','Money mahr',null],

    // Liberia
    ['Kpelle (Liberia)','Liberia','West Africa',['Patrilocal; polygyny','Dowry presentation'],'Money/Goods','Cash and gifts',null],
    ['Bassa (Liberia)','Liberia','West Africa',['Secret societies','Rice and cash'],'Rice/Cash','Rice with cash',null],
    ['Grebo','Liberia','West Africa',['Clan-based feasts'],'Livestock','Animal wealth',null],

    // Mali
    ['Bambara (Mali)','Mali','West Africa',['Kin alliances','Monetized gifts','Bazin attire'],'Money/Goods','Cash and gifts',null],
    ['Fulani (Mali)','Mali','West Africa',['Polygyny','Nomadic cattle'],'Cattle','Cattle transfers',null],
    ['Senufo (Mali)','Mali','West Africa',['Mask dances'],'Goods','Cloth and tools',null],
    ['Songhai (Mali)','Mali','West Africa',['Islamic contracts','Feasts'],'Mahr','Money mahr',null],

    // Niger
    ['Hausa (Niger)','Niger','West Africa',['Islamic walima','Segregated celebrations','Dancing camels'],'Mahr','Money and livestock',null],
    ['Zarma-Songhai','Niger','West Africa',['Riverine arranged unions'],'Goods','Cloth and gifts',null],
    ['Fulani (Niger)','Niger','West Africa',['Sharo (flogging)','Cattle alliances'],'Cattle','Cattle dowry',null],
    ['Tuareg (Niger)','Niger','West Africa',['Matrilineal veiling','Nomadic'],'Camels','Camels and goods',null],

    // Nigeria
    ['Hausa (Nigeria)','Nigeria','West Africa',['Islamic walima','Sharo flogging'],'Mahr','Money and livestock',null],
    ['Yoruba (Nigeria)','Nigeria','West Africa',['Tasting four elements','Aso-ebi','Family consents'],'Cash/Cloth','Cash and cloth',null],
    ['Igbo','Nigeria','West Africa',['Igba nkwu (wine carrying)','High bride price'],'Cash/Yams','Cash and yam gifts',null],
    ['Ijaw','Nigeria','West Africa',['Riverine canoes','Dances'],'Goods','Fish and cloth',null],

    // Senegal
    ['Wolof (Senegal)','Senegal','West Africa',['Endogamous cousin marriages','Lavish feasts'],'Mahr','Money and gold mahr',null],
    ['Fula (Senegal)','Senegal','West Africa',['Cattle herding','Polygyny'],'Cattle','Cattle payments',null],
    ['Serer','Senegal','West Africa',['Matrilineal elements','Bride price'],'Livestock/Cash','Animals and cash',null],
    ['Jola (Senegal)','Senegal','West Africa',['Rice-based customs','Wrestling'],'Rice/Goods','Rice and gifts',null],

    // Sierra Leone
    ['Mende','Sierra Leone','West Africa',['Secret societies','Singing and dancing'],'Rice/Cash','Rice and cash',null],
    ['Temne','Sierra Leone','West Africa',['Family negotiations','Islamic/animist blend'],'Cash/Livestock','Cash and animals',null],
    ['Krio (Sierra Leone)','Sierra Leone','West Africa',['Western church with African elements'],'Money/Gifts','Cash and presents',null],

    // Togo
    ['Ewe/Mina (Togo)','Togo','West Africa',['Kola nuts','Family introductions','Civil/Christian/traditional'],'Kola/Drinks','Kola nuts and drinks',null],
    ['Kabye/Tem','Togo','West Africa',['Livestock; family arranged'],'Goats/Cash','Goats with cash',null],
    ['Akan/Gurma (Togo)','Togo','West Africa',['Knocking; fufu mashing'],'Cloth/Money','Cloth and money',null],
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
echo json_encode(['success'=>true,'message'=>'West Africa seed complete','added'=>$added]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Error: ' . $e->getMessage()]);
}
?>


