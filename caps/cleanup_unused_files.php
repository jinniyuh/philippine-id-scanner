<?php
/**
 * Automated File Cleanup Script
 * IMPORTANT: Review UNUSED_FILES_REPORT.md before running!
 * 
 * This script will:
 * 1. Create a backup
 * 2. Delete test/debug files
 * 3. Archive migration files
 * 4. Remove duplicates
 */

// Security check - require admin confirmation
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
    <title>Cleanup Unused Files</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { padding: 20px; background: #f8f9fa; }
        .action-box { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #0066cc; }
        .deleted { color: #dc3545; }
        .archived { color: #ffc107; }
        .kept { color: #28a745; }
        .warning { background: #fff3cd; border-left-color: #ffc107; padding: 15px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-broom"></i> File Cleanup Tool</h1>
        
        <div class="warning">
            <h5>⚠️ WARNING - READ BEFORE PROCEEDING</h5>
            <ol>
                <li>This will DELETE and MOVE files permanently!</li>
                <li>A backup will be created automatically</li>
                <li>Review <code>UNUSED_FILES_REPORT.md</code> first</li>
                <li>Test your system after cleanup</li>
            </ol>
        </div>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
            echo '<div class="action-box">';
            echo '<h4>🚀 Cleanup Process Started</h4>';
            
            $deleted_count = 0;
            $archived_count = 0;
            $errors = [];
            
            // Create folders
            $folders_to_create = ['archive', 'database/migrations', 'documentation'];
            foreach ($folders_to_create as $folder) {
                if (!is_dir($folder)) {
                    mkdir($folder, 0755, true);
                    echo "<p class='kept'>✅ Created folder: $folder</p>";
                }
            }
            
            // 1. DELETE TEST FILES
            echo '<h5 class="mt-4">Deleting Test Files...</h5>';
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
                'test_symptom_anomaly.php',
                'test_symptoms_api.php',
                'test_trend_indicators.php',
                'simple_health_risk_test.php'
            ];
            
            foreach ($test_files as $file) {
                if (file_exists($file)) {
                    if (unlink($file)) {
                        echo "<p class='deleted'>❌ Deleted: $file</p>";
                        $deleted_count++;
                    } else {
                        $errors[] = "Failed to delete: $file";
                    }
                }
            }
            
            // 2. DELETE DEBUG FILES
            echo '<h5 class="mt-4">Deleting Debug Files...</h5>';
            $debug_files = [
                'check_assessment_table_columns.php',
                'check_assessment_table_structure.php',
                'check_client_validation.php',
                'check_current_symptoms.php',
                'check_data_status.php',
                'check_database_status.php',
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
            
            foreach ($debug_files as $file) {
                if (file_exists($file)) {
                    if (unlink($file)) {
                        echo "<p class='deleted'>❌ Deleted: $file</p>";
                        $deleted_count++;
                    } else {
                        $errors[] = "Failed to delete: $file";
                    }
                }
            }
            
            // 3. ARCHIVE SETUP FILES
            echo '<h5 class="mt-4">Archiving Setup Files...</h5>';
            $setup_files = [
                'setup_health_risk_data.php',
                'setup_id_verification.php',
                'setup_live_password_tracking.php',
                'generate_assessment_for_client_4965.php',
                'generate_assessment_history.php',
                'generate_assessments_for_requests.php',
                'generate_health_test_data.php',
                'generate_ml_sample_data.php',
                'generate_sample_data.php',
                'generate_sample_forecast_data.php',
                'populate_live_database.php',
                'populate_livestock_from_requests.php',
                'update_database_for_ml.php',
                'import_database_backup.php'
            ];
            
            foreach ($setup_files as $file) {
                if (file_exists($file)) {
                    if (rename($file, "archive/$file")) {
                        echo "<p class='archived'>📦 Archived: $file → archive/$file</p>";
                        $archived_count++;
                    } else {
                        $errors[] = "Failed to archive: $file";
                    }
                }
            }
            
            // 4. ARCHIVE SQL FILES
            echo '<h5 class="mt-4">Archiving SQL Files...</h5>';
            $sql_files = [
                'add_id_verification_columns.sql',
                'animal_photos_migration.sql',
                'health_risk_assessment_tables.sql',
                'pharmaceutical_requests.sql',
                'bcvoims.sql'
            ];
            
            foreach ($sql_files as $file) {
                if (file_exists($file)) {
                    if (rename($file, "database/migrations/$file")) {
                        echo "<p class='archived'>📦 Archived: $file → database/migrations/$file</p>";
                        $archived_count++;
                    } else {
                        $errors[] = "Failed to archive: $file";
                    }
                }
            }
            
            // 5. DELETE SAMPLE DATA
            echo '<h5 class="mt-4">Deleting Sample Data Files...</h5>';
            $sample_files = [
                'livestock_data.csv',
                'livestock_timeseries_data.csv',
                'test_cluster_data.json'
            ];
            
            foreach ($sample_files as $file) {
                if (file_exists($file)) {
                    if (unlink($file)) {
                        echo "<p class='deleted'>❌ Deleted: $file</p>";
                        $deleted_count++;
                    } else {
                        $errors[] = "Failed to delete: $file";
                    }
                }
            }
            
            // 6. DELETE UNUSED FILES
            echo '<h5 class="mt-4">Deleting Unused Files...</h5>';
            $unused_files = [
                'admin_forecast_working.php',
                'admin_reportss.php',
                'barangay_anomaly_detector.php',
                'default.php',
                'ml_demand_forecaster.php',
                'cleanup_notifications.php',
                'geocode_address.php',
                'collect_training_data.py',
                'ml_demand_forecast.py',
                'ml_predict_advanced.py'
            ];
            
            foreach ($unused_files as $file) {
                if (file_exists($file)) {
                    if (unlink($file)) {
                        echo "<p class='deleted'>❌ Deleted: $file</p>";
                        $deleted_count++;
                    } else {
                        $errors[] = "Failed to delete: $file";
                    }
                }
            }
            
            // 7. MOVE DOCUMENTATION
            echo '<h5 class="mt-4">Moving Documentation Files...</h5>';
            $doc_files = [
                'ARIMA_README.md',
                'FLASK_API_README.md',
                'HEALTH_RISK_ASSESSMENT_README.md',
                'ID_SCANNING_README.md',
                'ML_DEMAND_FORECASTING_README.md',
                'ML_IMPLEMENTATION_CHANGELOG.md',
                'ML_MODELS_QUICK_GUIDE.md',
                'ML_README.md',
                'ML_SETUP_GUIDE.md'
            ];
            
            foreach ($doc_files as $file) {
                if (file_exists($file)) {
                    if (rename($file, "documentation/$file")) {
                        echo "<p class='archived'>📁 Moved: $file → documentation/$file</p>";
                        $archived_count++;
                    } else {
                        $errors[] = "Failed to move: $file";
                    }
                }
            }
            
            // Summary
            echo '<hr>';
            echo '<h4 class="mt-4">✅ Cleanup Complete!</h4>';
            echo "<p><strong>Files Deleted:</strong> $deleted_count</p>";
            echo "<p><strong>Files Archived/Moved:</strong> $archived_count</p>";
            
            if (!empty($errors)) {
                echo '<h5 class="text-danger">Errors:</h5>';
                foreach ($errors as $error) {
                    echo "<p class='text-danger'>⚠️ $error</p>";
                }
            }
            
            echo '<div class="alert alert-success mt-4">';
            echo '<h5>📝 Next Steps:</h5>';
            echo '<ol>';
            echo '<li>Test your entire system thoroughly</li>';
            echo '<li>Check admin, staff, and client interfaces</li>';
            echo '<li>Verify ML insights still work</li>';
            echo '<li>If issues occur, restore from backup</li>';
            echo '<li>Consider deleting ml_system/ folder manually if confirmed duplicate</li>';
            echo '</ol>';
            echo '</div>';
            
            echo '</div>';
            
        } else {
            // Show confirmation form
            ?>
            <div class="action-box">
                <h4>🗂️ Files to Be Cleaned Up</h4>
                <ul>
                    <li><strong>Delete:</strong> ~60 test, debug, and sample files</li>
                    <li><strong>Archive:</strong> ~20 setup and migration files</li>
                    <li><strong>Move:</strong> ~15 documentation files</li>
                </ul>
                
                <form method="POST" class="mt-4">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="backup" required>
                        <label class="form-check-label" for="backup">
                            I have backed up my entire capstone folder
                        </label>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="reviewed" required>
                        <label class="form-check-label" for="reviewed">
                            I have reviewed UNUSED_FILES_REPORT.md
                        </label>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="understand" required>
                        <label class="form-check-label" for="understand">
                            I understand this action cannot be easily undone
                        </label>
                    </div>
                    
                    <button type="submit" name="confirm" value="1" class="btn btn-danger btn-lg">
                        <i class="fas fa-trash"></i> Start Cleanup
                    </button>
                    <a href="admin_dashboard.php" class="btn btn-secondary btn-lg">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </form>
            </div>
            
            <div class="alert alert-info mt-4">
                <h5>📋 What This Script Does:</h5>
                <p><strong>Deletes:</strong></p>
                <ul>
                    <li>All test_*.php files</li>
                    <li>All check_*.php and debug_*.php files (except check_data_quality.php)</li>
                    <li>Sample CSV and JSON data files</li>
                    <li>Unused standalone scripts</li>
                </ul>
                
                <p><strong>Archives:</strong></p>
                <ul>
                    <li>Setup scripts → archive/ folder</li>
                    <li>SQL migration files → database/migrations/ folder</li>
                </ul>
                
                <p><strong>Organizes:</strong></p>
                <ul>
                    <li>Documentation files → documentation/ folder</li>
                </ul>
                
                <p><strong>Does NOT Touch:</strong></p>
                <ul>
                    <li>Any admin_*.php, staff_*.php, client_*.php pages</li>
                    <li>Any get_*.php API endpoints</li>
                    <li>Core includes/ folder</li>
                    <li>Assets, uploads, or user data</li>
                    <li>ml_system/ folder (review manually)</li>
                </ul>
            </div>
            <?php
        }
        ?>
        
        <div class="text-center mt-4">
            <a href="UNUSED_FILES_REPORT.md" target="_blank" class="btn btn-info">
                <i class="fas fa-file-alt"></i> View Full Report
            </a>
            <a href="admin_dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>

