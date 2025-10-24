<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access - Please login as admin first");
}

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Update Database for ML - Bago City Veterinary Office</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .btn { padding: 10px 20px; margin: 5px; background: #007bff; color: white; text-decoration: none; border-radius: 3px; }
    </style>
</head>
<body>";

echo "<h1>Update Database for ML Anomaly Detection</h1>";

try {
    if (isset($_POST['update'])) {
        echo "<div class='section'>";
        echo "<h3>Database Update Process Started</h3>";
        
        $results = [];
        
        // 1. Remove populated pharmaceuticals (keep only original ones)
        echo "<h4>Cleaning Pharmaceuticals...</h4>";
        $removed_count = cleanPharmaceuticals($conn);
        $results['pharmaceuticals_removed'] = $removed_count;
        echo "<p class='success'>✓ Removed $removed_count populated pharmaceutical records</p>";
        
        // 2. Add more clients based on existing patterns
        echo "<h4>Adding More Clients...</h4>";
        $client_count = addMoreClients($conn);
        $results['clients_added'] = $client_count;
        echo "<p class='success'>✓ Added $client_count client records</p>";
        
        // 3. Add pharmaceutical requests based on existing patterns
        echo "<h4>Adding Pharmaceutical Requests for ML...</h4>";
        $request_count = addPharmaceuticalRequestsForML($conn);
        $results['requests_added'] = $request_count;
        echo "<p class='success'>✓ Added $request_count pharmaceutical request records</p>";
        
        // 4. Add health risk assessments for anomaly detection
        echo "<h4>Adding Health Risk Assessments...</h4>";
        $assessment_count = addHealthRiskAssessmentsForML($conn);
        $results['assessments_added'] = $assessment_count;
        echo "<p class='success'>✓ Added $assessment_count health risk assessment records</p>";
        
        echo "<h3 class='success'>Database Update Completed!</h3>";
        echo "<p><a href='admin_dashboard.php' class='btn'>Return to Dashboard</a></p>";
        echo "<p><a href='admin_ml_insights.php' class='btn'>View ML Insights</a></p>";
        echo "</div>";
    } else {
        // Show current status and update form
        echo "<div class='section'>";
        echo "<h3>Current Database Status</h3>";
        
        $tables = ['pharmaceuticals', 'clients', 'pharmaceutical_requests', 'health_risk_assessments'];
        foreach ($tables as $table) {
            $result = $conn->query("SELECT COUNT(*) as count FROM $table");
            if ($result) {
                $count = $result->fetch_assoc()['count'];
                echo "<p><strong>$table:</strong> $count records</p>";
            }
        }
        echo "</div>";
        
        echo "<div class='section'>";
        echo "<h3>ML Database Update Options</h3>";
        echo "<p>This tool will:</p>";
        echo "<ul>";
        echo "<li>Remove populated pharmaceuticals (keep only original ones)</li>";
        echo "<li>Add more clients based on existing patterns</li>";
        echo "<li>Add pharmaceutical requests with realistic symptoms and patterns for anomaly detection</li>";
        echo "<li>Add health risk assessments with varied risk levels for ML training</li>";
        echo "</ul>";
        
        echo "<form method='post'>";
        echo "<input type='submit' name='update' value='Update Database for ML' class='btn' style='background: #28a745;'>";
        echo "</form>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='section'>";
    echo "<h3 class='error'>Error</h3>";
    echo "<p class='error'>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</body></html>";

// Helper Functions

function cleanPharmaceuticals($conn) {
    // Keep only pharmaceuticals that were likely original (not generated)
    // Remove pharmaceuticals with generic names or those that look auto-generated
    $generic_names = [
        'Ivermectin', 'Amoxicillin', 'Penicillin G', 'Tetracycline', 'Vitamin B Complex',
        'Rabies Vaccine', 'Hog Cholera Vaccine', 'Newcastle Disease Vaccine', 'Felbendazole',
        'Oxytetracycline', 'Diclofenac', 'Meloxicam', 'Multivitamin', 'Probiotics', 'Iron Dextran'
    ];
    
    $removed_count = 0;
    foreach ($generic_names as $name) {
        $stmt = $conn->prepare("DELETE FROM pharmaceuticals WHERE name = ?");
        if ($stmt) {
            $stmt->bind_param("s", $name);
            if ($stmt->execute()) {
                $removed_count += $stmt->affected_rows;
            }
            $stmt->close();
        }
    }
    
    return $removed_count;
}

function addMoreClients($conn) {
    // Get existing barangays from current clients
    $barangays = [];
    $result = $conn->query("SELECT DISTINCT barangay FROM clients WHERE barangay IS NOT NULL");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $barangays[] = $row['barangay'];
        }
    }
    
    // If no existing clients, use default barangays
    if (empty($barangays)) {
        $barangays = ['Abuanan', 'Alianza', 'Atipuluan', 'Bacong-Montilla', 'Bagroy', 'Balingasag', 'Binubuhan', 'Busay', 'Calumangan', 'Caridad'];
    }
    
    // Generate Filipino names for clients
    $first_names = [
        'Juan', 'Maria', 'Pedro', 'Ana', 'Carlos', 'Elena', 'Miguel', 'Rosa', 'Antonio', 'Carmen',
        'Jose', 'Isabel', 'Francisco', 'Teresa', 'Manuel', 'Guadalupe', 'Ricardo', 'Dolores', 'Ramon', 'Esperanza',
        'Luis', 'Consuelo', 'Alberto', 'Beatriz', 'Fernando', 'Concepcion', 'Roberto', 'Mercedes', 'Sergio', 'Catalina',
        'Alfredo', 'Amparo', 'Eduardo', 'Gloria', 'Victor', 'Luz', 'Raul', 'Pilar', 'Oscar', 'Rosario'
    ];
    
    $last_names = [
        'Santos', 'Garcia', 'Rodriguez', 'Lopez', 'Martinez', 'Cruz', 'Torres', 'Flores', 'Reyes', 'Vargas',
        'Ramos', 'Mendoza', 'Gonzalez', 'Morales', 'Castillo', 'Jimenez', 'Herrera', 'Ruiz', 'Diaz', 'Aguilar',
        'Romero', 'Moreno', 'Alvarez', 'Gutierrez', 'Mendez', 'Hernandez', 'Vega', 'Castro', 'Ortiz', 'Delgado'
    ];
    
    $count = 0;
    for ($i = 0; $i < 25; $i++) {
        $first_name = $first_names[array_rand($first_names)];
        $last_name = $last_names[array_rand($last_names)];
        $full_name = $first_name . ' ' . $last_name;
        
        $contact_number = '09' . rand(100000000, 999999999);
        $barangay = $barangays[array_rand($barangays)];
        $purok = 'Purok ' . rand(1, 10);
        $username = strtolower(str_replace(' ', '', $full_name)) . rand(1, 99);
        $password = password_hash('password123', PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO clients (full_name, contact_number, barangay, purok, username, password) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssssss", $full_name, $contact_number, $barangay, $purok, $username, $password);
            if ($stmt->execute()) {
                $count++;
            }
            $stmt->close();
        }
    }
    
    return $count;
}

function addPharmaceuticalRequestsForML($conn) {
    // Get existing patterns from database
    $existing_symptoms = [];
    $existing_species = [];
    $existing_pharma_ids = [];
    
    // Get existing symptoms
    $result = $conn->query("SELECT DISTINCT symptoms FROM pharmaceutical_requests WHERE symptoms IS NOT NULL AND symptoms != ''");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if (!empty(trim($row['symptoms']))) {
                $existing_symptoms[] = trim($row['symptoms']);
            }
        }
    }
    
    // Get existing species
    $result = $conn->query("SELECT DISTINCT species FROM pharmaceutical_requests WHERE species IS NOT NULL AND species != ''");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if (!empty(trim($row['species']))) {
                $existing_species[] = trim($row['species']);
            }
        }
    }
    
    // Get pharmaceutical IDs
    $result = $conn->query("SELECT pharma_id FROM pharmaceuticals");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $existing_pharma_ids[] = $row['pharma_id'];
        }
    }
    
    // Get client IDs
    $client_ids = [];
    $result = $conn->query("SELECT client_id FROM clients");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $client_ids[] = $row['client_id'];
        }
    }
    
    // If no existing data, use defaults
    if (empty($existing_symptoms)) {
        $existing_symptoms = [
            'Fever', 'Loss of appetite', 'Diarrhea', 'Coughing', 'Lameness', 'Respiratory distress',
            'Skin lesions', 'Nasal discharge', 'Weight loss', 'Lethargy', 'Vomiting', 'Difficulty breathing',
            'Swollen joints', 'Eye discharge', 'Mouth sores', 'Hair loss', 'Dehydration', 'Pale gums',
            'Increased heart rate', 'Abnormal behavior', 'Bloating', 'Constipation', 'Excessive salivation'
        ];
    }
    
    if (empty($existing_species)) {
        $existing_species = [
            'Cattle', 'Swine', 'Goat', 'Sheep', 'Horse', 'Water Buffalo', 'Donkey',
            'Chicken', 'Duck', 'Goose', 'Turkey', 'Quail', 'Guinea Fowl'
        ];
    }
    
    if (empty($existing_pharma_ids)) {
        return 0; // Can't create requests without pharmaceuticals
    }
    
    $types = ['Livestock', 'Poultry'];
    $statuses = ['Pending', 'Approved', 'Issued'];
    
    $count = 0;
    for ($i = 0; $i < 75; $i++) { // Generate 75 requests for good ML data
        $client_id = $client_ids[array_rand($client_ids)];
        $pharma_id = $existing_pharma_ids[array_rand($existing_pharma_ids)];
        $type = $types[array_rand($types)];
        $species = $existing_species[array_rand($existing_species)];
        $symptoms = $existing_symptoms[array_rand($existing_symptoms)];
        
        // Add multiple symptoms for some requests (more realistic)
        if (rand(1, 3) == 1) {
            $additional_symptoms = array_rand(array_flip($existing_symptoms), rand(1, 2));
            if (is_array($additional_symptoms)) {
                $symptoms .= ', ' . implode(', ', $additional_symptoms);
            } else {
                $symptoms .= ', ' . $additional_symptoms;
            }
        }
        
        $poultry_quantity = ($type === 'Poultry') ? rand(1, 100) : rand(1, 20);
        $weight = ($type === 'Livestock') ? rand(50, 800) : rand(1, 10);
        $status = $statuses[array_rand($statuses)];
        
        $stmt = $conn->prepare("INSERT INTO pharmaceutical_requests (client_id, type, species, symptoms, pharma_id, poultry_quantity, weight, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("isssidis", $client_id, $type, $species, $symptoms, $pharma_id, $poultry_quantity, $weight, $status);
            if ($stmt->execute()) {
                $count++;
            }
            $stmt->close();
        }
    }
    
    return $count;
}

