<?php

/**
 * StegaVault - Email Service
 * File: includes/EmailService.php
 * Handles all email sending functionality
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';

class EmailService
{

    private $mail;
    private $fromEmail;
    private $fromName;

    /**
     * Constructor - Initialize PHPMailer with SMTP settings
     */
    public function __construct()
    {
        $this->mail = new PHPMailer(true);

        // SMTP Configuration
        $this->mail->isSMTP();
        $this->mail->Host = 'smtp.gmail.com';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = 'angcuteko213@gmail.com'; // TODO: Change this to your Gmail
        $this->mail->Password = 'rdid tkas akdu hrdd'; // TODO: Change this to your Gmail App Password
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $this->mail->Port = 465;

        // Default sender
        $this->fromEmail = 'angcuteko213@gmail.com'; // TODO: Change this to your Gmail
        $this->fromName = 'StegaVault Security';
    }

    /**
     * Send activation email to new user (REQUIRED by users.php)
     * 
     * @param string $email User's email address
     * @param string $name User's full name
     * @param string $username User's username
     * @param string $role User's role
     * @param string $activationLink Activation link
     * @param string|null $expirationDate Account expiration date (optional)
     * @return bool True if email sent successfully
     */
    public function sendActivationEmail($email, $name, $username, $role, $activationLink, $expirationDate = null)
    {
        try {
            $this->mail->clearAddresses();
            $this->mail->clearAttachments();

            $this->mail->setFrom($this->fromEmail, $this->fromName);
            $this->mail->addAddress($email, $name);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Activate Your StegaVault Account';

            $this->mail->Body = $this->getActivationEmailTemplate($name, $username, $role, $activationLink, $expirationDate);
            $this->mail->AltBody = "Hello $name,\n\nYour StegaVault account has been created!\n\nUsername: $username\nRole: $role\n\nActivate your account: $activationLink\n\nThank you,\nStegaVault Security Team";

            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email sending failed: " . $this->mail->ErrorInfo);
            return false;
        }
    }

    /**
     * Send verification email (for email verification flow)
     * 
     * @param string $email User's email address
     * @param string $name User's full name
     * @param string $token Verification token
     * @return bool True if email sent successfully
     */
    public function sendVerificationEmail($email, $name, $token)
    {
        try {
            $this->mail->clearAddresses();
            $this->mail->clearAttachments();

            $this->mail->setFrom($this->fromEmail, $this->fromName);
            $this->mail->addAddress($email, $name);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Verify Your StegaVault Account';

            // Create verification link (update with your domain)
            $verificationLink = 'http://localhost/StegaVault/verify.php?email=' . urlencode($email) . '&token=' . $token;

            $this->mail->Body = $this->getVerificationEmailTemplate($name, $verificationLink);
            $this->mail->AltBody = "Hello $name,\n\nPlease verify your email by clicking this link: $verificationLink\n\nThis link will expire in 24 hours.\n\nThank you,\nStegaVault Security Team";

            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email sending failed: " . $this->mail->ErrorInfo);
            return false;
        }
    }

    /**
     * Send password reset email
     * 
     * @param string $email User's email address
     * @param string $name User's full name
     * @param string $resetLink Password reset link
     * @return bool True if email sent successfully
     */
    public function sendPasswordResetEmail($email, $name, $resetLink)
    {
        try {
            $this->mail->clearAddresses();
            $this->mail->clearAttachments();

            $this->mail->setFrom($this->fromEmail, $this->fromName);
            $this->mail->addAddress($email, $name);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Reset Your StegaVault Password';

            $this->mail->Body = $this->getPasswordResetEmailTemplate($name, $resetLink);
            $this->mail->AltBody = "Hello $name,\n\nClick this link to reset your password: $resetLink\n\nThis link will expire in 1 hour.\n\nThank you,\nStegaVault Security Team";

            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email sending failed: " . $this->mail->ErrorInfo);
            return false;
        }
    }

    /**
     * Get HTML template for activation email
     */
    private function getActivationEmailTemplate($name, $username, $role, $activationLink, $expirationDate)
    {
        $expiryText = $expirationDate ? "<p style='color: #f59e0b; margin: 5px 0 0 0; font-size: 14px;'><strong>Account Expiration:</strong> " . date('F j, Y', strtotime($expirationDate)) . "</p>" : '';

        // Determine login page based on role
        $loginPage = ($role === 'admin') ? 'admin/login.php' : 'login.php';
        $loginUrl = 'http://localhost/StegaVault/' . $loginPage; // TODO: Update domain

        return '
        <!DOCTYPE html>
        <html>
        <head>
    <link rel="icon" type="image/png" href="../Assets/favicon.png">
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Activate Your Account</title>
        </head>
        <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #0f172a;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #0f172a; padding: 40px 20px;">
                <tr>
                    <td align="center">
                        <table width="600" cellpadding="0" cellspacing="0" style="background-color: #1e293b; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);">
                            <!-- Header -->
                            <tr>
                                <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center;">
                                    <div style="display: inline-block; background-color: rgba(255, 255, 255, 0.2); padding: 12px; border-radius: 12px; margin-bottom: 20px;">
                                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 2L3 7V11C3 16.55 6.84 21.74 12 23C17.16 21.74 21 16.55 21 11V7L12 2Z" fill="white"/>
                                        </svg>
                                    </div>
                                    <h1 style="color: white; margin: 0; font-size: 28px; font-weight: bold;">Account Created!</h1>
                                    <p style="color: rgba(255, 255, 255, 0.9); margin: 8px 0 0 0; font-size: 14px;">StegaVault Access Granted</p>
                                </td>
                            </tr>
                            
                            <!-- Content -->
                            <tr>
                                <td style="padding: 40px 30px;">
                                    <h2 style="color: #f1f5f9; margin: 0 0 20px 0; font-size: 24px; font-weight: bold;">Welcome to StegaVault!</h2>
                                    <p style="color: #cbd5e1; margin: 0 0 20px 0; font-size: 16px; line-height: 1.6;">Hello <strong style="color: #f1f5f9;">' . htmlspecialchars($name) . '</strong>,</p>
                                    <p style="color: #cbd5e1; margin: 0 0 20px 0; font-size: 16px; line-height: 1.6;">Your StegaVault account has been created by an administrator. Click the button below to activate your account and set up your access.</p>
                                    
                                    <!-- Account Details Box -->
                                    <div style="background-color: #334155; border-radius: 8px; padding: 20px; margin: 20px 0;">
                                        <p style="color: #94a3b8; margin: 0 0 10px 0; font-size: 14px; font-weight: bold;">Account Details:</p>
                                        <p style="color: #e2e8f0; margin: 5px 0; font-size: 14px;"><strong>Username:</strong> ' . htmlspecialchars($username) . '</p>
                                        <p style="color: #e2e8f0; margin: 5px 0; font-size: 14px;"><strong>Role:</strong> ' . ucfirst($role) . '</p>
                                        ' . $expiryText . '
                                    </div>
                                    
                                    <!-- Button -->
                                    <table width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0;">
                                        <tr>
                                            <td align="center">
                                                <a href="' . $activationLink . '" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: bold; font-size: 16px; box-shadow: 0 4px 6px rgba(102, 126, 234, 0.4);">Activate Account</a>
                                            </td>
                                        </tr>
                                    </table>
                                    
                                    <!-- Alternative Link -->
                                    <div style="background-color: #334155; border-radius: 8px; padding: 20px; margin: 20px 0;">
                                        <p style="color: #94a3b8; margin: 0 0 10px 0; font-size: 14px;">If the button doesn\'t work, copy and paste this link into your browser:</p>
                                        <p style="color: #667eea; margin: 0; font-size: 13px; word-break: break-all; font-family: monospace;">' . $activationLink . '</p>
                                    </div>
                                    
                                    <!-- Security Notice -->
                                    <div style="border-left: 3px solid #667eea; padding-left: 15px; margin: 25px 0;">
                                        <p style="color: #94a3b8; margin: 0; font-size: 14px; line-height: 1.6;"><strong style="color: #f1f5f9;">Security Notice:</strong> This activation link is for one-time use only.</p>
                                    </div>
                                    
                                    <!-- Login Link -->
                                    <div style="background-color: #334155; border-radius: 8px; padding: 20px; margin: 20px 0;">
                                        <p style="color: #94a3b8; margin: 0 0 10px 0; font-size: 14px;">After activating your account, login here:</p>
                                        <p style="margin: 0;">
                                            <a href="' . $loginUrl . '" style="color: #667eea; text-decoration: none; font-weight: bold; font-size: 14px;">→ ' . ($role === 'admin' ? 'Admin Login' : ($role === 'collaborator' ? 'Collaborator Login' : 'Employee Login')) . '</a>
                                        </p>
                                    </div>
                                    
                                    <p style="color: #94a3b8; margin: 20px 0 0 0; font-size: 14px; line-height: 1.6;">If you didn\'t expect this email, please contact your administrator.</p>
                                </td>
                            </tr>
                            
                            <!-- Footer -->
                            <tr>
                                <td style="background-color: #0f172a; padding: 30px; text-align: center; border-top: 1px solid #334155;">
                                    <p style="color: #64748b; margin: 0 0 10px 0; font-size: 12px;">&copy; ' . date('Y') . ' StegaVault. All rights reserved.</p>
                                    <p style="color: #64748b; margin: 0; font-size: 12px;">Enterprise-Grade Digital Watermarking System</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>';
    }

    /**
     * Get HTML template for verification email
     */
    private function getVerificationEmailTemplate($name, $verificationLink)
    {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Verify Your Account</title>
        </head>
        <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #0f172a;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #0f172a; padding: 40px 20px;">
                <tr>
                    <td align="center">
                        <table width="600" cellpadding="0" cellspacing="0" style="background-color: #1e293b; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);">
                            <tr>
                                <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center;">
                                    <h1 style="color: white; margin: 0; font-size: 28px; font-weight: bold;">Email Verification</h1>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 40px 30px;">
                                    <p style="color: #cbd5e1; margin: 0 0 20px 0; font-size: 16px; line-height: 1.6;">Hello <strong style="color: #f1f5f9;">' . htmlspecialchars($name) . '</strong>,</p>
                                    <p style="color: #cbd5e1; margin: 0 0 20px 0; font-size: 16px; line-height: 1.6;">Please verify your email address to complete your registration:</p>
                                    
                                    <table width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0;">
                                        <tr>
                                            <td align="center">
                                                <a href="' . $verificationLink . '" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: bold; font-size: 16px;">Verify Email</a>
                                            </td>
                                        </tr>
                                    </table>
                                    
                                    <p style="color: #94a3b8; margin: 20px 0 0 0; font-size: 14px;">This link will expire in 24 hours.</p>
                                </td>
                            </tr>
                            <tr>
                                <td style="background-color: #0f172a; padding: 30px; text-align: center; border-top: 1px solid #334155;">
                                    <p style="color: #64748b; margin: 0; font-size: 12px;">&copy; ' . date('Y') . ' StegaVault Security</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>';
    }

    /**
     * Get HTML template for password reset email
     */
    private function getPasswordResetEmailTemplate($name, $resetLink)
    {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Reset Password</title>
        </head>
        <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #0f172a;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #0f172a; padding: 40px 20px;">
                <tr>
                    <td align="center">
                        <table width="600" cellpadding="0" cellspacing="0" style="background-color: #1e293b; border-radius: 12px; overflow: hidden;">
                            <tr>
                                <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center;">
                                    <h1 style="color: white; margin: 0; font-size: 28px; font-weight: bold;">Password Reset</h1>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 40px 30px;">
                                    <p style="color: #cbd5e1; margin: 0 0 20px 0; font-size: 16px;">Hello <strong style="color: #f1f5f9;">' . htmlspecialchars($name) . '</strong>,</p>
                                    <p style="color: #cbd5e1; margin: 0 0 20px 0; font-size: 16px;">Click the button below to reset your password:</p>
                                    
                                    <table width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0;">
                                        <tr>
                                            <td align="center">
                                                <a href="' . $resetLink . '" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: bold;">Reset Password</a>
                                            </td>
                                        </tr>
                                    </table>
                                    
                                    <p style="color: #94a3b8; margin: 20px 0 0 0; font-size: 14px;">This link expires in 1 hour.</p>
                                </td>
                            </tr>
                            <tr>
                                <td style="background-color: #0f172a; padding: 30px; text-align: center; border-top: 1px solid #334155;">
                                    <p style="color: #64748b; margin: 0; font-size: 12px;">&copy; ' . date('Y') . ' StegaVault Security</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>';
    }
}
