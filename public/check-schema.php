<?php
header('Content-Type: application/json');

$host = 'bugis.sharehostserver.com';
$db   = 'umu43661_siadev';
$user = 'umu43661_sia';
$pass = '-Siaumuka190522';
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
     echo json_encode(["error" => "Connection failed: " . $e->getMessage()]);
     exit;
}

$stmt1 = $pdo->query("DESCRIBE akd_skripsi_bimbingan");
$schema = $stmt1->fetchAll();

echo json_encode([
    "schema" => $schema
], JSON_PRETTY_PRINT);
