<?php
require_once 'config.php';
require_once 'db_connect.php';
header('Content-Type: text/html; charset=utf-8');

echo "<h2>Resonance Log - Hardware Debug Tool</h2>";

// 1. Check Connection
if ($conn->connect_error) {
    echo "<p style='color:red'>❌ Database Connection Failed: " . $conn->connect_error . "</p>";
} else {
    echo "<p style='color:green'>✅ Database Connected Successfully</p>";
}

// 2. Check Time Synchronization
date_default_timezone_set('Asia/Kuala_Lumpur');
$php_time = date('Y-m-d H:i:s');
$res = $conn->query("SELECT NOW() as db_now");
$row = $res->fetch_assoc();
$db_time = $row['db_now'];

echo "<h3>Time Status</h3>";
echo "<ul>";
echo "<li>PHP Time (Kuala Lumpur): $php_time</li>";
echo "<li>Database Time (NOW()): $db_time</li>";
echo "</ul>";

if ($php_time !== $db_time) {
    echo "<p style='color:orange'>⚠️ Note: Small differences (1-2 seconds) are normal. If they are hours apart, heartbeat checks will fail.</p>";
}

// 3. Check Registered Devices
echo "<h3>Registered Devices in Table 'esp32_devices'</h3>";
$res = $conn->query("SELECT *, TIMESTAMPDIFF(SECOND, last_seen, NOW()) as seconds_ago FROM esp32_devices");

if ($res->num_rows === 0) {
    echo "<p style='color:red'>❌ NO DEVICES FOUND. The IoT device has never successfully sent a heartbeat to THIS database.</p>";
    echo "<p><b>Possible Reasons:</b><br>
    1. The ESP32 is sending heartbeats to the LIVE server, but you are checking the LOCAL server.<br>
    2. The ESP32 WiFi connection is failing.<br>
    3. The API endpoint 'api/esp32_heartbeat.php' has a bug or wrong credentials.</p>";
} else {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Device ID</th><th>Last Seen</th><th>Status</th><th>Seconds Ago</th><th>Online?</th></tr>";
    while ($device = $res->fetch_assoc()) {
        $is_online = ($device['seconds_ago'] < 60) ? "✅ ONLINE" : "❌ OFFLINE";
        $color = ($device['seconds_ago'] < 60) ? "green" : "red";
        echo "<tr>";
        echo "<td>{$device['device_id']}</td>";
        echo "<td>{$device['last_seen']}</td>";
        echo "<td>{$device['status']}</td>";
        echo "<td>{$device['seconds_ago']}s</td>";
        echo "<td style='color:$color; font-weight:bold'>$is_online</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 4. Test API Link
echo "<h3>Test API Tool</h3>";
echo "<p>Click the button below to simulate a heartbeat from a device named 'TEST_DEVICE'. This verifies if the API can write to the database.</p>";
echo "<form method='POST'>
        <input type='hidden' name='action' value='test_heartbeat'>
        <button type='submit' style='padding:10px 20px; cursor:pointer'>Send Test Heartbeat</button>
      </form>";

if (isset($_POST['action']) && $_POST['action'] === 'test_heartbeat') {
    $test_id = 'TEST_DEVICE';
    $mac = 'AA:BB:CC:DD:EE:FF';
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Simulate what the ESP32 does
    $stmt = $conn->prepare("INSERT INTO esp32_devices (device_id, mac_address, ip_address, last_seen, status) 
                            VALUES (?, ?, ?, NOW(), 'online')
                            ON DUPLICATE KEY UPDATE 
                            mac_address = VALUES(mac_address),
                            ip_address = VALUES(ip_address),
                            last_seen = NOW(),
                            status = 'online'");
    $stmt->bind_param("sss", $test_id, $mac, $ip);
    
    if ($stmt->execute()) {
        echo "<p style='color:green; font-weight:bold'>✅ Success! Test heartbeat recorded. Refresh page to see it in the table above.</p>";
    } else {
        echo "<p style='color:red'>❌ Failed to record test heartbeat: " . $conn->error . "</p>";
    }
    $stmt->close();
}
?>
