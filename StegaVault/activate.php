<?php

/**
 * StegaVault - Account Activation Handler
 * File: activate.php
 * Handles account activation when users click the activation link
 */

session_start();
require_once 'includes/db.php';
require_once 'includes/ActivityLogger.php';

$message = '';
$success = false;
$token = $_GET['token'] ?? '';
$redirectTo = 'employee/login.php'; // Default to employee login

if (empty($token)) {
    $message = 'Invalid activation link. Please check your email and try again.';
} else {
    // Find user with matching activation token
    $stmt = $db->prepare("
        SELECT id, name, email, role, status, activation_token 
        FROM users 
        WHERE activation_token = ?
    ");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $message = 'Invalid activation link. The token may have already been used or is incorrect.';
    } else {
        $user = $result->fetch_assoc();

        // Check if already activated
        if ($user['status'] === 'active' && empty($user['activation_token'])) {
            $message = 'Your account has already been activated. You can now log in.';
            $success = true;
            switch ($user['role']) {
                case 'admin':
                    $redirectTo = 'admin/login.php';
                    break;
                case 'collaborator':
                    $redirectTo = 'collaborator/login.php';
                    break;
                case 'employee':
                default:
                    $redirectTo = 'employee/login.php';
                    break;
            }
        }
        // Activate the user
        else {
            $stmt = $db->prepare("
                UPDATE users 
                SET status = 'active', activation_token = NULL, is_verified = 1 
                WHERE id = ?
            ");
            $stmt->bind_param('i', $user['id']);

            if ($stmt->execute()) {
                // Log activation
                $description = "Account activated for user: {$user['name']} ({$user['email']})";
                logActivityEvent($db, (int)$user['id'], 'account_activated', $description, $_SERVER['REMOTE_ADDR'] ?? null, $user['role'] ?? null, false);

                $message = 'Your account has been successfully activated! You can now log in to StegaVault.';
                $success = true;
                switch ($user['role']) {
                    case 'admin':
                        $redirectTo = 'admin/login.php';
                        break;
                    case 'collaborator':
                        $redirectTo = 'collaborator/login.php';
                        break;
                    case 'employee':
                    default:
                        $redirectTo = 'employee/login.php';
                        break;
                }
            } else {
                $message = 'An error occurred during activation. Please try again or contact support.';
            }
        }
    }
}

// Database connection will close automatically
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Account Activation - StegaVault</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
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
        body {
            font-family: 'Space Grotesk', sans-serif;
        }

        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
</head>

<body class="bg-background-dark min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <!-- Card -->
        <div class="bg-surface-dark border border-border-dark rounded-2xl shadow-2xl overflow-hidden">
            <!-- Header -->
            <div class="<?php echo $success ? 'bg-gradient-to-r from-green-600 to-emerald-600' : 'bg-gradient-to-r from-red-600 to-rose-600'; ?> p-8 text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-white/20 rounded-full mb-4">
                    <span class="material-symbols-outlined text-white text-5xl" style="font-variation-settings: 'FILL' 1;">
                        <?php echo $success ? 'check_circle' : 'error'; ?>
                    </span>
                </div>
                <h1 class="text-white text-2xl font-bold">
                    <?php echo $success ? 'Activation Successful!' : 'Activation Failed'; ?>
                </h1>
            </div>

            <!-- Content -->
            <div class="p-8">
                <div class="mb-6">
                    <p class="text-slate-300 text-center leading-relaxed">
                        <?php echo htmlspecialchars($message); ?>
                    </p>
                </div>

                <?php if ($success): ?>
                    <!-- Success Actions -->
                    <div class="space-y-3">
                        <a href="<?php echo $redirectTo; ?>" class="block w-full bg-gradient-to-r from-primary to-purple-600 hover:from-primary/90 hover:to-purple-600/90 text-white text-center font-bold py-3 px-6 rounded-xl transition-all shadow-lg shadow-primary/20">
                            <span class="flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined">login</span>
                                <?php echo ($redirectTo === 'admin/login.php') ? 'Login to Admin Panel' : (($redirectTo === 'collaborator/login.php') ? 'Login as Collaborator' : 'Login to StegaVault'); ?>
                            </span>
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Error Actions -->
                    <div class="space-y-3">
                        <a href="employee/login.php" class="block w-full bg-slate-700 hover:bg-slate-600 text-white text-center font-bold py-3 px-6 rounded-xl transition-all">
                            <span class="flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined">arrow_back</span>
                                Back to Login
                            </span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Footer -->
            <div class="bg-card-dark/50 border-t border-border-dark px-8 py-4 text-center">
                <p class="text-slate-500 text-xs">
                    Need help? Contact <a href="mailto:support@stegavault.com" class="text-primary hover:underline">support@stegavault.com</a>
                </p>
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