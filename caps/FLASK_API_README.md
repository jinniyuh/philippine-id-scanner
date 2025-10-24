# Flask ML API Documentation

## Overview

The Flask ML API provides RESTful endpoints for machine learning predictions and forecasting in the Bago City Veterinary Office Information Management System.

## Architecture

```
┌─────────────────┐
│  PHP Frontend   │ ← Web application, UI, authentication
└────────┬────────┘
         │ HTTP requests
         ↓
┌─────────────────┐
│  Flask API      │ ← ML computations, forecasting
│  (Port 5000)    │   Python ML models
└────────┬────────┘
         │ SQL queries
         ↓
┌─────────────────┐
│  MySQL Database │ ← bagovets / production DB
└─────────────────┘
```

## Installation

### Step 1: Install Python Dependencies

```bash
# Windows
pip install -r requirements.txt

# Linux/Mac
pip3 install -r requirements.txt
```

### Step 2: Verify Installation

```bash
# Test Flask import
python -c "import flask; print(flask.__version__)"

# Should output: 2.3.x or higher
```

## Starting the Flask Server

### Windows

**Option 1: Using Batch File (Recommended)**
```cmd
start_flask.bat
```

**Option 2: Manual Start**
```cmd
python ml_flask_api.py
```

### Linux/Mac

**Option 1: Using Shell Script**
```bash
chmod +x start_flask.sh
./start_flask.sh
```

**Option 2: Manual Start**
```bash
python3 ml_flask_api.py
```

### Expected Output

```
============================================================
ML Flask API Server
============================================================
Starting server on http://localhost:5000
API Endpoints:
  GET  /                  - API info
  GET  /health            - Health check
  GET  /api/insights      - Full ML insights
  POST /api/forecast      - General forecast
============================================================
 * Serving Flask app 'ml_flask_api'
 * Debug mode: on
 * Running on http://0.0.0.0:5000
```

## API Endpoints

### 1. Home / API Info

**Endpoint:** `GET /`

**Description:** Returns API information and available endpoints

**Example:**
```bash
curl http://localhost:5000/
```

**Response:**
```json
{
  "success": true,
  "message": "ML Flask API for Veterinary Management System",
  "version": "1.0.0",
  "endpoints": {
    "GET /": "This help message",
    "GET /health": "Health check",
    "POST /api/forecast": "Generate ML forecasts",
    "GET /api/insights": "Get comprehensive ML insights"
  }
}
```

### 2. Health Check

**Endpoint:** `GET /health`

**Description:** Check if API is running

**Example:**
```bash
curl http://localhost:5000/health
```

**Response:**
```json
{
  "success": true,
  "status": "healthy",
  "timestamp": "2025-10-14T12:30:45.123456"
}
```

### 3. Get ML Insights (Main Endpoint)

**Endpoint:** `GET /api/insights`

**Description:** Get comprehensive ML forecasts for all categories

**Example:**
```bash
curl http://localhost:5000/api/insights
```

**Response:**
```json
{
  "success": true,
  "insights": {
    "pharmaceutical_demand": {
      "title": "Pharmaceutical Demand Forecast",
      "forecast": [45, 48, 52],
      "historical": [30, 35, 38, 40, 42, 45],
      "historical_labels": ["May 2024", "Jun 2024", ...],
      "forecast_labels": ["Nov 2024", "Dec 2024", "Jan 2025"],
      "trend": "increasing",
      "trend_emoji": "📈",
      "trend_text": "Increasing trend (+12.5%)",
      "percentage_change": 12.5
    },
    "livestock_population": { ... },
    "poultry_population": { ... },
    "transaction_volume": { ... }
  },
  "generated_at": "2025-10-14 12:30:45",
  "model": "Ensemble ML (RF + GB + LR)"
}
```

### 4. General Forecast

**Endpoint:** `POST /api/forecast`

**Description:** Forecast from provided historical data

**Request Body:**
```json
{
  "type": "pharmaceutical",
  "historical_data": [10, 12, 15, 18, 20, 22, 25, 28, 30],
  "months_ahead": 3
}
```

**Example:**
```bash
curl -X POST http://localhost:5000/api/forecast \
  -H "Content-Type: application/json" \
  -d '{"type":"pharmaceutical","historical_data":[10,12,15,18,20,22,25,28,30],"months_ahead":3}'
```

