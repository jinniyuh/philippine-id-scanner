-- Health Risk Assessment ML Feature Database Tables
-- This file creates the necessary tables for the Animal Health Risk Assessment feature

-- Table for storing health risk assessment results
CREATE TABLE IF NOT EXISTS `health_risk_assessments` (
  `assessment_id` int(11) NOT NULL AUTO_INCREMENT,
  `animal_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `risk_score` decimal(5,2) NOT NULL COMMENT 'Risk score from 0-100',
  `risk_level` enum('Low','Medium','High','Critical') NOT NULL,
  `risk_factors` text COMMENT 'JSON string of identified risk factors',
  `recommendations` text COMMENT 'JSON string of recommended actions',
  `assessment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `assessed_by` int(11) DEFAULT NULL COMMENT 'User ID who performed assessment',
  `status` enum('Active','Resolved','Monitoring') DEFAULT 'Active',
  PRIMARY KEY (`assessment_id`),
  KEY `animal_id` (`animal_id`),
  KEY `client_id` (`client_id`),
  KEY `assessment_date` (`assessment_date`),
  KEY `risk_level` (`risk_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table for storing health indicators and metrics
CREATE TABLE IF NOT EXISTS `health_indicators` (
  `indicator_id` int(11) NOT NULL AUTO_INCREMENT,
  `animal_id` int(11) NOT NULL,
  `indicator_type` enum('Weight','Temperature','Vaccination_Status','Medication_History','Environmental_Factor','Behavioral_Change') NOT NULL,
  `indicator_value` varchar(255) NOT NULL,
  `indicator_unit` varchar(50) DEFAULT NULL,
  `recorded_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `recorded_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`indicator_id`),
  KEY `animal_id` (`animal_id`),
  KEY `indicator_type` (`indicator_type`),
  KEY `recorded_date` (`recorded_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table for storing disease patterns and risk factors
CREATE TABLE IF NOT EXISTS `disease_patterns` (
  `pattern_id` int(11) NOT NULL AUTO_INCREMENT,
  `disease_name` varchar(100) NOT NULL,
  `animal_type` enum('Livestock','Poultry') NOT NULL,
  `symptoms` text NOT NULL COMMENT 'JSON array of symptoms',
  `risk_factors` text NOT NULL COMMENT 'JSON array of risk factors',
  `seasonal_pattern` text DEFAULT NULL COMMENT 'JSON object with seasonal data',
  `severity_score` int(11) DEFAULT 50 COMMENT 'Severity score 1-100',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`pattern_id`),
  KEY `animal_type` (`animal_type`),
  KEY `disease_name` (`disease_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table for storing ML model performance metrics
CREATE TABLE IF NOT EXISTS `ml_model_metrics` (
  `metric_id` int(11) NOT NULL AUTO_INCREMENT,
  `model_name` varchar(100) NOT NULL,
  `model_version` varchar(20) NOT NULL,
  `accuracy` decimal(5,2) DEFAULT NULL,
  `precision` decimal(5,2) DEFAULT NULL,
  `recall` decimal(5,2) DEFAULT NULL,
  `f1_score` decimal(5,2) DEFAULT NULL,
  `training_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `test_data_size` int(11) DEFAULT NULL,
  `training_data_size` int(11) DEFAULT NULL,
  `model_parameters` text DEFAULT NULL COMMENT 'JSON string of model parameters',
  PRIMARY KEY (`metric_id`),
  KEY `model_name` (`model_name`),
  KEY `training_date` (`training_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample disease patterns
INSERT INTO `disease_patterns` (`disease_name`, `animal_type`, `symptoms`, `risk_factors`, `seasonal_pattern`, `severity_score`) VALUES
('Avian Influenza', 'Poultry', '["Lethargy", "Loss of appetite", "Respiratory distress", "Drop in egg production"]', '["High density housing", "Poor ventilation", "Stress", "Contact with wild birds"]', '{"peak_months": [11, 12, 1, 2], "risk_multiplier": 1.5}', 85),
('Foot and Mouth Disease', 'Livestock', '["Fever", "Blisters on mouth and feet", "Lameness", "Loss of appetite"]', '["Contact with infected animals", "Poor biosecurity", "Stress", "Overcrowding"]', '{"peak_months": [3, 4, 5], "risk_multiplier": 1.3}', 90),
('Mastitis', 'Livestock', '["Swollen udder", "Abnormal milk", "Fever", "Loss of appetite"]', '["Poor hygiene", "Injury to udder", "Stress", "Poor nutrition"]', '{"peak_months": [6, 7, 8], "risk_multiplier": 1.2}', 60),
('Newcastle Disease', 'Poultry', '["Respiratory signs", "Nervous signs", "Drop in egg production", "High mortality"]', '["Poor vaccination", "Stress", "Poor nutrition", "Contact with wild birds"]', '{"peak_months": [10, 11, 12], "risk_multiplier": 1.4}', 80);

-- Insert sample health indicators for existing animals
INSERT INTO `health_indicators` (`animal_id`, `indicator_type`, `indicator_value`, `indicator_unit`, `recorded_by`, `notes`) 
SELECT 
    lp.animal_id,
    'Weight' as indicator_type,
    CASE 
        WHEN lp.animal_type = 'Livestock' THEN FLOOR(200 + RAND() * 300)
        ELSE FLOOR(1 + RAND() * 5)
    END as indicator_value,
    CASE 
        WHEN lp.animal_type = 'Livestock' THEN 'kg'
        ELSE 'kg'
    END as indicator_unit,
    1 as recorded_by,
    'Initial weight measurement' as notes
FROM livestock_poultry lp
LIMIT 10;

-- Insert sample health indicators for vaccination status
INSERT INTO `health_indicators` (`animal_id`, `indicator_type`, `indicator_value`, `indicator_unit`, `recorded_by`, `notes`) 
SELECT 
    lp.animal_id,
    'Vaccination_Status' as indicator_type,
    CASE 
        WHEN RAND() > 0.3 THEN 'Up to date'
        ELSE 'Overdue'
    END as indicator_value,
    NULL as indicator_unit,
    1 as recorded_by,
    'Vaccination status check' as notes
FROM livestock_poultry lp
LIMIT 10;

-- Insert sample health indicators for environmental factors
INSERT INTO `health_indicators` (`animal_id`, `indicator_type`, `indicator_value`, `indicator_unit`, `recorded_by`, `notes`) 
SELECT 
    lp.animal_id,
    'Environmental_Factor' as indicator_type,
    CASE 
        WHEN RAND() > 0.5 THEN 'Good'
        WHEN RAND() > 0.3 THEN 'Fair'
        ELSE 'Poor'
    END as indicator_value,
    NULL as indicator_unit,
    1 as recorded_by,
    'Environmental condition assessment' as notes
FROM livestock_poultry lp
LIMIT 10;
