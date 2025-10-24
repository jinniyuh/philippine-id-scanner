# 📊 Capstone Project - Unused Files Analysis Report

**Generated:** <?php echo date('Y-m-d H:i:s'); ?>  
**Total PHP Files:** 240  
**Files Safe to Delete:** 38  

---

## ✅ SAFE TO DELETE IMMEDIATELY (38 files)

### 1. Test Files (22 files) - Development/Testing Only
```
❌ test_animal_list_direct.php
❌ test_anomaly_api.php
❌ test_api_direct.php
❌ test_automatic_geocoding.php
❌ test_client_photos.php
❌ test_critical_outbreaks.php
❌ test_email_config.php
❌ test_email_sending.php
❌ test_flask_api.php
❌ test_geotagging.php
❌ test_global_alert.php
❌ test_health_risk_assessment.php
❌ test_health_risk_simple.php
❌ test_ml_api_simple.php
❌ test_phpmailer_installation.php
❌ test_risk_level_fix.php
❌ test_simple_api.php
❌ test_symptoms_api.php
❌ test_symptom_anomaly.php
❌ test_trend_indicators.php
❌ ml_system/api/test_flask_api.php
❌ ml_system/api/test_health_risk_api.php
```

### 2. Debug Files (2 files) - Debugging Only
```
❌ debug_health_risk.php
❌ debug_ml_insights_live.php
```

### 3. Unused Check/Validation Files (14 files) - Not Referenced Anywhere
```
❌ check_assessment_table_columns.php
❌ check_assessment_table_structure.php
❌ check_current_symptoms.php
❌ check_database_status.php
❌ check_data_status.php
❌ check_duplicate_fullname.php
❌ check_duplicate_username.php
❌ check_edit_user_availability.php
❌ check_health_assessments.php
❌ check_notifications.php
❌ check_pharma_table.php
❌ check_risk_assessments.php
❌ check_username.php
❌ check_user_availability.php
```

**Total: 38 files can be safely deleted**

---

## ⚠️ REVIEW BEFORE DELETING

### Setup Files (3 files) - One-time Setup Scripts
```
⚠️  setup_health_risk_data.php         - Setup health risk data (run once, then delete)
⚠️  setup_id_verification.php          - Setup ID verification (run once, then delete)
⚠️  setup_live_password_tracking.php   - Setup password tracking (run once, then delete)
```

### Generate/Populate Files (7 files) - Data Generation (Useful for Dev/Testing)
```
⚠️  generate_assessments_for_requests.php  - Generate test assessments
⚠️  generate_assessment_for_client_4965.php - Client-specific generation
⚠️  generate_assessment_history.php        - Generate assessment history
⚠️  generate_health_test_data.php          - Generate health test data
⚠️  generate_ml_sample_data.php            - Generate ML sample data
⚠️  generate_sample_data.php               - Generate sample data
⚠️  generate_sample_forecast_data.php      - Generate forecast data
```

### Additional Utility Files Worth Reviewing
```
⚠️  import_database_backup.php             - Database import utility
⚠️  update_database_for_ml.php             - ML database updates
⚠️  examine_database_structure.php         - Database structure checker
⚠️  populate_live_database.php             - Live database population
⚠️  populate_livestock_from_requests.php   - Livestock data population
⚠️  install_password_reset.php             - Password reset installer
⚠️  find_health_tables.php                 - Find health tables
⚠️  simple_health_risk_test.php            - Simple health risk test
⚠️  analyze_unused_files.php               - This analysis script
```

---

## ✅ KEEP - THESE ARE USED BY THE APPLICATION

### Check Files That Are Actually Used (4 files)
```
✅ check_client_validation.php       - Used by registration
✅ check_fullname_register.php       - Used by registration form
✅ check_upload_permission.php       - Used by upload system
✅ check_username_register.php       - Used by registration form
```

### ML System API Files (4 files used)
```
✅ ml_system/api/get_health_risk_flask.php       - Used by health monitoring
✅ ml_system/api/get_ml_insights.php             - Used by ML insights
✅ ml_system/api/get_ml_insights_enhanced.php    - Used by enhanced insights
✅ ml_system/api/get_ml_insights_flask.php       - Used by Flask integration
```

### ML System API Files (4 files NOT used - can delete)
```
❌ ml_system/api/get_livestock_forecast.php      - NOT USED
❌ ml_system/api/get_ml_demand_forecast.php      - NOT USED
❌ ml_system/api/get_pharmaceutical_forecast.php - NOT USED
❌ ml_system/api/get_poultry_forecast.php        - NOT USED
```

---

## 🔄 DUPLICATE FILES IN ml_system/

The `ml_system/` folder contains duplicates of files in `includes/`:

```
⚠️  ml_system/includes/arima_forecaster.php          (DUPLICATE)
⚠️  ml_system/includes/health_risk_assessor.php      (DUPLICATE)
⚠️  ml_system/includes/ml_health_risk_assessor.php   (DUPLICATE)
⚠️  ml_system/includes/sample_data_generator.php     (DUPLICATE)
```

**Recommendation:** Keep only one version (preferably in `includes/`)

---

## 📋 DELETION INSTRUCTIONS

### Option 1: Automatic Deletion (Safest Files Only)
```bash
php delete_unused_files.php
```
This will delete the 38 files marked as "SAFE TO DELETE IMMEDIATELY"

### Option 2: Manual Deletion
Review each category and delete manually based on your needs.

### Option 3: Move to Archive
Create an `_archive` folder and move files there instead of deleting:
```bash
mkdir _archive
mkdir _archive/test_files
mkdir _archive/debug_files
mkdir _archive/unused_check_files
```

---

## 📊 FILE CATEGORIES SUMMARY

| Category | Count | Action |
|----------|-------|--------|
| Test Files | 22 | ✅ DELETE |
| Debug Files | 2 | ✅ DELETE |
| Unused Check Files | 14 | ✅ DELETE |
| Setup Files | 3 | ⚠️ REVIEW |
| Generate Files | 7 | ⚠️ REVIEW |
| Used Check Files | 4 | ✅ KEEP |
| ML API Used | 4 | ✅ KEEP |
| ML API Unused | 4 | ❌ DELETE |
| Duplicate Files | 4 | ⚠️ REVIEW |
| Utility Files | ~15 | ⚠️ REVIEW |

---

## 🎯 RECOMMENDED ACTIONS

### Immediate (Safe)
1. ✅ Delete all 22 test files
2. ✅ Delete 2 debug files
3. ✅ Delete 14 unused check files
4. ✅ Delete 4 unused ML API files

**Total: 42 files → Will free up space and reduce clutter**

### After Review
1. ⚠️ Delete setup files (after confirming setup is complete)
2. ⚠️ Archive generate files (move to _archive, don't delete yet)
3. ⚠️ Consolidate ml_system duplicates (choose one location)

### Keep
1. ✅ All used check files (4 files)
2. ✅ Used ML API files (4 files)
3. ✅ Main application files
4. ✅ Core includes files

---

## ⚠️ WARNINGS

- **Backup first!** Always backup before deleting files
- **Test after deletion** - Run the application and test all features
- **Database scripts** - Files like `import_database_backup.php` might be needed for deployment
- **Generate files** - Useful for development/testing, archive instead of delete

---

## 🔗 GENERATED FILES

This analysis created:
- `UNUSED_FILES_REPORT.md` (this file)
- `delete_unused_files.php` (deletion script)
- `analyze_unused_files.php` (analysis script)

---

**END OF REPORT**

