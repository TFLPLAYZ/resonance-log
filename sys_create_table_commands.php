<?php
// sys_create_table_commands.php
require_once 'db_connect.php';

$sql = "CREATE TABLE IF NOT EXISTS `sensor_commands` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `device_id` VARCHAR(50) DEFAULT NULL,
  `command_type` ENUM('delete_fingerprint', 'delete_all') NOT NULL,
  `parameter` VARCHAR(255) NOT NULL,
  `status` ENUM('pending', 'fetched', 'completed', 'failed') DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

if ($conn->query($sql) === TRUE) {
    echo "Table 'sensor_commands' created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
