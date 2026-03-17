<?php
header('Content-Type: application/json');

require_once '../db_connect.php';

// Get device info from request
$device_id = isset($_POST['device_id']) ? $_POST['device_id'] : 'ESP32_DEFAULT';
$mac_address = isset($_POST['mac_address']) ? $_POST['mac_address'] : null;
$ip_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;

// Update or insert device record
$stmt = $conn->prepare("INSERT INTO esp32_devices (device_id, mac_address, ip_address, last_seen, status) 
                        VALUES (?, ?, ?, NOW(), 'online')
                        ON DUPLICATE KEY UPDATE 
                        mac_address = VALUES(mac_address),
                        ip_address = VALUES(ip_address),
                        last_seen = NOW(),
                        status = 'online'");
$stmt->bind_param("sss", $device_id, $mac_address, $ip_address);

if ($stmt->execute()) {
    date_default_timezone_set('Asia/Kuala_Lumpur');
    echo json_encode([
        'success' => true,
        'server_time' => date('Y-m-d H:i:s'),
        'message' => 'Heartbeat received'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update heartbeat: ' . $conn->error
    ]);
}

$stmt->close();
$conn->close();
?>
