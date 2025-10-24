<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access - Please login as admin first");
}

header('Content-Type: application/json');

$results = [
    'success' => false,
    'transactions_created' => 0,
    'livestock_created' => 0,
    'pharmaceuticals_created' => 0,
    'errors' => []
];

try {
    // 1. Generate sample transactions (last 12 months)
    $pharmaceuticals = [
        'Ivermectin', 'Amoxicillin', 'Penicillin', 'Tetracycline', 'Vitamin B Complex',
        'Rabies Vaccine', 'Hog Cholera Vaccine', 'Newcastle Disease Vaccine', 'Felbendezole', 'Oxytetracycline'
    ];
    
    $clients = [];
    $client_result = $conn->query("SELECT client_id FROM clients LIMIT 10");
    if ($client_result) {
        while ($row = $client_result->fetch_assoc()) {
            $clients[] = $row['client_id'];
        }
    }
    
    if (empty($clients)) {
        $results['errors'][] = "No clients found. Please add clients first.";
    } else {
        // Generate 50+ transactions over the last 12 months
        for ($i = 0; $i < 60; $i++) {
            $pharma = $pharmaceuticals[array_rand($pharmaceuticals)];
            $client_id = $clients[array_rand($clients)];
            $quantity = rand(1, 20);
            $date = date('Y-m-d', strtotime('-' . rand(0, 365) . ' days'));
            
            $stmt = $conn->prepare("INSERT INTO transactions (client_id, pharmaceutical_name, quantity, request_date, status) VALUES (?, ?, ?, ?, 'Approved')");
            if ($stmt) {
                $stmt->bind_param("isi", $client_id, $pharma, $quantity);
                if ($stmt->execute()) {
                    $results['transactions_created']++;
                }
                $stmt->close();
            }
        }
    }
    
    // 2. Generate sample livestock/poultry data
    $animal_types = ['Livestock', 'Poultry'];
    $livestock_species = ['Cattle', 'Swine', 'Goat', 'Sheep', 'Carabao'];
    $poultry_species = ['Chicken', 'Duck', 'Turkey', 'Quail', 'Pigeon'];
    $sources = ['Owned', 'Purchased', 'Gift', 'Breeding'];
    $health_statuses = ['Healthy', 'Under Treatment', 'Recovering'];
    
    if (!empty($clients)) {
        for ($i = 0; $i < 30; $i++) {
            $animal_type = $animal_types[array_rand($animal_types)];
            $species = $animal_type === 'Livestock' ? 
                      $livestock_species[array_rand($livestock_species)] : 
                      $poultry_species[array_rand($poultry_species)];
            $client_id = $clients[array_rand($clients)];
            $quantity = rand(1, 50);
            $weight = $animal_type === 'Livestock' ? rand(50, 500) : rand(1, 10);
            $source = $sources[array_rand($sources)];
            $health_status = $health_statuses[array_rand($health_statuses)];
            $last_vaccination = $animal_type === 'Livestock' ? date('Y-m-d', strtotime('-' . rand(0, 180) . ' days')) : null;
            $sex = ['Male', 'Female'][array_rand([0, 1])];
            
            $stmt = $conn->prepare("INSERT INTO livestock_poultry (animal_type, species, weight, quantity, client_id, source, health_status, last_vaccination, sex) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssdiisss", $animal_type, $species, $weight, $quantity, $client_id, $source, $health_status, $last_vaccination, $sex);
                if ($stmt->execute()) {
                    $results['livestock_created']++;
                }
                $stmt->close();
            }
        }
    }
    
    // 3. Generate sample pharmaceuticals
    $pharma_data = [
        ['Ivermectin', 100, 20],
        ['Amoxicillin', 50, 10],
        ['Penicillin', 75, 15],
        ['Tetracycline', 60, 12],
        ['Vitamin B Complex', 200, 30],
        ['Rabies Vaccine', 80, 15],
        ['Hog Cholera Vaccine', 40, 8],
        ['Newcastle Disease Vaccine', 90, 18],
        ['Felbendezole', 70, 14],
        ['Oxytetracycline', 55, 11]
    ];
    
    foreach ($pharma_data as $pharma) {
        $stmt = $conn->prepare("INSERT INTO pharmaceuticals (name, stock, reorder_level) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE stock = stock + VALUES(stock)");
        if ($stmt) {
            $stmt->bind_param("sii", $pharma[0], $pharma[1], $pharma[2]);
            if ($stmt->execute()) {
                $results['pharmaceuticals_created']++;
            }
            $stmt->close();
        }
    }
    
    $results['success'] = true;
    $results['message'] = "Sample data generated successfully!";
    
} catch (Exception $e) {
    $results['errors'][] = "Error generating sample data: " . $e->getMessage();
}

echo json_encode($results, JSON_PRETTY_PRINT);
?>
