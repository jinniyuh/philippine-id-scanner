<?php
session_start();
include 'includes/conn.php';

// Check admin session
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Date filters
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate   = $_GET['end_date'] ?? date('Y-m-t');

// If the selected range has no transaction rows, fall back to last 90 days ending at latest record
$minmaxRes = $conn->query("SELECT 
    DATE(MIN(COALESCE(issued_date, request_date))) AS min_d,
    DATE(MAX(COALESCE(issued_date, request_date))) AS max_d,
    SUM(CASE WHEN COALESCE(issued_date, request_date) BETWEEN '$startDate' AND '$endDate' THEN 1 ELSE 0 END) AS cnt_in_range
  FROM transactions");
if ($minmaxRes && ($mm = $minmaxRes->fetch_assoc())) {
    if ((int)$mm['cnt_in_range'] === 0 && !empty($mm['max_d'])) {
        $endDate = $mm['max_d'];
        $startDate = date('Y-m-d', strtotime($endDate . ' -89 days'));
    }
}

// Get current month/year for quick filters
$currentMonth = date('Y-m');
$lastMonth = date('Y-m', strtotime('-1 month'));
$currentYear = date('Y');
$lastYear = date('Y', strtotime('-1 year'));

// --- Fetch Data for Charts Only ---

// Top Medicines (with date filter)
$topMedicines = [];
$res = $conn->query("SELECT p.name, SUM(t.quantity) as total_dispensed FROM transactions t JOIN pharmaceuticals p ON t.pharma_id = p.pharma_id WHERE t.status IN ('Approved','Issued') AND COALESCE(t.issued_date, t.request_date) BETWEEN '$startDate' AND '$endDate' GROUP BY t.pharma_id ORDER BY total_dispensed DESC LIMIT 5");
if($res) while($row=$res->fetch_assoc()) $topMedicines[]=$row;

// Summary Statistics
$summaryStats = [];
// Total transactions
$res = $conn->query("SELECT COUNT(*) as total FROM transactions WHERE COALESCE(issued_date, request_date) BETWEEN '$startDate' AND '$endDate'");
if($res) $summaryStats['total_transactions'] = $res->fetch_assoc()['total'];

// Total medicines dispensed
$res = $conn->query("SELECT SUM(quantity) as total FROM transactions WHERE status IN ('Approved','Issued') AND COALESCE(issued_date, request_date) BETWEEN '$startDate' AND '$endDate'");
if($res) $summaryStats['total_medicines'] = $res->fetch_assoc()['total'] ?? 0;

// Total clients served
$res = $conn->query("SELECT COUNT(DISTINCT client_id) as total FROM transactions WHERE COALESCE(issued_date, request_date) BETWEEN '$startDate' AND '$endDate'");
if($res) $summaryStats['total_clients'] = $res->fetch_assoc()['total'];

// Average transaction value
$res = $conn->query("SELECT AVG(quantity) as avg FROM transactions WHERE status IN ('Approved','Issued') AND COALESCE(issued_date, request_date) BETWEEN '$startDate' AND '$endDate'");
if($res) $summaryStats['avg_quantity'] = round($res->fetch_assoc()['avg'] ?? 0, 2);

// Monthly trend data for the selected period
$monthlyTrend = [];
$res = $conn->query("SELECT DATE_FORMAT(COALESCE(issued_date, request_date), '%Y-%m') as month, SUM(quantity) as total FROM transactions WHERE status IN ('Approved','Issued') AND COALESCE(issued_date, request_date) BETWEEN '$startDate' AND '$endDate' GROUP BY month ORDER BY month ASC");
if($res) while($row=$res->fetch_assoc()) $monthlyTrend[$row['month']] = (int)$row['total'];

// Client activity by barangay
$barangayStats = [];
$res = $conn->query("SELECT c.barangay, COUNT(DISTINCT t.client_id) as client_count, SUM(t.quantity) as total_quantity FROM transactions t JOIN clients c ON t.client_id = c.client_id WHERE COALESCE(t.issued_date, t.request_date) BETWEEN '$startDate' AND '$endDate' GROUP BY c.barangay ORDER BY total_quantity DESC LIMIT 10");
if($res) while($row=$res->fetch_assoc()) $barangayStats[] = $row;

// Medicine category breakdown
$categoryStats = [];
$res = $conn->query("SELECT p.category, SUM(t.quantity) as total FROM transactions t JOIN pharmaceuticals p ON t.pharma_id = p.pharma_id WHERE t.status IN ('Approved','Issued') AND COALESCE(t.issued_date, t.request_date) BETWEEN '$startDate' AND '$endDate' GROUP BY p.category ORDER BY total DESC");
if($res) while($row=$res->fetch_assoc()) $categoryStats[] = $row;

// Trend Data (12 months)
$trendMonths = [];
$trendData = [];
for($i=11;$i>=0;$i--){ 
    $monthKey = date('Y-m',strtotime("-$i months")); 
    $trendMonths[] = date('M Y',strtotime("-$i months")); 
    $trendData[$monthKey] = 0;
}
$res = $conn->query("SELECT DATE_FORMAT(COALESCE(issued_date, request_date), '%Y-%m') as month, SUM(quantity) as total FROM transactions WHERE status IN ('Approved','Issued') AND COALESCE(issued_date, request_date) >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY month ORDER BY month ASC");
if($res) while($row=$res->fetch_assoc()) $trendData[$row['month']] = (int)$row['total'];
$trendDataOrdered = [];
foreach($trendMonths as $label){ $key = date('Y-m',strtotime($label)); $trendDataOrdered[] = $trendData[$key]??0; }

// Livestock & Poultry
$livestockCount = 0; $poultryCount = 0;
$res = $conn->query("SELECT animal_type,SUM(quantity) as total FROM livestock_poultry GROUP BY animal_type");
if($res) while($row=$res->fetch_assoc()){
    if($row['animal_type']=='Livestock') $livestockCount=(int)$row['total'];
    if($row['animal_type']=='Poultry') $poultryCount=(int)$row['total'];
}

// Status Breakdown (excluding Issued)
$statusBreakdown = ['Pending'=>0,'Approved'=>0];
$res = $conn->query("SELECT status, COUNT(*) as total FROM transactions WHERE status IN ('Pending', 'Approved') GROUP BY status");
if($res) while($row=$res->fetch_assoc()) $statusBreakdown[$row['status']] = (int)$row['total'];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Reports - Bago City Veterinary Office</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Google Fonts for Aesthetic -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
body { background-color: #f4f6fb; font-family: 'Poppins', Arial, sans-serif; }
body { background-color: #6c63ff; font-family: Arial, sans-serif; }
.container-fluid { padding-left: 0; padding-right: 0; overflow-x: hidden; }
.wrapper { display: flex; align-items: flex-start; }
/* Sidebar width fix for main-content */
.admin-sidebar {
    min-width: 250px;
    max-width: 250px;
    width: 250px;
    background: #fff;
    min-height: 100vh;
    z-index: 10;
    box-shadow: 2px 0 12px rgba(108,99,255,0.07);
}
@media (max-width: 1200px) {
    .admin-sidebar {
        min-width: 0;
        max-width: none;
        width: 100%;
        position: static;
        box-shadow: none;
    }
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
@media (max-width: 1200px) {
    .main-content { 
        margin-left: 0 !important; 
        max-width: 100vw;
        border-radius: 0;
        padding-left: 10px;
        padding-right: 10px;
    }
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
.admin-header h2 { margin: 0; font-weight: bold; color: #000000; }
.page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
.charts-container { display:flex; gap:28px; margin-bottom:28px; flex-wrap:wrap; }
.chart-card { flex:1; min-width:320px; max-width:480px; border:0; border-radius:20px; padding:24px 20px 18px 20px; margin-bottom:20px; background: linear-gradient(135deg, #f7f7ff 60%, #e9e6ff 100%); box-shadow: 0 2px 12px rgba(108,99,255,0.07); }
.chart-card canvas { background: transparent; }

/* Summary Stats Cards */
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
    height: 150px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    cursor: pointer;
}
.metric-card:hover {
    transform: translateY(-6px) scale(1.015);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.13);
}
.metric-card::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.13), transparent);
    transition: left 0.5s;
}
.metric-card:hover::after {
    left: 100%;
}
.metric-card .metric-title {
    font-size: 1rem;
    font-weight: 500;
    opacity: 0.9;
    margin-bottom: 15px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.metric-card .metric-value {
    font-size: 2.2rem;
    font-weight: 700;
    margin-bottom: 12px;
    line-height: 1;
    color: white;
}
.metric-card .metric-detail {
    font-size: 0.75rem;
    opacity: 0.8;
    font-weight: 400;
    margin-bottom: 0;
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

/* Date Filter Section */
.date-filter-section { background: #f8f9fa; border-radius: 18px; padding: 28px; margin-bottom: 28px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); }
.date-filter-row { display: flex; gap: 22px; align-items: end; flex-wrap: wrap; }
.date-input-group { flex: 1; min-width: 210px; }
.quick-filter-buttons { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 10px; }
.quick-filter-btn { padding: 9px 18px; border: 2px solid #e9ecef; background: white; border-radius: 9px; color: #6c757d; font-size: 1rem; transition: all 0.3s ease; cursor: pointer; font-weight: 500; }
.quick-filter-btn:hover, .quick-filter-btn.active { background: #6c63ff; color: white; border-color: #6c63ff; }

/* Performance Metrics */
.performance-metrics { padding: 20px 0 0 0; }
.metric-item { display: flex; justify-content: space-between; align-items: center; padding: 13px 0; border-bottom: 1px solid #f0f0f0; }
.metric-item:last-child { border-bottom: none; }
.metric-label { color: #7f8c8d; font-size: 1rem; font-weight: 600; }
.metric-value { color: #2c3e50; font-size: 1.25rem; font-weight: 700; }

/* Quick Insights */
.quick-insights { padding: 20px 0 0 0; }
.insight-item { display: flex; align-items: center; gap: 14px; padding: 11px 0; border-bottom: 1px solid #f0f0f0; }
.insight-item:last-child { border-bottom: none; }
.insight-item i { font-size: 1.2rem; width: 22px; }
.insight-item span { color: #2c3e50; font-size: 1rem; font-weight: 500; }

/* Loading Spinner */
.loading-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.93); display: none; align-items: center; justify-content: center; z-index: 9999; }
.spinner { width: 54px; height: 54px; border: 6px solid #f3f3f3; border-top: 6px solid #6c63ff; border-radius: 50%; animation: spin 1s linear infinite; }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

/* Scrollbar styling */
.main-content::-webkit-scrollbar { width: 8px; background: transparent; }
.main-content::-webkit-scrollbar-thumb { background: #bdbdbd; border-radius: 8px; }
.main-content::-webkit-scrollbar-track { background: transparent; }
.main-content { scrollbar-width: thin; scrollbar-color: #bdbdbd transparent; }

@media (max-width: 1200px) {
    .main-content { margin-left: 0 !important; }
    .charts-container { flex-direction: column; gap: 18px; }
    .date-filter-row { flex-direction: column; align-items: stretch; }
    .export-buttons { justify-content: center; }
    .metric-card {
        margin-bottom: 15px;
        padding: 15px 12px;
    }
    .metric-card .metric-value {
        font-size: 1.8rem;
    }
    .metric-card .metric-title {
        font-size: 0.9rem;
        margin-bottom: 12px;
    }
    .metric-card .metric-detail {
        font-size: 0.7rem;
        margin-bottom: 0;
    }
}
</style>
</head>
<body>
<div class="container-fluid">
    <div class="wrapper">
        <div class="sidebar">
            <?php include 'includes/admin_sidebar.php'; ?>
        </div>
        <div class="main-content">
            <div class="admin-header">
                <h2><i class="fas fa-chart-bar me-2" style="color:#6c63ff;"></i>Reports & Analytics</h2>
                <div class="btn-group">
                    <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-print"></i> Print Reports
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="#" onclick="openReportModal('print_farmers_served.php','Farmers Served'); return false;">
                                <i class="fas fa-users me-2"></i> Farmers Served
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" onclick="openReportModal('print_livestock_disseminated.php','Livestock Disseminated'); return false;">
                                <i class="fas fa-drumstick-bite me-2"></i> Livestock Disseminated
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" onclick="openReportModal('print_poultry.php','Poultry'); return false;">
                                <i class="fas fa-egg me-2"></i> Poultry
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

    <!-- Summary Statistics -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="metric-card">
                <div class="metric-title">Total Transactions</div>
                <div class="metric-value"><?= number_format($summaryStats['total_transactions'] ?? 0) ?></div>
                <div class="metric-detail">All processed requests</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card">
                <div class="metric-title">Medicines Dispensed</div>
                <div class="metric-value"><?= number_format($summaryStats['total_medicines'] ?? 0) ?></div>
                <div class="metric-detail">Total units distributed</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card">
                <div class="metric-title">Clients Served</div>
                <div class="metric-value"><?= number_format($summaryStats['total_clients'] ?? 0) ?></div>
                <div class="metric-detail">Unique beneficiaries</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metric-card">
                <div class="metric-title">Average Quantity</div>
                <div class="metric-value"><?= $summaryStats['avg_quantity'] ?? 0 ?></div>
                <div class="metric-detail">Average units per request</div>
            </div>
        </div>
    </div>

    <form method="GET" id="dateFilterForm">
    <div class="date-filter-section">
        <h5 class="mb-3" style="color: #2c3e50; font-weight: 600;">
            <i class="fas fa-calendar-alt me-2" style="color: #6c63ff;"></i>
            Date Range Filter
        </h5>
        <div class="date-filter-row">
            <div class="date-input-group">
                <label class="form-label fw-semibold">Start Date</label>
                <input type="date" class="form-control" name="start_date" value="<?= $startDate ?>" required>
            </div>
            <div class="date-input-group">
                <label class="form-label fw-semibold">End Date</label>
                <input type="date" class="form-control" name="end_date" value="<?= $endDate ?>" required>
            </div>
            <div class="date-input-group">
                <label class="form-label fw-semibold">&nbsp;</label>
                <button type="submit" class="btn btn-primary d-block w-100">
                    <i class="fas fa-filter me-2"></i>Apply Filter
                </button>
            </div>
        </div>
    </div>
</form>

        <div class="quick-filter-buttons">
            <span class="text-muted me-3">Quick Filters:</span>
            <button type="button" class="quick-filter-btn" onclick="setDateRange('<?= $currentMonth ?>-01', '<?= $currentMonth ?>-<?= date('t') ?>', this)">This Month</button>
            <button type="button" class="quick-filter-btn" onclick="setDateRange('<?= $lastMonth ?>-01', '<?= $lastMonth ?>-<?= date('t', strtotime($lastMonth)) ?>', this)">Last Month</button>
            <button type="button" class="quick-filter-btn" onclick="setDateRange('<?= $currentYear ?>-01-01', '<?= $currentYear ?>-12-31', this)">This Year</button>
            <button type="button" class="quick-filter-btn" onclick="setDateRange('<?= $lastYear ?>-01-01', '<?= $lastYear ?>-12-31', this)">Last Year</button>
            <button type="button" class="quick-filter-btn" onclick="setDateRange('<?= date('Y-m-d', strtotime('-30 days')) ?>', '<?= date('Y-m-d') ?>', this)">Last 30 Days</button>
        </div>
    
    <!-- Charts Section -->
    <div class="charts-container">
        <div class="chart-card">
            <div class="fw-semibold mb-2" style="font-size:1.13rem; color:#2c3e50;">
                <i class="fas fa-chart-line me-1"></i> Medicine Dispensed Trends (12 Months)
            </div>
            <canvas id="trendChart" height="100"></canvas>
        </div>
        <div class="chart-card">
            <div class="fw-semibold mb-2" style="font-size:1.13rem; color:#2c3e50;">
                <i class="fas fa-pills me-1"></i> Top 5 Medicines Dispensed
            </div>
            <canvas id="topMedicinesChart" height="100"></canvas>
        </div>
        <div class="chart-card">
            <div class="fw-semibold mb-2" style="font-size:1.13rem; color:#2c3e50;">
                <i class="fas fa-map-marker-alt me-1"></i> Top Barangays by Activity
            </div>
            <canvas id="barangayChart" height="100"></canvas>
        </div>
    </div>
    
    <div class="charts-container">
        <div class="chart-card">
            <div class="fw-semibold mb-2" style="font-size:1.13rem; color:#2c3e50;">
                <i class="fas fa-chart-area me-1"></i> Monthly Trend (Selected Period)
            </div>
            <canvas id="monthlyTrendChart" height="100"></canvas>
        </div>
        <div class="chart-card">
            <div class="fw-semibold mb-2" style="font-size:1.13rem; color:#2c3e50;">
                <i class="fas fa-chart-pie me-1"></i> Performance Overview
            </div>
            <div class="performance-metrics">
                <div class="metric-item">
                    <div class="metric-label">Approval Rate</div>
                    <div class="metric-value">
                        <?php 
                        $total = $statusBreakdown['Pending'] + $statusBreakdown['Approved'];
                        $approvalRate = $total > 0 ? round(($statusBreakdown['Approved'] / $total) * 100, 1) : 0;
                        echo $approvalRate . '%';
                        ?>
                    </div>
                </div>
                <div class="metric-item">
                    <div class="metric-label">Avg. per Client</div>
                    <div class="metric-value">
                        <?php 
                        $avgPerClient = $summaryStats['total_clients'] > 0 ? round($summaryStats['total_medicines'] / $summaryStats['total_clients'], 1) : 0;
                        echo $avgPerClient;
                        ?>
                    </div>
                </div>
                <div class="metric-item">
                    <div class="metric-label">Most Active Month</div>
                    <div class="metric-value">
                        <?php 
                        $mostActiveMonth = !empty($monthlyTrend) ? array_keys($monthlyTrend, max($monthlyTrend))[0] : 'N/A';
                        echo $mostActiveMonth;
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="chart-card">
            <div class="fw-semibold mb-2" style="font-size:1.13rem; color:#2c3e50;">
                <i class="fas fa-info-circle me-1"></i> Quick Insights
            </div>
            <div class="quick-insights">
                <div class="insight-item">
                    <i class="fas fa-arrow-up text-success"></i>
                    <span>Peak activity in <?= !empty($monthlyTrend) ? array_keys($monthlyTrend, max($monthlyTrend))[0] : 'N/A' ?></span>
                </div>
                <div class="insight-item">
                    <i class="fas fa-users text-info"></i>
                    <span><?= number_format($summaryStats['total_clients'] ?? 0) ?> clients served</span>
                </div>
                <div class="insight-item">
                    <i class="fas fa-pills text-warning"></i>
                    <span><?= number_format($summaryStats['total_medicines'] ?? 0) ?> medicines dispensed</span>
                </div>
                <div class="insight-item">
                    <i class="fas fa-check-circle text-success"></i>
                    <span><?= $approvalRate ?>% approval rate</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="charts-container">
        <div class="chart-card">
            <div class="fw-semibold mb-2" style="font-size:1.13rem; color:#2c3e50;">
                <i class="fas fa-tags me-1"></i> Medicine Categories
            </div>
            <canvas id="categoryChart" height="100"></canvas>
        </div>
        <div class="chart-card">
            <div class="fw-semibold mb-2" style="font-size:1.13rem; color:#2c3e50;">
                <i class="fas fa-drumstick-bite me-1"></i> Livestock vs Poultry
            </div>
            <canvas id="animalPieChart" height="100"></canvas>
        </div>
        <div class="chart-card">
            <div class="fw-semibold mb-2" style="font-size:1.13rem; color:#2c3e50;">
                <i class="fas fa-tasks me-1"></i> Request Status Breakdown
            </div>
            <canvas id="statusPieChart" height="100"></canvas>
        </div>
    </div>
</div>

<!-- Report Modal -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-xl modal-dialog-scrollable">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="reportModalLabel">Report</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body p-0" style="height: 80vh;">
				<iframe id="reportFrame" src="about:blank" style="width:100%; height:100%; border:0;"></iframe>
			</div>
		</div>
	</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Chart.js global defaults for aesthetic
Chart.defaults.font.family = "'Poppins', Arial, sans-serif";
Chart.defaults.font.size = 15;
Chart.defaults.plugins.legend.labels.boxWidth = 18;
Chart.defaults.plugins.legend.labels.boxHeight = 18;
Chart.defaults.plugins.legend.labels.padding = 18;
Chart.defaults.plugins.legend.position = 'bottom';
Chart.defaults.plugins.tooltip.backgroundColor = '#fff';
Chart.defaults.plugins.tooltip.titleColor = '#6c63ff';
Chart.defaults.plugins.tooltip.bodyColor = '#222';
Chart.defaults.plugins.tooltip.borderColor = '#6c63ff';
Chart.defaults.plugins.tooltip.borderWidth = 1.2;
Chart.defaults.plugins.tooltip.titleFont = {weight: '600', size: 15};
Chart.defaults.plugins.tooltip.bodyFont = {weight: '400', size: 14};
Chart.defaults.plugins.tooltip.padding = 12;

// Medicine Dispensed Trends (Line)
new Chart(document.getElementById('trendChart').getContext('2d'),{
  type:'line',
  data:{
    labels:<?= json_encode($trendMonths) ?>,
    datasets:[{
      label:'Dispensed',
      data:<?= json_encode($trendDataOrdered) ?>,
      borderColor:'#6c63ff',
      backgroundColor:'rgba(108,99,255,0.13)',
      fill:true,
      tension:0.35,
      pointBackgroundColor:'#fff',
      pointBorderColor:'#6c63ff',
      pointRadius:5,
      pointHoverRadius:7,
      pointHoverBackgroundColor:'#6c63ff',
      pointHoverBorderColor:'#fff',
      borderWidth:3
    }]
  },
  options:{
    responsive:true,
    plugins:{
      legend:{display:false},
      title:{display:false}
    },
    scales:{
      x:{
        grid:{display:false},
        ticks:{color:'#888', font:{weight:'500'}}
      },
      y:{
        beginAtZero:true,
        grid:{color:'rgba(108,99,255,0.08)'},
        ticks:{color:'#888', font:{weight:'500'}}
      }
    }
  }
});

// Top 5 Medicines (Bar)
const barColors = [
  'rgba(108,99,255,0.8)',
  'rgba(108,99,255,0.7)',
  'rgba(108,99,255,0.6)',
  'rgba(108,99,255,0.5)',
  'rgba(108,99,255,0.4)'
];
new Chart(document.getElementById('topMedicinesChart').getContext('2d'),{
  type:'bar',
  data:{
    labels:<?= json_encode(array_column($topMedicines,'name')) ?>,
    datasets:[{
      label:'Total Dispensed',
      data:<?= json_encode(array_column($topMedicines,'total_dispensed')) ?>,
      backgroundColor: barColors,
      borderRadius: 12,
      borderSkipped: false,
      maxBarThickness: 38
    }]
  },
  options:{
    responsive:true,
    plugins:{
      legend:{display:false},
      title:{display:false},
      tooltip:{
        callbacks:{
          label: function(context){
            return ' ' + context.parsed.y + ' dispensed';
          }
        }
      }
    },
    scales:{
      x:{
        grid:{display:false},
        ticks:{color:'#27ae60', font:{weight:'600'}}
      },
      y:{
        beginAtZero:true,
        grid:{color:'rgba(39,174,96,0.08)'},
        ticks:{color:'#27ae60', font:{weight:'600'}}
      }
    }
  }
});

// Livestock vs Poultry (Pie)
new Chart(document.getElementById('animalPieChart').getContext('2d'),{
  type:'doughnut',
  data:{
    labels:['Livestock','Poultry'],
    datasets:[{
      data:[<?= $livestockCount ?>,<?= $poultryCount ?>],
      backgroundColor:['#6c63ff','#95a5a6'],
      borderColor:'#fff',
      borderWidth:2,
      hoverOffset:16
    }]
  },
  options:{
    responsive:true,
    cutout:'65%',
    plugins:{
      legend:{
        display:true,
        labels:{
          color:'#444',
          font:{weight:'600'}
        }
      },
      title:{display:false}
    }
  }
});

// Requests Status Breakdown (Pie) - Only Pending and Approved
new Chart(document.getElementById('statusPieChart').getContext('2d'),{
  type:'doughnut',
  data:{
    labels:['Pending','Approved'],
    datasets:[{
      data:[
        <?= $statusBreakdown['Pending']??0 ?>,
        <?= $statusBreakdown['Approved']??0 ?>
      ],
      backgroundColor:['#95a5a6','#6c63ff'],
      borderColor:'#fff',
      borderWidth:2,
      hoverOffset:16
    }]
  },
  options:{
    responsive:true,
    cutout:'65%',
    plugins:{
      legend:{
        display:true,
        labels:{
          color:'#444',
          font:{weight:'600'}
        }
      }, 
      
      title:{display:false}
    }
  }
});

// Monthly Trend Chart (Selected Period)
const monthlyTrendData = <?= json_encode($monthlyTrend) ?>;
const monthlyLabels = Object.keys(monthlyTrendData).map(month => {
    const date = new Date(month + '-01');
    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
});
const monthlyValues = Object.values(monthlyTrendData);

new Chart(document.getElementById('monthlyTrendChart').getContext('2d'),{
  type:'line',
  data:{
    labels: monthlyLabels,
    datasets:[{
      label:'Medicines Dispensed',
      data: monthlyValues,
      borderColor:'#6c63ff',
      backgroundColor:'rgba(108,99,255,0.13)',
      fill:true,
      tension:0.35,
      pointBackgroundColor:'#fff',
      pointBorderColor:'#6c63ff',
      pointRadius:5,
      pointHoverRadius:7,
      pointHoverBackgroundColor:'#6c63ff',
      pointHoverBorderColor:'#fff',
      borderWidth:3
    }]
  },
  options:{
    responsive:true,
    plugins:{
      legend:{display:false},
      title:{display:false}
    },
    scales:{
      x:{
        grid:{display:false},
        ticks:{color:'#888', font:{weight:'500'}}
      },
      y:{
        beginAtZero:true,
        grid:{color:'rgba(231,76,60,0.08)'},
        ticks:{color:'#888', font:{weight:'500'}}
      }
    }
  }
});

// Barangay Activity Chart
const barangayData = <?= json_encode($barangayStats) ?>;
const barangayLabels = barangayData.map(item => item.barangay);
const barangayValues = barangayData.map(item => item.total_quantity);

new Chart(document.getElementById('barangayChart').getContext('2d'),{
  type:'bar',
  data:{
    labels: barangayLabels,
    datasets:[{
      label:'Total Quantity',
      data: barangayValues,
      backgroundColor: 'rgba(108,99,255,0.8)',
      borderColor: '#6c63ff',
      borderWidth: 1,
      borderRadius: 8,
      borderSkipped: false
    }]
  },
  options:{
    responsive:true,
    plugins:{
      legend:{display:false},
      title:{display:false}
    },
    scales:{
      x:{
        grid:{display:false},
        ticks:{color:'#9b59b6', font:{weight:'600'}, maxRotation: 45}
      },
      y:{
        beginAtZero:true,
        grid:{color:'rgba(155,89,182,0.08)'},
        ticks:{color:'#9b59b6', font:{weight:'600'}}
      }
    }
  }
});

// Medicine Categories Chart
const categoryData = <?= json_encode($categoryStats) ?>;
const categoryLabels = categoryData.map(item => item.category);
const categoryValues = categoryData.map(item => item.total);

new Chart(document.getElementById('categoryChart').getContext('2d'),{
  type:'doughnut',
  data:{
    labels: categoryLabels,
    datasets:[{
      data: categoryValues,
      backgroundColor:['#6c63ff','#95a5a6','#bdc3c7','#ecf0f1','#34495e','#2c3e50'],
      borderColor:'#fff',
      borderWidth:2,
      hoverOffset:16
    }]
  },
  options:{
    responsive:true,
    cutout:'65%',
    plugins:{
      legend:{
        display:true,
        labels:{
          color:'#444',
          font:{weight:'600'}
        }
      },
      title:{display:false}
    }
  }
});

// JavaScript Functions for Enhanced Functionality
function setDateRange(startDate, endDate, btn) {
    document.querySelector('input[name="start_date"]').value = startDate;
    document.querySelector('input[name="end_date"]').value = endDate;
    
    // Update active quick filter button
    document.querySelectorAll('.quick-filter-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    
    // Auto-submit form
    document.getElementById('dateFilterForm').submit();
}

function refreshData() {
    showLoading();
    setTimeout(() => {
        window.location.reload();
    }, 1000);
}

function showLoading() {
    document.querySelector('.loading-overlay').style.display = 'flex';
}

function hideLoading() {
    document.querySelector('.loading-overlay').style.display = 'none';
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'success' ? 'success' : 'info'} alert-dismissible fade show`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(notification);
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Auto-refresh data every 5 minutes
setInterval(() => {
    if (!document.hidden) {
        refreshData();
    }
}, 300000); // 5 minutes

// Add loading overlay to body
document.body.insertAdjacentHTML('beforeend', `
    <div class="loading-overlay">
        <div class="text-center">
            <div class="spinner"></div>
            <p class="mt-3 text-muted">Loading data...</p>
        </div>
    </div>
`);

function redirectToReport(event) {
    event.preventDefault();

    const form = document.getElementById("dateFilterForm");
    const report = form.querySelector("select[name='report']").value;
    const startDate = form.querySelector("input[name='start_date']").value;
    const endDate = form.querySelector("input[name='end_date']").value;

    if (!report) {
        alert("Please select a report.");
        return;
    }

    // Redirect to the selected report with query params
    window.location.href = `${report}?start_date=${startDate}&end_date=${endDate}`;
}

function openReportModal(reportUrl, title) {
    const startDate = document.querySelector("input[name='start_date']").value;
    const endDate = document.querySelector("input[name='end_date']").value;
    const url = `${reportUrl}?start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}`;
    const iframe = document.getElementById('reportFrame');
    iframe.src = url;
    document.getElementById('reportModalLabel').textContent = title || 'Report';
    const modal = new bootstrap.Modal(document.getElementById('reportModal'));
    modal.show();
}

// Clear iframe when modal hides to free resources
document.addEventListener('DOMContentLoaded', function() {
    const reportModal = document.getElementById('reportModal');
    if (reportModal) {
        reportModal.addEventListener('hidden.bs.modal', function() {
            const iframe = document.getElementById('reportFrame');
            if (iframe) iframe.src = 'about:blank';
        });
    }
});
</script>
</body>
</html>
