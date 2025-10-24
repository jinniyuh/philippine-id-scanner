<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get current view mode (default to clients)
$view_mode = isset($_GET['view']) ? $_GET['view'] : 'clients';

// CLIENT MANAGEMENT DATA
$sql = "
SELECT 
    c.client_id,
    c.full_name,
    c.contact_number,
    c.latitude,
    c.longitude,
    c.status,
    a.animal_type AS type,
    a.species,
    a.quantity,
    a.created_at AS transfer_date
FROM clients c
LEFT JOIN livestock_poultry a ON c.client_id = a.client_id
";

// Barangay filter whitelist
$barangays = [
    'Abuanan','Alianza','Atipuluan','Bacong-Montilla','Bagroy','Balingasag','Binubuhan','Busay','Calumangan','Caridad',
    'Don Jorge L. Araneta','Dulao','Ilijan','Lag-Asan','Ma-ao Barrio','Mailum','Malingin','Napoles','Pacol','Poblacion',
    'Sagasa','Sampinit','Tabunan','Taloc'
];
$selected_barangay = isset($_GET['barangay']) ? trim($_GET['barangay']) : '';
if ($selected_barangay !== '' && !in_array($selected_barangay, $barangays, true)) {
    // Invalid value; ignore
    $selected_barangay = '';
}

// Get client counts with error checking
$where_barangay_total = $selected_barangay !== '' ? " WHERE barangay = '" . $conn->real_escape_string($selected_barangay) . "'" : '';
$total_clients_result = $conn->query("SELECT COUNT(*) as count FROM clients" . $where_barangay_total);
if (!$total_clients_result) {
    die("Query failed: " . $conn->error);
}
$total_clients = $total_clients_result->fetch_assoc()['count'];

$where_barangay_cond = $selected_barangay !== '' ? " AND barangay = '" . $conn->real_escape_string($selected_barangay) . "'" : '';
$complied_clients_result = $conn->query("SELECT COUNT(*) as count FROM clients WHERE status = 'Complied'" . $where_barangay_cond);
if (!$complied_clients_result) {
    die("Query failed: " . $conn->error);
}
$complied_clients = $complied_clients_result->fetch_assoc()['count'];

