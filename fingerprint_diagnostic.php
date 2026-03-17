<?php
session_start();
require_once 'db_connect.php';

// Check if user is Warden
if (!isset($_SESSION['jawatan']) || $_SESSION['jawatan'] !== 'Warden') {
    die("Access denied. Warden only.");
}

$message = "";

// Handle fingerprint deletion from sensor
if (isset($_POST['delete_sensor_id'])) {
    $sensor_id = intval($_POST['delete_sensor_id']);
    // This would need to be implemented in the firmware
    $message = "To delete fingerprint ID $sensor_id from the sensor, you need to implement a delete command in the firmware.";
}

// Get all fingerprints from database
$query = "SELECT c.id_cap_jari, c.no_kp_pengguna, c.fingerprint_data, c.dicipta_pada, c.dikemaskini_pada, p.nama_penuh, p.jawatan 
          FROM cap_jari c 
          LEFT JOIN pengguna p ON c.no_kp_pengguna = p.no_kp 
          ORDER BY c.id_cap_jari DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fingerprint Diagnostic - Resonance Log</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: 500;
        }
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .sensor-id {
            background: #667eea;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
            display: inline-block;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        .btn {
            padding: 8px 16px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .solution-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .solution-box h3 {
            color: #1976D2;
            margin-bottom: 10px;
        }
        .solution-box ol {
            margin-left: 20px;
        }
        .solution-box li {
            margin: 10px 0;
            line-height: 1.6;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-fingerprint"></i> Fingerprint Diagnostic Tool</h1>
        <p style="color: #666; margin-bottom: 20px;">Diagnose and fix fingerprint synchronization issues between sensor and database</p>

        <?php if ($message): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Current Issue:</strong> ESP32 sensor has fingerprints (IDs: 1, 7) that are not registered in the database.
        </div>

        <div class="solution-box">
            <h3><i class="fas fa-tools"></i> How to Fix This Issue</h3>
            <ol>
                <li><strong>Clear all fingerprints from the sensor:</strong>
                    <ul>
                        <li>You need to add a delete function to your firmware or use a separate Arduino sketch to clear the sensor</li>
                        <li>Or manually delete each ID using the sensor's delete command</li>
                    </ul>
                </li>
                <li><strong>Re-register users through the web interface:</strong>
                    <ul>
                        <li>AJK users: Visit <code>ajk/daftar_fingerprint.php</code></li>
                        <li>Penghuni users: Visit <code>penghuni/daftar_fingerprint.php</code></li>
                        <li>This will properly sync the sensor ID with the database</li>
                    </ul>
                </li>
            </ol>
        </div>

        <h2 style="margin-top: 30px; color: #2c3e50;"><i class="fas fa-database"></i> Database Records</h2>
        
        <?php if ($result && $result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>DB ID</th>
                        <th>Sensor ID</th>
                        <th>No. K/P</th>
                        <th>Nama</th>
                        <th>Jawatan</th>
                        <th>Fingerprint Data</th>
                        <th>Dicipta</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): 
                        // Extract sensor ID from fingerprint_data (format: FP_1_123456)
                        $sensor_id = "N/A";
                        if (preg_match('/FP_(\d+)_/', $row['fingerprint_data'], $matches)) {
                            $sensor_id = $matches[1];
                        }
                    ?>
                        <tr>
                            <td><?= $row['id_cap_jari'] ?></td>
                            <td><span class="sensor-id"><?= $sensor_id ?></span></td>
                            <td><?= htmlspecialchars($row['no_kp_pengguna']) ?></td>
                            <td><?= htmlspecialchars($row['nama_penuh'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($row['jawatan'] ?? 'N/A') ?></td>
                            <td><code><?= htmlspecialchars(substr($row['fingerprint_data'], 0, 30)) ?>...</code></td>
                            <td><?= date('d/m/Y H:i', strtotime($row['dicipta_pada'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-inbox" style="font-size: 48px; opacity: 0.3; margin-bottom: 15px;"></i>
                <p>No fingerprints registered in database yet.</p>
                <p style="margin-top: 10px;">Users need to register through the web interface first.</p>
            </div>
        <?php endif; ?>

        <div style="margin-top: 30px; text-align: center;">
            <a href="warden/utama.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
