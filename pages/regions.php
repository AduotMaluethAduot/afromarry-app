<?php
require_once '../config/database.php';

// Free platform - regions page is accessible to everyone
// No authentication required for browsing tribes
$user = getCurrentUser(); // Get user if logged in, but don't require it
$database = new Database();
$db = $database->getConnection();

// Get all tribes grouped by region and country
$query = "SELECT * FROM tribes ORDER BY region, country, name ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$allTribes = $stmt->fetchAll();

// Decode customs JSON
foreach ($allTribes as &$tribe) {
    $tribe['customs'] = json_decode($tribe['customs'], true) ?: [];
}

// Group tribes by region and country
$regionsData = [];
foreach ($allTribes as $tribe) {
    $region = $tribe['region'] ?: 'Other';
    $country = $tribe['country'] ?: 'Unknown';
    
    if (!isset($regionsData[$region])) {
        $regionsData[$region] = [];
    }
    
    if (!isset($regionsData[$region][$country])) {
        $regionsData[$region][$country] = [];
    }
    
    $regionsData[$region][$country][] = $tribe;
}

// Helper function to convert seed data format to tribe array
function convertSeedToTribe($tribeArray, $counter) {
    [$name, $country, $region, $customs, $dowryType, $dowryDetails, $image] = $tribeArray;
    $regionCode = strtolower(substr(str_replace(' ', '', $region), 0, 2));
    return [
        'id' => 'seed_' . $regionCode . '_' . $counter,
        'name' => $name,
        'country' => $country,
        'region' => $region,
        'customs' => is_array($customs) ? $customs : [],
        'dowry_type' => $dowryType,
        'dowry_details' => $dowryDetails,
        'image' => $image
    ];
}

// All tribes from seed files (complete data from all regions)
$allSeedTribes = [];
$counter = 1;

// Central Africa tribes
$centralAfricaTribes = [
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
    ['Gbaya','Central African Republic','Central Africa',['Ngbwa ritual','Arranged lineage links'], 'Goods', 'Cloth and money gifts', null],
    ['Banda','Central African Republic','Central Africa',['High child marriage rate historically'], 'Livestock', 'Livestock payments', null],
    ['Mandja (CAR)','Central African Republic','Central Africa',['Animist feasts'], 'Cash/Animals', 'Cash and animals', null],
    ['Zande (CAR)','Central African Republic','Central Africa',['State-forming alliances'], 'Livestock', 'Cattle/goats', null],
    ['Aka/Pygmy (CAR)','Central African Republic','Central Africa',['Hunter-gatherer sharing'], 'Shared', 'Resource sharing', null],
    ['Sara (CAR)','Central African Republic','Central Africa',['Consent-based unions'], 'Livestock', 'Cattle/goats', null],
    ['Sara (Chad)','Chad','Central Africa',['Consent-based','French code influences'], 'Livestock', 'Cattle/goats', null],
    ['Arab (Chad)','Chad','Central Africa',['Islamic mahr','Gender segregation'], 'Mahr', 'Money as mahr', null],
    ['Kanembu','Chad','Central Africa',['Livestock transfers'], 'Livestock', 'Cows', null],
    ['Ouaddai','Chad','Central Africa',['Tribal negotiations'], 'Cash/Goods', 'Cash and gifts', null],
    ['Bagirmi','Chad','Central Africa',['River customs'], 'Goods', 'Cloth and goods', null],
    ['Hadjerai','Chad','Central Africa',['Highland traditions'], 'Livestock', 'Goats', null],
    ['Zaghawa','Chad','Central Africa',['Nomadic camel culture'], 'Camels', 'Camel transfers', null],
    ['Kanuri (Chad)','Chad','Central Africa',['Islamic nikah'], 'Mahr', 'Money per Islamic law', null],
    ['Luba','Congo, Democratic Republic of the','Central Africa',['Polygamy with first wife pre-eminent','Bride wealth'], 'Livestock/Money', 'Cattle and money', null],
    ['Kongo (DRC)','Congo, Democratic Republic of the','Central Africa',['Matrilineal elements','Festive dances'], 'Goods', 'Cloth and cash', null],
    ['Mongo','Congo, Democratic Republic of the','Central Africa',['Clan alliances'], 'Livestock', 'Cattle/goats', null],
    ['Rundi','Congo, Democratic Republic of the','Central Africa',['Urban blends'], 'Cash/Goods', 'Cash and goods', null],
    ['Lunda (DRC)','Congo, Democratic Republic of the','Central Africa',['Initiation'], 'Goods', 'Symbolic goods', null],
    ['Bakongo (RoC)','Congo, Republic of the','Central Africa',['Matrilineal','Festive dances'], 'Goods', 'Cloth and cash', null],
    ['Bateke','Congo, Republic of the','Central Africa',['River negotiations'], 'Livestock', 'Cattle/goats', null],
    ['M\'Bochi','Congo, Republic of the','Central Africa',['Clan structures'], 'Money', 'Cash transfers', null],
    ['Sangha','Congo, Republic of the','Central Africa',['Forest rites'], 'Goods', 'Household goods', null],
    ['Babongo/Pygmy (RoC)','Congo, Republic of the','Central Africa',['Sharing economy'], 'Shared', 'Resources shared', null],
    ['Fang (EG)','Equatorial Guinea','Central Africa',['Dowry and groom service','Polygyny common'], 'Livestock/Cash', 'Cattle with cash gifts', null],
    ['Bubi','Equatorial Guinea','Central Africa',['Island rituals'], 'Goods', 'Gifts and goods', null],
    ['Ndowe','Equatorial Guinea','Central Africa',['Coastal traditions'], 'Cash', 'Cash and gifts', null],
    ['Fang (Gabon)','Gabon','Central Africa',['Inter-ethnic common','Arranged options'], 'Livestock/Cash', 'Cattle and cash', null],
    ['Myene','Gabon','Central Africa',['Coastal religious/customary mix'], 'Goods', 'Household goods', null],
    ['Bateke (Gabon)','Gabon','Central Africa',['River customs'], 'Livestock', 'Goats/cattle', null],
    ['Forros/Creole','São Tomé and Príncipe','Central Africa',['Catholic with ethnic rituals','Monogamy common'], 'Money/Gifts', 'Cash and gifts', null],
    ['Angolares','São Tomé and Príncipe','Central Africa',['Communal island customs'], 'Goods', 'Household goods', null],
    ['Tonga (STP)','São Tomé and Príncipe','Central Africa',['Feasts and gatherings'], 'Livestock', 'Goats', null],
];

