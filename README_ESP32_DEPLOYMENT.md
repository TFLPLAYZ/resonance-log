# ESP32 Fingerprint Attendance System - Deployment Guide

## 📋 Prerequisites

### Hardware
- ESP32 Development Board
- AS608 Fingerprint Sensor
- Powerbank (for portable operation)
- Jumper wires

### Software
- Arduino IDE with ESP32 board support
- Required Libraries:
  - Adafruit Fingerprint Sensor Library
  - WiFi (built-in)
  - HTTPClient (built-in)

## 🔌 Hardware Wiring

Connect AS608 to ESP32:
```
AS608 VCC  → ESP32 3.3V
AS608 GND  → ESP32 GND
AS608 TX   → ESP32 GPIO 16 (RX)
AS608 RX   → ESP32 GPIO 17 (TX)
```

Built-in LED on GPIO 2 will indicate status.

## 💾 Database Setup

1. Open phpMyAdmin or MySQL command line
2. Select your `resonance_log_db` database
3. Run the SQL script:
```bash
mysql -u root -p resonance_log_db < database_updates.sql
```

Or manually execute `database_updates.sql` in phpMyAdmin.

This creates two new tables:
- `esp32_devices` - Tracks connected ESP32 devices
- `scanner_activations` - Manages activation queue

## 🌐 Server Configuration

### For Local Testing (XAMPP)

1. Ensure XAMPP is running (Apache + MySQL)
2. Files should be in: `C:\xampp\htdocs\resonance-log\`
3. Access via: `http://localhost/resonance-log/`

### For Online Deployment (https://resonance-log.pta-smart.my/)

1. Upload all files via FTP/FileZilla to your web server
2. Update `config.php` with live database credentials
3. Update `db_connect.php` with live database credentials
4. Ensure HTTPS is enabled (SSL certificate required)

**Important:** The ESP32 firmware is pre-configured for:
```
SERVER_URL = "https://resonance-log.pta-smart.my"
```

If testing locally, edit `firmware/main_fingerprint/main_fingerprint.ino` line 23:
```cpp
// For local testing:
const char* SERVER_URL = "http://192.168.1.100/resonance-log";
// Replace 192.168.1.100 with your computer's IP address
```

## 📱 ESP32 Firmware Upload

### Step 1: Install Arduino IDE Libraries

1. Open Arduino IDE
2. Go to **Sketch → Include Library → Manage Libraries**
3. Search and install:
   - "Adafruit Fingerprint Sensor Library" by Adafruit
   
### Step 2: Configure ESP32 Board

1. Go to **File → Preferences**
2. Add to "Additional Board Manager URLs":
   ```
   https://raw.githubusercontent.com/espressif/arduino-esp32/gh-pages/package_esp32_index.json
   ```
3. Go to **Tools → Board → Boards Manager**
4. Search "esp32" and install "esp32 by Espressif Systems"
5. Select **Tools → Board → ESP32 Dev Module**

### Step 3: Upload Firmware

1. Open `firmware/main_fingerprint/main_fingerprint.ino`
2. Verify WiFi credentials (lines 17-20):
   ```cpp
   const char* WIFI_SSID_1 = "TFL";
   const char* WIFI_PASS_1 = "va17161366";
   const char* WIFI_SSID_2 = "Kv WiFi Hostel";
   const char* WIFI_PASS_2 = "";
   ```
3. Connect ESP32 via USB
4. Select correct COM port: **Tools → Port → COM X**
5. Click **Upload** button (→)
6. Wait for "Done uploading" message

### Step 4: Monitor Serial Output

1. Open **Tools → Serial Monitor**
2. Set baud rate to **115200**
3. You should see:
   ```
   =================================
   ESP32 Fingerprint Attendance System
   =================================
   
   MAC Address: XX:XX:XX:XX:XX:XX
   ✅ AS608 Fingerprint Sensor detected!
   ✅ Connected to TFL
   IP Address: 192.168.X.X
   ✅ System ready - entering polling mode
   ```

## 🚀 System Usage

### For AJK Users

1. Login to system as AJK
2. Navigate to **Daftar / Imbas Fingerprint**
3. Click **"Mula Imbas Cap Jari"** or **"Kemaskini Cap Jari"**
4. Place finger on AS608 sensor (on ESP32 hardware)
5. Wait for confirmation message
6. Fingerprint is now registered!

### For Penghuni Users

