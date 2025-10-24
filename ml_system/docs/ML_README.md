# 🤖 Machine Learning System - BCVOIMS
## Bago City Veterinary Office Information Management System

---

## 📖 Overview

This ML system uses **Python** with **scikit-learn** to predict animal health risks based on:
- Symptoms from pharmaceutical requests
- Vital signs (temperature, weight, heart rate)
- Environmental factors (season, temperature, humidity)
- Animal characteristics (age, breed, species)
- Historical health data

### **Key Features**
- ✅ **Advanced ML Models**: Random Forest & Gradient Boosting
- ✅ **Real-time Predictions**: Integrated with PHP backend
- ✅ **Risk Assessment**: Low, Medium, High, Critical levels
- ✅ **Confidence Scores**: Probability distributions for each risk level
- ✅ **Automated Recommendations**: Health action suggestions
- ✅ **Easy Retraining**: Update models with new data regularly

---

## 🚀 Quick Start (3 Steps)

### **Step 1: Install Python & Dependencies**

#### Windows:
```bash
# Run the setup script
setup_ml.bat
```

#### Manual Installation:
```bash
# Install Python packages
pip install -r requirements.txt

# Create models directory
mkdir models
```

### **Step 2: Run Complete Setup**

```bash
python run_ml_setup.py
```

This will automatically:
1. ✅ Check dependencies
2. ✅ Collect training data from database
3. ✅ Train the ML model
4. ✅ Test predictions
5. ✅ Verify integration

### **Step 3: Access ML Features**

- **Admin Dashboard**: `http://localhost/capstone5/admin_dashboard.php`
- **ML Insights**: `http://localhost/capstone5/admin_ml_insights.php`

---

## 📁 File Structure

```
capstone5/
├── 📄 Python Scripts
│   ├── collect_training_data.py       # Collects data from database
│   ├── train_ml_model_advanced.py     # Trains advanced ML models
│   ├── ml_predict_advanced.py         # Makes predictions
│   ├── test_ml_system.py              # Tests the complete system
│   └── run_ml_setup.py                # Automated setup
│
├── 📄 Legacy Scripts
│   ├── train_ml_model.py              # Simple training (fallback)
│   ├── ml_predict.py                  # Simple prediction (fallback)
│   └── train_ml_model_simple.py       # Simplified training
│
├── 📄 Configuration
│   ├── requirements.txt               # Python dependencies
│   ├── setup_ml.bat                   # Windows setup script
│   └── ML_SETUP_GUIDE.md             # Detailed setup guide
│
├── 📂 Models (Generated)
│   ├── health_risk_model.pkl          # Trained model
│   ├── label_encoder.pkl              # Label encoder
│   ├── scaler.pkl                     # Feature scaler
│   └── model_metadata.json            # Model information
│
├── 📂 Data (Generated)
│   └── training_data.json             # Collected training data
│
└── 📂 PHP Integration
    ├── includes/ml_health_risk_assessor.php
    ├── includes/health_risk_assessor.php
    └── admin_ml_insights.php
```

---

## 🔄 ML Workflow

### **1. Data Collection**

```bash
python collect_training_data.py
```

**What it does:**
- Connects to MySQL database (`bago_city_vet`)
- Extracts pharmaceutical requests with symptoms
- Collects livestock and poultry health data
- Processes and formats data for ML training
- Saves to `training_data.json`

**Database Tables Used:**
- `pharmaceutical_requests` - Symptoms and medicine data
- `livestock` - Livestock health records
- `poultry` - Poultry health records
- `clients` - Owner and location information

### **2. Model Training**

```bash
python train_ml_model_advanced.py
```

**What it does:**
- Loads training data
- Extracts 40+ features per record:
  - 23 symptom indicators (binary)
  - Symptom count & severity
  - 4 vital sign features
  - 3 environmental features
  - 4 animal characteristic features
  - 2 health status features
  - 2 interaction features
- Trains multiple models (Random Forest, Gradient Boosting)
- Selects best performer via cross-validation
- Saves models to `models/` directory

**Output:**
- Training & test accuracy
- Cross-validation scores
- Classification report
- Feature importance analysis
- Saved model files

