# Sensor Data Database Setup

This guide explains how to set up the database to store IoT sensor data from the ESP32 device deployed in Malanday.

## Database Schema

The `sensor_data` table stores real-time readings from ESP32 IoT devices:

- **Environmental Data**: Temperature, Humidity
- **Flood Detection**: Water level percentage (0, 33, 66, 100) and flood level description
- **Air Quality**: Air quality index, gas sensor readings (analog & voltage)
- **Metadata**: Location (barangay), device IP, status, source, timestamps

## Setup Instructions

### 1. Run the SQL Migration

Execute the SQL file to create the table:

```bash
# Option 1: Using MySQL command line
mysql -u root -p gomarikina_db < docs/sql/007_create_sensor_data.sql

# Option 2: Using phpMyAdmin
# - Open phpMyAdmin
# - Select 'gomarikina_db' database
# - Go to SQL tab
# - Copy and paste contents of 007_create_sensor_data.sql
# - Click 'Go'
```

### 2. Verify Table Creation

Check if the table was created successfully:

```sql
USE gomarikina_db;
SHOW TABLES;
DESCRIBE sensor_data;
```

### 3. Update ESP32 IP Address (if needed)

If your ESP32 has a different IP address, update it in `api/get_sensor_data.php`:

```php
$default_ip = '172.20.10.3'; // Change to your ESP32 IP
```

## API Endpoints

### 1. Get Current Sensor Data
**GET** `/api/get_sensor_data.php?barangay=Malanday`

Fetches current sensor readings from ESP32 and saves to database every 10 minutes.

**Response:**
```json
{
  "temperature": 28.5,
  "humidity": 65.0,
  "waterPercent": 0,
  "floodLevel": "No Flood",
  "airQuality": 120,
  "gasAnalog": 850,
  "gasVoltage": 0.68,
  "barangay": "Malanday",
  "timestamp": "2025-11-04 14:30:00",
  "status": "online",
  "source": "esp32",
  "device_ip": "172.20.10.3"
}
```

### 2. Save Sensor Data
**POST** `/api/save_sensor_data.php`

Manually save sensor data to the database.

**Request Body:**
```json
{
  "barangay": "Malanday",
  "temperature": 28.5,
  "humidity": 65.0,
  "waterPercent": 0,
  "floodLevel": "No Flood",
  "airQuality": 120,
  "gasAnalog": 850,
  "gasVoltage": 0.68,
  "device_ip": "172.20.10.3",
  "status": "online",
  "source": "esp32"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Sensor data saved successfully",
  "id": 123,
  "barangay": "Malanday",
  "timestamp": "2025-11-04 14:30:00"
}
```

### 3. Get Sensor History
**GET** `/api/get_sensor_history.php`

Retrieve historical sensor data from the database.

**Query Parameters:**
- `barangay` - Filter by barangay (e.g., "Malanday")
- `limit` - Number of records (default: 100, max: 1000)
- `latest=true` - Get only the latest reading per barangay
- `from` - Start date/time (YYYY-MM-DD HH:MM:SS)
- `to` - End date/time (YYYY-MM-DD HH:MM:SS)

**Examples:**
```
# Get latest reading for Malanday
/api/get_sensor_history.php?barangay=Malanday&latest=true

# Get last 50 readings
/api/get_sensor_history.php?barangay=Malanday&limit=50

# Get readings from specific time range
/api/get_sensor_history.php?barangay=Malanday&from=2025-11-04 00:00:00&to=2025-11-04 23:59:59
```

**Response:**
```json
{
  "success": true,
  "count": 50,
  "data": [
    {
      "id": 123,
      "barangay": "Malanday",
      "deviceIp": "172.20.10.3",
      "temperature": 28.5,
      "humidity": 65.0,
      "waterPercent": 0,
      "floodLevel": "No Flood",
      "airQuality": 120,
      "gasAnalog": 850,
      "gasVoltage": 0.68,
      "status": "online",
      "source": "esp32",
      "timestamp": "2025-11-04 14:30:00",
      "createdAt": "2025-11-04 14:30:00"
    }
  ]
}
```

