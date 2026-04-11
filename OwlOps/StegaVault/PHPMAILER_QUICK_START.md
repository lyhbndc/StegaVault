# 📧 PHPMailer Quick Reference

## ✅ What's Been Set Up

I've implemented PHPMailer integration for your StegaVault project! Here's what you now have:

### **New Files Created:**
1. ✅ **`includes/EmailService.php`** - Updated with PHPMailer support
2. ✅ **`includes/email_config.php`** - Email configuration template
3. ✅ **`test-email.php`** - Test your email setup
4. ✅ **`install-phpmailer.php`** - Installation helper
5. ✅ **`PHPMAILER_SETUP_GUIDE.md`** - Complete setup guide

---

## 🚀 Quick Start (3 Steps)

### **Step 1: Install PHPMailer**

**Option A - Using Composer (Recommended):**
```bash
cd c:\xampp\htdocs\StegaVault
composer require phpmailer/phpmailer
```

**Option B - Manual Installation:**
1. Visit: http://localhost/StegaVault/install-phpmailer.php
2. Follow the on-screen instructions

### **Step 2: Configure Email Settings**

Edit `includes/email_config.php`:

```php
'enabled' => true,  // Enable email sending

'smtp' => [
    'username' => 'your-email@gmail.com',
    'password' => 'your-app-password',  // Gmail App Password!
],
```

**For Gmail App Password:**
1. Go to: https://myaccount.google.com/apppasswords
2. Generate password
3. Copy to config file

### **Step 3: Test It**

Visit: http://localhost/StegaVault/test-email.php

---

## 📋 Email Provider Quick Config

### **Gmail**
```php
'smtp' => [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'encryption' => 'tls',
    'username' => 'your-email@gmail.com',
    'password' => 'your-16-char-app-password',
],
```

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

### **Yahoo**
```php
'smtp' => [
    'host' => 'smtp.mail.yahoo.com',
    'port' => 587,
    'encryption' => 'tls',
    'username' => 'your-email@yahoo.com',
    'password' => 'your-app-password',
],
```

---

## 🎯 How It Works

### **Current Integration:**
Your `EmailService` is already integrated in `api/users.php`:

```
Admin creates user → EmailService sends activation email → User receives email → User clicks link → Account activated
```

### **Email Types Available:**
1. ✅ **Activation Email** - Sent when new user is created
2. ✅ **Password Reset Email** - Ready to use (just call the method)

### **Add More Email Types:**
```php
// In EmailService.php
public function sendCustomEmail($to, $name, $message) {
    $subject = "Your Subject";
    $htmlBody = "<p>Hello $name, $message</p>";
    $plainBody = "Hello $name, $message";
    return $this->send($to, $subject, $htmlBody, $plainBody);
}
```

---

## 🔧 Troubleshooting

### **"SMTP connect() failed"**
- ✅ Check SMTP host and port
- ✅ Verify firewall allows port 587/465
- ✅ For Gmail, use App Password

### **"Could not authenticate"**
- ✅ Double-check username and password
- ✅ Gmail requires App Password (not regular password)
- ✅ Enable 2FA for Gmail first

### **"Class PHPMailer not found"**
- ✅ Run: `composer require phpmailer/phpmailer`
- ✅ Or install manually (see install-phpmailer.php)

### **Emails go to spam**
- ✅ Use verified domain email
- ✅ Add SPF/DKIM records
- ✅ Avoid spam trigger words

---

## 📁 File Locations

```
StegaVault/
├── includes/
│   ├── EmailService.php          ← Main email service
│   └── email_config.php          ← Your SMTP settings
├── logs/
│   ├── email_log.txt             ← Email logs (when disabled)
│   └── email_error.txt           ← Error logs
├── test-email.php                ← Test your setup
├── install-phpmailer.php         ← Installation helper
└── PHPMAILER_SETUP_GUIDE.md      ← Full documentation
```

---

## 🎨 Features

### **Smart Fallback:**
- If PHPMailer not installed → Logs to file
- If email disabled → Logs to file
- If sending fails → Logs error + falls back to file

### **Flexible Configuration:**
- Support for Composer or manual installation
- Works with Gmail, Outlook, Yahoo, custom SMTP
- Debug mode for troubleshooting
- HTML + plain text emails

### **Production Ready:**
- Error logging
- Status checking
- Configuration validation
- Secure credential handling

---

## 📞 Usage Examples

### **Send Activation Email:**
```php
require_once 'includes/EmailService.php';

$emailer = new EmailService();
$emailer->sendActivationEmail(
    'user@example.com',
    'John Doe',
    'johndoe',
    'employee',
    'http://localhost/StegaVault/activate.php?token=abc123',
    '2026-12-31'
);
```

### **Send Password Reset:**
```php
$emailer->sendPasswordResetEmail(
    'user@example.com',
    'John Doe',
    'http://localhost/StegaVault/reset.php?token=xyz789'
);
```

### **Check Status:**
```php
$status = $emailer->getStatus();
print_r($status);
// Shows: enabled, phpmailer_available, configured, etc.
```

---

## 🔐 Security Tips

1. **Never commit credentials:**
   - Add `email_config.php` to `.gitignore`
   - Use environment variables in production

2. **Use App Passwords:**
   - Gmail requires App Passwords
   - Never use your main email password

3. **Enable TLS/SSL:**
   - Always use encrypted connections
   - Prefer TLS (port 587)

4. **Rate Limiting:**
   - Consider limiting emails per user/hour
   - Track sent emails in database

---

## 🎉 Summary

**You now have:**
- ✅ PHPMailer integration ready
- ✅ Configuration template
- ✅ Test scripts
- ✅ Installation helper
- ✅ Full documentation
- ✅ Multiple email provider support
- ✅ Automatic fallback to file logging
- ✅ Error handling and logging

**Next Steps:**
1. Install PHPMailer (Composer or manual)
2. Configure `email_config.php` with your SMTP settings
3. Test with `test-email.php`
4. Set `enabled => true` when ready
5. Start sending emails! 🚀

---

**Need Help?**
- 📚 Full guide: `PHPMAILER_SETUP_GUIDE.md`
- 🔧 Installation: `install-phpmailer.php`
- 🧪 Testing: `test-email.php`
- 📝 Logs: `logs/email_log.txt` and `logs/email_error.txt`