**Response:**
```json
{
  "success": true,
  "type": "pharmaceutical",
  "result": {
    "forecast": [32, 35, 38],
    "historical": [10, 12, 15, 18, 20, 22, 25, 28, 30],
    "method": "ensemble_ml",
    "trend": "increasing",
    "trend_percentage": 15.5
  },
  "timestamp": "2025-10-14T12:30:45.123456"
}
```

### 5. Pharmaceutical Forecast

**Endpoint:** `POST /api/forecast/pharmaceutical`

**Description:** Get pharmaceutical demand forecast from database

**Request Body (Optional):**
```json
{
  "pharma_id": 5,
  "months_ahead": 6
}
```

**Example:**
```bash
curl -X POST http://localhost:5000/api/forecast/pharmaceutical \
  -H "Content-Type: application/json" \
  -d '{"pharma_id":5,"months_ahead":3}'
```

### 6. Livestock Forecast

**Endpoint:** `POST /api/forecast/livestock`

**Description:** Get livestock population forecast

**Request Body (Optional):**
```json
{
  "species": "Cattle",
  "months_ahead": 3
}
```

### 7. Poultry Forecast

**Endpoint:** `POST /api/forecast/poultry`

**Description:** Get poultry population forecast

**Request Body (Optional):**
```json
{
  "species": "Chicken",
  "months_ahead": 3
}
```

## PHP Integration

The Flask API is integrated with PHP through `get_ml_insights_flask.php`:

### PHP Usage Example:

```php
<?php
// Automatic in admin_ml_insights.php
// Calls get_ml_insights_flask.php which contacts Flask API
?>
```

### JavaScript Usage Example:

```javascript
// From admin_ml_insights.php
async function loadMLInsights() {
    const response = await fetch('get_ml_insights_flask.php');
    const data = await response.json();
    
    if (data.success) {
        updateCharts(data.insights);
    }
}
```

## ML Models Used

### Ensemble Forecasting

The API uses an **ensemble of 3 models** for better accuracy:

1. **Random Forest Regressor** (40% weight)
   - Handles non-linear patterns
   - Robust to outliers
   - Good for complex relationships

2. **Gradient Boosting Regressor** (40% weight)
   - Sequential error correction
   - High accuracy
   - Captures trends well

3. **Linear Regression** (20% weight)
   - Fast and stable
   - Good for linear trends
   - Provides baseline

### Feature Engineering

Each forecast uses:
- **Historical values** (last 3 data points)
- **Time features** (month index, year trend)
- **Moving averages** (3-month, 6-month)
- **Trend slope** (recent direction)

## Configuration

### Port Configuration

Default: `5000`

To change port, edit `ml_flask_api.py`:

```python
if __name__ == '__main__':
    app.run(
        host='0.0.0.0',
        port=5000,  # Change this
        debug=True,
        threaded=True
    )
```

Also update PHP endpoint in `get_ml_insights_flask.php`:

```php
$flask_url = 'http://localhost:5000/api/insights';  // Update port here
```

### Database Configuration

The API auto-detects environment:

**Local:**
- Host: localhost
- User: root
- Password: (empty)
- Database: bagovets

**Production:**
- Host: localhost
- User: u520834156_userIMSvet25
- Password: Uk~V3GKL4
- Database: u520834156_dbBagoVetIMS

## Troubleshooting

### Problem: Port 5000 Already in Use

**Error:**
```
OSError: [Errno 98] Address already in use
```

**Solution 1:** Kill existing process
```bash
# Windows
netstat -ano | findstr :5000
taskkill /PID <PID> /F

# Linux/Mac
lsof -i :5000
kill -9 <PID>
```

**Solution 2:** Change port (see Configuration section)

### Problem: Flask Not Installed

**Error:**
```
ModuleNotFoundError: No module named 'flask'
```

**Solution:**
```bash
pip install Flask Flask-CORS
# or
pip install -r requirements.txt
```

### Problem: Database Connection Failed

**Error:**
```
{
  "success": false,
  "error": "Database connection failed"
}
```

**Solution:**
- Verify MySQL is running
- Check database credentials in `ml_flask_api.py`
- Test connection manually:

