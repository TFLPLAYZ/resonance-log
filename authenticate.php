<?php
session_start();
require_once 'db_connect.php';

// Check if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no_kp = trim($_POST['no_kp'] ?? '');
    $kata_laluan = trim($_POST['kata_laluan'] ?? '');

    if (empty($no_kp) || empty($kata_laluan)) {
        echo "<script>alert('Sila isi semua ruangan.'); window.location.href='index.php';</script>";
        exit;
    }

    // Query the database for user
    $stmt = $conn->prepare("SELECT * FROM pengguna WHERE no_kp = ?");
    $stmt->bind_param("s", $no_kp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Compare plain 4-digit password
        if ($kata_laluan === $user['kata_laluan']) {
            // Store session data
            $_SESSION['no_kp'] = $user['no_kp'];
            $_SESSION['nama_penuh'] = $user['nama_penuh'];
            $_SESSION['jawatan'] = $user['jawatan'];

            // CHECK HARDWARE STATUS FOR NON-WARDENS
            if ($user['jawatan'] !== 'Warden') {
                $hw_stmt = $conn->prepare("SELECT device_id FROM esp32_devices WHERE TIMESTAMPDIFF(SECOND, last_seen, NOW()) < 60 LIMIT 1");
                $hw_stmt->execute();
                $hw_result = $hw_stmt->get_result();
                
                if ($hw_result->num_rows === 0) {
                    session_destroy(); // Kill session immediately
                    echo "<script>alert('Maaf, sistem (IoT) sedang OFFLINE. Log masuk hanya dibenarkan apabila sistem online.'); window.location.href='index.php';</script>";
                    exit;
                }
                $hw_stmt->close();
            }

            // Redirect by role
            if ($user['jawatan'] === 'Warden') {
                header("Location: warden/utama.php");
            } elseif ($user['jawatan'] === 'AJK') {
                header("Location: ajk/utama.php");
            } else {
                header("Location: penghuni/utama.php");
            }
            exit;
        } else {
            echo "<script>alert('No KP atau kata laluan salah.'); window.location.href='index.php';</script>";
            exit;
        }
    } else {
        echo "<script>alert('Pengguna tidak ditemui.'); window.location.href='index.php';</script>";
        exit;
    }
}
?>
