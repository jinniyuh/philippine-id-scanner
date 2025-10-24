#!/usr/bin/env python3
"""
Training Data Collection Script
Collects historical health data from the database for ML model training
"""

import json
import os
import sys
from datetime import datetime
import mysql.connector
from mysql.connector import Error

class TrainingDataCollector:
    def __init__(self):
        self.db_config = {
            'host': 'localhost',
            'database': 'bago_city_vet',
            'user': 'root',
            'password': ''  # Update with your MySQL password if needed
        }
        self.connection = None
        
    def connect_database(self):
        """Connect to MySQL database"""
        try:
            self.connection = mysql.connector.connect(**self.db_config)
            if self.connection.is_connected():
                print(f"✅ Connected to MySQL database: {self.db_config['database']}")
                return True
        except Error as e:
            print(f"❌ Database connection failed: {e}")
            return False
    
    def collect_pharmaceutical_data(self):
        """Collect data from pharmaceutical requests with symptoms"""
        if not self.connection:
            return []
        
        try:
            cursor = self.connection.cursor(dictionary=True)
            
            # Query pharmaceutical requests with animal and client data
            query = """
            SELECT 
                pr.request_id,
                pr.client_id,
                pr.animal_id,
                pr.request_date,
                pr.symptoms,
                pr.medicine_type,
                pr.quantity,
                pr.status,
                c.full_name as owner_name,
                c.barangay,
                c.contact_number,
                CASE 
                    WHEN l.livestock_id IS NOT NULL THEN 'livestock'
                    WHEN p.poultry_id IS NOT NULL THEN 'poultry'
                    ELSE 'unknown'
                END as animal_type,
                COALESCE(l.species, p.species, 'unknown') as species,
                COALESCE(l.breed, p.breed, 'unknown') as breed,
                COALESCE(l.age, p.age, 0) as age,
                COALESCE(l.weight, p.weight, 0) as weight,
                COALESCE(l.health_status, p.health_status, 'unknown') as health_status
            FROM pharmaceutical_requests pr
            JOIN clients c ON pr.client_id = c.client_id
            LEFT JOIN livestock l ON pr.animal_id = l.livestock_id
            LEFT JOIN poultry p ON pr.animal_id = p.poultry_id
            WHERE pr.symptoms IS NOT NULL 
            AND pr.symptoms != ''
            ORDER BY pr.request_date DESC
            LIMIT 1000
            """
            
            cursor.execute(query)
            results = cursor.fetchall()
            
            print(f"📊 Collected {len(results)} pharmaceutical request records")
            cursor.close()
            return results
            
        except Error as e:
            print(f"❌ Error collecting pharmaceutical data: {e}")
            return []
    
    def collect_livestock_data(self):
        """Collect livestock data"""
        if not self.connection:
            return []
        
        try:
            cursor = self.connection.cursor(dictionary=True)
            
            query = """
            SELECT 
                l.livestock_id,
                l.client_id,
                l.species,
                l.breed,
                l.age,
                l.weight,
                l.health_status,
                l.vaccination_status,
                l.registration_date,
                c.barangay
            FROM livestock l
            JOIN clients c ON l.client_id = c.client_id
            ORDER BY l.registration_date DESC
            LIMIT 1000
            """
            
            cursor.execute(query)
            results = cursor.fetchall()
            
            print(f"🐄 Collected {len(results)} livestock records")
            cursor.close()
            return results
            
        except Error as e:
            print(f"❌ Error collecting livestock data: {e}")
            return []
    
    def collect_poultry_data(self):
        """Collect poultry data"""
        if not self.connection:
            return []
        
        try:
            cursor = self.connection.cursor(dictionary=True)
            
            query = """
            SELECT 
                p.poultry_id,
                p.client_id,
                p.species,
                p.breed,
                p.age,
                p.weight,
                p.health_status,
                p.vaccination_status,
                p.registration_date,
                c.barangay
            FROM poultry p
            JOIN clients c ON p.client_id = c.client_id
            ORDER BY p.registration_date DESC
            LIMIT 1000
            """
            
            cursor.execute(query)
            results = cursor.fetchall()
            
            print(f"🐔 Collected {len(results)} poultry records")
            cursor.close()
            return results
            
        except Error as e:
            print(f"❌ Error collecting poultry data: {e}")
            return []
    
    def parse_symptoms(self, symptom_text):
        """Parse symptom text into list of symptoms"""
        if not symptom_text:
            return []
        
        # Common symptom keywords (English and Filipino)
        symptom_keywords = {
            'fever': ['fever', 'lagnat', 'high fever', 'mataas na lagnat'],
            'lethargy': ['lethargy', 'weak', 'mahina', 'walang lakas'],
            'loss_of_appetite': ['loss of appetite', 'hindi kumakain', 'walang gana'],
            'diarrhea': ['diarrhea', 'pagtatae', 'loose stool'],
            'vomiting': ['vomiting', 'pagsusuka', 'nagsusuka'],
            'difficulty_breathing': ['difficulty breathing', 'hirap huminga', 'shortness of breath'],
            'swollen': ['swollen', 'namamaga', 'swelling', 'pamamaga'],
            'weakness': ['weakness', 'panghihina', 'weak'],
            'convulsions': ['convulsions', 'kombulsyon', 'seizure'],
            'paralysis': ['paralysis', 'pagkaparalisa', 'paralyzed'],
            'sudden_death': ['sudden death', 'biglaang pagkamatay'],
            'lameness': ['lameness', 'hirap tumayo', 'limping'],
            'behavior_change': ['behavior change', 'abnormal behavior', 'agitated']
        }
        
        symptom_text_lower = symptom_text.lower()
        detected_symptoms = []
        
        for symptom, keywords in symptom_keywords.items():
            for keyword in keywords:
                if keyword in symptom_text_lower:
                    detected_symptoms.append(symptom)
                    break
        
        return detected_symptoms
    
    def determine_risk_level(self, health_status, symptoms):
        """Determine risk level based on health status and symptoms"""
        if not health_status:
            health_status = 'unknown'
        
        health_status_lower = health_status.lower()
        num_symptoms = len(symptoms)
        
        # Critical conditions
        critical_symptoms = {'sudden_death', 'paralysis', 'convulsions', 'difficulty_breathing'}
        if any(s in symptoms for s in critical_symptoms):
            return 'Critical'
        
        # High risk conditions
        if health_status_lower in ['sick', 'critical', 'quarantine']:
            return 'High'
        
        if num_symptoms >= 4:
            return 'High'
        
        # Medium risk
        if health_status_lower in ['under observation', 'recovering']:
            return 'Medium'
        
        if num_symptoms >= 2:
            return 'Medium'
        
        # Low risk
        if health_status_lower in ['healthy', 'good']:
            return 'Low'
        
        if num_symptoms == 1:
            return 'Low'
        
        return 'Low'
    
    def get_season(self, date):
        """Determine season from date (Philippines climate)"""
        if not date:
            return 'summer'
        
        month = date.month
        
        # Dry season (hot): March to May
        if month in [3, 4, 5]:
            return 'summer'
        # Wet season (rainy): June to November
        elif month in [6, 7, 8, 9, 10, 11]:
            return 'autumn'  # Using 'autumn' to represent rainy season
        # Cool dry season: December to February
        else:
            return 'winter'  # Using 'winter' to represent cool season
    
    def convert_to_training_format(self, pharma_data, livestock_data, poultry_data):
        """Convert database records to ML training format"""
        training_records = []
        
        # Process pharmaceutical requests (primary source)
        for record in pharma_data:
            symptoms = self.parse_symptoms(record.get('symptoms', ''))
            risk_level = self.determine_risk_level(record.get('health_status'), symptoms)
            
            training_record = {
                'record_id': f"pharma_{record['request_id']}",
                'animal_id': record.get('animal_id', 0),
                'timestamp': record.get('request_date').isoformat() if record.get('request_date') else '',
                'symptoms': symptoms,
                'vital_signs': {
                    'temperature': 38.5,  # Default (can be enhanced with actual data)
                    'weight': float(record.get('weight', 0)) if record.get('weight') else 0,
                    'heart_rate': 0  # Not available in current schema
                },
                'environment': {
                    'temperature': 28.0,  # Philippines average
                    'humidity': 65.0,
                    'season': self.get_season(record.get('request_date'))
                },
                'animal_characteristics': {
                    'species': record.get('species', 'unknown'),
                    'breed': record.get('breed', 'unknown'),
                    'age': int(record.get('age', 0)) if record.get('age') else 0,
                    'type': record.get('animal_type', 'unknown')
                },
                'health_status': record.get('health_status', 'unknown'),
                'outcome': risk_level,  # This is our target variable
                'data_source': 'pharmaceutical_request'
            }
            training_records.append(training_record)
        
        # Process livestock data (secondary source - less detailed)
        for record in livestock_data:
            health_status = record.get('health_status', 'unknown')
            
            # Only include if health status indicates some issue
            if health_status.lower() not in ['healthy', 'good']:
                training_record = {
                    'record_id': f"livestock_{record['livestock_id']}",
                    'animal_id': record['livestock_id'],
                    'timestamp': record.get('registration_date').isoformat() if record.get('registration_date') else '',
                    'symptoms': [],  # No symptom data available
                    'vital_signs': {
                        'temperature': 38.5,
                        'weight': float(record.get('weight', 0)) if record.get('weight') else 0,
                        'heart_rate': 0
                    },
                    'environment': {
                        'temperature': 28.0,
                        'humidity': 65.0,
                        'season': self.get_season(record.get('registration_date'))
                    },
                    'animal_characteristics': {
                        'species': record.get('species', 'unknown'),
                        'breed': record.get('breed', 'unknown'),
                        'age': int(record.get('age', 0)) if record.get('age') else 0,
                        'type': 'livestock'
                    },
                    'health_status': health_status,
                    'outcome': 'Medium' if health_status.lower() in ['under observation'] else 'Low',
                    'data_source': 'livestock'
                }
                training_records.append(training_record)
        
        # Process poultry data (secondary source - less detailed)
        for record in poultry_data:
            health_status = record.get('health_status', 'unknown')
            
            # Only include if health status indicates some issue
            if health_status.lower() not in ['healthy', 'good']:
                training_record = {
                    'record_id': f"poultry_{record['poultry_id']}",
                    'animal_id': record['poultry_id'],
                    'timestamp': record.get('registration_date').isoformat() if record.get('registration_date') else '',
                    'symptoms': [],  # No symptom data available
                    'vital_signs': {
                        'temperature': 41.0,  # Poultry normal temp is higher
                        'weight': float(record.get('weight', 0)) if record.get('weight') else 0,
                        'heart_rate': 0
                    },
                    'environment': {
                        'temperature': 28.0,
                        'humidity': 65.0,
                        'season': self.get_season(record.get('registration_date'))
                    },
                    'animal_characteristics': {
                        'species': record.get('species', 'unknown'),
                        'breed': record.get('breed', 'unknown'),
                        'age': int(record.get('age', 0)) if record.get('age') else 0,
                        'type': 'poultry'
                    },
                    'health_status': health_status,
                    'outcome': 'Medium' if health_status.lower() in ['under observation'] else 'Low',
                    'data_source': 'poultry'
                }
                training_records.append(training_record)
        
        return training_records
    
    def save_training_data(self, data, filename='training_data.json'):
        """Save training data to JSON file"""
        try:
            with open(filename, 'w') as f:
                json.dump(data, f, indent=2)
            
            print(f"\n✅ Training data saved to: {filename}")
            print(f"   Total records: {len(data)}")
            
            # Print statistics
            risk_levels = {}
            data_sources = {}
            
            for record in data:
                outcome = record.get('outcome', 'Unknown')
                source = record.get('data_source', 'Unknown')
                
                risk_levels[outcome] = risk_levels.get(outcome, 0) + 1
                data_sources[source] = data_sources.get(source, 0) + 1
            
            print(f"\n📊 Risk Level Distribution:")
            for level, count in sorted(risk_levels.items()):
                print(f"   {level}: {count} records ({count/len(data)*100:.1f}%)")
            
            print(f"\n📁 Data Source Distribution:")
            for source, count in sorted(data_sources.items()):
                print(f"   {source}: {count} records ({count/len(data)*100:.1f}%)")
            
            return True
            
        except Exception as e:
            print(f"❌ Error saving training data: {e}")
            return False
    
    def close_connection(self):
        """Close database connection"""
        if self.connection and self.connection.is_connected():
            self.connection.close()
            print("\n🔒 Database connection closed")