### **3. Making Predictions**

```bash
python ml_predict_advanced.py test_data.json
```

**Input Format:**
```json
{
  "animal_id": "123",
  "symptoms": ["fever", "lethargy", "loss_of_appetite"],
  "vital_signs": {
    "temperature": 39.5,
    "weight": 350,
    "heart_rate": 80
  },
  "environment": {
    "temperature": 28.0,
    "humidity": 65.0,
    "season": "summer"
  },
  "animal_characteristics": {
    "species": "cattle",
    "breed": "native",
    "age": 2,
    "type": "livestock"
  },
  "health_status": "under observation"
}
```

**Output:**
```json
{
  "risk_level": "Medium",
  "risk_score": 52,
  "confidence": 0.78,
  "probabilities": {
    "Low": 0.10,
    "Medium": 0.78,
    "High": 0.10,
    "Critical": 0.02
  },
  "recommendations": [
    "📋 Schedule routine veterinary check-up",
    "Monitor animal behavior and appetite daily",
    "Monitor temperature regularly (every 4 hours)"
  ],
  "model_version": "advanced_v1.0"
}
```

### **4. PHP Integration**

The ML system automatically integrates with PHP through:

**`includes/ml_health_risk_assessor.php`**
- Collects animal data from database
- Prepares data for Python script
- Calls `ml_predict_advanced.py`
- Processes results for display

**Usage in PHP:**
```php
require_once 'includes/ml_health_risk_assessor.php';

$ml_assessor = new MLHealthRiskAssessor($conn);
$result = $ml_assessor->assessAnimalHealthRiskML($animal_id);

if (!isset($result['error'])) {
    echo "Risk Level: " . $result['risk_level'];
    echo "Risk Score: " . $result['risk_score'];
    echo "Confidence: " . ($result['confidence'] * 100) . "%";
}
```

---

## 🔧 Configuration

### **Database Settings**

Edit `collect_training_data.py`:
```python
self.db_config = {
    'host': 'localhost',
    'database': 'bago_city_vet',
    'user': 'root',
    'password': ''  # Add your MySQL password
}
```

### **Python Path**

Edit `includes/ml_health_risk_assessor.php`:
```php
$this->python_path = 'python'; // Or 'C:\\Python310\\python.exe'
```

---

## 📊 Model Performance

### **Current Models**

1. **Random Forest Classifier**
   - 100-200 estimators
   - Max depth: 20-30
   - Multi-class classification

2. **Gradient Boosting Classifier**
   - 100 estimators
   - Learning rate: 0.1
   - Max depth: 5

### **Performance Metrics**

- **Accuracy**: 70-90% (depends on data quality)
- **Cross-validation**: 5-fold CV
- **Evaluation**: Precision, Recall, F1-Score, Confusion Matrix

### **Feature Importance**

Top features typically include:
1. Symptom count
2. Critical symptom presence
3. Temperature deviation
4. Specific symptoms (fever, lethargy, etc.)
5. Animal age and breed

---

## 🔄 Retraining the Model

### **When to Retrain:**
- ✅ Weekly or monthly (recommended)
- ✅ When accuracy drops
- ✅ After significant system changes
- ✅ When new symptoms/conditions are added

### **How to Retrain:**

#### Option 1: Complete Automated Setup
```bash
python run_ml_setup.py
```

#### Option 2: Manual Steps
```bash
# 1. Collect latest data
python collect_training_data.py

# 2. Train new model
python train_ml_model_advanced.py

# 3. Test predictions
python test_ml_system.py
```

#### Option 3: Windows Batch
```bash
setup_ml.bat
```

---

## 🐛 Troubleshooting

### **Python Not Found**
```
'python' is not recognized...
```
**Solution:**
1. Install Python from https://python.org
2. Check "Add Python to PATH"
3. Restart terminal/command prompt

### **Module Not Found**
```
ModuleNotFoundError: No module named 'sklearn'
```
**Solution:**
```bash
pip install scikit-learn
# or
pip install -r requirements.txt
```

