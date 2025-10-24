<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in as staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: login.php");
    exit();
}

// Fetch user information
if (isset($_SESSION["user_id"])) {
    $userId = $_SESSION["user_id"];
    $queryUser = "SELECT * FROM users WHERE user_id = '$userId'";
    $resultUser = mysqli_query($conn, $queryUser);
    if ($resultUser && mysqli_num_rows($resultUser) > 0) {
        $user = mysqli_fetch_assoc($resultUser);
        $staffName = isset($user['name']) ? $user['name'] : 'Staff Name';
    } else {
        $staffName = "Staff Name";
    }
} else {
    $staffName = "Staff Name";
}

// --- UPDATED STATS CARDS ---
// Total Transactions
$transactionsCount = 0;
$resultTrans = $conn->query("SELECT COUNT(*) as cnt FROM transactions");
if ($resultTrans && $row = $resultTrans->fetch_assoc()) {
    $transactionsCount = $row['cnt'];
}

// Dispensed Pharmaceuticals (sum of all dispensed pharma quantity)
$pharmaCount = 0;
$resultPharma = $conn->query("SELECT SUM(quantity) as total_dispensed FROM transactions WHERE status IN ('Approved', 'Issued')");
if ($resultPharma && $row = $resultPharma->fetch_assoc()) {
    $pharmaCount = $row['total_dispensed'] ? $row['total_dispensed'] : 0;
}

// Fetch transactions with error handling
$sql = "SELECT t.*, c.full_name AS client_name, p.name AS pharma_name, p.unit
        FROM transactions t
        JOIN clients c ON t.client_id = c.client_id
        JOIN pharmaceuticals p ON t.pharma_id = p.pharma_id
        ORDER BY t.transaction_id DESC";

$result = $conn->query($sql);
if (!$result) {
    die("Query failed: " . $conn->error);
}

