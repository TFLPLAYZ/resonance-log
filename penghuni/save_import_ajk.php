<?php
session_start();
header('Content-Type: application/json');

// Warden might use different auth, but checking session generally
if (!isset($_SESSION['no_kp'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

require_once '../db_connect.php';
// $conn (mysqli) is available from db_connect.php

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (empty($data) || !is_array($data)) {
        echo json_encode(['success' => false, 'message' => 'No data provided']);
        exit();
    }

    $conn->begin_transaction();

    $successCount = 0;
    $errors = [];

    $stmtCheck = $conn->prepare("SELECT no_kp FROM pengguna WHERE no_kp = ?");
    $stmtInsert = $conn->prepare("
        INSERT INTO pengguna (no_kp, nama_penuh, kata_laluan, dorm, kohort, kelas, jawatan, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'Aktif')
    ");

    foreach ($data as $index => $row) {
        $name = trim($row['nama_penuh'] ?? '');
        $ic = trim($row['no_kp'] ?? '');
        $dorm = trim($row['dorm'] ?? '');
        $kohort = intval($row['kohort'] ?? 0);
        $kelas = trim($row['kelas'] ?? '');
        $jawatan = trim($row['jawatan'] ?? 'Pelajar');

        if (!$name || !$ic) {
            $errors[] = "Baris " . ($index + 1) . ": Nama atau IC kosong.";
            continue;
        }

        // Check for duplicate
        $stmtCheck->bind_param("s", $ic);
        $stmtCheck->execute();
        $stmtCheck->store_result();
        if ($stmtCheck->num_rows > 0) {
            $errors[] = "Baris " . ($index + 1) . ": No. KP $ic sudah wujud.";
            continue;
        }

        $password = substr($ic, -4);
        $stmtInsert->bind_param("ssssiss", $ic, $name, $password, $dorm, $kohort, $kelas, $jawatan);
        
        if ($stmtInsert->execute()) {
            $successCount++;
        } else {
            $errors[] = "Baris " . ($index + 1) . ": Ralat pangkalan data.";
        }
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => "Berjaya mengimport $successCount data.",
        'errors' => $errors
    ]);

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
