<?php
// require_once 'db_connect.php';
$conn = new mysqli('localhost', 'root', '', 'resonance_log_db');
if ($conn->connect_error) die("Local connection failed: " . $conn->connect_error);

echo "--- Table structure for cap_jari ---\n";
$resDesc = $conn->query("DESCRIBE cap_jari");
if ($resDesc) {
    while($row = $resDesc->fetch_assoc()) {
        echo "{$row['Field']} - {$row['Type']}\n";
    }
} else {
    echo "Could not describe table: " . $conn->error . "\n";
}

echo "\n--- Checking cap_jari for Sensor ID 1 ---\n";
// The sensor reports ID 1. Matcher checks if id_cap_jari = 1 OR fingerprint_template LIKE %SENSOR_ID_1_%
$sql = "SELECT * FROM cap_jari WHERE id_cap_jari = 1";
// We'll check fingerprint_template only if it exists
$resDesc = $conn->query("DESCRIBE cap_jari");
$hasTemplate = false;
while($row = $resDesc->fetch_assoc()) if($row['Field'] == 'fingerprint_template') $hasTemplate = true;

if ($hasTemplate) {
    $sql = "SELECT c.*, p.nama_penuh, p.status 
            FROM cap_jari c 
            LEFT JOIN pengguna p ON c.no_kp_pengguna = p.no_kp 
            WHERE c.id_cap_jari = 1 
               OR c.fingerprint_template LIKE '%SENSOR_ID_1_%'";
} else {
    $sql = "SELECT c.*, p.nama_penuh, p.status 
            FROM cap_jari c 
            LEFT JOIN pengguna p ON c.no_kp_pengguna = p.no_kp 
            WHERE c.id_cap_jari = 1";
}

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "No records found matching Sensor ID 1.\n";
}

echo "\n--- Recent records in cap_jari ---\n";
$sql2 = "SELECT c.*, p.nama_penuh FROM cap_jari c LEFT JOIN pengguna p ON c.no_kp_pengguna = p.no_kp ORDER BY dicipta_pada DESC LIMIT 5";
$result2 = $conn->query($sql2);
while($row = $result2->fetch_assoc()) {
    print_r($row);
}
?>
