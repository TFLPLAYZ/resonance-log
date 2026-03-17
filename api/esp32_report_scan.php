<?php
header('Content-Type: application/json');

require_once '../db_connect.php';
require_once '../includes/fingerprint_matcher.php';

// Get data from ESP32
$no_kp = isset($_POST['no_kp']) ? $_POST['no_kp'] : null;
$activation_id = isset($_POST['activation_id']) ? $_POST['activation_id'] : null;
$fingerprint_template = isset($_POST['fingerprint_template']) ? $_POST['fingerprint_template'] : null;

if (!$no_kp || !$activation_id) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

if (!$fingerprint_template) {
    echo json_encode(['success' => false, 'message' => 'Missing fingerprint_template']);
    exit();
}

// Validate template format
if (!FingerprintMatcher::validateTemplate($fingerprint_template)) {
    echo json_encode(['success' => false, 'message' => 'Invalid fingerprint template format']);
    exit();
}

date_default_timezone_set('Asia/Kuala_Lumpur');
$current_hour = (int)date('H');

// Check: Only allow ONE scan per day
$check_dup = $conn->prepare("SELECT id_log FROM log_keberadaan WHERE no_kp_pengguna = ? AND DATE(masa_imbasan) = CURDATE() LIMIT 1");
$check_dup->bind_param("s", $no_kp);
$check_dup->execute();
$dup_result = $check_dup->get_result();
if ($dup_result->num_rows > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Imbasan sudah dilakukan hari ini. Hanya satu imbasan dibenarkan sehari.'
    ]);
    $check_dup->close();
    $conn->close();
    exit();
}
$check_dup->close();

// Determine attendance status based on time
// Rule:
// Sunday: Cutoff 6pm (18:00)
// Mon-Sat: Cutoff 10am (10:00)

$timestamp = time();
$day_of_week = (int)date('w', $timestamp); // 0 (Sunday) to 6 (Saturday)
$current_hour = (int)date('H', $timestamp);

$status_keberadaan = 'Hadir'; // Default
$catatan = 'Imbasan cap jari pada ' . date('d/m/Y H:i:s');

if ($day_of_week == 0) {
    // SUNDAY RULE
    if ($current_hour >= 18) {
        $status_keberadaan = 'Lewat';
    }
} else {
    // MONDAY - SATURDAY RULE
    if ($current_hour >= 10) {
        $status_keberadaan = 'Lewat';
    }
}

// Store fingerprint template using FingerprintMatcher
if (!FingerprintMatcher::storeTemplate($no_kp, $fingerprint_template, $conn)) {
    echo json_encode(['success' => false, 'message' => 'Failed to store fingerprint template']);
    exit();
}

// Get the fingerprint ID
$check_stmt = $conn->prepare("SELECT id_cap_jari FROM cap_jari WHERE no_kp_pengguna = ?");
$check_stmt->bind_param("s", $no_kp);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Failed to retrieve fingerprint ID']);
    exit();
}

$row = $result->fetch_assoc();
$id_cap_jari = $row['id_cap_jari'];
$check_stmt->close();

// ALWAYS INSERT attendance log (whether first time or not)
$log_stmt = $conn->prepare("INSERT INTO log_keberadaan 
                           (id_cap_jari, no_kp_pengguna, masa_imbasan, status_keberadaan, catatan) 
                           VALUES (?, ?, NOW(), ?, ?)");
$log_stmt->bind_param("isss", $id_cap_jari, $no_kp, $status_keberadaan, $catatan);

if (!$log_stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Failed to log attendance: ' . $log_stmt->error]);
    exit();
}
$log_stmt->close();

// Mark activation as completed
$complete_stmt = $conn->prepare("UPDATE scanner_activations 
                                SET status = 'completed', 
                                    completed_time = NOW() 
                                WHERE id = ?");
$complete_stmt->bind_param("i", $activation_id);
$complete_stmt->execute();
$complete_stmt->close();

echo json_encode([
    'success' => true,
    'message' => 'Fingerprint and attendance recorded successfully',
    'status_keberadaan' => $status_keberadaan,
    'id_cap_jari' => $id_cap_jari
]);

$conn->close();
?>