$pending_clients_result = $conn->query("SELECT COUNT(*) as count FROM clients c 
                                        WHERE c.status = 'Pending' 
                                        AND c.client_id IN (
                                            SELECT DISTINCT client_id 
                                            FROM livestock_poultry 
                                            WHERE source = 'Disseminated'
                                        )" . $where_barangay_cond);
if (!$pending_clients_result) {
    die("Query failed: " . $conn->error);
}
$pending_clients = $pending_clients_result->fetch_assoc()['count'];

// Count clients with no disseminated animals (None status)
$none_clients_query = "SELECT COUNT(*) as count FROM clients c 
                       WHERE c.client_id NOT IN (
                           SELECT DISTINCT client_id 
                           FROM livestock_poultry 
                           WHERE source = 'Disseminated'
                       )" . $where_barangay_cond;
$none_clients_result = $conn->query($none_clients_query);
if (!$none_clients_result) {
    die("Query failed: " . $conn->error);
}
$none_clients = $none_clients_result->fetch_assoc()['count'];

// Detailed lists for modals
$all_clients_list = $conn->query("SELECT client_id, full_name, contact_number, barangay, status FROM clients" . $where_barangay_total . " ORDER BY full_name ASC");
if (!$all_clients_list) { die("Query failed: " . $conn->error); }

$complied_clients_list = $conn->query("SELECT client_id, full_name, contact_number, barangay, status FROM clients WHERE status = 'Complied'" . $where_barangay_cond . " ORDER BY full_name ASC");
if (!$complied_clients_list) { die("Query failed: " . $conn->error); }

$pending_clients_list = $conn->query("SELECT c.client_id, c.full_name, c.contact_number, c.barangay, c.status 
                                        FROM clients c 
                                        WHERE c.status = 'Pending' 
                                        AND c.client_id IN (
                                            SELECT DISTINCT client_id 
                                            FROM livestock_poultry 
                                            WHERE source = 'Disseminated'
                                        )" . $where_barangay_cond . " ORDER BY c.full_name ASC");
if (!$pending_clients_list) { die("Query failed: " . $conn->error); }

// Get clients with no disseminated animals (None status) for modal
$none_clients_list_query = "SELECT c.client_id, c.full_name, c.contact_number, c.barangay, c.status 
                           FROM clients c 
                           WHERE c.client_id NOT IN (
                               SELECT DISTINCT client_id 
                               FROM livestock_poultry 
                               WHERE source = 'Disseminated'
                           )" . $where_barangay_cond . " 
                           ORDER BY c.full_name ASC";
$none_clients_list = $conn->query($none_clients_list_query);
if (!$none_clients_list) { die("Query failed: " . $conn->error); }

// Get all clients (main table)
$clients_query = "SELECT * FROM clients" . $where_barangay_total . " ORDER BY full_name ASC";
$clients = $conn->query($clients_query);
if (!$clients) {
    die("Query failed: " . $conn->error);
}

// USER MANAGEMENT DATA
// Get user counts
$total_users_result = $conn->query("SELECT COUNT(*) as count FROM users");
$total_users = $total_users_result->fetch_assoc()['count'];

$active_users_result = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'Active'");
$active_users = $active_users_result->fetch_assoc()['count'];

$inactive_users = $total_users - $active_users;

$admin_users_result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
$admin_users = $admin_users_result->fetch_assoc()['count'];

$staff_users_result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'staff'");
$staff_users = $staff_users_result->fetch_assoc()['count'];

$active_staff_result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'staff' AND status = 'Active'");
$active_staff = $active_staff_result->fetch_assoc()['count'];

$inactive_staff = $staff_users - $active_staff;

// Get all users
$users = $conn->query("SELECT * FROM users ORDER BY username");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $view_mode === 'users' ? 'Users Management' : 'Clients Management'; ?> - Bago City Veterinary Office</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" 
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" 
          crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" 
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" 
            crossorigin=""></script>
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
        }
        
        .metric-card .metric-detail {
            font-size: 0.75rem;
            opacity: 0.8;
            font-weight: 400;
            margin-bottom: 0;
        }
        .stats-sublabel {
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            display: inline-block;
        }
        .status-complied {
            background-color: #28a745;
            color: white;
        }
        .status-pending {
            background-color: #ffc107;
            color: black;
        }
        .status-none {
            background-color: #6c757d;
            color: white;
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
        
        .clients-table {
            width: 100%;
            border-collapse: collapse;
        }
        .clients-table th {
            background-color: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
            position: sticky;
            top: 0;
            z-index: 5;
        }
        .clients-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
        }
        .clients-table tr:hover {
            background-color: #f8f9fa;
        }
        .add-btn {
            background-color: #4e73df;
            color: white;
            border-radius: 5px;
            padding: 8px 20px;
            font-weight: bold;
        }
        .action-btn {
            width: 35px;
            height: 35px;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 5px;
            color: white;
        }
        .edit-btn {
            background-color: #4e73df;
        }
        .delete-btn {
            background-color: #e74a3b;
        }
        .view-btn {
            background-color: #36b9cc;
            color: white;
            border-radius: 15px;
            padding: 5px 10px;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
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
        .modal-header {
            background-color: #6c63ff;
            color: white;
         }
         
         /* Delete confirmation modal styling */
         #deleteConfirmModal .modal-header {
             background-color: #6c63ff;
             color: white;
         }
         
         #deleteConfirmModal .modal-body {
             padding: 20px;
         }
         
         #deleteConfirmModal .btn-danger {
             background-color: #6c63ff;
             border-color: #6c63ff;
         }
         
         #deleteConfirmModal .btn-danger:hover {
             background-color: #6c63ff;
             border-color: #6c63ff;
         }
         
         /* Alert styling */
         .alert {
             margin-bottom: 20px;
             border-radius: 8px;
         }
         
                 /* Custom scrollbar styling */
        .table-responsive::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        
        .table-responsive::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        
        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* For Firefox */
        .table-responsive {
            scrollbar-width: thin;
            scrollbar-color: #c1c1c1 #f1f1f1;
        }
        
        /* Custom spacing control */
        .stats-section {
            margin-bottom: 1.5rem;
        }
        
        .search-section {
            margin-top: 1rem;
            margin-bottom: 0.55rem;
        }
        
        .table-section {
            margin-top: 0.55rem;
        }
        
        /* Validation styling */
        .form-group-with-validation {
            position: relative;
        }
        
        .validation-indicator {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
        }
        
        .validation-indicator.valid {
            color: #28a745;
        }
        
        .validation-indicator.invalid {
            color: #dc3545;
        }
        
        .validation-message {
            font-size: 12px;
            margin-top: 5px;
        }
        
        .validation-message.valid {
            color: #28a745;
        }
        
        .validation-message.invalid {
            color: #dc3545;
        }
        
        .form-control.is-valid {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
        
        .form-control.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        
        /* Success modal styling */
        .success-modal .modal-header {
            background-color: #28a745;
            color: white;
            border-bottom: none;
        }
        
        .success-modal .modal-body {
            text-align: center;
            padding: 2rem;
        }
        
        .success-modal .modal-body i {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 1rem;
        }
        
        .success-modal .modal-title {
            font-weight: 600;
        }
        
        /* Predictive Analytics Styling */
        .prediction-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #6c63ff;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .prediction-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .prediction-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .prediction-header i {
            font-size: 1.5rem;
            margin-right: 10px;
        }
        
        .prediction-header h6 {
            margin: 0;
            font-weight: 600;
            color: #495057;
        }
        
        .prediction-content .alert {
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        
        .prediction-content .alert:last-child {
            margin-bottom: 0;
        }
        
        .prediction-content ul {
            margin-bottom: 0;
            padding-left: 20px;
        }
        
        .prediction-content li {
            margin-bottom: 5px;
        }
        
        /* Anomaly Detection Styling */
        .anomaly-list .alert {
            margin-bottom: 10px;
            font-size: 0.9rem;
            border-left: 4px solid;
        }
        
        .anomaly-list .alert-warning {
            border-left-color: #ffc107;
        }
        
        .anomaly-list .alert-success {
            border-left-color: #28a745;
        }
        
        .anomaly-list .alert-secondary {
            border-left-color: #6c757d;
        }
        
        .anomaly-list .alert:last-child {
            margin-bottom: 0;
        }
        
        .anomaly-list h6 {
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        /* Card styling improvements */
        .card {
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        
        .card-header {
            border-radius: 10px 10px 0 0 !important;
            border-bottom: none;
            padding: 15px 20px;
        }
        
        .card-body {
            padding: 20px;
        }
        
        
        
        /* View Switch Button Styles */
        .view-switch-btn {
            background: #6c63ff;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(108, 99, 255, 0.3);
        }
        
        .view-switch-btn:hover {
            background: #5a52d5;
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(108, 99, 255, 0.4);
        }
        
        .view-switch-btn:active {
            transform: scale(0.95);
        }
        
        .view-switch-btn i {
            font-size: 16px;
        }
        
        /* Avatar Dropdown Button */
        .avatar-dropdown-btn {
            background: none;
            border: none;
            padding: 0;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
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
        
        .admin-name {
            margin-left: 5px;
            color: #333;
            text-decoration: none;
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
        .activity-icon.icon-add { background: #17a2b8; color: white; }
        .activity-icon.icon-edit { background: #ffc107; color: white; }
        .activity-icon.icon-delete { background: #dc3545; color: white; }
        .activity-icon.icon-view { background: #6c63ff; color: white; }
        .activity-icon.icon-approve { background: #28a745; color: white; }
        .activity-icon.icon-default { background: #6c757d; color: white; }
        
        /* Ensure modal buttons are clickable */
        #confirmDisableBtn, #confirmEnableBtn {
            pointer-events: auto !important;
            cursor: pointer !important;
            z-index: 9999 !important;
            position: relative !important;
        }
        
        .modal-footer button {
            pointer-events: auto !important;
            cursor: pointer !important;
        }
        
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
        
        /* User Management Specific Styles */
        .status-active {
            color: green;
            font-weight: bold;
        }
        .status-inactive {
            color: red;
            font-weight: bold;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
        }
        .users-table th {
            background-color: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
            position: sticky;
            top: 0;
            z-index: 5;
        }
        .users-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
        }
        .users-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .disable-btn {
            background-color: #f39c12;
        }
        .enable-btn {
            background-color: #28a745;
            color: white;
        }
        .enable-btn:hover {
            background-color: #218838;
        }
        
        /* Responsive adjustments for metric cards */
        @media (max-width: 768px) {
            .metric-card {
                height: 160px;
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
            
            .view-switch-btn {
                width: 35px;
                height: 35px;
            }
            
            .view-switch-btn i {
                font-size: 14px;
            }
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
                    <div class="d-flex align-items-center">
                        <h2 class="mb-0 me-3"><?php echo $view_mode === 'users' ? 'Users Management' : 'Clients Management'; ?></h2>
                        <button class="view-switch-btn" onclick="switchView('<?php echo $view_mode === 'clients' ? 'users' : 'clients'; ?>')" 
                                title="Switch to <?php echo $view_mode === 'clients' ? 'Users Management' : 'Clients Management'; ?>">
                            <i class="fas fa-random"></i>
                        </button>
                    </div>
                    <div class="admin-profile">
                        <!-- Avatar with Dropdown -->
                        <div class="dropdown">
                            <button class="btn btn-link avatar-dropdown-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="avatar-container">
                                    <img src="assets/default-avatar.png" alt="Admin Profile" class="avatar-img">
                                    <div class="dropdown-indicator"><i class="fas fa-chevron-down"></i></div>
                                </div>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" id="avatarDropdown">
                                <li><a class="dropdown-item" href="#" id="viewActivityLogs"><i class="fas fa-history me-2"></i>View Activity Logs</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div> 
                </div>
                
                <!-- Success/Error Messages -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                 
                <!-- Stats Cards -->
                <div class="row g-3 stats-section">
                    <?php if ($view_mode === 'clients'): ?>
                        <div class="col-md-3">
                            <div class="metric-card" role="button" data-bs-toggle="modal" data-bs-target="#allClientsModal">
                                <div class="metric-title">Registered Clients</div>
                                <div class="metric-value"><?php echo number_format($total_clients); ?></div>
                                <div class="metric-detail">Total Registered Clients</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="metric-card" role="button" data-bs-toggle="modal" data-bs-target="#compliedClientsModal">
                                <div class="metric-title">Complied Clients</div>
                                <div class="metric-value"><?php echo number_format($complied_clients); ?></div>
                                <div class="metric-detail">Total Complied Clients</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="metric-card" role="button" data-bs-toggle="modal" data-bs-target="#pendingClientsModal">
                                <div class="metric-title">Pending Clients</div>
                                <div class="metric-value"><?php echo number_format($pending_clients); ?></div>
                                <div class="metric-detail">Total Pending Clients</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="metric-card" role="button" data-bs-toggle="modal" data-bs-target="#noneClientsModal">
                                <div class="metric-title">No Animals</div>
                                <div class="metric-value"><?php echo number_format($none_clients); ?></div>
                                <div class="metric-detail">Clients with No Animals</div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="col-md-4">
                            <div class="metric-card">
                                <div class="metric-title">Total Users</div>
                                <div class="metric-value"><?php echo $total_users; ?></div>
                                <div class="metric-detail">All registered users</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="metric-card" role="button" data-bs-toggle="modal" data-bs-target="#adminUsersModal">
                                <div class="metric-title">Admin</div>
                                <div class="metric-value"><?php echo $admin_users; ?></div>
                                <div class="metric-detail">Click to view details</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="metric-card" role="button" data-bs-toggle="modal" data-bs-target="#staffUsersModal">
                                <div class="metric-title">Staff</div>
                                <div class="metric-value"><?php echo $staff_users; ?></div>
                                <div class="metric-detail">Click to view details</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                
                
                <!-- Search and Filter -->
                <div class="row mt-1 search-section">
                    <div class="col-md-6">
                        <div class="search-container">
                            <?php if ($view_mode === 'clients'): ?>
                                <input type="text" id="searchClient" placeholder="Search by name or contact number...">
                            <?php else: ?>
                                <input type="text" id="searchUser" placeholder="Search by full name, username, or status...">
                            <?php endif; ?>
                            <button type="button"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="d-flex justify-content-end gap-2">
                            <?php if ($view_mode === 'clients'): ?>
                                <select class="filter-dropdown" id="filterStatus">
                                    <option value="">Filter by Status</option>
                                    <option value="Complied">Complied</option>
                                    <option value="Pending">Pending</option>
                                    <option value="No Animals">No Animals</option>
                                </select>
                                <select class="filter-dropdown" id="filterBarangay">
                                    <option value="">Filter by Barangay</option>
                                    <?php foreach ($barangays as $b): ?>
                                        <option value="<?php echo htmlspecialchars($b); ?>" <?php echo $selected_barangay === $b ? 'selected' : ''; ?>><?php echo htmlspecialchars($b); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="add-btn" data-bs-toggle="modal" data-bs-target="#addClientModal">
                                    Add Client
                                </button>
                            <?php else: ?>
                                <select class="filter-dropdown" id="userFilterRole">
                                    <option value="">Filter by Role</option>
                                    <option value="admin">Admin</option>
                                    <option value="staff">Staff</option>
                                </select>
                                <select class="filter-dropdown" id="userFilterStatus">
                                    <option value="">Filter by Status</option>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                                <button class="add-btn" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                    Add User
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
        <div class="table-responsive mt-2 table-section">
            <?php if ($view_mode === 'clients'): ?>
                <table class="clients-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact Number</th>
                            <th>Barangay</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($clients && $clients->num_rows > 0): ?>
                            <?php while($client = $clients->fetch_assoc()): ?>
                            <tr>
                                <td><?= $client['full_name']; ?></td>
                                <td><?= $client['contact_number']; ?></td>
                                <td>
                                    <?= htmlspecialchars($client['barangay'] ?? 'N/A'); ?>
                                </td>
                                <td>
                                    <?php 
                                    // Check if client has disseminated animals
                                    $hasDisseminated = false;
                                    $checkAnimals = $conn->query("SELECT COUNT(*) as count FROM livestock_poultry WHERE client_id = " . $client['client_id'] . " AND source = 'Disseminated'");
                                    if ($checkAnimals && $checkAnimals->num_rows > 0) {
                                        $result = $checkAnimals->fetch_assoc();
                                        $hasDisseminated = $result['count'] > 0;
                                    }
                                    
                                    if ($client['status'] == 'Complied'): ?>
                                        <span class="stats-sublabel status-complied">Complied</span>
                                    <?php elseif ($client['status'] == 'Pending' && $hasDisseminated): ?>
                                        <span class="stats-sublabel status-pending">Pending</span>
                                    <?php else: ?>
                                        <span class="stats-sublabel status-none">No Animals</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="action-btn edit-btn"
                                    onclick="editClient(
                                        '<?= $client['client_id']; ?>',
                                        '<?= addslashes($client['full_name']); ?>',
                                        '<?= addslashes($client['contact_number']); ?>',
                                        '<?= addslashes($client['barangay']); ?>'
                                    )">
                                    <i class="fas fa-edit"></i>
                                </button>

                                    <button type="button" class="action-btn delete-btn"
                                        onclick="deleteClient('<?= $client['client_id']; ?>', '<?= addslashes($client['full_name']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center">No clients found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Fullname</th>
                            <th>Contact Number</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users->num_rows > 0): ?>
                            <?php while($user = $users->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $user['username']; ?></td>
                                <td><?php echo $user['name']; ?></td>
                                <td><?php echo $user['contact_number']; ?></td>
                                <td><?php echo ucfirst($user['role']); ?></td>
                                <td class="<?php echo $user['status'] == 'Active' ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $user['status']; ?>
                                </td>
                                <td><?php echo $user['created_at'] ? date('Y-m-d h:i A', strtotime($user['created_at'])) : 'Never'; ?></td>
                                <td>
                                    <button type="button" class="action-btn edit-btn" 
                                        onclick="editUser(
                                            '<?php echo htmlspecialchars($user['user_id'], ENT_QUOTES); ?>', 
                                            '<?php echo htmlspecialchars($user['username'], ENT_QUOTES); ?>', 
                                            '<?php echo htmlspecialchars($user['name'], ENT_QUOTES); ?>', 
                                            '<?php echo htmlspecialchars($user['contact_number'], ENT_QUOTES); ?>', 
                                            '<?php echo htmlspecialchars($user['role'], ENT_QUOTES); ?>', 
                                            '<?php echo htmlspecialchars($user['status'], ENT_QUOTES); ?>'
                                        )">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($user['status'] === 'Active'): ?>
                                        <form method="POST" action="admin_disable_user.php" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['user_id'], ENT_QUOTES); ?>">
                                            <button type="submit" class="action-btn disable-btn">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="admin_enable_user.php" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['user_id'], ENT_QUOTES); ?>">
                                            <button type="submit" class="action-btn enable-btn">
                                                <i class="fas fa-check-circle"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No users found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
<!-- Add Client Modal -->
<div class="modal fade" id="addClientModal" tabindex="-1" aria-labelledby="addClientModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form id="addClientForm" method="POST" action="admin_add_client.php" class="modal-content rounded-4" enctype="multipart/form-data" autocomplete="off">
      <div class="modal-header border-0" style="background-color: #6c63ff; color: white; border-radius: 15px 15px 0 0;">
        <h5 class="modal-title" id="addClientModalLabel">Add New Client</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div class="row g-3">
          <div class="col-md-6">
            <label for="add_full_name" class="form-label">Full Name</label>
            <div class="form-group-with-validation">
              <input type="text" id="add_full_name" name="full_name" class="form-control" required>
              <div class="validation-indicator" id="fullNameIndicator"></div>
            </div>
            <div class="validation-message" id="fullNameMessage"></div>
          </div>
          <div class="col-md-6">
            <label for="add_contact_number" class="form-label">Contact Number</label>
            <div class="form-group-with-validation">
              <input type="text" id="add_contact_number" name="contact_number" class="form-control" maxlength="11" required>
              <div class="validation-indicator" id="contactIndicator"></div>
            </div>
            <div class="validation-message" id="contactMessage"></div>
          </div>
          <div class="col-md-6">
            <label for="add_barangay" class="form-label">Barangay</label>
            <select id="add_barangay" name="barangay" class="form-select" required>
              <option value="" disabled selected>-- Choose Barangay --</option>
              <option value="Abuanan">Abuanan</option>
              <option value="Alianza">Alianza</option>
              <option value="Atipuluan">Atipuluan</option>
              <option value="Bacong-Montilla">Bacong-Montilla</option>
              <option value="Bagroy">Bagroy</option>
              <option value="Balingasag">Balingasag</option>
              <option value="Binubuhan">Binubuhan</option>
              <option value="Busay">Busay</option>
              <option value="Calumangan">Calumangan</option>
              <option value="Caridad">Caridad</option>
              <option value="Don Jorge L. Araneta">Don Jorge L. Araneta</option>
              <option value="Dulao">Dulao</option>
              <option value="Ilijan">Ilijan</option>
              <option value="Lag-Asan">Lag-Asan</option>
              <option value="Ma-ao">Ma-ao</option>
              <option value="Mailum">Mailum</option>
              <option value="Malingin">Malingin</option>
              <option value="Napoles">Napoles</option>
              <option value="Pacol">Pacol</option>
              <option value="Poblacion">Poblacion</option>
              <option value="Sagasa">Sagasa</option>
              <option value="Tabunan">Tabunan</option>
              <option value="Taloc">Taloc</option>
              <option value="Sampinit">Sampinit</option>
            </select>
          </div>
          <div class="col-md-6">
            <label for="add_username" class="form-label">Username</label>
            <div class="form-group-with-validation">
              <input type="text" id="add_username" name="username" class="form-control" required>
              <div class="validation-indicator" id="usernameIndicator"></div>
            </div>
            <div class="validation-message" id="usernameMessage"></div>
          </div>
          <div class="col-md-6">
            <label for="add_password" class="form-label">Password</label>
            <div class="form-group-with-validation">
              <div class="input-group">
                <input type="password" id="add_password" name="password" class="form-control" required>
                <button class="btn btn-outline-secondary" type="button" id="addTogglePassword">
                  <i class="fa fa-eye" id="addToggleIcon"></i>
                </button>
              </div>
              <div class="validation-indicator" id="passwordIndicator" style="right: 50px;"></div>
            </div>
            <div class="validation-message" id="passwordMessage"></div>
          </div>
          
        </div>
        <div class="modal-footer border-0">
          <button type="submit" class="btn btn-primary px-4" id="addClientSubmitBtn">Add Client</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
// Removed ID scanning code
</script>


<!-- Edit Client Modal -->
<div class="modal fade" id="editClientModal" tabindex="-1" aria-labelledby="editClientModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editClientForm" action="admin_update_client.php" method="post">
        <div class="modal-header">
          <h5 class="modal-title" id="editClientModalLabel">Edit Client</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="edit_client_id" name="client_id">

          <div class="mb-3">
            <label for="edit_name" class="form-label">Full Name</label>
            <input type="text" class="form-control" id="edit_name" name="full_name" required>
          </div>

          <div class="mb-3">
            <label for="edit_contact" class="form-label">Contact Number</label>
            <input type="text" class="form-control" id="edit_contact_client" name="contact_number" required>
          </div>

          <div class="mb-3">
            <label for="edit_address" class="form-label">Barangay</label>
            <select class="form-select" id="edit_address" name="barangay" required>
              <option value="" disabled selected>-- Choose Barangay --</option>
              <option value="Abuanan">Abuanan</option>
              <option value="Alianza">Alianza</option>
              <option value="Atipuluan">Atipuluan</option>
              <option value="Bacong-Montilla">Bacong-Montilla</option>
              <option value="Bagroy">Bagroy</option>
              <option value="Balingasag">Balingasag</option>
              <option value="Binubuhan">Binubuhan</option>
              <option value="Busay">Busay</option>
              <option value="Calumangan">Calumangan</option>
              <option value="Caridad">Caridad</option>
              <option value="Don Jorge L. Araneta">Don Jorge L. Araneta</option>
              <option value="Dulao">Dulao</option>
              <option value="Ilijan">Ilijan</option>
              <option value="Lag-Asan">Lag-Asan</option>
              <option value="Ma-ao">Ma-ao</option>
              <option value="Mailum">Mailum</option>
              <option value="Malingin">Malingin</option>
              <option value="Napoles">Napoles</option>
              <option value="Pacol">Pacol</option>
              <option value="Poblacion">Poblacion</option>
              <option value="Sagasa">Sagasa</option>
              <option value="Tabunan">Tabunan</option>
              <option value="Taloc">Taloc</option>
              <option value="Sampinit">Sampinit</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

    <!-- All Clients Modal -->
    <div class="modal fade" id="allClientsModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">All Registered Clients</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Barangay</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                <?php if ($all_clients_list && $all_clients_list->num_rows > 0): ?>
                    <?php while($row = $all_clients_list->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                        <td><?php echo htmlspecialchars($row['barangay'] ?? 'N/A'); ?></td>
                        <td>
                            <?php 
                            // Check if client has disseminated animals (same logic as main table)
                            $hasDisseminated = false;
                            $checkAnimals = $conn->query("SELECT COUNT(*) as count FROM livestock_poultry WHERE client_id = " . $row['client_id'] . " AND source = 'Disseminated'");
                            if ($checkAnimals && $checkAnimals->num_rows > 0) {
                                $result = $checkAnimals->fetch_assoc();
                                $hasDisseminated = $result['count'] > 0;
                            }
                            
                            if ($row['status'] == 'Complied'): ?>
                                <span class="stats-sublabel status-complied">Complied</span>
                            <?php elseif ($row['status'] == 'Pending' && $hasDisseminated): ?>
                                <span class="stats-sublabel status-pending">Pending</span>
                            <?php else: ?>
                                <span class="stats-sublabel status-none">No Animals</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="text-center">No clients found</td></tr>
                <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Complied Clients Modal -->
    <div class="modal fade" id="compliedClientsModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Clients with approved monthly photo submissions.</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Barangay</th>
                  </tr>
                </thead>
                <tbody>
                <?php if ($complied_clients_list && $complied_clients_list->num_rows > 0): ?>
                    <?php while($row = $complied_clients_list->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                        <td><?php echo htmlspecialchars($row['barangay'] ?? 'N/A'); ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3" class="text-center">No complied clients</td></tr>
                <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pending Clients Modal -->
    <div class="modal fade" id="pendingClientsModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Clients with disseminated animals awaiting photo submission review.</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Barangay</th>
                  </tr>
                </thead>
                <tbody>
                <?php if ($pending_clients_list && $pending_clients_list->num_rows > 0): ?>
                    <?php while($row = $pending_clients_list->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                        <td><?php echo htmlspecialchars($row['barangay'] ?? 'N/A'); ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3" class="text-center">No pending clients</td></tr>
                <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- None Status Clients Modal -->
    <div class="modal fade" id="noneClientsModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Clients with no Disseminated animals.</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Barangay</th>
                  </tr>
                </thead>
                <tbody>
                <?php if ($none_clients_list && $none_clients_list->num_rows > 0): ?>
                    <?php while($row = $none_clients_list->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                        <td><?php echo htmlspecialchars($row['barangay'] ?? 'N/A'); ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3" class="text-center">No clients with no animals</td></tr>
                <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
     
     <!-- Success Modal -->
     <div class="modal fade success-modal" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
       <div class="modal-dialog modal-dialog-centered">
         <div class="modal-content">
           <div class="modal-header">
             <h5 class="modal-title" id="successModalLabel">Success!</h5>
             <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
           </div>
           <div class="modal-body">
             <i class="fas fa-check-circle"></i>
             <h4>Client Added Successfully</h4>
             <p>The new client has been added to the system.</p>
           </div>
           <div class="modal-footer">
             <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
           </div>
         </div>
       </div>
     </div>
     
     <!-- Delete Confirmation Modal -->
     <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
       <div class="modal-dialog">
         <div class="modal-content">
           <div class="modal-header">
             <h5 class="modal-title" id="deleteConfirmModalLabel">Confirm Delete</h5>
             <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
           </div>
           <div class="modal-body">
             <p>Are you sure you want to delete client "<span id="deleteClientName"></span>"?</p>
             <p class="text-danger"><strong>This action cannot be undone and will delete all related data.</strong></p>
           </div>
           <div class="modal-footer">
             <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
             <form id="deleteClientForm" method="POST" action="admin_delete_client.php" style="display: inline;">
               <input type="hidden" id="deleteClientId" name="client_id">
               <button type="submit" class="btn btn-danger">Delete Client</button>
             </form>
           </div>
         </div>
       </div>
     </div>
     
     <!-- Add User Modal -->
     <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
         <div class="modal-dialog">
             <div class="modal-content">
                 <div class="modal-header">
                     <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                 </div>
                 <form action="admin_add_user.php" method="post">
                     <div class="modal-body">
                         <div class="mb-3">
                             <label for="username" class="form-label">Username</label>
                             <div class="form-group-with-validation">
                                 <input type="text" class="form-control" id="username" name="username" required>
                                 <span id="username-indicator" class="validation-indicator"></span>
                             </div>
                             <div id="username-message"></div>
                         </div>
                         <div class="mb-3">
                             <label for="fullname" class="form-label">Full Name</label>
                             <div class="form-group-with-validation">
                                 <input type="text" class="form-control" id="fullname" name="fullname" required>
                                 <span id="fullname-indicator" class="validation-indicator"></span>
                             </div>
                             <div id="fullname-message"></div>
                         </div>
                         <div class="mb-3">
                             <label for="contact_number" class="form-label">Contact Number</label>
                             <div class="form-group-with-validation">
                                 <input type="text" class="form-control" id="contact_number" name="contact_number" 
                                        placeholder="09XXXXXXXXX" maxlength="11" required>
                                 <span id="contact-indicator" class="validation-indicator"></span>
                             </div>
                             <div id="contact-message" class="validation-message"></div>
                         </div>
                         <div class="mb-3">
                             <label for="password" class="form-label">Password</label>
                             <div class="form-group-with-validation">
                                 <input type="password" class="form-control" id="password" name="password" required>
                                 <span id="password-indicator" class="validation-indicator"></span>
                             </div>
                             <div id="password-message" class="validation-message"></div>
                         </div>
                         <div class="mb-3">
                             <label for="role" class="form-label">Role</label>
                             <select class="form-select" id="role" name="role" required>
                                 <option value="staff">Staff</option>
                                 <option value="admin">Admin</option>
                             </select>
                         </div>
                         <div class="mb-3">
                             <label for="status" class="form-label">Status</label>
                             <select class="form-select" id="status" name="status" required>
                                 <option value="Active">Active</option>
                                 <option value="Inactive">Inactive</option>
                             </select>
                         </div>
                     </div>
                     <div class="modal-footer">
                         <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                         <button type="submit" class="btn btn-primary">Add User</button>
                     </div>
                 </form>
             </div>
         </div>
     </div>

     <!-- Edit User Modal -->
     <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
         <div class="modal-dialog">
             <div class="modal-content">
                 <div class="modal-header">
                     <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                 </div>
                 <form id="editUserForm" action="admin_update_user.php" method="post">
                     <div class="modal-body">
                         <input type="hidden" id="edit_user_id" name="user_id">
                         <div class="mb-3">
                             <label for="edit_username" class="form-label">Username</label>
                             <div class="form-group-with-validation">
                                 <input type="text" class="form-control" id="edit_username" name="username" required>
                                 <span id="edit-username-indicator" class="validation-indicator"></span>
                             </div>
                             <div id="edit-username-message"></div>
                         </div>
                         <div class="mb-3">
                             <label for="edit_fullname" class="form-label">Full Name</label>
                             <div class="form-group-with-validation">
                                 <input type="text" class="form-control" id="edit_fullname" name="fullname" required>
                                 <span id="edit-fullname-indicator" class="validation-indicator"></span>
                             </div>
                             <div id="edit-fullname-message"></div>
                         </div>
                         <div class="mb-3">
                             <label for="edit_contact" class="form-label">Contact Number</label>
                             <div class="form-group-with-validation">
                                 <input type="text" class="form-control" id="edit_contact" name="contact_number" 
                                        placeholder="09XXXXXXXXX" maxlength="11">
                                 <span id="edit-contact-indicator" class="validation-indicator"></span>
                             </div>
                             <div id="edit-contact-message"></div>
                         </div>
                         <div class="mb-3">
                             <label for="edit_role" class="form-label">Role</label>
                             <select class="form-select" id="edit_role" name="role" required>
                                 <option value="staff">Staff</option>
                                 <option value="admin">Admin</option>
                             </select>
                         </div>
                     </div>
                     <div class="modal-footer">
                         <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                         <button type="submit" class="btn btn-primary">Save Changes</button>
                     </div>
                 </form>
             </div>
         </div>
     </div>

     <!-- Admin Users Modal -->
     <div class="modal fade" id="adminUsersModal" tabindex="-1" aria-hidden="true">
         <div class="modal-dialog modal-lg">
             <div class="modal-content">
                 <div class="modal-header">
                     <h5 class="modal-title">Admin Users</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                 </div>
                 <div class="modal-body">
                     <div class="table-responsive">
                         <table class="table table-striped">
                             <thead>
                                 <tr>
                                     <th>Username</th>
                                     <th>Full Name</th>
                                     <th>Contact Number</th>
                                     <th>Status</th>
                                     <th>Date Created</th>
                                 </tr>
                             </thead>
                             <tbody>
                             <?php 
                             $admin_users_query = "SELECT * FROM users WHERE role = 'admin' ORDER BY created_at DESC";
                             $admin_users_result = $conn->query($admin_users_query);
                             if ($admin_users_result && $admin_users_result->num_rows > 0): ?>
                                 <?php while($user = $admin_users_result->fetch_assoc()): ?>
                                 <tr>
                                     <td><?php echo htmlspecialchars($user['username']); ?></td>
                                     <td><?php echo htmlspecialchars($user['name']); ?></td>
                                     <td><?php echo htmlspecialchars($user['contact_number']); ?></td>
                                     <td>
                                         <span class="badge <?php echo $user['status'] === 'Active' ? 'bg-success' : 'bg-secondary'; ?>">
                                             <?php echo $user['status']; ?>
                                         </span>
                                     </td>
                                     <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                 </tr>
                                 <?php endwhile; ?>
                             <?php else: ?>
                                 <tr><td colspan="5" class="text-center">No admin users found</td></tr>
                             <?php endif; ?>
                             </tbody>
                         </table>
                     </div>
                 </div>
             </div>
         </div>
     </div>

     <!-- Staff Users Modal -->
     <div class="modal fade" id="staffUsersModal" tabindex="-1" aria-hidden="true">
         <div class="modal-dialog modal-lg">
             <div class="modal-content">
                 <div class="modal-header">
                     <h5 class="modal-title">Staff Users</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                 </div>
                 <div class="modal-body">
                     <div class="table-responsive">
                         <table class="table table-striped">
                             <thead>
                                 <tr>
                                     <th>Username</th>
                                     <th>Full Name</th>
                                     <th>Contact Number</th>
                                     <th>Status</th>
                                     <th>Date Created</th>
                                 </tr>
                             </thead>
                             <tbody>
                             <?php 
                             $staff_users_query = "SELECT * FROM users WHERE role = 'staff' ORDER BY created_at DESC";
                             $staff_users_result = $conn->query($staff_users_query);
                             if ($staff_users_result && $staff_users_result->num_rows > 0): ?>
                                 <?php while($user = $staff_users_result->fetch_assoc()): ?>
                                 <tr>
                                     <td><?php echo htmlspecialchars($user['username']); ?></td>
                                     <td><?php echo htmlspecialchars($user['name']); ?></td>
                                     <td><?php echo htmlspecialchars($user['contact_number']); ?></td>
                                     <td>
                                         <span class="badge <?php echo $user['status'] === 'Active' ? 'bg-success' : 'bg-secondary'; ?>">
                                             <?php echo $user['status']; ?>
                                         </span>
                                     </td>
                                     <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                 </tr>
                                 <?php endwhile; ?>
                             <?php else: ?>
                                 <tr><td colspan="5" class="text-center">No staff users found</td></tr>
                             <?php endif; ?>
                             </tbody>
                         </table>
                     </div>
                 </div>
             </div>
         </div>
     </div>

     <!-- Disable User Confirmation Modal -->
     <div class="modal fade" id="disableUserModal" tabindex="-1" aria-labelledby="disableUserModalLabel" aria-hidden="true">
         <div class="modal-dialog modal-dialog-centered">
             <div class="modal-content">
                 <div class="modal-header">
                     <h5 class="modal-title" id="disableUserModalLabel">Confirm Disable User</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                 </div>
                 <div class="modal-body">
                     <div class="d-flex align-items-center mb-3">
                         <i class="fas fa-exclamation-triangle text-warning me-3" style="font-size: 2rem;"></i>
                         <div>
                             <h6 class="mb-1">Are you sure you want to disable this user?</h6>
                             <p class="mb-0 text-muted" id="disableUserName"></p>
                         </div>
                     </div>
                     <p class="text-muted mb-0">They will no longer be able to sign in to the system.</p>
                 </div>
                 <div class="modal-footer">
                     <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                     <button type="button" class="btn btn-warning" id="confirmDisableBtn">Disable User</button>
                 </div>
             </div>
         </div>
     </div>

     <!-- Enable User Confirmation Modal -->
     <div class="modal fade" id="enableUserModal" tabindex="-1" aria-labelledby="enableUserModalLabel" aria-hidden="true">
         <div class="modal-dialog modal-dialog-centered">
             <div class="modal-content">
                 <div class="modal-header">
                     <h5 class="modal-title" id="enableUserModalLabel">Confirm Enable User</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                 </div>
                 <div class="modal-body">
                     <div class="d-flex align-items-center mb-3">
                         <i class="fas fa-check-circle text-success me-3" style="font-size: 2rem;"></i>
                         <div>
                             <h6 class="mb-1">Are you sure you want to enable this user?</h6>
                             <p class="mb-0 text-muted" id="enableUserName"></p>
                         </div>
                     </div>
                     <p class="text-muted mb-0">They will be able to sign in to the system again.</p>
                 </div>
                 <div class="modal-footer">
                     <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                     <button type="button" class="btn btn-success" id="confirmEnableBtn">Enable User</button>
                 </div>
             </div>
         </div>
     </div>
     
     <!-- Action Success Modal -->
     <div class="modal fade" id="actionSuccessModal" tabindex="-1" aria-labelledby="actionSuccessModalLabel" aria-hidden="true">
         <div class="modal-dialog modal-dialog-centered">
             <div class="modal-content">
                 <div class="modal-header">
                     <h5 class="modal-title" id="actionSuccessModalLabel">Success</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                 </div>
                 <div class="modal-body">
                     <div class="d-flex align-items-center">
                         <i class="fas fa-check-circle text-success me-3" style="font-size: 2rem;"></i>
                         <div id="actionSuccessModalMessage">Action completed successfully.</div>
                     </div>
                 </div>
                 <div class="modal-footer">
                     <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
                 </div>
             </div>
         </div>
     </div>

     <!-- Action Error Modal -->
     <div class="modal fade" id="actionErrorModal" tabindex="-1" aria-labelledby="actionErrorModalLabel" aria-hidden="true">
         <div class="modal-dialog modal-dialog-centered">
             <div class="modal-content">
                 <div class="modal-header">
                     <h5 class="modal-title" id="actionErrorModalLabel">Error</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                 </div>
                 <div class="modal-body">
                     <div class="d-flex align-items-center">
                         <i class="fas fa-exclamation-circle text-danger me-3" style="font-size: 2rem;"></i>
                         <div id="actionErrorModalMessage">There was a problem processing your request.</div>
                     </div>
                 </div>
                 <div class="modal-footer">
                     <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                 </div>
             </div>
         </div>
     </div>


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
     
     <script src="https://cdn.jsdelivr.net/npm/fuse.js@6.6.2"></script>
     <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
     <script src="https://cdn.jsdelivr.net/npm/tesseract.js@4.0.2/dist/tesseract.min.js"></script>
    <script>
        // Load activity logs function
        function loadActivityLogs() {
            fetch('get_activity_logs.php')
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
        document.addEventListener('DOMContentLoaded', function() {
            const viewActivityLogsLink = document.getElementById('viewActivityLogs');
            if (viewActivityLogsLink) {
                viewActivityLogsLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation(); // Prevent dropdown from closing
                    loadActivityLogs();
                });
            }
        });

        // Show success/error modals based on server-side session messages
        <?php if (!empty($_SESSION['success'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            var msg = <?php echo json_encode($_SESSION['success']); ?>;
            var el = document.getElementById('actionSuccessModalMessage');
            if (el) { el.textContent = msg; }
            new bootstrap.Modal(document.getElementById('actionSuccessModal')).show();
        });
        <?php unset($_SESSION['success']); endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            var msg = <?php echo json_encode($_SESSION['error']); ?>;
            var el = document.getElementById('actionErrorModalMessage');
            if (el) { el.textContent = msg; }
            new bootstrap.Modal(document.getElementById('actionErrorModal')).show();
        });
        <?php unset($_SESSION['error']); endif; ?>

        // View switching functionality
        function switchView(view) {
            const url = new URL(window.location.href);
            url.searchParams.set('view', view);
            window.location.href = url.toString();
        }
        
        // Search functionality for clients
        const searchClientInput = document.getElementById('searchClient');
        if (searchClientInput) {
            searchClientInput.addEventListener('keyup', function() {
                const searchValue = this.value.toLowerCase();
                const tableRows = document.querySelectorAll('.clients-table tbody tr');
                
                tableRows.forEach(row => {
                    const name = row.cells[0].textContent.toLowerCase();
                    const contact = row.cells[1].textContent.toLowerCase();
                    
                    if (name.includes(searchValue) || contact.includes(searchValue)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
        
        // Search + filter functionality for users (mirrors clients UX)
        function filterUserTable() {
            const tableRows = document.querySelectorAll('.users-table tbody tr');
            const searchEl = document.getElementById('searchUser');
            const roleEl = document.getElementById('userFilterRole');
            const statusEl = document.getElementById('userFilterStatus');
            const searchValue = (searchEl ? searchEl.value : '').toLowerCase();
            const roleFilter = (roleEl ? roleEl.value : '').toLowerCase();
            const statusFilter = (statusEl ? statusEl.value : '').toLowerCase();

            tableRows.forEach(row => {
                const username = row.cells[0] ? row.cells[0].textContent.toLowerCase() : '';
                const fullname = row.cells[1] ? row.cells[1].textContent.toLowerCase() : '';
                const role = row.cells[3] ? row.cells[3].textContent.toLowerCase() : '';
                const status = row.cells[4] ? row.cells[4].textContent.toLowerCase() : '';

                const matchesSearch = !searchValue || username.includes(searchValue) || fullname.includes(searchValue) || role.includes(searchValue) || status.includes(searchValue);
                const matchesRole = !roleFilter || role === roleFilter;
                const matchesStatus = !statusFilter || status === statusFilter;

                row.style.display = (matchesSearch && matchesRole && matchesStatus) ? '' : 'none';
            });
        }

        const searchUserInput = document.getElementById('searchUser');
        if (searchUserInput) {
            searchUserInput.addEventListener('keyup', filterUserTable);
        }
        const userFilterRole = document.getElementById('userFilterRole');
        if (userFilterRole) {
            userFilterRole.addEventListener('change', filterUserTable);
        }
        const userFilterStatus = document.getElementById('userFilterStatus');
        if (userFilterStatus) {
            userFilterStatus.addEventListener('change', filterUserTable);
        }
        
        // Filter by status
        document.getElementById('filterStatus').addEventListener('change', function() {
            const filterValue = this.value;
            const tableRows = document.querySelectorAll('.clients-table tbody tr');
            
            if (filterValue === '') {
                tableRows.forEach(row => {
                    row.style.display = '';
                });
                return;
            }
            
            tableRows.forEach(row => {
                const status = row.cells[3].textContent.trim();
                
                if (status === filterValue) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        
        // Filter by barangay (reload page so stats/cards and modals reflect selection)
        document.getElementById('filterBarangay').addEventListener('change', function() {
            const value = encodeURIComponent(this.value);
            const url = new URL(window.location.href);
            if (value) {
                url.searchParams.set('barangay', this.value);
            } else {
                url.searchParams.delete('barangay');
            }
            window.location.href = url.toString();
        });
        

        
        
        // Function to initialize the Add Client modal
        function initAddClientModal() {
            // Reset form fields
            document.getElementById('addClientForm').reset();
            
            // Open the modal
            var addModal = new bootstrap.Modal(document.getElementById('addClientModal'));
            addModal.show();
        }
        
       function editClient(clientId, name, contact, barangay) {
    document.getElementById('edit_client_id').value = clientId;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_contact_client').value = contact;
    document.getElementById('edit_address').value = barangay;

    var editModal = new bootstrap.Modal(document.getElementById('editClientModal'));
    editModal.show();
}

function deleteClient(clientId, clientName) {
    // Set the client name and ID in the modal
    document.getElementById('deleteClientName').textContent = clientName;
    document.getElementById('deleteClientId').value = clientId;
    
    // Show the confirmation modal
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    deleteModal.show();
}


        // Password toggle for Add Client modal
  const addToggleBtn = document.getElementById('addTogglePassword');
  const addPasswordInput = document.getElementById('add_password');
  const addToggleIcon = document.getElementById('addToggleIcon');

  addToggleBtn.addEventListener('click', () => {
    if (addPasswordInput.type === 'password') {
      addPasswordInput.type = 'text';
      addToggleIcon.classList.remove('fa-eye');
      addToggleIcon.classList.add('fa-eye-slash');
    } else {
      addPasswordInput.type = 'password';
      addToggleIcon.classList.remove('fa-eye-slash');
      addToggleIcon.classList.add('fa-eye');
    }
  });

        // Validation functions
        function validateContactNumber(contact) {
            return /^09\d{9}$/.test(contact);
        }
        
        function validatePassword(password) {
            return password.length >= 8 && /[a-z]/.test(password) && /[A-Z]/.test(password);
        }
        
        function updateValidationIndicator(elementId, isValid, message) {
            const indicator = document.getElementById(elementId + 'Indicator');
            const messageEl = document.getElementById(elementId + 'Message');
            const input = document.getElementById('add_' + elementId);
            
            if (isValid) {
                indicator.innerHTML = '<i class="fas fa-check"></i>';
                indicator.className = 'validation-indicator valid';
                messageEl.textContent = message;
                messageEl.className = 'validation-message valid';
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
            } else {
                indicator.innerHTML = '<i class="fas fa-times"></i>';
                indicator.className = 'validation-indicator invalid';
                messageEl.textContent = message;
                messageEl.className = 'validation-message invalid';
                input.classList.remove('is-valid');
                input.classList.add('is-invalid');
            }
        }
        
        // Full name validation
        const fullNameInput = document.getElementById('add_full_name');
        let fullNameTimeout;
        
        fullNameInput.addEventListener('input', function() {
            clearTimeout(fullNameTimeout);
            const value = this.value.trim();
            
            if (value === '') {
                updateValidationIndicator('fullName', false, 'Full name is required');
                return;
            }
            
            // Check if it has first name and last name
            const nameParts = value.split(' ').filter(part => part.length > 0);
            if (nameParts.length < 2) {
                updateValidationIndicator('fullName', false, 'Please enter first name and last name');
                return;
            }
            
            // Check if name already exists (with debounce)
            fullNameTimeout = setTimeout(() => {
                const formData = new FormData();
                formData.append('type', 'full_name');
                formData.append('value', value);
                
                fetch('check_client_validation.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    updateValidationIndicator('fullName', data.valid, data.message);
                })
                .catch(error => {
                    console.error('Validation Error:', error);
                    // Fallback to local validation only
                    updateValidationIndicator('fullName', true, 'Full name is valid');
                });
            }, 500);
        });
        
        // Contact number validation - only allow numbers and limit to 11 digits
        const contactInput = document.getElementById('add_contact_number');
        
        contactInput.addEventListener('input', function() {
            // Remove any non-numeric characters
            this.value = this.value.replace(/\D/g, '');
            
            // Limit to 11 digits
            if (this.value.length > 11) {
                this.value = this.value.slice(0, 11);
            }
            
            const value = this.value.trim();
            
            if (value === '') {
                updateValidationIndicator('contact', false, 'Contact number is required');
                return;
            }
            
            if (!validateContactNumber(value)) {
                updateValidationIndicator('contact', false, 'Must start with 09 and be 11 digits');
                return;
            }
            
            updateValidationIndicator('contact', true, 'Contact number is valid');
        });
        
        // Username validation
        const usernameInput = document.getElementById('add_username');
        let usernameTimeout;
        
        if (usernameInput) {
            usernameInput.addEventListener('input', function() {
            clearTimeout(usernameTimeout);
            const value = this.value.trim();
            
            if (value === '') {
                updateValidationIndicator('username', false, 'Username is required');
                return;
            }
            
            // Check if username already exists (with debounce)
            usernameTimeout = setTimeout(() => {
                const formData = new FormData();
                formData.append('type', 'username');
                formData.append('value', value);
                
                fetch('check_client_validation.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    updateValidationIndicator('username', data.valid, data.message);
                })
                .catch(error => {
                    console.error('Validation Error:', error);
                    // Fallback to local validation only
                    updateValidationIndicator('username', true, 'Username is available');
                });
            }, 500);
        });
        }
        
        // Password validation
        const passwordInput = document.getElementById('add_password');
        
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
            const value = this.value;
            
            if (value === '') {
                updateValidationIndicator('password', false, 'Password is required');
                return;
            }
            
            if (!validatePassword(value)) {
                updateValidationIndicator('password', false, 'Must be 8+ characters with uppercase and lowercase');
                return;
            }
            
            updateValidationIndicator('password', true, 'Password is valid');
        });
        }
        
        // Form submission validation
        const addClientForm = document.getElementById('addClientForm');
        if (addClientForm) {
            addClientForm.addEventListener('submit', function(e) {
            const fullName = fullNameInput.value.trim();
            const contact = contactInput.value.trim();
            const username = usernameInput.value.trim();
            const password = passwordInput.value;
            
            let isValid = true;
            
            // Check required fields
            if (fullName === '') {
                updateValidationIndicator('fullName', false, 'Full name is required');
                isValid = false;
            }
            
            if (contact === '') {
                updateValidationIndicator('contact', false, 'Contact number is required');
                isValid = false;
            }
            
            if (username === '') {
                updateValidationIndicator('username', false, 'Username is required');
                isValid = false;
            }
            
            if (password === '') {
                updateValidationIndicator('password', false, 'Password is required');
                isValid = false;
            }
            
            // Check validation
            if (fullName !== '' && fullName.split(' ').filter(part => part.length > 0).length < 2) {
                updateValidationIndicator('fullName', false, 'Please enter first name and last name');
                isValid = false;
            }
            
            if (contact !== '' && !validateContactNumber(contact)) {
                updateValidationIndicator('contact', false, 'Must start with 09 and be 11 digits');
                isValid = false;
            }
            
            if (username !== '' && document.getElementById('usernameIndicator').classList.contains('invalid')) {
                isValid = false;
            }
            
            if (password !== '' && !validatePassword(password)) {
                updateValidationIndicator('password', false, 'Must be 8+ characters with uppercase and lowercase');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fix the validation errors before submitting.');
            }
        });
        }
        
        
        // Show error message if there was an error
        <?php if (isset($_SESSION['error'])): ?>
            alert('<?php echo addslashes($_SESSION['error']); ?>');
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        // User Management Functions
        function editUser(userId, username, name, contactNumber, role, status) {
            // Populate the edit modal with user data
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_fullname').value = name;
            document.getElementById('edit_contact').value = contactNumber;
            document.getElementById('edit_role').value = role;
            
            // Clear any previous validation messages
            document.getElementById('edit-username-indicator').innerHTML = '';
            document.getElementById('edit-fullname-indicator').innerHTML = '';
            document.getElementById('edit-contact-indicator').innerHTML = '';
            document.getElementById('edit-username-message').innerHTML = '';
            document.getElementById('edit-fullname-message').innerHTML = '';
            document.getElementById('edit-contact-message').innerHTML = '';
            
            // Enable submit button initially
            document.querySelector('#editUserModal button[type="submit"]').disabled = false;
            
            // Show the edit modal
            const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
            editModal.show();
        }

        function disableUser(userId, name) {
            if (!userId) return;
            
            // Set the user name in the modal
            document.getElementById('disableUserName').textContent = name;
            
            // Store the user ID for the confirm action
            document.getElementById('confirmDisableBtn').setAttribute('data-user-id', userId);
            
            // Show the modal
            const disableModal = new bootstrap.Modal(document.getElementById('disableUserModal'));
            disableModal.show();
        }

        function enableUser(userId, name) {
            if (!userId) return;
            
            // Set the user name in the modal
            document.getElementById('enableUserName').textContent = name;
            
            // Store the user ID for the confirm action
            document.getElementById('confirmEnableBtn').setAttribute('data-user-id', userId);
            
            // Show the modal
            const enableModal = new bootstrap.Modal(document.getElementById('enableUserModal'));
            enableModal.show();
        }


        // Set up event listeners when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            
            // Set up event listeners for the confirm buttons
            const confirmDisableBtn = document.getElementById('confirmDisableBtn');
            if (confirmDisableBtn) {
                confirmDisableBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const userId = this.getAttribute('data-user-id');
                    if (userId) {
                        // Preserve current page as redirect target
                        postWithHiddenForm('admin_disable_user.php', { user_id: userId, redirect: window.location.pathname + window.location.search });
                    } else {
                        console.error('No user_id found for disable action');
                    }
                });
            }
            
            const confirmEnableBtn = document.getElementById('confirmEnableBtn');
            if (confirmEnableBtn) {
                confirmEnableBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const userId = this.getAttribute('data-user-id');
                    if (userId) {
                        // Preserve current page as redirect target
                        postWithHiddenForm('admin_enable_user.php', { user_id: userId, redirect: window.location.pathname + window.location.search });
                    } else {
                        console.error('No user_id found for enable action');
                    }
                });
            }
        });

        // Make sure the disableUser and enableUser functions are properly defined
        function disableUser(userId, name) {
            if (!userId) return;
            
            // Set the user name in the modal
            const nameElement = document.getElementById('disableUserName');
            if (nameElement) nameElement.textContent = name;
            
            // Store the user ID for the confirm action
            const confirmBtn = document.getElementById('confirmDisableBtn');
            if (confirmBtn) confirmBtn.setAttribute('data-user-id', userId);
            
            // Show the modal
            const disableModal = new bootstrap.Modal(document.getElementById('disableUserModal'));
            disableModal.show();
        }

        function enableUser(userId, name) {
            if (!userId) return;
            
            // Set the user name in the modal
            const nameElement = document.getElementById('enableUserName');
            if (nameElement) nameElement.textContent = name;
            
            // Store the user ID for the confirm action
            const confirmBtn = document.getElementById('confirmEnableBtn');
            if (confirmBtn) confirmBtn.setAttribute('data-user-id', userId);
            
            // Show the modal
            const enableModal = new bootstrap.Modal(document.getElementById('enableUserModal'));
            enableModal.show();
        }

        function postWithHiddenForm(actionUrl, params) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = actionUrl;
            for (const key in params) {
                if (Object.prototype.hasOwnProperty.call(params, key)) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = params[key];
                    form.appendChild(input);
                }
            }
            document.body.appendChild(form);
            form.submit();
        }

</script>

</body>
</html>