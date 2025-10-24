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
    <title>Database Structure Analysis - Bago City Veterinary Office</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .btn { padding: 10px 20px; margin: 5px; background: #007bff; color: white; text-decoration: none; border-radius: 3px; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>";

echo "<h1>Database Structure Analysis for ML Anomaly Detection</h1>";

try {
    // Analyze pharmaceutical_requests table structure and patterns
    echo "<div class='section'>";
    echo "<h3>Pharmaceutical Requests Analysis</h3>";
    
    // Get all unique symptoms
    $result = $conn->query("SELECT DISTINCT symptoms FROM pharmaceutical_requests WHERE symptoms IS NOT NULL AND symptoms != ''");
    $symptoms = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if (!empty(trim($row['symptoms']))) {
                $symptoms[] = trim($row['symptoms']);
            }
        }
    }
    
    echo "<h4>Existing Symptoms Patterns:</h4>";
    echo "<ul>";
    foreach (array_unique($symptoms) as $symptom) {
        echo "<li>" . htmlspecialchars($symptom) . "</li>";
    }
    echo "</ul>";
    
    // Get all unique species
    $result = $conn->query("SELECT DISTINCT species FROM pharmaceutical_requests WHERE species IS NOT NULL AND species != ''");
    $species = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if (!empty(trim($row['species']))) {
                $species[] = trim($row['species']);
            }
        }
    }
    
    echo "<h4>Existing Species Patterns:</h4>";
    echo "<ul>";
    foreach (array_unique($species) as $specie) {
        echo "<li>" . htmlspecialchars($specie) . "</li>";
    }
    echo "</ul>";
    
    // Get animal types distribution
    $result = $conn->query("SELECT type, COUNT(*) as count FROM pharmaceutical_requests GROUP BY type");
    echo "<h4>Animal Type Distribution:</h4>";
    echo "<table>";
    echo "<tr><th>Type</th><th>Count</th></tr>";
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>" . htmlspecialchars($row['type']) . "</td><td>" . $row['count'] . "</td></tr>";
        }
    }
    echo "</table>";
    
    // Get weight ranges
    $result = $conn->query("SELECT MIN(weight) as min_weight, MAX(weight) as max_weight, AVG(weight) as avg_weight FROM pharmaceutical_requests WHERE weight IS NOT NULL");
    if ($result) {
        $weight_data = $result->fetch_assoc();
        echo "<h4>Weight Statistics:</h4>";
        echo "<p>Min: " . round($weight_data['min_weight'], 2) . " | Max: " . round($weight_data['max_weight'], 2) . " | Average: " . round($weight_data['avg_weight'], 2) . "</p>";
    }
    
    // Get quantity ranges
    $result = $conn->query("SELECT MIN(poultry_quantity) as min_qty, MAX(poultry_quantity) as max_qty, AVG(poultry_quantity) as avg_qty FROM pharmaceutical_requests WHERE poultry_quantity IS NOT NULL");
    if ($result) {
        $qty_data = $result->fetch_assoc();
        echo "<h4>Quantity Statistics:</h4>";
        echo "<p>Min: " . round($qty_data['min_qty'], 2) . " | Max: " . round($qty_data['max_qty'], 2) . " | Average: " . round($qty_data['avg_qty'], 2) . "</p>";
    }
    echo "</div>";
    
    // Analyze pharmaceuticals table
    echo "<div class='section'>";
    echo "<h3>Pharmaceuticals Analysis</h3>";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM pharmaceuticals");
    $pharma_count = $result ? $result->fetch_assoc()['count'] : 0;
    echo "<p><strong>Total Pharmaceuticals:</strong> $pharma_count</p>";
    
    // Get categories
    $result = $conn->query("SELECT category, COUNT(*) as count FROM pharmaceuticals GROUP BY category");
    echo "<h4>Pharmaceutical Categories:</h4>";
    echo "<table>";
    echo "<tr><th>Category</th><th>Count</th></tr>";
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>" . htmlspecialchars($row['category']) . "</td><td>" . $row['count'] . "</td></tr>";
        }
    }
    echo "</table>";
    
    // Get units
    $result = $conn->query("SELECT unit, COUNT(*) as count FROM pharmaceuticals GROUP BY unit");
    echo "<h4>Pharmaceutical Units:</h4>";
    echo "<table>";
    echo "<tr><th>Unit</th><th>Count</th></tr>";
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>" . htmlspecialchars($row['unit']) . "</td><td>" . $row['count'] . "</td></tr>";
        }
    }
    echo "</table>";
    echo "</div>";
    
    // Analyze clients table
    echo "<div class='section'>";
    echo "<h3>Clients Analysis</h3>";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM clients");
    $client_count = $result ? $result->fetch_assoc()['count'] : 0;
    echo "<p><strong>Total Clients:</strong> $client_count</p>";
    
    // Get barangay distribution
    $result = $conn->query("SELECT barangay, COUNT(*) as count FROM clients GROUP BY barangay ORDER BY count DESC");
    echo "<h4>Client Distribution by Barangay:</h4>";
    echo "<table>";
    echo "<tr><th>Barangay</th><th>Count</th></tr>";
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>" . htmlspecialchars($row['barangay']) . "</td><td>" . $row['count'] . "</td></tr>";
        }
    }
    echo "</table>";
    echo "</div>";
    
    // Analyze health_risk_assessments table
    echo "<div class='section'>";
    echo "<h3>Health Risk Assessments Analysis</h3>";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM health_risk_assessments");
    $assessment_count = $result ? $result->fetch_assoc()['count'] : 0;
    echo "<p><strong>Total Risk Assessments:</strong> $assessment_count</p>";
    
    // Get risk level distribution
    $result = $conn->query("SELECT risk_level, COUNT(*) as count FROM health_risk_assessments GROUP BY risk_level");
    echo "<h4>Risk Level Distribution:</h4>";
    echo "<table>";
    echo "<tr><th>Risk Level</th><th>Count</th></tr>";
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr><td>" . htmlspecialchars($row['risk_level']) . "</td><td>" . $row['count'] . "</td></tr>";
        }
    }
    echo "</table>";
    
    // Get risk score ranges
    $result = $conn->query("SELECT MIN(risk_score) as min_score, MAX(risk_score) as max_score, AVG(risk_score) as avg_score FROM health_risk_assessments");
    if ($result) {
        $score_data = $result->fetch_assoc();
        echo "<h4>Risk Score Statistics:</h4>";
        echo "<p>Min: " . round($score_data['min_score'], 2) . " | Max: " . round($score_data['max_score'], 2) . " | Average: " . round($score_data['avg_score'], 2) . "</p>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='section'>";
    echo "<h3 class='error'>Error</h3>";
    echo "<p class='error'>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>
