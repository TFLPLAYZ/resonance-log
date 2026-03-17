<?php
// Local credentials for XAMPP
$servername = 'localhost';
$username = 'root';
$password = '';
$dbname = 'resonance_log_db';

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
} catch (Exception $e) {
    die("Sambungan ke pangkalan data gagal: " . $e->getMessage());
}

header('Content-Type: text/plain');

echo "=== CHECKING SCHEMA ===\n";
$schemaResult = $conn->query("DESCRIBE cap_jari");
while ($row = $schemaResult->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
echo "\n";

// Check cap_jari records
$query = "SELECT * FROM cap_jari ORDER BY id_cap_jari DESC LIMIT 20";

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    echo str_pad("ID", 5) . " | " . str_pad("No KP", 15) . " | " . str_pad("Status", 10) . " | Template Content\n";
    echo str_repeat("-", 80) . "\n";
    
    while ($row = $result->fetch_assoc()) {
        $template = $row['fingerprint_template'];
        // Show the first 50 chars of template or a summary
        $templateDisplay = $template ? (strlen($template) > 50 ? substr($template, 0, 50) . "..." : $template) : "[NULL]";
        
        echo str_pad($row['id_cap_jari'], 5) . " | " 
           . str_pad($row['no_kp_pengguna'], 15) . " | " 
           . str_pad($row['status'] ?? 'N/A', 10) . " | " 
           . $templateDisplay . "\n";
    }
} else {
    echo "No records found or query failed: " . $conn->error . "\n";
}

$conn->close();
?>
