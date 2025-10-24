# 🤖 Python Machine Learning Setup Guide
## Bago City Veterinary Office Information Management System

---

## 📋 **Prerequisites**

### **1. Install Python**
- **Download**: [Python 3.10 or later](https://www.python.org/downloads/)
- **Important**: During installation, check ✅ **"Add Python to PATH"**
- **Verify installation**:
  ```bash
  python --version
  # Should show: Python 3.10.x or later
  ```

### **2. Install pip (Python Package Manager)**
- Usually comes with Python
- **Verify**:
  ```bash
  pip --version
  ```

---

## 🚀 **Quick Setup (3 Steps)**

### **Step 1: Install Required Packages**

Open Command Prompt or PowerShell in the project directory (`C:\xampp\htdocs\capstone5`) and run:

```bash
pip install -r requirements.txt
```

This installs:
- ✅ `numpy` - Numerical computing
- ✅ `pandas` - Data manipulation
- ✅ `scikit-learn` - Machine learning algorithms
- ✅ `joblib` - Model serialization
- ✅ `mysql-connector-python` - Database connectivity
- ✅ `matplotlib` & `seaborn` - Data visualization

### **Step 2: Verify Installation**

```bash
python -c "import numpy, pandas, sklearn; print('✅ All packages installed successfully!')"
```

### **Step 3: Create Models Directory**

```bash
mkdir models
```

---

## 📊 **ML Workflow**

### **1. Collect Training Data**

Run the data collection script to gather historical health data from your database:

```bash
python collect_training_data.py
```

**Output**: `training_data.json` (contains historical health assessments)

### **2. Train ML Model**

Train the Random Forest model on collected data:

```bash
python train_ml_model.py
```

**Output**:
- `models/health_risk_model.pkl` - Trained model
- `models/label_encoder.pkl` - Label encoder
- Training accuracy and performance metrics

### **3. Test Predictions**

Test the model with sample data:

```bash
python ml_predict.py test_data.json
```

**Sample test_data.json**:
```json
{
  "animal_id": "123",
  "symptoms": ["fever", "lethargy", "loss_of_appetite"],
  "vital_signs": {
    "temperature": 39.5,
    "weight": 45.2,
    "heart_rate": 85
  },
  "environment": {
    "temperature": 28.5,
    "humidity": 65,
    "season": "winter"
  },
  "age": 2,
  "breed": "cattle"
}
```

### **4. Integrate with PHP**

The PHP system automatically calls Python scripts when:
- ✅ Assessing animal health risks
- ✅ Viewing ML insights dashboard
- ✅ Generating health predictions

---

## 🔧 **Configuration**

### **Update Python Path in PHP**

Edit `includes/ml_health_risk_assessor.php`:

```php
// Line 13
$this->python_path = 'python'; // Or full path: 'C:\\Python310\\python.exe'
```

### **Test Python from PHP**

```bash
# Test if PHP can call Python
php -r "echo shell_exec('python --version');"
```

---

## 📁 **File Structure**

```
capstone5/
├── ml_predict.py                 # Prediction script (called by PHP)
├── train_ml_model.py            # Model training script
├── train_ml_model_simple.py     # Simplified training
├── collect_training_data.py     # Data collection (to be created)
├── requirements.txt             # Python dependencies
├── models/                      # Trained models directory
│   ├── health_risk_model.pkl
│   └── label_encoder.pkl
├── includes/
│   ├── ml_health_risk_assessor.php
│   └── health_risk_assessor.php
└── training_data.json          # Training dataset
```

---

## 🎯 **ML Features**

### **Current Implementation**

#### **1. Health Risk Classification**
- **Algorithm**: Random Forest Classifier
- **Input Features**:
  - 23 symptom indicators (binary)
  - Vital signs (temperature, weight, heart rate)
  - Environmental factors (temp, humidity, season)
  - Animal characteristics (age, breed)
- **Output**: Risk level (Low, Medium, High, Critical) with confidence score

#### **2. Feature Engineering**
- **Symptom encoding**: Binary presence/absence
- **Seasonal encoding**: Spring=0, Summer=1, Autumn=2, Winter=3
- **Breed encoding**: Numeric mapping for animal types

#### **3. Model Performance**
- **Training**: 80% of data
- **Testing**: 20% of data
- **Validation**: 5-fold cross-validation
- **Metrics**: Accuracy, precision, recall, F1-score

---

## 🐛 **Troubleshooting**

### **Issue: "Python not recognized"**
**Solution**: Add Python to system PATH
1. Find Python installation directory (e.g., `C:\Python310\`)
2. Add to system PATH environment variable
3. Restart command prompt

### **Issue: "No module named 'sklearn'"**
**Solution**: Install scikit-learn
```bash
pip install scikit-learn
```

### **Issue: "Permission denied"**
**Solution**: Run as administrator or use virtual environment
```bash
python -m venv venv
venv\Scripts\activate
pip install -r requirements.txt
```

### **Issue: "Models not found"**
**Solution**: Train models first
```bash
python train_ml_model.py
```

### **Issue: "Training data not found"**
**Solution**: Collect data first
```bash
python collect_training_data.py
```

---

## 📈 **Advanced: Improving Model Accuracy**

### **1. Collect More Data**
- More training examples = better predictions
- Aim for at least 100+ health assessments
- Include diverse cases (different breeds, seasons, symptoms)

### **2. Feature Engineering**
- Add more relevant features
- Create interaction features
- Normalize/standardize numerical features

### **3. Try Different Algorithms**
```python
# In train_ml_model.py, replace RandomForestClassifier with:

# Gradient Boosting
from sklearn.ensemble import GradientBoostingClassifier
model = GradientBoostingClassifier(n_estimators=100)

# XGBoost (if installed)
import xgboost as xgb
model = xgb.XGBClassifier(n_estimators=100)

# Neural Network
from sklearn.neural_network import MLPClassifier
model = MLPClassifier(hidden_layer_sizes=(100, 50))
```

### **4. Hyperparameter Tuning**
```python
from sklearn.model_selection import GridSearchCV

param_grid = {
    'n_estimators': [50, 100, 200],
    'max_depth': [10, 20, 30],
    'min_samples_split': [2, 5, 10]
}

grid_search = GridSearchCV(RandomForestClassifier(), param_grid, cv=5)
grid_search.fit(X_train, y_train)
best_model = grid_search.best_estimator_
```

---

## 🔄 **Retraining the Model**

**When to retrain**:
- ✅ Weekly or monthly (as new data accumulates)
- ✅ When accuracy drops
- ✅ When adding new features
- ✅ After significant data changes

**How to retrain**:
```bash
# 1. Collect latest data
python collect_training_data.py

# 2. Retrain model
python train_ml_model.py

# 3. Test new model
python ml_predict.py test_data.json
```

---

## 📝 **Next Steps**

1. ✅ Install Python and dependencies
2. ✅ Create models directory
3. ⬜ Collect training data from database
4. ⬜ Train initial model
5. ⬜ Test predictions
6. ⬜ Monitor model performance
7. ⬜ Retrain regularly with new data

---

## 🆘 **Support**

### **Common Commands Reference**

```bash
# Install packages
pip install -r requirements.txt

# Collect data
python collect_training_data.py

# Train model
python train_ml_model.py

# Make prediction
python ml_predict.py test_data.json

# Check Python version
python --version

# Check installed packages
pip list

# Update a package
pip install --upgrade scikit-learn
```

### **Python Virtual Environment (Recommended)**

```bash
# Create virtual environment
python -m venv venv

# Activate (Windows)
venv\Scripts\activate

# Activate (Linux/Mac)
source venv/bin/activate

# Install packages in venv
pip install -r requirements.txt

# Deactivate
deactivate
```

---

## 🎉 **You're Ready!**

Your Python ML environment is now set up. Follow the ML Workflow section to start training your models!

