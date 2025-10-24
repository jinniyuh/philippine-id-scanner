# 📦 Live Server Upload Checklist

## Files to Upload to https://bagovetims.bccbsis.com/

Upload these files to fix all ML insights errors and remove sample data generation.

---

## ✅ **REQUIRED FILES (Must Upload)**

### **1. Core ML Files - includes/**

```
📁 Upload to: capstone/includes/

✅ arima_forecaster.php
   - Added validateForecastAccuracy() method
   - Removed all sample data generation
   - Removed closing ?> tag

✅ sample_data_generator.php
   - Removed closing ?> tag
```

---

### **2. Main API Endpoints - capstone/ (root)**

```
📁 Upload to: capstone/

✅ get_ml_insights_enhanced.php (NEW FILE)
   - Enhanced ML insights API
   - No sample data generation
   - Clean JSON output

✅ get_pharmaceuticals.php (NEW FILE)
   - Pharmaceutical list API
   - For dropdown population

✅ get_pharmaceutical_forecast.php (UPDATED)
   - Removed closing ?> tag

✅ get_livestock_forecast.php (NEW FILE)
   - Livestock forecasting API
   - Added accuracy calculation
   - Includes arima_forecaster.php

✅ get_poultry_forecast.php (NEW FILE)
   - Poultry forecasting API
   - Added accuracy calculation
   - Includes arima_forecaster.php
```

---

### **3. Backup ML System Files - ml_system/**

```
📁 Upload to: capstone/ml_system/includes/

✅ arima_forecaster.php
   - Synchronized with main includes/ version

✅ sample_data_generator.php
   - Synchronized with main includes/ version


📁 Upload to: capstone/ml_system/api/

✅ get_ml_insights_enhanced.php
   - Synchronized with root version
```

---

## 📋 **Complete File List (9 files total)**

Copy and paste this list to track your uploads:

```
☐ capstone/includes/arima_forecaster.php
☐ capstone/includes/sample_data_generator.php
☐ capstone/get_ml_insights_enhanced.php
☐ capstone/get_pharmaceuticals.php
☐ capstone/get_pharmaceutical_forecast.php
☐ capstone/get_livestock_forecast.php
☐ capstone/get_poultry_forecast.php
☐ capstone/ml_system/includes/arima_forecaster.php
☐ capstone/ml_system/api/get_ml_insights_enhanced.php
```

---

## 🎯 **Priority Upload Order**

Upload in this order for best results:

### **Step 1: Core Dependencies First**
1. ✅ `capstone/includes/arima_forecaster.php`
2. ✅ `capstone/includes/sample_data_generator.php`

### **Step 2: Main API Endpoints**
3. ✅ `capstone/get_ml_insights_enhanced.php`
4. ✅ `capstone/get_pharmaceuticals.php`
5. ✅ `capstone/get_pharmaceutical_forecast.php`
6. ✅ `capstone/get_livestock_forecast.php`
7. ✅ `capstone/get_poultry_forecast.php`

### **Step 3: Backup/Sync Files**
8. ✅ `capstone/ml_system/includes/arima_forecaster.php`
9. ✅ `capstone/ml_system/api/get_ml_insights_enhanced.php`

---

## 🚀 **Quick Upload Guide**

### **Using FileZilla / FTP:**

1. Connect to your server:
   - Host: `bagovetims.bccbsis.com`
   - Username: [your FTP username]
   - Password: [your FTP password]

2. Navigate to: `/public_html/capstone/` (or wherever your capstone folder is)

3. Upload files maintaining the folder structure:
   ```
   /capstone/includes/arima_forecaster.php
   /capstone/includes/sample_data_generator.php
   /capstone/get_ml_insights_enhanced.php
   /capstone/get_pharmaceuticals.php
   /capstone/get_pharmaceutical_forecast.php
   /capstone/get_livestock_forecast.php
   /capstone/get_poultry_forecast.php
   /capstone/ml_system/includes/arima_forecaster.php
   /capstone/ml_system/api/get_ml_insights_enhanced.php
   ```

---

## ✅ **After Upload - Verify**

Test these URLs to confirm everything works:

1. **Admin Dashboard:**
   ```
   https://bagovetims.bccbsis.com/admin_dashboard.php
   ```
   ✅ Should show ML insights without errors

2. **ML Insights Page:**
   ```
   https://bagovetims.bccbsis.com/admin_ml_insights.php
   ```
   ✅ Should load completely with forecasts

3. **Check Browser Console:**
   - Press F12
   - Look for errors
   - Should see NO 404 or 500 errors

---

## 🔍 **What Gets Fixed**

After uploading these files:

✅ **Errors Fixed:**
- ✅ No more 404 (Not Found) errors
- ✅ No more 500 (Internal Server Error)
- ✅ No more JSON parse errors
- ✅ No more "validateForecastAccuracy not found" errors

✅ **Features Enabled:**
- ✅ ML insights load properly
- ✅ Pharmaceutical dropdown works
- ✅ All forecasts generate correctly
- ✅ No fake/sample data (100% real data)

---

## ⚠️ **Important Notes**

1. **File Permissions:**
   - Ensure PHP files have permission `644` or `755`
   - Files must be readable by web server

2. **File Paths:**
   - Maintain exact folder structure
   - Case-sensitive on Linux servers

3. **Database:**
   - Make sure `config.env.php` exists on live server
   - Database credentials must be correct

4. **Clear Cache:**
   - After upload, clear browser cache (Ctrl + Shift + Del)
   - May need to restart PHP-FPM or Apache on server

---

## 📞 **Troubleshooting**

If issues persist after upload:

1. **Check file upload:**
   - Verify all 9 files uploaded successfully
   - Check file sizes match local versions

2. **Check server logs:**
   - Look in `/var/log/apache2/error.log` (or equivalent)
   - Look for PHP errors

3. **Test individual endpoints:**
   ```
   https://bagovetims.bccbsis.com/get_ml_insights_enhanced.php
   https://bagovetims.bccbsis.com/get_pharmaceuticals.php
   ```
   Should return valid JSON

---

## 🎉 **Success Criteria**

You'll know everything works when:

✅ Admin dashboard loads without console errors
✅ ML insights section shows data
✅ Pharmaceutical dropdown populates
✅ Forecast charts display correctly
✅ No error messages in browser console
✅ No "insufficient data" errors (if you have real data)

---

**Date Created:** October 15, 2025
**Total Files to Upload:** 9 files
**Estimated Upload Time:** 2-5 minutes

---

## 📝 **Upload Tracking**

Mark as you upload:

- [ ] Step 1: Core files uploaded
- [ ] Step 2: API files uploaded  
- [ ] Step 3: Sync files uploaded
- [ ] Verification: Tested admin_dashboard.php
- [ ] Verification: Tested admin_ml_insights.php
- [ ] Verification: No console errors
- [ ] ✅ **COMPLETE!**

