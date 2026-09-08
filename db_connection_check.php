<?php
// Temporary, non-sensitive connectivity check. Remove after diagnosing.
require_once __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');

$result = ['host' => DB_HOST, 'mysqli' => false, 'pdo' => false];
$mysqli = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$mysqli->connect_error) {
    $result['mysqli'] = true;
    $mysqli->close();
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $result['pdo'] = true;
} catch (Throwable $e) {
    $result['pdo_error_code'] = (string) $e->getCode();
}

echo json_encode($result);
