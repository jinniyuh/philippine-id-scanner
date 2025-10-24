<?php
// Start output buffering to prevent any accidental output
ob_start();

session_start();
include 'includes/conn.php';
include 'includes/arima_forecaster.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    ob_clean(); // Clear any output
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Clear any accidental output and set headers
ob_clean();
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

$forecaster = new VeterinaryForecaster($conn);

try {
    $insights = [];
    
    // 1. Pharmaceutical Demand Forecast
    $pharma_forecast = $forecaster->forecastPharmaceuticalDemand(null, 3);
    if (!isset($pharma_forecast['error'])) {
        $current_avg = array_sum(array_slice($pharma_forecast['historical'], -3)) / 3;
        $forecast_avg = array_sum($pharma_forecast['forecast']) / count($pharma_forecast['forecast']);
        $percentage_change = $current_avg > 0 ? round((($forecast_avg - $current_avg) / $current_avg) * 100, 1) : 0;
        
        if (abs($percentage_change) < 5) {
            $trend = 'stable';
            $trend_emoji = '➖';
            $trend_text = 'Stable demand expected';
        } elseif ($percentage_change > 0) {
            $trend = 'increasing';
            $trend_emoji = '📈';
            $trend_text = "Demand increasing (+{$percentage_change}% next quarter)";
        } else {
            $trend = 'decreasing';
            $trend_emoji = '📉';
            $trend_text = "Demand declining ({$percentage_change}% next quarter)";
        }
        
        $insights['pharmaceutical_demand'] = [
            'title' => 'Pharmaceutical Demand Forecast',
            'description' => 'Predicted demand for the next 3 months',
            'forecast' => $pharma_forecast['forecast'],
            'historical' => $pharma_forecast['historical'],
            'trend' => $trend,
            'trend_emoji' => $trend_emoji,
            'trend_text' => $trend_text,
            'percentage_change' => $percentage_change
        ];
    }
    
    // 2. Livestock Population Forecast
    $livestock_forecast = $forecaster->forecastLivestockPopulation('Livestock', 3);
    if (!isset($livestock_forecast['error'])) {
        $current_avg = array_sum(array_slice($livestock_forecast['historical'], -3)) / 3;
        $forecast_avg = array_sum($livestock_forecast['forecast']) / count($livestock_forecast['forecast']);
        $percentage_change = $current_avg > 0 ? round((($forecast_avg - $current_avg) / $current_avg) * 100, 1) : 0;
        
        if (abs($percentage_change) < 5) {
            $trend = 'stable';
            $trend_emoji = '➖';
            $trend_text = 'Stable population expected';
        } elseif ($percentage_change > 0) {
            $trend = 'growing';
            $trend_emoji = '📈';
            $trend_text = "Population growing (+{$percentage_change}% next quarter)";
        } else {
            $trend = 'declining';
            $trend_emoji = '📉';
            $trend_text = "Population declining ({$percentage_change}% next quarter)";
        }
        
        $insights['livestock_population'] = [
            'title' => 'Livestock Population Forecast',
            'description' => 'Predicted livestock population for the next 3 months',
            'forecast' => $livestock_forecast['forecast'],
            'historical' => $livestock_forecast['historical'],
            'trend' => $trend,
            'trend_emoji' => $trend_emoji,
            'trend_text' => $trend_text,
            'percentage_change' => $percentage_change
        ];
    }
    
    // 3. Poultry Population Forecast
    $poultry_forecast = $forecaster->forecastLivestockPopulation('Poultry', 3);
    if (!isset($poultry_forecast['error'])) {
        $current_avg = array_sum(array_slice($poultry_forecast['historical'], -3)) / 3;
        $forecast_avg = array_sum($poultry_forecast['forecast']) / count($poultry_forecast['forecast']);
        $percentage_change = $current_avg > 0 ? round((($forecast_avg - $current_avg) / $current_avg) * 100, 1) : 0;
        
        if (abs($percentage_change) < 5) {
            $trend = 'stable';
            $trend_emoji = '➖';
            $trend_text = 'Stable population expected';
        } elseif ($percentage_change > 0) {
            $trend = 'growing';
            $trend_emoji = '📈';
            $trend_text = "Population growing (+{$percentage_change}% next quarter)";
        } else {
            $trend = 'declining';
            $trend_emoji = '📉';
            $trend_text = "Population declining ({$percentage_change}% next quarter)";
        }
        
        $insights['poultry_population'] = [
            'title' => 'Poultry Population Forecast',
            'description' => 'Predicted poultry population for the next 3 months',
            'forecast' => $poultry_forecast['forecast'],
            'historical' => $poultry_forecast['historical'],
            'trend' => $trend,
            'trend_emoji' => $trend_emoji,
            'trend_text' => $trend_text,
            'percentage_change' => $percentage_change
        ];
    }
    
    // 4. Low Stock Predictions
    $low_stock_predictions = $forecaster->getLowStockPredictions();
    $insights['low_stock_alerts'] = [
        'title' => 'Low Stock Predictions',
        'description' => 'Pharmaceuticals that may run out of stock soon',
        'predictions' => $low_stock_predictions,
        'critical_count' => count(array_filter($low_stock_predictions, function($item) {
            return $item['days_until_stockout'] <= 7;
        }))
    ];
    
    // 5. Seasonal Trends
    $seasonal_trends = $forecaster->getSeasonalTrends();
    $insights['seasonal_analysis'] = [
        'title' => 'Seasonal Trends Analysis',
        'description' => 'Monthly transaction patterns over the past year',
        'data' => $seasonal_trends,
        'peak_month' => array_search(max(array_column($seasonal_trends, 'count')), array_column($seasonal_trends, 'count')),
        'low_month' => array_search(min(array_column($seasonal_trends, 'count')), array_column($seasonal_trends, 'count'))
    ];
    
    // 6. Transaction Volume Forecast
    $transaction_data = $forecaster->getTransactionTrends(12);
    if (count($transaction_data) >= 3) {
        $transaction_forecaster = new ARIMAForecaster($transaction_data, 1, 1, 1);
        $transaction_forecast = $transaction_forecaster->forecast(3);
        
        $current_avg = array_sum(array_slice($transaction_data, -3)) / 3;
        $forecast_avg = array_sum($transaction_forecast) / count($transaction_forecast);
        $percentage_change = $current_avg > 0 ? round((($forecast_avg - $current_avg) / $current_avg) * 100, 1) : 0;
        
        if (abs($percentage_change) < 5) {
            $trend = 'stable';
            $trend_emoji = '➖';
            $trend_text = 'Stable transaction volume expected';
        } elseif ($percentage_change > 0) {
            $trend = 'increasing';
            $trend_emoji = '📈';
            $trend_text = "Transaction volume increasing (+{$percentage_change}% next quarter)";
        } else {
            $trend = 'decreasing';
            $trend_emoji = '📉';
            $trend_text = "Transaction volume declining ({$percentage_change}% next quarter)";
        }
        
        // Generate month labels for charts
        $historical_labels = $forecaster->getHistoricalMonthLabels(12);
        $forecast_labels = $forecaster->getForecastMonthLabels(3);

        $insights['transaction_volume'] = [
            'title' => 'Transaction Volume Forecast',
            'description' => 'Predicted number of transactions for the next 3 months',
            'forecast' => $transaction_forecast,
            'historical' => $transaction_data,
            'historical_labels' => $historical_labels,
            'forecast_labels' => $forecast_labels,
            'trend' => $trend,
            'trend_emoji' => $trend_emoji,
            'trend_text' => $trend_text,
            'percentage_change' => $percentage_change
        ];
    }
    
    // 7. Generate actionable insights
    $insights['recommendations'] = [];
    
    // Stock recommendations
    if (!empty($low_stock_predictions)) {
        $critical_items = array_filter($low_stock_predictions, function($item) {
            return $item['days_until_stockout'] <= 7;
        });
        
        if (!empty($critical_items)) {
            $insights['recommendations'][] = [
                'type' => 'urgent',
                'message' => 'Critical: ' . count($critical_items) . ' pharmaceutical(s) may run out within a week',
                'action' => 'Immediate restocking required'
            ];
        }
    }
    
    // Demand recommendations
    if (isset($insights['pharmaceutical_demand'])) {
        $avg_forecast = array_sum($pharma_forecast['forecast']) / count($pharma_forecast['forecast']);
        $current_avg = array_sum(array_slice($pharma_forecast['historical'], -3)) / 3;
        
        if ($avg_forecast > $current_avg * 1.2) {
            $insights['recommendations'][] = [
                'type' => 'warning',
                'message' => 'Expected 20% increase in pharmaceutical demand',
                'action' => 'Consider increasing stock levels'
            ];
        }
    }
    
    // Population recommendations
    if (isset($insights['livestock_population']) && isset($insights['poultry_population'])) {
        $livestock_growth = $livestock_forecast['forecast'][0] - end($livestock_forecast['historical']);
        $poultry_growth = $poultry_forecast['forecast'][0] - end($poultry_forecast['historical']);
        
        if ($livestock_growth > 0 || $poultry_growth > 0) {
            $insights['recommendations'][] = [
                'type' => 'info',
                'message' => 'Growing animal population detected',
                'action' => 'Prepare for increased veterinary service demand'
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'insights' => $insights,
        'generated_at' => date('Y-m-d H:i:s'),
        'model' => 'ARIMA(1,1,1)'
    ]);
    
} catch (Exception $e) {
    ob_clean(); // Clear any output
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to generate insights',
        'message' => $e->getMessage()
    ]);
}

// Flush the output buffer
ob_end_flush();
