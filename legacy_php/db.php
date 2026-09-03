<?php
// Taarifa rasmi za uunganishaji wa Database kwa ajili ya InfinityFree Hosting
// Kwenye InfinityFree, DB Host inatakiwa kuwa host maalum ya MySQL na sio localhost.
$host = 'sql200.infinityfree.com'; // Tafuta host halisi kwenye Control Panel yako chini ya MySQL Databases kama hii itatofautiana

// Jina la database yako uliyotengeneza (Kwenye InfinityFree huanza na id ya akaunti yako)
$db   = 'if0_42048510_schoolresults'; 

// Username ya MySQL iliyotolewa kwenye akaunti yako ya InfinityFree
$user = 'if0_42048510';

// Password ya MySQL (Kutokana na picha uliyotuma, password yako ya sasa ni ashy2023)
$pass = 'ashy2023'; 

$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>