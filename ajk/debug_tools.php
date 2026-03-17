<?php
session_start();
require_once '../db_connect.php';

// Simple Auth Check (Ensure only Warden/Admin can access)
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'warden') { ... } 
// For now, assuming open or local access as requested for debugging.

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $id_to_delete = $_POST['delete_id'];
        $device_id = $_POST['device_id'] ?: NULL;
        
        $stmt = $conn->prepare("INSERT INTO sensor_commands (device_id, command_type, parameter, status) VALUES (?, 'delete_fingerprint', ?, 'pending')");
        $stmt->bind_param("ss", $device_id, $id_to_delete);
        
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Command queued: Delete ID $id_to_delete</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }
}

// Fetch pending commands
$pending = $conn->query("SELECT * FROM sensor_commands WHERE status IN ('pending', 'fetched') ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sensor Debug Tools</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <h1>🛠 Sensor Debug Tools</h1>
        <hr>
        <?php echo $message; ?>

        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                Remote Fingerprint Deletion
            </div>
            <div class="card-body">
                <p>Use this to delete a stored fingerprint ID from the ESP32 sensor.</p>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Sensor ID to Delete</label>
                        <input type="number" name="delete_id" class="form-control" placeholder="e.g. 1" required>
                        <div class="form-text">This is the internal ID on the AS608 sensor (not the DB ID).</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Target Device ID (Optional)</label>
                        <input type="text" name="device_id" class="form-control" placeholder="e.g. ESP32_FP_001">
                    </div>
                    <button type="submit" class="btn btn-danger">Queue Deletion Command</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                Command Queue
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Command</th>
                            <th>Parameter</th>
                            <th>Status</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $pending->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['command_type']; ?></td>
                            <td><?php echo $row['parameter']; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $row['status'] == 'pending' ? 'warning' : 'info'; ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <td><?php echo $row['created_at']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
