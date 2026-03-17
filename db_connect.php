<?php
// Use shared configuration
// Wrap in try-catch because config.php attempts PDO connection. 
// We want to proceed even if PDO fails, as we use MySQLi here.
try {
    require_once __DIR__ . '/config.php';
} catch (PDOException $e) {
    // Ignore PDO error, we just needed the variables
}

$servername = $host ?? 'localhost';
$username = $user ?? 'root';
$password = $pass ?? '';
$dbname = $db ?? 'resonance_log_db';

try {
    // Suppress warnings to handle errors manually if needed (though try-catch works for PHP 8.1+)
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Set time zone to Malaysia (+08:00)
    $conn->query("SET time_zone = '+08:00'");
} catch (Exception $e) {
    die("Sambungan ke pangkalan data gagal (MySQLi Error): " . $e->getMessage());
}

if ($conn->connect_error) {
    die("Sambungan ke pangkalan data gagal: " . $conn->connect_error);
}
?>
