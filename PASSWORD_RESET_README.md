# Password Reset Feature - Deployment Guide for Hostinger

## 📋 Overview
Complete "Forgot Password" feature for Bago City Veterinary Office system with email-based password reset using PHPMailer and Hostinger SMTP.

## 🎯 Features
- ✅ Secure token-based password reset
- ✅ Email verification with HTML formatting
- ✅ 1-hour token expiration
- ✅ Password strength validation
- ✅ Real-time password match checking
- ✅ Production-ready for Hostinger deployment
- ✅ Mobile-responsive design
- ✅ Security best practices

## 📁 Files Included

### PHP Files
1. **forgot_password.php** - Password reset request page
2. **reset_password.php** - Password reset confirmation page

### Database Files
3. **database/add_password_reset_fields.sql** - Database migration

### Documentation
4. **PASSWORD_RESET_README.md** - This file

## 🚀 Deployment Instructions

### Step 1: Database Setup

1. **Log into Hostinger cPanel**
   - Go to: https://hpanel.hostinger.com/
   - Navigate to: Hosting → Databases → phpMyAdmin

2. **Select your database**
   - Click on your database (e.g., `u520834156_dbBagoVetIMS`)

3. **Run the migration SQL**
   ```sql
   -- Copy and paste from add_password_reset_fields.sql
   ALTER TABLE `clients` 
   ADD COLUMN `reset_token` VARCHAR(64) NULL DEFAULT NULL;
   
   ALTER TABLE `clients` 
   ADD COLUMN `reset_expiry` DATETIME NULL DEFAULT NULL;
   
   ALTER TABLE `clients` 
   ADD INDEX `idx_reset_token` (`reset_token`);
   ```

4. **Verify the email column exists**
   - Check if `clients` table has an `email` column
   - If not, add it:
   ```sql
   ALTER TABLE `clients` 
   ADD COLUMN `email` VARCHAR(255) NOT NULL AFTER `contact_number`;
   
   ALTER TABLE `clients` 
   ADD UNIQUE INDEX `idx_email` (`email`);
   ```

### Step 2: Install PHPMailer

**Option A: Using Composer (Recommended)**
```bash
cd public_html
composer require phpmailer/phpmailer
```

**Option B: Manual Installation**
1. Download PHPMailer from: https://github.com/PHPMailer/PHPMailer/releases
2. Extract to: `public_html/includes/PHPMailer/`
3. Structure should be:
   ```
   public_html/
   └── includes/
       └── PHPMailer/
           ├── PHPMailer.php
           ├── SMTP.php
           └── Exception.php
   ```

### Step 3: Upload Files to Hostinger

1. **Using File Manager (cPanel)**
   - Login to Hostinger cPanel
   - Navigate to: File Manager → public_html
   - Upload `forgot_password.php` to root
   - Upload `reset_password.php` to root

2. **Using FTP (FileZilla)**
   - Host: ftp.yourdomain.com
   - Username: Your FTP username
   - Password: Your FTP password
   - Upload files to: `/public_html/`

### Step 4: Configure SMTP Settings

The files are already configured with Hostinger SMTP:
```php
Host:     smtp.hostinger.com
Port:     465 (SSL)
Username: bagovet_info@bccbsis.com
Password: Y^k*/[ElK4c
```

⚠️ **Security Note**: For production, consider moving credentials to a config file:

Create `includes/email_config.php`:
```php
<?php
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 465);
define('SMTP_USERNAME', 'bagovet_info@bccbsis.com');
define('SMTP_PASSWORD', 'Y^k*/[ElK4c');
define('SMTP_FROM_EMAIL', 'bagovet_info@bccbsis.com');
define('SMTP_FROM_NAME', 'Bago City Veterinary Office');
?>
```

Then update forgot_password.php to use these constants.

### Step 5: Update Login Page

Add a "Forgot Password?" link to your login.php:

```html
<div class="text-center mt-2">
  <a href="forgot_password.php" class="text-muted small">
    <i class="fas fa-key me-1"></i>Forgot Password?
  </a>
</div>
```

### Step 6: Test the Feature

1. **Test Email Sending**
   - Go to: https://yourdomain.com/forgot_password.php
   - Enter a registered email
   - Check inbox (and spam folder)

2. **Test Password Reset**
   - Click the link in the email
   - Should redirect to reset_password.php
   - Enter new password
   - Try logging in with new password

3. **Test Token Expiration**
   - Request a reset link
   - Wait 1 hour
   - Try using the expired link
   - Should show "link has expired" error

## 🔒 Security Features