## Flood Levels

The system detects 4 flood levels based on float sensors:

| Level | Water Percent | Description | Float Sensors |
|-------|--------------|-------------|---------------|
| No Flood | 0% | Safe conditions | 0 sensors triggered |
| Level 1 | 33% | Gutter Deep | 1 sensor triggered |
| Level 2 | 66% | Knee Deep | 2 sensors triggered |
| Level 3 | 100% | Waist Deep | 3 sensors triggered |

## Data Retention

The system automatically keeps the last 1000 records per barangay to prevent database bloat. Older records are automatically deleted when new data is saved.

## Testing

### Test the API Endpoints

```bash
# Test getting current data
curl "http://localhost/ncp3106-projectGoMarikina/api/get_sensor_data.php?barangay=Malanday"

# Test getting history
curl "http://localhost/ncp3106-projectGoMarikina/api/get_sensor_history.php?barangay=Malanday&limit=10"

# Test saving data (POST)
curl -X POST "http://localhost/ncp3106-projectGoMarikina/api/save_sensor_data.php" \
  -H "Content-Type: application/json" \
  -d '{
    "barangay": "Malanday",
    "temperature": 28.5,
    "humidity": 65.0,
    "waterPercent": 0,
    "floodLevel": "No Flood",
    "airQuality": 120,
    "gasAnalog": 850,
    "gasVoltage": 0.68
  }'
```

## Data Storage Interval

⏱️ **Important:** The system automatically saves sensor data to the database **every 10 minutes** to prevent database bloat. 

- You can call the API as often as you like (e.g., every 5 seconds for real-time display)
- The API will always return current sensor readings from ESP32
- Database storage happens only once every 10 minutes
- This keeps your database size manageable while maintaining historical data

To change the interval, modify line 36 in `api/get_sensor_data.php`:
```php
if (($current_time - $last_timestamp) < 600) {  // 600 = 10 minutes in seconds
```

## Troubleshooting

### Cannot connect to ESP32
- Verify ESP32 is powered on and connected to WiFi
- Check if the IP address in `get_sensor_data.php` matches your ESP32's IP
- Make sure you're on the same network as the ESP32

### Database connection errors
- Verify MySQL service is running (XAMPP Control Panel)
- Check database credentials in `config/db.php`
- Ensure the table was created successfully

### No data being saved
- Check PHP error logs in XAMPP
- Verify the `sensor_data` table exists
- Test the API endpoints using curl or Postman
- Wait 10 minutes between saves (data is stored every 10 minutes)

## View Data in Database

```sql
-- View latest readings
SELECT * FROM sensor_data_latest;

-- View all readings for Malanday
SELECT * FROM sensor_data 
WHERE barangay = 'Malanday' 
ORDER BY reading_timestamp DESC 
LIMIT 10;

-- View flood alerts
SELECT * FROM sensor_data 
WHERE water_percent > 0 
ORDER BY reading_timestamp DESC;

-- View data by time range
SELECT * FROM sensor_data 
WHERE barangay = 'Malanday' 
  AND reading_timestamp >= '2025-11-04 00:00:00'
  AND reading_timestamp <= '2025-11-04 23:59:59'
ORDER BY reading_timestamp DESC;
```

## Integration with Frontend

The sensor data can be displayed on the IoT Dashboard page. The frontend should poll the API every few seconds to get updated readings:

```javascript
async function updateSensorData() {
  const response = await fetch('/api/get_sensor_data.php?barangay=Malanday');
  const data = await response.json();
  
  // Update UI with sensor data
  document.getElementById('temperature').textContent = data.temperature;
  document.getElementById('humidity').textContent = data.humidity;
  document.getElementById('floodLevel').textContent = data.floodLevel;
  // ... etc
}

// Update every 5 seconds
setInterval(updateSensorData, 5000);
```
