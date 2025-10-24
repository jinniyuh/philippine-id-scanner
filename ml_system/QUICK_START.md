# ML System - Quick Start Guide

## ⚡ 30-Second Setup

### 1. Install Dependencies (One Time)
```bash
# From capstone root folder
cd c:\xampp\htdocs\capstone
pip install -r requirements.txt
```

### 2. Start Flask Server
```bash
# From ml_system folder
cd ml_system
start_flask.bat    # Windows
# OR
./start_flask.sh   # Linux/Mac
```

### 3. Verify Running
Open browser: http://localhost:5000

Should see:
```json
{
  "success": true,
  "message": "ML Flask API for Veterinary Management System"
}
```

### 4. Test API
Open: http://localhost/capstone/ml_system/api/test_flask_api.php

Click "Run All Tests" ✅

## 🎯 Common Tasks

### Start Flask Server
**Windows:**
```cmd
cd c:\xampp\htdocs\capstone\ml_system
start_flask.bat
```

**Linux/Mac:**
```bash
cd /xampp/htdocs/capstone/ml_system
chmod +x start_flask.sh
./start_flask.sh
```

### Stop Flask Server
Press `Ctrl + C` in terminal

### Test API
- Browser: http://localhost/capstone/ml_system/api/test_flask_api.php
- Command: `curl http://localhost:5000/health`

### View ML Insights Dashboard
http://localhost/capstone/admin_ml_insights.php
(Must be logged in as admin)

### Check Flask Status
```bash
curl http://localhost:5000/health
```

Expected response:
```json
{
  "success": true,
  "status": "healthy",
  "timestamp": "2025-10-14T12:30:45.123456"
}
```

## 📁 File Locations

| Component | Location |
|-----------|----------|
| Flask API | `ml_system/api/ml_flask_api.py` |
| PHP Connector | `ml_system/api/get_ml_insights_flask.php` |
| Test Page | `ml_system/api/test_flask_api.php` |
| Forecasting Script | `ml_system/scripts/ml_demand_forecast.py` |
| Health Prediction | `ml_system/scripts/ml_predict_advanced.py` |
| Start Script (Win) | `ml_system/start_flask.bat` |
| Start Script (Linux) | `ml_system/start_flask.sh` |
| Documentation | `ml_system/docs/` |

## 🔧 Troubleshooting

### "Port 5000 already in use"
```bash
# Kill process using port 5000
# Windows:
netstat -ano | findstr :5000
taskkill /PID <PID> /F

# Linux/Mac:
lsof -i :5000
kill -9 <PID>
```

### "ModuleNotFoundError: No module named 'flask'"
```bash
pip install -r requirements.txt
```

### "Database connection failed"
1. Check MySQL is running
2. Verify credentials in `api/ml_flask_api.py`
3. Test: `mysql -u root -p bagovets`

### "Can't connect to Flask from PHP"
1. Verify Flask is running: `curl http://localhost:5000/health`
2. Check firewall settings
3. Ensure cURL enabled in PHP

## 📊 API Endpoints Quick Reference

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/` | API info |
| GET | `/health` | Health check |
| GET | `/api/insights` | Full ML insights (main endpoint) |
| POST | `/api/forecast` | Custom forecast |
| POST | `/api/forecast/pharmaceutical` | Pharma forecast |
| POST | `/api/forecast/livestock` | Livestock forecast |
| POST | `/api/forecast/poultry` | Poultry forecast |

## 💡 Example API Calls

### Health Check
```bash
curl http://localhost:5000/health
```

### Get Full Insights
```bash
curl http://localhost:5000/api/insights
```

### Custom Forecast
```bash
curl -X POST http://localhost:5000/api/forecast \
  -H "Content-Type: application/json" \
  -d '{"historical_data":[10,12,15,18,20],"months_ahead":3}'
```

### From PHP
```php
<?php
$response = file_get_contents('http://localhost:5000/api/insights');
$data = json_decode($response, true);
print_r($data);
?>
```

### From JavaScript
```javascript
fetch('http://localhost:5000/api/insights')
  .then(response => response.json())
  .then(data => console.log(data));
```

## 🚀 Integration with Main App

The ML system automatically integrates with:

1. **Admin Dashboard** - `admin_ml_insights.php`
   - Automatically uses Flask API if running
   - Falls back to PHP if Flask unavailable
   - No code changes needed

2. **Forecasting** - Real-time predictions
   - Pharmaceutical demand
   - Livestock population
   - Poultry population
   - Transaction volume

3. **Charts** - Visual analytics
   - Historical data
   - Forecast trends
   - Confidence intervals

## 📚 More Help

- Full documentation: `README.md`
- Flask API guide: `docs/FLASK_API_README.md`
- ML details: `docs/ML_README.md`
- Forecasting guide: `docs/ML_DEMAND_FORECASTING_README.md`

## ⚠️ Important Notes

1. **Flask must be running** for ML features to work optimally
2. **Port 5000** must be available
3. **MySQL** must be running
4. **Python 3.8+** required
5. **Admin login** required for dashboard access

## 🎓 Learning Resources

### Python Dependencies
```
Flask - Web framework
scikit-learn - ML models
NumPy - Numerical computing
MySQL Connector - Database
```

### ML Models Used
- Random Forest Regressor
- Gradient Boosting Regressor  
- Linear Regression
- Ensemble averaging

### Tech Stack
```
Backend: PHP + Python Flask
ML: scikit-learn
Database: MySQL
Frontend: JavaScript + Chart.js
```

---

## 🆘 Need Help?

1. Check this guide
2. Read `README.md`
3. Test with `api/test_flask_api.php`
4. Check server logs
5. Verify MySQL connection

---

**Remember:** Keep Flask server running for best performance! 🚀

