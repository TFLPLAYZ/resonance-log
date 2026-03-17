<?php
require_once 'db_connect.php';

echo "=== Checking cap_jari table ===\n\n";

$result = $conn->query("SELECT id_cap_jari, no_kp_pengguna, fingerprint_data, dicipta_pada FROM cap_jari ORDER BY id_cap_jari DESC LIMIT 10");

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id_cap_jari'] . "\n";
        echo "No KP: " . $row['no_kp_pengguna'] . "\n";
        echo "Fingerprint Data: " . $row['fingerprint_data'] . "\n";
        echo "Created: " . $row['dicipta_pada'] . "\n";
        echo "---\n";
    }
} else {
    echo "No records found in cap_jari table.\n";
}

$conn->close();
?>
