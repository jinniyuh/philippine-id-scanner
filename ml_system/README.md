# Machine Learning System

## 📁 Folder Structure

This folder contains all machine learning components for the Bago City Veterinary Office Information Management System.

```
ml_system/
├── api/                          # Flask REST API & PHP Endpoints
│   ├── ml_flask_api.py          # Main Flask API server
│   ├── get_ml_insights_flask.php # ML insights connector
│   ├── get_ml_insights_enhanced.php # Enhanced ML insights
│   ├── get_ml_insights.php      # ML insights endpoint
│   ├── get_ml_demand_forecast.php # Demand forecast endpoint
│   ├── get_pharmaceutical_forecast.php # Pharma forecast
│   ├── get_livestock_forecast.php # Livestock forecast
│   ├── get_poultry_forecast.php # Poultry forecast
│   ├── get_health_risk_flask.php # Health risk connector
│   ├── test_flask_api.php       # API testing interface
│   └── test_health_risk_api.php # Health risk tester
│
├── includes/                     # PHP ML Classes
│   ├── arima_forecaster.php     # ARIMA forecasting implementation
│   ├── health_risk_assessor.php # Health risk assessment
│   ├── ml_health_risk_assessor.php # ML health risk integration
│   └── sample_data_generator.php # Sample data generator
│
├── scripts/                      # ML Scripts & Utilities
│   ├── ml_demand_forecast.py    # Demand forecasting (ensemble ML)
│   └── ml_predict_advanced.py   # Health risk prediction
│
├── models/                       # Trained ML Models
│   └── *.json                   # Model configurations
│
├── docs/                         # Documentation
│   ├── FLASK_API_README.md      # Flask API documentation
│   ├── ML_README.md             # General ML documentation
│   └── ML_DEMAND_FORECASTING_README.md
│
├── start_flask.bat              # Windows: Start Flask server
├── start_flask.sh               # Linux/Mac: Start Flask server
├── README.md                    # This file
├── QUICK_START.md               # Quick start guide
├── HEALTH_RISK_ML_INTEGRATION.md # Health risk ML docs
├── TEST_INTEGRATION.md          # Testing guide
└── INTEGRATION_COMPLETE.md      # Integration summary
```

---

## 🚀 Quick Start

### 1. Install Dependencies

From the main capstone folder:

```bash
pip install -r requirements.txt
```

### 2. Start Flask API Server

**Windows:**
```cmd
cd ml_system
start_flask.bat
```

**Linux/Mac:**
```bash
cd ml_system
chmod +x start_flask.sh
./start_flask.sh
```

### 3. Verify Server is Running

Open browser: http://localhost:5000

You should see API information.

### 4. Test API

Open: http://localhost/capstone/ml_system/api/test_flask_api.php

Click "Run All Tests"

---

## 📡 Components

### Flask API (`api/ml_flask_api.py`)

**Purpose:** RESTful API for ML predictions and forecasting

**Features:**
- Pharmaceutical demand forecasting
- Livestock population prediction
- Poultry population prediction
- Transaction volume forecasting
- Ensemble ML models (Random Forest + Gradient Boosting + Linear Regression)

**Endpoints:**
- `GET /` - API information
- `GET /health` - Health check
- `GET /api/insights` - Full ML insights
- `POST /api/forecast` - Custom forecast
- `POST /api/forecast/pharmaceutical` - Pharmaceutical forecast
- `POST /api/forecast/livestock` - Livestock forecast
- `POST /api/forecast/poultry` - Poultry forecast

**Technology Stack:**
- Flask (Web framework)
- scikit-learn (ML models)
- MySQL (Database)
- NumPy (Numerical computing)

---

### Demand Forecasting Script (`scripts/ml_demand_forecast.py`)

**Purpose:** Standalone forecasting script for CLI usage

**Usage:**
```bash
python ml_demand_forecast.py config.json
```

**Config Example:**
```json
{
  "type": "pharmaceutical",
  "historical_data": [10, 12, 15, 18, 20, 22],
  "months_ahead": 3
}
```

**Models Used:**
- Random Forest Regressor (40%)
- Gradient Boosting Regressor (40%)
- Linear Regression (20%)

---

### Health Risk Prediction (`scripts/ml_predict_advanced.py`)

**Purpose:** Predict animal health risks using ML

**Usage:**
```bash
python ml_predict_advanced.py animal_data.json
```

**Features:**
- Multi-symptom analysis
- Risk level classification (Low, Medium, High, Critical)
- Confidence scores
- Actionable recommendations

---

### PHP Integration (`api/get_ml_insights_flask.php`)

**Purpose:** Connect PHP backend to Flask API

**How it works:**
1. PHP receives request from admin dashboard
2. Makes HTTP call to Flask API
3. Returns JSON response to frontend
4. Falls back to PHP implementation if Flask unavailable

**Usage in PHP:**
```php
<?php
// Automatically used by admin_ml_insights.php
$response = file_get_contents('ml_system/api/get_ml_insights_flask.php');
$data = json_decode($response);
?>
```

---

## 🔧 Configuration

### Database Connection

The Flask API auto-detects environment:

**Local Development:**
```python
{
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'bagovets'
}
```

**Production:**
```python
{
    'host': 'localhost',
    'user': 'u520834156_userIMSvet25',
    'password': 'Uk~V3GKL4',
    'database': 'u520834156_dbBagoVetIMS'
}
```

### Port Configuration

Default Flask port: **5000**

To change, edit `api/ml_flask_api.py`:
```python
app.run(port=5000)  # Change this
```

---

## 📊 ML Models Explained

### Ensemble Forecasting

Uses weighted average of 3 models:

1. **Random Forest (40% weight)**
   - Handles non-linear patterns
   - Robust to outliers
   - Good for complex relationships