// East Africa tribes
$eastAfricaTribes = [
    ['Hutu (Burundi)','Burundi','East Africa',['Gufata irembo','Gusaba','Three ceremonies'],'Cattle','Cows as bride wealth',null],
    ['Tutsi (Burundi)','Burundi','East Africa',['Pastoral alliances','Cattle emphasis'],'Cattle','Cattle transfers',null],
    ['Twa (Burundi)','Burundi','East Africa',['Forest-based','Community sharing'],'Goods','Shared resources',null],
    ['Comorians','Comoros','East Africa',['Grand marriage (Anda)','Djaliko day','Procession'],'Mahr/Gifts','Gold mahr and gifts',null],
    ['Makua (Comoros)','Comoros','East Africa',['Matrilineal African blends'],'Goods','Household goods',null],
    ['Somali/Issa (Djibouti)','Djibouti','East Africa',['Wedding chanting','Qat chewing','Polygyny allowed'],'Mahr','Cash mahr',null],
    ['Afar (Djibouti)','Djibouti','East Africa',['Nomadic veiling','Desert rites'],'Livestock','Camels and goats',null],
    ['Tigrinya (Eritrea)','Eritrea','East Africa',['Arranged early','Church/mosque','Unique dances'],'Livestock/Cash','Cattle and money',null],
    ['Tigre (Eritrea)','Eritrea','East Africa',['Female consent','Traditional ceremony'],'Cattle','Cattle transfers',null],
    ['Afar (Eritrea)','Eritrea','East Africa',['Elders propose','Three ways'],'Camels','Camel dowry',null],
    ['Swazi','Eswatini','East Africa',['Lobola','Umemulo','Polygynous options'],'Cattle/Money','Cows and cash',null],
    ['Oromo','Ethiopia','East Africa',['Gada proposal','Arfan ritual','Feasts'],'Goods','Cloth and animals',null],
    ['Amhara','Ethiopia','East Africa',['Elders propose','Church monogamy','Kal kidan'],'Cash/Goods','Cash and gifts',null],
    ['Somali (Ethiopia)','Ethiopia','East Africa',['Islamic nikah','Endogamy'],'Mahr','Gold and money',null],
    ['Kikuyu','Kenya','East Africa',['Ruracio','Goat slaughter','Family meetings'],'Goats/Cash','Goats and cash',null],
    ['Luhya','Kenya','East Africa',['Clan-based','Communal feasts'],'Livestock','Cows and goats',null],
    ['Kalenjin','Kenya','East Africa',['Koito proposal','Girls preparation'],'Cattle','Cattle payments',null],
    ['Luo','Kenya','East Africa',['Ayie introduction','Endogamy'],'Cattle','Cattle wealth',null],
    ['Maasai (Kenya)','Kenya','East Africa',['Warrior dances','Cow thigh ritual'],'Cattle','Cattle transfers',null],
    ['Merina','Madagascar','East Africa',['Vodi-ondry (sheep rump gift)','Arranged multi-day'],'Sheep/Goods','Sheep and gifts',null],
    ['Betsimisaraka','Madagascar','East Africa',['Coastal; family involvement'],'Livestock','Goats/cattle',null],
    ['Betsileo','Madagascar','East Africa',['Highland arranged','Cow sacrifice'],'Cattle','Cow sacrifice and transfers',null],
    ['Chewa','Malawi','East Africa',['Chilanga mulilo food test','Matrilineal'],'Lobola','Cows as lobola',null],
    ['Lomwe','Malawi','East Africa',['Chitengwa communal'],'Livestock','Livestock payments',null],
    ['Yao (Malawi)','Malawi','East Africa',['Chikamwini','Islamic influence'],'Cash','Monetary dowry',null],
    ['Hutu (Rwanda)','Rwanda','East Africa',['Gufata irembo','Gusaba','Three ceremonies'],'Cattle','Cows as bride wealth',null],
    ['Tutsi (Rwanda)','Rwanda','East Africa',['Pastoral alliances'],'Cattle','Cattle transfers',null],
    ['Twa (Rwanda)','Rwanda','East Africa',['Community sharing'],'Goods','Shared resources',null],
    ['Somali (Somalia)','Somalia','East Africa',['Clan endogamy','Gold adornment','Veiling'],'Mahr','Gold and money',null],
    ['Bantu (Somalia)','Somalia','East Africa',['Agricultural alliances'],'Livestock','Cattle/goats',null],
    ['Sukuma','Tanzania','East Africa',['Dowry negotiations','Vibrant attire'],'Cash/Livestock','Cash and animals',null],
    ['Chagga','Tanzania','East Africa',['Beer offerings','Kilimanjaro rituals'],'Beer/Cash','Beer and cash',null],
    ['Maasai (Tanzania)','Tanzania','East Africa',['Warrior dances','Bride handover'],'Cattle','Cattle transfers',null],
    ['Baganda','Uganda','East Africa',['Kwanjula','Senga aunt','Kukyala'],'Money/Drinks/Cloth','Cash, drinks and cloth',null],
    ['Banyankole','Uganda','East Africa',['Kuhingira giveaway','Introduction'],'Cattle','Cows as bride wealth',null],
    ['Basoga','Uganda','East Africa',['Clan exogamy','Similar to Baganda'],'Livestock/Cash','Animals and cash',null],
];

