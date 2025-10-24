# 🚀 Password Reset Feature - Deployment Checklist

## Pre-Deployment

### 📦 Files to Upload
- [ ] `forgot_password.php`
- [ ] `reset_password.php`
- [ ] `install_password_reset.php` (temporary)
- [ ] `test_email_config.php` (temporary)
- [ ] `includes/PHPMailer/` (if not using Composer)

### 🔧 System Requirements
- [ ] PHP 7.0 or higher
- [ ] MySQL/MariaDB database
- [ ] OpenSSL extension enabled
- [ ] mbstring extension enabled
- [ ] SSL certificate (HTTPS) active

## Installation Steps

### Step 1: Upload Files
```bash
# Via FTP or File Manager
1. Upload to: public_html/
2. Set permissions: 644 for .php files
3. Verify files are accessible
```

### Step 2: Install PHPMailer
**Option A: Composer (Recommended)**
```bash
cd public_html
composer require phpmailer/phpmailer
```

**Option B: Manual Upload**
```bash
# Upload to: public_html/includes/PHPMailer/
- PHPMailer.php
- SMTP.php
- Exception.php
```

### Step 3: Run Installer
```
1. Open: https://yourdomain.com/install_password_reset.php
2. Follow the 3-step wizard
3. Verify all checks pass
4. Install database changes
5. Note success message
```

### Step 4: Test Email
```
1. Open: https://yourdomain.com/test_email_config.php
2. Update $test_email variable
3. Click "Send Test Email"
4. Check email inbox and spam folder
5. Verify email received
```

### Step 5: Update Login Page
Add forgot password link to `login.php`:
```html
<div class="text-center mt-2">
  <a href="forgot_password.php" class="text-muted small">
    <i class="fas fa-key me-1"></i>Forgot Password?
  </a>
</div>
```

### Step 6: Security Cleanup
```bash
# DELETE these files after successful installation:
rm install_password_reset.php
rm test_email_config.php
rm DEPLOYMENT_CHECKLIST.md
```

## Testing Checklist

### ✅ Functional Tests
- [ ] Can access forgot_password.php
- [ ] Can submit email address
- [ ] Receives email with reset link
- [ ] Can click link and access reset_password.php
- [ ] Can set new password
- [ ] Can login with new password
- [ ] Token expires after 1 hour
- [ ] Cannot reuse same token
- [ ] Password requirements enforced
- [ ] Passwords must match

### ✅ Security Tests
- [ ] HTTPS enabled (SSL certificate)
- [ ] Passwords are hashed (bcrypt)
- [ ] Tokens are random and unique
- [ ] No email enumeration (same message for valid/invalid)
- [ ] SQL injection prevented (prepared statements)
- [ ] XSS prevented (htmlspecialchars)
- [ ] Token cleared after use
- [ ] Expired tokens rejected

### ✅ Email Tests
- [ ] Email arrives in inbox (not spam)
- [ ] HTML formatting displays correctly
- [ ] Links are clickable
- [ ] Reset button works
- [ ] Plain text fallback works
- [ ] Sender name correct
- [ ] Subject line appropriate

### ✅ Mobile Tests
- [ ] Pages responsive on phone
- [ ] Forms usable on mobile
- [ ] Buttons touchable
- [ ] Text readable
- [ ] Email displays on mobile

## Troubleshooting

### Emails Not Sending
```php
// Add to forgot_password.php for debugging:
$mail->SMTPDebug = 2;

// Check error log:
tail -f /path/to/php_error.log
```

### Database Errors
```sql
-- Verify columns exist:
DESCRIBE clients;

-- Check for reset tokens:
SELECT email, reset_token, reset_expiry FROM clients WHERE reset_token IS NOT NULL;
```

### Token Not Working
```
1. Check URL has ?token= parameter
2. Verify token is 64 characters
3. Check expiry time hasn't passed
4. Ensure token exists in database
```

## Production Checklist

### 🔐 Security
- [ ] Installer files deleted
- [ ] Test files deleted
- [ ] HTTPS enforced
- [ ] Error logging enabled
- [ ] Display_errors set to Off
- [ ] Database credentials secure
- [ ] SMTP credentials secure

### 📊 Monitoring
- [ ] Email delivery monitoring
- [ ] Error log monitoring
- [ ] Failed reset attempts tracking
- [ ] Success rate tracking

### 💾 Backup
- [ ] Database backed up
- [ ] Files backed up
- [ ] Backup restoration tested

### 📝 Documentation
- [ ] Feature documented
- [ ] Users informed
- [ ] Support team trained
- [ ] FAQ updated

## Configuration Reference

### SMTP Settings (Hostinger)
```
Host:     smtp.hostinger.com
Port:     465 (SSL)
Username: bagovet_info@bccbsis.com
Password: Y^k*/[ElK4c
```

### Database Fields
```sql
reset_token  VARCHAR(64)  NULL  -- 64-char hex token
reset_expiry DATETIME     NULL  -- Expiration timestamp
```

### Password Requirements
```
- Minimum 8 characters
- At least 1 uppercase letter
- At least 1 lowercase letter
- At least 1 number
```

## Support

### Common Issues

**Issue: "Failed to send email"**
```
Solution:
1. Verify SMTP credentials
2. Check port 465 not blocked
3. Ensure OpenSSL enabled
4. Review error logs
```

**Issue: "Invalid token"**
```
Solution:
1. Token may have expired (1 hour limit)
2. Token may have been used already
3. Request new reset link
```

**Issue: "Database error"**
```
Solution:
1. Verify columns exist
2. Check database permissions
3. Review SQL error log
```

## Post-Deployment

### Week 1
- [ ] Monitor email delivery
- [ ] Check error logs daily
- [ ] Gather user feedback
- [ ] Track success rate

### Month 1
- [ ] Review usage statistics
- [ ] Optimize if needed
- [ ] Update documentation
- [ ] Plan improvements

## Rollback Plan

If issues occur:
```bash
1. Backup current database
2. Remove database columns:
   ALTER TABLE clients DROP COLUMN reset_token;
   ALTER TABLE clients DROP COLUMN reset_expiry;
3. Remove PHP files
4. Restore previous login.php
5. Notify users
```

---

**Version:** 1.0.0  
**Last Updated:** January 2025  
**Status:** Ready for Production ✅

