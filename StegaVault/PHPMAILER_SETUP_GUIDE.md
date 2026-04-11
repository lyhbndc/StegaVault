# 📧 PHPMailer Implementation Guide for StegaVault

## Overview
This guide will help you integrate PHPMailer into your StegaVault application to send real emails for user activation and notifications.

---

## 🚀 Quick Setup (Choose One Method)

### **Method 1: Using Composer (Recommended)**

1. **Install Composer** (if not already installed):
   - Download from: https://getcomposer.org/download/
   - Run the installer
   - Restart your terminal/command prompt

2. **Install PHPMailer**:
   ```bash
   cd c:\xampp\htdocs\StegaVault
   composer require phpmailer/phpmailer
   ```

3. **Skip to Step 2: Configure Email Settings**

---

### **Method 2: Manual Installation (No Composer)**

1. **Download PHPMailer**:
   - Go to: https://github.com/PHPMailer/PHPMailer/releases
   - Download the latest `PHPMailer-X.X.X.zip`
   - Extract it

2. **Copy Files**:
   - Create folder: `c:\xampp\htdocs\StegaVault\vendor\phpmailer\phpmailer\src`
   - Copy all `.php` files from the extracted `src` folder to this location
   - You should have files like: `PHPMailer.php`, `SMTP.php`, `Exception.php`, etc.

3. **Continue to Step 2**

---

## ⚙️ Step 2: Configure Email Settings

Create a configuration file for your email settings:

**File: `includes/email_config.php`**

```php
<?php
/**
 * Email Configuration
 * Copy this file and customize for your email provider
 */

return [
    // Enable/Disable email sending
    'enabled' => true, // Set to false to use file logging only
    
    // SMTP Settings
    'smtp' => [
        'host' => 'smtp.gmail.com',        // Gmail SMTP server
        'port' => 587,                      // TLS port (or 465 for SSL)
        'encryption' => 'tls',              // 'tls' or 'ssl'
        'auth' => true,                     // Enable authentication
        'username' => 'your-email@gmail.com',  // Your Gmail address
        'password' => 'your-app-password',     // Gmail App Password (NOT your regular password!)
    ],
    
    // Sender Information
    'from' => [
        'email' => 'noreply@stegavault.local',
        'name' => 'StegaVault System'
    ],
    
    // Debug Settings
    'debug' => false, // Set to true to see SMTP debug output
];
?>
```

---

## 📝 Step 3: Updated EmailService.php

I've created an updated version of your `EmailService.php` that includes PHPMailer integration.

**Key Features:**
- ✅ Automatic fallback to file logging if PHPMailer isn't available
- ✅ Support for both Composer and manual installation
- ✅ Gmail, Outlook, and custom SMTP support
- ✅ HTML and plain text emails
- ✅ Error logging
- ✅ Debug mode

---

## 🔐 Step 4: Gmail Setup (If Using Gmail)

### **Important: Use App Passwords, NOT Your Regular Password!**

1. **Enable 2-Factor Authentication**:
   - Go to: https://myaccount.google.com/security
   - Enable "2-Step Verification"

2. **Generate App Password**:
   - Go to: https://myaccount.google.com/apppasswords
   - Select "Mail" and "Windows Computer"
   - Click "Generate"
   - Copy the 16-character password (e.g., `abcd efgh ijkl mnop`)

3. **Update Configuration**:
   - Open `includes/email_config.php`
   - Set `username` to your Gmail address
   - Set `password` to the App Password (remove spaces)

---

## 📮 Alternative Email Providers

### **Outlook/Hotmail**
```php
'smtp' => [
    'host' => 'smtp-mail.outlook.com',
    'port' => 587,
    'encryption' => 'tls',
    'username' => 'your-email@outlook.com',
    'password' => 'your-password',
],
```

### **Yahoo Mail**
```php
'smtp' => [
    'host' => 'smtp.mail.yahoo.com',
    'port' => 587,
    'encryption' => 'tls',
    'username' => 'your-email@yahoo.com',
    'password' => 'your-app-password', // Yahoo also requires app passwords
],
```

### **Custom SMTP Server**
```php
'smtp' => [
    'host' => 'mail.yourdomain.com',
    'port' => 587,
    'encryption' => 'tls',
    'username' => 'noreply@yourdomain.com',
    'password' => 'your-password',
],
```

---

## ✅ Step 5: Testing

### **Test Script**

