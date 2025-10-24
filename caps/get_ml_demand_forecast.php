<?php
session_start();
include 'includes/conn.php';
include 'includes/ml_demand_forecaster.php';

// Check if user is logged in as admin or staff
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$forecaster = new MLDemandForecaster($conn);

try {
    $type = $_GET['type'] ?? 'pharmaceutical';
    $months_ahead = isset($_GET['months']) ? intval($_GET['months']) : 3;
    
    $result = [];
    
    switch ($type) {
        case 'pharmaceutical':
            $pharma_id = isset($_GET['pharma_id']) ? intval($_GET['pharma_id']) : null;
            $forecast_result = $forecaster->forecastPharmaceuticalDemand($pharma_id, $months_ahead);
            
            // Calculate trend information
            if (isset($forecast_result['historical']) && count($forecast_result['historical']) > 0) {
                $current_avg = array_sum(array_slice($forecast_result['historical'], -3)) / 3;
                $forecast_avg = array_sum($forecast_result['forecast']) / count($forecast_result['forecast']);
                $percentage_change = $current_avg > 0 ? round((($forecast_avg - $current_avg) / $current_avg) * 100, 1) : 0;
                
                $forecast_result['percentage_change'] = $percentage_change;
                
                if (abs($percentage_change) < 5) {
                    $forecast_result['trend_text'] = 'Stable demand expected';
                    $forecast_result['trend_emoji'] = '➖';
                } elseif ($percentage_change > 0) {
                    $forecast_result['trend_text'] = "Demand increasing (+{$percentage_change}%)";
                    $forecast_result['trend_emoji'] = '📈';
                } else {
                    $forecast_result['trend_text'] = "Demand declining ({$percentage_change}%)";
                    $forecast_result['trend_emoji'] = '📉';
                }
            }
            
            $result = $forecast_result;
            break;
            
        case 'livestock':
            $forecast_result = $forecaster->forecastLivestockDemand($months_ahead);
            
            // Calculate trend information
            if (isset($forecast_result['historical']) && count($forecast_result['historical']) > 0) {
                $current_avg = array_sum(array_slice($forecast_result['historical'], -3)) / 3;
                $forecast_avg = array_sum($forecast_result['forecast']) / count($forecast_result['forecast']);
                $percentage_change = $current_avg > 0 ? round((($forecast_avg - $current_avg) / $current_avg) * 100, 1) : 0;
                
                $forecast_result['percentage_change'] = $percentage_change;
                
                if (abs($percentage_change) < 5) {
                    $forecast_result['trend_text'] = 'Stable population expected';
                    $forecast_result['trend_emoji'] = '➖';
                } elseif ($percentage_change > 0) {
                    $forecast_result['trend_text'] = "Population increasing (+{$percentage_change}%)";
                    $forecast_result['trend_emoji'] = '📈';
                } else {
                    $forecast_result['trend_text'] = "Population declining ({$percentage_change}%)";
                    $forecast_result['trend_emoji'] = '📉';
                }
            }
            
            $result = $forecast_result;
            break;
            
        case 'poultry':
            $forecast_result = $forecaster->forecastPoultryDemand($months_ahead);
            
            // Calculate trend information
            if (isset($forecast_result['historical']) && count($forecast_result['historical']) > 0) {
                $current_avg = array_sum(array_slice($forecast_result['historical'], -3)) / 3;
                $forecast_avg = array_sum($forecast_result['forecast']) / count($forecast_result['forecast']);
                $percentage_change = $current_avg > 0 ? round((($forecast_avg - $current_avg) / $current_avg) * 100, 1) : 0;
                
                $forecast_result['percentage_change'] = $percentage_change;
                
                if (abs($percentage_change) < 5) {
                    $forecast_result['trend_text'] = 'Stable population expected';
                    $forecast_result['trend_emoji'] = '➖';
                } elseif ($percentage_change > 0) {
                    $forecast_result['trend_text'] = "Population increasing (+{$percentage_change}%)";
                    $forecast_result['trend_emoji'] = '📈';
                } else {
                    $forecast_result['trend_text'] = "Population declining ({$percentage_change}%)";
                    $forecast_result['trend_emoji'] = '📉';
                }
            }
            
            $result = $forecast_result;
            break;
            
        default:
            $result = ['error' => 'Invalid forecast type'];
    }
    
    // Add chart labels
    $result['historical_labels'] = $forecaster->getHistoricalMonthLabels(count($result['historical'] ?? []));
    $result['forecast_labels'] = $forecaster->getForecastMonthLabels($months_ahead);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Forecasting failed',
        'message' => $e->getMessage()
    ]);
}
?>

