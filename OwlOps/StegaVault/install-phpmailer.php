<?php
/**
 * PHPMailer Manual Installation Helper
 * File: install-phpmailer.php
 * 
 * This script helps you manually install PHPMailer if Composer is not available
 * Run via browser: http://localhost/StegaVault/install-phpmailer.php
 */

$isCLI = php_sapi_name() === 'cli';

if (!$isCLI) {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>PHPMailer Installation Helper</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
            .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            h1 { color: #667eea; }
            .success { color: #10b981; background: #d1fae5; padding: 15px; border-radius: 4px; margin: 15px 0; }
            .error { color: #ef4444; background: #fee2e2; padding: 15px; border-radius: 4px; margin: 15px 0; }
            .info { color: #3b82f6; background: #dbeafe; padding: 15px; border-radius: 4px; margin: 15px 0; }
            .warning { color: #f59e0b; background: #fef3c7; padding: 15px; border-radius: 4px; margin: 15px 0; }
            pre { background: #1f2937; color: #f3f4f6; padding: 15px; border-radius: 4px; overflow-x: auto; }
            .step { background: #f9fafb; padding: 15px; margin: 10px 0; border-left: 4px solid #667eea; }
            code { background: #e5e7eb; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
            .btn { display: inline-block; background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin: 10px 5px; }
            .btn:hover { background: #5568d3; }
        </style>
    </head>
    <body>
    <div class='container'>";
}

echo $isCLI ? "\n=== PHPMailer Installation Helper ===\n\n" : "<h1>📦 PHPMailer Installation Helper</h1>";

// Check if PHPMailer is already installed
$vendorPath = __DIR__ . '/vendor/phpmailer/phpmailer/src';
$composerAutoload = __DIR__ . '/vendor/autoload.php';

$phpmailerInstalled = false;
$installMethod = 'none';

if (file_exists($composerAutoload)) {
    $phpmailerInstalled = true;
    $installMethod = 'composer';
} elseif (file_exists($vendorPath . '/PHPMailer.php')) {
    $phpmailerInstalled = true;
    $installMethod = 'manual';
}

// Display current status
if ($phpmailerInstalled) {
    $msg = "✅ PHPMailer is already installed!\n\nInstallation method: " . ucfirst($installMethod);
    echo $isCLI ? $msg . "\n" : "<div class='success'>" . nl2br($msg) . "</div>";
    
    if (!$isCLI) {
        echo "<p><a href='test-email.php' class='btn'>Test Email Configuration →</a></p>";
    }
} else {
    $msg = "⚠️ PHPMailer is not installed yet.";
    echo $isCLI ? $msg . "\n\n" : "<div class='warning'>" . nl2br($msg) . "</div>";
}

// Installation instructions
echo $isCLI ? "=== Installation Methods ===\n\n" : "<h2>Installation Methods</h2>";

// Method 1: Composer
echo $isCLI ? "Method 1: Using Composer (Recommended)\n" : "<div class='step'><h3>Method 1: Using Composer (Recommended)</h3>";

$composerSteps = "1. Download Composer from: https://getcomposer.org/download/
2. Install Composer (run the installer)
3. Open Command Prompt in StegaVault folder
4. Run: composer require phpmailer/phpmailer
5. Done!";

echo $isCLI ? $composerSteps . "\n\n" : "<pre>" . $composerSteps . "</pre>";

if (!$isCLI) {
    echo "<p><a href='https://getcomposer.org/download/' target='_blank' class='btn'>Download Composer</a></p></div>";
}

// Method 2: Manual
echo $isCLI ? "Method 2: Manual Installation\n" : "<div class='step'><h3>Method 2: Manual Installation</h3>";

$manualSteps = "1. Download PHPMailer from: https://github.com/PHPMailer/PHPMailer/releases
2. Extract the ZIP file
3. Create folder structure:
   c:\\xampp\\htdocs\\StegaVault\\vendor\\phpmailer\\phpmailer\\src\\
4. Copy all .php files from extracted 'src' folder to the folder above
5. You should have these files:
   - PHPMailer.php
   - SMTP.php
   - Exception.php
   - (and others)
6. Done!";

echo $isCLI ? $manualSteps . "\n\n" : "<pre>" . $manualSteps . "</pre>";

if (!$isCLI) {
    echo "<p><a href='https://github.com/PHPMailer/PHPMailer/releases' target='_blank' class='btn'>Download PHPMailer</a></p></div>";
}

// Verification
echo $isCLI ? "=== Verify Installation ===\n" : "<h2>Verify Installation</h2>";

$verifyMsg = "After installation, check if these files exist:";
echo $isCLI ? $verifyMsg . "\n" : "<p>$verifyMsg</p>";

$requiredFiles = [
    'vendor/phpmailer/phpmailer/src/PHPMailer.php',
    'vendor/phpmailer/phpmailer/src/SMTP.php',
    'vendor/phpmailer/phpmailer/src/Exception.php'
];

foreach ($requiredFiles as $file) {
    $fullPath = __DIR__ . '/' . $file;
    $exists = file_exists($fullPath);
    $status = $exists ? '✅' : '❌';
    
    if ($isCLI) {
        echo "  $status $file\n";
    } else {
        $class = $exists ? 'success' : 'error';
        echo "<div style='padding: 5px; margin: 5px 0;'>$status <code>$file</code></div>";
    }
}

// Next steps
echo $isCLI ? "\n=== Next Steps ===\n" : "<h2>Next Steps After Installation</h2>";

$nextSteps = [
    "1. Configure email settings in: includes/email_config.php",
    "2. Set your SMTP credentials (Gmail, Outlook, etc.)",
    "3. For Gmail: Use App Password (not regular password)",
    "4. Set 'enabled' => true to enable email sending",
    "5. Run test-email.php to verify everything works"
];

foreach ($nextSteps as $step) {
    echo $isCLI ? "  $step\n" : "<p>$step</p>";
}

// Documentation
$docMsg = "\n📚 For detailed instructions, see: PHPMAILER_SETUP_GUIDE.md";
echo $isCLI ? $docMsg . "\n" : "<div class='info'>" . nl2br($docMsg) . "</div>";

if (!$isCLI) {
    echo "<p><a href='PHPMAILER_SETUP_GUIDE.md' class='btn'>View Setup Guide</a> 
          <a href='test-email.php' class='btn'>Test Email</a></p>";
    echo "</div></body></html>";
}
?>
