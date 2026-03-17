<?php
header('Content-Type: application/json');

require_once '../db_connect.php';
require_once '../includes/fingerprint_matcher.php';

// Get data from ESP32
$device_id = isset($_POST['device_id']) ? $_POST['device_id'] : null;
$fingerprint_template = isset($_POST['fingerprint_template']) ? $_POST['fingerprint_template'] : null;

if (!$fingerprint_template) {
    echo json_encode(['success' => false, 'message' => 'Missing fingerprint_template']);
    exit();
}

// Validate template format
if (!FingerprintMatcher::validateTemplate($fingerprint_template)) {
    echo json_encode(['success' => false, 'message' => 'Invalid fingerprint template format']);
    exit();
}

// Perform server-side matching
$match = FingerprintMatcher::findMatch($fingerprint_template, $conn, 70); // 70% threshold

if (!$match) {
    echo json_encode([
        'success' => false,
        'message' => 'Fingerprint not recognized. Please register your fingerprint first.'
    ]);
    exit();
}

$no_kp = $match['no_kp'];
$db_fingerprint_id = $match['id_cap_jari'];
$confidence = $match['confidence'];

// Check: Only allow ONE scan per day
date_default_timezone_set('Asia/Kuala_Lumpur');
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

// Apply Attendance Rules
date_default_timezone_set('Asia/Kuala_Lumpur');
$timestamp = time();
$day_of_week = (int)date('w', $timestamp); // 0 (Sunday) to 6 (Saturday)
$current_time = date('H:i:s', $timestamp);
$current_hour = (int)date('H', $timestamp);
$current_minute = (int)date('i', $timestamp);

$status_keberadaan = 'Hadir'; // Default

if ($day_of_week == 0) {
    // SUNDAY RULE
    // Before 6pm (18:00) = Hadir
    // After 6pm (18:00) = Lewat
    if ($current_hour >= 18) {
        $status_keberadaan = 'Lewat';
    }
} else {
    // MONDAY - SATURDAY RULE
    // Before 10am (10:00) = Hadir
    // After 10am (10:00) = Lewat
    if ($current_hour >= 10) {
        $status_keberadaan = 'Lewat';
    }
}

$catatan = 'Imbasan cap jari pada ' . date('d/m/Y H:i:s') . ' (Confidence: ' . $confidence . '%)';

// Log Attendance
$log_stmt = $conn->prepare("INSERT INTO log_keberadaan 
                           (id_cap_jari, no_kp_pengguna, masa_imbasan, status_keberadaan, catatan) 
                           VALUES (?, ?, NOW(), ?, ?)");
$log_stmt->bind_param("isss", $db_fingerprint_id, $no_kp, $status_keberadaan, $catatan);

if ($log_stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Attendance recorded',
        'no_kp' => $no_kp,
        'status' => $status_keberadaan,
        'confidence' => $confidence
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $log_stmt->error
    ]);
}

$log_stmt->close();
$conn->close();
?>
