<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Function to generate random date within a range
function randomDate($start_date, $end_date) {
    $start = strtotime($start_date);
    $end = strtotime($end_date);
    $random_timestamp = rand($start, $end);
    return date('Y-m-d H:i:s', $random_timestamp);
}

// Function to generate sample pharmaceutical data
function generatePharmaceuticals($conn) {
    $pharmaceuticals = [
        ['name' => 'Dewormer', 'stock' => 45, 'reorder_level' => 10],
        ['name' => 'Antibiotics', 'stock' => 23, 'reorder_level' => 15],
        ['name' => 'Vaccines', 'stock' => 67, 'reorder_level' => 20],
        ['name' => 'Vitamins', 'stock' => 89, 'reorder_level' => 25],
        ['name' => 'Pain Relievers', 'stock' => 34, 'reorder_level' => 12],
        ['name' => 'Antiseptics', 'stock' => 56, 'reorder_level' => 18],
        ['name' => 'Fertilizers', 'stock' => 78, 'reorder_level' => 30],
        ['name' => 'Feed Supplements', 'stock' => 12, 'reorder_level' => 15]
    ];
    
    foreach ($pharmaceuticals as $pharma) {
        $sql = "INSERT INTO pharmaceuticals (name, stock, reorder_level) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $pharma['name'], $pharma['stock'], $pharma['reorder_level']);
        $stmt->execute();
    }
    
    return $conn->insert_id;
}

// Function to generate sample livestock data
function generateLivestockData($conn) {
    $animal_types = ['Livestock', 'Poultry'];
    $livestock_species = ['Cattle', 'Swine', 'Goat', 'Sheep', 'Carabao'];
    $poultry_species = ['Chicken', 'Duck', 'Turkey', 'Quail', 'Pigeon'];
    
    // Generate data for the past 12 months
    for ($month = 11; $month >= 0; $month--) {
        $date = date('Y-m-d H:i:s', strtotime("-$month months"));
        
        // Generate livestock data
        foreach ($livestock_species as $species) {
            $quantity = rand(10, 50);
            $sql = "INSERT INTO livestock_poultry (client_id, age, animal_type, species, quantity, created_at) 
                    VALUES (1, ?, 'Livestock', ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $age = rand(1, 10);
            $stmt->bind_param("isis", $age, $species, $quantity, $date);
            $stmt->execute();
        }
        
        // Generate poultry data
        foreach ($poultry_species as $species) {
            $quantity = rand(20, 100);
            $sql = "INSERT INTO livestock_poultry (client_id, age, animal_type, species, quantity, created_at) 
                    VALUES (1, ?, 'Poultry', ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $age = rand(1, 5);
            $stmt->bind_param("isis", $age, $species, $quantity, $date);
            $stmt->execute();
        }
    }
}

// Function to generate sample transaction data
function generateTransactionData($conn) {
    // Get pharmaceutical IDs
    $pharma_result = $conn->query("SELECT pharma_id FROM pharmaceuticals LIMIT 5");
    $pharma_ids = [];
    while ($row = $pharma_result->fetch_assoc()) {
        $pharma_ids[] = $row['pharma_id'];
    }
    
    // Generate transactions for the past 12 months
    for ($month = 11; $month >= 0; $month--) {
        $base_date = strtotime("-$month months");
        
        // Generate 5-15 transactions per month
        $transactions_count = rand(5, 15);
        
        for ($i = 0; $i < $transactions_count; $i++) {
            $request_date = date('Y-m-d H:i:s', $base_date + rand(0, 2592000)); // Random day in month
            $pharma_id = $pharma_ids[array_rand($pharma_ids)];
            $quantity = rand(1, 10);
            $status = ['Pending', 'Approved', 'Issued'][array_rand(['Pending', 'Approved', 'Issued'])];
            
            $sql = "INSERT INTO transactions (client_id, user_id, pharma_id, quantity, status, request_date) 
                    VALUES (1, 1, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiss", $pharma_id, $quantity, $status, $request_date);
            $stmt->execute();
        }
    }
}

// Function to generate sample client data
function generateClientData($conn) {
    $clients = [
        ['full_name' => 'Juan Dela Cruz', 'contact_number' => '09123456789', 'address' => 'Brgy. Dulao'],
        ['full_name' => 'Maria Santos', 'contact_number' => '09234567890', 'address' => 'Brgy. Ilijan'],
        ['full_name' => 'Pedro Garcia', 'contact_number' => '09345678901', 'address' => 'Brgy. Ma-ao'],
        ['full_name' => 'Ana Rodriguez', 'contact_number' => '09456789012', 'address' => 'Brgy. Binubuhan'],
        ['full_name' => 'Luis Martinez', 'contact_number' => '09567890123', 'address' => 'Brgy. Lag-asan']
    ];
    
    foreach ($clients as $client) {
        $username = strtolower(str_replace(' ', '', $client['full_name']));
        $password = password_hash('password123', PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO clients (full_name, contact_number, address, username, password) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $client['full_name'], $client['contact_number'], 
                         $client['address'], $username, $password);
        $stmt->execute();
    }
}

// Main execution
try {
    // Check if data already exists
    $check_pharma = $conn->query("SELECT COUNT(*) as count FROM pharmaceuticals");
    $pharma_count = $check_pharma->fetch_assoc()['count'];
    
    if ($pharma_count == 0) {
        echo "<h2>Generating Sample Data...</h2>";
        
        // Generate sample data
        generateClientData($conn);
        echo "<p>✓ Generated sample clients</p>";
        
        generatePharmaceuticals($conn);
        echo "<p>✓ Generated sample pharmaceuticals</p>";
        
        generateLivestockData($conn);
        echo "<p>✓ Generated sample livestock/poultry data</p>";
        
        generateTransactionData($conn);
        echo "<p>✓ Generated sample transaction data</p>";
        
        echo "<h3>Sample data generation completed!</h3>";
        echo "<p><a href='admin_dashboard.php'>Return to Dashboard</a></p>";
        echo "<p><a href='admin_ml_insights.php'>View ML Insights</a></p>";
        
    } else {
        echo "<h2>Sample data already exists!</h2>";
        echo "<p>The database already contains data. No need to regenerate.</p>";
        echo "<p><a href='admin_dashboard.php'>Return to Dashboard</a></p>";
        echo "<p><a href='admin_ml_insights.php'>View ML Insights</a></p>";
    }
    
} catch (Exception $e) {
    echo "<h2>Error generating sample data</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p><a href='admin_dashboard.php'>Return to Dashboard</a></p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Sample Data - Bago City Veterinary Office</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #6c63ff;
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Content will be generated by PHP above -->
    </div>
</body>
</html>
