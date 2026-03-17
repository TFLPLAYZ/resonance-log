<?php
header('Content-Type: application/json');

require_once '../db_connect.php';

// Check if any ESP32 device has sent a heartbeat in the last 15 seconds
$stmt = $conn->prepare("SELECT device_id, mac_address, ip_address, last_seen, status 
                        FROM esp32_devices 
                        WHERE TIMESTAMPDIFF(SECOND, last_seen, NOW()) < 60 
                        ORDER BY last_seen DESC 
                        LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $device = $result->fetch_assoc();
    echo json_encode([
        'online' => true,
        'device_id' => $device['device_id'],
        'ip_address' => $device['ip_address'],
        'last_seen' => $device['last_seen']
    ]);
} else {
    echo json_encode([
        'online' => false,
        'message' => 'No ESP32 device connected'
    ]);
}

$stmt->close();
$conn->close();
?>