### Token Security
- ✅ 64-character random hexadecimal token
- ✅ `random_bytes(32)` for cryptographic randomness
- ✅ One-time use (cleared after successful reset)
- ✅ 1-hour expiration

### Password Requirements
- ✅ Minimum 8 characters
- ✅ At least 1 uppercase letter
- ✅ At least 1 lowercase letter
- ✅ At least 1 number
- ✅ Hashed using `password_hash()` (bcrypt)

### Email Security
- ✅ SMTP authentication (SSL/TLS)
- ✅ HTML email with proper encoding
- ✅ No sensitive data in URLs (token only)

### Best Practices
- ✅ Rate limiting (consider adding)
- ✅ No email enumeration (same message for valid/invalid emails)
- ✅ HTTPS only (ensure SSL certificate is active)
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS prevention (htmlspecialchars)

## 🎨 User Interface

### Forgot Password Page
- Clean, modern design
- Gradient header
- Responsive layout
- Clear instructions
- Font Awesome icons

### Reset Password Page
- Password strength meter
- Real-time validation
- Show/hide password toggles
- Password match indicator
- User-friendly error messages

## 📧 Email Template

The system sends HTML emails with:
- Professional header with gradient
- Clear call-to-action button
- Clickable reset link
- Expiration warning
- Security tips
- Footer with company info

## 🐛 Troubleshooting

### Emails Not Sending

**Check 1: SMTP Credentials**
```php
// Test SMTP connection
$mail->SMTPDebug = 2; // Enable verbose debug output
```

**Check 2: PHP Mail Function**
```bash
# Check if mail() function is enabled
php -m | grep mail
```

**Check 3: Firewall/Ports**
- Ensure port 465 is not blocked
- Check Hostinger firewall settings

**Check 4: Email Logs**
```bash
# Check PHP error log
tail -f /path/to/php_error.log
```

### Token Not Working

**Check 1: Database**
```sql
SELECT reset_token, reset_expiry FROM clients WHERE email = 'user@example.com';
```

**Check 2: Token Format**
- Should be 64 characters
- Should be hexadecimal (0-9, a-f)

**Check 3: Expiry Time**
```sql
SELECT reset_expiry, NOW() FROM clients WHERE email = 'user@example.com';
```

### Password Not Updating

**Check 1: Database Permissions**
```sql
SHOW GRANTS FOR 'your_db_user'@'localhost';
```

**Check 2: Password Hash**
```php
// Test password hashing
$hash = password_hash('test123', PASSWORD_DEFAULT);
echo $hash; // Should output a bcrypt hash
```

## 📱 Mobile Responsiveness

Both pages are fully responsive:
- ✅ Works on phones (320px+)
- ✅ Works on tablets (768px+)
- ✅ Works on desktops (1024px+)
- ✅ Touch-friendly buttons
- ✅ Readable font sizes

## 🔄 Flow Diagram

```
User forgets password
    ↓
Click "Forgot Password?" on login page
    ↓
Enter email address
    ↓
Submit form → PHP validates email → Checks database
    ↓
If email exists:
    - Generate random token
    - Store token + expiry in database
    - Send email with reset link
    ↓
User clicks link in email
    ↓
Redirects to reset_password.php?token=...
    ↓
PHP validates token (exists + not expired)
    ↓
User enters new password
    ↓
Submit form → PHP validates password
    ↓
Update database:
    - Hash new password
    - Clear reset_token
    - Clear reset_expiry
    ↓
Success! User can login with new password
```

## 🛡️ Production Checklist

Before going live:

- [ ] Database fields added (reset_token, reset_expiry)
- [ ] PHPMailer installed
- [ ] SMTP credentials verified
- [ ] Test email sending
- [ ] Test password reset flow
- [ ] Test token expiration
- [ ] SSL certificate active (HTTPS)
- [ ] Error logging configured
- [ ] Backup database
- [ ] Test on mobile devices
- [ ] Add to login.php
- [ ] Monitor email delivery

## 📞 Support

For issues or questions:
- Email: bagovet_info@bccbsis.com
- Check Hostinger documentation
- Check PHPMailer documentation: https://github.com/PHPMailer/PHPMailer

## 📝 License

Copyright © 2025 Bago City Veterinary Office
All rights reserved.

## 🔄 Version History

**v1.0.0** - Initial Release
- Forgot password functionality
- Email-based reset with PHPMailer
- Secure token generation
- Password strength validation
- Mobile-responsive design
- Production-ready for Hostinger

---

**Last Updated:** January 2025  
**Status:** Production Ready ✅

