<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Start output buffering early to capture any stray output and keep JSON valid
if (!ob_get_level()) { ob_start(); }

// Be quiet on production: do not display warnings/notices that would break JSON
@ini_set('display_errors', '0');
@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

include 'includes/conn.php';
include 'includes/arima_forecaster.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(200);
    if (ob_get_length()) { ob_clean(); }
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    if (ob_get_level()) { ob_end_flush(); }
    exit();
}

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

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
        
        // Generate month labels
        $historical_labels = $forecaster->getHistoricalMonthLabels(12);
        $forecast_labels = $forecaster->getForecastMonthLabels(3);

        $insights['pharmaceutical_demand'] = [
            'title' => 'Pharmaceutical Demand Forecast',
            'description' => 'Predicted demand for the next 3 months',
            'forecast' => $pharma_forecast['forecast'],
            'historical' => $pharma_forecast['historical'],
            'historical_labels' => $historical_labels,
            'forecast_labels' => $forecast_labels,
            'trend' => $trend,
            'trend_emoji' => $trend_emoji,
            'trend_text' => $trend_text,
            'percentage_change' => $percentage_change
        ];
    } else {
        // NO FALLBACK - Show error if forecasting fails
        $insights['pharmaceutical_demand'] = [
            'error' => true,
            'message' => $pharma_forecast['error'] ?? 'Insufficient data for forecasting',
            'details' => 'Need at least 3 months of transaction data for accurate forecasting'
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
        
        // Generate month labels
        $historical_labels = $forecaster->getHistoricalMonthLabels(12);
        $forecast_labels = $forecaster->getForecastMonthLabels(3);

        $insights['livestock_population'] = [
            'title' => 'Livestock Population Forecast',
            'description' => 'Predicted livestock population for the next 3 months',
            'forecast' => $livestock_forecast['forecast'],
            'historical' => $livestock_forecast['historical'],
            'historical_labels' => $historical_labels,
            'forecast_labels' => $forecast_labels,
            'trend' => $trend,
            'trend_emoji' => $trend_emoji,
            'trend_text' => $trend_text,
            'percentage_change' => $percentage_change
        ];
    } else {
        // NO FALLBACK - Show error if forecasting fails
        $insights['livestock_population'] = [
            'error' => true,
            'message' => $livestock_forecast['error'] ?? 'Insufficient data for forecasting',
            'details' => 'Need at least 3 months of livestock registration data'
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
        
        // Generate month labels
        $historical_labels = $forecaster->getHistoricalMonthLabels(12);
        $forecast_labels = $forecaster->getForecastMonthLabels(3);

        $insights['poultry_population'] = [
            'title' => 'Poultry Population Forecast',
            'description' => 'Predicted poultry population for the next 3 months',
            'forecast' => $poultry_forecast['forecast'],
            'historical' => $poultry_forecast['historical'],
            'historical_labels' => $historical_labels,
            'forecast_labels' => $forecast_labels,
            'trend' => $trend,
            'trend_emoji' => $trend_emoji,
            'trend_text' => $trend_text,
            'percentage_change' => $percentage_change
        ];
    } else {
        // NO FALLBACK - Show error if forecasting fails
        $insights['poultry_population'] = [
            'error' => true,
            'message' => $poultry_forecast['error'] ?? 'Insufficient data for forecasting',
            'details' => 'Need at least 3 months of poultry registration data'
        ];
    }
    
    // 4. Low Stock Predictions
    $low_stock_predictions = $forecaster->getLowStockPredictions();
    $insights['low_stock_alerts'] = [
        'title' => 'Low Stock Predictions',
        'description' => 'Pharmaceuticals that may run out of stock soon',
        'predictions' => $low_stock_predictions,
        'critical_count' => count(array_filter($low_stock_predictions, function($item) {
            return isset($item['days_until_stockout']) && $item['days_until_stockout'] <= 7;
        }))
    ];
    
    // 5. Critical Alerts
    $critical_alerts = $forecaster->getCriticalAlerts();
    $insights['critical_alerts'] = $critical_alerts;

    // 6. Data Points Information
    $data_points_info = $forecaster->getDataPointsInfo();
    $insights['data_points_info'] = $data_points_info;

    // 7. Seasonal Trends
    $seasonal_trends = $forecaster->getSeasonalTrends();
    $insights['seasonal_analysis'] = [
        'title' => 'Seasonal Trends Analysis',
        'description' => 'Monthly transaction patterns over the past year',
        'data' => $seasonal_trends,
        'peak_month' => array_search(max(array_column($seasonal_trends, 'count')), array_column($seasonal_trends, 'count')),
        'low_month' => array_search(min(array_column($seasonal_trends, 'count')), array_column($seasonal_trends, 'count'))
    ];
    
    // 8. Transaction Volume Forecast
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
        
        // Generate month labels
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
    } else {
        // NO FALLBACK - Show error if forecasting fails
        $insights['transaction_volume'] = [
            'error' => true,
            'message' => 'Insufficient transaction data',
            'details' => 'Need at least 3 months of transaction data'
        ];
    }
    
    // 9. Generate actionable insights
    $insights['recommendations'] = [];
    
    // Stock recommendations
    if (!empty($low_stock_predictions)) {
        $critical_items = array_filter($low_stock_predictions, function($item) {
            return isset($item['days_until_stockout']) && $item['days_until_stockout'] <= 7;
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
    if (isset($insights['pharmaceutical_demand']) && !isset($insights['pharmaceutical_demand']['error'])) {
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
    if (isset($insights['livestock_population']) && isset($insights['poultry_population']) && 
        !isset($insights['livestock_population']['error']) && !isset($insights['poultry_population']['error'])) {
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
    
    // Add data quality recommendations
    $data_quality_issues = [];
    if (isset($insights['pharmaceutical_demand']['error'])) $data_quality_issues[] = 'pharmaceutical';
    if (isset($insights['livestock_population']['error'])) $data_quality_issues[] = 'livestock';
    if (isset($insights['poultry_population']['error'])) $data_quality_issues[] = 'poultry';
    if (isset($insights['transaction_volume']['error'])) $data_quality_issues[] = 'transaction';
    
    if (!empty($data_quality_issues)) {
        $insights['recommendations'][] = [
            'type' => 'info',
            'message' => 'Limited data available for: ' . implode(', ', $data_quality_issues),
            'action' => 'Continue using the system to build more historical data for accurate forecasting'
        ];
    }
    
    // 10. Calculate overall forecast accuracy
    $overall_accuracy = 'N/A';
    $accuracy_scores = [];
    
    // Calculate accuracy for each forecast type
    if (isset($insights['pharmaceutical_demand']) && !isset($insights['pharmaceutical_demand']['error'])) {
        $pharma_data = $forecaster->getPharmaceuticalUsage(null, 12);
        if (count($pharma_data) >= 6) {
            $accuracy_result = $forecaster->validateForecastAccuracy($pharma_data, 3);
            if ($accuracy_result && isset($accuracy_result['accuracy_percentage'])) {
                // Only include if accuracy > 10% (filter out very poor predictions)
                if ($accuracy_result['accuracy_percentage'] > 10) {
                    $accuracy_scores[] = $accuracy_result['accuracy_percentage'];
                }
            }
        }
    }
    
    if (isset($insights['livestock_population']) && !isset($insights['livestock_population']['error'])) {
        $livestock_data = $forecaster->getLivestockTrends('Livestock', 12);
        if (count($livestock_data) >= 6) {
            $accuracy_result = $forecaster->validateForecastAccuracy($livestock_data, 3);
            if ($accuracy_result && isset($accuracy_result['accuracy_percentage'])) {
                // Only include if accuracy > 10%
                if ($accuracy_result['accuracy_percentage'] > 10) {
                    $accuracy_scores[] = $accuracy_result['accuracy_percentage'];
                }
            }
        }
    }
    
    if (isset($insights['poultry_population']) && !isset($insights['poultry_population']['error'])) {
        $poultry_data = $forecaster->getLivestockTrends('Poultry', 12);
        if (count($poultry_data) >= 6) {
            $accuracy_result = $forecaster->validateForecastAccuracy($poultry_data, 3);
            if ($accuracy_result && isset($accuracy_result['accuracy_percentage'])) {
                // Only include if accuracy > 10%
                if ($accuracy_result['accuracy_percentage'] > 10) {
                    $accuracy_scores[] = $accuracy_result['accuracy_percentage'];
                }
            }
        }
    }
    
    // Calculate average accuracy from valid scores only
    if (!empty($accuracy_scores)) {
        $overall_accuracy = round(array_sum($accuracy_scores) / count($accuracy_scores), 1) . '%';
    }

    // 11. Summary metrics for dashboard cards
    $summary_metrics = [
        'forecast_accuracy' => $overall_accuracy,
        'critical_alerts_count' => is_array($critical_alerts) ? count(array_filter($critical_alerts, function($a){ return isset($a['level']) && $a['level'] === 'critical'; })) : 0,
        'total_data_points' => is_array($data_points_info) ? array_sum(array_map(function($i){ return $i['count'] ?? 0; }, $data_points_info)) : 0,
        'data_quality_score' => 0,
        'accuracy_breakdown' => $accuracy_scores
    ];

    if (ob_get_length()) { ob_clean(); }
    echo json_encode([
        'success' => true,
        'insights' => $insights,
        'summary_metrics' => $summary_metrics,
        'generated_at' => date('Y-m-d H:i:s'),
        'model' => 'ARIMA(1,1,1)',
        'data_quality' => [
            'pharmaceutical_data' => !isset($insights['pharmaceutical_demand']['error']),
            'livestock_data' => !isset($insights['livestock_population']['error']),
            'poultry_data' => !isset($insights['poultry_population']['error']),
            'transaction_data' => !isset($insights['transaction_volume']['error'])
        ]
    ]);
    if (ob_get_level()) { ob_end_flush(); }
    
} catch (Exception $e) {
    http_response_code(200);
    if (ob_get_length()) { ob_clean(); }
    echo json_encode([
        'success' => false,
        'error' => 'Failed to generate insights',
        'message' => $e->getMessage()
    ]);
    if (ob_get_level()) { ob_end_flush(); }
}
?>
