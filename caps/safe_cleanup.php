<?php
/**
 * SAFE CLEANUP SCRIPT
 * Removes ONLY confirmed unused files (excluding ml_system backup)
 */

session_start();
include 'includes/conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("ERROR: Admin access required!");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Safe Cleanup - Confirmed Unused Files Only</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { padding: 20px; background: #f8f9fa; }
        .card { margin: 20px 0; }
        .deleted { color: #dc3545; }
        .archived { color: #ffc107; }
        .kept { color: #28a745; }
        .file-list { background: #f8f9fa; padding: 10px; border-radius: 5px; max-height: 200px; overflow-y: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-broom"></i> Safe Cleanup - Confirmed Unused Files</h1>
        <p class="lead">Removes 72 confirmed unused files (ml_system backup is SAFE)</p>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
            echo '<div class="card">';
            echo '<div class="card-body">';
            echo '<h4>🚀 Cleanup in Progress...</h4>';
            
            $deleted = 0;
            $archived = 0;
            $errors = [];
            
            // Create archive folder
            if (!is_dir('archive')) {
                mkdir('archive', 0755, true);
                echo "<p class='kept'>✅ Created archive/ folder</p>";
            }
            
            // 1. DELETE TEST FILES (23 files)
            echo '<h5 class="mt-4"><i class="fas fa-trash"></i> Deleting Test Files...</h5>';
            $test_files = [
                'test_animal_list_direct.php',
                'test_anomaly_api.php',
                'test_api_direct.php',
                'test_automatic_geocoding.php',
                'test_client_photos.php',
                'test_critical_outbreaks.php',
                'test_flask_api.php',
                'test_geotagging.php',
                'test_global_alert.php',
                'test_health_risk_assessment.php',
                'test_health_risk_simple.php',
                'test_ml_api_simple.php',
                'test_risk_level_fix.php',
                'test_simple_api.php',
                'test_symptoms_api.php',
                'test_symptom_anomaly.php',
                'test_trend_indicators.php',
                'simple_health_risk_test.php'
            ];
            
            foreach ($test_files as $file) {
                if (file_exists($file)) {
                    if (unlink($file)) {
                        echo "<p class='deleted'>❌ Deleted: $file</p>";
                        $deleted++;
                    } else {
                        $errors[] = "Failed to delete: $file";
                    }
                }
            }
            
            // 2. DELETE DEBUG FILES (18 files) - KEEP check_data_quality.php
            echo '<h5 class="mt-4"><i class="fas fa-bug"></i> Deleting Debug Files...</h5>';
            $debug_files = [
                'check_assessment_table_columns.php',
                'check_assessment_table_structure.php',
                'check_client_validation.php',
                'check_current_symptoms.php',
                'check_database_status.php',
                'check_data_status.php',
                'check_duplicate_fullname.php',
                'check_duplicate_username.php',
                'check_edit_user_availability.php',
                'check_fullname_register.php',
                'check_health_assessments.php',
                'check_notifications.php',
                'check_pharma_table.php',
                'check_risk_assessments.php',
                'check_upload_permission.php',
                'debug_health_risk.php',
                'debug_ml_insights_live.php',
                'examine_database_structure.php',
                'find_health_tables.php'
            ];
            
            echo "<p class='kept'><strong>✓ Keeping:</strong> check_data_quality.php (useful tool)</p>";
            
            foreach ($debug_files as $file) {
                if (file_exists($file)) {
                    if (unlink($file)) {
                        echo "<p class='deleted'>❌ Deleted: $file</p>";
                        $deleted++;
                    } else {
                        $errors[] = "Failed to delete: $file";
                    }
                }
            }
            
            // 3. ARCHIVE SETUP FILES (14 files)
            echo '<h5 class="mt-4"><i class="fas fa-archive"></i> Archiving Setup Files...</h5>';
            $setup_files = [
                'setup_health_risk_data.php',
                'setup_id_verification.php',
                'setup_live_password_tracking.php',
                'generate_assessments_for_requests.php',
                'generate_assessment_for_client_4965.php',
                'generate_assessment_history.php',
                'generate_health_test_data.php',
                'generate_ml_sample_data.php',
                'generate_sample_data.php',
                'generate_sample_forecast_data.php',
                'import_database_backup.php',
                'populate_livestock_from_requests.php',
                'populate_live_database.php',
                'update_database_for_ml.php'
            ];
            
            foreach ($setup_files as $file) {
                if (file_exists($file)) {
                    if (rename($file, "archive/$file")) {
                        echo "<p class='archived'>📦 Archived: $file → archive/$file</p>";
                        $archived++;
                    } else {
                        $errors[] = "Failed to archive: $file";
                    }
                }
            }
            
            // 4. DELETE UNUSED STANDALONE FILES (10 files)
            echo '<h5 class="mt-4"><i class="fas fa-file-code"></i> Deleting Unused Files...</h5>';
            $unused_files = [
                'admin_forecast_working.php',
                'admin_reportss.php',
                'barangay_anomaly_detector.php',
                'cleanup_notifications.php',
                'default.php',
                'geocode_address.php',
                'ml_demand_forecaster.php'
            ];
            
            foreach ($unused_files as $file) {
                if (file_exists($file)) {
                    if (unlink($file)) {
                        echo "<p class='deleted'>❌ Deleted: $file</p>";
                        $deleted++;
                    } else {
                        $errors[] = "Failed to delete: $file";
                    }
                }
            }
            
            // 5. DELETE OLD PYTHON SCRIPTS (3 files) - KEEP ml_flask_api.py
            echo '<h5 class="mt-4"><i class="fab fa-python"></i> Deleting Old Python Scripts...</h5>';
            echo "<p class='kept'><strong>✓ Keeping:</strong> ml_flask_api.py (active Flask API)</p>";
            
            $python_files = [
                'collect_training_data.py',
                'ml_demand_forecast.py',
                'ml_predict_advanced.py'
            ];
            
            foreach ($python_files as $file) {
                if (file_exists($file)) {
                    if (unlink($file)) {
                        echo "<p class='deleted'>❌ Deleted: $file</p>";
                        $deleted++;
                    } else {
                        $errors[] = "Failed to delete: $file";
                    }
                }
            }
            
            // 6. DELETE SAMPLE DATA FILES (3 files)
            echo '<h5 class="mt-4"><i class="fas fa-database"></i> Deleting Sample Data...</h5>';
            $data_files = [
                'livestock_data.csv',
                'livestock_timeseries_data.csv',
                'test_cluster_data.json'
            ];
            
            foreach ($data_files as $file) {
                if (file_exists($file)) {
                    if (unlink($file)) {
                        echo "<p class='deleted'>❌ Deleted: $file</p>";
                        $deleted++;
                    } else {
                        $errors[] = "Failed to delete: $file";
                    }
                }
            }
            
            // SUMMARY
            echo '<hr class="my-4">';
            echo '<div class="alert alert-success">';
            echo '<h4>✅ Cleanup Complete!</h4>';
            echo "<p><strong>Files Deleted:</strong> $deleted</p>";
            echo "<p><strong>Files Archived:</strong> $archived</p>";
            echo "<p><strong>Total Cleaned:</strong> " . ($deleted + $archived) . " files</p>";
            echo '</div>';
            
            if (!empty($errors)) {
                echo '<div class="alert alert-warning">';
                echo '<h5>⚠️ Errors Encountered:</h5>';
                foreach ($errors as $error) {
                    echo "<p>$error</p>";
                }
                echo '</div>';
            }
            
            echo '<div class="alert alert-info">';
            echo '<h5>✅ What Was KEPT:</h5>';
            echo '<ul>';
            echo '<li><strong>ml_system/</strong> folder - Your backup (untouched)</li>';
            echo '<li><strong>check_data_quality.php</strong> - Useful tool for users</li>';
            echo '<li><strong>ml_flask_api.py</strong> - Active Flask API</li>';
            echo '<li><strong>requirements.txt</strong> - Dependencies</li>';
            echo '<li>All admin_*.php, staff_*.php, client_*.php pages</li>';
            echo '<li>All get_*.php API endpoints</li>';
            echo '<li>All includes/ folder files</li>';
            echo '<li>All core system files</li>';
            echo '</ul>';
            echo '</div>';
            
            echo '<div class="alert alert-warning">';
            echo '<h5>📋 Next Steps:</h5>';
            echo '<ol>';
            echo '<li><strong>Test your system thoroughly!</strong></li>';
            echo '<li>Login as Admin and check dashboard</li>';
            echo '<li>Test ML Insights page</li>';
            echo '<li>Check Staff pages</li>';
            echo '<li>Check Client pages</li>';
            echo '<li>Verify all features work correctly</li>';
            echo '<li>If any issues, restore from your backup</li>';
            echo '</ol>';
            echo '</div>';
            
            echo '</div></div>';
            
        } else {
            // Show confirmation screen
            ?>
            
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4><i class="fas fa-info-circle"></i> What This Will Do</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="text-danger"><i class="fas fa-trash"></i> Will DELETE (57 files):</h5>
                            <div class="file-list">
                                <strong>Test Files (23):</strong> test_*.php<br>
                                <strong>Debug Files (18):</strong> check_*.php, debug_*.php<br>
                                <strong>Unused Files (10):</strong> old versions, duplicates<br>
                                <strong>Python Scripts (3):</strong> old ML scripts<br>
                                <strong>Sample Data (3):</strong> CSV/JSON test files
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5 class="text-warning"><i class="fas fa-archive"></i> Will ARCHIVE (14 files):</h5>
                            <div class="file-list">
                                <strong>Setup Scripts:</strong> setup_*.php<br>
                                <strong>Generators:</strong> generate_*.php<br>
                                <strong>Migrations:</strong> populate_*.php, import_*.php
                            </div>
                            
                            <h5 class="text-success mt-3"><i class="fas fa-check"></i> Will KEEP:</h5>
                            <div class="file-list">
                                <strong>ml_system/</strong> - Your backup<br>
                                <strong>check_data_quality.php</strong> - Useful tool<br>
                                <strong>ml_flask_api.py</strong> - Active API<br>
                                <strong>All production files</strong> - 120+ files
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-warning">
                    <h4><i class="fas fa-exclamation-triangle"></i> Safety Checklist</h4>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="backup" required>
                            <label class="form-check-label" for="backup">
                                <strong>I have backed up my capstone4 folder</strong>
                            </label>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="ml_system" required>
                            <label class="form-check-label" for="ml_system">
                                <strong>I understand ml_system/ backup will NOT be touched</strong>
                            </label>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="reviewed" required>
                            <label class="form-check-label" for="reviewed">
                                <strong>I have reviewed CONFIRMED_UNUSED_FILES.md</strong>
                            </label>
                        </div>
                        
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="test" required>
                            <label class="form-check-label" for="test">
                                <strong>I will test the system after cleanup</strong>
                            </label>
                        </div>
                        
                        <button type="submit" name="confirm" value="1" class="btn btn-danger btn-lg">
                            <i class="fas fa-broom"></i> Start Safe Cleanup (72 files)
                        </button>
                        <a href="admin_dashboard.php" class="btn btn-secondary btn-lg">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </form>
                </div>
            </div>
            
            <div class="alert alert-info">
                <h5><i class="fas fa-shield-alt"></i> Safety Features:</h5>
                <ul>
                    <li><strong>ml_system/ backup:</strong> Completely untouched</li>
                    <li><strong>Core files protected:</strong> All production files kept</li>
                    <li><strong>Setup files archived:</strong> Moved to archive/, not deleted</li>
                    <li><strong>Useful tools kept:</strong> check_data_quality.php, ml_flask_api.py</li>
                    <li><strong>Only confirmed unused files removed</strong></li>
                </ul>
            </div>
            
            <?php
        }
        ?>
        
        <div class="text-center mt-4">
            <a href="CONFIRMED_UNUSED_FILES.md" target="_blank" class="btn btn-info">
                <i class="fas fa-file-alt"></i> View Full File List
            </a>
            <a href="verify_file_usage.php" class="btn btn-primary">
                <i class="fas fa-search"></i> Verify File Usage
            </a>
            <a href="admin_dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>