Create a test file to verify email sending:

**File: `test-email.php`**

```php
<?php
require_once 'includes/db.php';
require_once 'includes/EmailService.php';

$emailService = new EmailService();

// Test activation email
$result = $emailService->sendActivationEmail(
    'test@example.com',           // Recipient email
    'Test User',                   // Name
    'testuser',                    // Username
    'employee',                    // Role
    'http://localhost/StegaVault/activate.php?token=test123', // Activation link
    '2026-12-31'                   // Expiration date
);

if ($result) {
    echo "✅ Email sent successfully!\n";
    echo "Check your inbox or logs/email_log.txt\n";
} else {
    echo "❌ Email failed to send.\n";
    echo "Check logs/email_error.txt for details.\n";
}
?>
```

### **Run Test**:
```bash
php test-email.php
```

Or visit: `http://localhost/StegaVault/test-email.php`

---

## 🐛 Troubleshooting

### **Problem: "SMTP connect() failed"**
**Solution:**
- Check your SMTP credentials
- Verify port and encryption settings
- Make sure your firewall allows outbound connections on port 587/465
- For Gmail, ensure you're using an App Password

### **Problem: "Could not authenticate"**
**Solution:**
- Double-check username and password
- For Gmail, use App Password (not regular password)
- Ensure 2FA is enabled for Gmail

### **Problem: Emails go to spam**
**Solution:**
- Use a verified domain email address
- Add SPF and DKIM records to your domain
- Avoid spam trigger words in subject/body
- Send from a consistent email address

### **Problem: PHPMailer class not found**
**Solution:**
- Verify PHPMailer files are in `vendor/phpmailer/phpmailer/src/`
- Check file permissions
- Clear any PHP opcode cache

---

## 📊 Current Integration Points

Your `EmailService` is already integrated in:

1. **`api/users.php`** (Line 162):
   - Sends activation emails when new users are created
   - Called automatically by admin when creating accounts

2. **User Registration Flow**:
   ```
   Admin creates user → EmailService sends activation email → User clicks link → Account activated
   ```

---

## 🎯 Usage Examples

### **Send Activation Email**
```php
$emailer = new EmailService();
$emailer->sendActivationEmail(
    $email,
    $name,
    $username,
    $role,
    $activationLink,
    $expirationDate
);
```

### **Add Custom Email Method**
You can extend `EmailService.php` to add more email types:

```php
public function sendPasswordReset($to, $name, $resetLink) {
    $subject = "StegaVault - Password Reset Request";
    $body = "Click here to reset: $resetLink";
    return $this->send($to, $subject, $body, $body);
}
```

---

## 🔒 Security Best Practices

1. **Never commit email credentials to Git**:
   - Add `includes/email_config.php` to `.gitignore`
   - Use environment variables in production

2. **Use App Passwords**:
   - Never use your main email password
   - Generate app-specific passwords

3. **Enable TLS/SSL**:
   - Always use encrypted connections
   - Prefer TLS (port 587) over SSL (port 465)

4. **Rate Limiting**:
   - Consider implementing rate limits to prevent email spam
   - Track sent emails in database

---

## 📁 File Structure After Setup

```
StegaVault/
├── includes/
│   ├── EmailService.php          ← Updated with PHPMailer
│   └── email_config.php          ← New configuration file
├── vendor/
│   └── phpmailer/
│       └── phpmailer/
│           └── src/
│               ├── PHPMailer.php
│               ├── SMTP.php
│               └── Exception.php
├── logs/
│   ├── email_log.txt             ← Email logs (when disabled)
│   └── email_error.txt           ← Error logs
└── test-email.php                ← Test script
```

---

## 🎉 Summary

**What You Get:**
- ✅ Professional email sending with PHPMailer
- ✅ Automatic activation emails for new users
- ✅ HTML formatted emails with branding
- ✅ Fallback to file logging for development
- ✅ Support for Gmail, Outlook, Yahoo, and custom SMTP
- ✅ Error logging and debugging
- ✅ Easy to extend for more email types

**Next Steps:**
1. Choose installation method (Composer or Manual)
2. Create `email_config.php` with your SMTP settings
3. Update `EmailService.php` with the new code
4. Test with `test-email.php`
5. Enable in production by setting `enabled => true`

---

**Need Help?**
- Check logs in `logs/email_error.txt`
- Enable debug mode in `email_config.php`
- Test with `test-email.php` first
