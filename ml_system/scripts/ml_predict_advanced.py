#!/usr/bin/env python3
"""
Advanced ML Prediction Script
Makes predictions using the trained advanced ML model
"""

import sys
import json
import os
import joblib
import numpy as np
from sklearn.preprocessing import StandardScaler

def load_models():
    """Load trained ML models and preprocessors"""
    try:
        models_dir = 'models'
        if not os.path.exists(models_dir):
            return None, None, None, None
        
        model_path = os.path.join(models_dir, 'health_risk_model.pkl')
        encoder_path = os.path.join(models_dir, 'label_encoder.pkl')
        scaler_path = os.path.join(models_dir, 'scaler.pkl')
        metadata_path = os.path.join(models_dir, 'model_metadata.json')
        
        if not all(os.path.exists(p) for p in [model_path, encoder_path, scaler_path]):
            return None, None, None, None
        
        model = joblib.load(model_path)
        label_encoder = joblib.load(encoder_path)
        scaler = joblib.load(scaler_path)
        
        # Load metadata
        metadata = None
        if os.path.exists(metadata_path):
            with open(metadata_path, 'r') as f:
                metadata = json.load(f)
        
        return model, label_encoder, scaler, metadata
        
    except Exception as e:
        print(json.dumps({'error': f'Failed to load models: {str(e)}'}))
        sys.exit(1)

def get_symptom_list():
    """Get comprehensive symptom list"""
    return [
        'fever', 'lethargy', 'loss_of_appetite', 'diarrhea', 'vomiting',
        'difficulty_breathing', 'swollen', 'weakness', 'convulsions',
        'paralysis', 'sudden_death', 'lameness', 'behavior_change',
        'high_fever', 'mataas_na_lagnat', 'pagtatae', 'pagsusuka',
        'hirap_huminga', 'namamaga', 'panghihina', 'kombulsyon',
        'pagkaparalisa', 'biglaang_pagkamatay', 'hirap_tumayo'
    ]

def extract_features(animal_data):
    """Extract enhanced features from animal data"""
    features = []
    symptom_list = get_symptom_list()
    
    # 1. Symptom features (binary encoding)
    symptoms = animal_data.get('symptoms', [])
    symptom_features = [1 if symptom in symptoms else 0 for symptom in symptom_list]
    features.extend(symptom_features)
    
    # 2. Symptom count and severity
    features.append(len(symptoms))  # Total symptoms
    critical_symptoms = {'sudden_death', 'paralysis', 'convulsions', 'difficulty_breathing'}
    features.append(sum(1 for s in symptoms if s in critical_symptoms))  # Critical symptom count
    
    # 3. Vital signs
    vital_signs = animal_data.get('vital_signs', {})
    temperature = vital_signs.get('temperature', 38.5)
    weight = vital_signs.get('weight', 0)
    heart_rate = vital_signs.get('heart_rate', 0)
    
    features.extend([temperature, weight, heart_rate])
    
    # Temperature deviation
    animal_type = animal_data.get('animal_characteristics', {}).get('type', 'livestock')
    if not animal_type:
        animal_type = animal_data.get('type', 'livestock')
    
    normal_temp = 41.0 if animal_type == 'poultry' else 38.5
    temp_deviation = abs(temperature - normal_temp)
    features.append(temp_deviation)
    
    # 4. Environmental factors
    environment = animal_data.get('environment', {})
    features.extend([
        environment.get('temperature', 28.0),
        environment.get('humidity', 65.0),
        get_season_encoding(environment.get('season', 'summer'))
    ])
    
    # 5. Animal characteristics
    animal_chars = animal_data.get('animal_characteristics', {})
    age = animal_chars.get('age', animal_data.get('age', 0))
    breed = animal_chars.get('breed', animal_data.get('breed', 'unknown'))
    species = animal_chars.get('species', animal_data.get('species', 'unknown'))
    animal_type_val = animal_chars.get('type', animal_data.get('type', 'unknown'))
    
    features.extend([
        age,
        get_breed_encoding(breed),
        get_species_encoding(species),
        get_animal_type_encoding(animal_type_val)
    ])
    
    # 6. Health status encoding
    health_status = animal_data.get('health_status', 'unknown')
    features.append(get_health_status_encoding(health_status))
    
    # 7. Interaction features
    age_weight_ratio = weight / (age + 1) if age > 0 and weight > 0 else 0
    features.append(age_weight_ratio)
    
    symptom_temp_interaction = len(symptoms) * temp_deviation
    features.append(symptom_temp_interaction)
    
    return features

def get_season_encoding(season):
    """Encode season"""
    season_map = {'spring': 0, 'summer': 1, 'autumn': 2, 'winter': 3}
    return season_map.get(season, 1)

def get_breed_encoding(breed):
    """Encode breed"""
    breed_lower = str(breed).lower()
    breed_map = {
        'native': 0, 'brahman': 1, 'holstein': 2, 'jersey': 3,
        'duroc': 4, 'landrace': 5, 'large white': 6,
        'boer': 7, 'anglo-nubian': 8,
        'broiler': 9, 'layer': 10, 'native chicken': 11,
        'pekin': 12, 'muscovy': 13
    }
    for key, val in breed_map.items():
        if key in breed_lower:
            return val
    return 99