// West Africa tribes
$westAfricaTribes = [
    ['Fon','Benin','West Africa',['Tasting four elements','Family consents','Fufu mashing'],'Cloth/Drinks/Money','Cloth, drinks and monetary gifts',null],
    ['Adja','Benin','West Africa',['Seafood offerings','Clan approvals'],'Seafood/Cash','Seafood gifts and cash',null],
    ['Yoruba (Benin)','Benin','West Africa',['Aso-ebi attire','Tasting elements'],'Cash/Cloth','Cash and cloth parcels',null],
    ['Bariba','Benin','West Africa',['Islamic influences','Livestock dowry'],'Goats/Yams','Goats and yam contributions',null],
    ['Fulani (Benin)','Benin','West Africa',['Cattle alliances','Polygyny possible'],'Cattle','Cattle bride wealth',null],
    ['Ottamari','Benin','West Africa',['Communal feasts'],'Goods','Rice and cloth gifts',null],
    ['Dendi','Benin','West Africa',['Riverine rituals'],'Fish/Cash','Fish offerings and cash',null],
    ['Somba','Benin','West Africa',['Animist bride price'],'Goods','Tools and animals',null],
    ['Mossi','Burkina Faso','West Africa',['Bãng-buudu presentation','Younger brother drink','Polygyny allowed'],'Livestock/Cash','Cattle and cash bride wealth',null],
    ['Fulani (Burkina)','Burkina Faso','West Africa',['Cousin marriages','Nomadic cattle exchanges'],'Cattle','Cattle transfers',null],
    ['Gurma','Burkina Faso','West Africa',['Matrilineal elements','Negotiated bride price'],'Livestock/Cash','Livestock and cash',null],
    ['Bobo (Burkina)','Burkina Faso','West Africa',['Joking relationships','Communal feasts'],'Goods','Cloth and tools',null],
    ['Gurunsi','Burkina Faso','West Africa',['Animist rites'],'Rice/Goods','Rice and gifts',null],
    ['Senufo (Burkina)','Burkina Faso','West Africa',['Mask dances'],'Goods','Cloth and tools',null],
    ['Creole/Mulatto','Cape Verde','West Africa',['Catholic ceremonies','Pomp and circumstance'],'Money/Gifts','Monetary gifts and presents',null],
    ['African (Cape Verde)','Cape Verde','West Africa',['Traditional blends','Community feasts'],'Goods/Shared','Shared resources and goods',null],
    ['Portuguese/European','Cape Verde','West Africa',['Western-Christian church rites'],'Gold/Cash','Gold and cash',null],
    ["Akan/Baoulé","Côte d'Ivoire","West Africa",['Knocking ceremony','Fufu mashing','Family arranged'],'Cloth/Drinks/Money','Cloth, drinks and money',null],
    ['Bété','Côte d\'Ivoire','West Africa',['Clan exogamy','Communal feasts'],'Livestock/Cash','Livestock and cash',null],
    ['Senufo (CIV)','Côte d\'Ivoire','West Africa',['Mask dances','Introduction rites'],'Cash/Livestock','Cash and animals',null],
    ['Mandé (CIV)','Côte d\'Ivoire','West Africa',['Kin-based marriages','Polygyny'],'Money/Goods','Cash and gifts',null],
    ['Krou','Côte d\'Ivoire','West Africa',['Coastal rituals'],'Goods','Cloth and beads',null],
    ['Mandinka (Gambia)','Gambia','West Africa',['Islamic walima','Segregated celebrations','Arranged unions'],'Mahr','Money and livestock as mahr',null],
    ['Fula (Gambia)','Gambia','West Africa',['Cattle alliances','Polygyny'],'Cattle','Cattle bride wealth',null],
    ['Wolof (Gambia)','Gambia','West Africa',['Endogamous cousin marriages','Lavish feasts'],'Mahr (Gold)','Gold mahr',null],
    ['Jola (Gambia)','Gambia','West Africa',['Rice-based customs','Wrestling rituals'],'Rice/Goods','Rice and gifts',null],
    ['Akan (Ghana)','Ghana','West Africa',['Knocking ceremony','Fufu mashing','Consent three times'],'Cloth/Drinks/Money','Cloth, drinks and money',null],
    ['Mole-Dagbani','Ghana','West Africa',['Livestock dowry','Islamic influences'],'Goats/Yams','Goats and yams',null],
    ['Ewe (Ghana)','Ghana','West Africa',['Kola nuts','Family introductions'],'Kola/Drinks','Kola nuts and drinks',null],
    ['Ga-Adangbe','Ghana','West Africa',['Seafood offerings','Clan approvals'],'Seafood/Cash','Seafood and cash',null],
    ['Fulani/Peul (Guinea)','Guinea','West Africa',['Cousin marriages','Nomadic cattle','Polygyny'],'Cattle','Cattle transfers',null],
    ['Malinke (Guinea)','Guinea','West Africa',['Arranged by elders','Farmer rites'],'Cash/Yams','Cash and yams',null],
    ['Susu','Guinea','West Africa',['Polygynous norms','Occupation-based roles'],'Goods','Cloth and gifts',null],
    ['Balanta','Guinea-Bissau','West Africa',['Kwâssi cleansing','B-Bâsti pregnancy rite','Matriarchal traits'],'Livestock','Cattle and goats',null],
    ['Fula (Guinea-Bissau)','Guinea-Bissau','West Africa',['Arranged with bride price','Groom service'],'Cattle','Cattle dowry',null],
    ['Manjaco','Guinea-Bissau','West Africa',['Coastal inter-ethnic unions'],'Goods','Rice and goods',null],
    ['Mandinka (GB)','Guinea-Bissau','West Africa',['Islamic; endogamous'],'Mahr','Money mahr',null],
    ['Kpelle (Liberia)','Liberia','West Africa',['Patrilocal; polygyny','Dowry presentation'],'Money/Goods','Cash and gifts',null],
    ['Bassa (Liberia)','Liberia','West Africa',['Secret societies','Rice and cash'],'Rice/Cash','Rice with cash',null],
    ['Grebo','Liberia','West Africa',['Clan-based feasts'],'Livestock','Animal wealth',null],
    ['Bambara (Mali)','Mali','West Africa',['Kin alliances','Monetized gifts','Bazin attire'],'Money/Goods','Cash and gifts',null],
    ['Fulani (Mali)','Mali','West Africa',['Polygyny','Nomadic cattle'],'Cattle','Cattle transfers',null],
    ['Senufo (Mali)','Mali','West Africa',['Mask dances'],'Goods','Cloth and tools',null],
    ['Songhai (Mali)','Mali','West Africa',['Islamic contracts','Feasts'],'Mahr','Money mahr',null],
    ['Hausa (Niger)','Niger','West Africa',['Islamic walima','Segregated celebrations','Dancing camels'],'Mahr','Money and livestock',null],
    ['Zarma-Songhai','Niger','West Africa',['Riverine arranged unions'],'Goods','Cloth and gifts',null],
    ['Fulani (Niger)','Niger','West Africa',['Sharo (flogging)','Cattle alliances'],'Cattle','Cattle dowry',null],
    ['Tuareg (Niger)','Niger','West Africa',['Matrilineal veiling','Nomadic'],'Camels','Camels and goods',null],
    ['Hausa (Nigeria)','Nigeria','West Africa',['Islamic walima','Sharo flogging'],'Mahr','Money and livestock',null],
    ['Yoruba (Nigeria)','Nigeria','West Africa',['Tasting four elements','Aso-ebi','Family consents'],'Cash/Cloth','Cash and cloth',null],
    ['Igbo','Nigeria','West Africa',['Igba nkwu (wine carrying)','High bride price'],'Cash/Yams','Cash and yam gifts',null],
    ['Ijaw','Nigeria','West Africa',['Riverine canoes','Dances'],'Goods','Fish and cloth',null],
    ['Wolof (Senegal)','Senegal','West Africa',['Endogamous cousin marriages','Lavish feasts'],'Mahr','Money and gold mahr',null],
    ['Fula (Senegal)','Senegal','West Africa',['Cattle herding','Polygyny'],'Cattle','Cattle payments',null],
    ['Serer','Senegal','West Africa',['Matrilineal elements','Bride price'],'Livestock/Cash','Animals and cash',null],
    ['Jola (Senegal)','Senegal','West Africa',['Rice-based customs','Wrestling'],'Rice/Goods','Rice and gifts',null],
    ['Mende','Sierra Leone','West Africa',['Secret societies','Singing and dancing'],'Rice/Cash','Rice and cash',null],
    ['Temne','Sierra Leone','West Africa',['Family negotiations','Islamic/animist blend'],'Cash/Livestock','Cash and animals',null],
    ['Krio (Sierra Leone)','Sierra Leone','West Africa',['Western church with African elements'],'Money/Gifts','Cash and presents',null],
    ['Ewe/Mina (Togo)','Togo','West Africa',['Kola nuts','Family introductions','Civil/Christian/traditional'],'Kola/Drinks','Kola nuts and drinks',null],
    ['Kabye/Tem','Togo','West Africa',['Livestock; family arranged'],'Goats/Cash','Goats with cash',null],
    ['Akan/Gurma (Togo)','Togo','West Africa',['Knocking; fufu mashing'],'Cloth/Money','Cloth and money',null],
];

