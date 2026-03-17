<?php
session_start();
require_once 'config.php'; // make sure this file exists

if (isset($_POST['no_kp'], $_POST['kata_laluan'])) {
    $no_kp = $_POST['no_kp'];
    $kata_laluan = $_POST['kata_laluan'];

    $stmt = $pdo->prepare("SELECT * FROM pengguna WHERE no_kp = ?");
    $stmt->execute([$no_kp]);
    $user = $stmt->fetch();

    if(password_verify($input_password, $db_password_hash)) {
        // Login successful
        $_SESSION['no_kp'] = $user['no_kp'];
        $_SESSION['nama_penuh'] = $user['nama_penuh'];
        header('Location: dashboard.php');
        exit;
    } else {
        // Login failed
        echo "No KP or password is incorrect.";
    }
} else {
    echo "Please fill in both fields.";
}
?>