def get_species_encoding(species):
    """Encode species"""
    species_map = {
        'cattle': 0, 'carabao': 1, 'swine': 2, 'goat': 3, 'sheep': 4,
        'chicken': 5, 'duck': 6, 'other': 7
    }
    return species_map.get(str(species).lower(), 7)

def get_animal_type_encoding(animal_type):
    """Encode animal type"""
    type_map = {'livestock': 0, 'poultry': 1, 'unknown': 2}
    return type_map.get(str(animal_type).lower(), 2)

def get_health_status_encoding(health_status):
    """Encode health status"""
    status_map = {
        'healthy': 0, 'good': 1,
        'under observation': 2, 'recovering': 3,
        'sick': 4, 'critical': 5, 'quarantine': 6,
        'unknown': 7
    }
    return status_map.get(str(health_status).lower(), 7)

def predict_health_risk(animal_data, model, label_encoder, scaler):
    """Predict health risk using advanced ML model"""
    try:
        # Extract features
        features = extract_features(animal_data)
        features_array = np.array([features])
        
        # Scale features
        features_scaled = scaler.transform(features_array)
        
        # Make prediction
        prediction = model.predict(features_scaled)[0]
        probabilities = model.predict_proba(features_scaled)[0]
        
        # Get risk level
        risk_level = label_encoder.inverse_transform([prediction])[0]
        confidence = max(probabilities)
        
        # Create probabilities dictionary
        probs_dict = {}
        for i, class_name in enumerate(label_encoder.classes_):
            probs_dict[class_name] = float(probabilities[i])
        
        # Calculate risk score (0-100)
        risk_score_map = {'Low': 20, 'Medium': 50, 'High': 75, 'Critical': 95}
        risk_score = risk_score_map.get(risk_level, 50)
        
        # Adjust risk score based on probabilities
        weighted_score = sum(
            risk_score_map.get(cls, 50) * prob 
            for cls, prob in probs_dict.items()
        )
        
        # Generate recommendations
        recommendations = generate_recommendations(
            risk_level, 
            animal_data.get('symptoms', []),
            animal_data.get('vital_signs', {}),
            animal_data.get('health_status', 'unknown')
        )
        
        return {
            'risk_level': risk_level,
            'risk_score': int(weighted_score),
            'confidence': float(confidence),
            'probabilities': probs_dict,
            'recommendations': recommendations,
            'model_version': 'advanced_v1.0'
        }
        
    except Exception as e:
        return {'error': f'Prediction failed: {str(e)}'}

def generate_recommendations(risk_level, symptoms, vital_signs, health_status):
    """Generate health recommendations based on prediction"""
    recommendations = []
    
    # Risk level based recommendations
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
    else:  # Low
        recommendations.append("✅ Continue regular health monitoring")
        recommendations.append("Maintain vaccination schedule")
        recommendations.append("Ensure proper nutrition and housing")
    
    # Symptom-specific recommendations
    critical_symptoms = {'sudden_death', 'paralysis', 'convulsions', 'difficulty_breathing'}
    if any(s in symptoms for s in critical_symptoms):
        recommendations.append("⚠️ Critical symptoms detected - seek immediate help")
    
    if 'fever' in symptoms or 'high_fever' in symptoms:
        recommendations.append("Monitor temperature regularly (every 4 hours)")
        recommendations.append("Ensure adequate hydration")
    
    if 'diarrhea' in symptoms or 'vomiting' in symptoms:
        recommendations.append("Provide electrolyte solution to prevent dehydration")
        recommendations.append("Adjust diet to easily digestible foods")
    
    if 'loss_of_appetite' in symptoms:
        recommendations.append("Offer palatable and nutritious feed")
        recommendations.append("Monitor feed and water intake")
    
    # Vital signs recommendations
    temp = vital_signs.get('temperature', 0)
    if temp > 39.5:  # High temperature for livestock
        recommendations.append("Elevated temperature detected - monitor for infection")
    
    return recommendations

def main():
    if len(sys.argv) != 2:
        print(json.dumps({'error': 'Usage: python ml_predict_advanced.py <data_file>'}))
        sys.exit(1)
    
    data_file = sys.argv[1]
    
    try:
        # Load animal data
        with open(data_file, 'r') as f:
            animal_data = json.load(f)
        
        # Load ML models
        model, label_encoder, scaler, metadata = load_models()
        
        if model is None:
            print(json.dumps({
                'error': 'Models not found. Please train the model first.',
                'instructions': 'Run: python train_ml_model_advanced.py'
            }))
            sys.exit(1)
        
        # Make prediction
        result = predict_health_risk(animal_data, model, label_encoder, scaler)
        
        # Add metadata if available
        if metadata:
            result['model_info'] = {
                'name': metadata.get('model_name'),
                'trained_at': metadata.get('trained_at')
            }
        
        # Output result as JSON
        print(json.dumps(result, indent=2))
        
    except FileNotFoundError:
        print(json.dumps({'error': f'Data file not found: {data_file}'}))
        sys.exit(1)
    except json.JSONDecodeError:
        print(json.dumps({'error': f'Invalid JSON in data file: {data_file}'}))
        sys.exit(1)
    except Exception as e:
        print(json.dumps({'error': f'Script failed: {str(e)}'}))
        sys.exit(1)

if __name__ == '__main__':
    main()

