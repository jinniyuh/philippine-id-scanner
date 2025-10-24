<?php
session_start();
include 'includes/conn.php';
include 'includes/health_risk_assessor.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$assessor = new HealthRiskAssessor($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Risk Monitoring - Bago City Veterinary Office</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php include 'includes/global_alert_include.php'; ?>
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
        
        /* Responsive adjustments */
        @media (max-width: 1200px) {
            .main-content {
                margin-left: 280px;
                padding: 0 20px 20px 20px;
            }
        }
        
        @media (max-width: 992px) {
            .main-content {
                margin: 10px;
                margin-left: 0;
                padding: 0 15px 15px 15px;
                height: auto;
                min-height: auto;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin: 5px;
                padding: 0 10px 10px 10px;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .h4 {
                font-size: 1.5rem;
            }
            
            .btn-sm {
                font-size: 0.875rem;
                padding: 0.375rem 0.75rem;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                margin: 2px;
                padding: 0 5px 5px 5px;
            }
            
            .card-body {
                padding: 0.75rem;
            }
            
            .h4 {
                font-size: 1.25rem;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
            
            .d-flex.flex-column.flex-md-row > div {
                width: 100%;
            }
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
        .risk-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
            margin-bottom: 20px;
        }
        .risk-card:hover {
            transform: translateY(-2px);
        }
        .clickable-card {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .clickable-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        .symptoms-full {
            word-wrap: break-word;
            white-space: normal;
            line-height: 1.4;
            margin-top: 5px;
        }
        .symptoms-full small {
            display: block;
            max-width: 100%;
            overflow: visible;
        }
        .risk-level-critical {
            border-left: 5px solid #dc3545;
        }
        .risk-level-high {
            border-left: 5px solid #fd7e14;
        }
        .risk-level-medium {
            border-left: 5px solid #ffc107;
        }
        .risk-level-low {
            border-left: 5px solid #28a745;
        }
        .risk-score {
            font-size: 2rem;
            font-weight: bold;
        }
        .risk-critical { color: #dc3545; }
        .risk-high { color: #fd7e14; }
        .risk-medium { color: #ffc107; }
        .risk-low { color: #28a745; }
        
        /* Anomaly Detection Card Styles - Matching Health Risk Cards */
        .anomaly-level-total {
            border-left: 5px solid #17a2b8;
        }
        .anomaly-level-critical {
            border-left: 5px solid #dc3545;
        }
        .anomaly-level-high {
            border-left: 5px solid #fd7e14;
        }
        .anomaly-level-areas {
            border-left: 5px solid #28a745;
        }
        
        .anomaly-total { color: #17a2b8; }
        .anomaly-critical { color: #dc3545; }
        .anomaly-high { color: #fd7e14; }
        .anomaly-areas { color: #28a745; }
        
        /* Symptoms Risk Analysis Card Styles - Matching Health Risk Cards */
        .symptoms-level-critical {
            border-left: 5px solid #dc3545;
        }
        .symptoms-level-high {
            border-left: 5px solid #fd7e14;
        }
        .symptoms-level-medium {
            border-left: 5px solid #17a2b8;
        }
        .symptoms-level-low {
            border-left: 5px solid #28a745;
        }
        
        .symptoms-critical { color: #dc3545; }
        .symptoms-high { color: #fd7e14; }
        .symptoms-medium { color: #17a2b8; }
        .symptoms-low { color: #28a745; }
        .loading-spinner {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
        }
        .animal-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .animal-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .risk-badge {
            font-size: 0.8rem;
            padding: 4px 8px;
            border-radius: 12px;
            font-weight: bold;
        }
        .btn-assess {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        .btn-assess:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            color: white;
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
                    <h2><i class="fas fa-heartbeat me-2"></i>Health Risk Monitoring</h2>
                    <div class="admin-profile">
                        <img src="assets/default-avatar.png" alt="Admin Profile">
                        <div>
                            <div><?php echo $_SESSION['name']; ?></div>
                        </div>
                    </div>
                </div>

                <!-- Risk Summary Cards -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
                        <div class="card risk-card risk-level-low h-100 clickable-card" onclick="showRiskModal('low')" style="cursor: pointer;">
                            <div class="card-body text-center">
                                <h5 class="card-title">Low Risk</h5>
                                <div class="risk-score risk-low" id="low-risk-count">--</div>
                                <small class="text-muted">Animals with low health risk</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
                        <div class="card risk-card risk-level-medium h-100 clickable-card" onclick="showRiskModal('medium')" style="cursor: pointer;">
                            <div class="card-body text-center">
                                <h5 class="card-title">Medium Risk</h5>
                                <div class="risk-score risk-medium" id="medium-risk-count">--</div>
                                <small class="text-muted">Animals requiring monitoring</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
                        <div class="card risk-card risk-level-high h-100 clickable-card" onclick="showRiskModal('high')" style="cursor: pointer;">
                            <div class="card-body text-center">
                                <h5 class="card-title">High Risk</h5>
                                <div class="risk-score risk-high" id="high-risk-count">--</div>
                                <small class="text-muted">Animals needing attention</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
                        <div class="card risk-card risk-level-critical h-100 clickable-card" onclick="showRiskModal('critical')" style="cursor: pointer;">
                            <div class="card-body text-center">
                                <h5 class="card-title">Critical Risk</h5>
                                <div class="risk-score risk-critical" id="critical-risk-count">--</div>
                                <small class="text-muted">Animals requiring immediate care</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Barangay Anomaly Detection System -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Symptom-Based Anomaly Detection</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-3">Advanced monitoring system for detecting unusual patterns in animal symptoms by barangay location to identify potential disease outbreaks.</p>
                                
                                <!-- Barangay Anomaly Status -->
                                <div id="barangay-anomaly-status" class="mb-4">
                                    <div class="row">
                                        <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
                                            <div class="card risk-card anomaly-level-total clickable-card h-100" onclick="showAnomalyModal('total')" style="cursor: pointer;">
                                                <div class="card-body text-center">
                                                    <h5 class="card-title">Total Anomalies</h5>
                                                    <div class="risk-score anomaly-total" id="total-barangay-anomalies">--</div>
                                                    <small class="text-muted">Detected patterns</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
                                            <div class="card risk-card anomaly-level-critical clickable-card h-100" onclick="showAnomalyModal('critical')" style="cursor: pointer;">
                                                <div class="card-body text-center">
                                                    <h5 class="card-title">Critical</h5>
                                                    <div class="risk-score anomaly-critical" id="critical-barangay-anomalies">--</div>
                                                    <small class="text-muted">High priority</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
                                            <div class="card risk-card anomaly-level-high clickable-card h-100" onclick="showAnomalyModal('high')" style="cursor: pointer;">
                                                <div class="card-body text-center">
                                                    <h5 class="card-title">High Risk</h5>
                                                    <div class="risk-score anomaly-high" id="high-barangay-anomalies">--</div>
                                                    <small class="text-muted">Medium priority</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
                                            <div class="card risk-card anomaly-level-areas clickable-card h-100" onclick="showAnomalyModal('areas')" style="cursor: pointer;">
                                                <div class="card-body text-center">
                                                    <h5 class="card-title">Affected Areas</h5>
                                                    <div class="risk-score anomaly-areas" id="affected-barangays">--</div>
                                                    <small class="text-muted">Barangays</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Barangay Anomaly Controls -->
                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
                                    <div class="mb-2 mb-md-0">
                                        <button class="btn btn-primary btn-sm me-2 mb-2" onclick="checkBarangayAnomalies()">
                                            <i class="fas fa-search me-1"></i>Check Barangay Anomalies
                                        </button>
                                        <button class="btn btn-info btn-sm mb-2" onclick="showBarangayMap()">
                                            <i class="fas fa-map me-1"></i>View Barangay Map
                                        </button>
                                    </div>
                                    <div>
                                        <small class="text-muted">Last checked: <span id="last-barangay-check">Never</span></small>
                                </div>
                                </div>
                                
                                
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Animal Assessment List -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card risk-card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Animal Assessment List</h5>
                            </div>
                            <div class="card-body" id="animal-list-container">
                                <div class="loading-spinner">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Symptoms-Based Risk Analysis -->
                <div class="row">
                    <div class="col-12">
                        <div class="card risk-card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Symptoms-Based Risk Analysis</h5>
                            </div>
                            <div class="card-body">
                                <!-- Search Bar -->
                                <div class="row mb-3">
                                    <div class="col-md-8">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                                            <input type="text" id="symptomsSearchInput" class="form-control" placeholder="Search by client name, symptoms, species, or risk level..." onkeyup="searchSymptomsData()">
                                </div>
                            </div>
                                    <div class="col-md-4">
                                        <div class="btn-group w-100" role="group">
                                            <button type="button" class="btn btn-outline-primary" onclick="filterSymptomsByRisk('all')" id="filterAllSymptoms">All</button>
                                            <button type="button" class="btn btn-outline-danger" onclick="filterSymptomsByRisk('critical')" id="filterCriticalSymptoms">Critical</button>
                                            <button type="button" class="btn btn-outline-warning" onclick="filterSymptomsByRisk('high')" id="filterHighSymptoms">High</button>
                                            <button type="button" class="btn btn-outline-info" onclick="filterSymptomsByRisk('medium')" id="filterMediumSymptoms">Medium</button>
                                            <button type="button" class="btn btn-outline-success" onclick="filterSymptomsByRisk('low')" id="filterLowSymptoms">Low</button>
                        </div>
                    </div>
                </div>

                                <!-- Search Results Info -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <small class="text-muted">
                                            <span id="symptomsSearchResultsCount">Showing all symptoms</span>
                                        </small>
                            </div>
                                    <div class="col-md-6 text-end">
                                        <button class="btn btn-sm btn-outline-secondary" onclick="clearSymptomsSearch()">
                                            <i class="fas fa-times me-1"></i>Clear Search
                                        </button>
                                    </div>
                                </div>
                                
                                <div id="symptoms-risk-container">
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

    <!-- Assessment Modal -->
    <div class="modal fade" id="assessmentModal" tabindex="-1" aria-labelledby="assessmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assessmentModalLabel">Health Risk Assessment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="assessmentModalBody">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Anomaly Details Modal -->
    <div class="modal fade" id="anomalyModal" tabindex="-1" aria-labelledby="anomalyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="anomalyModalLabel">Anomaly Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="anomalyModalBody">
                    <!-- Anomaly content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Risk Level Details Modal -->
    <div class="modal fade" id="riskModal" tabindex="-1" aria-labelledby="riskModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="riskModalLabel">Risk Level Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="riskModalBody">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading risk level details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        
        // Load initial data
        document.addEventListener('DOMContentLoaded', function() {
            loadRiskSummary();
            loadSymptomsRiskData();
            loadAnimalList();
            
            // Initialize global alert system
            if (typeof globalAlert === 'undefined') {
                globalAlert = new GlobalAlert();
            }
            globalAlert.startMonitoring();
            
            // Check for alerts immediately
            setTimeout(function() {
                globalAlert.checkForAlerts();
            }, 2000);
            
            // Check for anomaly alerts
            setTimeout(function() {
                console.log('Checking for anomalies...');
                checkBarangayAnomalies();
            }, 3000);
            
            // Check for alerts every 30 seconds
            setInterval(function() {
                loadRiskSummary();
            }, 30000);
            
            // Refresh data every 5 minutes
            setInterval(function() {
                loadRiskSummary();
                loadSymptomsRiskData();
            }, 300000);
        });
        
        function loadRiskSummary() {
            // Load both traditional health risk assessment and symptoms-based risk data
            Promise.all([
                fetch('get_health_risk_assessment_simple.php?action=summary'),
                fetch('get_symptoms_risk_data.php')
            ])
            .then(responses => Promise.all(responses.map(r => r.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('JSON Parse Error in loadRiskSummary:', e);
                    console.error('Response text:', text);
                    return { success: false, error: 'Invalid JSON response' };
                }
            }))))
            .then(([healthData, symptomsData]) => {
                let summary = { Low: { count: 0 }, Medium: { count: 0 }, High: { count: 0 }, Critical: { count: 0 } };
                
                // Combine health assessment data
                if (healthData.success && healthData.summary) {
                    summary = healthData.summary;
                }
                
                // Add symptoms-based risk data
                if (symptomsData.success && symptomsData.summary) {
                    summary.Low.count += symptomsData.summary.Low.count;
                    summary.Medium.count += symptomsData.summary.Medium.count;
                    summary.High.count += symptomsData.summary.High.count;
                    summary.Critical.count += symptomsData.summary.Critical.count;
                }
                
                // Update display
                        document.getElementById('low-risk-count').textContent = summary.Low?.count || 0;
                        document.getElementById('medium-risk-count').textContent = summary.Medium?.count || 0;
                        document.getElementById('high-risk-count').textContent = summary.High?.count || 0;
                        document.getElementById('critical-risk-count').textContent = summary.Critical?.count || 0;
                        
                
                // Store symptoms data for later use
                window.symptomsRiskData = symptomsData;
                })
                .catch(error => {
                    console.error('Error loading risk summary:', error);
                    showErrorMessage('Error loading risk summary: ' + error.message);
                });
        }
        
        
        function loadSymptomsRiskData() {
            fetch('get_symptoms_risk_data.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status + ': ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        displaySymptomsRiskData(data);
                    } else {
                        console.error('API returned error:', data.error);
                        showSymptomsErrorMessage('Failed to load symptoms risk data: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error loading symptoms risk data:', error);
                    showSymptomsErrorMessage('Error loading symptoms risk data: ' + error.message);
                });
        }
        
        function displaySymptomsRiskData(data) {
            const container = document.getElementById('symptoms-risk-container');
            
            if (!data.risk_categories || data.total_requests === 0) {
                container.innerHTML = '<div class="text-center text-muted"><i class="fas fa-info-circle fa-2x mb-2"></i><div>No symptoms data available</div></div>';
                return;
            }
            
            let html = `
                <div class="row mb-3">
                    <div class="col-12">
                        <h6>Recent Symptoms Analysis (Last 30 Days)</h6>
                        <p class="text-muted">Total pharmaceutical requests with symptoms: ${data.total_requests}</p>
                    </div>
                </div>
                <div class="row">
            `;
            
            // Display each risk category
            const categories = ['Critical', 'High', 'Medium', 'Low'];
            const colors = ['danger', 'warning', 'info', 'success'];
            
            categories.forEach((category, index) => {
                const categoryData = data.risk_categories[category] || [];
                const color = colors[index];
                const riskClass = category.toLowerCase().replace(' ', '-');
                
                html += `
                    <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
                        <div class="card risk-card symptoms-level-${riskClass} h-100">
                            <div class="card-body text-center">
                                <h5 class="card-title">${category} Risk</h5>
                                <div class="risk-score symptoms-${riskClass}">${categoryData.length}</div>
                                <small class="text-muted">${data.summary[category].percentage}% of total</small>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            
            // Show recent symptoms from all risk levels
            if (data.risk_categories) {
                html += `
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6>Recent Symptoms by Risk Level</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Client</th>
                                            <th>Species</th>
                                            <th>Risk Level</th>
                                            <th>Symptoms</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody id="symptomsTableBody">
                `;
                
                // Combine all risk categories and show recent symptoms
                const allSymptoms = [];
                ['Critical', 'High', 'Medium', 'Low'].forEach(riskLevel => {
                    if (data.risk_categories[riskLevel]) {
                        data.risk_categories[riskLevel].forEach(animal => {
                            allSymptoms.push(animal);
                        });
                    }
                });
                
                // Sort by date (most recent first) and take top 10
                allSymptoms.sort((a, b) => new Date(b.request_date) - new Date(a.request_date));
                
                allSymptoms.slice(0, 10).forEach(animal => {
                    let riskBadgeClass = 'bg-secondary';
                    if (animal.risk_level === 'Critical') riskBadgeClass = 'bg-danger';
                    else if (animal.risk_level === 'High') riskBadgeClass = 'bg-warning';
                    else if (animal.risk_level === 'Medium') riskBadgeClass = 'bg-info';
                    else if (animal.risk_level === 'Low') riskBadgeClass = 'bg-success';
                    
                    html += `
                        <tr class="symptom-card" data-client="${animal.client_name}" data-species="${animal.species}" data-risk="${animal.risk_level.toLowerCase()}" data-symptoms="${animal.symptoms}" data-barangay="${animal.barangay || ''}">
                            <td>${animal.client_name || 'Unknown'}</td>
                            <td>${animal.species || 'Unknown'}</td>
                            <td><span class="badge ${riskBadgeClass} risk-level">${animal.risk_level}</span></td>
                            <td><small>${(animal.symptoms_array || []).slice(0, 2).join(', ')}${(animal.symptoms_array || []).length > 2 ? '...' : ''}</small></td>
                            <td><small>${new Date(animal.request_date).toLocaleDateString()}</small></td>
                        </tr>
                    `;
                });
                
                html += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
            }
            
            container.innerHTML = html;
        }
        
        function showSymptomsErrorMessage(message) {
            const container = document.getElementById('symptoms-risk-container');
            container.innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> ${message}
                </div>
            `;
        }
        
        function loadAnimalList() {
            const container = document.getElementById('animal-list-container');
            
            // Show loading spinner
            container.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading animal assessments...</p>
                </div>
            `;
            
            // Set timeout to prevent infinite loading
            const timeout = setTimeout(() => {
                container.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="fas fa-clock me-2"></i>
                        <strong>Loading Timeout</strong>
                        <p class="mb-0">The request is taking longer than expected. Please try refreshing the page.</p>
                        <button class="btn btn-sm btn-outline-primary mt-2" onclick="loadAnimalList()">
                            <i class="fas fa-refresh me-1"></i>Retry
                        </button>
                    </div>
                `;
            }, 10000); // 10 second timeout
            
            fetch('get_animal_list_simple.php', {
                method: 'GET',
                credentials: 'same-origin', // Include session cookies
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status + ': ' + response.statusText);
                    }
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('JSON Parse Error:', e);
                            console.error('Response text:', text);
                            throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                        }
                    });
                })
                .then(data => {
                    clearTimeout(timeout); // Clear timeout on success
                    if (data.success && data.animals && data.animals.length > 0) {
                        displayAnimalList(data.animals);
                    } else {
                        // Show message if no animals found
                        container.innerHTML = `
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>No Animal Assessments Found</strong>
                                <p class="mb-0">No health risk assessments have been generated yet. 
                                Assessments are created automatically when pharmaceutical requests with symptoms are submitted.</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    clearTimeout(timeout); // Clear timeout on error
                    console.error('Error loading animal list:', error);
                    container.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Unable to Load Animal List</strong>
                            <p class="mb-0">There was an issue loading the animal assessments. Please try refreshing the page.</p>
                            <small class="text-muted">Error: ${error.message}</small>
                            <br><br>
                            <button class="btn btn-sm btn-outline-primary" onclick="loadAnimalList()">
                                <i class="fas fa-refresh me-1"></i>Retry
                            </button>
                        </div>
                    `;
                });
        }
        
        function showErrorMessage(message) {
            // Show error message in console and alert
            console.error('Health Risk Monitoring Error:', message);
            alert('Error: ' + message + '\n\nPlease check the browser console for more details.');
        }
        
        
        function displayAnimalList(animals) {
            const container = document.getElementById('animal-list-container');
            
            let html = '<div class="table-responsive"><table class="table table-striped">';
            html += '<thead><tr><th>Animal</th><th>Client</th><th>Health Status</th><th>Last Risk Level</th><th>Last Assessment</th><th>Actions</th></tr></thead><tbody>';
            
            animals.forEach(animal => {
                const lastRiskLevel = animal.last_risk_level || 'Not Assessed';
                let lastAssessment;
                if (animal.last_assessment === 'Symptom-based') {
                    lastAssessment = 'Symptom-based';
                } else if (animal.last_assessment) {
                    lastAssessment = new Date(animal.last_assessment).toLocaleDateString();
                } else {
                    lastAssessment = 'Never';
                }
                const badgeClass = getRiskBadgeClass(lastRiskLevel);
                
                html += `
                    <tr>
                        <td>${animal.animal_name || 'Unnamed'}</td>
                        <td>${animal.client_name}</td>
                        <td><span class="badge bg-${animal.health_status === 'Healthy' ? 'success' : 'warning'}">${animal.health_status}</span></td>
                        <td><span class="badge ${badgeClass}">${lastRiskLevel}</span></td>
                        <td>${lastAssessment}</td>
                        <td>
                            <button class="btn btn-sm btn-assess me-1" onclick="assessAnimal(${animal.animal_id})">
                                <i class="fas fa-stethoscope"></i> Assess
                            </button>
                            <button class="btn btn-sm btn-outline-info" onclick="viewAssessment(${animal.animal_id})">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table></div>';
            container.innerHTML = html;
        }
        
        function getRiskBadgeClass(riskLevel) {
            switch(riskLevel) {
                case 'Critical': return 'bg-danger';
                case 'High': return 'bg-warning';
                case 'Medium': return 'bg-info';
                case 'Low': return 'bg-success';
                default: return 'bg-secondary';
            }
        }
        
        function assessAnimal(animalId) {
            fetch(`get_health_risk_assessment_simple.php?action=assess&animal_id=${animalId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAssessmentModal(data.assessment);
                        // Refresh data after assessment
                        loadRiskSummary();
                        loadAnimalList();
                    } else {
                        alert('Assessment failed: ' + (data.error || 'Unknown error occurred'));
                    }
                })
                .catch(error => {
                    console.error('Error assessing animal:', error);
                    alert('Assessment failed. Please try again or contact support.');
                });
        }
        
        function assessAllAnimals() {
            if (!confirm('This will assess all animals. This may take a few minutes. Continue?')) {
                return;
            }
            
            // Get all animal IDs
            fetch('get_health_risk_assessment_simple.php?action=animal_list')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const animalIds = data.animals.map(animal => animal.animal_id);
                        return fetch(`get_health_risk_assessment_simple.php?action=bulk_assess&animal_ids=${animalIds.join(',')}`);
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`Successfully assessed ${data.count} animals`);
                        loadRiskSummary();
                        loadAnimalList();
                    } else {
                        alert('Bulk assessment failed: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error in bulk assessment:', error);
                    alert('Error performing bulk assessment');
                });
        }
        
        function viewAssessment(animalId) {
            fetch(`get_health_risk_assessment_simple.php?action=get_assessment_history&animal_id=${animalId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAssessmentHistoryModal(data.assessments, animalId);
                    } else {
                        alert('Failed to load assessment history: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error loading assessment history:', error);
                    alert('Error loading assessment history');
                });
        }
        
        function showAssessmentModal(assessment) {
            const modalBody = document.getElementById('assessmentModalBody');
            const riskClass = assessment.risk_level === 'Critical' ? 'risk-critical' : 
                             assessment.risk_level === 'High' ? 'risk-high' : 
                             assessment.risk_level === 'Medium' ? 'risk-medium' : 'risk-low';
            
            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Risk Assessment Results</h6>
                        <div class="mb-3">
                            <strong>Risk Score:</strong> <span class="${riskClass}">${assessment.risk_score}%</span>
                        </div>
                        <div class="mb-3">
                            <strong>Risk Level:</strong> <span class="badge ${getRiskBadgeClass(assessment.risk_level)}">${assessment.risk_level}</span>
                        </div>
                        <div class="mb-3">
                            <strong>Confidence:</strong> ${assessment.confidence}%
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6>Risk Factors</h6>
                        <ul class="list-unstyled">
            `;
            
            assessment.risk_factors.forEach(factor => {
                html += `<li><i class="fas fa-exclamation-circle text-warning me-2"></i>${factor}</li>`;
            });
            
            html += `
                        </ul>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <h6>Recommendations</h6>
                        <ul class="list-unstyled">
            `;
            
            assessment.recommendations.forEach(rec => {
                html += `<li><i class="fas fa-check-circle text-success me-2"></i>${rec}</li>`;
            });
            
            html += `
                        </ul>
                    </div>
                </div>
            `;
            
            modalBody.innerHTML = html;
            new bootstrap.Modal(document.getElementById('assessmentModal')).show();
        }
        
        function showAssessmentHistoryModal(assessments, animalId) {
            const modalBody = document.getElementById('assessmentModalBody');
            
            let html = `<h6>Assessment History for Animal ID: ${animalId}</h6>`;
            
            if (assessments.length === 0) {
                html += '<p class="text-muted">No assessment history available.</p>';
            } else {
                html += '<div class="table-responsive"><table class="table table-sm">';
                html += '<thead><tr><th>Date</th><th>Risk Level</th><th>Score</th><th>Assessed By</th></tr></thead><tbody>';
                
                assessments.forEach(assessment => {
                    html += `
                        <tr>
                            <td>${new Date(assessment.assessment_date).toLocaleDateString()}</td>
                            <td><span class="badge ${getRiskBadgeClass(assessment.risk_level)}">${assessment.risk_level}</span></td>
                            <td>${assessment.risk_score}%</td>
                            <td>${assessment.assessed_by_name || 'System'}</td>
                        </tr>
                    `;
                });
                
                html += '</tbody></table></div>';
            }
            
            modalBody.innerHTML = html;
            new bootstrap.Modal(document.getElementById('assessmentModal')).show();
        }
        
        
        function refreshData() {
            loadRiskSummary();
            loadAnimalList();
        }
        
        // Anomaly Warning Popup Functions
        let anomalyAlertsShown = new Set(); // Track shown anomaly alerts
        
        // Reset anomaly alert tracking daily
        function resetAnomalyAlertTracking() {
            const today = new Date().toDateString();
            const lastReset = localStorage.getItem('anomalyAlertLastReset');
            
            if (lastReset !== today) {
                anomalyAlertsShown.clear();
                localStorage.setItem('anomalyAlertLastReset', today);
                console.log('Daily anomaly alert tracking reset for:', today);
            }
        }
        
        // Initialize anomaly alert tracking
        resetAnomalyAlertTracking();
        
        // Clear any existing popups on page load
        document.addEventListener('DOMContentLoaded', function() {
            closeBarangayAlert();
        });
        
        function showAnomalyWarning(severity) {
            // Create a unique identifier for this alert
            const alertId = `anomaly_${severity}_${new Date().toDateString()}`;
            
            // Check if we've already shown this alert today
            if (anomalyAlertsShown.has(alertId)) {
                console.log('Anomaly alert already shown today:', alertId);
                return;
            }
            
            const warningData = {
                animal: 'Swine',
                medicine: 'hog cholera',
                disease: 'Swine Fever',
                severity: 'warning'
            };
            
            const criticalData = {
                animal: 'Poultry',
                medicine: 'Newcastle',
                disease: 'Newcastle Disease',
                severity: 'critical'
            };
            
            const data = severity === 'critical' ? criticalData : warningData;
            const animalEmoji = getAnimalEmoji(data.animal);
            
            // Create popup HTML
            const popupHTML = `
                <div id="anomaly-warning-popup" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display: flex;">
                    <div class="max-w-md w-full mx-4 rounded-lg shadow-xl border-2 p-6 ${severity === 'critical' ? 'bg-red-50 border-red-200' : 'bg-yellow-50 border-yellow-200'}">
                        <!-- Warning Icon -->
                        <div class="text-center mb-4">
                            <div class="text-4xl ${severity === 'critical' ? 'text-red-600' : 'text-yellow-600'}">
                                ⚠️
                            </div>
                        </div>

                        <!-- Title -->
                        <h3 class="text-xl font-bold text-center mb-4 ${severity === 'critical' ? 'text-red-800' : 'text-yellow-800'}">
                            Possible Outbreak Detected
                        </h3>

                        <!-- Body Content -->
                        <div class="text-center mb-6 ${severity === 'critical' ? 'text-red-800' : 'text-yellow-800'}">
                            <p class="text-lg mb-2">
                                ${animalEmoji} Multiple approved ${data.medicine} medicines detected.
                            </p>
                            <p class="text-sm">
                                🔎 Possible ${data.disease} outbreak in this area.
                            </p>
                        </div>

                        <!-- Close Button -->
                        <div class="text-center">
                            <button onclick="closeAnomalyWarning()" class="px-6 py-2 rounded-lg text-white font-semibold transition-colors duration-200 ${severity === 'critical' ? 'bg-red-500 hover:bg-red-600' : 'bg-yellow-500 hover:bg-yellow-600'}">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            // Add popup to body
            document.body.insertAdjacentHTML('beforeend', popupHTML);
            
            // Mark this alert as shown
            anomalyAlertsShown.add(alertId);
            console.log('Anomaly alert shown:', alertId);
        }
        
        function closeAnomalyWarning() {
            const popup = document.getElementById('anomaly-warning-popup');
            if (popup) {
                popup.remove();
            }
        }
        
        function getAnimalEmoji(animal) {
            const emojiMap = {
                'Swine': '🐖',
                'Poultry': '🐔',
                'Cattle': '🐄',
                'Goat': '🐐',
                'Dog': '🐕',
                'Horse': '🐴',
                'Sheep': '🐑',
                'Carabao': '🐃'
            };
            return emojiMap[animal] || '🐾';
        }
        
        // Barangay Anomaly Detection Functions
        let barangayData = null;
        let barangayPopupVisible = false;
        let alertAcknowledged = false;
        let alertAcknowledgedTime = null;
        
        // Check for symptom-based anomalies
        async function checkBarangayAnomalies() {
            try {
                console.log('🔍 Checking symptom-based anomalies...');
                
                // Check if alert was recently acknowledged (within 1 hour)
                if (alertAcknowledged && alertAcknowledgedTime) {
                    const oneHour = 60 * 60 * 1000; // 1 hour in milliseconds
                    const timeSinceAcknowledged = Date.now() - alertAcknowledgedTime;
                    
                    if (timeSinceAcknowledged < oneHour) {
                        console.log(`Alert was acknowledged ${Math.round(timeSinceAcknowledged / 60000)} minutes ago. Skipping check until 1 hour passes.`);
                        return;
                    } else {
                        // Reset acknowledgment after 1 hour
                        alertAcknowledged = false;
                        alertAcknowledgedTime = null;
                        console.log('1 hour has passed since acknowledgment. Resuming anomaly checks.');
                    }
                }
                
                // Close any existing popups first
                closeBarangayAlert();
                
                // Fetch real data from pharmaceutical requests
                const response = await fetch('get_real_symptom_data.php');
                const data = await response.json();
                
                console.log('Real symptom data:', data);
                
                if (data.outbreak_alerts && data.outbreak_alerts.length > 0) {
                    // Only show alert if outbreak conditions are met (10+ barangays, 2+ people each)
                    console.log('OUTBREAK ALERT DETECTED:', data.outbreak_alerts);
                    barangayData = data;
                    updateBarangayStatus(data);
                    
                    // Show outbreak alert popup
                    const outbreakAlert = data.outbreak_alerts[0];
                    const outbreakData = {
                        type: 'CRITICAL OUTBREAK ALERT',
                        message: outbreakAlert.message,
                        severity: 'critical',
                        count: outbreakAlert.total_requests,
                        symptoms: outbreakAlert.symptom,
                        barangay: outbreakAlert.affected_barangays + ' barangays',
                        disease: outbreakAlert.disease,
                        animal: outbreakAlert.animal,
                        affected_barangays: outbreakAlert.affected_barangays,
                        total_requests: outbreakAlert.total_requests,
                        barangay_details: outbreakAlert.barangay_details
                    };
                    showBarangayAlertPopup(outbreakData);
                    
                    // Update last check time
                    document.getElementById('last-barangay-check').textContent = new Date().toLocaleTimeString();
                } else {
                    // No outbreak detected - show normal status
                    console.log('No outbreak conditions met');
                    barangayData = data;
                    updateBarangayStatus(data);
                    
                    // Update last check time
                    document.getElementById('last-barangay-check').textContent = new Date().toLocaleTimeString();
                }
            } catch (error) {
                console.error('Error checking barangay anomalies:', error);
                document.getElementById('total-barangay-anomalies').textContent = 'Error';
                document.getElementById('critical-barangay-anomalies').textContent = '--';
                document.getElementById('high-barangay-anomalies').textContent = '--';
                document.getElementById('affected-barangays').textContent = '--';
            }
        }
        
        // Update symptom-based status display
        function updateBarangayStatus(data) {
            if (data.outbreak_alerts && data.outbreak_alerts.length > 0) {
                // Show outbreak data
                const outbreak = data.outbreak_alerts[0];
                document.getElementById('total-barangay-anomalies').textContent = outbreak.total_requests;
                document.getElementById('critical-barangay-anomalies').textContent = outbreak.affected_barangays;
                document.getElementById('high-barangay-anomalies').textContent = outbreak.affected_barangays;
                document.getElementById('affected-barangays').textContent = outbreak.affected_barangays;
            } else if (data.symptom_patterns) {
                // Calculate proper statistics from symptom patterns
                let totalAnomalies = 0;
                let criticalCount = 0;
                let highCount = 0;
                let affectedBarangays = new Set();
                
                Object.values(data.symptom_patterns).forEach(pattern => {
                    totalAnomalies += pattern.total_requests;
                    
                    // Count by severity
                    if (pattern.severity === 'critical') {
                        criticalCount += pattern.barangay_count;
                    } else if (pattern.severity === 'high') {
                        highCount += pattern.barangay_count;
                    }
                    
                    // Collect unique barangays
                    pattern.affected_barangays.forEach(barangay => {
                        affectedBarangays.add(barangay.barangay);
                    });
                });
                
                // Update the display
                document.getElementById('total-barangay-anomalies').textContent = totalAnomalies;
                document.getElementById('critical-barangay-anomalies').textContent = criticalCount;
                document.getElementById('high-barangay-anomalies').textContent = highCount;
                document.getElementById('affected-barangays').textContent = affectedBarangays.size;
                
                console.log('Updated status:', {
                    totalAnomalies,
                    criticalCount,
                    highCount,
                    affectedBarangays: affectedBarangays.size
                });
            } else {
                // No data - show zeros
                document.getElementById('total-barangay-anomalies').textContent = '0';
                document.getElementById('critical-barangay-anomalies').textContent = '0';
                document.getElementById('high-barangay-anomalies').textContent = '0';
                document.getElementById('affected-barangays').textContent = '0';
            }
        }
        
        // Show barangay alert popup
        function showBarangayAlertPopup(popupData) {
            console.log('showBarangayAlertPopup called with:', popupData);
            
            // Close any existing popup first
            closeBarangayAlert();
            
            console.log('Creating popup for:', popupData);
            barangayPopupVisible = true;
            const animalEmoji = getAnimalEmoji(popupData.animal);
            
            // Create professional popup HTML
            const popupHTML = `
                <div id="barangay-alert-popup" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.75); z-index: 99999; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
                    <div style="background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); padding: 0; border-radius: 15px; max-width: 600px; margin: 20px; box-shadow: 0 25px 50px rgba(0,0,0,0.4); border: 1px solid #e9ecef; overflow: hidden; animation: slideInScale 0.4s ease-out;">
                        <!-- Professional Header -->
                        <div style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); padding: 20px; color: white; position: relative;">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <div style="display: flex; align-items: center;">
                                    <div style="background: rgba(255,255,255,0.2); border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                                        <i class="fas fa-exclamation-triangle" style="font-size: 24px; color: white;"></i>
                                    </div>
                                    <div>
                                        <h3 style="margin: 0; font-size: 20px; font-weight: 700; text-shadow: 0 1px 2px rgba(0,0,0,0.3);">CRITICAL OUTBREAK ALERT</h3>
                                        <p style="margin: 5px 0 0 0; font-size: 14px; opacity: 0.9;">Immediate veterinary attention required</p>
                                    </div>
                                </div>
                                <button onclick="closeBarangayAlert()" style="background: rgba(255,255,255,0.2); border: none; color: white; font-size: 24px; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease;">&times;</button>
                            </div>
                        </div>
                        
                        <!-- Alert Content -->
                        <div style="padding: 30px;">
                            <!-- Main Alert Message -->
                            <div style="text-align: center; margin-bottom: 25px;">
                                <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
                                    <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                                        <i class="fas fa-thermometer-full" style="color: #dc3545; font-size: 24px; margin-right: 10px;"></i>
                                        <span style="font-size: 18px; font-weight: 600; color: #856404;">High Fever Symptoms Detected</span>
                                    </div>
                                    <p style="margin: 0; font-size: 16px; color: #856404;">
                                        <strong>${popupData.symptoms || popupData.symptom || popupData.medicine || 'Critical symptoms'}</strong> symptoms reported in <strong style="color: #dc3545;">${popupData.barangay || 'Multiple locations'}</strong>
                                    </p>
                                </div>
                                
                                <!-- Disease Information -->
                                <div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 10px; padding: 15px; margin-bottom: 20px;">
                                    <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 8px;">
                                        <i class="fas fa-virus" style="color: #721c24; font-size: 20px; margin-right: 8px;"></i>
                                        <span style="font-weight: 600; color: #721c24;">Suspected Disease: ${popupData.disease || 'Critical symptoms detected'}</span>
                                    </div>
                                    <p style="margin: 0; font-size: 14px; color: #721c24;">
                                        This pattern suggests a potential outbreak requiring immediate investigation
                                    </p>
                                </div>
                                
                                <!-- Statistics -->
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px;">
                                    <div style="background: #e2e3e5; border-radius: 8px; padding: 15px; text-align: center;">
                                        <div style="font-size: 24px; font-weight: 700; color: #dc3545; margin-bottom: 5px;">${popupData.count || popupData.current_requests || 0}</div>
                                        <div style="font-size: 12px; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px;">Reports</div>
                                    </div>
                                    <div style="background: #e2e3e5; border-radius: 8px; padding: 15px; text-align: center;">
                                        <div style="font-size: 24px; font-weight: 700; color: #fd7e14; margin-bottom: 5px;">+${popupData.increase_percentage || 0}%</div>
                                        <div style="font-size: 12px; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px;">Increase</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div style="display: flex; gap: 15px;">
                                <button onclick="closeBarangayAlert()" style="flex: 1; background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%); color: white; border: none; padding: 12px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                    <i class="fas fa-check" style="margin-right: 8px;"></i>Acknowledge
                                </button>
                                <button onclick="viewBarangayDetails()" style="flex: 1; background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; border: none; padding: 12px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                    <i class="fas fa-chart-line" style="margin-right: 8px;"></i>View Details
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <style>
                    @keyframes slideInScale {
                        0% {
                            opacity: 0;
                            transform: scale(0.8) translateY(-20px);
                        }
                        100% {
                            opacity: 1;
                            transform: scale(1) translateY(0);
                        }
                    }
                    
                    #barangay-alert-popup button:hover {
                        transform: translateY(-1px);
                        box-shadow: 0 4px 8px rgba(0,0,0,0.2) !important;
                    }
                </style>
            `;
            
            // Add popup to body
            document.body.insertAdjacentHTML('beforeend', popupHTML);
        }
        
        // Close barangay alert popup
        function closeBarangayAlert() {
            const popup = document.getElementById('barangay-alert-popup');
            if (popup) {
                popup.remove();
                barangayPopupVisible = false;
                
                // Set acknowledgment flag and timestamp
                alertAcknowledged = true;
                alertAcknowledgedTime = Date.now();
                
                console.log('Alert acknowledged. Will not show again for 1 hour.');
                console.log(`Acknowledgment time: ${new Date().toLocaleTimeString()}`);
            }
        }
        
        // View barangay details
        function viewBarangayDetails() {
            console.log('View Details clicked');
            closeBarangayAlert();
            showBarangayMap();
        }
        
        // Show outbreak alert
        function showOutbreakAlert(outbreakAlert) {
            console.log('🚨 OUTBREAK ALERT:', outbreakAlert);
            
            // Close any existing popups
            closeBarangayAlert();
            
            const popup = document.createElement('div');
            popup.id = 'outbreak-alert-popup';
            popup.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.8);
                z-index: 10000;
                display: flex;
                justify-content: center;
                align-items: center;
                font-family: Arial, sans-serif;
            `;
            
            popup.innerHTML = `
                <div style="
                    background: linear-gradient(135deg, #dc3545, #c82333);
                    color: white;
                    padding: 30px;
                    border-radius: 15px;
                    max-width: 600px;
                    width: 90%;
                    text-align: center;
                    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
                    border: 3px solid #ff6b6b;
                    animation: pulse 2s infinite;
                ">
                    <div style="font-size: 48px; margin-bottom: 20px;">🚨</div>
                    <h2 style="margin: 0 0 15px 0; font-size: 28px; font-weight: bold;">
                        POSSIBLE OUTBREAK
                    </h2>
                    <div style="background: rgba(255, 255, 255, 0.2); padding: 20px; border-radius: 10px; margin: 20px 0;">
                        <h3 style="margin: 0 0 10px 0; font-size: 22px;">${outbreakAlert.disease}</h3>
                        <p style="margin: 5px 0; font-size: 18px;"><strong>Affected Barangays:</strong> ${outbreakAlert.affected_barangays}</p>
                        <p style="margin: 5px 0; font-size: 18px;"><strong>Total Cases:</strong> ${outbreakAlert.total_requests}</p>
                        <p style="margin: 5px 0; font-size: 18px;"><strong>Animal Type:</strong> ${outbreakAlert.animal}</p>
                    </div>
                    <div style="background: rgba(255, 255, 255, 0.1); padding: 15px; border-radius: 8px; margin: 15px 0;">
                        <p style="margin: 0; font-size: 16px; font-weight: bold;">
                            ⚠️ IMMEDIATE VETERINARY INTERVENTION REQUIRED ⚠️
                        </p>
                        <p style="margin: 10px 0 0 0; font-size: 14px;">
                            This pattern suggests a potential disease outbreak across multiple barangays.
                        </p>
                    </div>
                    <div style="margin-top: 25px;">
                        <button onclick="viewOutbreakDetails()" style="
                            background: #28a745;
                            color: white;
                            border: none;
                            padding: 12px 25px;
                            border-radius: 8px;
                            font-size: 16px;
                            font-weight: bold;
                            cursor: pointer;
                            margin-right: 10px;
                            transition: background 0.3s;
                        " onmouseover="this.style.background='#218838'" onmouseout="this.style.background='#28a745'">
                            View Details
                        </button>
                        <button onclick="closeOutbreakAlert()" style="
                            background: #6c757d;
                            color: white;
                            border: none;
                            padding: 12px 25px;
                            border-radius: 8px;
                            font-size: 16px;
                            font-weight: bold;
                            cursor: pointer;
                            transition: background 0.3s;
                        " onmouseover="this.style.background='#5a6268'" onmouseout="this.style.background='#6c757d'">
                            Close
                        </button>
                    </div>
                </div>
                <style>
                    @keyframes pulse {
                        0% { transform: scale(1); }
                        50% { transform: scale(1.02); }
                        100% { transform: scale(1); }
                    }
                </style>
            `;
            
            document.body.appendChild(popup);
            barangayPopupVisible = true;
        }
        
        // Close outbreak alert
        function closeOutbreakAlert() {
            const popup = document.getElementById('outbreak-alert-popup');
            if (popup) {
                popup.remove();
                barangayPopupVisible = false;
            }
        }
        
        // View outbreak details
        function viewOutbreakDetails() {
            console.log('View Outbreak Details clicked');
            closeOutbreakAlert();
            showOutbreakDetails();
        }
        
        // Show outbreak details
        function showOutbreakDetails() {
            console.log('Showing outbreak details');
            
            let html = `
                <div class="container-fluid">
                    <h5 class="mb-3"><i class="fas fa-virus text-danger me-2"></i>Outbreak Analysis</h5>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border-danger">
                                <div class="card-body text-center">
                                    <h4 class="text-danger">POSSIBLE OUTBREAK</h4>
                                    <p class="mb-0">Multi-Barangay Disease Pattern</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-warning">
                                <div class="card-body text-center">
                                    <h4 class="text-warning">10+ Barangays</h4>
                                    <p class="mb-0">Affected Locations</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header bg-danger text-white">
                            <h6 class="mb-0"><i class="fas fa-map-marked-alt me-2"></i>Geographic Spread Analysis</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Detection Criteria:</strong><br>
                                    • Minimum 2 people per barangay<br>
                                    • Same symptoms across locations<br>
                                    • 10+ affected barangays
                                </div>
                                <div class="col-md-6">
                                    <strong>Risk Assessment:</strong><br>
                                    • High transmission potential<br>
                                    • Requires immediate response<br>
                                    • Quarantine measures needed
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-danger mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>CRITICAL ALERT:</strong> This pattern indicates a potential disease outbreak requiring immediate veterinary investigation, quarantine measures, and coordination with health authorities.
                    </div>
                </div>
            `;
            
            // Show in modal
            const modalBody = document.getElementById('assessmentModalBody');
            if (modalBody) {
                modalBody.innerHTML = html;
                new bootstrap.Modal(document.getElementById('assessmentModal')).show();
            }
        }
        
        // Symptoms search functionality
        function searchSymptomsData() {
            const searchTerm = document.getElementById('symptomsSearchInput').value.toLowerCase();
            const riskFilter = getCurrentSymptomsRiskFilter();
            
            // Get all symptom cards and table rows within the symptoms section only
            const symptomsContainer = document.querySelector('#symptoms-risk-container');
            if (!symptomsContainer) return;
            
            const cards = symptomsContainer.querySelectorAll('.symptom-card, #symptomsTableBody tr, .symptoms-level-critical, .symptoms-level-high, .symptoms-level-medium, .symptoms-level-low');
            let visibleCount = 0;
            
            cards.forEach(card => {
                const cardText = card.textContent.toLowerCase();
                const isVisible = cardText.includes(searchTerm) && matchesSymptomsRiskFilter(card, riskFilter);
                
                if (isVisible) {
                    card.style.display = card.tagName === 'TR' ? 'table-row' : 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Update search results count
            updateSymptomsSearchResultsCount(visibleCount, cards.length, searchTerm);
        }
        
        // Filter symptoms by risk level
        function filterSymptomsByRisk(riskLevel) {
            // Update button states
            document.querySelectorAll('[id^="filter"][id$="Symptoms"]').forEach(btn => {
                btn.classList.remove('active');
            });
            document.getElementById('filter' + riskLevel.charAt(0).toUpperCase() + riskLevel.slice(1) + 'Symptoms').classList.add('active');
            
            // Apply search with current filter
            searchSymptomsData();
        }
        
        // Get current symptoms risk filter
        function getCurrentSymptomsRiskFilter() {
            const activeButton = document.querySelector('[id^="filter"][id$="Symptoms"].active');
            return activeButton ? activeButton.id.replace('filter', '').replace('Symptoms', '').toLowerCase() : 'all';
        }
        
        // Check if card matches symptoms risk filter
        function matchesSymptomsRiskFilter(card, riskFilter) {
            if (riskFilter === 'all') return true;
            
            const cardText = card.textContent.toLowerCase();
            
            // Check data attributes first
            const dataRisk = card.getAttribute('data-risk');
            if (dataRisk && dataRisk.toLowerCase().includes(riskFilter)) {
                return true;
            }
            
            // Check for risk level badges
            const riskLevel = card.querySelector('.badge, .risk-level, [class*="risk"]');
            if (riskLevel) {
                const level = riskLevel.textContent.toLowerCase();
                return level.includes(riskFilter);
            }
            
            // Check for risk level classes
            const riskClasses = card.className.toLowerCase();
            if (riskClasses.includes(riskFilter)) {
                return true;
            }
            
            // Fallback: check for risk indicators in text
            const riskIndicators = {
                'critical': ['critical', 'severe', 'emergency', 'danger'],
                'high': ['high', 'serious', 'urgent', 'warning'],
                'medium': ['medium', 'moderate', 'info'],
                'low': ['low', 'mild', 'minor', 'success']
            };
            
            if (riskIndicators[riskFilter]) {
                return riskIndicators[riskFilter].some(indicator => cardText.includes(indicator));
            }
            
            return true;
        }
        
        // Update symptoms search results count
        function updateSymptomsSearchResultsCount(visible, total, searchTerm) {
            const countElement = document.getElementById('symptomsSearchResultsCount');
            
            if (searchTerm) {
                countElement.textContent = `Showing ${visible} of ${total} results for "${searchTerm}"`;
            } else {
                countElement.textContent = `Showing ${visible} of ${total} symptoms`;
            }
        }
        
        // Clear symptoms search
        function clearSymptomsSearch() {
            document.getElementById('symptomsSearchInput').value = '';
            document.querySelectorAll('[id^="filter"][id$="Symptoms"]').forEach(btn => {
                btn.classList.remove('active');
            });
            document.getElementById('filterAllSymptoms').classList.add('active');
            
            // Show all cards and table rows within symptoms container only
            const symptomsContainer = document.querySelector('#symptoms-risk-container');
            if (symptomsContainer) {
                const cards = symptomsContainer.querySelectorAll('.symptom-card, #symptomsTableBody tr, .symptoms-level-critical, .symptoms-level-high, .symptoms-level-medium, .symptoms-level-low');
                cards.forEach(card => {
                    card.style.display = card.tagName === 'TR' ? 'table-row' : 'block';
                });
                
                updateSymptomsSearchResultsCount(cards.length, cards.length, '');
            }
        }
        
        // Initialize symptoms search on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Set default active filter for symptoms
            const filterAllSymptoms = document.getElementById('filterAllSymptoms');
            if (filterAllSymptoms) {
                filterAllSymptoms.classList.add('active');
            }
        });
        
        // Show current anomaly details
        function showCurrentAnomalyDetails() {
            console.log('Showing current anomaly details');
            console.log('barangayData:', barangayData);
            
            if (!barangayData || !barangayData.symptom_patterns) {
                // No data available
                let html = `
                    <div class="container-fluid">
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle me-2"></i>No Anomaly Data Available</h5>
                            <p class="mb-0">No symptom patterns have been detected yet. The system is monitoring for unusual patterns.</p>
                        </div>
                    </div>
                `;
                
                const modalBody = document.getElementById('assessmentModalBody');
                if (modalBody) {
                    modalBody.innerHTML = html;
                    new bootstrap.Modal(document.getElementById('assessmentModal')).show();
                }
                return;
            }
            
            // Calculate statistics from actual data
            let totalAnomalies = 0;
            let criticalCount = 0;
            let highCount = 0;
            let affectedBarangays = new Set();
            let criticalPatterns = [];
            let highPatterns = [];
            
            Object.values(barangayData.symptom_patterns).forEach(pattern => {
                totalAnomalies += pattern.total_requests;
                
                if (pattern.severity === 'critical') {
                    criticalCount += pattern.barangay_count;
                    criticalPatterns.push(pattern);
                } else if (pattern.severity === 'high') {
                    highCount += pattern.barangay_count;
                    highPatterns.push(pattern);
                }
                
                pattern.affected_barangays.forEach(barangay => {
                    affectedBarangays.add(barangay.barangay);
                });
            });
            
            let html = `
                <div class="container-fluid">
                    <h5 class="mb-3"><i class="fas fa-chart-bar text-primary me-2"></i>Total Anomalies Details</h5>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border-primary">
                                <div class="card-body text-center">
                                    <h4 class="text-primary">${totalAnomalies}</h4>
                                    <p class="mb-0">Total Detected Patterns</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-success">
                                <div class="card-body text-center">
                                    <h4 class="text-success">${affectedBarangays.size}</h4>
                                    <p class="mb-0">Affected Barangays</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-primary">
                        <h6><i class="fas fa-info-circle me-2"></i>Total Anomalies Overview</h6>
                        <p class="mb-0">This shows the total number of unusual symptom patterns detected across all barangays in the last 48 hours.</p>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Pattern Statistics</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <h4 class="text-danger">${criticalCount}</h4>
                                            <small class="text-muted">Critical Patterns</small>
                                        </div>
                                        <div class="col-6">
                                            <h4 class="text-warning">${highCount}</h4>
                                            <small class="text-muted">High Risk Patterns</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Affected Areas</h6>
                                </div>
                                <div class="card-body">
                                    <p class="mb-1"><strong>Total Barangays:</strong> ${affectedBarangays.size}</p>
                                    <p class="mb-1"><strong>Barangays:</strong> ${Array.from(affectedBarangays).join(', ')}</p>
                                    <p class="mb-0"><strong>Status:</strong> <span class="badge bg-success">Monitoring</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            const modalBody = document.getElementById('assessmentModalBody');
            if (modalBody) {
                modalBody.innerHTML = html;
                new bootstrap.Modal(document.getElementById('assessmentModal')).show();
            }
        }
        
        // Show critical anomalies details
        function showCriticalAnomalyDetails() {
            console.log('Showing critical anomaly details');
            console.log('barangayData:', barangayData);
            
            if (!barangayData || !barangayData.symptom_patterns) {
                let html = `
                    <div class="container-fluid">
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle me-2"></i>No Critical Anomalies</h5>
                            <p class="mb-0">No critical anomalies have been detected. The system is monitoring for unusual patterns.</p>
                        </div>
                    </div>
                `;
                
                const modalBody = document.getElementById('assessmentModalBody');
                if (modalBody) {
                    modalBody.innerHTML = html;
                    new bootstrap.Modal(document.getElementById('assessmentModal')).show();
                }
                return;
            }
            
            // Filter critical patterns
            let criticalPatterns = [];
            Object.values(barangayData.symptom_patterns).forEach(pattern => {
                if (pattern.severity === 'critical') {
                    criticalPatterns.push(pattern);
                }
            });
            
            if (criticalPatterns.length === 0) {
                let html = `
                    <div class="container-fluid">
                        <div class="alert alert-warning">
                            <h5><i class="fas fa-exclamation-triangle me-2"></i>Critical Anomalies - Immediate Action Required</h5>
                            <p class="mb-0">These are high-priority anomalies that require immediate attention. They indicate potential disease outbreaks.</p>
                        </div>
                        <div class="alert alert-info">
                            <p class="mb-0">No critical anomalies detected.</p>
                        </div>
                    </div>
                `;
                
                const modalBody = document.getElementById('assessmentModalBody');
                if (modalBody) {
                    modalBody.innerHTML = html;
                    new bootstrap.Modal(document.getElementById('assessmentModal')).show();
                }
                return;
            }
            
            // Show critical patterns
            let html = `
                <div class="container-fluid">
                    <h5 class="mb-3"><i class="fas fa-exclamation-triangle text-danger me-2"></i>Critical Anomalies Details</h5>
                    
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Critical Anomalies - Immediate Action Required</h6>
                        <p class="mb-0">These are high-priority anomalies that require immediate attention. They indicate potential disease outbreaks.</p>
                    </div>
                    
                    <div class="row">
            `;
            
            criticalPatterns.forEach(pattern => {
                html += `
                    <div class="col-md-6 mb-3">
                        <div class="card border-danger">
                            <div class="card-header bg-danger text-white">
                                <h6 class="mb-0"><i class="fas fa-virus me-2"></i>${pattern.symptom}</h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Disease:</strong> ${pattern.disease}</p>
                                <p><strong>Animal:</strong> ${pattern.animal}</p>
                                <p><strong>Affected Barangays:</strong> ${pattern.barangay_count}</p>
                                <p><strong>Total Requests:</strong> ${pattern.total_requests}</p>
                                <div class="mt-2">
                                    <span class="badge bg-danger">Critical Severity</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += `
                    </div>
                </div>
            `;
            
            const modalBody = document.getElementById('assessmentModalBody');
            if (modalBody) {
                modalBody.innerHTML = html;
                new bootstrap.Modal(document.getElementById('assessmentModal')).show();
            }
        }
        
        // Show high risk anomalies details
        function showHighRiskAnomalyDetails() {
            console.log('Showing high risk anomaly details');
            console.log('barangayData:', barangayData);
            
            if (!barangayData || !barangayData.symptom_patterns) {
                let html = `
                    <div class="container-fluid">
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle me-2"></i>No High Risk Anomalies</h5>
                            <p class="mb-0">No high risk anomalies have been detected. The system is monitoring for unusual patterns.</p>
                        </div>
                    </div>
                `;
                
                const modalBody = document.getElementById('assessmentModalBody');
                if (modalBody) {
                    modalBody.innerHTML = html;
                    new bootstrap.Modal(document.getElementById('assessmentModal')).show();
                }
                return;
            }
            
            // Filter high risk patterns
            let highRiskPatterns = [];
            Object.values(barangayData.symptom_patterns).forEach(pattern => {
                if (pattern.severity === 'high') {
                    highRiskPatterns.push(pattern);
                }
            });
            
            if (highRiskPatterns.length === 0) {
                let html = `
                    <div class="container-fluid">
                        <div class="alert alert-warning">
                            <h5><i class="fas fa-exclamation-triangle me-2"></i>High Risk Anomalies - Monitoring Required</h5>
                            <p class="mb-0">These are medium-priority anomalies that need monitoring. They may indicate developing health issues.</p>
                        </div>
                        <div class="alert alert-info">
                            <p class="mb-0">No high risk anomalies detected.</p>
                        </div>
                    </div>
                `;
                
                const modalBody = document.getElementById('assessmentModalBody');
                if (modalBody) {
                    modalBody.innerHTML = html;
                    new bootstrap.Modal(document.getElementById('assessmentModal')).show();
                }
                return;
            }
            
            // Show high risk patterns
            let html = `
                <div class="container-fluid">
                    <h5 class="mb-3"><i class="fas fa-exclamation-triangle text-warning me-2"></i>High Risk Anomalies Details</h5>
                    
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>High Risk Anomalies - Monitoring Required</h6>
                        <p class="mb-0">These are medium-priority anomalies that need monitoring. They may indicate developing health issues.</p>
                    </div>
                    
                    <div class="row">
            `;
            
            highRiskPatterns.forEach(pattern => {
                html += `
                    <div class="col-md-6 mb-3">
                        <div class="card border-warning">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="mb-0"><i class="fas fa-thermometer-half me-2"></i>${pattern.symptom}</h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Disease:</strong> ${pattern.disease}</p>
                                <p><strong>Animal:</strong> ${pattern.animal}</p>
                                <p><strong>Affected Barangays:</strong> ${pattern.barangay_count}</p>
                                <p><strong>Total Requests:</strong> ${pattern.total_requests}</p>
                                <div class="mt-2">
                                    <span class="badge bg-warning">High Risk</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += `
                    </div>
                </div>
            `;
            
            const modalBody = document.getElementById('assessmentModalBody');
            if (modalBody) {
                modalBody.innerHTML = html;
                new bootstrap.Modal(document.getElementById('assessmentModal')).show();
            }
        }
        
        // Show affected areas details
        function showAffectedAreasDetails() {
            console.log('Showing affected areas details');
            console.log('barangayData:', barangayData);
            
            if (!barangayData || !barangayData.symptom_patterns) {
                let html = `
                    <div class="container-fluid">
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle me-2"></i>No Affected Areas</h5>
                            <p class="mb-0">No affected areas have been detected. The system is monitoring for unusual patterns.</p>
                        </div>
                    </div>
                `;
                
                const modalBody = document.getElementById('assessmentModalBody');
                if (modalBody) {
                    modalBody.innerHTML = html;
                    new bootstrap.Modal(document.getElementById('assessmentModal')).show();
                }
                return;
            }
            
            // Collect all affected barangays
            let affectedBarangays = new Set();
            let barangayDetails = {};
            
            Object.values(barangayData.symptom_patterns).forEach(pattern => {
                pattern.affected_barangays.forEach(barangay => {
                    affectedBarangays.add(barangay.barangay);
                    if (!barangayDetails[barangay.barangay]) {
                        barangayDetails[barangay.barangay] = {
                            barangay: barangay.barangay,
                            patterns: [],
                            totalRequests: 0
                        };
                    }
                    barangayDetails[barangay.barangay].patterns.push(pattern);
                    barangayDetails[barangay.barangay].totalRequests += barangay.request_count;
                });
            });
            
            if (affectedBarangays.size === 0) {
                let html = `
                    <div class="container-fluid">
                        <div class="alert alert-success">
                            <h5><i class="fas fa-map-marker-alt me-2"></i>Affected Areas Overview</h5>
                            <p class="mb-0">These are the barangays where anomalies have been detected.</p>
                        </div>
                        <div class="alert alert-info">
                            <p class="mb-0">No affected areas detected.</p>
                        </div>
                    </div>
                `;
                
                const modalBody = document.getElementById('assessmentModalBody');
                if (modalBody) {
                    modalBody.innerHTML = html;
                    new bootstrap.Modal(document.getElementById('assessmentModal')).show();
                }
                return;
            }
            
            // Show affected areas
            let html = `
                <div class="container-fluid">
                    <h5 class="mb-3"><i class="fas fa-map-marker-alt text-success me-2"></i>Affected Areas Details</h5>
                    
                    <div class="alert alert-success">
                        <h6><i class="fas fa-map-marker-alt me-2"></i>Affected Areas Overview</h6>
                        <p class="mb-0">These are the barangays where anomalies have been detected.</p>
                    </div>
                    
                    <div class="row">
            `;
            
            Object.values(barangayDetails).forEach(detail => {
                html += `
                    <div class="col-md-6 mb-3">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>${detail.barangay}</h6>
                            </div>
                            <div class="card-body">
                                <p><strong>Total Requests:</strong> ${detail.totalRequests}</p>
                                <p><strong>Patterns Detected:</strong> ${detail.patterns.length}</p>
                                <div class="mt-2">
                                    <span class="badge bg-success">Affected Area</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += `
                    </div>
                </div>
            `;
            
            const modalBody = document.getElementById('assessmentModalBody');
            if (modalBody) {
                modalBody.innerHTML = html;
                new bootstrap.Modal(document.getElementById('assessmentModal')).show();
            }
        }
        
        // Show barangay map
        function showBarangayMap() {
            console.log('showBarangayMap called');
            console.log('barangayData:', barangayData);
            
            if (!barangayData || !barangayData.barangay_map) {
                console.log('No barangay data available, showing current anomaly details...');
                // Show current anomaly details instead
                showCurrentAnomalyDetails();
                return;
            }
            
            let html = '<div class="table-responsive"><table class="table table-striped"><thead><tr><th>Barangay</th><th>Total Anomalies</th><th>Critical</th><th>High</th><th>Medium</th><th>Low</th></tr></thead><tbody>';
            
            if (barangayData.barangay_map) {
                Object.values(barangayData.barangay_map).forEach(barangay => {
                    html += '<tr>' +
                        '<td><strong>' + (barangay.barangay || 'Unknown') + '</strong></td>' +
                        '<td><span class="badge bg-primary">' + (barangay.total_anomalies || 0) + '</span></td>' +
                        '<td><span class="badge bg-danger">' + (barangay.critical_count || 0) + '</span></td>' +
                        '<td><span class="badge bg-warning">' + (barangay.high_count || 0) + '</span></td>' +
                        '<td><span class="badge bg-info">' + (barangay.medium_count || 0) + '</span></td>' +
                        '<td><span class="badge bg-success">' + (barangay.low_count || 0) + '</span></td>' +
                        '</tr>';
                });
            }
            
            html += '</tbody></table></div>';
            
            // Show in modal
            const modalBody = document.getElementById('assessmentModalBody');
            if (modalBody) {
                modalBody.innerHTML = html;
                new bootstrap.Modal(document.getElementById('assessmentModal')).show();
            }
        }
        
        // Show anomaly modal with details
        function showAnomalyModal(type) {
            if (!barangayData) {
                alert('No anomaly data available. Please check for anomalies first.');
                return;
            }
            
            // Debug: Log the actual data structure
            console.log('Barangay data structure:', barangayData);
            console.log('Symptom patterns:', barangayData.symptom_patterns);
            
            // Call the appropriate function based on type
            switch(type) {
                case 'total':
                    showCurrentAnomalyDetails();
                    break;
                case 'critical':
                    showCriticalAnomalyDetails();
                    break;
                case 'high':
                    showHighRiskAnomalyDetails();
                    break;
                case 'areas':
                    showAffectedAreasDetails();
                    break;
            }
        }
        
        // Generate content for each modal type
        function generateTotalAnomaliesContent() {
            let html = `
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle me-2"></i>Total Anomalies Overview</h6>
                    <p class="mb-0">This shows the total number of unusual symptom patterns detected across all barangays in the last 48 hours.</p>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-primary">
                            <div class="card-body text-center">
                                <h4 class="text-primary">${barangayData.summary?.total_anomalies || 0}</h4>
                                <p class="mb-0">Total Detected Patterns</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-info">
                            <div class="card-body text-center">
                                <h4 class="text-info">${Object.keys(barangayData.summary?.by_barangay || {}).length}</h4>
                                <p class="mb-0">Affected Barangays</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            if (barangayData.anomalies && barangayData.anomalies.length > 0) {
                html += `
                    <h6 class="mt-4">Recent Anomalies:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr><th>Symptom</th><th>Barangay</th><th>Severity</th><th>Reports</th></tr>
                            </thead>
                            <tbody>
                `;
                
                barangayData.anomalies.slice(0, 5).forEach(anomaly => {
                    html += '<tr>' +
                        '<td>' + (anomaly.symptom || 'Unknown') + '</td>' +
                        '<td>' + (anomaly.barangay || 'Unknown') + '</td>' +
                        '<td><span class="badge bg-' + (anomaly.severity === 'critical' ? 'danger' : 'warning') + '">' + (anomaly.severity || 'Unknown') + '</span></td>' +
                        '<td>' + (anomaly.current_requests || 0) + '</td>' +
                        '</tr>';
                });
                
                html += '</tbody></table></div>';
            }
            
            return html;
        }
        
        function generateCriticalAnomaliesContent() {
            let criticalAnomalies = barangayData.anomalies?.filter(a => a.severity === 'critical') || [];
            
            let html = `
                <div class="alert alert-danger">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Critical Anomalies - Immediate Action Required</h6>
                    <p class="mb-0">These are high-priority anomalies that require immediate attention. They indicate potential disease outbreaks.</p>
                </div>
            `;
            
            if (criticalAnomalies.length > 0) {
                html += `
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr><th>Symptom</th><th>Barangay</th><th>Disease</th><th>Reports</th><th>Increase</th></tr>
                            </thead>
                            <tbody>
                `;
                
                criticalAnomalies.forEach(anomaly => {
                    html += `
                        <tr class="table-danger">
                            <td><strong>${anomaly.symptom}</strong></td>
                            <td>${anomaly.barangay}</td>
                            <td>${anomaly.disease}</td>
                            <td><span class="badge bg-danger">${anomaly.current_requests}</span></td>
                            <td><span class="text-danger">+${anomaly.increase_percentage}%</span></td>
                        </tr>
                    `;
                });
                
                html += '</tbody></table></div>';
            } else {
                html += '<p class="text-muted">No critical anomalies detected.</p>';
            }
            
            return html;
        }
        
        function generateHighRiskAnomaliesContent() {
            let highRiskAnomalies = barangayData.anomalies?.filter(a => a.severity === 'high') || [];
            
            let html = `
                <div class="alert alert-warning">
                    <h6><i class="fas fa-exclamation-circle me-2"></i>High Risk Anomalies - Monitoring Required</h6>
                    <p class="mb-0">These are medium-priority anomalies that need monitoring. They may indicate developing health issues.</p>
                </div>
            `;
            
            if (highRiskAnomalies.length > 0) {
                html += `
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr><th>Symptom</th><th>Barangay</th><th>Disease</th><th>Reports</th><th>Increase</th></tr>
                            </thead>
                            <tbody>
                `;
                
                highRiskAnomalies.forEach(anomaly => {
                    html += `
                        <tr class="table-warning">
                            <td><strong>${anomaly.symptom}</strong></td>
                            <td>${anomaly.barangay}</td>
                            <td>${anomaly.disease}</td>
                            <td><span class="badge bg-warning">${anomaly.current_requests}</span></td>
                            <td><span class="text-warning">+${anomaly.increase_percentage}%</span></td>
                        </tr>
                    `;
                });
                
                html += '</tbody></table></div>';
            } else {
                html += '<p class="text-muted">No high risk anomalies detected.</p>';
            }
            
            return html;
        }
        
        function generateAffectedAreasContent() {
            let barangayMap = barangayData.summary?.by_barangay || {};
            let anomalies = barangayData.anomalies || [];
            
            let html = `
                <div class="alert alert-success">
                    <h6><i class="fas fa-map-marker-alt me-2"></i>Affected Areas Overview</h6>
                    <p class="mb-0">These are the barangays where anomalies have been detected.</p>
                </div>
            `;
            
            if (Object.keys(barangayMap).length > 0) {
                html += `
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr><th>Barangay</th><th>Total Anomalies</th><th>Critical</th><th>High Risk</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                `;
                
                // Process barangay data correctly
                Object.entries(barangayMap).forEach(([barangayName, count]) => {
                    // Count critical and high risk anomalies for this barangay
                    let criticalCount = anomalies.filter(a => a.barangay === barangayName && a.severity === 'critical').length;
                    let highCount = anomalies.filter(a => a.barangay === barangayName && a.severity === 'high').length;
                    
                    let statusClass = criticalCount > 0 ? 'danger' : highCount > 0 ? 'warning' : 'success';
                    let statusText = criticalCount > 0 ? 'Critical Alert' : highCount > 0 ? 'High Risk' : 'Normal';
                    
                    html += `
                        <tr>
                            <td><strong>${barangayName}</strong></td>
                            <td><span class="badge bg-primary">${count}</span></td>
                            <td><span class="badge bg-danger">${criticalCount}</span></td>
                            <td><span class="badge bg-warning">${highCount}</span></td>
                            <td><span class="badge bg-${statusClass}">${statusText}</span></td>
                        </tr>
                    `;
                });
                
                html += '</tbody></table></div>';
            } else {
                html += '<p class="text-muted">No affected areas detected.</p>';
            }
            
            return html;
        }
        
        // Demo function for showing barangay alerts
        function showBarangayAlert(barangay, medicine, disease, severity) {
            const demoData = {
                animal: medicine === 'Hog Colera' ? 'Swine' : 'Poultry',
                medicine: medicine,
                disease: disease,
                severity: severity,
                barangay: barangay,
                count: severity === 'critical' ? 5 : 3,
                deviationPercentage: severity === 'critical' ? 300 : 200,
                affectedAreas: [barangay],
                totalQuantity: severity === 'critical' ? 25 : 15,
                affectedClients: [1, 2, 3]
            };
            
            showBarangayAlertPopup(demoData);
        }
        
        // Helper functions for barangay popup styling
        function getBarangayBackgroundClass(severity) {
            switch (severity) {
                case 'critical': return 'bg-red-50';
                case 'high': return 'bg-orange-50';
                case 'medium': return 'bg-yellow-50';
                case 'low': return 'bg-blue-50';
                default: return 'bg-yellow-50';
            }
        }
        
        function getBarangayHeaderClass(severity) {
            switch (severity) {
                case 'critical': return 'bg-red-600 text-white';
                case 'high': return 'bg-orange-600 text-white';
                case 'medium': return 'bg-yellow-600 text-white';
                case 'low': return 'bg-blue-600 text-white';
                default: return 'bg-yellow-600 text-white';
            }
        }
        
        function getBarangayTextColor(severity) {
            switch (severity) {
                case 'critical': return 'text-red-800';
                case 'high': return 'text-orange-800';
                case 'medium': return 'text-yellow-800';
                case 'low': return 'text-blue-800';
                default: return 'text-yellow-800';
            }
        }
        
        function getBarangayIcon(severity) {
            switch (severity) {
                case 'critical': return '🚨';
                case 'high': return '⚠️';
                case 'medium': return '⚠️';
                case 'low': return 'ℹ️';
                default: return '⚠️';
            }
        }
        
        function getBarangayTitle(severity) {
            switch (severity) {
                case 'critical': return 'CRITICAL OUTBREAK DETECTED';
                case 'high': return 'HIGH RISK DETECTED';
                case 'medium': return 'POSSIBLE OUTBREAK DETECTED';
                case 'low': return 'LOW RISK DETECTED';
                default: return 'POSSIBLE OUTBREAK DETECTED';
            }
        }
        
        // Note: Automatic checks removed to respect 1-hour acknowledgment period
        // Users can manually check using the "Check Barangay Anomalies" button
        
        // Initialize page on load
        document.addEventListener('DOMContentLoaded', function() {
            // Initial data load
            loadRiskSummary();
            loadAnimalList();
            
            // Check for barangay anomalies after a short delay
            setTimeout(() => {
                checkBarangayAnomalies();
            }, 2000);
        });
        
        // Show risk modal with details
        function showRiskModal(riskLevel) {
            // Set modal title
            const titles = {
                'low': 'Low Risk Animals Details',
                'medium': 'Medium Risk Animals Details', 
                'high': 'High Risk Animals Details',
                'critical': 'Critical Risk Animals Details'
            };
            
            document.getElementById('riskModalLabel').textContent = titles[riskLevel] || 'Risk Level Details';
            
            // Show loading state
            document.getElementById('riskModalBody').innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading ${riskLevel} risk details...</p>
                </div>
            `;
            
            // Show modal
            new bootstrap.Modal(document.getElementById('riskModal')).show();
            
            // Load data based on risk level
            loadRiskModalData(riskLevel);
        }
        
        // Load data for risk modal
        function loadRiskModalData(riskLevel) {
            const riskLevelMap = {
                'low': 'Low',
                'medium': 'Medium', 
                'high': 'High',
                'critical': 'Critical'
            };
            
            const mappedLevel = riskLevelMap[riskLevel];
            
            // Get the count from the stat cards
            const countElement = document.getElementById(`${riskLevel}-risk-count`);
            const count = countElement ? parseInt(countElement.textContent) || 0 : 0;
            
            // Create data structure
            const mockData = {
                success: true,
                animals: [],
                unique_clients: 0,
                unique_barangays: 0,
                avg_risk_score: riskLevel === 'critical' ? 90 : riskLevel === 'high' ? 75 : riskLevel === 'medium' ? 50 : 25
            };
            
            // Note: High risk animals section has been removed
            // Risk modal data will be loaded from other sources
            
            // Also try to get data from symptoms-based risk analysis
            const symptomsContainer = document.getElementById('symptoms-risk-container');
            if (symptomsContainer && symptomsContainer.innerHTML.includes('animal-card')) {
                const symptomCards = symptomsContainer.querySelectorAll('.animal-card');
                symptomCards.forEach(card => {
                    const riskBadge = card.querySelector('.risk-badge');
                    if (riskBadge) {
                        const riskText = riskBadge.textContent.toLowerCase();
                        const riskScore = parseInt(riskText.replace('%', '')) || 0;
                        
                        // Check if this card matches the risk level we're looking for
                        let matchesRiskLevel = false;
                        if (riskLevel === 'low' && (riskText.includes('low') || (riskScore > 0 && riskScore < 50))) {
                            matchesRiskLevel = true;
                        } else if (riskLevel === 'medium' && (riskText.includes('medium') || (riskScore >= 50 && riskScore < 75))) {
                            matchesRiskLevel = true;
                        } else if (riskLevel === 'high' && (riskText.includes('high') || (riskScore >= 75 && riskScore < 90))) {
                            matchesRiskLevel = true;
                        } else if (riskLevel === 'critical' && (riskText.includes('critical') || riskScore >= 90)) {
                            matchesRiskLevel = true;
                        }
                        
                        if (matchesRiskLevel) {
                            const animalData = extractAnimalDataFromCard(card);
                            if (animalData) {
                                mockData.animals.push(animalData);
                            }
                        }
                    }
                });
            }
            
            // If still no animals found, try to get data from the main page content
            if (mockData.animals.length === 0) {
                // Try to extract data from the main page content
                const pageContent = document.body.innerHTML;
                
                // Look for animal data in the page content
                const animalMatches = pageContent.match(/<div[^>]*class="[^"]*animal-card[^"]*"[^>]*>[\s\S]*?<\/div>/g);
                if (animalMatches) {
                    animalMatches.forEach(html => {
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = html;
                        const card = tempDiv.firstElementChild;
                        
                        if (card) {
                            const riskBadge = card.querySelector('.risk-badge');
                            if (riskBadge) {
                                const riskText = riskBadge.textContent.toLowerCase();
                                const riskScore = parseInt(riskText.replace('%', '')) || 0;
                                
                                // Check if this matches the risk level
                                let matchesRiskLevel = false;
                                if (riskLevel === 'low' && (riskText.includes('low') || (riskScore > 0 && riskScore < 50))) {
                                    matchesRiskLevel = true;
                                } else if (riskLevel === 'medium' && (riskText.includes('medium') || (riskScore >= 50 && riskScore < 75))) {
                                    matchesRiskLevel = true;
                                } else if (riskLevel === 'high' && (riskText.includes('high') || (riskScore >= 75 && riskScore < 90))) {
                                    matchesRiskLevel = true;
                                } else if (riskLevel === 'critical' && (riskText.includes('critical') || riskScore >= 90)) {
                                    matchesRiskLevel = true;
                                }
                                
                                if (matchesRiskLevel) {
                                    const animalData = extractAnimalDataFromCard(card);
                                    if (animalData) {
                                        mockData.animals.push(animalData);
                                    }
                                }
                            }
                        }
                    });
                }
            }
            
            // Try to get data from symptoms API
            fetch('get_symptoms_risk_data.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.risk_categories && data.risk_categories[mappedLevel]) {
                        // Use real symptoms data
                        const symptomsData = {
                            success: true,
                            animals: data.risk_categories[mappedLevel].map(animal => ({
                                animal_id: animal.request_id,
                                animal_name: animal.species,
                                animal_type: animal.species,
                                client_name: animal.client_name,
                                barangay: animal.barangay,
                                risk_level: animal.risk_level,
                                risk_score: animal.risk_score,
                                assessment_date: animal.request_date,
                                symptoms: animal.symptoms_array || [animal.symptoms],
                                source: 'symptoms'
                            })),
                            unique_clients: new Set(data.risk_categories[mappedLevel].map(a => a.client_id)).size,
                            unique_barangays: new Set(data.risk_categories[mappedLevel].map(a => a.barangay)).size,
                            avg_risk_score: mappedLevel === 'Critical' ? 90 : mappedLevel === 'High' ? 75 : mappedLevel === 'Medium' ? 50 : 25
                        };
                        
                        displayRiskModalContent(symptomsData, mappedLevel);
                    } else {
                        // Fallback to mock data
                        displayRiskModalContent(mockData, mappedLevel);
                    }
                })
                .catch(error => {
                    console.error('Error loading risk modal data:', error);
                    // Fallback to mock data
                    displayRiskModalContent(mockData, mappedLevel);
                });
        }
        
        // Extract animal data from existing card HTML
        function extractAnimalDataFromCard(card) {
            try {
                // Get animal ID from data attribute or generate from content
                let animalId = card.querySelector('[data-animal-id]')?.getAttribute('data-animal-id');
                if (!animalId) {
                    // Try to extract from the card content
                    const cardText = card.textContent || '';
                    const idMatch = cardText.match(/Animal ID[:\s]*(\d+)/i);
                    animalId = idMatch ? idMatch[1] : 'Unknown';
                }
                
                // Get species name
                let species = card.querySelector('h6')?.textContent || 'Unknown';
                // Remove "(Symptoms)" from species name
                species = species.replace(' (Symptoms)', '').replace('(Symptoms)', '');
                
                // Get client name - try multiple selectors
                let clientName = 'Unknown';
                const clientElement = card.querySelector('.text-muted') || 
                                   card.querySelector('[class*="client"]') ||
                                   card.querySelector('small') ||
                                   card.querySelector('p');
                if (clientElement) {
                    clientName = clientElement.textContent.trim();
                    // Clean up client name (remove species prefix if present)
                    clientName = clientName.replace(/^(Swine|Carabao|Cattle|Goat|Chicken|Duck)\s*-\s*/i, '');
                }
                
                // Try to extract symptoms from the card content
                let symptoms = 'No symptoms recorded';
                const cardText = card.textContent || '';
                const symptomsMatch = cardText.match(/Symptoms[:\s]*(.+?)(?:\n|$)/i);
                if (symptomsMatch) {
                    symptoms = symptomsMatch[1].trim();
                    // Clean up extra information that might be appended
                    symptoms = symptoms.replace(/\s+(High|Medium|Low|Critical)\s+[A-Za-z\s-]+\s+\d{1,2}\/\d{1,2}\/\d{4}$/i, '');
                    symptoms = symptoms.replace(/\s+(Bacong-Montilla|Bago City|Dulao|Other Location)\s+\d{1,2}\/\d{1,2}\/\d{4}$/i, '');
                } else {
                    // Try to find symptoms in other formats
                    const altSymptomsMatch = cardText.match(/(?:Mataas na lagnat|Pagtatae|Panghihina|Kombulsyon|Hirap huminga|Pagsusuka|Pagtatae na may dugo|Pagsusuka na may dugo|Biglaang pagkamatay|Pagkaparalisa|Namamaga|Panginginig)[^.]*/i);
                    if (altSymptomsMatch) {
                        symptoms = altSymptomsMatch[0].trim();
                        // Clean up extra information
                        symptoms = symptoms.replace(/\s+(High|Medium|Low|Critical)\s+[A-Za-z\s-]+\s+\d{1,2}\/\d{1,2}\/\d{4}$/i, '');
                        symptoms = symptoms.replace(/\s+(Bacong-Montilla|Bago City|Dulao|Other Location)\s+\d{1,2}\/\d{1,2}\/\d{4}$/i, '');
                    }
                }
                
                // Get risk score - handle both percentage and text formats
                let riskScore = '0';
                const riskBadge = card.querySelector('.risk-badge');
                if (riskBadge) {
                    const badgeText = riskBadge.textContent;
                    // Check if it's a percentage (contains %)
                    if (badgeText.includes('%')) {
                        riskScore = badgeText.replace('%', '');
                    } else {
                        // If it's text like "High", "Critical", convert to percentage
                        const riskText = badgeText.toLowerCase();
                        if (riskText.includes('critical')) {
                            riskScore = '90';
                        } else if (riskText.includes('high')) {
                            riskScore = '75';
                        } else if (riskText.includes('medium')) {
                            riskScore = '50';
                        } else if (riskText.includes('low')) {
                            riskScore = '25';
                        }
                    }
                }
                
                // Get assessment date - try to find date in the card
                let assessmentDate = new Date().toLocaleDateString();
                const dateMatch = cardText.match(/(\d{1,2}\/\d{1,2}\/\d{4})/);
                if (dateMatch) {
                    assessmentDate = dateMatch[1];
                }
                
                // Try to extract barangay from the card content
                let barangay = 'Unknown';
                const barangayMatch = cardText.match(/Bacong-Montilla|Bago City|Dulao|Other Location/i);
                if (barangayMatch) {
                    barangay = barangayMatch[0];
                }
                
                return {
                    animal_id: animalId,
                    species: species,
                    client_name: clientName,
                    barangay: barangay,
                    risk_score: riskScore,
                    status: 'Active',
                    assessment_date: assessmentDate,
                    symptoms: symptoms
                };
            } catch (error) {
                console.error('Error extracting animal data:', error);
                return null;
            }
        }
        
        // Display risk modal content
        function displayRiskModalContent(data, riskLevel) {
            let html = `
                <div class="alert alert-${riskLevel === 'Critical' ? 'danger' : riskLevel === 'High' ? 'warning' : riskLevel === 'Medium' ? 'info' : 'success'}">
                    <h6><i class="fas fa-${riskLevel === 'Critical' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>${riskLevel} Risk Animals</h6>
                    <p class="mb-0">${getRiskDescription(riskLevel)}</p>
                </div>
            `;
            
            if (data.animals && data.animals.length > 0) {
                html += `
                    <h6 class="mt-4">${riskLevel} Risk Animals:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-${riskLevel === 'Critical' ? 'danger' : riskLevel === 'High' ? 'warning' : riskLevel === 'Medium' ? 'info' : 'success'}">
                                <tr>
                                    <th>Species</th>
                                    <th>Client</th>
                                    <th>Barangay</th>
                                    <th>Symptoms</th>
                                    <th>Assessment Date</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                data.animals.forEach(animal => {
                    html += `
                        <tr>
                            <td><strong>${animal.animal_name || animal.species || 'Unknown'}</strong></td>
                            <td>${animal.client_name || 'Unknown'}</td>
                            <td>${animal.barangay || 'Unknown'}</td>
                            <td><small class="text-muted">${Array.isArray(animal.symptoms) ? animal.symptoms.join(', ') : animal.symptoms || 'No symptoms recorded'}</small></td>
                            <td>${animal.assessment_date || 'N/A'}</td>
                        </tr>
                    `;
                });
                
                html += '</tbody></table></div>';
            } else {
                // Get the count from the stat card
                const countElement = document.getElementById(`${riskLevel.toLowerCase()}-risk-count`);
                const count = countElement ? parseInt(countElement.textContent) || 0 : 0;
                
                if (count > 0) {
                    // Show message that data is being processed
                    html += `
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>${riskLevel} Risk Animals</h6>
                            <p class="mb-0">There are <strong>${count}</strong> animals with ${riskLevel.toLowerCase()} risk level. Please check the main dashboard for detailed information.</p>
                        </div>
                        <div class="text-center mt-3">
                            <button class="btn btn-primary" onclick="window.location.reload()">
                                <i class="fas fa-refresh me-2"></i>Refresh Page
                            </button>
                        </div>
                    `;
                } else {
                    html += `
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>No ${riskLevel} Risk Animals Found</h6>
                            <p class="mb-0">There are currently no animals with ${riskLevel.toLowerCase()} risk level.</p>
                        </div>
                    `;
                }
            }
            
            document.getElementById('riskModalBody').innerHTML = html;
        }
        
        // Display error in risk modal
        function displayRiskModalError(message) {
            document.getElementById('riskModalBody').innerHTML = `
                <div class="alert alert-danger">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>Error Loading Data</h6>
                    <p class="mb-0">${message}</p>
                </div>
            `;
        }
        
        // Get risk level description
        function getRiskDescription(riskLevel) {
            const descriptions = {
                'Low': 'Animals with minimal health concerns requiring routine monitoring.',
                'Medium': 'Animals showing some health indicators that need regular monitoring and attention.',
                'High': 'Animals with significant health concerns requiring immediate attention and care.',
                'Critical': 'Animals with severe health issues requiring immediate emergency care and intervention.'
            };
            return descriptions[riskLevel] || 'Risk level details.';
        }
        
    </script>
</body>
</html>