// Southern Africa tribes
$southernAfricaTribes = [
    ['Tswana (Botswana)','Botswana','Southern Africa',['Bogwera/Bogadi initiation','Pre-arranged options','Polygyny possible'],'Cattle (Heifers)','Heifers as bogadi',null],
    ['Kalanga','Botswana','Southern Africa',['Similar Tswana rites','Polygyny'],'Livestock','Cattle/goats',null],
    ['Basarwa/San (Botswana)','Botswana','Southern Africa',['Egalitarian sharing','No formal lobola'],'Shared Goods','Community resource sharing',null],
    ['Herero (Botswana)','Botswana','Southern Africa',['Cattle economy','Victorian dresses'],'Cattle','Cattle exchanges',null],
    ['Wayeyi','Botswana','Southern Africa',['Matrilineal patterns'],'Livestock','Livestock transfers',null],
    ['Basotho','Lesotho','Southern Africa',['Mahlabiso celebration','Lobola','Polygynous elite'],'Cattle','Cow bride wealth',null],
    ['Nguni (Lesotho)','Lesotho','Southern Africa',['Zulu influence'],'Livestock','Cattle/goats',null],
    ['Xhosa (Lesotho)','Lesotho','Southern Africa',['Ukutwala customs'],'Cash','Negotiated cash gifts',null],
    ['Indo-Mauritians/Hindus','Mauritius','Southern Africa',['Vivaha fire ritual','Endogamous; kin-organized'],'Dowry (Goods)','Household goods and gold',null],
    ['Creoles (Mauritius)','Mauritius','Southern Africa',['Catholic ceremonies','Inter-ethnic unions'],'Money/Gifts','Cash and presents',null],
    ['Indo-Mauritians/Muslims','Mauritius','Southern Africa',['Nikah','Gender-segregated'],'Mahr','Islamic money mahr',null],
    ['Makua (Mozambique)','Mozambique','Southern Africa',['Matrilineal differentials'],'Livestock','Cattle/goats',null],
    ['Tsonga (Mozambique)','Mozambique','Southern Africa',['Patrilineal; lobola'],'Cattle','Cattle transfers',null],
    ['Lomwe (Mozambique)','Mozambique','Southern Africa',['Communal; inter-ethnic unions'],'Goods','Household goods',null],
    ['Sena (Mozambique)','Mozambique','Southern Africa',['Riverine patterns'],'Cash','Monetary bride price',null],
    ['Yao (Mozambique)','Mozambique','Southern Africa',['Islamic; arranged'],'Mahr','Islamic mahr',null],
    ['Ovambo (Namibia)','Namibia','Southern Africa',['Lobola','Family consents'],'Cattle','Cattle wealth',null],
    ['Kavango','Namibia','Southern Africa',['River feasts'],'Livestock','Cattle/goats',null],
    ['Herero (Namibia)','Namibia','Southern Africa',['Cattle economy','Victorian dresses'],'Cattle','Cattle exchanges',null],
    ['Himba (Namibia)','Namibia','Southern Africa',['Ochre body paint','Arranged polygamy'],'Livestock','Goats/cattle',null],
    ['San (Namibia)','Namibia','Southern Africa',['Hunter-gatherer','No formal lobola'],'Shared','Shared resources',null],
    ['Seychellois/Creole','Seychelles','Southern Africa',['Sega drums','Tropical flowers','Matriarchal; civil/religious'],'Money/Gifts','Cash and gifts',null],
    ['Indian (Seychelles)','Seychelles','Southern Africa',['Hindu rites; endogamous'],'Dowry','Dowry goods',null],
    ['Zulu (South Africa)','South Africa','Southern Africa',['Lobola','Umemulo','Polygyny allowed'],'Cattle/Money','Cows and cash',null],
    ['Xhosa (South Africa)','South Africa','Southern Africa',['Ukutwala; negotiations'],'Livestock/Cash','Cattle and money',null],
    ['Sotho (South Africa)','South Africa','Southern Africa',['Initiation; lobola'],'Cattle','Cattle bride wealth',null],
    ['Tswana (South Africa)','South Africa','Southern Africa',['Bogadi practice'],'Heifers','Heifers for bogadi',null],
    ['Venda (South Africa)','South Africa','Southern Africa',['Domba dance'],'Livestock','Cattle/goats',null],
    ['Bemba','Zambia','Southern Africa',['Ichilanga mulilo cooking test','Multi-stage process'],'Cash/Goods','Cash and gifts',null],
    ['Tonga (Zambia)','Zambia','Southern Africa',['Matrilineal emphasis'],'Cattle','Cattle wealth',null],
    ['Nyanja (Zambia)','Zambia','Southern Africa',['Urban statutory/customary mix'],'Money/Livestock','Cash and animals',null],
    ['Lozi (Zambia)','Zambia','Southern Africa',['River negotiations'],'Goods','Cattle and cloth',null],
    ['Shona','Zimbabwe','Southern Africa',['Roora; kukundikana test','White wedding after'],'Cattle/Cash','Cattle and money',null],
    ['Ndebele (Zimbabwe)','Zimbabwe','Southern Africa',['Lobola; house paintings'],'Cattle','Cows as lobola',null],
    ['Tonga (Zimbabwe)','Zimbabwe','Southern Africa',['Matrilineal lines'],'Cattle','Cattle transfers',null],
    ['Venda (Zimbabwe)','Zimbabwe','Southern Africa',['Domba rites'],'Goods','Household goods',null],
];