```python
import mysql.connector
conn = mysql.connector.connect(
    host='localhost',
    user='root',
    password='',
    database='bagovets'
)
print(conn.is_connected())
```

### Problem: PHP Can't Connect to Flask

**Error in browser console:**
```
Flask API connection failed: Failed to connect
```

**Solution:**
1. Verify Flask is running:
   ```bash
   curl http://localhost:5000/health
   ```

2. Check Windows Firewall (Windows):
   - Allow Python through firewall
   - Allow port 5000

3. Check if PHP cURL is enabled:
   ```php
   <?php phpinfo(); ?>
   // Look for cURL section
   ```

## Production Deployment

### Option 1: Run as Background Service (Windows)

Create `run_flask_service.bat`:
```batch
@echo off
start /B python ml_flask_api.py > flask.log 2>&1
```

### Option 2: Use Process Manager (Linux)

**Using systemd:**

Create `/etc/systemd/system/flask-ml.service`:
```ini
[Unit]
Description=Flask ML API
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/capstone
ExecStart=/usr/bin/python3 ml_flask_api.py
Restart=always

[Install]
WantedBy=multi-user.target
```

Enable and start:
```bash
sudo systemctl enable flask-ml
sudo systemctl start flask-ml
```

### Option 3: Use Gunicorn (Production WSGI)

Install:
```bash
pip install gunicorn
```

Run:
```bash
gunicorn -w 4 -b 0.0.0.0:5000 ml_flask_api:app
```

## Performance

- **Response Time:** ~200-500ms for full insights
- **Concurrent Requests:** Supports multiple simultaneous requests (threaded)
- **Memory Usage:** ~150-300MB per worker
- **CPU Usage:** Spikes during forecast computation (2-3 seconds)

## Security Considerations

### Current Setup (Development)

- ✅ CORS enabled (for development)
- ✅ No API key required
- ⚠️ Debug mode ON

### Production Recommendations

1. **Disable Debug Mode:**
   ```python
   app.run(debug=False)
   ```

2. **Add API Authentication:**
   ```python
   from functools import wraps
   
   def require_api_key(f):
       @wraps(f)
       def decorated(*args, **kwargs):
           api_key = request.headers.get('X-API-Key')
           if api_key != 'your-secret-key':
               return jsonify({'error': 'Unauthorized'}), 401
           return f(*args, **kwargs)
       return decorated
   
   @app.route('/api/insights')
   @require_api_key
   def get_insights():
       ...
   ```

3. **Restrict CORS:**
   ```python
   CORS(app, origins=['http://yourdomain.com'])
   ```

4. **Use HTTPS:**
   - Run behind nginx/Apache reverse proxy
   - Enable SSL certificates

## Testing

### Test All Endpoints

```bash
# Health check
curl http://localhost:5000/health

# Full insights
curl http://localhost:5000/api/insights

# Custom forecast
curl -X POST http://localhost:5000/api/forecast \
  -H "Content-Type: application/json" \
  -d '{"historical_data":[10,12,15,18,20],"months_ahead":3}'
```

### Test from PHP

```php
<?php
$ch = curl_init('http://localhost:5000/health');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
echo $response;
curl_close($ch);
?>
```

## Monitoring

### Check Flask Logs

Flask outputs logs to console. To save logs:

**Windows:**
```bash
python ml_flask_api.py > flask.log 2>&1
```

**Linux:**
```bash
python3 ml_flask_api.py > flask.log 2>&1 &
```

### Monitor Performance

Add logging in `ml_flask_api.py`:

```python
import logging
logging.basicConfig(level=logging.INFO)

@app.before_request
def log_request():
    app.logger.info(f'{request.method} {request.path}')
```

## Support

For issues:
1. Check Flask server is running: `curl http://localhost:5000/health`
2. Check logs for errors
3. Verify database connection
4. Test endpoints individually
5. Check PHP cURL extension enabled

## Version History

**v1.0.0** (Current)
- Flask API with REST endpoints
- Ensemble ML forecasting
- Database integration
- Automatic fallback to PHP
- CORS support
- Error handling

## License

Part of Bago City Veterinary Office Information Management System
© 2025 All Rights Reserved

