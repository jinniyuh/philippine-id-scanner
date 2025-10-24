<?php
session_start();
include 'includes/conn.php';
include 'includes/session_validator.php';
include 'includes/arima_forecaster.php';

// Validate session and ensure user is staff
requireActiveSession($conn, 'staff');

$userId = $_SESSION["user_id"];
$queryUser = "SELECT * FROM users WHERE user_id = '$userId'";
$resultUser = mysqli_query($conn, $queryUser);
if ($resultUser && mysqli_num_rows($resultUser) > 0) {
    $user = mysqli_fetch_assoc($resultUser);
    $staffName = isset($user['name']) ? $user['name'] : 'Staff Name';
} else {
    $staffName = "Staff Name";
}

$forecaster = new VeterinaryForecaster($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ML Insights - Bago City Veterinary Office</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #6c63ff;
            font-family: Arial, sans-serif;
        }
        .container-fluid {
            padding-left: 0;
            padding-right: 0;
            overflow-x: hidden;
        }
        .main-content {
            background: white;
            margin: 20px;
            margin-left: 312px;
            padding: 0 25px 25px 25px;
            border-radius: 10px;
            min-height: 600px;
            height: calc(100vh - 40px);
            overflow-y: auto;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .wrapper {
            display: flex;
            align-items: flex-start;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: calc(100% + 50px);
            min-height: 80px;
            margin: 0 -25px 0 -25px;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            padding: 10px 25px 0 25px;
            position: sticky;
            top: 0;
            background: white;
            z-index: 1;
        }
        .admin-header h2 {
            margin: 0;
            font-weight: bold;
        }
        .admin-profile {
            display: flex;
            align-items: center;
        }
        .admin-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .insight-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .insight-card:hover {
            transform: translateY(-2px);
        }
        .trend-indicator {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .trend-up {
            background-color: #d4edda;
            color: #155724;
        }
        .trend-down {
            background-color: #f8d7da;
            color: #721c24;
        }
        .trend-stable {
            background-color: #fff3cd;
            color: #856404;
        }
        .metric-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            padding: 20px 25px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .metric-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .metric-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s;
        }
        
        .metric-card:hover::after {
            left: 100%;
        }
        
        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: rgba(255, 255, 255, 0.3);
        }
        
        .metric-card .metric-icon {
            font-size: 2rem;
            opacity: 0.8;
            margin-bottom: 10px;
        }
        
        .metric-card .metric-title {
            font-size: 0.8rem;
            font-weight: 500;
            opacity: 0.9;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .metric-card .metric-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 3px;
            line-height: 1;
        }
        
        .metric-card .metric-detail {
            font-size: 0.75rem;
            opacity: 0.8;
            font-weight: 400;
        }
        
        .metric-card .metric-subtitle {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 5px;
        }
        
        /* Individual card color schemes */
        .metric-card:nth-child(1) {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .metric-card:nth-child(2) {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .metric-card:nth-child(3) {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .metric-card:nth-child(4) {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        
        /* Loading animation for metric cards */
        .metric-card.loading {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .metric-card {
                margin-bottom: 15px;
                padding: 15px 12px;
            }
            
            .metric-card .metric-icon {
                font-size: 1.5rem;
                margin-bottom: 8px;
            }
            
            .metric-card .metric-value {
                font-size: 1.4rem;
            }
            
            .metric-card .metric-title {
                font-size: 0.7rem;
            }
            
            .metric-card .metric-detail {
                font-size: 0.7rem;
            }
        }
        .loading-spinner {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }
        .alert-item {
            padding: 10px;
            margin: 5px 0;
            border-radius: 8px;
            border-left: 4px solid #007bff;
            background-color: #f8f9fa;
        }
        .alert-urgent {
            border-left-color: #dc3545;
            background-color: #f8d7da;
        }
        .alert-warning {
            border-left-color: #ffc107;
            background-color: #fff3cd;
        }
        .alert-info {
            border-left-color: #17a2b8;
            background-color: #d1ecf1;
        }
        .accuracy-value {
            font-size: 1.5em;
            font-weight: bold;
            display: block;
        }
        .accuracy-detail {
            font-size: 0.8em;
            opacity: 0.8;
        }
        .alert-count {
            font-size: 1.5em;
            font-weight: bold;
            display: block;
        }
        .alert-detail {
            font-size: 0.8em;
            opacity: 0.8;
        }
        .data-count {
            font-size: 1.5em;
            font-weight: bold;
            display: block;
        }
        .data-detail {
            font-size: 0.8em;
            opacity: 0.8;
        }
        .alert-sm {
            padding: 0.5rem 0.75rem;
            font-size: 0.8rem;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="wrapper">
            <!-- Sidebar -->
            <div class="sidebar">
                <?php include 'includes/staff_sidebar.php'; ?>
            </div>
            
            <!-- Main Content -->
            <div class="main-content">
                <div class="admin-header">
                    <h2><i class="fas fa-brain me-2"></i>ML Insights & Analytics</h2>
                    <div class="admin-profile">
                        <img src="assets/default-avatar.png" alt="Staff Profile">
                        <div>
                            <div><?php echo $staffName; ?></div>
                        </div>
                    </div>
                </div>

                <!-- Model Information -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card insight-card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>ARIMA Forecasting Model</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <h4 class="text-primary">ARIMA(1,1,1)</h4>
                                            <small class="text-muted">AutoRegressive Integrated Moving Average</small>
                                        </div>
                                    </div>
                                    <div class="col-md-9">
                                        <p><strong>Model Components:</strong></p>
                                        <ul>
                                            <li><strong>AR(1):</strong> AutoRegressive component using 1 lag</li>
                                            <li><strong>I(1):</strong> First-order differencing for stationarity</li>
                                            <li><strong>MA(1):</strong> Moving Average component using 1 lag</li>
                                        </ul>
                                        <p><strong>Use Cases:</strong> Pharmaceutical demand forecasting, livestock population trends, transaction volume prediction</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Key Metrics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-icon">
                                <i class="fas fa-bullseye"></i>
                            </div>
                            <div class="metric-title">Forecast Accuracy</div>
                            <div class="metric-value" id="accuracy-metric">--</div>
                            <div class="metric-detail">Model Reliability</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="metric-title">Critical Alerts</div>
                            <div class="metric-value" id="critical-alerts">--</div>
                            <div class="metric-detail">Urgent Issues</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="metric-title">Trend Direction</div>
                            <div class="metric-value" id="trend-direction">--</div>
                            <div class="metric-detail">Overall Movement</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-icon">
                                <i class="fas fa-database"></i>
                            </div>
                            <div class="metric-title">Data Points</div>
                            <div class="metric-value" id="data-points">--</div>
                            <div class="metric-detail">Training Records</div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card insight-card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-pills me-2"></i>Pharmaceutical Demand Forecast</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="pharmaChart"></canvas>
                                </div>
                                <div id="pharma-insights"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card insight-card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-cow me-2"></i>Livestock Population Forecast</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="livestockChart"></canvas>
                                </div>
                                <div id="livestock-insights"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card insight-card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chicken me-2"></i>Poultry Population Forecast</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="poultryChart"></canvas>
                                </div>
                                <div id="poultry-insights"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card insight-card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Transaction Volume Forecast</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="transactionChart"></canvas>
                                </div>
                                <div id="transaction-insights"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seasonal Analysis -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card insight-card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Seasonal Trends Analysis</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="seasonalChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card insight-card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i>AI Recommendations</h5>
                            </div>
                            <div class="card-body" id="recommendations-container">
                                <div class="loading-spinner">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Predictions -->
                <div class="row">
                    <div class="col-12">
                        <div class="card insight-card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-exclamation-circle me-2"></i>Low Stock Predictions</h5>
                            </div>
                            <div class="card-body" id="low-stock-container">
                                <div class="loading-spinner">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global variables for charts
        let pharmaChart, livestockChart, poultryChart, transactionChart, seasonalChart;
        
        // Load ML insights
        async function loadMLInsights() {
            try {
                console.log('Loading ML insights...');
                
                // Add loading state to metric cards
                const metricCards = document.querySelectorAll('.metric-card');
                metricCards.forEach(card => {
                    card.classList.add('loading');
                    const valueElement = card.querySelector('.metric-value');
                    if (valueElement) {
                        valueElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    }
                });
                
                const response = await fetch('get_ml_insights_enhanced.php');
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log('API Response:', data);
                
                if (data.success) {
                    // Remove loading state
                    const metricCards = document.querySelectorAll('.metric-card');
                    metricCards.forEach(card => {
                        card.classList.remove('loading');
                    });
                    
                    updateMetrics(data.insights, data.summary_metrics);
                    createCharts(data.insights);
                    updateRecommendations(data.insights.recommendations);
                    updateLowStockPredictions(data.insights.low_stock_alerts);
                    updateCriticalAlerts(data.insights.critical_alerts);
                    updateDataPointsInfo(data.insights.data_points_info);
                    console.log('ML insights loaded successfully');
                } else {
                    // Remove loading state on error
                    const metricCards = document.querySelectorAll('.metric-card');
                    metricCards.forEach(card => {
                        card.classList.remove('loading');
                    });
                    
                    console.error('Failed to load insights:', data.error || data.message);
                    showError('Failed to load ML insights: ' + (data.error || data.message));
                }
            } catch (error) {
                // Remove loading state on error
                const metricCards = document.querySelectorAll('.metric-card');
                metricCards.forEach(card => {
                    card.classList.remove('loading');
                });
                
                console.error('Error loading ML insights:', error);
                showError('Error loading ML insights: ' + error.message);
            }
        }
        
        function showError(message) {
            // Show error in the first chart container
            const container = document.querySelector('.chart-container');
            if (container) {
                container.innerHTML = `
                    <div class="alert alert-danger">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>ML Insights Error</h5>
                        <p>${message}</p>
                        <button class="btn btn-primary" onclick="loadMLInsights()">
                            <i class="fas fa-redo me-1"></i>Retry
                        </button>
                    </div>
                `;
            } else {
                // Fallback: show error in console and try to show in any available container
                console.error('ML Insights Error:', message);
                const fallbackContainer = document.querySelector('.main-content');
                if (fallbackContainer) {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'alert alert-danger mt-3';
                    errorDiv.innerHTML = `
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>ML Insights Error</h5>
                        <p>${message}</p>
                        <button class="btn btn-primary" onclick="loadMLInsights()">
                            <i class="fas fa-redo me-1"></i>Retry
                        </button>
                    `;
                    fallbackContainer.insertBefore(errorDiv, fallbackContainer.firstChild);
                }
            }
        }
        
        function updateMetrics(insights, summaryMetrics) {
            // Update key metrics with enhanced data
            let criticalCount = 0;
            let trendDirection = 'STABLE';
            let trendEmoji = '➖';
            let totalDataPoints = 0;
            let forecastAccuracy = 'N/A';
            
            // Use summary metrics if available
            if (summaryMetrics) {
                criticalCount = summaryMetrics.critical_alerts_count || 0;
                totalDataPoints = summaryMetrics.total_data_points || 0;
                forecastAccuracy = summaryMetrics.forecast_accuracy || 'N/A';
            } else {
                // Fallback to calculating from insights
                if (insights.low_stock_alerts) {
                    criticalCount = insights.low_stock_alerts.critical_count;
                }
                
                if (insights.data_points_info) {
                    totalDataPoints = Object.values(insights.data_points_info).reduce((sum, info) => sum + (info.count || 0), 0);
                }
                
                if (insights.overall_accuracy) {
                    forecastAccuracy = insights.overall_accuracy + '%';
                }
            }
            
            // Determine overall trend direction from all forecasts
            let upCount = 0, downCount = 0, stableCount = 0;
            
            if (insights.pharmaceutical_demand) {
                if (insights.pharmaceutical_demand.trend === 'increasing') upCount++;
                else if (insights.pharmaceutical_demand.trend === 'decreasing') downCount++;
                else stableCount++;
            }
            
            if (insights.livestock_population) {
                if (insights.livestock_population.trend === 'growing') upCount++;
                else if (insights.livestock_population.trend === 'declining') downCount++;
                else stableCount++;
            }
            
            if (insights.poultry_population) {
                if (insights.poultry_population.trend === 'growing') upCount++;
                else if (insights.poultry_population.trend === 'declining') downCount++;
                else stableCount++;
            }
            
            if (insights.transaction_volume) {
                if (insights.transaction_volume.trend === 'increasing') upCount++;
                else if (insights.transaction_volume.trend === 'decreasing') downCount++;
                else stableCount++;
            }
            
            // Determine overall trend
            if (upCount > downCount && upCount > stableCount) {
                trendDirection = 'UP';
                trendEmoji = '📈';
            } else if (downCount > upCount && downCount > stableCount) {
                trendDirection = 'DOWN';
                trendEmoji = '📉';
            } else {
                trendDirection = 'STABLE';
                trendEmoji = '➖';
            }
            
            // Update the metric cards with enhanced information
            document.getElementById('critical-alerts').innerHTML = criticalCount;
            document.getElementById('trend-direction').innerHTML = `${trendEmoji} ${trendDirection}`;
            document.getElementById('data-points').innerHTML = totalDataPoints;
            
            // Update accuracy with detailed information
            const accuracyElement = document.getElementById('accuracy-metric');
            if (forecastAccuracy !== 'N/A') {
                accuracyElement.innerHTML = forecastAccuracy;
            } else {
                accuracyElement.innerHTML = 'N/A';
            }
        }
        
        function createCharts(insights) {
            try {
                // Pharmaceutical Demand Chart
                if (insights.pharmaceutical_demand && document.getElementById('pharmaChart')) {
                    createForecastChart('pharmaChart', 'Pharmaceutical Demand', 
                        insights.pharmaceutical_demand.historical, 
                        insights.pharmaceutical_demand.forecast,
                        insights.pharmaceutical_demand.trend,
                        insights.pharmaceutical_demand.historical_labels,
                        insights.pharmaceutical_demand.forecast_labels);
                    
                    updateInsights('pharma-insights', insights.pharmaceutical_demand);
                }
                
                // Livestock Population Chart
                if (insights.livestock_population && document.getElementById('livestockChart')) {
                    createForecastChart('livestockChart', 'Livestock Population', 
                        insights.livestock_population.historical, 
                        insights.livestock_population.forecast,
                        insights.livestock_population.trend,
                        insights.livestock_population.historical_labels,
                        insights.livestock_population.forecast_labels);
                    
                    updateInsights('livestock-insights', insights.livestock_population);
                }
                
                // Poultry Population Chart
                if (insights.poultry_population && document.getElementById('poultryChart')) {
                    createForecastChart('poultryChart', 'Poultry Population', 
                        insights.poultry_population.historical, 
                        insights.poultry_population.forecast,
                        insights.poultry_population.trend,
                        insights.poultry_population.historical_labels,
                        insights.poultry_population.forecast_labels);
                    
                    updateInsights('poultry-insights', insights.poultry_population);
                }
                
                // Transaction Volume Chart
                if (insights.transaction_volume && document.getElementById('transactionChart')) {
                    createForecastChart('transactionChart', 'Transaction Volume', 
                        insights.transaction_volume.historical, 
                        insights.transaction_volume.forecast,
                        insights.transaction_volume.trend,
                        insights.transaction_volume.historical_labels,
                        insights.transaction_volume.forecast_labels);
                    
                    updateInsights('transaction-insights', insights.transaction_volume);
                }
                
                // Seasonal Analysis Chart
                if (insights.seasonal_analysis && document.getElementById('seasonalChart')) {
                    createSeasonalChart(insights.seasonal_analysis);
                }
            } catch (error) {
                console.error('Error creating charts:', error);
                showError('Error creating charts: ' + error.message);
            }
        }
        
        function createForecastChart(canvasId, title, historical, forecast, trend, historicalLabels = null, forecastLabels = null) {
            // Check if Chart.js is loaded
            if (typeof Chart === 'undefined') {
                console.error('Chart.js is not loaded');
                return;
            }
            
            const canvas = document.getElementById(canvasId);
            if (!canvas) {
                console.error(`Canvas element with id '${canvasId}' not found`);
                return;
            }
            const ctx = canvas.getContext('2d');
            
            // Use provided labels or fallback to generic ones
            const histLabels = historicalLabels || Array.from({length: historical.length}, (_, i) => `Month ${i + 1}`);
            const foreLabels = forecastLabels || Array.from({length: forecast.length}, (_, i) => `F${i + 1}`);
            
            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [...histLabels, ...foreLabels],
                    datasets: [{
                        label: 'Historical Data',
                        data: historical,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1
                    }, {
                        label: 'Forecast',
                        data: [...Array(historical.length).fill(null), ...forecast],
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderDash: [5, 5],
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: title
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
            
            // Store chart reference
            if (canvasId === 'pharmaChart') pharmaChart = chart;
            else if (canvasId === 'livestockChart') livestockChart = chart;
            else if (canvasId === 'poultryChart') poultryChart = chart;
            else if (canvasId === 'transactionChart') transactionChart = chart;
        }
        
        function createSeasonalChart(seasonalData) {
            // Check if Chart.js is loaded
            if (typeof Chart === 'undefined') {
                console.error('Chart.js is not loaded');
                return;
            }
            
            const canvas = document.getElementById('seasonalChart');
            if (!canvas) {
                console.error('Canvas element with id "seasonalChart" not found');
                return;
            }
            const ctx = canvas.getContext('2d');
            
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                           'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const transactionCounts = Object.values(seasonalData.data).map(d => d.count);
            
            seasonalChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Transaction Count',
                        data: transactionCounts,
                        backgroundColor: 'rgba(54, 162, 235, 0.8)',
                        borderColor: 'rgb(54, 162, 235)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Monthly Transaction Patterns'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
        
        function updateInsights(containerId, data) {
            const container = document.getElementById(containerId);
            const trendClass = data.trend === 'increasing' || data.trend === 'growing' ? 'trend-up' : 
                              data.trend === 'decreasing' || data.trend === 'declining' ? 'trend-down' : 'trend-stable';
            
            // Check if using sample data
            const isSampleData = data.is_sample_data || false;
            const dataSourceIndicator = isSampleData ? 
                '<div class="alert alert-warning alert-sm mt-2 mb-2"><i class="fas fa-exclamation-triangle me-1"></i>Using sample data for demonstration</div>' : '';
            
            container.innerHTML = `
                <div class="mt-3">
                    ${dataSourceIndicator}
                    <div class="d-flex align-items-center mb-2">
                        <span class="trend-indicator ${trendClass} me-2" style="font-size: 1.5em;">
                            ${data.trend_emoji || '📊'}
                        </span>
                        <div>
                            <strong class="d-block">${data.trend_text || data.trend}</strong>
                            <small class="text-muted">Next month forecast: ${data.forecast[0]}</small>
                        </div>
                    </div>
                    ${data.percentage_change !== undefined ? 
                        `<div class="mt-2">
                            <span class="badge ${Math.abs(data.percentage_change) < 5 ? 'bg-secondary' : 
                                               data.percentage_change > 0 ? 'bg-success' : 'bg-danger'}">
                                ${data.percentage_change > 0 ? '+' : ''}${data.percentage_change}% change
                            </span>
                        </div>` : ''
                    }
                </div>
            `;
        }
        
        function updateRecommendations(recommendations) {
            const container = document.getElementById('recommendations-container');
            
            if (!recommendations || recommendations.length === 0) {
                container.innerHTML = '<p class="text-muted">No recommendations at this time.</p>';
                return;
            }
            
            let html = '';
            recommendations.forEach(rec => {
                const alertClass = rec.type === 'urgent' ? 'alert-urgent' : 
                                  rec.type === 'warning' ? 'alert-warning' : 'alert-info';
                
                html += `
                    <div class="alert-item ${alertClass}">
                        <strong>${rec.message}</strong><br>
                        <small>${rec.action}</small>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        function updateLowStockPredictions(lowStockData) {
            const container = document.getElementById('low-stock-container');
            
            if (!lowStockData || !lowStockData.predictions || lowStockData.predictions.length === 0) {
                container.innerHTML = '<p class="text-success">All pharmaceuticals are well-stocked.</p>';
                return;
            }
            
            let html = '<div class="table-responsive"><table class="table table-striped">';
            html += '<thead><tr><th>Pharmaceutical</th><th>Current Stock</th><th>Predicted Demand</th><th>Days Until Stockout</th><th>Stockout Date</th><th>Status</th><th>Action</th></tr></thead><tbody>';
            
            lowStockData.predictions.forEach(item => {
                const statusClass = item.alert_level === 'critical' ? 'text-danger' : 
                                   item.alert_level === 'warning' ? 'text-warning' : 'text-info';
                const statusText = item.alert_level === 'critical' ? 'Critical' : 
                                  item.alert_level === 'warning' ? 'Warning' : 'Monitor';
                const badgeClass = item.alert_level === 'critical' ? 'danger' : 
                                  item.alert_level === 'warning' ? 'warning' : 'info';
                
                html += `
                    <tr>
                        <td><strong>${item.name}</strong></td>
                        <td>${item.current_stock}</td>
                        <td>${item.predicted_demand}</td>
                        <td class="${statusClass}"><strong>${item.days_until_stockout} days</strong></td>
                        <td class="${statusClass}">${item.stockout_date}</td>
                        <td><span class="badge bg-${badgeClass}">${statusText}</span></td>
                        <td><small>Order ${item.recommended_order} units</small></td>
                    </tr>
                `;
            });
            
            html += '</tbody></table></div>';
            container.innerHTML = html;
        }
        
        function updateCriticalAlerts(criticalAlerts) {
            // Update the critical alerts metric with detailed information
            if (criticalAlerts && criticalAlerts.length > 0) {
                const criticalCount = criticalAlerts.filter(alert => alert.level === 'critical').length;
                const warningCount = criticalAlerts.filter(alert => alert.level === 'warning').length;
                
                // Update the metric display
                const alertsElement = document.getElementById('critical-alerts');
                alertsElement.innerHTML = criticalCount;
                
                // Update the detail text
                const detailElement = alertsElement.parentElement.querySelector('.metric-detail');
                if (detailElement) {
                    detailElement.innerHTML = `${criticalCount} Critical${warningCount > 0 ? `, ${warningCount} Warnings` : ''}`;
                }
            }
        }
        
        function updateDataPointsInfo(dataPointsInfo) {
            // Update the data points metric with detailed information
            if (dataPointsInfo) {
                const totalPoints = Object.values(dataPointsInfo).reduce((sum, info) => sum + (info.count || 0), 0);
                const dataTypes = Object.keys(dataPointsInfo).length;
                
                const dataPointsElement = document.getElementById('data-points');
                dataPointsElement.innerHTML = totalPoints;
                
                // Update the detail text
                const detailElement = dataPointsElement.parentElement.querySelector('.metric-detail');
                if (detailElement) {
                    detailElement.innerHTML = `${dataTypes} Data Types`;
                }
            }
        }
        
        // Load insights when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Add a small delay to ensure all DOM elements are ready
            setTimeout(function() {
                loadMLInsights();
            }, 100);
            
            // Refresh insights every 5 minutes
            setInterval(loadMLInsights, 300000);
        });
    </script>
</body>
</html>