// Debug: Check if we have any transactions
$transactionCount = $result->num_rows;
if ($transactionCount == 0) {
    error_log("No transactions found in database");
} else {
    // Debug: Check a sample transaction's issued_date
    $result->data_seek(0);
    $sampleRow = $result->fetch_assoc();
    error_log("Sample transaction issued_date: " . ($sampleRow['issued_date'] ?? 'NULL'));
    $result->data_seek(0); // Reset pointer
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <title>Transaction Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
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
        .wrapper {
            display: flex;
            align-items: flex-start;
        }
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            height: 100vh;
            z-index: 1000;
        }
        .main-wrapper {
                background: white;
                margin: 20px;
            margin-left: 312px;
                padding: 0 25px 25px 25px;
                border-radius: 10px;
                min-height: 600px;
                height: calc(100vh - 40px);
                flex: 1;
                display: flex;
                flex-direction: column;
            }
        .staff-header {
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
            pointer-events: auto;
            }
        .staff-header h2 {
                margin: 0;
                font-weight: bold;
            }
        .staff-header, .staff-profile {
            display: flex; 
            align-items: center; 
            justify-content: space-between;
        }
        
        .staff-profile {
            position: relative;
            z-index: 1050;
        }
        
        .staff-profile .dropdown {
            position: relative;
            z-index: 1050;
        }
        .staff-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        /* Avatar Dropdown Button */
        .avatar-dropdown-btn {
            background: none;
            border: none;
            padding: 0;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            z-index: 1050;
        }
        
        .avatar-dropdown-btn:hover {
            transform: scale(1.05);
        }
        
        .avatar-dropdown-btn:focus {
            box-shadow: none;
        }
        
        .avatar-dropdown-btn:hover {
            transform: scale(1.05);
        }
        
        .avatar-dropdown-btn:focus {
            box-shadow: none;
        }
        
        .avatar-container {
            position: relative;
            display: inline-block;
        }
        
        .avatar-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            transition: all 0.3s ease;
        }
        
        .dropdown-indicator {
            position: absolute;
            bottom: -2px;
            right: 8px;
            background: #6c63ff;
            color: white;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            border: 2px solid white;
        }
        
        .staff-name {
            margin-left: 5px;
            color: #333;
            text-decoration: none;
        }
        /* ML Insights Style Metric Cards */
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
        
        
        .metric-card .metric-title {
            font-size: 0.9rem;
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
            .sidebar {
                width: 100%;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-wrapper {
                margin-left: 20px;
                width: calc(100% - 40px);
            }
            .metric-card {
                margin-bottom: 15px;
                padding: 15px 12px;
            }
            
            
            .metric-card .metric-value {
                font-size: 1.8rem;
            }
            
            .metric-card .metric-title {
                font-size: 0.8rem;
                margin-bottom: 12px;
            }
            
            .metric-card .metric-detail {
                font-size: 0.7rem;
                margin-bottom: 0;
            }
        }
        .search-container {
            margin-bottom: 5px;
            position: relative;
        }
        .search-container input {
            width: 100%;
            padding: 10px 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
            padding-right: 45px;
        }
        .search-container button {
            position: absolute;
            right: 0;
            top: 0;
            background: #4e73df;
            border: none;
            border-radius: 0 5px 5px 0;
            width: 45px;
            height: 100%;
            color: white;
        }
        .filter-dropdown {
            padding: 8px 20px;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 14px;
            background-color: white;
            transition: border-color 0.3s ease;
            height: 42px;
        }
        .filter-dropdown:focus {
            border-color: #6c63ff;
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(108, 99, 255, 0.25);
        }
        .table-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            flex: 1;
            max-height: 60vh;
            overflow-y: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }
        .table th {
            background-color: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
            position: sticky;
            top: 0;
            z-index: 5;
        }
        .table td {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
        }
        .modal-header {
            background-color: #6c63ff;
            color: white;
        }
        
        /* Z-index fixes for modals and dropdowns */
        .modal {
            z-index: 1055 !important;
        }
        .modal-backdrop {
            z-index: 1050 !important;
        }
        .dropdown-menu {
            z-index: 1040 !important;
        }
        .main-content {
            z-index: 1;
        }
        .table-responsive {
            max-height: 400px;
            overflow-y: auto;
        }
        
        /* Activity Logs Dropdown Styles */
        .activity-logs-dropdown {
            width: 350px;
            max-height: 400px;
            overflow-y: auto;
            padding: 0;
        }
        .activity-logs-header {
            padding: 12px 16px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
            color: #495057;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .activity-item {
            display: flex;
            align-items: flex-start;
            padding: 12px 16px;
            border-bottom: 1px solid #f5f5f5;
            transition: all 0.3s ease;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-item:hover {
            background-color: #fafbff;
        }
        .activity-avatar {
            position: relative;
            margin-right: 10px;
            flex-shrink: 0;
        }
        .activity-avatar .avatar-img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.8rem;
            color: white;
        }
        .activity-icon {
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
                display: flex;
            align-items: center;
                justify-content: center;
            font-size: 7px;
            border: 2px solid white;
        }
        .activity-icon.icon-login { background: #28a745; color: white; }
        .activity-icon.icon-logout { background: #dc3545; color: white; }
        .activity-icon.icon-view { background: #6c63ff; color: white; }
        .activity-icon.icon-default { background: #6c757d; color: white; }
        .activity-content {
            flex: 1;
        }
        .activity-text {
            margin: 0;
            font-size: 13px;
            line-height: 1.3;
                color: #333;
        }
        .activity-time {
            font-size: 11px;
            color: #6c757d;
            margin-top: 2px;
            }
        </style>
</head>
<body>
  <div class="container-fluid">
    <div class="wrapper">
      <div class="sidebar">
        <?php include 'includes/staff_sidebar.php'; ?>
      </div>
            
            <div class="main-wrapper">
            <div class="staff-header">
          <h2>Transaction Management</h2>
                <div class="staff-profile">
                    <!-- Avatar with Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-link avatar-dropdown-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="avatarDropdownToggle">
                            <div class="avatar-container">
                                <img src="assets/default-avatar.png" alt="Staff Profile" class="avatar-img" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMjAiIGZpbGw9IiM2YzYzZmYiLz4KPHN2ZyB4PSIxMCIgeT0iMTAiIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIj4KPHBhdGggZD0iTTEyIDEyQzE0LjIwOTEgMTIgMTYgMTAuMjA5MSAxNiA4QzE2IDUuNzkwODYgMTQuMjA5MSA0IDEyIDRDOS43OTA4NiA0IDggNS43OTA4NiA4IDhDOCAxMC4yMDkxIDkuNzkwODYgMTIgMTIgMTJaIiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBkPSJNMTIgMTRDOC42ODYyOSAxNCA2IDE2LjY4NjMgNiAyMFYyMkgxOFYyMEMxOCAxNi42ODYzIDE1LjMxMzcgMTQgMTIgMTRaIiBmaWxsPSJ3aGl0ZSIvPgo8L3N2Zz4KPC9zdmc+';">
                                <div class="dropdown-indicator"><i class="fas fa-chevron-down"></i></div>
                            </div>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" id="avatarDropdown" style="z-index: 1060;">
                            <li><a class="dropdown-item" href="#" id="viewActivityLogs"><i class="fas fa-history me-2"></i>View Activity Logs</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
          </div>
        </div>
            </div>
            
                <!-- Stats Cards -->
                <div class="row g-3 mb-4 stats-section">
                    <div class="col-md-6">
                        <div class="metric-card" data-bs-toggle="modal" data-bs-target="#totalTransactionsModal">
                            <div class="metric-title">Total Transactions</div>
                            <div class="metric-value"><?= number_format($transactionsCount) ?></div>
                            <div class="metric-detail">Click to view details</div>
            </div>
          </div>
                    <div class="col-md-6">
                        <div class="metric-card" data-bs-toggle="modal" data-bs-target="#dispensedMedicinesModal">
                            <div class="metric-title">Dispensed Medicines</div>
                            <div class="metric-value"><?= number_format($pharmaCount) ?></div>
                            <div class="metric-detail">Click to view details</div>
            </div>
          </div>
        </div>

                <!-- Search and Filter -->
                <div class="row mt-1 search-section">
                    <div class="col-md-6">
                        <div class="search-container">
                            <input type="text" id="searchTransaction" placeholder="Search by client name, medicine...">
                            <button type="button"><i class="fas fa-search"></i></button>
                        </div>
        </div>
                    <div class="col-md-6 text-end">
                        <div class="d-flex justify-content-end gap-2">
                            <select class="filter-dropdown" id="filterMedicine">
                                <option value="">Filter by Medicine</option>
                                <?php
                                $medicines = $conn->query("SELECT DISTINCT p.name FROM pharmaceuticals p JOIN transactions t ON p.pharma_id = t.pharma_id ORDER BY p.name ASC");
                                if ($medicines && $medicines->num_rows > 0) {
                                    while($medicine = $medicines->fetch_assoc()) {
                                        echo '<option value="' . htmlspecialchars($medicine['name']) . '">' . htmlspecialchars($medicine['name']) . '</option>';
                                    }
                                }
                                ?>
          </select>
                            <select class="filter-dropdown" id="filterMonth">
                                <option value="">Filter by Month</option>
                                <option value="01">January</option>
                                <option value="02">February</option>
                                <option value="03">March</option>
                                <option value="04">April</option>
                                <option value="05">May</option>
                                <option value="06">June</option>
                                <option value="07">July</option>
                                <option value="08">August</option>
                                <option value="09">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
          </select>
                        </div>
                    </div>
        </div>
                
        <!-- Transactions Table -->
                 <div class="table-container">
                     <table class="table table-hover">
            <thead>
              <tr>
                                <th>Client</th>
                                <th>Medicine</th>
                            <th>Quantity</th>
                                <th>Unit</th>
                                <th>Issued Date</th>
                            <th>Status</th>
                        </tr>
            </thead>
             <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['client_name']); ?></td>
                                    <td><?= htmlspecialchars($row['pharma_name']); ?></td>
                                    <td><?= htmlspecialchars($row['quantity']); ?></td>
                                        <td><?= htmlspecialchars($row['unit'] ?? 'units'); ?></td>
                                        <td><?= htmlspecialchars($row['issued_date'] ? date('Y-m-d', strtotime($row['issued_date'])) : 'N/A'); ?></td>
                                    <td><span class="badge bg-success"><?= htmlspecialchars($row['status']); ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                                <tr><td colspan="6" class="text-center">No transactions found.</td></tr>
                        <?php endif; ?>
                    </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  
    <!-- Total Transactions Modal -->
    <div class="modal fade" id="totalTransactionsModal" tabindex="-1" aria-labelledby="totalTransactionsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="totalTransactionsModalLabel">
                        <i class="fas fa-list me-2"></i>All Transactions
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Medicine</th>
                                    <th>Quantity</th>
                                    <th>Issued Date</th>
                                </tr>
                            </thead>
                            <tbody id="totalTransactionsBody">
                                <!-- Content will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dispensed Medicines Modal -->
    <div class="modal fade" id="dispensedMedicinesModal" tabindex="-1" aria-labelledby="dispensedMedicinesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dispensedMedicinesModalLabel">
                        <i class="fas fa-pills me-2"></i>Dispensed Medicines Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Medicine</th>
                                    <th>Total Dispensed</th>
                                    <th>Times Dispensed</th>
                                    <th>Last Dispensed</th>
                                </tr>
                            </thead>
                            <tbody id="dispensedMedicinesBody">
                                <!-- Content will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
  
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    $(document).ready(function() {
        // Modal event handlers for loading data
        // Cache for pre-loaded data
        let transactionDataCache = {
            all: null,
            dispensed: null
        };

        // Pre-load data when page loads
        function preloadTransactionData() {
            const statuses = ['all'];
            statuses.forEach(status => {
                fetch(`get_staff_transactions.php?status=${status}&limit=50`)
                    .then(response => response.json())
                    .then(data => {
                        transactionDataCache[status] = data;
                    })
                    .catch(error => {
                        console.error(`Error pre-loading ${status} data:`, error);
                    });
            });
        }

        // Start pre-loading immediately
        preloadTransactionData();

        // Function to display cached data instantly
        function displayCachedData(data, bodyId) {
            const tbody = document.getElementById(bodyId);
            tbody.innerHTML = '';
            
            if (data.length > 0) {
                data.forEach(transaction => {
                    const row = `
                        <tr>
                            <td>${transaction.client_name}</td>
                            <td>${transaction.pharma_name}</td>
                            <td>${transaction.quantity}</td>
                            <td>${new Date(transaction.request_date).toLocaleDateString()}</td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center">No transactions found</td></tr>';
            }
        }

        $('#totalTransactionsModal').on('show.bs.modal', function () {
            if (transactionDataCache.all) {
                displayCachedData(transactionDataCache.all, 'totalTransactionsBody');
            } else {
                loadTransactionData('all', 'totalTransactionsBody');
            }
        });

        $('#dispensedMedicinesModal').on('show.bs.modal', function () {
            loadDispensedMedicinesData();
        });

        function loadTransactionData(status, bodyId) {
            const tbody = document.getElementById(bodyId);
            
            // Show loading indicator
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center">
                        <div class="d-flex justify-content-center align-items-center py-4">
                            <div class="spinner-border text-primary me-2" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <span>Loading transactions...</span>
                        </div>
                    </td>
                </tr>
            `;
            
            fetch(`get_staff_transactions.php?status=${status}&limit=50`)
                .then(response => response.json())
                .then(data => {
                    tbody.innerHTML = '';
                    
                    if (data.length > 0) {
                        data.forEach(transaction => {
                            const row = `
                                <tr>
                                    <td>${transaction.client_name}</td>
                                    <td>${transaction.pharma_name}</td>
                                    <td>${transaction.quantity}</td>
                                    <td>${new Date(transaction.request_date).toLocaleDateString()}</td>
                                </tr>
                            `;
                            tbody.innerHTML += row;
                        });
            } else {
                        tbody.innerHTML = '<tr><td colspan="4" class="text-center">No transactions found</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error loading transaction data:', error);
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="4" class="text-center text-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Error loading data. Please try again.
                            </td>
                        </tr>
                    `;
                });
        }

        function loadDispensedMedicinesData() {
            fetch('get_staff_transactions.php?status=dispensed')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('dispensedMedicinesBody');
                    tbody.innerHTML = '';
                    
                    if (data.length > 0) {
                        data.forEach(medicine => {
                            const row = `
                                <tr>
                                    <td>${medicine.pharma_name}</td>
                                    <td>${medicine.total_dispensed} units</td>
                                    <td>${medicine.times_dispensed} times</td>
                                    <td>${new Date(medicine.last_dispensed).toLocaleDateString()}</td>
                                </tr>
                            `;
                            tbody.innerHTML += row;
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="4" class="text-center">No dispensed medicines found</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('dispensedMedicinesBody').innerHTML = 
                        '<tr><td colspan="4" class="text-center text-danger">Error loading data</td></tr>';
                });
        }

        // Search functionality
        document.getElementById('searchTransaction').addEventListener('input', function() {
            applyFilters();
        });

        // Filter by medicine
        document.getElementById('filterMedicine').addEventListener('change', function() {
            applyFilters();
        });

        // Filter by month
        document.getElementById('filterMonth').addEventListener('change', function() {
            applyFilters();
        });

        // Initialize dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Bootstrap dropdown
            const avatarDropdown = new bootstrap.Dropdown(document.getElementById('avatarDropdownToggle'));
            
            // Add multiple click event listeners for better reliability
            const avatarButton = document.getElementById('avatarDropdownToggle');
            const avatarContainer = document.querySelector('.avatar-container');
            const avatarImage = document.querySelector('.avatar-img');
            const dropdownMenu = document.getElementById('avatarDropdown');
            
            if (avatarButton) {
                avatarButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    avatarDropdown.toggle();
                });
            }
            
            if (avatarContainer) {
                avatarContainer.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    avatarDropdown.toggle();
                });
            }
            
            if (avatarImage) {
                avatarImage.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    avatarDropdown.toggle();
                });
            }
            
            if (dropdownMenu) {
                dropdownMenu.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
        });

        function applyFilters() {
            const searchValue = document.getElementById('searchTransaction').value.toLowerCase();
            const selectedMedicine = document.getElementById('filterMedicine').value;
            const selectedMonth = document.getElementById('filterMonth').value;
            const tableRows = document.querySelectorAll('.table-container .table tbody tr');
            
            let totalTransactions = 0;
            let dispensedMedicines = 0;
            
            tableRows.forEach(row => {
                const cells = row.querySelectorAll('td');
                const medicineCell = cells[1] ? cells[1].textContent.trim() : '';
                const dateCell = cells[4] ? cells[4].textContent.trim() : ''; // Issued Date is column 4
                
                // Search filter
                let searchMatch = searchValue === '' || (() => {
                    let found = false;
                    cells.forEach(cell => {
                        if (cell.textContent.toLowerCase().includes(searchValue)) {
                            found = true;
                        }
                    });
                    return found;
                })();
                
                // Medicine filter
                let medicineMatch = selectedMedicine === '' || medicineCell === selectedMedicine;
                
                // Month filter
                let monthMatch = selectedMonth === '' || (() => {
                    if (selectedMonth === '') return true;
                    const month = dateCell.split('-')[1];
                    return month === selectedMonth;
                })();
                
                if (searchMatch && medicineMatch && monthMatch) {
                    row.style.display = '';
                    totalTransactions++;
                    
                    // Count dispensed medicines (column 2 has quantity)
                    const quantityCell = cells[2];
                    if (quantityCell) {
                        const quantity = parseInt(quantityCell.textContent.trim()) || 0;
                        dispensedMedicines += quantity;
                    }
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Update stats cards with filtered results
            updateStatsCards(totalTransactions, dispensedMedicines);
        }
        
        // Function to update stats cards
        function updateStatsCards(totalTransactions, dispensedMedicines) {
            const totalCard = document.querySelector('.stats-section .metric-card .metric-value');
            const dispensedCard = document.querySelectorAll('.stats-section .metric-card .metric-value')[1];
            
            if (totalCard) {
                totalCard.textContent = totalTransactions.toString();
            }
            
            if (dispensedCard) {
                dispensedCard.textContent = dispensedMedicines.toString();
            }
        }
        
        // Initialize filters on page load
        applyFilters();
    });

    // Load activity logs function
    function loadActivityLogs() {
        fetch('get_staff_activity_logs.php')
            .then(response => response.text())
            .then(data => {
                // Create the activity logs dropdown content
                const dropdownContent = `
                    <div class="activity-logs-header">
                        <i class="fas fa-arrow-left me-2" id="backToMenu" style="cursor: pointer;"></i>Activity Logs
                    </div>
                    ${data}
                `;
                document.getElementById('avatarDropdown').innerHTML = dropdownContent;
                document.getElementById('avatarDropdown').classList.add('activity-logs-dropdown');
                
                // Add back button functionality
                const backButton = document.getElementById('backToMenu');
                if (backButton) {
                    backButton.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        // Restore original dropdown menu
                        document.getElementById('avatarDropdown').innerHTML = `
                            <li><a class="dropdown-item" href="#" id="viewActivityLogs"><i class="fas fa-history me-2"></i>View Activity Logs</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        `;
                        document.getElementById('avatarDropdown').classList.remove('activity-logs-dropdown');
                        
                        // Re-attach the event listener for "View Activity Logs"
                        const newViewActivityLogsLink = document.getElementById('viewActivityLogs');
                        if (newViewActivityLogsLink) {
                            newViewActivityLogsLink.addEventListener('click', function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                loadActivityLogs();
                            });
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Error loading activity logs:', error);
                document.getElementById('avatarDropdown').innerHTML = 
                    '<div class="activity-logs-header"><i class="fas fa-history me-2"></i>Activity Logs</div><div class="text-center text-muted p-3"><i class="fas fa-exclamation-triangle me-2"></i>Error loading activity logs</div>';
            });
    }

    // Load activity logs when "View Activity Logs" is clicked
    const viewActivityLogsLink = document.getElementById('viewActivityLogs');
        if (viewActivityLogsLink) {
            viewActivityLogsLink.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation(); // Prevent dropdown from closing
                loadActivityLogs();
            });
        }
</script>

    <!-- Logout Confirmation Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-sign-out-alt text-primary me-3" style="font-size: 2rem;"></i>
                        <div>
                            <h6 class="mb-1">Are you sure you want to logout?</h6>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="logout.php" class="btn btn-primary">Logout</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>