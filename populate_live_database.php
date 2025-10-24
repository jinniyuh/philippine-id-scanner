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
    <title>Database Population - Bago City Veterinary Office</title>
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

echo "<h1>Database Population Tool</h1>";

try {
    // Check current database status
    echo "<div class='section'>";
    echo "<h3>Current Database Status</h3>";
    
    $tables = ['pharmaceuticals', 'clients', 'transactions', 'livestock_poultry', 'users'];
    $current_counts = [];
    
    foreach ($tables as $table) {
        $result = $conn->query("SELECT COUNT(*) as count FROM $table");
        if ($result) {
            $count = $result->fetch_assoc()['count'];
            $current_counts[$table] = $count;
            echo "<p><strong>$table:</strong> $count records</p>";
        }
    }
    echo "</div>";

    // Start population process
    if (isset($_POST['populate'])) {
        echo "<div class='section'>";
        echo "<h3>Population Process Started</h3>";
        
        $population_results = [];
        
        // 1. Populate Pharmaceuticals (if empty or minimal)
        if ($current_counts['pharmaceuticals'] < 10) {
            echo "<h4>Populating Pharmaceuticals...</h4>";
            $pharma_count = populatePharmaceuticals($conn);
            $population_results['pharmaceuticals'] = $pharma_count;
            echo "<p class='success'>✓ Added $pharma_count pharmaceutical records</p>";
        } else {
            echo "<p class='info'>Pharmaceuticals already have sufficient data</p>";
        }
        
        // 2. Populate Clients (if empty or minimal)
        if ($current_counts['clients'] < 5) {
            echo "<h4>Populating Clients...</h4>";
            $client_count = populateClients($conn);
            $population_results['clients'] = $client_count;
            echo "<p class='success'>✓ Added $client_count client records</p>";
        } else {
            echo "<p class='info'>Clients already have sufficient data</p>";
        }
        
        // 3. Populate Livestock/Poultry (if empty or minimal)
        if ($current_counts['livestock_poultry'] < 20) {
            echo "<h4>Populating Livestock/Poultry...</h4>";
            $livestock_count = populateLivestockPoultry($conn);
            $population_results['livestock_poultry'] = $livestock_count;
            echo "<p class='success'>✓ Added $livestock_count livestock/poultry records</p>";
        } else {
            echo "<p class='info'>Livestock/Poultry already have sufficient data</p>";
        }
        
        // 4. Populate Transactions (if insufficient for ML)
        if ($current_counts['transactions'] < 50) {
            echo "<h4>Populating Transactions...</h4>";
            $transaction_count = populateTransactions($conn);
            $population_results['transactions'] = $transaction_count;
            echo "<p class='success'>✓ Added $transaction_count transaction records</p>";
        } else {
            echo "<p class='info'>Transactions already have sufficient data</p>";
        }
        
        // 5. Populate Pharmaceutical Requests
        echo "<h4>Populating Pharmaceutical Requests...</h4>";
        $request_count = populatePharmaceuticalRequests($conn);
        $population_results['pharmaceutical_requests'] = $request_count;
        echo "<p class='success'>✓ Added $request_count pharmaceutical request records</p>";
        
        // 6. Populate Health Risk Assessments
        echo "<h4>Populating Health Risk Assessments...</h4>";
        $assessment_count = populateHealthRiskAssessments($conn);
        $population_results['health_risk_assessments'] = $assessment_count;
        echo "<p class='success'>✓ Added $assessment_count health risk assessment records</p>";
        
        echo "<h3 class='success'>Population Process Completed!</h3>";
        echo "<p><a href='admin_dashboard.php' class='btn'>Return to Dashboard</a></p>";
        echo "<p><a href='admin_ml_insights.php' class='btn'>View ML Insights</a></p>";
        echo "</div>";
    } else {
        // Show population form
        echo "<div class='section'>";
        echo "<h3>Database Population Options</h3>";
        echo "<p>This tool will populate your database with realistic sample data for testing ML insights and forecasting.</p>";
        echo "<p><strong>Note:</strong> Only tables with insufficient data will be populated.</p>";
        
        echo "<form method='post'>";
        echo "<input type='submit' name='populate' value='Start Population Process' class='btn' style='background: #28a745;'>";
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

// Population Functions

function populatePharmaceuticals($conn) {
    $pharmaceuticals = [
        ['Ivermectin', 'Antiparasitic medication for livestock', 'Antiparasitics', 'Milliliters (ml)', 500, '2025-12-31'],
        ['Amoxicillin', 'Broad-spectrum antibiotic', 'Antibiotics', 'Tablets', 1000, '2025-11-30'],
        ['Penicillin G', 'Antibiotic for bacterial infections', 'Antibiotics', 'Vials', 200, '2025-10-15'],
        ['Tetracycline', 'Antibiotic for respiratory infections', 'Antibiotics', 'Capsules', 800, '2025-09-20'],
        ['Vitamin B Complex', 'Nutritional supplement', 'Vitamins', 'Milliliters (ml)', 300, '2026-01-15'],
        ['Rabies Vaccine', 'Vaccine for rabies prevention', 'Vaccines', 'Vials', 150, '2025-08-30'],
        ['Hog Cholera Vaccine', 'Vaccine for swine', 'Vaccines', 'Milliliters (ml)', 100, '2025-07-25'],
        ['Newcastle Disease Vaccine', 'Poultry vaccine', 'Vaccines', 'Vials', 250, '2025-12-10'],
        ['Felbendazole', 'Deworming medication', 'Dewormer', 'Tablets', 600, '2025-11-05'],
        ['Oxytetracycline', 'Antibiotic injection', 'Antibiotics', 'Milliliters (ml)', 400, '2025-09-12'],
        ['Diclofenac', 'Anti-inflammatory pain relief', 'Anti_inflammatory', 'Milliliters (ml)', 200, '2025-10-08'],
        ['Meloxicam', 'Anti-inflammatory for livestock', 'Anti_inflammatory', 'Milliliters (ml)', 150, '2025-12-20'],
        ['Multivitamin', 'General vitamin supplement', 'Vitamins', 'Tablets', 1000, '2026-02-28'],
        ['Probiotics', 'Digestive health supplement', 'Supplements', 'Grams', 500, '2025-11-18'],
        ['Iron Dextran', 'Iron supplement for anemia', 'Supplements', 'Milliliters (ml)', 100, '2025-08-15']
    ];
    
    $count = 0;
    foreach ($pharmaceuticals as $pharma) {
        $stmt = $conn->prepare("INSERT INTO pharmaceuticals (name, description, category, unit, stock, expiry_date) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssssis", $pharma[0], $pharma[1], $pharma[2], $pharma[3], $pharma[4], $pharma[5]);
            if ($stmt->execute()) {
                $count++;
            }
            $stmt->close();
        }
    }
    return $count;
}

function populateClients($conn) {
    $barangays = ['Abuanan', 'Alianza', 'Atipuluan', 'Bacong-Montilla', 'Bagroy', 'Balingasag', 'Binubuhan', 'Busay', 'Calumangan', 'Caridad', 'Don Jorge L. Araneta', 'Dulao', 'Ilijan', 'Lag-Asan', 'Ma-ao', 'Mailum', 'Malingin', 'Napoles', 'Pacol', 'Poblacion', 'Sagasa', 'Tabunan', 'Taloc', 'Sampinit'];
    
    $clients = [
        ['Juan Santos', '09123456789', 'Abuanan', 'Purok 1'],
        ['Maria Garcia', '09234567890', 'Alianza', 'Purok 2'],
        ['Pedro Rodriguez', '09345678901', 'Atipuluan', 'Purok 3'],
        ['Ana Lopez', '09456789012', 'Bacong-Montilla', 'Purok 4'],
        ['Carlos Martinez', '09567890123', 'Bagroy', 'Purok 5'],
        ['Elena Cruz', '09678901234', 'Balingasag', 'Purok 6'],
        ['Miguel Torres', '09789012345', 'Binubuhan', 'Purok 7'],
        ['Rosa Flores', '09890123456', 'Busay', 'Purok 8'],
        ['Antonio Reyes', '09901234567', 'Calumangan', 'Purok 9'],
        ['Carmen Vargas', '09012345678', 'Caridad', 'Purok 10']
    ];
    
    $count = 0;
    foreach ($clients as $client) {
        $username = strtolower(str_replace(' ', '', $client[0]));
        $password = password_hash('password123', PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO clients (full_name, contact_number, barangay, purok, username, password) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssssss", $client[0], $client[1], $client[2], $client[3], $username, $password);
            if ($stmt->execute()) {
                $count++;
            }
            $stmt->close();
        }
    }
    return $count;
}

function populateLivestockPoultry($conn) {
    // Get client IDs
    $client_ids = [];
    $result = $conn->query("SELECT client_id FROM clients LIMIT 10");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $client_ids[] = $row['client_id'];
        }
    }
    
    $livestock_data = [
        ['Livestock', 'Cattle', 5, 'Male', 450.5, 'Purchased'],
        ['Livestock', 'Water Buffalo', 3, 'Female', 380.2, 'Purchased'],
        ['Livestock', 'Goat', 8, 'Male', 35.8, 'Bred'],
        ['Livestock', 'Sheep', 6, 'Female', 28.5, 'Purchased'],
        ['Livestock', 'Swine', 12, 'Male', 85.3, 'Purchased'],
        ['Poultry', 'Chicken', 25, 'Female', 2.1, 'Bred'],
        ['Poultry', 'Duck', 15, 'Male', 2.8, 'Purchased'],
        ['Poultry', 'Goose', 8, 'Female', 4.2, 'Bred'],
        ['Poultry', 'Turkey', 6, 'Male', 6.5, 'Purchased'],
        ['Livestock', 'Horse', 2, 'Male', 320.0, 'Purchased']
    ];
    
    $count = 0;
    foreach ($livestock_data as $data) {
        $client_id = $client_ids[array_rand($client_ids)];
        
        $stmt = $conn->prepare("INSERT INTO livestock_poultry (client_id, animal_type, species, quantity, sex, weight, source) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("issisds", $client_id, $data[0], $data[1], $data[2], $data[3], $data[4], $data[5]);
            if ($stmt->execute()) {
                $count++;
            }
            $stmt->close();
        }
    }
    return $count;
}

