<?php
/**
 * StegaVault - Verification Pending Page
 * File: verification-pending.php
 * Shown after a new user is created, informing them to check their email
 */

session_start();

// Check if we should display this page
if (!isset($_SESSION['verification_email_sent'])) {
    header('Location: ../index.html');
    exit;
}

// Clear the session variable so page can't be accessed again
unset($_SESSION['verification_email_sent']);
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
    <link rel="icon" type="image/png" href="../Assets/favicon.png">
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Verify Your Email - StegaVault</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#667eea",
                        "background-dark": "#0f172a",
                        "surface-dark": "#1e293b",
                        "card-dark": "#1e293b",
                        "border-dark": "#334155"
                    },
                    fontFamily: {
                        "display": ["Space Grotesk", "sans-serif"]
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Space Grotesk', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(102, 126, 234, 0.4); }
            50% { box-shadow: 0 0 40px rgba(102, 126, 234, 0.6); }
        }
        
        .pulse-glow {
            animation: pulse-glow 2s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-background-dark min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full">
        <!-- Card -->
        <div class="bg-surface-dark border border-border-dark rounded-2xl shadow-2xl overflow-hidden">
            <!-- Header with Gradient -->
            <div class="bg-gradient-to-r from-primary to-purple-600 p-8 text-center relative overflow-hidden">
                <div class="absolute inset-0 bg-grid-white/5"></div>
                <div class="relative">
                    <div class="inline-flex items-center justify-center w-24 h-24 bg-white/20 rounded-full mb-4 pulse-glow">
                        <span class="material-symbols-outlined text-white text-6xl" style="font-variation-settings: 'FILL' 1;">mail</span>
                    </div>
                    <h1 class="text-white text-3xl font-bold mb-2">Check Your Email!</h1>
                    <p class="text-white/80 text-sm">We've sent you a verification link</p>
                </div>
            </div>
            
            <!-- Content -->
            <div class="p-8">
                <div class="mb-8">
                    <h2 class="text-white text-xl font-bold mb-4">Account Created Successfully 🎉</h2>
                    <p class="text-slate-300 mb-4 leading-relaxed">
                        Your StegaVault account has been created! To complete your registration and access the system, please verify your email address.
                    </p>
                    <p class="text-slate-300 leading-relaxed">
                        We've sent a verification email to your inbox. Click the verification link in the email to activate your account.
                    </p>
                </div>
                
                <!-- Steps -->
                <div class="space-y-4 mb-8">
                    <div class="flex gap-4 items-start">
                        <div class="flex-shrink-0 w-10 h-10 bg-primary/20 rounded-full flex items-center justify-center">
                            <span class="text-primary font-bold">1</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-white font-bold mb-1">Check your inbox</h3>
                            <p class="text-slate-400 text-sm">Look for an email from StegaVault Security</p>
                        </div>
                    </div>
                    
                    <div class="flex gap-4 items-start">
                        <div class="flex-shrink-0 w-10 h-10 bg-primary/20 rounded-full flex items-center justify-center">
                            <span class="text-primary font-bold">2</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-white font-bold mb-1">Click the verification link</h3>
                            <p class="text-slate-400 text-sm">The link will expire in 24 hours for security</p>
                        </div>
                    </div>
                    
                    <div class="flex gap-4 items-start">
                        <div class="flex-shrink-0 w-10 h-10 bg-primary/20 rounded-full flex items-center justify-center">
                            <span class="text-primary font-bold">3</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-white font-bold mb-1">Start using StegaVault</h3>
                            <p class="text-slate-400 text-sm">Once verified, you can log in and access all features</p>
                        </div>
                    </div>
                </div>
                
                <!-- Info Boxes -->
                <div class="space-y-3 mb-8">
                    <div class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-4">
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-blue-400 flex-shrink-0">info</span>
                            <div class="flex-1">
                                <p class="text-blue-300 text-sm font-medium mb-1">Didn't receive the email?</p>
                                <p class="text-blue-200/80 text-xs">Check your spam or junk folder. If you still can't find it, contact your administrator to resend the verification email.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-4">
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-amber-400 flex-shrink-0">schedule</span>
                            <div class="flex-1">
                                <p class="text-amber-300 text-sm font-medium mb-1">Time-sensitive link</p>
                                <p class="text-amber-200/80 text-xs">Your verification link will expire in 24 hours. Make sure to verify your email before then.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action -->
                <div class="text-center">
                    <a href="login.php" class="inline-flex items-center gap-2 text-slate-400 hover:text-white transition-colors">
                        <span class="material-symbols-outlined text-sm">arrow_back</span>
                        <span class="text-sm font-medium">Back to Login</span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- StegaVault Branding -->
        <div class="text-center mt-8">
            <div class="flex items-center justify-center gap-2 mb-2">
                <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
                    <span class="material-symbols-outlined text-white text-sm">shield</span>
                </div>
                <h2 class="text-white text-lg font-bold">StegaVault</h2>
            </div>
            <p class="text-slate-500 text-xs">Enterprise Digital Watermarking System</p>
        </div>
    </div>
</body>
</html>