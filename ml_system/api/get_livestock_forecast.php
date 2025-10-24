<?php
header('Content-Type: application/json');
include 'includes/conn.php';

try {
    $species = $_GET['species'] ?? '';
    
    if (empty($species)) {
        throw new Exception("Species parameter is required");
    }
    
    // Sanitize input
    $species = $conn->real_escape_string($species);
    
    // Get historical data for the species (last 12 months)
    $historicalQuery = "SELECT 
                            DATE_FORMAT(created_at, '%Y-%m') as month,
                            SUM(quantity) as total_population
                        FROM livestock_poultry
                        WHERE animal_type = 'Livestock' 
                        AND species = '$species'
                        AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                        ORDER BY month ASC";
    
    $result = $conn->query($historicalQuery);
    
    if (!$result) {
        throw new Exception("Failed to fetch historical data: " . $conn->error);
    }
    
    // Build historical data array
    $monthData = [];
    while ($row = $result->fetch_assoc()) {
        $monthData[$row['month']] = (int)$row['total_population'];
    }
    
    // Fill in missing months with 0
    $historical = [];
    $historicalLabels = [];
    $currentDate = new DateTime('first day of this month');
    
    for ($i = 11; $i >= 0; $i--) {
        $date = clone $currentDate;
        $date->modify("-{$i} months");
        $monthKey = $date->format('Y-m');
        $monthLabel = $date->format('M Y');
        
        $historicalLabels[] = $monthLabel;
        $historical[] = $monthData[$monthKey] ?? 0;
    }
    
    // Generate forecast using simple trend analysis
    // Calculate average growth rate from last 3 months
    $lastThree = array_slice($historical, -3);
    $avgValue = array_sum($lastThree) / max(count($lastThree), 1);
    
    // Calculate trend
    $trend = 0;
    if (count($lastThree) >= 2) {
        $trend = ($lastThree[count($lastThree) - 1] - $lastThree[0]) / max(count($lastThree), 1);
    }
    
    // Generate 3-month forecast
    $forecast = [];
    $forecastLabels = [];
    $lastValue = end($historical) ?: $avgValue;
    
    for ($i = 1; $i <= 3; $i++) {
        $date = clone $currentDate;
        $date->modify("+{$i} months");
        $forecastLabels[] = $date->format('M Y');
        
        // Simple linear projection with slight randomness
        $forecastValue = max(0, round($lastValue + ($trend * $i)));
        $forecast[] = $forecastValue;
    }
    
    // Get current species info
    $infoQuery = "SELECT 
                    COUNT(DISTINCT client_id) as farmers_count,
                    SUM(quantity) as total_population,
                    AVG(weight) as avg_weight
                  FROM livestock_poultry
                  WHERE animal_type = 'Livestock' 
                  AND species = '$species'";
    
    $infoResult = $conn->query($infoQuery);
    $speciesInfo = [
        'farmers_count' => 0,
        'total_population' => 0,
        'avg_weight' => 0
    ];
    
    if ($infoResult && $infoResult->num_rows > 0) {
        $info = $infoResult->fetch_assoc();
        $speciesInfo = [
            'species' => $species,
            'farmers_count' => (int)$info['farmers_count'],
            'total_population' => (int)$info['total_population'],
            'current_population' => (int)$info['total_population'],
            'avg_weight' => round((float)$info['avg_weight'], 2)
        ];
    } else {
        $speciesInfo['species'] = $species;
        $speciesInfo['current_population'] = 0;
    }
    
    // Calculate trend direction
    $trendDirection = 'stable';
    $trendPercent = 0;
    if (count($historical) >= 2) {
        $firstHalf = array_slice($historical, 0, 6);
        $secondHalf = array_slice($historical, 6);
        $firstAvg = array_sum($firstHalf) / max(count($firstHalf), 1);
        $secondAvg = array_sum($secondHalf) / max(count($secondHalf), 1);
        
        if ($firstAvg > 0) {
            $trendPercent = round((($secondAvg - $firstAvg) / $firstAvg) * 100, 1);
        }
        
        if ($trendPercent > 5) {
            $trendDirection = 'increasing';
        } elseif ($trendPercent < -5) {
            $trendDirection = 'decreasing';
        }
    }
    
    echo json_encode([
        'success' => true,
        'forecast' => [
            'species_name' => $species,
            'historical' => $historical,
            'historical_labels' => $historicalLabels,
            'forecast' => $forecast,
            'forecast_labels' => $forecastLabels,
            'trend_direction' => $trendDirection,
            'trend_percent' => $trendPercent
        ],
        'species_info' => $speciesInfo,
        'generated_at' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to generate livestock forecast',
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>