function populateTransactions($conn) {
    // Get client and pharmaceutical IDs
    $client_ids = [];
    $pharma_ids = [];
    
    $result = $conn->query("SELECT client_id FROM clients");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $client_ids[] = $row['client_id'];
        }
    }
    
    $result = $conn->query("SELECT pharma_id FROM pharmaceuticals");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $pharma_ids[] = $row['pharma_id'];
        }
    }
    
    if (empty($client_ids) || empty($pharma_ids)) {
        return 0;
    }
    
    $count = 0;
    // Generate transactions for the last 12 months
    for ($i = 0; $i < 100; $i++) {
        $client_id = $client_ids[array_rand($client_ids)];
        $pharma_id = $pharma_ids[array_rand($pharma_ids)];
        $quantity = rand(1, 20);
        $days_ago = rand(0, 365);
        $request_date = date('Y-m-d', strtotime("-$days_ago days"));
        $barangay = ['Abuanan', 'Alianza', 'Atipuluan', 'Bacong-Montilla', 'Bagroy'][array_rand(['Abuanan', 'Alianza', 'Atipuluan', 'Bacong-Montilla', 'Bagroy'])];
        $status = ['Approved', 'Pending'][array_rand(['Approved', 'Pending'])];
        $type = ['Livestock', 'Poultry', 'Pharmaceutical'][array_rand(['Livestock', 'Poultry', 'Pharmaceutical'])];
        
        $stmt = $conn->prepare("INSERT INTO transactions (client_id, user_id, pharma_id, quantity, barangay, status, request_date, type) VALUES (?, 1, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("iiissss", $client_id, $pharma_id, $quantity, $barangay, $status, $request_date, $type);
            if ($stmt->execute()) {
                $count++;
            }
            $stmt->close();
        }
    }
    return $count;
}

