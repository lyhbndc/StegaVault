<?php
/**
 * Email Service Test Script
 * File: test-email.php
 * 
 * This script tests your email configuration
 * Run via browser: http://localhost/StegaVault/test-email.php
 * Or via CLI: php test-email.php
 */

require_once 'includes/db.php';
require_once 'includes/EmailService.php';

// Styling for browser output
$isCLI = php_sapi_name() === 'cli';
if (!$isCLI) {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>StegaVault Email Test</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
            .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            h1 { color: #667eea; }
            .success { color: #10b981; background: #d1fae5; padding: 15px; border-radius: 4px; margin: 15px 0; }
            .error { color: #ef4444; background: #fee2e2; padding: 15px; border-radius: 4px; margin: 15px 0; }
            .info { color: #3b82f6; background: #dbeafe; padding: 15px; border-radius: 4px; margin: 15px 0; }
            .warning { color: #f59e0b; background: #fef3c7; padding: 15px; border-radius: 4px; margin: 15px 0; }
            pre { background: #1f2937; color: #f3f4f6; padding: 15px; border-radius: 4px; overflow-x: auto; }
            .status-item { padding: 8px; margin: 5px 0; border-left: 3px solid #667eea; background: #f9fafb; }
        </style>
    </head>
    <body>
    <div class='container'>";
}

echo $isCLI ? "\n=== StegaVault Email Service Test ===\n\n" : "<h1>📧 StegaVault Email Service Test</h1>";

// Initialize email service
$emailService = new EmailService();

// Get status
$status = $emailService->getStatus();

// Display configuration status
echo $isCLI ? "Configuration Status:\n" : "<h2>Configuration Status</h2>";

foreach ($status as $key => $value) {
    $displayKey = ucwords(str_replace('_', ' ', $key));
    $displayValue = is_bool($value) ? ($value ? '✅ Yes' : '❌ No') : $value;
    
    if ($isCLI) {
        echo "  $displayKey: $displayValue\n";
    } else {
        echo "<div class='status-item'><strong>$displayKey:</strong> $displayValue</div>";
    }
}

echo $isCLI ? "\n" : "";

// Check if configured
if (!$status['phpmailer_available']) {
    $msg = "⚠️ PHPMailer is not installed!\n\nTo install:\n1. Using Composer: composer require phpmailer/phpmailer\n2. Manual: Download from https://github.com/PHPMailer/PHPMailer/releases";
    echo $isCLI ? $msg . "\n" : "<div class='warning'>" . nl2br($msg) . "</div>";
}

if (!$status['enabled']) {
    $msg = "ℹ️ Email sending is DISABLED. Emails will be logged to logs/email_log.txt\n\nTo enable: Set 'enabled' => true in includes/email_config.php";
    echo $isCLI ? $msg . "\n" : "<div class='info'>" . nl2br($msg) . "</div>";
}

// Test email sending
echo $isCLI ? "\n--- Sending Test Email ---\n" : "<h2>Sending Test Email</h2>";

$testEmail = 'test@example.com'; // Change this to your email for real testing
$testName = 'Test User';
$testUsername = 'testuser';
$testRole = 'employee';
$testActivationLink = 'http://localhost/StegaVault/activate.php?token=test123456';
$testExpiration = date('Y-m-d', strtotime('+30 days'));

echo $isCLI ? "Sending to: $testEmail\n" : "<p><strong>Sending to:</strong> $testEmail</p>";

try {
    $result = $emailService->sendActivationEmail(
        $testEmail,
        $testName,
        $testUsername,
        $testRole,
        $testActivationLink,
        $testExpiration
    );
    
    if ($result) {
        $successMsg = "✅ Email sent successfully!";
        
        if (!$status['enabled']) {
            $successMsg .= "\n\n📁 Check the email content in: logs/email_log.txt";
        } else {
            $successMsg .= "\n\n📬 Check your inbox at: $testEmail";
        }
        
        echo $isCLI ? $successMsg . "\n" : "<div class='success'>" . nl2br($successMsg) . "</div>";
        
        // Show log file content if disabled
        if (!$status['enabled']) {
            $logFile = __DIR__ . '/logs/email_log.txt';
            if (file_exists($logFile)) {
                $logContent = file_get_contents($logFile);
                $lastEmail = substr($logContent, strrpos($logContent, '=================================================='));
                
                echo $isCLI ? "\nLast Email Log:\n$lastEmail\n" : "<h3>Last Email Log:</h3><pre>" . htmlspecialchars($lastEmail) . "</pre>";
            }
        }
    } else {
        $errorMsg = "❌ Email failed to send.\n\nCheck logs/email_error.txt for details.";
        echo $isCLI ? $errorMsg . "\n" : "<div class='error'>" . nl2br($errorMsg) . "</div>";
        
        // Show error log
        $errorFile = __DIR__ . '/logs/email_error.txt';
        if (file_exists($errorFile)) {
            $errorContent = file_get_contents($errorFile);
            echo $isCLI ? "\nError Log:\n$errorContent\n" : "<h3>Error Log:</h3><pre>" . htmlspecialchars($errorContent) . "</pre>";
        }
    }
    
} catch (Exception $e) {
    $errorMsg = "❌ Exception: " . $e->getMessage();
    echo $isCLI ? $errorMsg . "\n" : "<div class='error'>$errorMsg</div>";
}

// Next steps
echo $isCLI ? "\n=== Next Steps ===\n" : "<h2>Next Steps</h2>";

$nextSteps = [];

if (!$status['phpmailer_available']) {
    $nextSteps[] = "1. Install PHPMailer (see PHPMAILER_SETUP_GUIDE.md)";
}

if (!$status['configured']) {
    $nextSteps[] = ($status['phpmailer_available'] ? "1. " : "2. ") . "Configure SMTP settings in includes/email_config.php";
}

if (!$status['enabled']) {
    $nextSteps[] = (count($nextSteps) + 1) . ". Set 'enabled' => true in includes/email_config.php";
}

$nextSteps[] = (count($nextSteps) + 1) . ". Change \$testEmail to your real email address in this file";
$nextSteps[] = (count($nextSteps) + 1) . ". Run this test again to verify";

foreach ($nextSteps as $step) {
    echo $isCLI ? "  $step\n" : "<p>$step</p>";
}

// Documentation link
$docMsg = "\n📚 For detailed setup instructions, see: PHPMAILER_SETUP_GUIDE.md";
echo $isCLI ? $docMsg . "\n" : "<div class='info'>" . nl2br($docMsg) . "</div>";

if (!$isCLI) {
    echo "</div></body></html>";
}
?>
