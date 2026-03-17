# ESP32 Server-Side Storage - Quick Deployment Guide

## 🚀 Quick Start (5 Steps)

### 1️⃣ Backup Everything
```bash
mysqldump -u root -p resonance_log_db > backup.sql
```

### 2️⃣ Run Database Migration
```bash
mysql -u root -p resonance_log_db < database_migration_server_side.sql
```

### 3️⃣ Upload PHP Files
- Upload `includes/fingerprint_matcher.php` (NEW)
- Upload `api/match_fingerprint.php` (NEW)
- Replace `api/esp32_report_scan.php`
- Replace `api/esp32_attendance.php`

### 4️⃣ Flash ESP32 Firmware
1. Open `firmware/main_fingerprint/main_fingerprint.ino`
2. Upload to ESP32
3. Verify Serial Monitor shows: "Version 2.0 - Server-Side Storage"

### 5️⃣ Re-Enroll All Users
- All users must register fingerprints again via web interface
- Old fingerprints on ESP32 sensor are no longer used

---

## ⚠️ Important Notes

- **Breaking Change**: All existing fingerprints must be re-enrolled
- **No Rollback**: Once migrated, cannot revert to old firmware without data loss
- **Testing**: Test with 2-3 users before full deployment

---

## ✅ Verification Checklist

- [ ] Database has `fingerprint_template` column
- [ ] ESP32 shows "Version 2.0" in Serial Monitor
- [ ] Test enrollment works (template stored in database)
- [ ] Test attendance scanning works (server-side matching)
- [ ] Confidence scores appear in attendance logs

---

## 📞 Support

Check `walkthrough.md` for detailed troubleshooting and technical details.
