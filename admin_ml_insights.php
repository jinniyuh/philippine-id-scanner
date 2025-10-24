<?php
session_start();
include 'includes/conn.php';
include 'includes/arima_forecaster.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
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
            z-index: 10;
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
                <?php include 'includes/admin_sidebar.php'; ?>
            </div>
            
            <!-- Main Content -->
            <div class="main-content">
                <div class="admin-header">
                    <h2><i class="fas fa-brain me-2"></i>ML Insights & Analytics</h2>
                    <div class="admin-profile">
                        <img src="assets/default-avatar.png" alt="Admin Profile">
                        <div>
                            <div><?php echo $_SESSION['name']; ?></div>
                        </div>
                    </div>
                </div>

                <!-- 
                Model Information
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
                -->

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
                                <!-- Medicine Selection -->
                                <div class="mb-3">
                                    <label for="medicineSelect" class="form-label"><i class="fas fa-search me-1"></i>Select Medicine for Forecasting:</label>
                                    <select class="form-select" id="medicineSelect" onchange="loadPharmaceuticalForecast()">
                                        <option value="">-- Select Medicine First --</option>
                                    </select>
                                </div>
                                
                                <!-- Forecast Content -->
                                <div id="pharma-forecast-content">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Please select a medicine from the dropdown above to view demand forecasting.
                                    </div>
                                </div>
                                
                                <div class="chart-container" id="pharma-chart-container" style="display: none;">
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
                                <!-- Species Selection for Livestock -->
                                <div class="mb-3">
                                    <label for="livestockSpeciesSelect" class="form-label"><i class="fas fa-cow me-1"></i>Select Species for Livestock Forecasting:</label>
                                    <select class="form-select" id="livestockSpeciesSelect" onchange="loadLivestockForecast()">
                                        <option value="">-- Select Species First --</option>
                                        <option value="Cattle">Cattle</option>
                                        <option value="Water Buffalo">Water Buffalo</option>
                                        <option value="Goat">Goat</option>
                                        <option value="Sheep">Sheep</option>
                                        <option value="Swine">Swine</option>
                                        <option value="Horse">Horse</option>
                                        <option value="Donkey">Donkey</option>
                                    </select>
                                </div>
                                
                                <!-- Forecast Content -->
                                <div id="livestock-forecast-content">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Please select a species from the dropdown above to view livestock population forecasting.
                                    </div>
                                </div>
                                
                                <div class="chart-container" id="livestock-chart-container" style="display: none;">
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
                                <!-- Species Selection for Poultry -->
                                <div class="mb-3">
                                    <label for="poultrySpeciesSelect" class="form-label"><i class="fas fa-chicken me-1"></i>Select Species for Poultry Forecasting:</label>
                                    <select class="form-select" id="poultrySpeciesSelect" onchange="loadPoultryForecast()">
                                        <option value="">-- Select Species First --</option>
                                        <option value="Chicken">Chicken</option>
                                        <option value="Duck">Duck</option>
                                        <option value="Goose">Goose</option>
                                        <option value="Turkey">Turkey</option>
                                        <option value="Quail">Quail</option>
                                        <option value="Guinea Fowl">Guinea Fowl</option>
                                    </select>
                                </div>
                                
                                <!-- Forecast Content -->
                                <div id="poultry-forecast-content">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Please select a species from the dropdown above to view poultry population forecasting.
                                    </div>
                                </div>
                                
                                <div class="chart-container" id="poultry-chart-container" style="display: none;">
                                    <canvas id="poultryChart"></canvas>
                                </div>
                                <div id="poultry-insights"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
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

                <!-- Seasonal Analysis -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card insight-card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Seasonal Trends Analysis</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="seasonalChart"></canvas>
                                </div>
                                <div id="seasonal-explanation" class="mt-3" style="display: none;">
                                    <div class="alert alert-info">
                                        <h6><i class="fas fa-info-circle me-2"></i>Seasonal Analysis</h6>
                                        <div id="seasonal-insights"></div>
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
                    loadPharmaceuticals();
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
        
        // Load pharmaceuticals for dropdown
        async function loadPharmaceuticals() {
            try {
                const response = await fetch('get_pharmaceuticals.php');
                const data = await response.json();
                
                if (data.success) {
                    const select = document.getElementById('medicineSelect');
                    select.innerHTML = '<option value="">-- Select Medicine First --</option>';
                    
                    data.pharmaceuticals.forEach(pharma => {
                        const option = document.createElement('option');
                        option.value = pharma.pharma_id;
                        option.textContent = `${pharma.name} (${pharma.category}) - Stock: ${pharma.stock}`;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading pharmaceuticals:', error);
            }
        }
        
        // Load pharmaceutical forecast for selected medicine
        async function loadPharmaceuticalForecast() {
            const medicineId = document.getElementById('medicineSelect').value;
            const forecastContent = document.getElementById('pharma-forecast-content');
            const chartContainer = document.getElementById('pharma-chart-container');
            
            if (!medicineId) {
                forecastContent.innerHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Please select a medicine from the dropdown above to view demand forecasting.
                    </div>
                `;
                chartContainer.style.display = 'none';
                // Hide seasonal analysis
                document.getElementById('seasonal-explanation').style.display = 'none';
                return;
            }
            
            try {
                forecastContent.innerHTML = `
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading forecast...</span>
                        </div>
                        <p class="mt-2">Generating demand forecast and seasonal analysis...</p>
                    </div>
                `;
                
                const response = await fetch(`get_pharmaceutical_forecast.php?medicine_id=${medicineId}`);
                const data = await response.json();
                
                if (data.success) {
                    // Show chart container
                    chartContainer.style.display = 'block';
                    
                    // Create forecast chart
                    createPharmaceuticalForecastChart(data.forecast);
                    
                    // Update forecast content with insights
                    updatePharmaceuticalForecastContent(data.forecast, data.medicine_info);
                    
                    // Show seasonal analysis
                    showSeasonalAnalysis(data.forecast, 'pharmaceutical', data.medicine_info.name);
                } else {
                    forecastContent.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${data.message || 'Unable to generate forecast for this medicine.'}
                        </div>
                    `;
                    chartContainer.style.display = 'none';
                    document.getElementById('seasonal-explanation').style.display = 'none';
                }
            } catch (error) {
                console.error('Error loading pharmaceutical forecast:', error);
                forecastContent.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error loading forecast: ${error.message}
                    </div>
                `;
                chartContainer.style.display = 'none';
                document.getElementById('seasonal-explanation').style.display = 'none';
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
                // Pharmaceutical Demand Chart - Skip this as it's now handled by medicine selection
                // if (insights.pharmaceutical_demand && document.getElementById('pharmaChart')) {
                //     createForecastChart('pharmaChart', 'Pharmaceutical Demand', 
                //         insights.pharmaceutical_demand.historical, 
                //         insights.pharmaceutical_demand.forecast,
                //         insights.pharmaceutical_demand.trend,
                //         insights.pharmaceutical_demand.historical_labels,
                //         insights.pharmaceutical_demand.forecast_labels);
                //     
                //     updateInsights('pharma-insights', insights.pharmaceutical_demand);
                // }
                
                // Livestock Population Chart - Skip this as it's now handled by medicine selection
                // if (insights.livestock_population && document.getElementById('livestockChart')) {
                //     createForecastChart('livestockChart', 'Livestock Population', 
                //         insights.livestock_population.historical, 
                //         insights.livestock_population.forecast,
                //         insights.livestock_population.trend,
                //         insights.livestock_population.historical_labels,
                //         insights.livestock_population.forecast_labels);
                //     
                //     updateInsights('livestock-insights', insights.livestock_population);
                // }
                
                // Poultry Population Chart - Skip this as it's now handled by medicine selection
                // if (insights.poultry_population && document.getElementById('poultryChart')) {
                //     createForecastChart('poultryChart', 'Poultry Population', 
                //         insights.poultry_population.historical, 
                //         insights.poultry_population.forecast,
                //         insights.poultry_population.trend,
                //         insights.poultry_population.historical_labels,
                //         insights.poultry_population.forecast_labels);
                //     
                //     updateInsights('poultry-insights', insights.poultry_population);
                // }
                
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
        
        // Show seasonal analysis for selected item
        function showSeasonalAnalysis(forecastData, type, itemName) {
            const explanationDiv = document.getElementById('seasonal-explanation');
            const insightsDiv = document.getElementById('seasonal-insights');
            
            // Show the container
            explanationDiv.style.display = 'block';
            
            // Analyze seasonal patterns
            const historical = forecastData.historical || [];
            const forecast = forecastData.forecast || [];
            
            if (historical.length < 6) {
                insightsDiv.innerHTML = `
                    <p class="mb-0"><strong>${itemName}</strong> - Insufficient data for detailed seasonal analysis. Need at least 6 months of historical data.</p>
                `;
                return;
            }
            
            // Calculate simple statistics
            const avgHistorical = historical.reduce((a, b) => a + b, 0) / historical.length;
            const avgForecast = forecast.reduce((a, b) => a + b, 0) / forecast.length;
            const changePercent = ((avgForecast - avgHistorical) / avgHistorical * 100).toFixed(1);
            
            // Find highest and lowest months
            const maxValue = Math.max(...historical);
            const minValue = Math.min(...historical);
            const maxMonth = historical.indexOf(maxValue);
            const minMonth = historical.indexOf(minValue);
            const variation = ((maxValue - minValue) / avgHistorical * 100).toFixed(1);
            
            // Determine trend
            let trendIcon, trendText, trendClass;
            if (Math.abs(changePercent) < 5) {
                trendIcon = '➖';
                trendText = 'stable';
                trendClass = 'text-info';
            } else if (changePercent > 0) {
                trendIcon = '📈';
                trendText = 'increasing';
                trendClass = 'text-success';
            } else {
                trendIcon = '📉';
                trendText = 'decreasing';
                trendClass = 'text-danger';
            }
            
            // Generate type-specific insights
            let typeSpecificInsight = '';
            if (type === 'pharmaceutical') {
                typeSpecificInsight = `
                    <p><strong>Medicine Usage Pattern:</strong></p>
                    <ul class="mb-2">
                        <li>Average monthly demand: <strong>${avgHistorical.toFixed(1)} units</strong></li>
                        <li>Peak demand month: <strong>Month ${maxMonth + 1}</strong> (${maxValue} units)</li>
                        <li>Lowest demand month: <strong>Month ${minMonth + 1}</strong> (${minValue} units)</li>
                        <li>Demand variability: <strong>${variation}%</strong></li>
                    </ul>
                    <p><strong>Forecast:</strong> Demand is expected to be <span class="${trendClass}"><strong>${trendIcon} ${trendText}</strong></span> 
                    (${changePercent > 0 ? '+' : ''}${changePercent}%) over the next 3 months.</p>
                `;
            } else if (type === 'livestock') {
                typeSpecificInsight = `
                    <p><strong>Population Growth Pattern:</strong></p>
                    <ul class="mb-2">
                        <li>Average population: <strong>${avgHistorical.toFixed(0)} heads</strong></li>
                        <li>Peak population month: <strong>Month ${maxMonth + 1}</strong> (${maxValue} heads)</li>
                        <li>Lowest population month: <strong>Month ${minMonth + 1}</strong> (${minValue} heads)</li>
                        <li>Population fluctuation: <strong>${variation}%</strong></li>
                    </ul>
                    <p><strong>Forecast:</strong> Population is expected to be <span class="${trendClass}"><strong>${trendIcon} ${trendText}</strong></span> 
                    (${changePercent > 0 ? '+' : ''}${changePercent}%) over the next 3 months.</p>
                    <p class="mb-0"><small class="text-muted"><i class="fas fa-info-circle me-1"></i>
                    Livestock populations typically grow steadily with minimal seasonal variation.</small></p>
                `;
            } else if (type === 'poultry') {
                typeSpecificInsight = `
                    <p><strong>Population Growth Pattern:</strong></p>
                    <ul class="mb-2">
                        <li>Average population: <strong>${avgHistorical.toFixed(0)} birds</strong></li>
                        <li>Peak season: <strong>Month ${maxMonth + 1}</strong> (${maxValue} birds)</li>
                        <li>Low season: <strong>Month ${minMonth + 1}</strong> (${minValue} birds)</li>
                        <li>Seasonal variation: <strong>${variation}%</strong></li>
                    </ul>
                    <p><strong>Forecast:</strong> Population is expected to be <span class="${trendClass}"><strong>${trendIcon} ${trendText}</strong></span> 
                    (${changePercent > 0 ? '+' : ''}${changePercent}%) over the next 3 months.</p>
                    <p class="mb-0"><small class="text-muted"><i class="fas fa-calendar-alt me-1"></i>
                    Poultry populations often show seasonal patterns due to breeding cycles and market demand (holidays, festivals).</small></p>
                `;
            }
            
            insightsDiv.innerHTML = `
                <h6 class="mb-2"><i class="fas fa-chart-line me-2"></i>Seasonal Analysis: ${itemName}</h6>
                ${typeSpecificInsight}
            `;
            
            // Update seasonal chart
            createSeasonalChart({
                labels: [...(forecastData.historical_labels || []), ...(forecastData.forecast_labels || [])],
                historical: historical,
                forecast: forecast,
                title: `${itemName} - Seasonal Trend`
            });
            
            // Update AI recommendations based on seasonal trends
            updateSeasonalRecommendations(type, itemName, {
                avgHistorical: avgHistorical,
                avgForecast: avgForecast,
                changePercent: changePercent,
                maxMonth: maxMonth,
                minMonth: minMonth,
                variation: variation,
                trend: trendText,
                trendClass: trendClass
            });
        }
        
        function createSeasonalChart(seasonalData) {
            console.log('Creating seasonal chart with data:', seasonalData);
            
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
            
            // Destroy existing chart if it exists
            if (seasonalChart) {
                seasonalChart.destroy();
            }
            
            const ctx = canvas.getContext('2d');
            
            // Check if this is forecast data (has historical and forecast arrays) or transaction data
            if (seasonalData.historical && seasonalData.forecast) {
                // This is forecast data from medicine/livestock/poultry selection
                const allLabels = seasonalData.labels || [];
                const historicalLength = seasonalData.historical.length;
                
                // Prepare data arrays with null values for proper display
                const historicalData = [...seasonalData.historical, ...Array(seasonalData.forecast.length).fill(null)];
                const forecastData = [...Array(historicalLength).fill(null), ...seasonalData.forecast];
                
                seasonalChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: allLabels,
                        datasets: [{
                            label: 'Historical',
                            data: historicalData,
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            tension: 0.1,
                            fill: false
                        }, {
                            label: 'Forecast',
                            data: forecastData,
                            borderColor: 'rgb(255, 99, 132)',
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            borderDash: [5, 5],
                            tension: 0.1,
                            fill: false
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: seasonalData.title || 'Seasonal Trend Analysis'
                            },
                            legend: {
                                display: true,
                                position: 'top'
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Value'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Time Period'
                                }
                            }
                        }
                    }
                });
            } else {
                // This is old transaction data format
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                               'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                
                // Map data to months (data keys are month numbers 1-12)
                const transactionCounts = [];
                for (let i = 1; i <= 12; i++) {
                    if (seasonalData.data && seasonalData.data[i]) {
                        transactionCounts.push(seasonalData.data[i].count || 0);
                    } else {
                        transactionCounts.push(0);
                    }
                }
                
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
                            },
                            legend: {
                                display: true,
                                position: 'top'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Number of Transactions'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Month'
                                }
                            }
                        }
                    }
                });
                
                // Generate and display seasonal insights for transactions
                generateSeasonalInsights(transactionCounts, months);
            }
        }
        
        function generateSeasonalInsights(transactionCounts, months) {
            const explanationDiv = document.getElementById('seasonal-explanation');
            const insightsDiv = document.getElementById('seasonal-insights');
            
            if (!explanationDiv || !insightsDiv) return;
            
            // Calculate statistics
            const totalTransactions = transactionCounts.reduce((sum, count) => sum + count, 0);
            const averageTransactions = totalTransactions / 12;
            const maxTransactions = Math.max(...transactionCounts);
            const minTransactions = Math.min(...transactionCounts);
            const maxMonthIndex = transactionCounts.indexOf(maxTransactions);
            const minMonthIndex = transactionCounts.indexOf(minTransactions);
            
            // Identify patterns
            const peakMonths = [];
            const lowMonths = [];
            
            transactionCounts.forEach((count, index) => {
                if (count > averageTransactions * 1.2) {
                    peakMonths.push(months[index]);
                } else if (count < averageTransactions * 0.8) {
                    lowMonths.push(months[index]);
                }
            });
            
            // Generate insights
            let insights = [];
            
            // Peak activity analysis with quantitative insights
            if (peakMonths.length > 0) {
                const peakRatio = (maxTransactions / averageTransactions).toFixed(1);
                insights.push(`<strong>Peak Activity:</strong> Highest activity observed in ${peakMonths.join(', ')} with ${maxTransactions} transactions (${peakRatio}x above average).`);
            }
            
            // Low activity analysis with impact assessment
            if (lowMonths.length > 0) {
                const lowRatio = (minTransactions / averageTransactions).toFixed(1);
                insights.push(`<strong>Low Activity:</strong> Reduced activity in ${lowMonths.join(', ')} with ${minTransactions} transactions (${lowRatio}x below average).`);
            }
            
            // Mid-year concentration analysis
            const midYearMonths = ['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'];
            const midYearTotal = midYearMonths.reduce((sum, month) => {
                const index = months.indexOf(month);
                return sum + (transactionCounts[index] || 0);
            }, 0);
            const midYearPercentage = ((midYearTotal / totalTransactions) * 100).toFixed(1);
            
            if (midYearPercentage > 60) {
                insights.push(`<strong>Mid-Year Concentration:</strong> ${midYearPercentage}% of annual transactions occur during April-September, indicating strong seasonal demand patterns.`);
            }
            
            // Seasonal patterns (Philippine seasons)
            const drySeasonMonths = ['Dec', 'Jan', 'Feb', 'Mar', 'Apr', 'May'];
            const wetSeasonMonths = ['Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov'];
            
            const drySeasonAvg = drySeasonMonths.reduce((sum, month) => {
                const index = months.indexOf(month);
                return sum + (transactionCounts[index] || 0);
            }, 0) / 6;
            
            const wetSeasonAvg = wetSeasonMonths.reduce((sum, month) => {
                const index = months.indexOf(month);
                return sum + (transactionCounts[index] || 0);
            }, 0) / 6;
            
            // Seasonal recommendations
            const seasonalData = [
                { season: 'Dry Season', avg: drySeasonAvg, months: drySeasonMonths },
                { season: 'Wet Season', avg: wetSeasonAvg, months: wetSeasonMonths }
            ];
            
            const highestSeason = seasonalData.reduce((max, current) => 
                current.avg > max.avg ? current : max
            );
            
            const lowestSeason = seasonalData.reduce((min, current) => 
                current.avg < min.avg ? current : min
            );
            
            insights.push(`<strong>Seasonal Pattern:</strong> ${highestSeason.season} shows highest activity (${highestSeason.avg.toFixed(1)} avg transactions), while ${lowestSeason.season} shows lowest activity (${lowestSeason.avg.toFixed(1)} avg transactions).`);
            
            // Business recommendations with specific operational guidance
            if (highestSeason.season === 'Dry Season') {
                const drySeasonIncrease = ((highestSeason.avg - lowestSeason.avg) / lowestSeason.avg * 100).toFixed(1);
                insights.push(`<strong>Operational Strategy:</strong> Dry season (Dec-May) shows peak activity with ${drySeasonIncrease}% increase. This aligns with agricultural preparation and breeding cycles.`);
                insights.push(`<strong>Resource Planning:</strong> Pre-position 40-50% more veterinary supplies before December. Schedule additional staff training in November to handle increased demand.`);
                insights.push(`<strong>Inventory Management:</strong> Stock up on breeding-related medicines, vaccines, and livestock supplements. Plan for 3-month supply coverage during peak months.`);
            } else if (highestSeason.season === 'Wet Season') {
                const wetSeasonIncrease = ((highestSeason.avg - lowestSeason.avg) / lowestSeason.avg * 100).toFixed(1);
                insights.push(`<strong>Operational Strategy:</strong> Wet season (Jun-Nov) shows peak activity with ${wetSeasonIncrease}% increase. This indicates higher veterinary needs during rainy months.`);
                insights.push(`<strong>Resource Planning:</strong> Increase staff capacity by 30-40% during June-September. Prepare for disease outbreaks and emergency veterinary services.`);
                insights.push(`<strong>Inventory Management:</strong> Stock up on wet season disease medicines, antibiotics, and emergency supplies. Maintain 2-month buffer stock for critical medicines.`);
            }
            
            // Cost and efficiency recommendations
            const peakToLowRatio = (maxTransactions / minTransactions).toFixed(1);
            if (peakToLowRatio > 3) {
                insights.push(`<strong>Capacity Planning:</strong> Peak months show ${peakToLowRatio}x higher activity than low months. Consider flexible staffing and resource allocation strategies.`);
                insights.push(`<strong>Cost Optimization:</strong> During low-activity months (${lowMonths.join(', ')}), focus on maintenance, training, and inventory auditing to prepare for peak season.`);
            }
            
            // Trend analysis
            const firstHalf = transactionCounts.slice(0, 6).reduce((sum, count) => sum + count, 0);
            const secondHalf = transactionCounts.slice(6, 12).reduce((sum, count) => sum + count, 0);
            
            if (secondHalf > firstHalf * 1.1) {
                const growthRate = ((secondHalf - firstHalf) / firstHalf * 100).toFixed(1);
                insights.push(`<strong>Growth Trend:</strong> Second half of the year shows ${growthRate}% increase in activity, indicating positive growth.`);
                insights.push(`<strong>Predictive Planning:</strong> Based on this growth pattern, expect approximately ${(maxTransactions * 1.1).toFixed(0)} transactions in next year's peak month. Plan resources accordingly.`);
            } else if (firstHalf > secondHalf * 1.1) {
                const declineRate = ((firstHalf - secondHalf) / secondHalf * 100).toFixed(1);
                insights.push(`<strong>Decline Trend:</strong> First half shows ${declineRate}% higher activity than second half, suggesting seasonal decline. Consider strategies to maintain year-round engagement.`);
                insights.push(`<strong>Strategic Response:</strong> Implement off-season programs like preventive care campaigns, farmer training, or equipment maintenance to maintain steady activity.`);
            }
            
            // Agricultural context insights
            if (midYearPercentage > 60) {
                insights.push(`<strong>Agricultural Context:</strong> The ${midYearPercentage}% mid-year concentration aligns with Philippine farming cycles - planting season (Apr-Jun) and harvest preparation (Jul-Sep).`);
                insights.push(`<strong>Service Optimization:</strong> Focus veterinary services on crop-livestock integration during planting season and livestock health monitoring during harvest preparation.`);
            }
            
            // Display insights
            insightsDiv.innerHTML = insights.map(insight => `<p class="mb-2">${insight}</p>`).join('');
            explanationDiv.style.display = 'block';
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
            
            console.log('Updating recommendations:', recommendations);
            
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
        
        function updateSeasonalRecommendations(type, itemName, seasonalData) {
            const container = document.getElementById('recommendations-container');
            const { avgHistorical, avgForecast, changePercent, maxMonth, minMonth, variation, trend, trendClass } = seasonalData;
            
            let recommendations = [];
            
            if (type === 'pharmaceutical') {
                // Medicine-specific recommendations
                if (trend === 'increasing') {
                    recommendations.push({
                        type: 'warning',
                        icon: '📈',
                        title: 'Demand Increasing',
                        message: `${itemName} demand is rising by ${Math.abs(changePercent).toFixed(1)}%`,
                        action: `Increase stock levels by at least ${Math.ceil(Math.abs(changePercent))}% to meet upcoming demand.`
                    });
                    
                    if (variation > 30) {
                        recommendations.push({
                            type: 'info',
                            icon: '📊',
                            title: 'High Variability Detected',
                            message: `Demand varies by ${variation}% between peak and low months`,
                            action: `Keep safety stock of ${Math.ceil(avgHistorical * 0.3)} units to handle fluctuations.`
                        });
                    }
                } else if (trend === 'decreasing') {
                    recommendations.push({
                        type: 'info',
                        icon: '📉',
                        title: 'Demand Declining',
                        message: `${itemName} demand is dropping by ${Math.abs(changePercent).toFixed(1)}%`,
                        action: `Consider reducing next order to avoid overstock. Monitor for ${Math.ceil(avgForecast)} units monthly.`
                    });
                } else {
                    recommendations.push({
                        type: 'success',
                        icon: '✅',
                        title: 'Stable Demand',
                        message: `${itemName} shows stable demand pattern`,
                        action: `Maintain current stock levels around ${Math.ceil(avgHistorical)} units per month.`
                    });
                }
                
                // Peak season recommendation
                recommendations.push({
                    type: 'info',
                    icon: '📅',
                    title: 'Peak Demand Period',
                    message: `Highest demand typically occurs in Month ${maxMonth + 1}`,
                    action: `Prepare extra stock before Month ${maxMonth + 1}. Plan orders 1-2 months in advance.`
                });
                
            } else if (type === 'livestock') {
                // Livestock-specific recommendations
                if (trend === 'increasing') {
                    recommendations.push({
                        type: 'success',
                        icon: '🐄',
                        title: 'Population Growing',
                        message: `${itemName} population increasing by ${Math.abs(changePercent).toFixed(1)}%`,
                        action: `Prepare facilities and resources for ${Math.ceil(avgForecast)} heads. Ensure adequate feed and space.`
                    });
                    
                    recommendations.push({
                        type: 'info',
                        icon: '💉',
                        title: 'Health Management',
                        message: `Growing population requires more veterinary supplies`,
                        action: `Stock up on vaccines and medicines proportional to population growth.`
                    });
                } else if (trend === 'decreasing') {
                    recommendations.push({
                        type: 'warning',
                        icon: '⚠️',
                        title: 'Population Declining',
                        message: `${itemName} numbers decreasing by ${Math.abs(changePercent).toFixed(1)}%`,
                        action: `Investigate reasons: disease, sales, or natural mortality. Monitor health status closely.`
                    });
                } else {
                    recommendations.push({
                        type: 'success',
                        icon: '➖',
                        title: 'Stable Population',
                        message: `${itemName} population remains steady`,
                        action: `Continue current management practices. Maintain around ${Math.ceil(avgHistorical)} heads.`
                    });
                }
                
                // Resource planning
                recommendations.push({
                    type: 'info',
                    icon: '🌾',
                    title: 'Resource Planning',
                    message: `Plan for ${Math.ceil(avgForecast)} heads in next quarter`,
                    action: `Ensure sufficient feed (${Math.ceil(avgForecast * 10)}kg/month) and water supply.`
                });
                
            } else if (type === 'poultry') {
                // Poultry-specific recommendations
                if (trend === 'increasing') {
                    recommendations.push({
                        type: 'success',
                        icon: '🐔',
                        title: 'Flock Growing',
                        message: `${itemName} population rising by ${Math.abs(changePercent).toFixed(1)}%`,
                        action: `Expand housing capacity for ${Math.ceil(avgForecast)} birds. Increase feed supply.`
                    });
                } else if (trend === 'decreasing') {
                    recommendations.push({
                        type: 'warning',
                        icon: '⚠️',
                        title: 'Flock Size Declining',
                        message: `${itemName} numbers dropping by ${Math.abs(changePercent).toFixed(1)}%`,
                        action: `Check for disease outbreaks. Review biosecurity measures. Consider restocking.`
                    });
                } else {
                    recommendations.push({
                        type: 'success',
                        icon: '✅',
                        title: 'Stable Flock',
                        message: `${itemName} population is stable`,
                        action: `Maintain current flock size of ~${Math.ceil(avgHistorical)} birds.`
                    });
                }
                
                // Seasonal breeding recommendation
                if (variation > 15) {
                    recommendations.push({
                        type: 'info',
                        icon: '📅',
                        title: 'Seasonal Pattern Detected',
                        message: `Population varies by ${variation}% - likely breeding cycles`,
                        action: `Peak in Month ${maxMonth + 1}, Low in Month ${minMonth + 1}. Plan breeding and sales accordingly.`
                    });
                }
                
                // Market demand
                recommendations.push({
                    type: 'info',
                    icon: '🏪',
                    title: 'Market Planning',
                    message: `Forecast: ${Math.ceil(avgForecast)} birds for next quarter`,
                    action: `Time sales for festivals/holidays. Stock feed for ${Math.ceil(avgForecast * 0.15)}kg/bird/month.`
                });
            }
            
            // Build HTML
            let html = `<h6 class="mb-3"><i class="fas fa-robot me-2"></i>AI Recommendations: ${itemName}</h6>`;
            
            recommendations.forEach(rec => {
                let alertClass = '';
                if (rec.type === 'urgent') alertClass = 'alert-danger';
                else if (rec.type === 'warning') alertClass = 'alert-warning';
                else if (rec.type === 'success') alertClass = 'alert-success';
                else alertClass = 'alert-info';
                
                html += `
                    <div class="alert ${alertClass} alert-sm mb-2">
                        <div class="d-flex align-items-start">
                            <span style="font-size: 1.5em; margin-right: 10px;">${rec.icon}</span>
                            <div>
                                <strong>${rec.title}</strong><br>
                                <small>${rec.message}</small><br>
                                <small class="text-muted"><i class="fas fa-lightbulb me-1"></i>${rec.action}</small>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        function updateLowStockPredictions(lowStockData) {
            const container = document.getElementById('low-stock-container');
            
            console.log('Updating low stock predictions:', lowStockData);
            
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
        
        // Create pharmaceutical forecast chart
        function createPharmaceuticalForecastChart(forecastData) {
            if (typeof Chart === 'undefined') {
                console.error('Chart.js is not loaded');
                return;
            }
            
            const canvas = document.getElementById('pharmaChart');
            if (!canvas) {
                console.error('Canvas element with id "pharmaChart" not found');
                return;
            }
            
            // Destroy existing chart if it exists
            if (pharmaChart) {
                pharmaChart.destroy();
            }
            
            const ctx = canvas.getContext('2d');
            
            pharmaChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [...forecastData.historical_labels, ...forecastData.forecast_labels],
                    datasets: [{
                        label: 'Historical Demand',
                        data: forecastData.historical,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1,
                        fill: false
                    }, {
                        label: 'Forecasted Demand',
                        data: [...Array(forecastData.historical.length).fill(null), ...forecastData.forecast],
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderDash: [5, 5],
                        tension: 0.1,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: `${forecastData.medicine_name} - Demand Forecast`
                        },
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Demand (Units)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Time Period'
                            }
                        }
                    }
                }
            });
        }
        
        // Update pharmaceutical forecast content with insights
        function updatePharmaceuticalForecastContent(forecastData, medicineInfo) {
            const forecastContent = document.getElementById('pharma-forecast-content');
            const insightsContainer = document.getElementById('pharma-insights');
            
            // Calculate trend analysis
            const currentAvg = forecastData.historical.slice(-3).reduce((a, b) => a + b, 0) / 3;
            const forecastAvg = forecastData.forecast.reduce((a, b) => a + b, 0) / forecastData.forecast.length;
            const percentageChange = currentAvg > 0 ? ((forecastAvg - currentAvg) / currentAvg * 100).toFixed(1) : 0;
            
            let trendClass, trendIcon, trendText, suggestion;
            
            if (Math.abs(percentageChange) < 5) {
                trendClass = 'trend-stable';
                trendIcon = '➖';
                trendText = 'Stable demand expected';
                suggestion = 'Maintain current stock levels. Monitor for any seasonal changes.';
            } else if (percentageChange > 0) {
                trendClass = 'trend-up';
                trendIcon = '📈';
                trendText = `Demand increasing (+${percentageChange}%)`;
                suggestion = 'Consider increasing stock levels. Monitor for potential stockouts.';
            } else {
                trendClass = 'trend-down';
                trendIcon = '📉';
                trendText = `Demand decreasing (${percentageChange}%)`;
                suggestion = 'Review current stock levels. Consider reducing orders to avoid overstock.';
            }
            
            // Stock status analysis
            const currentStock = medicineInfo.stock;
            const avgMonthlyDemand = forecastAvg;
            const monthsOfStock = currentStock / avgMonthlyDemand;
            
            let stockStatus, stockClass, stockSuggestion;
            if (monthsOfStock < 1) {
                stockStatus = 'Critical - Less than 1 month';
                stockClass = 'alert-danger';
                stockSuggestion = 'URGENT: Order immediately to prevent stockout.';
            } else if (monthsOfStock < 2) {
                stockStatus = 'Low - Less than 2 months';
                stockClass = 'alert-warning';
                stockSuggestion = 'Consider ordering soon to maintain adequate stock.';
            } else if (monthsOfStock < 3) {
                stockStatus = 'Moderate - 2-3 months';
                stockClass = 'alert-info';
                stockSuggestion = 'Monitor stock levels and plan for future orders.';
            } else {
                stockStatus = 'Good - More than 3 months';
                stockClass = 'alert-success';
                stockSuggestion = 'Stock levels are adequate. Continue monitoring.';
            }
            
            forecastContent.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-0 bg-light">
                            <div class="card-body">
                                <h6 class="card-title"><i class="fas fa-chart-line me-2"></i>Demand Trend</h6>
                                <div class="d-flex align-items-center mb-2">
                                    <span class="trend-indicator ${trendClass} me-2" style="font-size: 1.5em;">
                                        ${trendIcon}
                                    </span>
                                    <div>
                                        <strong class="d-block">${trendText}</strong>
                                        <small class="text-muted">Next 3 months forecast</small>
                                    </div>
                                </div>
                                <p class="card-text small">${suggestion}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 bg-light">
                            <div class="card-body">
                                <h6 class="card-title"><i class="fas fa-boxes me-2"></i>Stock Analysis</h6>
                                <div class="mb-2">
                                    <strong>Current Stock:</strong> ${currentStock} ${medicineInfo.unit}<br>
                                    <strong>Predicted Monthly Demand:</strong> ${avgMonthlyDemand.toFixed(1)} ${medicineInfo.unit}<br>
                                    <strong>Stock Duration:</strong> ${monthsOfStock.toFixed(1)} months
                                </div>
                                <div class="alert ${stockClass} alert-sm">
                                    <strong>${stockStatus}</strong><br>
                                    <small>${stockSuggestion}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Update insights container
            insightsContainer.innerHTML = `
                <div class="mt-3">
                    <h6><i class="fas fa-lightbulb me-2"></i>Forecast Insights</h6>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-arrow-right me-2 text-primary"></i>Expected demand for next 3 months: ${forecastData.forecast.join(', ')} ${medicineInfo.unit}</li>
                        <li><i class="fas fa-percentage me-2 text-info"></i>Demand change: ${percentageChange > 0 ? '+' : ''}${percentageChange}%</li>
                        <li><i class="fas fa-calendar me-2 text-warning"></i>Based on ${forecastData.historical.length} months of historical data</li>
                        <li><i class="fas fa-chart-bar me-2 text-success"></i>Model accuracy: ${forecastData.accuracy || 'N/A'}%</li>
                    </ul>
                </div>
            `;
        }
        
        // Load livestock forecast for selected species
        async function loadLivestockForecast() {
            const species = document.getElementById('livestockSpeciesSelect').value;
            const forecastContent = document.getElementById('livestock-forecast-content');
            const chartContainer = document.getElementById('livestock-chart-container');
            
            if (!species) {
                forecastContent.innerHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Please select a species from the dropdown above to view livestock population forecasting.
                    </div>
                `;
                chartContainer.style.display = 'none';
                document.getElementById('seasonal-explanation').style.display = 'none';
                return;
            }
            
            try {
                forecastContent.innerHTML = `
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading forecast...</span>
                        </div>
                        <p class="mt-2">Generating ${species} population forecast and seasonal analysis...</p>
                    </div>
                `;
                
                const response = await fetch(`get_livestock_forecast.php?species=${encodeURIComponent(species)}`);
                const data = await response.json();
                
                if (data.success) {
                    // Show chart container
                    chartContainer.style.display = 'block';
                    
                    // Create forecast chart
                    createLivestockForecastChart(data.forecast);
                    
                    // Update forecast content with insights
                    updateLivestockForecastContent(data.forecast, data.species_info);
                    
                    // Show seasonal analysis
                    showSeasonalAnalysis(data.forecast, 'livestock', species);
                } else {
                    forecastContent.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${data.message || 'Unable to generate forecast for this species.'}
                        </div>
                    `;
                    chartContainer.style.display = 'none';
                    document.getElementById('seasonal-explanation').style.display = 'none';
                }
            } catch (error) {
                console.error('Error loading livestock forecast:', error);
                forecastContent.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error loading forecast: ${error.message}
                    </div>
                `;
                chartContainer.style.display = 'none';
                document.getElementById('seasonal-explanation').style.display = 'none';
            }
        }
        
        // Load poultry forecast for selected species
        async function loadPoultryForecast() {
            const species = document.getElementById('poultrySpeciesSelect').value;
            const forecastContent = document.getElementById('poultry-forecast-content');
            const chartContainer = document.getElementById('poultry-chart-container');
            
            if (!species) {
                forecastContent.innerHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Please select a species from the dropdown above to view poultry population forecasting.
                    </div>
                `;
                chartContainer.style.display = 'none';
                document.getElementById('seasonal-explanation').style.display = 'none';
                return;
            }
            
            try {
                forecastContent.innerHTML = `
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading forecast...</span>
                        </div>
                        <p class="mt-2">Generating ${species} population forecast and seasonal analysis...</p>
                    </div>
                `;
                
                const response = await fetch(`get_poultry_forecast.php?species=${encodeURIComponent(species)}`);
                const data = await response.json();
                
                if (data.success) {
                    // Show chart container
                    chartContainer.style.display = 'block';
                    
                    // Create forecast chart
                    createPoultryForecastChart(data.forecast);
                    
                    // Update forecast content with insights
                    updatePoultryForecastContent(data.forecast, data.species_info);
                    
                    // Show seasonal analysis
                    showSeasonalAnalysis(data.forecast, 'poultry', species);
                } else {
                    forecastContent.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${data.message || 'Unable to generate forecast for this species.'}
                        </div>
                    `;
                    chartContainer.style.display = 'none';
                    document.getElementById('seasonal-explanation').style.display = 'none';
                }
            } catch (error) {
                console.error('Error loading poultry forecast:', error);
                forecastContent.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error loading forecast: ${error.message}
                    </div>
                `;
                chartContainer.style.display = 'none';
                document.getElementById('seasonal-explanation').style.display = 'none';
            }
        }
        
        
        // Create livestock forecast chart
        function createLivestockForecastChart(forecastData) {
            if (typeof Chart === 'undefined') {
                console.error('Chart.js is not loaded');
                return;
            }
            
            const canvas = document.getElementById('livestockChart');
            if (!canvas) {
                console.error('Canvas element with id "livestockChart" not found');
                return;
            }
            
            // Destroy existing chart if it exists
            if (livestockChart) {
                livestockChart.destroy();
            }
            
            const ctx = canvas.getContext('2d');
            
            livestockChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [...forecastData.historical_labels, ...forecastData.forecast_labels],
                    datasets: [{
                        label: 'Historical Population',
                        data: forecastData.historical,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1,
                        fill: false
                    }, {
                        label: 'Forecasted Population',
                        data: [...Array(forecastData.historical.length).fill(null), ...forecastData.forecast],
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderDash: [5, 5],
                        tension: 0.1,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: `${forecastData.species_name} - Livestock Population Forecast`
                        },
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Population Count'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Time Period'
                            }
                        }
                    }
                }
            });
        }
        
        // Create poultry forecast chart
        function createPoultryForecastChart(forecastData) {
            if (typeof Chart === 'undefined') {
                console.error('Chart.js is not loaded');
                return;
            }
            
            const canvas = document.getElementById('poultryChart');
            if (!canvas) {
                console.error('Canvas element with id "poultryChart" not found');
                return;
            }
            
            // Destroy existing chart if it exists
            if (poultryChart) {
                poultryChart.destroy();
            }
            
            const ctx = canvas.getContext('2d');
            
            poultryChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [...forecastData.historical_labels, ...forecastData.forecast_labels],
                    datasets: [{
                        label: 'Historical Population',
                        data: forecastData.historical,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1,
                        fill: false
                    }, {
                        label: 'Forecasted Population',
                        data: [...Array(forecastData.historical.length).fill(null), ...forecastData.forecast],
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderDash: [5, 5],
                        tension: 0.1,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: `${forecastData.species_name} - Poultry Population Forecast`
                        },
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Population Count'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Time Period'
                            }
                        }
                    }
                }
            });
        }
        
        // Update livestock forecast content with insights
        function updateLivestockForecastContent(forecastData, speciesInfo) {
            const forecastContent = document.getElementById('livestock-forecast-content');
            const insightsContainer = document.getElementById('livestock-insights');
            
            // Calculate trend analysis
            const currentAvg = forecastData.historical.slice(-3).reduce((a, b) => a + b, 0) / 3;
            const forecastAvg = forecastData.forecast.reduce((a, b) => a + b, 0) / forecastData.forecast.length;
            const percentageChange = currentAvg > 0 ? ((forecastAvg - currentAvg) / currentAvg * 100).toFixed(1) : 0;
            
            let trendClass, trendIcon, trendText, suggestion;
            
            if (Math.abs(percentageChange) < 5) {
                trendClass = 'trend-stable';
                trendIcon = '➖';
                trendText = 'Stable population expected';
                suggestion = `${speciesInfo.species} population is expected to remain stable. Monitor for any seasonal changes.`;
            } else if (percentageChange > 0) {
                trendClass = 'trend-up';
                trendIcon = '📈';
                trendText = `Population growing (+${percentageChange}%)`;
                suggestion = `${speciesInfo.species} population is growing. Consider planning for increased veterinary services and medicine requirements.`;
            } else {
                trendClass = 'trend-down';
                trendIcon = '📉';
                trendText = `Population declining (${percentageChange}%)`;
                suggestion = `${speciesInfo.species} population is declining. Review management practices and health programs.`;
            }
            
            forecastContent.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-0 bg-light">
                            <div class="card-body">
                                <h6 class="card-title"><i class="fas fa-chart-line me-2"></i>Population Trend</h6>
                                <div class="d-flex align-items-center mb-2">
                                    <span class="trend-indicator ${trendClass} me-2" style="font-size: 1.5em;">
                                        ${trendIcon}
                                    </span>
                                    <div>
                                        <strong class="d-block">${trendText}</strong>
                                        <small class="text-muted">Next 3 months forecast</small>
                                    </div>
                                </div>
                                <p class="card-text small">${suggestion}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 bg-light">
                            <div class="card-body">
                                <h6 class="card-title"><i class="fas fa-paw me-2"></i>Species Analysis</h6>
                                <div class="mb-2">
                                    <strong>Species:</strong> ${speciesInfo.species}<br>
                                    <strong>Current Population:</strong> ${speciesInfo.current_population || 'N/A'} animals<br>
                                    <strong>Population Trend:</strong> ${percentageChange > 0 ? 'Growing' : percentageChange < 0 ? 'Declining' : 'Stable'}
                                </div>
                                <div class="alert alert-info alert-sm">
                                    <strong>Recommendation:</strong><br>
                                    <small>${suggestion}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Update insights container
            insightsContainer.innerHTML = `
                <div class="mt-3">
                    <h6><i class="fas fa-lightbulb me-2"></i>Livestock Forecast Insights</h6>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-arrow-right me-2 text-primary"></i>Expected ${speciesInfo.species} population for next 3 months: ${forecastData.forecast.join(', ')} animals</li>
                        <li><i class="fas fa-percentage me-2 text-info"></i>Population change: ${percentageChange > 0 ? '+' : ''}${percentageChange}%</li>
                        <li><i class="fas fa-calendar me-2 text-warning"></i>Based on ${forecastData.historical.length} months of historical data</li>
                        <li><i class="fas fa-chart-bar me-2 text-success"></i>Model accuracy: ${forecastData.accuracy || 'N/A'}%</li>
                    </ul>
                </div>
            `;
        }
        
        // Update poultry forecast content with insights
        function updatePoultryForecastContent(forecastData, speciesInfo) {
            const forecastContent = document.getElementById('poultry-forecast-content');
            const insightsContainer = document.getElementById('poultry-insights');
            
            // Calculate trend analysis
            const currentAvg = forecastData.historical.slice(-3).reduce((a, b) => a + b, 0) / 3;
            const forecastAvg = forecastData.forecast.reduce((a, b) => a + b, 0) / forecastData.forecast.length;
            const percentageChange = currentAvg > 0 ? ((forecastAvg - currentAvg) / currentAvg * 100).toFixed(1) : 0;
            
            let trendClass, trendIcon, trendText, suggestion;
            
            if (Math.abs(percentageChange) < 5) {
                trendClass = 'trend-stable';
                trendIcon = '➖';
                trendText = 'Stable population expected';
                suggestion = `${speciesInfo.species} population is expected to remain stable. Monitor for any seasonal changes.`;
            } else if (percentageChange > 0) {
                trendClass = 'trend-up';
                trendIcon = '📈';
                trendText = `Population growing (+${percentageChange}%)`;
                suggestion = `${speciesInfo.species} population is growing. Consider planning for increased veterinary services and medicine requirements.`;
            } else {
                trendClass = 'trend-down';
                trendIcon = '📉';
                trendText = `Population declining (${percentageChange}%)`;
                suggestion = `${speciesInfo.species} population is declining. Review management practices and health programs.`;
            }
            
            forecastContent.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-0 bg-light">
                            <div class="card-body">
                                <h6 class="card-title"><i class="fas fa-chart-line me-2"></i>Population Trend</h6>
                                <div class="d-flex align-items-center mb-2">
                                    <span class="trend-indicator ${trendClass} me-2" style="font-size: 1.5em;">
                                        ${trendIcon}
                                    </span>
                                    <div>
                                        <strong class="d-block">${trendText}</strong>
                                        <small class="text-muted">Next 3 months forecast</small>
                                    </div>
                                </div>
                                <p class="card-text small">${suggestion}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 bg-light">
                            <div class="card-body">
                                <h6 class="card-title"><i class="fas fa-feather me-2"></i>Species Analysis</h6>
                                <div class="mb-2">
                                    <strong>Species:</strong> ${speciesInfo.species}<br>
                                    <strong>Current Population:</strong> ${speciesInfo.current_population || 'N/A'} birds<br>
                                    <strong>Population Trend:</strong> ${percentageChange > 0 ? 'Growing' : percentageChange < 0 ? 'Declining' : 'Stable'}
                                </div>
                                <div class="alert alert-info alert-sm">
                                    <strong>Recommendation:</strong><br>
                                    <small>${suggestion}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Update insights container
            insightsContainer.innerHTML = `
                <div class="mt-3">
                    <h6><i class="fas fa-lightbulb me-2"></i>Poultry Forecast Insights</h6>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-arrow-right me-2 text-primary"></i>Expected ${speciesInfo.species} population for next 3 months: ${forecastData.forecast.join(', ')} birds</li>
                        <li><i class="fas fa-percentage me-2 text-info"></i>Population change: ${percentageChange > 0 ? '+' : ''}${percentageChange}%</li>
                        <li><i class="fas fa-calendar me-2 text-warning"></i>Based on ${forecastData.historical.length} months of historical data</li>
                        <li><i class="fas fa-chart-bar me-2 text-success"></i>Model accuracy: ${forecastData.accuracy || 'N/A'}%</li>
                    </ul>
                </div>
            `;
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
