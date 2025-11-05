# ESP32 Malanday Sensor - Quick Setup Guide

## ✅ Files Fixed

1. **config/db.php** - Added `get_db_connection()` function
2. **ESP32_Malanday_Sensor.ino** - Cleaned up Arduino code for ESP32
3. All API files now working without errors

## 🚀 Setup Steps

### 1. Upload ESP32 Code

1. Open **ESP32_Malanday_Sensor.ino** in Arduino IDE
2. Update WiFi credentials (lines 7-8):
   ```cpp
   const char* ssid = "YOUR_WIFI_NAME";
   const char* password = "YOUR_WIFI_PASSWORD";
   ```
3. Select board: **ESP32 Dev Module**
4. Upload to ESP32
5. Open Serial Monitor (115200 baud) to see the IP address

### 2. Setup Database

Run this command in MySQL or phpMyAdmin:

```bash
# Using MySQL command line
mysql -u root -p
```

```sql
-- In MySQL prompt
USE user_db;
SOURCE C:/xampp/htdocs/ncp3106-projectGoMarikina/docs/sql/007_create_sensor_data.sql;
```

Or in phpMyAdmin:
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Select `user_db` database
3. Go to SQL tab
4. Copy and paste contents of `docs/sql/007_create_sensor_data.sql`
5. Click Go

### 3. Update ESP32 IP Address

After uploading code to ESP32, note the IP address from Serial Monitor.

Update in `api/get_sensor_data.php` (line 9):
```php
$default_ip = '172.20.10.3'; // Change to your ESP32 IP
```

### 4. Test the System

#### Test ESP32 directly:
```
http://[ESP32_IP]/api/data
```
Example: http://172.20.10.3/api/data

#### Test through XAMPP proxy:
```
http://localhost/ncp3106-projectGoMarikina/api/get_sensor_data.php?barangay=Malanday
```

#### View sensor history:
```
http://localhost/ncp3106-projectGoMarikina/api/get_sensor_history.php?barangay=Malanday&limit=10
```

## 📊 ESP32 Pin Configuration

| Component | Pin | Notes |
|-----------|-----|-------|
| DHT22 Data | GPIO 4 | Temperature & Humidity |
| Float Sensor 1 | GPIO 13 | Gutter level |
| Float Sensor 2 | GPIO 14 | Knee level |
| Float Sensor 3 | GPIO 27 | Waist level |
| MQ135 Gas | GPIO 35 | Air quality sensor |
| Buzzer | GPIO 19 | Flood alert |
| LED | GPIO 23 | Visual alert |
| I2C SDA | GPIO 21 | LCD display |
| I2C SCL | GPIO 22 | LCD display |

## 🔧 Troubleshooting

### ESP32 won't connect to WiFi
- Check WiFi credentials
- Make sure WiFi is 2.4GHz (ESP32 doesn't support 5GHz)
- Check if WiFi has internet access

### "Database connection unavailable" error
- Start MySQL in XAMPP Control Panel
- Verify database name is `user_db`
- Check if table `sensor_data` exists

### No data from ESP32
- Verify ESP32 IP address is correct
- Make sure ESP32 and computer are on same network
- Check ESP32 Serial Monitor for errors
- Try accessing ESP32 directly: http://[ESP32_IP]/

### LCD not displaying
- Check I2C address (default 0x27)
- Verify wiring: SDA to GPIO 21, SCL to GPIO 22
- Test with I2C scanner sketch

## 📱 Flood Level Detection

| Level | Sensors | Water % | Alert |
|-------|---------|---------|-------|
| No Flood | 0 | 0% | None |
| Level 1 | 1 | 33% | Buzzer + LED (5s) |
| Level 2 | 2 | 66% | Buzzer + LED (5s) |
| Level 3 | 3 | 100% | Buzzer + LED (5s) |

## 🌐 API Endpoints

### Get Current Data
```
GET /api/get_sensor_data.php?barangay=Malanday
```

### Save Data
```
POST /api/save_sensor_data.php
Content-Type: application/json

{
  "barangay": "Malanday",
  "temperature": 28.5,
  "humidity": 65.0,
  "waterPercent": 0,
  "floodLevel": "No Flood",
  "airQuality": 120
}
```

### Get History
```
GET /api/get_sensor_history.php?barangay=Malanday&limit=50
GET /api/get_sensor_history.php?latest=true
```

## ✨ Features

✅ Real-time temperature & humidity monitoring  
✅ 3-level flood detection system  
✅ Air quality monitoring (MQ135)  
✅ Automatic buzzer & LED alerts  
✅ 16x4 LCD display with auto-rotation  
✅ Web dashboard (ESP32 hosted)  
✅ JSON API for database integration  
✅ Automatic data logging to MySQL  
✅ Historical data retrieval  

## 📞 Support

Check the full documentation in:
- `docs/SENSOR_DATABASE_SETUP.md`
- `docs/sql/007_create_sensor_data.sql`

Happy monitoring! 🚀
