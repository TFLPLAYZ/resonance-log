-- Resonance Log - Fresh Installation Script
-- This script wipes existing attendance data and recreates the tables correctly.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Drop existing tables in reverse order of dependencies
DROP TABLE IF EXISTS `log_keberadaan`;
DROP TABLE IF EXISTS `cap_jari`;
DROP TABLE IF EXISTS `scanner_activations`;
DROP TABLE IF EXISTS `esp32_devices`;
DROP TABLE IF EXISTS `sensor_commands`;
DROP TABLE IF EXISTS `pengguna`;

-- 1. Table structure for table `pengguna`
CREATE TABLE `pengguna` (
  `no_kp` varchar(14) NOT NULL,
  `nama_penuh` varchar(255) NOT NULL,
  `kata_laluan` varchar(255) NOT NULL,
  `dorm` varchar(10) DEFAULT NULL,
  `kohort` int(4) DEFAULT NULL,
  `kelas` enum('IPD','ISK','MTK 1','MTK 2','MPI 1','MPI 2') DEFAULT NULL,
  `jawatan` enum('Pelajar','AJK','Warden') NOT NULL DEFAULT 'Pelajar',
  `status` enum('Aktif','Cuti Sem') NOT NULL DEFAULT 'Aktif',
  `no_telefon` varchar(20) DEFAULT NULL,
  `no_telefon_penjaga` varchar(20) DEFAULT NULL,
  `alamat_rumah` text DEFAULT NULL,
  `tarikh_dicipta` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`no_kp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Table structure for table `cap_jari`
CREATE TABLE `cap_jari` (
  `id_cap_jari` int(11) NOT NULL AUTO_INCREMENT,
  `no_kp_pengguna` varchar(14) DEFAULT NULL,
  `fingerprint_data` text DEFAULT NULL COMMENT 'Legacy/Metadata',
  `fingerprint_template` TEXT DEFAULT NULL COMMENT 'Hybrid mapping string: SENSOR_ID_X_TS_...',
  `dicipta_pada` timestamp NOT NULL DEFAULT current_timestamp(),
  `dikemaskini_pada` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_cap_jari`),
  UNIQUE KEY `no_kp_pengguna_unik` (`no_kp_pengguna`),
  CONSTRAINT `fk_cap_jari_pengguna` FOREIGN KEY (`no_kp_pengguna`) REFERENCES `pengguna` (`no_kp`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Table structure for table `log_keberadaan`
CREATE TABLE `log_keberadaan` (
  `id_log` int(11) NOT NULL AUTO_INCREMENT,
  `id_cap_jari` int(11) DEFAULT NULL,
  `no_kp_pengguna` varchar(14) NOT NULL,
  `masa_imbasan` timestamp NOT NULL DEFAULT current_timestamp(),
  `status_keberadaan` enum('Hadir','Lewat','Tidak Hadir','Kecemasan') NOT NULL,
  `catatan` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_log`),
  CONSTRAINT `fk_log_keberadaan_pengguna` FOREIGN KEY (`no_kp_pengguna`) REFERENCES `pengguna` (`no_kp`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Table structure for table `esp32_devices`
CREATE TABLE `esp32_devices` (
  `device_id` VARCHAR(50) PRIMARY KEY,
  `mac_address` VARCHAR(17) UNIQUE,
  `ip_address` VARCHAR(45),
  `last_seen` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` ENUM('online', 'offline') DEFAULT 'offline'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Table structure for table `scanner_activations`
CREATE TABLE `scanner_activations` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `no_kp_pengguna` VARCHAR(14) NOT NULL,
  `activation_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('pending', 'processing', 'completed', 'timeout') DEFAULT 'pending',
  `completed_time` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Initial User Data (Admin context)
INSERT INTO `pengguna` (`no_kp`, `nama_penuh`, `kata_laluan`, `jawatan`, `status`) VALUES
('060556330336', 'Warden', '0336', 'Warden', 'Aktif');

COMMIT;
