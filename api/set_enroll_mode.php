<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['no_kp'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

require_once '../db_connect.php';

$no_kp = $_SESSION['no_kp'];

// Check current time - no enrollment after 10pm
date_default_timezone_set('Asia/Kuala_Lumpur');
$current_hour = (int)date('H');

if ($current_hour >= 22) {
    echo json_encode([
        'success' => false, 
        'message' => 'Pendaftaran/imbasan tidak dibenarkan selepas 10 malam. Anda akan ditandakan sebagai Tidak Hadir.'
    ]);
    exit();
}

// Create activation record
$stmt = $conn->prepare("INSERT INTO scanner_activations (no_kp_pengguna, status) VALUES (?, 'pending')");
$stmt->bind_param("s", $no_kp);

if ($stmt->execute()) {
    $activation_id = $stmt->insert_id;
    echo json_encode([
        'success' => true,
        'message' => 'Scanner activated',
        'activation_id' => $activation_id,
        'no_kp' => $no_kp
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to activate scanner: ' . $conn->error
    ]);
}

$stmt->close();
$conn->close();
?>