function populatePharmaceuticalRequests($conn) {
    // Get client, pharmaceutical, and livestock IDs
    $client_ids = [];
    $pharma_ids = [];
    $animal_ids = [];
    
    $result = $conn->query("SELECT client_id FROM clients LIMIT 5");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $client_ids[] = $row['client_id'];
        }
    }
    
    $result = $conn->query("SELECT pharma_id FROM pharmaceuticals LIMIT 5");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $pharma_ids[] = $row['pharma_id'];
        }
    }
    
    $result = $conn->query("SELECT animal_id FROM livestock_poultry LIMIT 10");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $animal_ids[] = $row['animal_id'];
        }
    }
    
    if (empty($client_ids) || empty($pharma_ids)) {
        return 0;
    }
    
    $symptoms = ['Fever', 'Loss of appetite', 'Diarrhea', 'Coughing', 'Lameness', 'Respiratory distress', 'Skin lesions'];
    $types = ['Livestock', 'Poultry'];
    $species = ['Cattle', 'Swine', 'Goat', 'Chicken', 'Duck'];
    
    $count = 0;
    for ($i = 0; $i < 25; $i++) {
        $client_id = $client_ids[array_rand($client_ids)];
        $pharma_id = $pharma_ids[array_rand($pharma_ids)];
        $type = $types[array_rand($types)];
        $species_name = $species[array_rand($species)];
        $symptom = $symptoms[array_rand($symptoms)];
        $poultry_quantity = rand(1, 50);
        $weight = rand(10, 500);
        $status = ['Pending', 'Approved', 'Issued'][array_rand(['Pending', 'Approved', 'Issued'])];
        
        $stmt = $conn->prepare("INSERT INTO pharmaceutical_requests (client_id, type, species, symptoms, pharma_id, poultry_quantity, weight, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("isssidis", $client_id, $type, $species_name, $symptom, $pharma_id, $poultry_quantity, $weight, $status);
            if ($stmt->execute()) {
                $count++;
            }
            $stmt->close();
        }
    }
    return $count;
}

function populateHealthRiskAssessments($conn) {
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
        return 0;
    }
    
    $risk_levels = ['Low', 'Medium', 'High', 'Critical'];
    $statuses = ['Active', 'Resolved', 'Monitoring'];
    
    $count = 0;
    for ($i = 0; $i < 15; $i++) {
        $animal_id = $animal_ids[array_rand($animal_ids)];
        $client_id = $client_ids[array_rand($client_ids)];
        $risk_score = rand(10, 95);
        $risk_level = $risk_levels[array_rand($risk_levels)];
        $risk_factors = json_encode(['Age', 'Weight', 'Environment']);
        $recommendations = json_encode(['Monitor closely', 'Administer medication', 'Isolate animal']);
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
