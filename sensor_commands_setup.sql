-- Run this in your Database (phpMyAdmin)
CREATE TABLE IF NOT EXISTS `sensor_commands` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `device_id` VARCHAR(50) DEFAULT NULL COMMENT 'Target specific device or NULL for any',
  `command_type` ENUM('delete_fingerprint', 'delete_all') NOT NULL,
  `parameter` VARCHAR(255) NOT NULL COMMENT 'The value for the command, e.g. the ID to delete',
  `status` ENUM('pending', 'fetched', 'completed', 'failed') DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
