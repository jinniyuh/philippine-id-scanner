<?php
session_start();
include 'includes/conn.php';
include 'includes/bago_config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_barangays'])) {
        $barangays = array_filter(array_map('trim', explode("\n", $_POST['barangays'])));
        if (updateBagoBarangays($barangays)) {
            $message = "Barangay list updated successfully!";
        } else {
            $error = "Failed to update barangay list.";
        }
    }
    
    if (isset($_POST['update_config'])) {
        $configKey = $_POST['config_key'];
        $configValue = $_POST['config_value'];
        
        if (updateBagoConfig($configKey, $configValue)) {
            $message = "Configuration updated successfully!";
        } else {
            $error = "Failed to update configuration.";
        }
    }
    
    if (isset($_POST['update_error_messages'])) {
        $errorMessages = [
            'not_bago_resident' => $_POST['not_bago_resident'],
            'wrong_province' => $_POST['wrong_province'],
            'invalid_barangay' => $_POST['invalid_barangay'],
            'name_mismatch' => $_POST['name_mismatch'],
            'success_verified' => $_POST['success_verified']
        ];
        
        if (updateBagoConfig('error_messages', $errorMessages)) {
            $message = "Error messages updated successfully!";
        } else {
            $error = "Failed to update error messages.";
        }
    }
}

// Get current configuration
$currentBarangays = getBagoBarangaysFromDB();
$currentConfig = getBagoCityConfig();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bago City Configuration - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 bg-dark text-white min-vh-100 p-3">
                <h5>Admin Panel</h5>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="admin_dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white active" href="admin_bago_config.php">
                            <i class="fas fa-cog"></i> Bago City Config
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-city"></i> Bago City Configuration</h2>
                    <a href="admin_dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Barangay Management -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-map-marker-alt"></i> Barangay Management</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="barangays" class="form-label">Barangay List (one per line)</label>
                                <textarea class="form-control" id="barangays" name="barangays" rows="15" required><?php echo implode("\n", $currentBarangays); ?></textarea>
                                <div class="form-text">Enter one barangay name per line. These will be used for validation.</div>
                            </div>
                            <button type="submit" name="update_barangays" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Barangays
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Basic Configuration -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-cogs"></i> Basic Configuration</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="city_name" class="form-label">City Name</label>
                                        <input type="text" class="form-control" id="city_name" name="config_value" value="<?php echo htmlspecialchars($currentConfig['city_name']); ?>" required>
                                        <input type="hidden" name="config_key" value="city_name">
                                    </div>
                                    <button type="submit" name="update_config" class="btn btn-success">
                                        <i class="fas fa-save"></i> Update City Name
                                    </button>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="province" class="form-label">Province</label>
                                        <input type="text" class="form-control" id="province" name="config_value" value="<?php echo htmlspecialchars($currentConfig['province']); ?>" required>
                                        <input type="hidden" name="config_key" value="province">
                                    </div>
                                    <button type="submit" name="update_config" class="btn btn-success">
                                        <i class="fas fa-save"></i> Update Province
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Error Messages Configuration -->
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Error Messages Configuration</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="not_bago_resident" class="form-label">Not Bago Resident Message</label>
                                <textarea class="form-control" id="not_bago_resident" name="not_bago_resident" rows="2" required><?php echo htmlspecialchars($currentConfig['error_messages']['not_bago_resident']); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="wrong_province" class="form-label">Wrong Province Message</label>
                                <textarea class="form-control" id="wrong_province" name="wrong_province" rows="2" required><?php echo htmlspecialchars($currentConfig['error_messages']['wrong_province']); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="invalid_barangay" class="form-label">Invalid Barangay Message</label>
                                <textarea class="form-control" id="invalid_barangay" name="invalid_barangay" rows="2" required><?php echo htmlspecialchars($currentConfig['error_messages']['invalid_barangay']); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="name_mismatch" class="form-label">Name Mismatch Message</label>
                                <textarea class="form-control" id="name_mismatch" name="name_mismatch" rows="2" required><?php echo htmlspecialchars($currentConfig['error_messages']['name_mismatch']); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="success_verified" class="form-label">Success Verification Message</label>
                                <textarea class="form-control" id="success_verified" name="success_verified" rows="2" required><?php echo htmlspecialchars($currentConfig['error_messages']['success_verified']); ?></textarea>
                            </div>
                            
                            <button type="submit" name="update_error_messages" class="btn btn-warning">
                                <i class="fas fa-save"></i> Update Error Messages
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Current Configuration Display -->
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Current Configuration</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Basic Settings:</h6>
                                <ul class="list-unstyled">
                                    <li><strong>City:</strong> <?php echo htmlspecialchars($currentConfig['city_name']); ?></li>
                                    <li><strong>Province:</strong> <?php echo htmlspecialchars($currentConfig['province']); ?></li>
                                    <li><strong>Country:</strong> <?php echo htmlspecialchars($currentConfig['country']); ?></li>
                                    <li><strong>Validation Enabled:</strong> <?php echo $currentConfig['validation_enabled'] ? 'Yes' : 'No'; ?></li>
                                    <li><strong>Strict Validation:</strong> <?php echo $currentConfig['strict_validation'] ? 'Yes' : 'No'; ?></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Barangay Count:</h6>
                                <p class="h4 text-primary"><?php echo count($currentBarangays); ?> Barangays</p>
                                <small class="text-muted">Total number of configured barangays</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