1. Login to system as Penghuni
2. Navigate to **Daftar / Imbas Fingerprint**
3. Check hardware status (should show "Hardware Dalam Talian")
4. Click **"Mula Imbasan"**
5. Place finger on AS608 sensor
6. Attendance will be recorded with appropriate status:
   - **Before 6pm**: Hadir
   - **After 6pm**: Lewat
   - **After 10pm**: Tidak Hadir (automatic)

## ⏰ Attendance Rules

| Time | Status | Notes |
|------|--------|-------|
| **Sunday** | | |
| Before 18:00 (6pm) | **Hadir** | On time |
| After 18:00 (6pm) | **Lewat** | Late |
| **Monday - Saturday** | | |
| Before 10:00 (10am) | **Hadir** | On time |
| After 10:00 (10am) | **Lewat** | Late |
| **Any Day** | | |
| After 22:00 (10pm) | **Tidak Hadir** | Scanner disabled, auto-marked absent |

## 🆕 Server-Side Storage (Version 2.0)

**IMPORTANT CHANGE**: The system now uses **server-side fingerprint storage and matching**.

### What Changed:
- ✅ ESP32 **NO LONGER stores** fingerprints locally
- ✅ All fingerprint templates are **extracted and sent to the server**
- ✅ Server performs **matching against database** templates
- ✅ Faster enrollment (no local storage delays)
- ✅ Centralized fingerprint management

### Migration Required:
1. **Clear ESP32 sensor** - All local fingerprints must be deleted
2. **Run database migration** - Execute `database_migration_server_side.sql`
3. **Upload new firmware** - Flash `main_fingerprint.ino` version 2.0
4. **Re-enroll all users** - Users must register fingerprints again via web interface

### Technical Details:
- Fingerprint templates are extracted as **512-byte binary data**
- Templates are **base64-encoded** for HTTP transmission
- Server uses **byte-by-byte comparison** with 70% threshold
- Match confidence score is logged with each attendance record

## 🔧 Troubleshooting

### ESP32 Won't Connect to WiFi

1. Check WiFi credentials in firmware
2. Ensure WiFi network is available
3. Try moving ESP32 closer to router
4. Check Serial Monitor for error messages

### Fingerprint Sensor Not Detected

1. Verify wiring connections
2. Check AS608 power (should have red LED on)
3. Try swapping TX/RX connections
4. Ensure sensor is AS608 model (57600 baud)

### Hardware Shows "Offline" on Website

1. Check ESP32 is powered on
2. Verify WiFi connection (check Serial Monitor)
3. Ensure server URL is correct
4. Check database `esp32_devices` table for heartbeat

### Fingerprint Enrollment Fails

1. Ensure finger is clean and dry
2. Press firmly on sensor
3. Use same finger for both scans
4. Wait for LED indicators
5. Check timeout (30 seconds)

## 📊 Testing Checklist

- [ ] Database tables created successfully
- [ ] ESP32 connects to WiFi (TFL or Kv WiFi Hostel)
- [ ] Hardware status shows "Online" on website
- [ ] AJK can register fingerprint
- [ ] Penghuni can register fingerprint
- [ ] Attendance logged correctly before 6pm (Hadir)
- [ ] Attendance logged correctly after 6pm (Lewat)
- [ ] Scanner disabled after 10pm
- [ ] System works on powerbank (portable)
- [ ] Online server deployment successful

## 🌐 WiFi Configuration

The system supports dual WiFi with automatic fallback:

1. **Primary**: TFL (password: va17161366)
2. **Secondary**: Kv WiFi Hostel (no password)

ESP32 will try primary first, then automatically switch to secondary if primary fails.

## 📞 Support

For issues or questions:
1. Check Serial Monitor output for detailed logs
2. Verify database entries in `scanner_activations` table
3. Check Apache error logs for PHP issues
4. Review browser console for JavaScript errors

## 🎯 System Architecture

```
┌─────────────┐         ┌──────────────┐         ┌─────────────┐
│   Web User  │────────▶│  Web Server  │◀────────│   ESP32     │
│ (AJK/Penghuni)│         │   (PHP/API)  │         │ + AS608     │
└─────────────┘         └──────────────┘         └─────────────┘
                              │
                              ▼
                        ┌──────────────┐
                        │   Database   │
                        │    (MySQL)   │
                        └──────────────┘
```

**Flow:**
1. User clicks "Activate Scanner" on website
2. API creates activation record in database
3. ESP32 polls API every 5 seconds
4. ESP32 receives activation command
5. ESP32 captures fingerprint via AS608
6. ESP32 reports back to API
7. API updates `cap_jari` and `log_keberadaan` tables
8. Website shows success message

---

**Version:** 1.0  
**Date:** 2026-01-30  
**System:** Resonance Log - PTA Smart
