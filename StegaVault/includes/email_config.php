<?php
/**
 * Email Configuration for StegaVault
 * File: includes/email_config.php
 * 
 * IMPORTANT: 
 * - Copy this file and customize for your email provider
 * - Add this file to .gitignore to keep credentials secure
 * - For Gmail, use App Passwords (not your regular password)
 */

return [
    // ============================================
    // ENABLE/DISABLE EMAIL SENDING
    // ============================================
    // Set to true to send real emails
    // Set to false to log emails to file only (for development)
    'enabled' => false, // Change to true when ready to send real emails
    
    // ============================================
    // SMTP SETTINGS
    // ============================================
    'smtp' => [
        // SMTP Server hostname
        'host' => 'smtp.gmail.com',
        
        // SMTP Port (587 for TLS, 465 for SSL)
        'port' => 587,
        
        // Encryption type ('tls' or 'ssl')
        'encryption' => 'tls',
        
        // Enable SMTP authentication
        'auth' => true,
        
        // SMTP Username (your email address)
        'username' => 'your-email@gmail.com',
        
        // SMTP Password
        // For Gmail: Use App Password (https://myaccount.google.com/apppasswords)
        // For other providers: Use your email password or app-specific password
        'password' => 'your-app-password-here',
    ],
    
    // ============================================
    // SENDER INFORMATION
    // ============================================
    'from' => [
        // Email address that appears in "From" field
        'email' => 'noreply@stegavault.local',
        
        // Name that appears in "From" field
        'name' => 'StegaVault System'
    ],
    
    // ============================================
    // DEBUG SETTINGS
    // ============================================
    // Set to true to see detailed SMTP debug output
    // Only enable for troubleshooting
    'debug' => false,
];

// ============================================
// CONFIGURATION EXAMPLES FOR DIFFERENT PROVIDERS
// ============================================

/*
// GMAIL
'smtp' => [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'encryption' => 'tls',
    'auth' => true,
    'username' => 'your-email@gmail.com',
    'password' => 'your-16-char-app-password', // Get from https://myaccount.google.com/apppasswords
],

// OUTLOOK / HOTMAIL
'smtp' => [
    'host' => 'smtp-mail.outlook.com',
    'port' => 587,
    'encryption' => 'tls',
    'auth' => true,
    'username' => 'your-email@outlook.com',
    'password' => 'your-password',
],

// YAHOO MAIL
'smtp' => [
    'host' => 'smtp.mail.yahoo.com',
    'port' => 587,
    'encryption' => 'tls',
    'auth' => true,
    'username' => 'your-email@yahoo.com',
    'password' => 'your-app-password', // Yahoo also requires app passwords
],

// CUSTOM SMTP SERVER
'smtp' => [
    'host' => 'mail.yourdomain.com',
    'port' => 587,
    'encryption' => 'tls',
    'auth' => true,
    'username' => 'noreply@yourdomain.com',
    'password' => 'your-password',
],

// SENDGRID
'smtp' => [
    'host' => 'smtp.sendgrid.net',
    'port' => 587,
    'encryption' => 'tls',
    'auth' => true,
    'username' => 'apikey',
    'password' => 'your-sendgrid-api-key',
],

// MAILGUN
'smtp' => [
    'host' => 'smtp.mailgun.org',
    'port' => 587,
    'encryption' => 'tls',
    'auth' => true,
    'username' => 'postmaster@yourdomain.mailgun.org',
    'password' => 'your-mailgun-password',
],
*/
