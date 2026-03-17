-- Resonance Log - Consolidated SQL Script
-- Includes all original user data and requirements for the Hybrid "Server-Side Matching" system.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Clean Installation: Drop existing tables if they exist
--
DROP TABLE IF EXISTS `log_keberadaan`;
DROP TABLE IF EXISTS `cap_jari`;
DROP TABLE IF EXISTS `scanner_activations`;
DROP TABLE IF EXISTS `esp32_devices`;
DROP TABLE IF EXISTS `sensor_commands`;
DROP TABLE IF EXISTS `pengguna`;

-- --------------------------------------------------------

--
-- Table structure for table `pengguna`
--

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

--
-- Dumping original user data
--

INSERT INTO `pengguna` (`no_kp`, `nama_penuh`, `kata_laluan`, `dorm`, `kohort`, `kelas`, `jawatan`, `status`, `no_telefon`, `no_telefon_penjaga`, `alamat_rumah`, `tarikh_dicipta`) VALUES
('060000020001', 'MUHAMMAD HAFIZ IKHWAN BIN JOHAIRY', '0001', 'G2', 2025, 'MPI 1', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 14:59:24'),
('060000020002', 'AFIQ DANISH BIN RAHIMEE', '0002', 'G2', 2025, 'MPI 2', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 14:59:24'),
('060000020003', 'MUHAMMAD HARIS BIN AZLYNAWAR', '0003', 'G2', 2025, 'MPI 2', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 14:59:24'),
('060000020004', 'MUHAMMAD AISY RAYYAN BIN AMIRULLAH', '0004', 'G2', 2025, 'MPI 2', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 14:59:24'),
('060000020005', 'MUHAMMAD A\'TIFF EZZANY BIN MOHD AROWAN ZAMRI', '0005', 'G2', 2025, 'MTK 2', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 14:59:24'),
('060000020006', 'MUHAMMAD EUSUFF HAKIEM BIN MAIZOEL IKHWAN', '0006', 'G2', 2025, 'MPI 1', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 14:59:24'),
('060000020007', 'MUHAMMAD AMMAR IMAN BIN ZULKIFLI', '0007', 'G2', 2025, 'MTK 2', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 14:59:24'),
('060000020008', 'NURI IMAN UBAIDULLAH BIN YADI', '0008', 'G2', 2025, 'MPI 1', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 14:59:24'),
('060000020009', 'MUHAMMAD THAQIF BIN ABDUL RAHIM', '0009', 'G2', 2025, 'MPI 1', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 14:59:24'),
('060000020010', 'ALIF HAIKAL BIN SYAHRIL RIDHUAN', '0010', 'G2', 2025, 'MPI 1', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 14:59:24'),
('060000020011', 'AFFANDI WAFI BIN ZULKHAIRI', '0011', 'G2', 2025, 'MPI 2', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 14:59:24'),
('060000030001', 'ABU BAKAR WAFIY BIN MOHD NIZAM', '0001', 'G3', 2024, 'MTK 1', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:03:10'),
('060000030002', 'ADAM DANIAL BIN MUSRATAMIZE', '0002', 'G3', 2024, 'MTK 1', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:03:10'),
('060000030003', 'NIK MUHAMMAD ALIF DANIAL BIN NIK MOHD ADAM', '0003', 'G3', 2024, 'MPI 1', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:03:10'),
('060000030004', 'RIFQI ZUHAIRI BIN AHMAD ZAMIRUDIN', '0004', 'G3', 2024, 'MPI 1', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:03:10'),
('060000030005', 'EDRY DANISH BIN KHAIRUL HISAM', '0005', 'G3', 2024, 'MTK 1', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:03:10'),
('060000030006', 'SYED MUHAMMAD IRFAN HAIQAL BIN SYED SALEM', '0006', 'G3', 2024, 'MTK 2', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:03:10'),
('060000030007', 'SHAZRIL ADAM BIN SHAMSUAR', '0007', 'G3', 2024, 'MTK 1', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:03:10'),
('060000030008', 'MUHAMMAD AMIRUL SHUQRY BIN ABDUL SAHLI', '0008', 'G3', 2024, 'MPI 2', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:03:10'),
('060000030009', 'ALIFF DANIEL BIN ADENAN', '0009', 'G3', 2024, 'MTK 1', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:03:10'),
('060000030010', 'MUHAMMAD DANISH HADIF BIN HILMEE', '0010', 'G3', 2024, 'MTK 2', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:03:10'),
('060000040001', 'MUHAMMAD RYAN AISY BIN REEZAL', '0001', 'G4', 2024, 'IPD', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:14:19'),
('060000040002', 'MUHAMMAD HAZIQ WAFI BIN HASMIZAI', '0002', 'G4', 2024, 'IPD', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:14:19'),
('060000040003', 'MUHAMMAD HADIF BIN MOHD HAZWAN', '0003', 'G4', 2024, 'ISK', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:14:19'),
('060000040004', 'MUHAMMAD IMRAN HARITH BIN KHAIRUL NIZYAM', '0004', 'G4', 2024, 'ISK', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:14:19'),
('060000040005', 'RIEAN AYRIESY BIN YUSRIE', '0005', 'G4', 2024, 'MTK 2', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:14:19'),
('060000040006', 'MUHAMAD SYAFIQ DANIAL BIN KAHAIRUL HARIZALL', '0006', 'G4', 2024, 'MTK 2', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:14:19'),
('060000040007', 'YUSRIN YUSUF BIN YUSMADI', '0007', 'G4', 2024, 'ISK', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:14:19'),
('060000040008', 'ALIF ZAQUAN BIN REHAN', '0008', 'G4', 2024, 'MTK 1', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:14:19'),
('060000040009', 'WAN MUHAMMAD IZZUL FAHMI BIN MOHD HAIL IZANI', '0009', 'G4', 2024, 'ISK', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:14:19'),
('060000040010', 'MUHAMMAD SYAFIQ DANISH BIN MOHAMAD', '0010', 'G4', 2024, 'ISK', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:14:19'),
('060000050001', 'AHMAD AQIL FARISHAH BIN ZAIRUL YAZID', '0001', 'G5', 2024, 'MPI 2', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:19:09'),
('060000050002', 'MUHAMMAD AMIRUL NURIMAN BIN NORAZAM', '0002', 'G5', 2024, 'MPI 2', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:19:09'),
('060000050003', 'MUHAMMAD IRFAN BIN RUZI', '0003', 'G5', 2024, 'MPI 2', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:19:09'),
('060000050004', 'MOHAMAD HARIS FAKHRI BIN MOHD FAIRUZ', '0004', 'G5', 2024, 'MTK 2', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:19:09'),
('060000050005', 'MUHAMMAD NIZA DARWISY BIN EHWAN ZAINI', '0005', 'G5', 2024, 'MPI 2', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:19:09'),
('060000050006', 'SHAFIRUL SHAZWAN BIN MOHD RIZAL', '0006', 'G5', 2024, 'MPI 2', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:19:09'),
('060000070001', 'AHMAD NAQUIB MUAZ BIN YUSOF', '0001', 'G7', 2024, 'MPI 2', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:27:03'),
('060000070002', 'MUHAMMAD ZARIF HAIKAL BIN MUHAMAD', '0002', 'G7', 2024, 'MPI 2', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:27:03'),
('060000070003', 'MOHAMAD AZRIZAM HAFIZ BIN SAFIE', '0003', 'G7', 2024, 'MPI 2', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:27:03'),
('060000070004', 'MUHAMMAD DANIEL ZAFFRAN BIN NOR AZLAN', '0004', 'G7', 2024, 'MPI 2', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:27:03'),
('060000070005', 'MUHAMMAD HASIIF BIN MOHD RAZALI', '0005', 'G7', 2024, 'MTK 2', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:27:03'),
('060000070006', 'MUHAMAD DAAIIE BIN AHMAD ARIF DAHLAN', '0006', 'G7', 2024, 'MTK 2', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:27:03'),
('060000070007', 'MUHAMAD ILYAS BIN RAZMAN SHAH', '0007', 'G7', 2024, 'MTK 1', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:27:03'),
('060000080001', 'MUHAMMAD JAFFRIELL AISY BIN MOHAMMAD JINNIS', '0001', 'G8', 2024, 'MTK 1', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:28:33'),
('060000080002', 'MUHAMMAD MIRZA SYAHMI BIN AZIM RAFIQ', '0002', 'G8', 2024, 'MTK 1', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:28:33'),
('060000080003', 'MUHAMMAD ILYAS BIN RASDAN', '0003', 'G8', 2024, 'MTK 2', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:28:33'),
('060000080004', 'NAZRUL AQIL HAIQAL BIN NAZRUL IZWAN', '0004', 'G8', 2024, 'MTK 2', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:28:33'),
('060000080005', 'MUHAMMAD AQIF LUQHMAN BIN ABD LATIF', '0005', 'G8', 2024, 'MTK 2', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:28:33'),
('060000080006', 'MUHAMMAD FARISH RAHIMI BIN ISMA ZAIMEI', '0006', 'G8', 2024, 'MTK 2', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:28:33'),
('060000080007', 'MUHAMMAD DANIAL IEMAN BIN MOHD YUNUS', '0007', 'G8', 2024, 'MTK 1', 'Pelajar', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:28:33'),
('060225160123', 'Ammar Danish Bin Mohd Redza Azhab', '0123', 'G1', 2024, 'IPD', 'AJK', 'Aktif', '0127392502', '0133961510', 'No.1 Jalan 1/9H, Seksyen 1, Bandar Baru Bangi, Selangor', '2026-01-12 06:48:28'),
('060556330336', 'Warden', '0336', NULL, NULL, NULL, 'Warden', 'Aktif', NULL, NULL, NULL, '2026-01-13 15:29:35');

-- --------------------------------------------------------

--
-- Table structure for table `cap_jari`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `log_keberadaan`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `esp32_devices`
--

CREATE TABLE IF NOT EXISTS `esp32_devices` (
  `device_id` VARCHAR(50) PRIMARY KEY,
  `mac_address` VARCHAR(17) UNIQUE,
  `ip_address` VARCHAR(45),
  `last_seen` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` ENUM('online', 'offline') DEFAULT 'offline'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scanner_activations`
--

CREATE TABLE IF NOT EXISTS `scanner_activations` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `no_kp_pengguna` VARCHAR(14) NOT NULL,
  `activation_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('pending', 'processing', 'completed', 'timeout') DEFAULT 'pending',
  `completed_time` TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (`no_kp_pengguna`) REFERENCES `pengguna`(`no_kp`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sensor_commands`
--

CREATE TABLE IF NOT EXISTS `sensor_commands` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `device_id` VARCHAR(50) DEFAULT NULL COMMENT 'Target specific device or NULL for any',
  `command_type` ENUM('delete_fingerprint', 'delete_all') NOT NULL,
  `parameter` VARCHAR(255) NOT NULL COMMENT 'The value for the command, e.g. the ID to delete',
  `status` ENUM('pending', 'fetched', 'completed', 'failed') DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- New Hybrid Indexes
--
CREATE INDEX idx_activation_status ON scanner_activations(status, activation_time);
CREATE INDEX idx_device_last_seen ON esp32_devices(last_seen);
CREATE INDEX idx_template_lookup ON cap_jari(fingerprint_template(100));

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
