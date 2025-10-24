<?php
session_start();
include 'includes/conn.php';
include 'includes/health_monitor.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$healthMonitor = new HealthMonitor($conn);

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_health_check': 
                $client_id = intval($_POST['client_id']);
                $species = $_POST['species'];
                $health_status = $_POST['health_status'];
                
                if ($healthMonitor->updateHealthCheck($client_id, $species, $health_status)) {
                    $success_message = "Health check updated successfully!";
                } else {
                    $error_message = "Failed to update health check.";
                }
                break;
                
            case 'update_vaccination':
                $client_id = intval($_POST['client_id']);
                $species = $_POST['species'];
                
                if ($healthMonitor->updateVaccination($client_id, $species)) {
                    $success_message = "Vaccination updated successfully!";
                } else {
                    $error_message = "Failed to update vaccination.";
                }
                break;
        }
    }
}

// Get health monitoring data
$summary = $healthMonitor->getHealthSummary();
$upcoming_vaccinations = $healthMonitor->getUpcomingVaccinations(30);
$overdue_vaccinations = $healthMonitor->getOverdueVaccinations();
$recent_health_checks = $healthMonitor->getRecentHealthChecks(30);
$needing_health_checks = $healthMonitor->getAnimalsNeedingHealthChecks(90);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Monitoring - Bago City Veterinary Office</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        .metric-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .metric-value {
            font-size: 2rem;
            font-weight: bold;
            color: #6c63ff;
        }
        .metric-label {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        .status-healthy { color: #28a745; }
        .status-warning { color: #ffc107; }
        .status-danger { color: #dc3545; }
        
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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
                    <h2><i class="fas fa-heartbeat me-2"></i>Health Monitoring</h2>
                    <div class="admin-profile">
                        <img src="assets/default-avatar.png" alt="Admin Profile">
                        <div>
                            <div><?php echo $_SESSION['name']; ?></div>
                        </div>
                    </div>
                </div>

                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value"><?php echo $summary['total_animals']; ?></div>
                            <div class="metric-label">Total Animals</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value status-healthy"><?php echo $summary['health_check_percentage']; ?>%</div>
                            <div class="metric-label">Health Checks</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value status-healthy"><?php echo $summary['vaccination_percentage']; ?>%</div>
                            <div class="metric-label">Vaccinations</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <div class="metric-value status-danger"><?php echo $summary['overdue_vaccinations']; ?></div>
                            <div class="metric-label">Overdue</div>
                        </div>
                    </div>
                </div>

                <!-- Overdue Vaccinations -->
                <?php if (!empty($overdue_vaccinations)): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Overdue Vaccinations</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Client</th>
                                                <th>Species</th>
                                                <th>Quantity</th>
                                                <th>Days Overdue</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($overdue_vaccinations as $vaccination): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($vaccination['client_name']); ?></td>
                                                <td><?php echo htmlspecialchars($vaccination['species']); ?></td>
                                                <td><?php echo $vaccination['quantity']; ?></td>
                                                <td><span class="badge bg-danger"><?php echo $vaccination['days_overdue']; ?> days</span></td>
                                                <td>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="update_vaccination">
                                                        <input type="hidden" name="client_id" value="<?php echo $vaccination['client_id']; ?>">
                                                        <input type="hidden" name="species" value="<?php echo htmlspecialchars($vaccination['species']); ?>">
                                                        <button type="submit" class="btn btn-sm btn-success">
                                                            <i class="fas fa-syringe me-1"></i>Mark Vaccinated
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Animals Needing Health Checks -->
                <?php if (!empty($needing_health_checks)): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0"><i class="fas fa-stethoscope me-2"></i>Animals Needing Health Checks</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Client</th>
                                                <th>Species</th>
                                                <th>Quantity</th>
                                                <th>Days Since Check</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($needing_health_checks as $animal): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($animal['client_name']); ?></td>
                                                <td><?php echo htmlspecialchars($animal['species']); ?></td>
                                                <td><?php echo $animal['quantity']; ?></td>
                                                <td><span class="badge bg-warning"><?php echo $animal['days_since_check']; ?> days</span></td>
                                                <td>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="update_health_check">
                                                        <input type="hidden" name="client_id" value="<?php echo $animal['client_id']; ?>">
                                                        <input type="hidden" name="species" value="<?php echo htmlspecialchars($animal['species']); ?>">
                                                        <input type="hidden" name="health_status" value="Healthy">
                                                        <button type="submit" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-check me-1"></i>Mark Checked
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Recent Health Checks -->
                <?php if (!empty($recent_health_checks)): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Recent Health Checks</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Client</th>
                                                <th>Species</th>
                                                <th>Quantity</th>
                                                <th>Status</th>
                                                <th>Check Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_health_checks as $check): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($check['client_name']); ?></td>
                                                <td><?php echo htmlspecialchars($check['species']); ?></td>
                                                <td><?php echo $check['quantity']; ?></td>
                                                <td><span class="badge bg-success"><?php echo htmlspecialchars($check['health_status']); ?></span></td>
                                                <td><?php echo date('M d, Y', strtotime($check['last_health_check_date'])); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Upcoming Vaccinations -->
                <?php if (!empty($upcoming_vaccinations)): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Upcoming Vaccinations (Next 30 Days)</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Client</th>
                                                <th>Species</th>
                                                <th>Quantity</th>
                                                <th>Due Date</th>
                                                <th>Days Until Due</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($upcoming_vaccinations as $vaccination): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($vaccination['client_name']); ?></td>
                                                <td><?php echo htmlspecialchars($vaccination['species']); ?></td>
                                                <td><?php echo $vaccination['quantity']; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($vaccination['next_vaccination_date'])); ?></td>
                                                <td><span class="badge bg-info"><?php echo $vaccination['days_until_vaccination']; ?> days</span></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (empty($overdue_vaccinations) && empty($needing_health_checks) && empty($recent_health_checks) && empty($upcoming_vaccinations)): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                                <h4>All Animals Are Up to Date!</h4>
                                <p class="text-muted">No health monitoring alerts at this time.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
