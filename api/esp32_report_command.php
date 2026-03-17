<?php
header('Content-Type: application/json');

require_once '../db_connect.php';

// Get data from ESP32
$command_id = isset($_POST['command_id']) ? $_POST['command_id'] : null;
$success = isset($_POST['success']) ? filter_var($_POST['success'], FILTER_VALIDATE_BOOLEAN) : false;
$message = isset($_POST['message']) ? $_POST['message'] : '';

if (!$command_id) {
    echo json_encode(['success' => false, 'message' => 'Missing command_id']);
    exit();
}

$status = $success ? 'completed' : 'failed';

$stmt = $conn->prepare("UPDATE sensor_commands SET status = ?, updated_at = NOW() WHERE id = ?");
$stmt->bind_param("si", $status, $command_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Command status updated']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