function addHealthRiskAssessmentsForML($conn) {
    // Get animal and client IDs
    $animal_ids = [];
    $client_ids = [];
    
    $result = $conn->query("SELECT animal_id, client_id FROM livestock_poultry");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $animal_ids[] = $row['animal_id'];
            $client_ids[] = $row['client_id'];
        }
    }
    
    if (empty($animal_ids)) {
        return 0; // Can't create assessments without animals
    }
    
    $risk_levels = ['Low', 'Medium', 'High', 'Critical'];
    $statuses = ['Active', 'Resolved', 'Monitoring'];
    
    // Define risk factors for different scenarios
    $risk_factors_options = [
        ['Age', 'Weight', 'Environment'],
        ['Vaccination_Status', 'Diet', 'Housing'],
        ['Temperature', 'Humidity', 'Stress'],
        ['Previous_Diseases', 'Genetic_Factors', 'Management'],
        ['Seasonal_Factors', 'Contact_with_Others', 'Sanitation'],
        ['Nutritional_Deficiency', 'Parasite_Load', 'Immune_Status']
    ];
    
    $recommendations_options = [
        ['Monitor closely', 'Improve nutrition', 'Isolate if needed'],
        ['Administer medication', 'Quarantine', 'Contact veterinarian'],
        ['Increase monitoring frequency', 'Adjust environment', 'Update vaccination'],
        ['Immediate intervention required', 'Emergency treatment', 'Notify authorities'],
        ['Regular health checks', 'Maintain current protocol', 'Document changes'],
        ['Preventive measures', 'Biosecurity protocols', 'Regular testing']
    ];
    
    $count = 0;
    for ($i = 0; $i < 50; $i++) { // Generate 50 assessments for ML
        $animal_id = $animal_ids[array_rand($animal_ids)];
        $client_id = $client_ids[array_rand($client_ids)];
        
        // Generate realistic risk scores based on normal distribution
        $base_score = rand(20, 80);
        $risk_score = max(0, min(100, $base_score + rand(-15, 15)));
        
        // Determine risk level based on score
        if ($risk_score >= 80) {
            $risk_level = 'Critical';
        } elseif ($risk_score >= 60) {
            $risk_level = 'High';
        } elseif ($risk_score >= 40) {
            $risk_level = 'Medium';
        } else {
            $risk_level = 'Low';
        }
        
        $risk_factors = json_encode($risk_factors_options[array_rand($risk_factors_options)]);
        $recommendations = json_encode($recommendations_options[array_rand($recommendations_options)]);
        $status = $statuses[array_rand($statuses)];
        
        $stmt = $conn->prepare("INSERT INTO health_risk_assessments (animal_id, client_id, risk_score, risk_level, risk_factors, recommendations, status, assessed_by) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        if ($stmt) {
            $stmt->bind_param("iidssss", $animal_id, $client_id, $risk_score, $risk_level, $risk_factors, $recommendations, $status);
            if ($stmt->execute()) {
                $count++;
            }
            $stmt->close();
        }
    }
    
    return $count;
}
?>
