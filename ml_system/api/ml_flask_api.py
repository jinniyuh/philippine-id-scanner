#!/usr/bin/env python3
"""
Flask API for ML Insights and Predictions
Provides REST endpoints for veterinary management system
"""

from flask import Flask, request, jsonify
from flask_cors import CORS
import sys
import json
import os
import numpy as np
import mysql.connector
from datetime import datetime, timedelta
import warnings
warnings.filterwarnings('ignore')

# Import ML modules
try:
    from sklearn.ensemble import RandomForestRegressor, GradientBoostingRegressor
    from sklearn.linear_model import LinearRegression
    from sklearn.preprocessing import StandardScaler
    import joblib
except ImportError as e:
    print(f"Error importing ML libraries: {e}")
    print("Run: pip install -r requirements.txt")
    sys.exit(1)

# Initialize Flask app
app = Flask(__name__)
CORS(app)  # Enable Cross-Origin Resource Sharing

# Configuration
app.config['JSON_SORT_KEYS'] = False
app.config['JSONIFY_PRETTYPRINT_REGULAR'] = True


# =============================================================================
# DATABASE CONNECTION
# =============================================================================

class DatabaseConnection:
    """Handle database connections with fallback for local/production"""
    
    def __init__(self):
        self.conn = None
        self.cursor = None
    
    def connect(self):
        """Attempt to connect to database"""
        configs = [
            # Local configuration
            {
                'host': 'localhost',
                'user': 'root',
                'password': '',
                'database': 'bagovets',
                'charset': 'utf8mb4'
            },
            # Production configuration
            {
                'host': 'localhost',
                'user': 'u520834156_userIMSvet25',
                'password': 'Uk~V3GKL4',
                'database': 'u520834156_dbBagoVetIMS',
                'charset': 'utf8mb4'
            }
        ]
        
        for config in configs:
            try:
                self.conn = mysql.connector.connect(**config)
                if self.conn.is_connected():
                    self.cursor = self.conn.cursor(dictionary=True)
                    return True
            except mysql.connector.Error:
                continue
        
        return False
    
    def close(self):
        """Close database connection"""
        if self.cursor:
            self.cursor.close()
        if self.conn:
            self.conn.close()


# =============================================================================
# ML FORECASTING CLASSES
# =============================================================================

