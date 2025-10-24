<?php
header('Content-Type: application/json');
include 'includes/conn.php';

try {
    $medicine_id = $_GET['medicine_id'] ?? '';
    
    if (empty($medicine_id)) {
        throw new Exception("Medicine ID parameter is required");
    }
    
    // Sanitize input
    $medicine_id = intval($medicine_id);
    
    // Get medicine information
    $medicineQuery = "SELECT pharma_id, name, category, stock, unit 
                      FROM pharmaceuticals 
                      WHERE pharma_id = $medicine_id";
    
    $medicineResult = $conn->query($medicineQuery);
    
    if (!$medicineResult || $medicineResult->num_rows === 0) {
        throw new Exception("Medicine not found");
    }
    
    $medicineInfo = $medicineResult->fetch_assoc();
    
    // Get historical usage data for the medicine (last 12 months)
    $historicalQuery = "SELECT 
                            DATE_FORMAT(request_date, '%Y-%m') as month,
                            SUM(quantity) as total_usage
                        FROM transactions
                        WHERE pharma_id = $medicine_id
                        AND status = 'Approved'
                        AND request_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                        GROUP BY DATE_FORMAT(request_date, '%Y-%m')
                        ORDER BY month ASC";
    
    $result = $conn->query($historicalQuery);
    
    if (!$result) {
        throw new Exception("Failed to fetch historical data: " . $conn->error);
    }
    
    // Build historical data array
    $monthData = [];
    while ($row = $result->fetch_assoc()) {
        $monthData[$row['month']] = (int)$row['total_usage'];
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
    // Calculate average usage from last 3 months
    $lastThree = array_slice($historical, -3);
    $avgUsage = array_sum($lastThree) / max(count($lastThree), 1);
    
    // Calculate trend
    $trend = 0;
    if (count($lastThree) >= 2) {
        $trend = ($lastThree[count($lastThree) - 1] - $lastThree[0]) / max(count($lastThree), 1);
    }
    
    // Generate 3-month forecast
    $forecast = [];
    $forecastLabels = [];
    $lastValue = end($historical) ?: $avgUsage;
    
    for ($i = 1; $i <= 3; $i++) {
        $date = clone $currentDate;
        $date->modify("+{$i} months");
        $forecastLabels[] = $date->format('M Y');
        
        // Simple linear projection
        $forecastValue = max(0, round($lastValue + ($trend * $i)));
        $forecast[] = $forecastValue;
    }
    
    // Calculate trend direction and percentage
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
    
    // Calculate accuracy based on how stable the data is
    $accuracy = 'N/A';
    if (count($historical) >= 3) {
        $variance = 0;
        $mean = array_sum($historical) / count($historical);
        
        if ($mean > 0) {
            foreach ($historical as $value) {
                $variance += pow($value - $mean, 2);
            }
            $variance /= count($historical);
            $stdDev = sqrt($variance);
            
            // Convert to accuracy percentage (lower variance = higher accuracy)
            $coefficientOfVariation = ($mean > 0) ? ($stdDev / $mean) : 1;
            $accuracy = max(0, min(100, round((1 - $coefficientOfVariation) * 100, 1)));
        }
    }
    
    // Get total dispensed and transaction count
    $statsQuery = "SELECT 
                    COUNT(transaction_id) as transaction_count,
                    SUM(quantity) as total_dispensed,
                    MIN(request_date) as first_transaction,
                    MAX(request_date) as last_transaction
                   FROM transactions
                   WHERE pharma_id = $medicine_id
                   AND status = 'Approved'";
    
    $statsResult = $conn->query($statsQuery);
    $stats = [
        'transaction_count' => 0,
        'total_dispensed' => 0,
        'first_transaction' => null,
        'last_transaction' => null
    ];
    
    if ($statsResult && $statsResult->num_rows > 0) {
        $stats = $statsResult->fetch_assoc();
    }
    
    echo json_encode([
        'success' => true,
        'forecast' => [
            'medicine_name' => $medicineInfo['name'],
            'historical' => $historical,
            'historical_labels' => $historicalLabels,
            'forecast' => $forecast,
            'forecast_labels' => $forecastLabels,
            'trend_direction' => $trendDirection,
            'trend_percent' => $trendPercent,
            'accuracy' => $accuracy
        ],
        'medicine_info' => [
            'pharma_id' => $medicineInfo['pharma_id'],
            'name' => $medicineInfo['name'],
            'category' => $medicineInfo['category'],
            'stock' => (int)$medicineInfo['stock'],
            'unit' => $medicineInfo['unit'] ?? 'units',
            'transaction_count' => (int)$stats['transaction_count'],
            'total_dispensed' => (int)$stats['total_dispensed'],
            'first_transaction' => $stats['first_transaction'],
            'last_transaction' => $stats['last_transaction']
        ],
        'generated_at' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to generate pharmaceutical forecast',
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
