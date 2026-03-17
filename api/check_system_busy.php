<?php
header('Content-Type: application/json');

require_once '../db_connect.php';

// Check for any enrollment that is pending or processing
// and is less than 60 seconds old (to prevent stale locks)
$stmt = $conn->prepare("SELECT no_kp_pengguna, status, activation_time 
                        FROM scanner_activations 
                        WHERE status IN ('pending', 'processing') 
                        AND TIMESTAMPDIFF(SECOND, activation_time, NOW()) < 60
                        ORDER BY activation_time DESC 
                        LIMIT 1");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode([
        'busy' => true,
        'user_kp' => $row['no_kp_pengguna'],
        'status' => $row['status'],
        'message' => 'Scanner is currently busy'
    ]);
} else {
    echo json_encode([
        'busy' => false,
        'message' => 'Scanner is idle'
    ]);
}

$stmt->close();
$conn->close();
?>