### **Database Connection Failed**
```
Database connection failed: Access denied
```
**Solution:**
1. Check MySQL is running
2. Verify database credentials in `collect_training_data.py`
3. Ensure database `bago_city_vet` exists

### **No Training Data**
```
Training data file 'training_data.json' not found!
```
**Solution:**
```bash
python collect_training_data.py
```

### **PHP Can't Call Python**
```
ML model execution failed - no output
```
**Solution:**
1. Test Python from command line: `python --version`
2. Update Python path in `includes/ml_health_risk_assessor.php`
3. Check file permissions
4. Try full path: `C:\\Python310\\python.exe`

### **Low Accuracy**
**Solution:**
1. Collect more training data (aim for 100+ records)
2. Ensure diverse data (different breeds, symptoms, seasons)
3. Check data quality (remove duplicates, fix errors)
4. Retrain with updated data

---

## 📈 Improving Model Performance

### **1. Collect More Data**
- More pharmaceutical requests with symptoms
- Diverse cases (different animals, conditions)
- Historical outcomes (recovered, critical, etc.)

### **2. Feature Engineering**
Edit `train_ml_model_advanced.py` to add:
- New symptom combinations
- Additional vital signs
- Location-based features
- Temporal features (time of year, disease trends)

### **3. Hyperparameter Tuning**
```python
from sklearn.model_selection import GridSearchCV

param_grid = {
    'n_estimators': [100, 200, 300],
    'max_depth': [10, 20, 30, None],
    'min_samples_split': [2, 5, 10]
}

grid_search = GridSearchCV(
    RandomForestClassifier(), 
    param_grid, 
    cv=5,
    n_jobs=-1
)
grid_search.fit(X_train, y_train)
best_model = grid_search.best_estimator_
```

### **4. Try Different Algorithms**
```bash
pip install xgboost
```

```python
import xgboost as xgb
model = xgb.XGBClassifier(n_estimators=100)
```

---

## 📝 Maintenance Checklist

### **Weekly:**
- [ ] Review prediction accuracy
- [ ] Check for new pharmaceutical requests
- [ ] Monitor system logs

### **Monthly:**
- [ ] Retrain model with new data
- [ ] Analyze feature importance
- [ ] Update symptom keywords if needed

### **Quarterly:**
- [ ] Full system audit
- [ ] Performance benchmark
- [ ] Consider algorithm updates

---

## 🆘 Support & Resources

### **Command Reference**
```bash
# Setup
pip install -r requirements.txt
python run_ml_setup.py

# Data & Training
python collect_training_data.py
python train_ml_model_advanced.py

# Testing
python test_ml_system.py
python ml_predict_advanced.py test_data.json

# Check versions
python --version
pip list
```

### **Files to Customize**
- `collect_training_data.py` - Database connection
- `train_ml_model_advanced.py` - Model parameters
- `ml_predict_advanced.py` - Prediction logic
- `includes/ml_health_risk_assessor.php` - PHP integration

### **Documentation**
- `ML_SETUP_GUIDE.md` - Detailed setup instructions
- `HEALTH_RISK_ASSESSMENT_README.md` - Feature documentation
- `ml_integration_plan.md` - Integration strategy

---

## ✅ Verification Checklist

After setup, verify:

- [ ] Python installed (`python --version`)
- [ ] All packages installed (`pip list`)
- [ ] Database accessible (`python collect_training_data.py`)
- [ ] Training data collected (`training_data.json` exists)
- [ ] Model trained (`models/*.pkl` files exist)
- [ ] Predictions work (`python test_ml_system.py`)
- [ ] PHP can call Python (test via admin panel)
- [ ] ML insights page loads (admin_ml_insights.php)

---

## 🎉 Success!

Your Python-based machine learning system is now fully integrated!

**Next Steps:**
1. 📊 Monitor model performance in admin dashboard
2. 🔄 Retrain monthly with accumulated data
3. 📈 Improve accuracy by collecting more diverse health cases
4. 🚀 Expand features based on veterinary feedback

**Support:**
- Review logs for errors
- Test with sample data regularly
- Keep models updated with fresh data

---

**Happy Predicting! 🐄🐔🤖**