2. **Gradient Boosting (40% weight)**
   - Sequential error correction
   - High accuracy
   - Captures trends well

3. **Linear Regression (20% weight)**
   - Fast and stable
   - Good for linear trends
   - Baseline predictor

### Feature Engineering

Each forecast uses:
- **Lag features:** Last 3 data points
- **Time features:** Month index, year trend
- **Moving averages:** 3-month and 6-month
- **Trend slope:** Recent direction

---

## 🧪 Testing

### Test Flask API

**Browser Test:**
```
http://localhost/capstone/ml_system/api/test_flask_api.php
```

**Command Line Test:**
```bash
# Health check
curl http://localhost:5000/health

# Full insights
curl http://localhost:5000/api/insights

# Custom forecast
curl -X POST http://localhost:5000/api/forecast \
  -H "Content-Type: application/json" \
  -d '{"historical_data":[10,12,15],"months_ahead":3}'
```

### Test Demand Forecasting

```bash
cd scripts
echo '{"type":"pharmaceutical","historical_data":[10,12,15,18,20],"months_ahead":3}' > test_config.json
python ml_demand_forecast.py test_config.json
```

---

## 🐛 Troubleshooting

### Flask Server Won't Start

**Problem:** Port 5000 already in use

**Solution:**
```bash
# Windows
netstat -ano | findstr :5000
taskkill /PID <PID> /F

# Linux/Mac
lsof -i :5000
kill -9 <PID>
```

### Import Errors

**Problem:** ModuleNotFoundError

**Solution:**
```bash
pip install -r requirements.txt
```

### Database Connection Failed

**Problem:** Can't connect to MySQL

**Solution:**
1. Check MySQL is running
2. Verify credentials in `api/ml_flask_api.py`
3. Test connection:
```bash
mysql -u root -p bagovets
```

### PHP Can't Reach Flask

**Problem:** Connection refused

**Solution:**
1. Verify Flask is running: `curl http://localhost:5000/health`
2. Check firewall settings
3. Ensure cURL is enabled in PHP

---

## 📈 Performance

- **Response Time:** 200-500ms for full insights
- **Forecast Generation:** 2-3 seconds
- **Memory Usage:** ~150-300MB
- **Concurrent Requests:** Supported (threaded)

---

## 🔐 Security (Production)

### Recommended Settings:

1. **Disable Debug Mode:**
```python
app.run(debug=False)
```

2. **Add API Authentication:**
```python
@app.before_request
def require_api_key():
    if request.headers.get('X-API-Key') != 'secret':
        return jsonify({'error': 'Unauthorized'}), 401
```

3. **Use HTTPS:**
- Deploy behind nginx/Apache
- Enable SSL certificates

4. **Restrict CORS:**
```python
CORS(app, origins=['https://yourdomain.com'])
```

---

## 📚 Documentation

| File | Description |
|------|-------------|
| `docs/FLASK_API_README.md` | Complete Flask API guide |
| `docs/ML_README.md` | General ML documentation |
| `docs/ML_DEMAND_FORECASTING_README.md` | Forecasting details |

---

## 🔄 Integration with Main App

The ML system integrates with:

1. **Admin Dashboard** (`admin_ml_insights.php`)
   - Displays forecasts and insights
   - Updates every 5 minutes
   - Auto-fallback to PHP if Flask unavailable

2. **PHP Backend** (`get_ml_insights_enhanced.php`)
   - Fallback implementation
   - Same structure as Flask API

3. **Database** (MySQL)
   - Reads transaction history
   - Analyzes livestock/poultry data
   - Generates predictions

---

## 🚀 Deployment

### Development

```bash
cd ml_system
python api/ml_flask_api.py
```

### Production (Gunicorn)

```bash
pip install gunicorn
cd ml_system
gunicorn -w 4 -b 0.0.0.0:5000 api.ml_flask_api:app
```

### Production (Systemd Service)

Create `/etc/systemd/system/flask-ml.service`:
```ini
[Unit]
Description=Flask ML API
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/capstone/ml_system
ExecStart=/usr/bin/python3 api/ml_flask_api.py
Restart=always

[Install]
WantedBy=multi-user.target
```

Enable:
```bash
sudo systemctl enable flask-ml
sudo systemctl start flask-ml
```

---

## 📝 Adding New Models

### 1. Create Script

```python
# scripts/my_new_model.py
def predict(data):
    # Your ML logic
    return predictions
```

### 2. Add Flask Endpoint

```python
# api/ml_flask_api.py
@app.route('/api/my_endpoint', methods=['POST'])
def my_endpoint():
    data = request.get_json()
    result = predict(data)
    return jsonify({'success': True, 'result': result})
```

### 3. Test

```bash
curl -X POST http://localhost:5000/api/my_endpoint \
  -H "Content-Type: application/json" \
  -d '{"data": "test"}'
```

---

## 📊 Monitoring

### Check Logs

```bash
# View Flask logs
tail -f flask.log

# Monitor in real-time
python api/ml_flask_api.py | tee flask.log
```

### Performance Metrics

Add logging:
```python
import logging
logging.basicConfig(level=logging.INFO)

@app.before_request
def log_request():
    app.logger.info(f'{request.method} {request.path}')
```

---

## 🤝 Support

For issues or questions:
1. Check documentation in `docs/`
2. Test endpoints with `api/test_flask_api.php`
3. Review logs for errors
4. Verify database connection
5. Ensure all dependencies installed

---

## 📜 Version History

**v1.0.0** - Initial organized structure
- Flask REST API
- Ensemble ML forecasting
- Health risk prediction
- Complete documentation
- PHP integration

---

## 📄 License

Part of Bago City Veterinary Office Information Management System
© 2025 All Rights Reserved

