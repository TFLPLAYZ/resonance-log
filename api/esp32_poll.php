<?php
header('Content-Type: application/json');

require_once '../db_connect.php';

// Get device ID from request
$device_id = isset($_GET['device_id']) ? $_GET['device_id'] : 'ESP32_DEFAULT';

// Check for pending COMMANDS first (High Priority)
$cmd_stmt = $conn->prepare("SELECT id, command_type, parameter FROM sensor_commands WHERE status = 'pending' ORDER BY created_at ASC LIMIT 1");
$cmd_stmt->execute();
$cmd_result = $cmd_stmt->get_result();

if ($cmd_result->num_rows > 0) {
    $cmd = $cmd_result->fetch_assoc();
    
    // Mark as fetched
    $update_cmd = $conn->prepare("UPDATE sensor_commands SET status = 'fetched' WHERE id = ?");
    $update_cmd->bind_param("i", $cmd['id']);
    $update_cmd->execute();
    $update_cmd->close();
    
    echo json_encode([
        'command' => 'execute_command',
        'type' => $cmd['command_type'],
        'parameter' => $cmd['parameter'], // e.g., '1' for ID
        'command_id' => $cmd['id']
    ]);
    
    $cmd_stmt->close();
    $conn->close();
    exit(); // Stop here, do not process enrollment
}
$cmd_stmt->close();

// Check for pending activation requests (Enrollment)
$stmt = $conn->prepare("SELECT id, no_kp_pengguna, activation_time 
                        FROM scanner_activations 
                        WHERE status = 'pending' 
                        AND TIMESTAMPDIFF(SECOND, activation_time, NOW()) < 30
                        ORDER BY activation_time ASC 
                        LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $activation = $result->fetch_assoc();
    
    // Mark as processing
    $update_stmt = $conn->prepare("UPDATE scanner_activations SET status = 'processing' WHERE id = ?");
    $update_stmt->bind_param("i", $activation['id']);
    $update_stmt->execute();
    $update_stmt->close();
    
    // Check if user already has fingerprint (for update vs new enrollment)
    $check_fp = $conn->prepare("SELECT id_cap_jari FROM cap_jari WHERE no_kp_pengguna = ?");
    $check_fp->bind_param("s", $activation['no_kp_pengguna']);
    $check_fp->execute();
    $fp_result = $check_fp->get_result();
    $has_fingerprint = $fp_result->num_rows > 0;
    
    if ($has_fingerprint) {
        $fp_data = $fp_result->fetch_assoc();
        $fingerprint_id = $fp_data['id_cap_jari'];
    } else {
        $fingerprint_id = null;
    }
    
    $check_fp->close();
    
    echo json_encode([
        'command' => 'enroll',
        'no_kp' => $activation['no_kp_pengguna'],
        'activation_id' => $activation['id'],
        'fingerprint_id' => $fingerprint_id,
        'is_update' => $has_fingerprint
    ]);
} else {
    echo json_encode([
        'command' => 'idle',
        'message' => 'No pending tasks'
    ]);
}

$stmt->close();
$conn->close();
?>