// North Africa tribes
$northAfricaTribes = [
    ['Arabs (Algeria)','Algeria','North Africa',['Henna night','El Khouara proposal','Multi-day feasts','Family-headed obedience'],'Mahr','Gold and money mahr',null],
    ['Berbers/Amazigh (Algeria)','Algeria','North Africa',['Negaffa guides bride','Traditional silk dress'],'Sheep/Jewelry','Sheep and jewelry as dowry',null],
    ['Tuareg (Algeria)','Algeria','North Africa',['Nomadic veiling','Saharan customs'],'Camels','Camel transfers as dowry',null],
    ['Mozabites','Algeria','North Africa',['Ibadi community customs'],'TBD','To be documented',null],
    ['Chaoui (Aures)','Algeria','North Africa',['Mountain rituals with music'],'Goods','Jewelry and livestock gifts',null],
    ['Sahrawi (Algeria)','Algeria','North Africa',['Desert nomadic rituals'],'Camels/Goods','Camels and gifts',null],
    ['Turks (Algeria)','Algeria','North Africa',['Ottoman attire influences'],'Gold/Cash','Gold and cash',null],
    ['French (Algeria)','Algeria','North Africa',['Western-influenced ceremonies'],'Money/Gifts','Monetary gifts',null],
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
    ['Arabs (Libya)','Libya','North Africa',['Hammam Bukhari bath','Pink ethnic dress','Patrilineal cousin preference'],'Mahr','Gold and money mahr',null],
    ['Berbers (Libya)','Libya','North Africa',['Matrilineal elements','Veiling'],'Jewelry/Livestock','Jewelry and animals',null],
    ['Tuareg (Libya)','Libya','North Africa',['Desert camel rituals'],'Camels','Camel transfers',null],
    ['Tebu/Toubou','Libya','North Africa',['Nomadic alliances'],'Livestock','Goats/camels',null],
    ['Greeks (Libya)','Libya','North Africa',['Orthodox wedding'],'Gold','Gold gifts',null],
    ['Italians (Libya)','Libya','North Africa',['Catholic ceremonies'],'Money/Gifts','Cash and gifts',null],
    ['Amazigh (Nafusi/Kabyle)','Libya','North Africa',['Mountain rituals','Veiling; jewelry'],'Jewelry/Goods','Jewelry and goods',null],
    ['Warfalla (Arab tribe)','Libya','North Africa',['Tribal negotiations'],'Livestock','Livestock payments',null],
    ['Arab-Berbers (Mauritania)','Mauritania','North Africa',['Arranged marriages','Cousin preference','Polygyny permitted'],'Mahr','Money and livestock',null],
    ['Haratines','Mauritania','North Africa',['Traditional Islamic rites','Intermarriage common'],'Livestock','Livestock contributions',null],
    ['Soninke (Mauritania)','Mauritania','North Africa',['Matrilineal elements','Arranged unions'],'Goods','Cloth and animals',null],
    ['Wolof (Mauritania)','Mauritania','North Africa',['Endogamous cousin marriages'],'Mahr (Gold)','Gold dowry',null],
    ['Fulani/Peul (Mauritania)','Mauritania','North Africa',['Cattle alliances','Polygyny'],'Cattle','Cattle payments',null],
    ['Tukulor','Mauritania','North Africa',['Islamic feasts'],'Money','Cash dowry',null],
    ['Bambara (Mauritania)','Mauritania','North Africa',['Kin-based alliances'],'Money/Goods','Cash and gifts',null],
    ['Moors','Mauritania','North Africa',['Nomadic customs'],'Livestock','Animal wealth',null],
    ['Arabs (Morocco)','Morocco','North Africa',['Henna night','Amariya procession','Multi-day feasts','Green/gold caftan'],'Mahr/Jewelry','Gold, money and jewelry',null],
    ['Amazigh/Berber (Morocco)','Morocco','North Africa',['Negaffa guides bride','Silk dress','Sheep/jewelry dowry'],'Sheep/Jewelry','Sheep and jewelry',null],
    ['Sahrawi (Morocco)','Morocco','North Africa',['Desert nomadic rituals'],'Camels/Goods','Camels and goods',null],
    ['Rifians (Morocco)','Morocco','North Africa',['Mountain rituals'],'Goods','Jewelry and livestock',null],
    ['Sous (Berber)','Morocco','North Africa',['Matrilineal tendencies'],'Jewelry','Jewelry as dowry',null],
    ['Chleuh (Berber)','Morocco','North Africa',['Veiling customs'],'Livestock','Goats/cattle',null],
    ['Jews (Morocco)','Morocco','North Africa',['Synagogue rites','Henna'],'Mahr','Mahr per rite',null],
    ['French (Morocco)','Morocco','North Africa',['Western blends'],'Money/Gifts','Cash and gifts',null],
    ['Spanish (Morocco)','Morocco','North Africa',['Catholic ceremonies'],'Gold','Gold gifts',null],
    ['Arab-Berbers (Tunisia)','Tunisia','North Africa',['Five-event weddings','Henna and feasts','White or traditional attire'],'Mahr/Money/Jewelry','Money and jewelry',null],
    ['Turkish descent (Tunisia)','Tunisia','North Africa',['Ottoman attire; multi-day'],'Gold/Cash','Gold and cash',null],
    ['Jews (Tunisia)','Tunisia','North Africa',['Synagogue rites; henna'],'Mahr','Mahr per law',null],
    ['Berbers (Kabyle, Tunisia)','Tunisia','North Africa',['Matrilineal; veiling'],'Jewelry','Jewelry transfer',null],
    ['French (Tunisia)','Tunisia','North Africa',['Western-Catholic'],'Money','Monetary gifts',null],
    ['Italians (Tunisia)','Tunisia','North Africa',['Mediterranean customs'],'Gold','Gold gifts',null],
    ['Maltese (Tunisia)','Tunisia','North Africa',['Island blends'],'Goods','Household goods',null],
];

