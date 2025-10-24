#!/usr/bin/env python3
"""
ML Demand Forecasting Script
Uses Python ML models to forecast pharmaceutical, livestock, and poultry demand
"""

import sys
import json
import numpy as np
from datetime import datetime, timedelta
import warnings
warnings.filterwarnings('ignore')

try:
    from sklearn.ensemble import RandomForestRegressor, GradientBoostingRegressor
    from sklearn.linear_model import LinearRegression
    from sklearn.preprocessing import StandardScaler
    import joblib
except ImportError:
    print(json.dumps({
        'error': 'Required packages not installed. Run: pip install scikit-learn joblib numpy'
    }))
    sys.exit(1)

class DemandForecaster:
    def __init__(self):
        self.models = {}
        self.scaler = StandardScaler()
        
    def prepare_time_series_features(self, data, lookback=3):
        """Prepare features from time series data"""
        X = []
        y = []
        
        data_array = np.array(data)
        
        for i in range(lookback, len(data_array)):
            # Features: past 'lookback' values
            features = list(data_array[i-lookback:i])
            
            # Add time-based features
            features.append(i % 12)  # Month of year (cyclical)
            features.append(i // 12)  # Year trend
            
            # Add moving averages
            features.append(np.mean(data_array[max(0, i-3):i]))  # 3-month MA
            features.append(np.mean(data_array[max(0, i-6):i]))  # 6-month MA
            
            # Add trend (slope of recent values)
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
    
    def forecast_pharmaceutical_demand(self, historical_data, months_ahead=3):
        """Forecast pharmaceutical demand using ensemble methods"""
        try:
            if len(historical_data) < 6:
                return {
                    'error': 'Insufficient data',
                    'message': 'Need at least 6 months of historical data'
                }
            
            # Prepare features
            X, y = self.prepare_time_series_features(historical_data, lookback=3)
            
            if len(X) == 0:
                return {'error': 'Could not prepare features from data'}
            
            # Scale features
            X_scaled = self.scaler.fit_transform(X)
            
            # Train ensemble of models
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
                # Prepare features for next prediction
                month_idx = (len(historical_data) + step) % 12
                year_trend = (len(historical_data) + step) // 12
                ma_3 = np.mean(current_sequence[-3:])
                ma_6 = np.mean(historical_data[-6:] + current_sequence) if len(historical_data) >= 6 else ma_3
                
                # Calculate trend
                if len(current_sequence) >= 3:
                    x_vals = np.arange(3)
                    y_vals = current_sequence[-3:]
                    slope = np.polyfit(x_vals, y_vals, 1)[0]
                else:
                    slope = 0
                
                features = current_sequence[-3:] + [month_idx, year_trend, ma_3, ma_6, slope]
                features_scaled = self.scaler.transform([features])
                
                # Ensemble prediction (weighted average)
                pred_rf = rf_model.predict(features_scaled)[0]
                pred_gb = gb_model.predict(features_scaled)[0]
                pred_lr = lr_model.predict(features_scaled)[0]
                
                # Weighted ensemble (more weight to RF and GB)
                prediction = (pred_rf * 0.4 + pred_gb * 0.4 + pred_lr * 0.2)
                
                # Ensure non-negative and reasonable bounds
                prediction = max(0, prediction)
                prediction = min(prediction, max(historical_data) * 2)  # Cap at 2x max historical
                
                forecast.append(round(prediction, 2))
                current_sequence.append(prediction)
            
            # Calculate confidence intervals (simple approach)
            std_dev = np.std(y)
            confidence_interval = [
                [max(0, f - 1.96 * std_dev), f + 1.96 * std_dev] 
                for f in forecast
            ]
            
            # Calculate trend
            avg_historical = np.mean(historical_data[-3:])
            avg_forecast = np.mean(forecast)
            trend_direction = 'increasing' if avg_forecast > avg_historical else 'decreasing'
            trend_percentage = ((avg_forecast - avg_historical) / avg_historical * 100) if avg_historical > 0 else 0
            
            return {
                'forecast': forecast,
                'confidence_intervals': confidence_interval,
                'trend': trend_direction,
                'trend_percentage': round(trend_percentage, 2),
                'model': 'ensemble',
                'accuracy_estimate': self.estimate_accuracy(historical_data, lookback=3)
            }
            
        except Exception as e:
            return {'error': f'Forecasting failed: {str(e)}'}
    
    def forecast_livestock_demand(self, historical_data, months_ahead=3):
        """Forecast livestock population/demand"""
        try:
            if len(historical_data) < 4:
                return {
                    'error': 'Insufficient data',
                    'message': 'Need at least 4 months of historical data'
                }
            
            # Livestock tends to have more stable growth
            # Use simpler exponential smoothing with trend
            
            # Calculate trend and seasonality
            alpha = 0.3  # Smoothing factor for level
            beta = 0.1   # Smoothing factor for trend
            
            # Initialize
            level = historical_data[0]
            trend = (historical_data[-1] - historical_data[0]) / (len(historical_data) - 1)
            
            # Smooth the historical data
            smoothed = []
            for value in historical_data:
                level_prev = level
                level = alpha * value + (1 - alpha) * (level + trend)
                trend = beta * (level - level_prev) + (1 - beta) * trend
                smoothed.append(level)
            
            # Forecast
            forecast = []
            for step in range(1, months_ahead + 1):
                prediction = level + step * trend
                
                # Add slight random variation for realism
                variation = np.random.normal(0, np.std(historical_data) * 0.1)
                prediction = max(0, prediction + variation)
                
                forecast.append(round(prediction, 1))
            
            # Calculate trend
            avg_historical = np.mean(historical_data[-3:])
            avg_forecast = np.mean(forecast)
            trend_direction = 'increasing' if avg_forecast > avg_historical else 'decreasing'
            trend_percentage = ((avg_forecast - avg_historical) / avg_historical * 100) if avg_historical > 0 else 0
            
            return {
                'forecast': forecast,
                'trend': trend_direction,
                'trend_percentage': round(trend_percentage, 2),
                'model': 'exponential_smoothing',
                'growth_rate': round(trend, 2)
            }
            
        except Exception as e:
            return {'error': f'Forecasting failed: {str(e)}'}
    
    def forecast_poultry_demand(self, historical_data, months_ahead=3):
        """Forecast poultry population/demand"""
        try:
            if len(historical_data) < 4:
                return {
                    'error': 'Insufficient data',
                    'message': 'Need at least 4 months of historical data'
                }
            
            # Poultry can have more volatility than livestock
            # Use ARIMA-like approach with seasonal component
            
            # Detect seasonality (if data is long enough)
            if len(historical_data) >= 12:
                seasonal_period = 12
                seasonal_indices = []
                for i in range(seasonal_period):
                    seasonal_values = [historical_data[j] for j in range(i, len(historical_data), seasonal_period)]
                    seasonal_indices.append(np.mean(seasonal_values) / np.mean(historical_data))
            else:
                seasonal_indices = [1.0] * 12
            
            # Deseasonalize
            deseasonalized = []
            for i, value in enumerate(historical_data):
                season_idx = i % len(seasonal_indices)
                deseasonalized.append(value / seasonal_indices[season_idx])
            
            # Fit trend
            x = np.arange(len(deseasonalized))
            z = np.polyfit(x, deseasonalized, 1)
            p = np.poly1d(z)
            
            # Forecast
            forecast = []
            for step in range(months_ahead):
                future_idx = len(historical_data) + step
                trend_value = p(future_idx)
                season_idx = future_idx % len(seasonal_indices)
                seasonal_value = trend_value * seasonal_indices[season_idx]
                
                # Add noise based on historical variance
                noise = np.random.normal(0, np.std(historical_data) * 0.05)
                prediction = max(0, seasonal_value + noise)
                
                forecast.append(round(prediction, 1))
            
            # Calculate trend
            avg_historical = np.mean(historical_data[-3:])
            avg_forecast = np.mean(forecast)
            trend_direction = 'increasing' if avg_forecast > avg_historical else 'decreasing'
            trend_percentage = ((avg_forecast - avg_historical) / avg_historical * 100) if avg_historical > 0 else 0
            
            return {
                'forecast': forecast,
                'trend': trend_direction,
                'trend_percentage': round(trend_percentage, 2),
                'model': 'seasonal_trend',
                'seasonal_strength': round(np.std(seasonal_indices), 3) if len(historical_data) >= 12 else 0
            }
            
        except Exception as e:
            return {'error': f'Forecasting failed: {str(e)}'}
    
    def estimate_accuracy(self, data, lookback=3):
        """Estimate forecast accuracy using historical data"""
        if len(data) < lookback + 3:
            return None
        
        # Use last few points as test set
        train_data = data[:-3]
        test_data = data[-3:]
        
        try:
            X, y = self.prepare_time_series_features(train_data, lookback=lookback)
            if len(X) < 2:
                return None
            
            X_scaled = self.scaler.fit_transform(X)
            model = RandomForestRegressor(n_estimators=50, random_state=42)
            model.fit(X_scaled, y)
            
            # Predict test set
            errors = []
            for i in range(len(test_data)):
                if i == 0:
                    sequence = list(train_data[-lookback:])
                else:
                    sequence = list(train_data[-lookback+i:]) + list(test_data[:i])
                
                month_idx = (len(train_data) + i) % 12
                year_trend = (len(train_data) + i) // 12
                ma_3 = np.mean(sequence[-3:])
                ma_6 = np.mean(train_data[-6:]) if len(train_data) >= 6 else ma_3
                
                if len(sequence) >= 3:
                    slope = np.polyfit(np.arange(3), sequence[-3:], 1)[0]
                else:
                    slope = 0
                
                features = sequence[-lookback:] + [month_idx, year_trend, ma_3, ma_6, slope]
                features_scaled = self.scaler.transform([features])
                prediction = model.predict(features_scaled)[0]
                
                error = abs(prediction - test_data[i]) / test_data[i] if test_data[i] > 0 else 0
                errors.append(error)
            
            mape = np.mean(errors) * 100  # Mean Absolute Percentage Error
            accuracy = max(0, 100 - mape)
            
            return round(accuracy, 1)
            
        except:
            return None

def main():
    if len(sys.argv) != 2:
        print(json.dumps({
            'error': 'Usage: python ml_demand_forecast.py <forecast_config.json>'
        }))
        sys.exit(1)
    
    config_file = sys.argv[1]
    
    try:
        # Load configuration
        with open(config_file, 'r') as f:
            config = json.load(f)
        
        forecast_type = config.get('type', 'pharmaceutical')
        historical_data = config.get('historical_data', [])
        months_ahead = config.get('months_ahead', 3)
        
        # Initialize forecaster
        forecaster = DemandForecaster()
        
        # Perform forecast based on type
        if forecast_type == 'pharmaceutical':
            result = forecaster.forecast_pharmaceutical_demand(historical_data, months_ahead)
        elif forecast_type == 'livestock':
            result = forecaster.forecast_livestock_demand(historical_data, months_ahead)
        elif forecast_type == 'poultry':
            result = forecaster.forecast_poultry_demand(historical_data, months_ahead)
        else:
            result = {'error': f'Unknown forecast type: {forecast_type}'}
        
        # Add metadata
        result['timestamp'] = datetime.now().isoformat()
        result['input_type'] = forecast_type
        result['historical_count'] = len(historical_data)
        result['forecast_months'] = months_ahead
        
        # Output result
        print(json.dumps(result, indent=2))
        
    except FileNotFoundError:
        print(json.dumps({'error': f'Config file not found: {config_file}'}))
        sys.exit(1)
    except json.JSONDecodeError:
        print(json.dumps({'error': f'Invalid JSON in config file: {config_file}'}))
        sys.exit(1)
    except Exception as e:
        print(json.dumps({'error': f'Forecast failed: {str(e)}'}))
        sys.exit(1)

if __name__ == '__main__':
    main()

