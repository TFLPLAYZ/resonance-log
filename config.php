<?php
require_once __DIR__ . '/includes/error_handler.php';
date_default_timezone_set('Asia/Kuala_Lumpur');
// // $host = 'localhost';
// $user = 'root';
// $pass = '';  // XAMPP default has no password
// $db   = 'resonance_log_db';
// $charset = 'utf8mb4';

// LIVE SERVER CONFIGURATION (ACTIVE)
$host = 'localhost'; 
$user = 'u999502738_ammar'; 
$pass = 'Ammar@0608';
$db   = 'u999502738_resonance';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->exec("SET time_zone = '+08:00'");
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