// Combine all tribes
$allSeedTribesRaw = array_merge(
    $centralAfricaTribes,
    $eastAfricaTribes,
    $westAfricaTribes,
    $southernAfricaTribes,
    $northAfricaTribes
);

// Convert to proper format
foreach ($allSeedTribesRaw as $tribeArray) {
    $allSeedTribes[] = convertSeedToTribe($tribeArray, $counter++);
}

// Merge with database tribes (avoid duplicates)
foreach ($allSeedTribes as $seedTribe) {
    $region = $seedTribe['region'] ?: 'Other';
    $country = $seedTribe['country'] ?: 'Unknown';
    
    // Check if already exists in database
    $exists = false;
    if (isset($regionsData[$region][$country])) {
        foreach ($regionsData[$region][$country] as $existing) {
            if ($existing['name'] === $seedTribe['name'] && $existing['country'] === $seedTribe['country']) {
                $exists = true;
                break;
            }
        }
    }
    
    if (!$exists) {
        if (!isset($regionsData[$region])) {
            $regionsData[$region] = [];
        }
        if (!isset($regionsData[$region][$country])) {
            $regionsData[$region][$country] = [];
        }
        $regionsData[$region][$country][] = $seedTribe;
    }
}

// Define region icons
$regionIcons = [
    'East Africa' => 'fas fa-mountain',
    'West Africa' => 'fas fa-tree',
    'Southern Africa' => 'fas fa-globe-africa',
    'North Africa' => 'fas fa-mosque',
    'Central Africa' => 'fas fa-leaf'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Regions - AfroMarry</title>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="<?php echo base_url('index.php'); ?>">
                    <i class="fas fa-heart"></i>
                    <span>AfroMarry</span>
                </a>
            </div>
            <div class="nav-menu">
                <a href="<?php echo page_url('dashboard.php'); ?>" class="nav-link">Dashboard</a>
                <a href="<?php echo page_url('regions.php'); ?>" class="nav-link active">Browse Regions</a>
                <a href="<?php echo page_url('cart.php'); ?>" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    Cart
                </a>
                <a href="<?php echo auth_url('logout.php'); ?>" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <?php
    // Get premium expiration for sidebar (only if user is logged in)
    $premium_expires = null;
    if ($user && ($user['is_premium'] ?? false)) {
        $premium_expires = $user['premium_expires_at'] ?? null;
    }
    ?>

    <div class="dashboard-container">
        <?php if ($user): ?>
            <?php include 'includes/dashboard-sidebar.php'; ?>
        <?php else: ?>
            <!-- No sidebar for anonymous users -->
            <div style="display: none;"></div>
        <?php endif; ?>

        <div class="dashboard-content">
            <div class="dashboard-header">
                <h1>Browse African Regions</h1>
                <p>Explore tribes organized by region, country, and tribe</p>
            </div>

            <!-- Search -->
            <div class="search-container" style="margin-bottom: 2rem;">
                <div class="search-box" style="max-width: 500px;">
                    <input type="text" id="region-search" placeholder="Search regions, countries, or tribes...">
                    <button id="search-btn" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>

            <!-- Regions -->
            <div id="regions-container" class="regions-browse-container">
                <?php foreach ($regionsData as $region => $countries): ?>
                <div class="region-section" data-region="<?php echo htmlspecialchars($region); ?>">
                    <div class="region-header" onclick="toggleRegion('<?php echo htmlspecialchars($region); ?>')">
                        <div class="region-header-left">
                            <i class="<?php echo $regionIcons[$region] ?? 'fas fa-map-marker-alt'; ?>"></i>
                            <h2><?php echo htmlspecialchars($region); ?></h2>
                            <span class="region-count"><?php 
                                $totalTribes = 0;
                                foreach ($countries as $countryTribes) {
                                    $totalTribes += count($countryTribes);
                                }
                                echo $totalTribes . ' tribe' . ($totalTribes !== 1 ? 's' : '');
                            ?></span>
                        </div>
                        <i class="fas fa-chevron-down region-toggle-icon"></i>
                    </div>
                    
                    <div class="region-content" id="region-<?php echo md5($region); ?>">
                        <?php foreach ($countries as $country => $tribes): ?>
                        <div class="country-section">
                            <div class="country-header" onclick="toggleCountry('<?php echo md5($region . $country); ?>')">
                                <div class="country-header-left">
                                    <i class="fas fa-flag"></i>
                                    <h3><?php echo htmlspecialchars($country); ?></h3>
                                    <span class="country-count"><?php echo count($tribes); ?> tribe<?php echo count($tribes) !== 1 ? 's' : ''; ?></span>
                                </div>
                                <i class="fas fa-chevron-down country-toggle-icon"></i>
                            </div>
                            
                            <div class="country-content" id="country-<?php echo md5($region . $country); ?>">
                                <div class="tribes-grid-compact">
                                    <?php foreach ($tribes as $tribe): ?>
                                    <div class="tribe-card-compact" data-tribe-name="<?php echo htmlspecialchars(strtolower($tribe['name'])); ?>">
                                        <div class="tribe-card-header">
                                            <h4><?php echo htmlspecialchars($tribe['name']); ?></h4>
                                            <button class="tribe-expand-btn" onclick="toggleTribeDetails('<?php echo md5($tribe['name'] . $tribe['country']); ?>')">
                                                <i class="fas fa-chevron-down"></i>
                                            </button>
                                        </div>
                                        <div class="tribe-card-body" id="tribe-<?php echo md5($tribe['name'] . $tribe['country']); ?>">
                                            <div class="tribe-info-row">
                                                <strong>Dowry Type:</strong>
                                                <span><?php echo htmlspecialchars($tribe['dowry_type']); ?></span>
                                            </div>
                                            <div class="tribe-info-row">
                                                <strong>Dowry Details:</strong>
                                                <span><?php echo htmlspecialchars($tribe['dowry_details']); ?></span>
                                            </div>
                                            <?php if (!empty($tribe['customs']) && is_array($tribe['customs'])): ?>
                                            <div class="tribe-customs-row">
                                                <strong>Key Customs:</strong>
                                                <ul class="customs-list-inline">
                                                    <?php foreach (array_slice($tribe['customs'], 0, 3) as $custom): ?>
                                                    <li><?php echo htmlspecialchars($custom); ?></li>
                                                    <?php endforeach; ?>
                                                    <?php if (count($tribe['customs']) > 3): ?>
                                                    <li class="more-customs">+<?php echo count($tribe['customs']) - 3; ?> more</li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                            <?php endif; ?>
                                            <button class="btn-secondary btn-small" onclick="viewTribeDetails('<?php echo $tribe['id']; ?>')">
                                                View Full Details
                                            </button>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($regionsData)): ?>
            <div class="empty-state">
                <i class="fas fa-globe-africa" style="font-size: 4rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                <h3>No tribes found</h3>
                <p>Start by seeding the database with tribe data.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .regions-browse-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .region-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .region-header {
            background: linear-gradient(135deg, #8B5CF6, #EC4899);
            color: white;
            padding: 1.5rem 2rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .region-header:hover {
            background: linear-gradient(135deg, #7C3AED, #DB2777);
        }

        .region-header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .region-header-left i {
            font-size: 1.5rem;
        }

        .region-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }

        .region-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.85rem;
            margin-left: 1rem;
        }

        .region-toggle-icon {
            transition: transform 0.3s ease;
        }

        .region-section.expanded .region-toggle-icon {
            transform: rotate(180deg);
        }

        .region-content {
            padding: 1.5rem;
            display: none;
        }

        .region-section.expanded .region-content {
            display: block;
        }

        .country-section {
            margin-bottom: 1.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
        }

        .country-section:last-child {
            margin-bottom: 0;
        }

        .country-header {
            background: #f8fafc;
            padding: 1rem 1.5rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .country-header:hover {
            background: #f1f5f9;
        }

        .country-header-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .country-header h3 {
            margin: 0;
            font-size: 1.1rem;
            color: #1f2937;
        }

        .country-count {
            background: #e5e7eb;
            color: #6b7280;
            padding: 0.2rem 0.6rem;
            border-radius: 10px;
            font-size: 0.8rem;
            margin-left: 0.5rem;
        }

        .country-toggle-icon {
            transition: transform 0.3s ease;
            color: #6b7280;
        }

        .country-section.expanded .country-toggle-icon {
            transform: rotate(180deg);
        }

        .country-content {
            padding: 1rem 1.5rem;
            display: none;
        }

        .country-section.expanded .country-content {
            display: block;
        }

        .tribes-grid-compact {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
        }

        .tribe-card-compact {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 1rem;
            transition: all 0.3s ease;
        }

        .tribe-card-compact:hover {
            background: white;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .tribe-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .tribe-card-header h4 {
            margin: 0;
            color: #1f2937;
            font-size: 1rem;
        }

        .tribe-expand-btn {
            background: transparent;
            border: none;
            color: #6b7280;
            cursor: pointer;
            padding: 0.25rem;
            transition: all 0.3s ease;
        }

        .tribe-expand-btn:hover {
            color: #8B5CF6;
        }

        .tribe-card-body {
            display: none;
            padding-top: 0.75rem;
            border-top: 1px solid #e5e7eb;
        }

        .tribe-card-compact.expanded .tribe-card-body {
            display: block;
        }

        .tribe-card-compact.expanded .tribe-expand-btn i {
            transform: rotate(180deg);
        }

        .tribe-info-row {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .tribe-info-row strong {
            color: #6b7280;
            min-width: 100px;
        }

        .tribe-info-row span {
            color: #1f2937;
        }

        .tribe-customs-row {
            margin-bottom: 0.75rem;
        }

        .tribe-customs-row strong {
            display: block;
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .customs-list-inline {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .customs-list-inline li {
            background: #e5e7eb;
            color: #6b7280;
            padding: 0.25rem 0.5rem;
            border-radius: 5px;
            font-size: 0.85rem;
        }

        .customs-list-inline li.more-customs {
            background: #8B5CF6;
            color: white;
        }

        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }

        @media (max-width: 768px) {
            .tribes-grid-compact {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        // Toggle region
        function toggleRegion(region) {
            const regionSection = document.querySelector(`[data-region="${region}"]`);
            regionSection.classList.toggle('expanded');
        }

        // Toggle country
        function toggleCountry(countryId) {
            const countrySection = document.getElementById(`country-${countryId}`).closest('.country-section');
            countrySection.classList.toggle('expanded');
        }

        // Toggle tribe details
        function toggleTribeDetails(tribeId) {
            const tribeCard = document.getElementById(`tribe-${tribeId}`).closest('.tribe-card-compact');
            tribeCard.classList.toggle('expanded');
        }

        // View full tribe details
        function viewTribeDetails(tribeId) {
            if (tribeId && !tribeId.startsWith('sample_') && !tribeId.startsWith('seed_')) {
                window.location.href = `<?php echo page_url('tribe-details.php'); ?>?id=${tribeId}`;
            } else {
                alert('Full details available after database seeding. This is a sample tribe.');
            }
        }

        // Search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('region-search');
            const searchBtn = document.getElementById('search-btn');

            function performSearch() {
                const query = searchInput.value.toLowerCase().trim();
                
                if (!query) {
                    // Show all
                    document.querySelectorAll('.region-section, .country-section, .tribe-card-compact').forEach(el => {
                        el.style.display = '';
                    });
                    return;
                }

                // Hide all first
                document.querySelectorAll('.region-section, .country-section, .tribe-card-compact').forEach(el => {
                    el.style.display = 'none';
                });

                // Show matching tribes
                document.querySelectorAll('.tribe-card-compact').forEach(card => {
                    const tribeName = card.getAttribute('data-tribe-name');
                    if (tribeName.includes(query)) {
                        card.style.display = '';
                        // Show parent country and region
                        card.closest('.country-section').style.display = '';
                        card.closest('.country-section').closest('.region-content').style.display = 'block';
                        card.closest('.region-section').style.display = '';
                        card.closest('.region-section').classList.add('expanded');
                    }
                });

                // Also search region and country names
                document.querySelectorAll('.region-header, .country-header').forEach(header => {
                    const text = header.textContent.toLowerCase();
                    if (text.includes(query)) {
                        const regionSection = header.closest('.region-section');
                        regionSection.style.display = '';
                        regionSection.classList.add('expanded');
                        if (header.classList.contains('country-header')) {
                            header.closest('.country-section').style.display = '';
                            header.closest('.country-section').classList.add('expanded');
                        }
                    }
                });
            }

            searchInput.addEventListener('input', performSearch);
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });
            searchBtn.addEventListener('click', performSearch);
        });
    </script>
</body>
</html>

