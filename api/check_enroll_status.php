<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['no_kp'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

require_once '../db_connect.php';

$no_kp = $_SESSION['no_kp'];

// Check if there's a completed activation for this user
$stmt = $conn->prepare("SELECT id, status, completed_time 
                        FROM scanner_activations 
                        WHERE no_kp_pengguna = ? 
                        AND status IN ('completed', 'processing')
                        ORDER BY activation_time DESC 
                        LIMIT 1");
$stmt->bind_param("s", $no_kp);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    if ($row['status'] === 'completed') {
        // Mark as processed and delete the activation record
        $delete_stmt = $conn->prepare("DELETE FROM scanner_activations WHERE id = ?");
        $delete_stmt->bind_param("i", $row['id']);
        $delete_stmt->execute();
        $delete_stmt->close();
        
        echo json_encode([
            'success' => true,
            'status' => 'enrolled',
            'message' => 'Fingerprint successfully enrolled'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'status' => 'processing',
            'message' => 'Processing fingerprint'
        ]);
    }
} else {
    // Check for timeout (more than 30 seconds old)
    $timeout_stmt = $conn->prepare("UPDATE scanner_activations 
                                    SET status = 'timeout' 
                                    WHERE no_kp_pengguna = ? 
                                    AND status = 'pending' 
                                    AND TIMESTAMPDIFF(SECOND, activation_time, NOW()) > 30");
    $timeout_stmt->bind_param("s", $no_kp);
    $timeout_stmt->execute();
    $timeout_stmt->close();
    
    echo json_encode([
        'success' => true,
        'status' => 'waiting',
        'message' => 'Waiting for fingerprint scan'
    ]);
}

$stmt->close();
$conn->close();
?>