class DemandForecaster:
    """ML-based demand forecasting"""
    
    def __init__(self):
        self.scaler = StandardScaler()
    
    def prepare_time_series_features(self, data, lookback=3):
        """Prepare features from time series data"""
        X = []
        y = []
        
        data_array = np.array(data)
        
        for i in range(lookback, len(data_array)):
            features = list(data_array[i-lookback:i])
            features.append(i % 12)  # Month of year
            features.append(i // 12)  # Year trend
            features.append(np.mean(data_array[max(0, i-3):i]))  # 3-month MA
            features.append(np.mean(data_array[max(0, i-6):i]))  # 6-month MA
            
            if i >= 3:
                x_vals = np.arange(3)
                y_vals = data_array[i-3:i]
                if len(y_vals) == 3:
                    slope = np.polyfit(x_vals, y_vals, 1)[0]
                    features.append(slope)
                else:
                    features.append(0)
            else:
                features.append(0)
            
            X.append(features)
            y.append(data_array[i])
        
        return np.array(X), np.array(y)
    
    def forecast(self, historical_data, months_ahead=3):
        """Forecast using ensemble methods"""
        try:
            if len(historical_data) < 6:
                # Fallback to simple average
                avg = np.mean(historical_data) if historical_data else 0
                return {
                    'forecast': [int(avg)] * months_ahead,
                    'historical': historical_data,
                    'method': 'simple_average',
                    'trend': 'stable',
                    'trend_percentage': 0
                }
            
            # Prepare features
            X, y = self.prepare_time_series_features(historical_data, lookback=3)
            
            if len(X) == 0:
                avg = np.mean(historical_data)
                return {
                    'forecast': [int(avg)] * months_ahead,
                    'historical': historical_data,
                    'method': 'average',
                    'trend': 'stable',
                    'trend_percentage': 0
                }
            
            # Scale features
            X_scaled = self.scaler.fit_transform(X)
            
            # Train ensemble
            rf_model = RandomForestRegressor(n_estimators=100, random_state=42)
            gb_model = GradientBoostingRegressor(n_estimators=100, random_state=42)
            lr_model = LinearRegression()
            
            rf_model.fit(X_scaled, y)
            gb_model.fit(X_scaled, y)
            lr_model.fit(X_scaled, y)
            
            # Make predictions
            forecast = []
            current_sequence = list(historical_data[-3:])
            
            for step in range(months_ahead):
                month_idx = (len(historical_data) + step) % 12
                year_trend = (len(historical_data) + step) // 12
                ma_3 = np.mean(current_sequence[-3:])
                ma_6 = np.mean(historical_data[-6:] + current_sequence) if len(historical_data) >= 6 else ma_3
                
                if len(current_sequence) >= 3:
                    slope = np.polyfit(np.arange(3), current_sequence[-3:], 1)[0]
                else:
                    slope = 0
                
                features = current_sequence[-3:] + [month_idx, year_trend, ma_3, ma_6, slope]
                features_scaled = self.scaler.transform([features])
                
                # Ensemble prediction
                pred_rf = rf_model.predict(features_scaled)[0]
                pred_gb = gb_model.predict(features_scaled)[0]
                pred_lr = lr_model.predict(features_scaled)[0]
                
                prediction = (pred_rf * 0.4 + pred_gb * 0.4 + pred_lr * 0.2)
                prediction = max(0, prediction)
                prediction = min(prediction, max(historical_data) * 2)
                
                forecast.append(int(round(prediction)))
                current_sequence.append(prediction)
            
            # Calculate trend
            avg_historical = np.mean(historical_data[-3:])
            avg_forecast = np.mean(forecast)
            trend_direction = 'increasing' if avg_forecast > avg_historical else 'decreasing'
            if abs(avg_forecast - avg_historical) / avg_historical < 0.05:
                trend_direction = 'stable'
            trend_percentage = ((avg_forecast - avg_historical) / avg_historical * 100) if avg_historical > 0 else 0
            
            return {
                'forecast': forecast,
                'historical': historical_data,
                'method': 'ensemble_ml',
                'trend': trend_direction,
                'trend_percentage': round(trend_percentage, 2)
            }
            
        except Exception as e:
            # Fallback
            avg = np.mean(historical_data) if historical_data else 0
            return {
                'forecast': [int(avg)] * months_ahead,
                'historical': historical_data,
                'method': 'fallback',
                'trend': 'stable',
                'trend_percentage': 0,
                'error': str(e)
            }


class MLInsightsGenerator:
    """Generate comprehensive ML insights"""
    
    def __init__(self, db):
        self.db = db
        self.forecaster = DemandForecaster()
    
    def get_pharmaceutical_usage(self, pharma_id=None, months=12):
        """Get pharmaceutical usage data"""
        query = """
            SELECT 
                DATE_FORMAT(t.request_date, '%Y-%m') as month,
                COALESCE(SUM(t.quantity), 0) as total_usage
            FROM transactions t
            WHERE t.status = 'Approved'
        """
        
        if pharma_id:
            query += f" AND t.pharma_id = {int(pharma_id)}"
        
        query += f"""
            GROUP BY DATE_FORMAT(t.request_date, '%Y-%m')
            ORDER BY month DESC
            LIMIT {int(months)}
        """
        
        self.db.cursor.execute(query)
        results = self.db.cursor.fetchall()
        
        data = [int(row['total_usage']) for row in results]
        return list(reversed(data))
    
    def get_livestock_population(self, animal_type='Livestock', months=12):
        """Get livestock/poultry population trends"""
        query = f"""
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COALESCE(SUM(quantity), 0) as total_population
            FROM livestock_poultry
            WHERE animal_type = '{animal_type}'
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month DESC
            LIMIT {int(months)}
        """
        
        self.db.cursor.execute(query)
        results = self.db.cursor.fetchall()
        
        data = [int(row['total_population']) for row in results]
        return list(reversed(data))
    
    def get_transaction_trends(self, months=12):
        """Get transaction volume trends"""
        query = f"""
            SELECT 
                DATE_FORMAT(request_date, '%Y-%m') as month,
                COUNT(*) as transaction_count
            FROM transactions
            GROUP BY DATE_FORMAT(request_date, '%Y-%m')
            ORDER BY month DESC
            LIMIT {int(months)}
        """
        
        self.db.cursor.execute(query)
        results = self.db.cursor.fetchall()
        
        data = [int(row['transaction_count']) for row in results]
        return list(reversed(data))
    
    def generate_month_labels(self, months, future=False):
        """Generate month labels"""
        labels = []
        for i in range(months):
            if future:
                date = datetime.now() + timedelta(days=30 * (i + 1))
            else:
                date = datetime.now() - timedelta(days=30 * (months - i - 1))
            labels.append(date.strftime('%b %Y'))
        return labels
    
    def calculate_trend_info(self, historical, forecast):
        """Calculate trend information"""
        current_avg = np.mean(historical[-3:]) if len(historical) >= 3 else np.mean(historical)
        forecast_avg = np.mean(forecast)
        
        percentage_change = ((forecast_avg - current_avg) / current_avg * 100) if current_avg > 0 else 0
        
        if abs(percentage_change) < 5:
            trend = 'stable'
            trend_emoji = '➖'
            trend_text = 'Stable trend expected'
        elif percentage_change > 0:
            trend = 'increasing'
            trend_emoji = '📈'
            trend_text = f"Increasing trend (+{percentage_change:.1f}%)"
        else:
            trend = 'decreasing'
            trend_emoji = '📉'
            trend_text = f"Decreasing trend ({percentage_change:.1f}%)"
        
        return {
            'trend': trend,
            'trend_emoji': trend_emoji,
            'trend_text': trend_text,
            'percentage_change': round(percentage_change, 1)
        }
    
    def generate_insights(self):
        """Generate all insights"""
        insights = {}
        
        # Pharmaceutical forecast
        pharma_data = self.get_pharmaceutical_usage(months=12)
        pharma_forecast = self.forecaster.forecast(pharma_data, months_ahead=3)
        pharma_trend = self.calculate_trend_info(pharma_forecast['historical'], pharma_forecast['forecast'])
        
        insights['pharmaceutical_demand'] = {
            'title': 'Pharmaceutical Demand Forecast',
            'forecast': pharma_forecast['forecast'],
            'historical': pharma_forecast['historical'],
            'historical_labels': self.generate_month_labels(len(pharma_forecast['historical'])),
            'forecast_labels': self.generate_month_labels(3, future=True),
            **pharma_trend
        }
        
        # Livestock forecast
        livestock_data = self.get_livestock_population('Livestock', months=12)
        livestock_forecast = self.forecaster.forecast(livestock_data, months_ahead=3)
        livestock_trend = self.calculate_trend_info(livestock_forecast['historical'], livestock_forecast['forecast'])
        
        insights['livestock_population'] = {
            'title': 'Livestock Population Forecast',
            'forecast': livestock_forecast['forecast'],
            'historical': livestock_forecast['historical'],
            'historical_labels': self.generate_month_labels(len(livestock_forecast['historical'])),
            'forecast_labels': self.generate_month_labels(3, future=True),
            **livestock_trend
        }
        
        # Poultry forecast
        poultry_data = self.get_livestock_population('Poultry', months=12)
        poultry_forecast = self.forecaster.forecast(poultry_data, months_ahead=3)
        poultry_trend = self.calculate_trend_info(poultry_forecast['historical'], poultry_forecast['forecast'])
        
        insights['poultry_population'] = {
            'title': 'Poultry Population Forecast',
            'forecast': poultry_forecast['forecast'],
            'historical': poultry_forecast['historical'],
            'historical_labels': self.generate_month_labels(len(poultry_forecast['historical'])),
            'forecast_labels': self.generate_month_labels(3, future=True),
            **poultry_trend
        }
        
        # Transaction forecast
        transaction_data = self.get_transaction_trends(months=12)
        transaction_forecast = self.forecaster.forecast(transaction_data, months_ahead=3)
        transaction_trend = self.calculate_trend_info(transaction_forecast['historical'], transaction_forecast['forecast'])
        
        insights['transaction_volume'] = {
            'title': 'Transaction Volume Forecast',
            'forecast': transaction_forecast['forecast'],
            'historical': transaction_forecast['historical'],
            'historical_labels': self.generate_month_labels(len(transaction_forecast['historical'])),
            'forecast_labels': self.generate_month_labels(3, future=True),
            **transaction_trend
        }
        
        return insights


# =============================================================================
# FLASK ROUTES
# =============================================================================

@app.route('/', methods=['GET'])
def home():
    """API home endpoint"""
    return jsonify({
        'success': True,
        'message': 'ML Flask API for Veterinary Management System',
        'version': '1.0.0',
        'endpoints': {
            'GET /': 'This help message',
            'GET /health': 'Health check',
            'POST /api/forecast': 'Generate ML forecasts',
            'POST /api/forecast/pharmaceutical': 'Pharmaceutical demand forecast',
            'POST /api/forecast/livestock': 'Livestock population forecast',
            'POST /api/forecast/poultry': 'Poultry population forecast',
            'GET /api/insights': 'Get comprehensive ML insights',
            'POST /api/health/predict': 'Predict animal health risk',
            'GET /api/health/assess/<animal_id>': 'Assess specific animal health'
        }
    })


@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({
        'success': True,
        'status': 'healthy',
        'timestamp': datetime.now().isoformat()
    })


@app.route('/api/insights', methods=['GET'])
def get_insights():
    """Get comprehensive ML insights"""
    db = DatabaseConnection()
    
    try:
        if not db.connect():
            return jsonify({
                'success': False,
                'error': 'Database connection failed'
            }), 500
        
        ml_system = MLInsightsGenerator(db)
        insights = ml_system.generate_insights()
        
        return jsonify({
            'success': True,
            'insights': insights,
            'generated_at': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
            'model': 'Ensemble ML (RF + GB + LR)'
        })
        
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500
    
    finally:
        db.close()


@app.route('/api/forecast', methods=['POST'])
def forecast():
    """General forecast endpoint"""
    try:
        data = request.get_json()
        
        if not data:
            return jsonify({
                'success': False,
                'error': 'No data provided'
            }), 400
        
        forecast_type = data.get('type', 'pharmaceutical')
        historical_data = data.get('historical_data', [])
        months_ahead = data.get('months_ahead', 3)
        
        if not historical_data:
            return jsonify({
                'success': False,
                'error': 'historical_data is required'
            }), 400
        
        forecaster = DemandForecaster()
        result = forecaster.forecast(historical_data, months_ahead)
        
        return jsonify({
            'success': True,
            'type': forecast_type,
            'result': result,
            'timestamp': datetime.now().isoformat()
        })
        
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500


@app.route('/api/forecast/pharmaceutical', methods=['POST'])
def forecast_pharmaceutical():
    """Pharmaceutical-specific forecast"""
    db = DatabaseConnection()
    
    try:
        data = request.get_json() or {}
        pharma_id = data.get('pharma_id')
        months_ahead = data.get('months_ahead', 3)
        
        if not db.connect():
            return jsonify({
                'success': False,
                'error': 'Database connection failed'
            }), 500
        
        ml_system = MLInsightsGenerator(db)
        usage_data = ml_system.get_pharmaceutical_usage(pharma_id, months=12)
        
        result = ml_system.forecaster.forecast(usage_data, months_ahead)
        
        return jsonify({
            'success': True,
            'forecast': result['forecast'],
            'historical': result['historical'],
            'historical_labels': ml_system.generate_month_labels(len(result['historical'])),
            'forecast_labels': ml_system.generate_month_labels(months_ahead, future=True),
            'trend': result['trend'],
            'trend_percentage': result['trend_percentage'],
            'timestamp': datetime.now().isoformat()
        })
        
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500
    
    finally:
        db.close()


@app.route('/api/forecast/livestock', methods=['POST'])
def forecast_livestock():
    """Livestock population forecast"""
    db = DatabaseConnection()
    
    try:
        data = request.get_json() or {}
        species = data.get('species', 'Cattle')
        months_ahead = data.get('months_ahead', 3)
        
        if not db.connect():
            return jsonify({
                'success': False,
                'error': 'Database connection failed'
            }), 500
        
        ml_system = MLInsightsGenerator(db)
        population_data = ml_system.get_livestock_population('Livestock', months=12)
        
        result = ml_system.forecaster.forecast(population_data, months_ahead)
        
        return jsonify({
            'success': True,
            'forecast': result['forecast'],
            'historical': result['historical'],
            'historical_labels': ml_system.generate_month_labels(len(result['historical'])),
            'forecast_labels': ml_system.generate_month_labels(months_ahead, future=True),
            'trend': result['trend'],
            'trend_percentage': result['trend_percentage'],
            'species': species,
            'timestamp': datetime.now().isoformat()
        })
        
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500
    
    finally:
        db.close()


@app.route('/api/forecast/poultry', methods=['POST'])
def forecast_poultry():
    """Poultry population forecast"""
    db = DatabaseConnection()
    
    try:
        data = request.get_json() or {}
        species = data.get('species', 'Chicken')
        months_ahead = data.get('months_ahead', 3)
        
        if not db.connect():
            return jsonify({
                'success': False,
                'error': 'Database connection failed'
            }), 500
        
        ml_system = MLInsightsGenerator(db)
        population_data = ml_system.get_livestock_population('Poultry', months=12)
        
        result = ml_system.forecaster.forecast(population_data, months_ahead)
        
        return jsonify({
            'success': True,
            'forecast': result['forecast'],
            'historical': result['historical'],
            'historical_labels': ml_system.generate_month_labels(len(result['historical'])),
            'forecast_labels': ml_system.generate_month_labels(months_ahead, future=True),
            'trend': result['trend'],
            'trend_percentage': result['trend_percentage'],
            'species': species,
            'timestamp': datetime.now().isoformat()
        })
        
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500
    
    finally:
        db.close()


@app.route('/api/health/predict', methods=['POST'])
def predict_health_risk():
    """Predict animal health risk using ML"""
    try:
        data = request.get_json()
        
        if not data:
            return jsonify({
                'success': False,
                'error': 'No data provided'
            }), 400
        
        # Load ML models for health prediction
        import joblib
        import os
        
        models_dir = os.path.join(os.path.dirname(__file__), '..', 'models')
        model_path = os.path.join(models_dir, 'health_risk_model.pkl')
        encoder_path = os.path.join(models_dir, 'label_encoder.pkl')
        scaler_path = os.path.join(models_dir, 'scaler.pkl')
        
        # Check if models exist
        if not all(os.path.exists(p) for p in [model_path, encoder_path, scaler_path]):
            # Use simple rule-based prediction as fallback
            return simple_health_risk_prediction(data)
        
        # Load models
        model = joblib.load(model_path)
        label_encoder = joblib.load(encoder_path)
        scaler = joblib.load(scaler_path)
        
        # Extract features (simplified version)
        features = extract_health_features(data)
        features_scaled = scaler.transform([features])
        
        # Make prediction
        prediction = model.predict(features_scaled)[0]
        probabilities = model.predict_proba(features_scaled)[0]
        
        risk_level = label_encoder.inverse_transform([prediction])[0]
        confidence = float(max(probabilities))
        
        # Create probabilities dict
        probs_dict = {}
        for i, class_name in enumerate(label_encoder.classes_):
            probs_dict[class_name] = float(probabilities[i])
        
        # Calculate risk score
        risk_score_map = {'Low': 20, 'Medium': 50, 'High': 75, 'Critical': 95}
        weighted_score = sum(
            risk_score_map.get(cls, 50) * prob 
            for cls, prob in probs_dict.items()
        )
        
        # Generate recommendations
        recommendations = generate_health_recommendations(
            risk_level,
            data.get('symptoms', []),
            data.get('vital_signs', {})
        )
        
        return jsonify({
            'success': True,
            'risk_level': risk_level,
            'risk_score': int(weighted_score),
            'confidence': confidence,
            'probabilities': probs_dict,
            'recommendations': recommendations,
            'model_version': 'advanced_v1.0',
            'timestamp': datetime.now().isoformat()
        })
        
    except Exception as e:
        # Fallback to simple prediction
        return simple_health_risk_prediction(data)


def simple_health_risk_prediction(data):
    """Simple rule-based health risk prediction (fallback)"""
    symptoms = data.get('symptoms', [])
    vital_signs = data.get('vital_signs', {})
    
    risk_score = 0
    
    # Symptom-based scoring
    critical_symptoms = ['sudden_death', 'paralysis', 'convulsions', 'difficulty_breathing']
    high_symptoms = ['high_fever', 'mataas_na_lagnat', 'severe_diarrhea']
    
    for symptom in symptoms:
        if symptom in critical_symptoms:
            risk_score += 30
        elif symptom in high_symptoms:
            risk_score += 20
        else:
            risk_score += 10
    
    # Temperature-based scoring
    temp = vital_signs.get('temperature', 38.5)
    if temp > 40:
        risk_score += 25
    elif temp > 39.5:
        risk_score += 15
    elif temp < 37:
        risk_score += 10
    
    # Determine risk level
    if risk_score >= 80:
        risk_level = 'Critical'
    elif risk_score >= 60:
        risk_level = 'High'
    elif risk_score >= 40:
        risk_level = 'Medium'
    else:
        risk_level = 'Low'
    
    recommendations = generate_health_recommendations(risk_level, symptoms, vital_signs)
    
    return jsonify({
        'success': True,
        'risk_level': risk_level,
        'risk_score': min(100, risk_score),
        'confidence': 0.7,
        'probabilities': {risk_level: 0.7},
        'recommendations': recommendations,
        'model_version': 'rule_based_fallback',
        'timestamp': datetime.now().isoformat()
    })


def extract_health_features(data):
    """Extract features for health prediction"""
    # Simplified feature extraction
    symptoms = data.get('symptoms', [])
    vital_signs = data.get('vital_signs', {})
    
    # Basic features (simplified - actual model needs 40+ features)
    features = []
    
    # Symptom count
    features.append(len(symptoms))
    
    # Vital signs
    features.append(vital_signs.get('temperature', 38.5))
    features.append(vital_signs.get('weight', 0))
    features.append(vital_signs.get('heart_rate', 0))
    
    # Add more features as needed
    features.extend([0] * 36)  # Placeholder for remaining features
    
    return features


def generate_health_recommendations(risk_level, symptoms, vital_signs):
    """Generate health recommendations"""
    recommendations = []
    
    if risk_level == 'Critical':
        recommendations.append("🚨 URGENT: Immediate veterinary attention required")
        recommendations.append("Isolate animal from herd to prevent disease spread")
        recommendations.append("Contact veterinarian for emergency consultation")
    elif risk_level == 'High':
        recommendations.append("⚠️ Schedule veterinary examination within 24 hours")
        recommendations.append("Monitor vital signs closely (every 2-4 hours)")
        recommendations.append("Separate from healthy animals if possible")
    elif risk_level == 'Medium':
        recommendations.append("📋 Schedule routine veterinary check-up")
        recommendations.append("Monitor animal behavior and appetite daily")
        recommendations.append("Keep detailed health records")
    else:
        recommendations.append("✅ Continue regular health monitoring")
        recommendations.append("Maintain vaccination schedule")
        recommendations.append("Ensure proper nutrition and housing")
    
    # Symptom-specific recommendations
    if 'fever' in symptoms or 'high_fever' in symptoms or 'mataas_na_lagnat' in symptoms:
        recommendations.append("Monitor temperature regularly (every 4 hours)")
        recommendations.append("Ensure adequate hydration")
    
    if 'diarrhea' in symptoms or 'pagtatae' in symptoms:
        recommendations.append("Provide electrolyte solution to prevent dehydration")
    
    return recommendations


@app.route('/api/health/assess/<int:animal_id>', methods=['GET'])
def assess_animal_health(animal_id):
    """Assess animal health risk by animal ID"""
    db = DatabaseConnection()
    
    try:
        if not db.connect():
            return jsonify({
                'success': False,
                'error': 'Database connection failed'
            }), 500
        
        # Get animal data from database
        query = """
            SELECT lp.*, c.barangay, c.latitude, c.longitude,
                   c.fname, c.mname, c.lname
            FROM livestock_poultry lp
            JOIN clients c ON lp.client_id = c.client_id
            WHERE lp.animal_id = %s
        """
        
        db.cursor.execute(query, (animal_id,))
        animal_data = db.cursor.fetchone()
        
        if not animal_data:
            return jsonify({
                'success': False,
                'error': 'Animal not found'
            }), 404
        
        # Get symptoms from recent pharmaceutical requests
        symptoms_query = """
            SELECT symptoms, species, request_date
            FROM pharmaceutical_requests
            WHERE client_id = %s
            AND status IN ('approved', 'Pending')
            AND request_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY request_date DESC
            LIMIT 5
        """
        
        db.cursor.execute(symptoms_query, (animal_data['client_id'],))
        symptoms_data = db.cursor.fetchall()
        
        # Extract symptoms
        symptom_list = []
        for symptom_row in symptoms_data:
            if symptom_row['symptoms']:
                symptom_list.extend([s.strip() for s in symptom_row['symptoms'].split(';')])
        
        # Prepare data for prediction
        prediction_data = {
            'animal_id': animal_id,
            'symptoms': list(set(symptom_list)),  # Unique symptoms
            'vital_signs': {
                'temperature': 38.5,  # Default
                'weight': 0,
                'heart_rate': 0
            },
            'environment': {
                'temperature': 28.0,
                'humidity': 65.0,
                'season': get_current_season()
            },
            'animal_characteristics': {
                'species': animal_data.get('species', 'unknown'),
                'breed': animal_data.get('animal_type', 'unknown'),
                'age': calculate_age(animal_data.get('birth_date')),
                'type': animal_data.get('animal_type', 'livestock')
            },
            'health_status': 'under observation' if symptom_list else 'healthy'
        }
        
        # Make prediction
        prediction_response = simple_health_risk_prediction(prediction_data)
        result = prediction_response.get_json()
        
        # Add animal info
        result['animal_info'] = {
            'animal_id': animal_id,
            'species': animal_data.get('species'),
            'animal_type': animal_data.get('animal_type'),
            'client_name': f"{animal_data.get('fname', '')} {animal_data.get('lname', '')}".strip(),
            'barangay': animal_data.get('barangay')
        }
        
        return jsonify(result)
        
    except Exception as e:
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500
    
    finally:
        db.close()


def get_current_season():
    """Get current season"""
    month = datetime.now().month
    if month in [12, 1, 2]:
        return 'winter'
    elif month in [3, 4, 5]:
        return 'spring'
    elif month in [6, 7, 8]:
        return 'summer'
    else:
        return 'autumn'


def calculate_age(birth_date):
    """Calculate animal age in years"""
    if not birth_date:
        return 0
    try:
        birth = datetime.strptime(str(birth_date), '%Y-%m-%d')
        return (datetime.now() - birth).days // 365
    except:
        return 0


# Error handlers
@app.errorhandler(404)
def not_found(error):
    return jsonify({
        'success': False,
        'error': 'Endpoint not found'
    }), 404


@app.errorhandler(500)
def internal_error(error):
    return jsonify({
        'success': False,
        'error': 'Internal server error'
    }), 500


# =============================================================================
# MAIN
# =============================================================================

if __name__ == '__main__':
    print("=" * 60)
    print("ML Flask API Server")
    print("=" * 60)
    print("Starting server on http://localhost:5000")
    print("API Endpoints:")
    print("  GET  /                  - API info")
    print("  GET  /health            - Health check")
    print("  GET  /api/insights      - Full ML insights")
    print("  POST /api/forecast      - General forecast")
    print("=" * 60)
    
    # Run Flask app
    app.run(
        host='0.0.0.0',  # Allow external connections
        port=5000,
        debug=True,      # Enable debug mode for development
        threaded=True    # Handle multiple requests
    )