def main():
    print("=" * 60)
    print("🤖 ML Training Data Collection Script")
    print("   Bago City Veterinary Office Inventory Management System")
    print("=" * 60)
    print()
    
    collector = TrainingDataCollector()
    
    # Connect to database
    if not collector.connect_database():
        print("❌ Failed to connect to database. Please check your settings.")
        sys.exit(1)
    
    # Collect data from different sources
    print("\n📥 Collecting data from database...")
    pharma_data = collector.collect_pharmaceutical_data()
    livestock_data = collector.collect_livestock_data()
    poultry_data = collector.collect_poultry_data()
    
    # Convert to training format
    print("\n🔄 Converting data to ML training format...")
    training_data = collector.convert_to_training_format(
        pharma_data, livestock_data, poultry_data
    )
    
    # Save training data
    if training_data:
        collector.save_training_data(training_data)
        
        print("\n" + "=" * 60)
        print("✅ Data collection completed successfully!")
        print("=" * 60)
        print("\nNext steps:")
        print("   1. Review training_data.json")
        print("   2. Run: python train_ml_model.py")
        print("   3. Test predictions with: python ml_predict.py")
    else:
        print("\n❌ No training data collected!")
        print("   Please ensure your database has pharmaceutical request records.")
    
    # Close database connection
    collector.close_connection()

if __name__ == '__main__':
    main()

