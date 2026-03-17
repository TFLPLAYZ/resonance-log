-- =====================================================
-- Database Migration Script
-- Resonance Log - Server-Side Fingerprint Storage
-- =====================================================
-- 
-- This script adds the fingerprint_template column to
-- the cap_jari table for server-side fingerprint matching.
--
-- IMPORTANT: Run this BEFORE deploying the new ESP32 firmware
--
-- Date: 2026-02-08
-- Version: 2.0
-- =====================================================

USE resonance_log_db;

-- Add fingerprint_template column to cap_jari table
ALTER TABLE `cap_jari` 
  ADD COLUMN IF NOT EXISTS `fingerprint_template` BLOB DEFAULT NULL 
  COMMENT 'Raw AS608 fingerprint template (512 bytes) for server-side matching' 
  AFTER `fingerprint_data`;

-- Add index for efficient template lookups
ALTER TABLE `cap_jari`
  ADD INDEX IF NOT EXISTS `idx_template_lookup` (`no_kp_pengguna`, `fingerprint_template`(100));

-- Update fingerprint_data column comment
ALTER TABLE `cap_jari` 
  MODIFY COLUMN `fingerprint_data` TEXT DEFAULT NULL 
  COMMENT 'Legacy: Placeholder fingerprint identifier';

-- Display confirmation
SELECT 'Migration completed successfully!' AS Status;
SELECT 'New column: fingerprint_template (BLOB)' AS Change1;
SELECT 'New index: idx_template_lookup' AS Change2;

-- Show updated table structure
DESCRIBE cap_jari;
