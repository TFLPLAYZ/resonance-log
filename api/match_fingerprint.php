<?php
/**
 * API Endpoint: Match Fingerprint Template
 * 
 * Receives a fingerprint template from ESP32 and matches it against
 * all stored templates in the database.
 * 
 * POST Parameters:
 * - fingerprint_template: Base64-encoded fingerprint template from AS608 sensor
 * - device_id: (optional) ESP32 device identifier
 * 
 * Returns:
 * - success: true/false
 * - no_kp: Matched user's IC number (if found)
 * - confidence: Match confidence score (0-100)
 * - id_cap_jari: Database fingerprint ID (if found)
 * - message: Error or success message
 */

header('Content-Type: application/json');

require_once '../db_connect.php';
require_once '../includes/fingerprint_matcher.php';

// Get data from ESP32
$fingerprint_template = isset($_POST['fingerprint_template']) ? $_POST['fingerprint_template'] : null;
$device_id = isset($_POST['device_id']) ? $_POST['device_id'] : 'unknown';

if (!$fingerprint_template) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing fingerprint_template parameter'
    ]);
    exit();
}

// Validate template format
if (!FingerprintMatcher::validateTemplate($fingerprint_template)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid fingerprint template format'
    ]);
    exit();
}

// Perform matching
$match = FingerprintMatcher::findMatch($fingerprint_template, $conn, 70); // 70% threshold

if ($match) {
    echo json_encode([
        'success' => true,
        'no_kp' => $match['no_kp'],
        'confidence' => $match['confidence'],
        'id_cap_jari' => $match['id_cap_jari'],
        'message' => 'Fingerprint matched successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No matching fingerprint found in database'
    ]);
}

$conn->close();
?>
