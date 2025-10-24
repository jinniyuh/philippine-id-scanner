# 🔍 DEBUG REJECTION GUIDE

## 🎯 **WHEN BAGO RESIDENTS ARE STILL BEING REJECTED:**

### **STEP 1: Get Console Logs**
1. Open your browser's **Developer Console** (F12)
2. Go to the **Console** tab
3. Try to scan the ID that's being rejected
4. **Copy ALL the console output** (especially lines starting with `===`, `✅`, `❌`, `🔍`, `📍`)

### **STEP 2: Use the Diagnostic Tool**
1. Open `test_specific_ocr.html` in your browser
2. **Paste the OCR text** from the console logs (the line that says "Full OCR Text:")
3. Click **"Test Validation"**
4. This will show you **exactly why** it's being rejected

### **STEP 3: Share the Information**
Send me:
1. ✅ The **full OCR text** (from "Full OCR Text:" line)
2. ✅ The **validation logs** (from "=== VALIDATION START ===" to the end)
3. ✅ The **error message** shown to the user
4. ✅ The **barangay** of the person (if known)

---

## 📋 **WHAT TO LOOK FOR:**

### **Common Issues:**

#### **Issue 1: OCR reads "NEGROS" but not "NEGROS OCCIDENTAL"**
```
Has BAGO: true
Has NEGROS OCCIDENTAL: false  ❌
```
**Solution:** The OCR might be reading "NEGROS" separately. We need to add more variations.

#### **Issue 2: OCR corruption makes "BAGO" unreadable**
```
Has BAGO: false  ❌
Has NEGROS OCCIDENTAL: true
```
**Solution:** The OCR might be reading "BAGO" as "EAGO", "RAGO", etc. We need to add these variations.

#### **Issue 3: Other city detected by mistake**
```
❌ Found other city: SOME_CITY
```
**Solution:** The OCR might be misreading something as another city name. We need to adjust the blacklist.

---

## 🚀 **ENHANCED DEBUG LOGGING:**

The system now logs:
- ✅ Full OCR text
- ✅ Text around "BAGO"
- ✅ Text around "NEGROS"
- ✅ All pattern matching attempts
- ✅ Flexible pattern matching details
- ✅ Final validation result

---

## 🎯 **NEXT STEPS:**

1. **Get the console logs** from a rejected Bago resident
2. **Use `test_specific_ocr.html`** to diagnose the issue
3. **Share the results** so I can add the missing patterns

**With the enhanced logging, we can now see EXACTLY why any ID is being rejected!** 🔍
