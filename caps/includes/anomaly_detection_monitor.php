<?php
/**
 * Anomaly Detection Monitoring System
 * Real-time monitoring and management of anomaly detection
 */

session_start();
require_once 'includes/db_connection.php';
require_once 'includes/anomaly_detector.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$anomaly_detector = new AnomalyDetector($conn);
$anomaly_summary = $anomaly_detector->getAnomalySummary();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anomaly Detection Monitor - Bago City Veterinary Office</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }
        .monitor-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .monitor-card:hover {
            transform: translateY(-2px);
        }
        .anomaly-indicator {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 10px;
        }
        .critical { background-color: #dc3545; }
        .high { background-color: #fd7e14; }
        .medium { background-color: #ffc107; }
        .low { background-color: #198754; }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        .anomaly-timeline {
            max-height: 400px;
            overflow-y: auto;
        }
        .timeline-item {
            border-left: 3px solid #dee2e6;
            padding-left: 15px;
            margin-bottom: 15px;
            position: relative;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -6px;
            top: 5px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #6c757d;
        }
        .timeline-item.critical::before { background-color: #dc3545; }
        .timeline-item.high::before { background-color: #fd7e14; }
        .timeline-item.medium::before { background-color: #ffc107; }
        .timeline-item.low::before { background-color: #198754; }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-0"><i class="fas fa-shield-alt text-primary me-2"></i>Anomaly Detection Monitor</h2>
                        <p class="text-muted mb-0">Real-time monitoring of pharmaceutical transaction patterns</p>
                    </div>
                    <div>
                        <button class="btn btn-primary" onclick="refreshData()">
                            <i class="fas fa-sync-alt me-1"></i>Refresh
                        </button>
                        <button class="btn btn-outline-secondary" onclick="window.history.back()">
                            <i class="fas fa-arrow-left me-1"></i>Back
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Overview -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card monitor-card border-info">
                    <div class="card-body text-center">
                        <i class="fas fa-exclamation-triangle text-info fa-2x mb-3"></i>
                        <h5 class="card-title text-info">Total Anomalies</h5>
                        <div class="h3 mb-0" id="total-anomalies"><?php echo $anomaly_summary['total_anomalies']; ?></div>
                        <small class="text-muted">Detected patterns</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card monitor-card border-danger">
                    <div class="card-body text-center">
                        <i class="fas fa-fire text-danger fa-2x mb-3"></i>
                        <h5 class="card-title text-danger">Critical</h5>
                        <div class="h3 mb-0" id="critical-anomalies"><?php echo $anomaly_summary['critical_anomalies']; ?></div>
                        <small class="text-muted">High priority</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card monitor-card border-warning">
                    <div class="card-body text-center">
                        <i class="fas fa-exclamation-circle text-warning fa-2x mb-3"></i>
                        <h5 class="card-title text-warning">High Risk</h5>
                        <div class="h3 mb-0" id="high-anomalies"><?php echo $anomaly_summary['high_anomalies']; ?></div>
                        <small class="text-muted">Medium priority</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card monitor-card border-success">
                    <div class="card-body text-center">
                        <i class="fas fa-info-circle text-success fa-2x mb-3"></i>
                        <h5 class="card-title text-success">Medium</h5>
                        <div class="h3 mb-0" id="medium-anomalies"><?php echo $anomaly_summary['medium_anomalies']; ?></div>
                        <small class="text-muted">Low priority</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Anomaly Details -->
        <div class="row">
            <div class="col-md-8 mb-4">
                <div class="card monitor-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Detected Anomalies</h5>
                    </div>
                    <div class="card-body">
                        <div id="anomalies-container">
                            <?php if (!empty($anomaly_summary['anomalies'])): ?>
                                <?php foreach ($anomaly_summary['anomalies'] as $anomaly): ?>
                                    <div class="card mb-3 border-<?php echo $anomaly['severity'] === 'critical' ? 'danger' : ($anomaly['severity'] === 'high' ? 'warning' : 'info'); ?>">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="card-title mb-2">
                                                        <span class="anomaly-indicator <?php echo $anomaly['severity']; ?>"></span>
                                                        <?php echo $anomaly['medicine'] ?? 'Unknown Medicine'; ?>
                                                    </h6>
                                                    <p class="card-text mb-2">
                                                        <strong>Disease:</strong> <?php echo $anomaly['disease'] ?? 'Unknown'; ?><br>
                                                        <strong>Animal:</strong> <?php echo $anomaly['animal'] ?? 'Unknown'; ?><br>
                                                        <strong>Count:</strong> <?php echo $anomaly['count'] ?? 0; ?> approvals<br>
                                                        <strong>Areas:</strong> <?php echo implode(', ', $anomaly['affected_areas'] ?? []); ?>
                                                    </p>
                                                    <small class="text-muted">
                                                        Detected: <?php echo date('M j, Y H:i', strtotime($anomaly['detected_at'] ?? 'now')); ?>
                                                    </small>
                                                </div>
                                                <div class="text-end">
                                                    <span class="badge bg-<?php echo $anomaly['severity'] === 'critical' ? 'danger' : ($anomaly['severity'] === 'high' ? 'warning' : 'info'); ?> status-badge">
                                                        <?php echo strtoupper($anomaly['severity']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                                    <h5 class="text-muted">No Anomalies Detected</h5>
                                    <p class="text-muted">All pharmaceutical transaction patterns appear normal.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card monitor-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Monitoring Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>System Status</span>
                                <span class="badge bg-success">Active</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Last Check</span>
                                <span id="last-check"><?php echo date('H:i:s'); ?></span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Check Frequency</span>
                                <span>Every 5 minutes</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span>Threshold</span>
                                <span>3+ approvals</span>
                            </div>
                        </div>
                        <hr>
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary btn-sm" onclick="checkAnomalies()">
                                <i class="fas fa-search me-1"></i>Check Now
                            </button>
                            <button class="btn btn-outline-info btn-sm" onclick="viewSettings()">
                                <i class="fas fa-cog me-1"></i>Settings
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="card monitor-card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <div class="anomaly-timeline" id="activity-timeline">
                            <div class="timeline-item">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong>System Started</strong>
                                        <p class="mb-0 text-muted">Anomaly detection monitoring activated</p>
                                    </div>
                                    <small class="text-muted"><?php echo date('H:i'); ?></small>
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
        // Auto-refresh data every 30 seconds
        setInterval(refreshData, 30000);
        
        // Check for anomalies every 5 minutes
        setInterval(checkAnomalies, 5 * 60 * 1000);
        
        async function refreshData() {
            try {
                const response = await fetch('get_anomaly_detection_data.php');
                const data = await response.json();
                
                if (data.success) {
                    updateAnomalyStatus(data);
                    updateLastCheck();
                }
            } catch (error) {
                console.error('Error refreshing data:', error);
            }
        }
        
        async function checkAnomalies() {
            try {
                const response = await fetch('get_anomaly_detection_data.php');
                const data = await response.json();
                
                if (data.success) {
                    updateAnomalyStatus(data);
                    updateLastCheck();
                    
                    if (data.has_anomalies) {
                        addActivityItem('Anomaly detected', 'New anomaly pattern identified', 'warning');
                    }
                }
            } catch (error) {
                console.error('Error checking anomalies:', error);
                addActivityItem('Error', 'Failed to check for anomalies', 'danger');
            }
        }
        
        function updateAnomalyStatus(data) {
            if (data.has_anomalies) {
                document.getElementById('total-anomalies').textContent = data.anomaly_count || 0;
                document.getElementById('critical-anomalies').textContent = data.critical_anomalies || 0;
                document.getElementById('high-anomalies').textContent = data.high_anomalies || 0;
                document.getElementById('medium-anomalies').textContent = data.medium_anomalies || 0;
            } else {
                document.getElementById('total-anomalies').textContent = '0';
                document.getElementById('critical-anomalies').textContent = '0';
                document.getElementById('high-anomalies').textContent = '0';
                document.getElementById('medium-anomalies').textContent = '0';
            }
        }
        
        function updateLastCheck() {
            document.getElementById('last-check').textContent = new Date().toLocaleTimeString();
        }
        
        function addActivityItem(title, description, type) {
            const timeline = document.getElementById('activity-timeline');
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            
            const item = document.createElement('div');
            item.className = `timeline-item ${type}`;
            item.innerHTML = `
                <div class="d-flex justify-content-between">
                    <div>
                        <strong>${title}</strong>
                        <p class="mb-0 text-muted">${description}</p>
                    </div>
                    <small class="text-muted">${timeString}</small>
                </div>
            `;
            
            timeline.insertBefore(item, timeline.firstChild);
            
            // Keep only last 10 items
            while (timeline.children.length > 10) {
                timeline.removeChild(timeline.lastChild);
            }
        }
        
        function viewSettings() {
            alert('Settings feature coming soon!');
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateLastCheck();
            addActivityItem('Page Loaded', 'Anomaly detection monitor initialized', 'info');
        });
    </script>
</body>
</html>
