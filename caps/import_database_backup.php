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
    <title>Smart Database Import for ML - Bago City Veterinary Office</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .btn { padding: 10px 20px; margin: 5px; background: #007bff; color: white; text-decoration: none; border-radius: 3px; }
        .step { background: #f8f9fa; padding: 10px; margin: 5px 0; border-left: 4px solid #007bff; }
    </style>
</head>
<body>";

echo "<h1>Smart Database Import for ML Anomaly Detection</h1>";

try {
    // Check if SQL file exists
    $sql_file = 'database/u520834156_dbBagoVetIMS.sql';
    
    if (!file_exists($sql_file)) {
        echo "<div class='section'>";
        echo "<h3 class='error'>SQL File Not Found</h3>";
        echo "<p>The SQL file 'database/u520834156_dbBagoVetIMS.sql' was not found.</p>";
        echo "</div>";
        exit;
    }
    
    // Check current database status
    echo "<div class='section'>";
    echo "<h3>Current Database Status</h3>";
    
    $tables = ['pharmaceuticals', 'clients', 'transactions', 'livestock_poultry', 'pharmaceutical_requests', 'health_risk_assessments'];
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
    
    if (isset($_POST['import'])) {
        echo "<div class='section'>";
        echo "<h3>Smart Import Process Started</h3>";
        
        // Step 0: Clean existing data to prevent conflicts
        echo "<div class='step'>";
        echo "<h4>Step 0: Cleaning Existing Data</h4>";
        
        // Remove existing transactions to prevent conflicts
        $transactions_removed = 0;
        $result = $conn->query("DELETE FROM transactions");
        if ($result) {
            $transactions_removed = $conn->affected_rows;
        }
        echo "<p class='info'>✓ Removed $transactions_removed existing transaction records</p>";
        
        // Remove existing pharmaceutical requests to prevent conflicts
        $requests_removed = 0;
        $result = $conn->query("DELETE FROM pharmaceutical_requests");
        if ($result) {
            $requests_removed = $conn->affected_rows;
        }
        echo "<p class='info'>✓ Removed $requests_removed existing pharmaceutical request records</p>";
        
        // Remove existing notifications that might cause conflicts
        $notifications_removed = 0;
        $result = $conn->query("DELETE FROM notifications");
        if ($result) {
            $notifications_removed = $conn->affected_rows;
        }
        echo "<p class='info'>✓ Removed $notifications_removed existing notification records</p>";
        
        echo "</div>";
        
        // Step 1: Import base database structure and data
        echo "<div class='step'>";
        echo "<h4>Step 1: Importing Base Database Structure</h4>";
        
        // Read SQL file
        $sql = file_get_contents($sql_file);
        
        if ($sql === false) {
            echo "<p class='error'>Error reading SQL file</p>";
            exit;
        }
        
        // Clean the SQL content to remove problematic data
        echo "<p class='info'>Cleaning SQL content to remove problematic data...</p>";
        
        // Remove problematic INSERT statements completely
        $sql = preg_replace('/INSERT INTO `(transactions|notifications|pharmaceutical_requests)`.*?;/s', '', $sql);
        
        // Remove statements containing problematic data patterns
        $sql = preg_replace('/INSERT INTO.*?(likluk|Vivien Patricio|uploa).*?;/s', '', $sql);
        
        // Remove very long INSERT statements that might cause issues
        $lines = explode("\n", $sql);
        $cleaned_lines = [];
        $current_statement = '';
        $in_long_statement = false;
        
        foreach ($lines as $line) {
            $current_statement .= $line . "\n";
            
            if (preg_match('/INSERT INTO/i', $line)) {
                $in_long_statement = true;
            }
            
            if (strpos($line, ';') !== false) {
                if ($in_long_statement && strlen($current_statement) > 50000) {
                    echo "<p class='info'>Skipped long INSERT statement (" . strlen($current_statement) . " chars)</p>";
                } else {
                    $cleaned_lines[] = $current_statement;
                }
                $current_statement = '';
                $in_long_statement = false;
            }
        }
        
        $sql = implode('', $cleaned_lines);
        echo "<p class='success'>✓ SQL content cleaned and sanitized</p>";
        
        // Disable foreign key checks temporarily
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");
        
        // Split SQL into individual statements
        $statements = explode(';', $sql);
        $imported_count = 0;
        $error_count = 0;
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement) && !preg_match('/^(--|\/\*|\*\/|\#)/', $statement)) {
                // Skip problematic INSERT statements that might cause conflicts
                if (preg_match('/INSERT INTO `(transactions|notifications|pharmaceutical_requests)`/', $statement)) {
                    continue; // Skip these - we'll populate them with ML-optimized data
                }
                
                // Skip statements that contain problematic data patterns
                if (preg_match('/likluk|Vivien Patricio|uploa/', $statement)) {
                    continue; // Skip statements with known problematic data
                }
                
                // Skip very long INSERT statements that might cause issues
                if (strlen($statement) > 50000) {
                    continue; // Skip extremely long statements
                }
                
                if ($conn->query($statement)) {
                    $imported_count++;
                } else {
                    $error_count++;
                    if ($error_count <= 5) { // Show first 5 errors only
                        echo "<p class='error'>SQL Error: " . $conn->error . "</p>";
                        echo "<p class='error'>Statement: " . substr($statement, 0, 200) . "...</p>";
                    }
                }
            }
        }
        
        // Re-enable foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        
        echo "<p class='success'>✓ Base database imported - $imported_count statements executed</p>";
        if ($error_count > 0) {
            echo "<p class='warning'>⚠ $error_count SQL statements skipped due to conflicts</p>";
        }
        
        echo "</div>";
        
        // Step 2: Remove transactions
        echo "<div class='step'>";
        echo "<h4>Step 2: Removing Transactions</h4>";
        
        $transactions_removed = 0;
        $result = $conn->query("DELETE FROM transactions");
        if ($result) {
            $transactions_removed = $conn->affected_rows;
        }
        echo "<p class='success'>✓ Removed $transactions_removed transaction records</p>";
        
        echo "</div>";
        
        // Step 3: Add more clients based on existing patterns
        echo "<div class='step'>";
        echo "<h4>Step 3: Adding More Clients Based on Existing Patterns</h4>";
        
        $client_count = addMoreClients($conn);
        echo "<p class='success'>✓ Added $client_count new client records based on database patterns</p>";
        
        echo "</div>";
        
        // Step 4: Add livestock and poultry records
        echo "<div class='step'>";
        echo "<h4>Step 4: Adding Livestock and Poultry Records</h4>";
        
        $livestock_count = addLivestockPoultryForML($conn);
        echo "<p class='success'>✓ Added $livestock_count livestock/poultry records based on existing patterns</p>";
        
        echo "</div>";
        
        // Step 5: Add pharmaceutical requests for anomaly detection
        echo "<div class='step'>";
        echo "<h4>Step 5: Adding Pharmaceutical Requests for ML Anomaly Detection</h4>";
        
        $request_count = addPharmaceuticalRequestsForML($conn);
        echo "<p class='success'>✓ Added $request_count pharmaceutical request records based on existing patterns</p>";
        
        echo "</div>";
        
        // Final status
        echo "<h3 class='success'>Smart Import Completed Successfully!</h3>";
        echo "<p>Your database is now optimized for ML anomaly detection and health monitoring.</p>";
        
        // Check final database status
        echo "<h4>Final Database Status</h4>";
        foreach ($tables as $table) {
            $result = $conn->query("SELECT COUNT(*) as count FROM $table");
            if ($result) {
                $count = $result->fetch_assoc()['count'];
                echo "<p><strong>$table:</strong> $count records</p>";
            }
        }
        
        echo "<p><a href='admin_dashboard.php' class='btn'>Return to Dashboard</a></p>";
        echo "<p><a href='admin_ml_insights.php' class='btn'>View ML Insights</a></p>";
        echo "</div>";
        
    } else {
        // Show import form
        echo "<div class='section'>";
        echo "<h3>Smart Database Import for ML</h3>";
        echo "<p>This smart import will:</p>";
        echo "<ol>";
        echo "<li><strong>Clean existing data</strong> (remove transactions, requests, notifications)</li>";
        echo "<li><strong>Import base database</strong> from SQL backup file</li>";
        echo "<li><strong>Remove transactions</strong> (NOT populated)</li>";
        echo "<li><strong>Add clients</strong> (50 new records from existing patterns)</li>";
        echo "<li><strong>Add livestock & poultry</strong> (100 new records from existing patterns)</li>";
        echo "<li><strong>Add pharmaceutical requests</strong> (150 new records for ML anomaly detection)</li>";
        echo "</ol>";
        
        echo "<p class='info'><strong>ML Benefits:</strong> This will create rich training data for your anomaly detection and health monitoring machine learning systems.</p>";
        
        echo "<form method='post'>";
        echo "<input type='submit' name='import' value='Start Smart Import for ML' class='btn' style='background: #28a745;'>";
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

// Helper Functions - Only populate clients, livestock_poultry, and pharmaceutical_requests

function addMoreClients($conn) {
    // Fix auto-increment issue - get the max client_id and reset auto-increment
    $result = $conn->query("SELECT MAX(client_id) as max_id FROM clients");
    if ($result) {
        $row = $result->fetch_assoc();
        $next_id = ($row['max_id'] ?? 0) + 1;
        $conn->query("ALTER TABLE clients AUTO_INCREMENT = $next_id");
    }
    
    // Get existing client patterns from database
    $existing_names = [];
    $existing_barangays = [];
    $existing_puroks = [];
    $existing_contact_patterns = [];
    
    $result = $conn->query("SELECT full_name, barangay, purok, contact_number FROM clients LIMIT 100");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['full_name'])) {
                $existing_names[] = $row['full_name'];
            }
            if (!empty($row['barangay'])) {
                $existing_barangays[] = $row['barangay'];
            }
            if (!empty($row['purok'])) {
                $existing_puroks[] = $row['purok'];
            }
            if (!empty($row['contact_number'])) {
                $existing_contact_patterns[] = substr($row['contact_number'], 0, 4); // Get prefix pattern
            }
        }
    }
    
    // Remove duplicates
    $existing_barangays = array_unique($existing_barangays);
    $existing_puroks = array_unique($existing_puroks);
    $existing_contact_patterns = array_unique($existing_contact_patterns);
    
    if (empty($existing_names) || empty($existing_barangays)) {
        return 0; // Can't generate without existing patterns
    }
    
    // Extract name patterns (first names and last names from existing data)
    $first_names = [];
    $last_names = [];
    foreach ($existing_names as $name) {
        $parts = explode(' ', $name);
        if (count($parts) >= 2) {
            $first_names[] = $parts[0];
            $last_names[] = $parts[count($parts) - 1];
        }
    }
    
    $first_names = array_unique($first_names);
    $last_names = array_unique($last_names);
    
    if (empty($first_names) || empty($last_names)) {
        return 0; // Can't generate without name patterns
    }
    
    $count = 0;
    for ($i = 0; $i < 50; $i++) { // Add 50 more clients
        // Use existing name patterns to create variations
        $first_name = $first_names[array_rand($first_names)];
        $last_name = $last_names[array_rand($last_names)];
        $full_name = $first_name . ' ' . $last_name;
        
        // Check if this combination already exists
        $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM clients WHERE full_name = ?");
        if ($check_stmt) {
            $check_stmt->bind_param("s", $full_name);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $check_row = $check_result->fetch_assoc();
            $check_stmt->close();
            
            if ($check_row['count'] > 0) {
                continue; // Skip duplicates
            }
        }
        
        // Use existing contact number patterns
        $contact_prefix = !empty($existing_contact_patterns) ? $existing_contact_patterns[array_rand($existing_contact_patterns)] : '0917';
        $contact_number = $contact_prefix . rand(1000000, 9999999);
        
        $barangay = $existing_barangays[array_rand($existing_barangays)];
        $purok = !empty($existing_puroks) ? $existing_puroks[array_rand($existing_puroks)] : 'Purok ' . rand(1, 10);
        
        $username = strtolower(str_replace(' ', '', $full_name)) . rand(100, 999);
        $password = password_hash('client' . rand(1000, 9999), PASSWORD_DEFAULT);
        
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
    // Fix auto-increment issue - get the max request_id and reset auto-increment
    $result = $conn->query("SELECT MAX(request_id) as max_id FROM pharmaceutical_requests");
    if ($result) {
        $row = $result->fetch_assoc();
        $next_id = ($row['max_id'] ?? 0) + 1;
        $conn->query("ALTER TABLE pharmaceutical_requests AUTO_INCREMENT = $next_id");
    }
    
    // Get species patterns from livestock_poultry table instead
    $existing_species = [];
    $result = $conn->query("SELECT DISTINCT type, species FROM livestock_poultry WHERE species IS NOT NULL AND species != ''");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if (!empty(trim($row['species']))) {
                $existing_species[] = [
                    'type' => $row['type'],
                    'species' => trim($row['species'])
                ];
            }
        }
    }
    
    // Get pharmaceutical IDs
    $existing_pharma_ids = [];
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
    
    // Must have existing data to generate
    if (empty($existing_species) || empty($existing_pharma_ids) || empty($client_ids)) {
        return 0; // Can't create requests without basic data
    }
    
    // Common symptoms for different animal types (based on veterinary knowledge)
    $livestock_symptoms = [
        'Fever', 'Loss of appetite', 'Diarrhea', 'Coughing', 'Lameness', 
        'Respiratory distress', 'Skin lesions', 'Nasal discharge', 'Weight loss', 
        'Lethargy', 'Bloating', 'Difficulty breathing', 'Swollen joints', 
        'Dehydration', 'Pale gums', 'Abnormal behavior', 'Constipation'
    ];
    
    $poultry_symptoms = [
        'Coughing', 'Sneezing', 'Nasal discharge', 'Eye discharge', 'Lethargy',
        'Loss of appetite', 'Diarrhea', 'Respiratory distress', 'Swollen head',
        'Decreased egg production', 'Difficulty breathing', 'Paralysis', 
        'Twisted neck', 'Weight loss', 'Ruffled feathers', 'Sudden death'
    ];
    
    $statuses = ['Pending', 'Approved', 'Issued'];
    
    $count = 0;
    for ($i = 0; $i < 150; $i++) { // Generate 150 requests for robust ML data
        $client_id = $client_ids[array_rand($client_ids)];
        $pharma_id = $existing_pharma_ids[array_rand($existing_pharma_ids)];
        
        // Pick a random species from existing livestock/poultry data
        $species_data = $existing_species[array_rand($existing_species)];
        $type = $species_data['type'];
        $species = $species_data['species'];
        
        // Use appropriate symptoms based on type
        $symptom_list = ($type === 'Poultry') ? $poultry_symptoms : $livestock_symptoms;
        $symptoms = $symptom_list[array_rand($symptom_list)];
        
        // Add multiple symptoms for some requests (for anomaly detection patterns)
        if (rand(1, 3) == 1) {
            $num_additional = rand(1, 2);
            $additional_symptoms = array_rand(array_flip($symptom_list), min($num_additional, count($symptom_list) - 1));
            if (is_array($additional_symptoms)) {
                $symptoms .= ', ' . implode(', ', $additional_symptoms);
            } elseif ($additional_symptoms !== $symptoms) {
                $symptoms .= ', ' . $additional_symptoms;
            }
        }
        
        // Realistic quantity and weight based on type
        if ($type === 'Poultry') {
            $poultry_quantity = rand(5, 200);
            $weight = rand(1, 8);
        } else {
            $poultry_quantity = rand(1, 10);
            $weight = rand(50, 900);
        }
        
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

function addLivestockPoultryForML($conn) {
    // Fix auto-increment issue
    $result = $conn->query("SELECT MAX(animal_id) as max_id FROM livestock_poultry");
    if ($result) {
        $row = $result->fetch_assoc();
        $next_id = ($row['max_id'] ?? 0) + 1;
        $conn->query("ALTER TABLE livestock_poultry AUTO_INCREMENT = $next_id");
    }
    
    // Get existing patterns from livestock_poultry table
    $existing_species = [];
    $result = $conn->query("SELECT DISTINCT type, species FROM livestock_poultry WHERE species IS NOT NULL AND species != ''");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if (!empty(trim($row['species']))) {
                $existing_species[] = [
                    'type' => $row['type'],
                    'species' => trim($row['species'])
                ];
            }
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
    
    if (empty($existing_species) || empty($client_ids)) {
        return 0; // Can't create without patterns
    }
    
    $statuses = ['Alive', 'Dead', 'Sick', 'Recovered'];
    
    $count = 0;
    for ($i = 0; $i < 100; $i++) { // Generate 100 livestock/poultry records
        $client_id = $client_ids[array_rand($client_ids)];
        
        // Pick a random species from existing data
        $species_data = $existing_species[array_rand($existing_species)];
        $type = $species_data['type'];
        $species = $species_data['species'];
        
        // Generate realistic data based on type
        if ($type === 'Poultry') {
            $quantity = rand(10, 500);
            $weight = rand(1, 8);
            $age_months = rand(1, 36);
        } else {
            $quantity = rand(1, 50);
            $weight = rand(50, 900);
            $age_months = rand(3, 120);
        }
        
        $status = $statuses[array_rand($statuses)];
        
        // Generate realistic vaccination and health status
        $vaccinated = (rand(1, 100) > 30) ? 'Yes' : 'No'; // 70% vaccinated
        $health_status = ($status === 'Alive') ? 'Healthy' : (($status === 'Sick') ? 'Sick' : 'Unknown');
        
        $stmt = $conn->prepare("INSERT INTO livestock_poultry (client_id, type, species, quantity, weight, age_months, status, vaccinated, health_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("issidisss", $client_id, $type, $species, $quantity, $weight, $age_months, $status, $vaccinated, $health_status);
            if ($stmt->execute()) {
                $count++;
            }
            $stmt->close();
        }
    }
    
    return $count;
}
?>
