<?php

/**
 * StegaVault - Forensic Analysis (Formerly Extraction)
 * File: admin/analysis.php
 */

session_start();
require_once '../includes/db.php';
require_once '../includes/watermark.php';
require_once '../includes/CryptoWatermark.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.html');
    exit;
}

$user = [
    'id' => $_SESSION['user_id'],
    'email' => $_SESSION['email'],
    'name' => $_SESSION['name'],
    'role' => $_SESSION['role']
];

$extractedData = null;
$error = null;
$fileName = null;
$fileSize = null;
$extractionTime = null;
$cryptoVerification = null;
$hasCrypto = false;

// Handle file upload for extraction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['suspect_file'])) {
    $file = $_FILES['suspect_file'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $tmpPath = $file['tmp_name'];
        $fileName = $file['name'];
        $fileSize = $file['size'];

        $startTime = microtime(true);

        $extractedData = Watermark::extractWatermark($tmpPath);

        $extractionTime = round((microtime(true) - $startTime) * 1000, 2);

        if ($extractedData === false) {
            $error = "Analysis complete: No valid forensic signature found. The file may be clean, or the digital signature has been compromised due to tampering.";
        }
        else {
            // Forensic content hash comparison
            $currentImgHash = Watermark::calculateImageHash($tmpPath);
            $extractedData['content_tampered'] = false;

            if (isset($extractedData['content_hash'])) {
                if ($currentImgHash !== $extractedData['content_hash']) {
                    $extractedData['content_tampered'] = true;
                }
            }

            if (isset($extractedData['crypto'])) {
                $hasCrypto = true;

                $userId = $extractedData['crypto']['public']['user_id'];
                $stmt = $db->prepare("SELECT id, email FROM users WHERE id = ?");

                if ($stmt) {
                    $stmt->bind_param('i', $userId);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result && $result->num_rows > 0) {
                        $watermarkUser = $result->fetch_assoc();
                        $userData = [
                            'id' => $watermarkUser['id'],
                            'email' => $watermarkUser['email']
                        ];
                        $cryptoVerification = CryptoWatermark::verifyWatermark($extractedData['crypto'], $userData);
                        if ($cryptoVerification && $cryptoVerification['valid'] === true) {
                            @CryptoWatermark::logVerification($db, $extractedData['crypto']['signature']);
                        }
                    }
                } else {
                    // Suppress DB errors here but flag that crypto verification was skipped
                    $hasCrypto = false;
                    error_log("Forensic Analysis: DB unreachable for crypto verification");
                }
            }
        }

        // Persist analysis result to forensic_analysis_log for reports
        $logStatus = ($extractedData === false) ? 'NO_WATERMARK'
            : ($extractedData['content_tampered'] ? 'TAMPERED' : 'VALID');
        $wFound    = ($extractedData !== false) ? 1 : 0;
        $logMime   = $file['type'] ?? null;
        $logHash   = $extractedData['content_hash'] ?? null;
        $logUid    = isset($extractedData['u_id']) ? (string)(int)$extractedData['u_id']
            : (isset($extractedData['user_id']) ? (string)(int)$extractedData['user_id'] : null);
        $logUname  = $extractedData['u_name'] ?? $extractedData['user_name'] ?? null;
        $logUrole  = $extractedData['u_role'] ?? null;
        $logIp     = $extractedData['ip'] ?? null;
        $logCrypto = $hasCrypto
            ? ($cryptoVerification && ($cryptoVerification['valid'] ?? false) ? '1' : '0')
            : null;

        @$db->query("CREATE TABLE IF NOT EXISTS forensic_analysis_log (
            id SERIAL PRIMARY KEY,
            analyzed_by INT NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_size BIGINT DEFAULT 0,
            mime_type VARCHAR(100) DEFAULT NULL,
            integrity_status VARCHAR(20) NOT NULL CHECK (integrity_status IN ('VALID','TAMPERED','NO_WATERMARK')),
            watermark_found SMALLINT DEFAULT 0,
            content_hash VARCHAR(64) DEFAULT NULL,
            extracted_user_id INT DEFAULT NULL,
            extracted_user_name VARCHAR(255) DEFAULT NULL,
            extracted_user_role VARCHAR(50) DEFAULT NULL,
            extracted_ip VARCHAR(45) DEFAULT NULL,
            crypto_verified SMALLINT DEFAULT NULL,
            analysis_time_ms DOUBLE PRECISION DEFAULT NULL,
            analyzed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $insStmt = $db->prepare("INSERT INTO forensic_analysis_log
            (analyzed_by, file_name, file_size, mime_type, integrity_status, watermark_found,
             content_hash, extracted_user_id, extracted_user_name, extracted_user_role,
             extracted_ip, crypto_verified, analysis_time_ms)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($insStmt) {
            $insStmt->bind_param('issssissssssd',
                $user['id'], $fileName, $fileSize, $logMime, $logStatus,
                $wFound, $logHash, $logUid, $logUname, $logUrole,
                $logIp, $logCrypto, $extractionTime
            );
            $insStmt->execute();
        }
    }
    else {
        $error = "Failed to upload file.";
    }
}

function safe_html($value, $default = 'N/A')
{
    return htmlspecialchars($value ?? $default);
}

function format_file_size($bytes)
{
    $size = (int)$bytes;
    if ($size <= 0) {
        return 'N/A';
    }
    if ($size >= 1073741824) {
        return round($size / 1073741824, 2) . ' GB';
    }
    if ($size >= 1048576) {
        return round($size / 1048576, 2) . ' MB';
    }
    if ($size >= 1024) {
        return round($size / 1024, 2) . ' KB';
    }
    return $size . ' B';
}

function format_ts($value)
{
    if ($value === null || $value === '') {
        return 'N/A';
    }
    if (is_numeric($value)) {
        return date('Y-m-d H:i:s', (int)$value);
    }

    $parsed = strtotime((string)$value);
    return $parsed ? date('Y-m-d H:i:s', $parsed) : (string)$value;
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <link rel="icon" type="image/png" href="../icon.png">
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Forensic Analysis - StegaVault</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#667eea",
                        "background-light": "#f5f6f8",
                        "background-dark": "#101622",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"],
                        "mono": ["ui-monospace", "monospace"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100 min-h-screen flex">

    <!-- Sidebar -->
    <aside
        class="w-64 border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-background-dark flex flex-col fixed inset-y-0 left-0 z-50">
        <div class="p-6 flex flex-col h-full">
            <!-- Logo -->
            <div class="flex items-center gap-3 mb-10">
                <img src="../PGMN%20LOGOS%20white.png" alt="PGMN Inc. Logo"
                    class="h-12 w-auto object-contain dark:invert-0 invert" />
                <div class="flex flex-col justify-center">
                    <h1 class="text-slate-900 dark:text-white text-base font-bold leading-tight">PGMN Inc.</h1>
                    <p class="text-slate-500 dark:text-slate-400 text-xs font-medium">Security Suite</p>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex flex-col gap-1 flex-1">
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    href="dashboard.php">
                    <span class="material-symbols-outlined text-[22px]">dashboard</span>
                    <p class="text-sm font-medium">Dashboard</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    href="projects.php">
                    <span class="material-symbols-outlined text-[22px]">folder_managed</span>
                    <p class="text-sm font-medium">Projects</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary text-white" href="analysis.php">
                    <span class="material-symbols-outlined text-[22px]"
                        style="font-variation-settings: 'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;">policy</span>
                    <p class="text-sm font-medium">Forensic Analysis</p>
                </a>
                <?php if ($user['role'] === 'admin'): ?>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    href="users.php">
                    <span class="material-symbols-outlined text-[22px]">group</span>
                    <p class="text-sm font-medium">User Management</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    href="activity.php">
                    <span class="material-symbols-outlined text-[22px]">history</span>
                    <p class="text-sm font-medium">Activity Logs</p>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    href="reports.php">
                    <span class="material-symbols-outlined text-[22px]">summarize</span>
                    <p class="text-sm font-medium">Reports</p>
                </a>
                <?php
endif; ?>
            </nav>

            <!-- User Profile (click to open settings) -->
            <div class="pt-6 border-t border-slate-200 dark:border-slate-800">
                <button onclick="openSettings()"
                    class="w-full flex items-center gap-3 p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group text-left">
                    <div id="sidebarProfileAvatar"
                        class="bg-primary rounded-full size-10 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                        <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p id="sidebarProfileName"
                            class="text-slate-900 dark:text-white text-sm font-semibold truncate">
                            <?php echo htmlspecialchars($user['name']); ?>
                        </p>
                        <p class="text-slate-500 dark:text-slate-400 text-xs capitalize">
                            <?php echo htmlspecialchars($user['role']); ?>
                        </p>
                    </div>
                    <span
                        class="material-symbols-outlined text-slate-400 group-hover:text-primary text-[18px] transition-colors">settings</span>
                </button>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 ml-64 flex flex-col">
        <!-- Sticky Top Header -->
        <header
            class="h-16 border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-background-dark/80 backdrop-blur-md sticky top-0 z-40 px-8 flex items-center gap-6">
            <h2 class="text-slate-900 dark:text-white text-lg font-bold tracking-tight flex-shrink-0">Forensic Analysis
            </h2>
            <?php include '../includes/search_bar.php'; ?>
            <div
                class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-500/10 text-emerald-500 text-xs font-semibold flex-shrink-0">
                <span class="size-2 rounded-full bg-emerald-500"></span>
                System: Operational
            </div>
        </header>


        <div class="p-8 max-w-4xl mx-auto w-full space-y-6">

            <?php if (!$extractedData && !$error): ?>
            <!-- ── Upload State ── -->
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-primary/10 rounded-lg">
                    <span class="material-symbols-outlined text-primary">policy</span>
                </div>
                <div>
                    <h1 class="text-slate-900 dark:text-white text-xl font-bold">Upload Evidence File</h1>
                    <p class="text-slate-500 dark:text-slate-400 text-sm">Upload a suspected leaked file to verify
                        integrity and identify the source.</p>
                </div>
            </div>

            <div
                class="bg-white dark:bg-slate-900 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-xl p-10 text-center shadow-sm">
                <div class="max-w-sm mx-auto">
                    <div class="size-16 mx-auto mb-4 bg-primary/10 rounded-full flex items-center justify-center">
                        <span class="material-symbols-outlined text-4xl text-primary">search</span>
                    </div>
                    <h3 class="text-slate-900 dark:text-white text-lg font-bold mb-1">Forensic Watermark Analysis</h3>
                    <p class="text-slate-500 dark:text-slate-400 text-sm mb-6">Upload a suspected leaked file to verify
                        integrity and identify the source.</p>
                    <form method="POST" enctype="multipart/form-data" id="mainUploadForm">
                        <input type="file" id="mainFileInput" name="suspect_file"
                            accept=".png,.mp4,.pdf,.doc,.docx,.xls,.xlsx" style="display: none;"
                            onchange="document.getElementById('mainUploadForm').submit()" />
                        <button type="button" onclick="document.getElementById('mainFileInput').click()"
                            class="px-6 py-2.5 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-primary/90 transition-colors">
                            Select File or Drag & Drop
                        </button>
                    </form>
                </div>
            </div>

            <?php
elseif ($error): ?>
            <!-- ── Error State ── -->
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-red-500/10 rounded-lg">
                    <span class="material-symbols-outlined text-red-500">gpp_bad</span>
                </div>
                <div>
                    <h1 class="text-slate-900 dark:text-white text-xl font-bold">Warning: Integrity Issue</h1>
                    <p class="text-slate-500 dark:text-slate-400 text-sm">The forensic analysis encountered a problem.
                    </p>
                </div>
            </div>

            <div
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 shadow-sm">
                <div class="flex items-start gap-4">
                    <div class="p-3 bg-red-500/10 rounded-lg flex-shrink-0">
                        <span class="material-symbols-outlined text-red-500 text-2xl">warning</span>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-slate-900 dark:text-white font-bold mb-1">Forensic Analysis Failed</h3>
                        <p class="text-slate-500 dark:text-slate-400 text-sm mb-4">
                            <?php echo htmlspecialchars($error); ?>
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-500/10 text-red-500 border border-red-500/20">
                                <span class="size-1.5 bg-red-500 rounded-full"></span>
                                Integrity: Compromised / Unknown
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-500/10 text-slate-400 border border-slate-500/20">
                                <span class="material-symbols-outlined text-[13px]">location_off</span>
                                Where from: External / Unknown
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-700/20 text-slate-400 border border-slate-500/20">
                                <span class="material-symbols-outlined text-[13px]">router</span>
                                Submitted from: <?php
                                    $submitterIp = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                                    echo htmlspecialchars(($submitterIp === '::1' || $submitterIp === '127.0.0.1') ? 'localhost' : $submitterIp);
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <input type="file" id="retryInput" name="suspect_file" accept=".png,.mp4,.pdf,.doc,.docx,.xls,.xlsx"
                    style="display: none;" onchange="this.form.submit()" />
                <button type="button" onclick="document.getElementById('retryInput').click()"
                    class="w-full py-3 px-6 bg-primary hover:bg-primary/90 text-white font-bold rounded-lg transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">refresh</span>
                    Analyze Another File
                </button>
            </form>

            <?php
else: ?>
            <!-- ── Success State ── -->
            <div id="forensicReport" class="space-y-6">
                <div class="flex items-center gap-3 mb-2 print:hidden">
                    <div class="p-2 bg-emerald-500/10 rounded-lg">
                        <span class="material-symbols-outlined text-emerald-500">verified_user</span>
                    </div>
                    <div>
                        <h1 class="text-slate-900 dark:text-white text-xl font-bold">Forensic Confirmation</h1>
                        <p class="text-slate-500 dark:text-slate-400 text-sm">
                            <?php echo ($extractedData['content_tampered'] ?? false) ? 'Warning: Digital signature is valid, but content tampering was detected.' : 'Digital signature verified — watermark is intact and authentic.'; ?>
                        </p>
                    </div>
                </div>

                <!-- ── Origin Card ── -->
                <?php
                $originName = $extractedData['u_name'] ?? $extractedData['user_name'] ?? null;
                $originRole = $extractedData['u_role'] ?? null;
                $originId   = $extractedData['u_id']   ?? $extractedData['user_id'] ?? null;
                $originIp   = $extractedData['ip']     ?? null;
                $originTs   = $extractedData['ts']     ?? $extractedData['timestamp'] ?? null;
                $fromSystem = ($originName !== null);
                ?>
                <div class="bg-white dark:bg-slate-900 border <?php echo $fromSystem ? 'border-primary/30' : 'border-slate-200 dark:border-slate-700'; ?> rounded-xl p-6 shadow-sm">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="material-symbols-outlined text-[18px] <?php echo $fromSystem ? 'text-primary' : 'text-slate-400'; ?>">
                            <?php echo $fromSystem ? 'travel_explore' : 'location_off'; ?>
                        </span>
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">File Origin</h2>
                        <?php if ($fromSystem): ?>
                        <span class="ml-auto inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary border border-primary/20">
                            <span class="material-symbols-outlined text-[13px]">verified</span>
                            From this system
                        </span>
                        <?php else: ?>
                        <span class="ml-auto inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-500/10 text-slate-400 border border-slate-500/20">
                            <span class="material-symbols-outlined text-[13px]">location_off</span>
                            Where from: Unknown
                        </span>
                        <?php endif; ?>
                    </div>

                    <?php if ($fromSystem): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-4">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Downloaded by</p>
                            <div class="flex items-center gap-2 mt-1">
                                <div class="size-8 rounded-full bg-primary/20 flex items-center justify-center text-primary font-bold text-xs flex-shrink-0">
                                    <?php echo strtoupper(substr($originName, 0, 2)); ?>
                                </div>
                                <div>
                                    <p class="text-slate-900 dark:text-white font-bold text-sm"><?php echo htmlspecialchars($originName); ?></p>
                                    <?php if ($originRole): ?>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-primary/10 text-primary uppercase"><?php echo htmlspecialchars($originRole); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-4">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Where from</p>
                            <div class="flex items-center gap-2 mt-2">
                                <span class="material-symbols-outlined text-slate-400 text-[18px]">router</span>
                                <p class="text-slate-900 dark:text-white font-mono font-semibold text-sm">
                                    <?php
                                    $ip = $originIp ?? 'Unknown';
                                    echo htmlspecialchars(($ip === '::1' || $ip === '127.0.0.1') ? 'localhost' : $ip);
                                    ?>
                                </p>
                            </div>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-4">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Downloaded At</p>
                            <div class="flex items-center gap-2 mt-2">
                                <span class="material-symbols-outlined text-slate-400 text-[18px]">schedule</span>
                                <p class="text-slate-900 dark:text-white font-semibold text-sm">
                                    <?php echo $originTs ? date('M j, Y g:i A', (int)$originTs) : 'N/A'; ?>
                                </p>
                            </div>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-4">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">User ID</p>
                            <div class="flex items-center gap-2 mt-2">
                                <span class="material-symbols-outlined text-slate-400 text-[18px]">badge</span>
                                <p class="text-slate-900 dark:text-white font-mono font-semibold text-sm">#<?php echo htmlspecialchars((string)($originId ?? 'N/A')); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <p class="text-slate-500 dark:text-slate-400 text-sm">No watermark or origin data could be extracted from this file. It may not have been downloaded from this system, or the watermark was stripped.</p>
                    <?php endif; ?>
                </div>

                <!-- Printable Report Wrapper -->
                <div id="printableReport" class="space-y-6 bg-transparent dark:bg-transparent p-1">

                    <!-- Professional Header (Visible in PDF) -->
                    <div
                        class="hidden print-only-block bg-white text-slate-900 p-8 border-b-2 border-slate-100 mb-6 rounded-t-xl">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="bg-primary p-1.5 rounded flex items-center justify-center">
                                        <span class="material-symbols-outlined text-white text-md">shield</span>
                                    </div>
                                    <h2 class="text-slate-900 font-black uppercase tracking-tighter text-lg">StegaVault
                                    </h2>
                                </div>
                                <h1 class="text-2xl font-bold text-slate-800">Forensic Audit & Integrity Report</h1>
                                <p class="text-slate-500 text-sm font-mono mt-1">Ref No:
                                    SV-FOR-
                                    <?php echo strtoupper(substr(md5($fileName . time()), 0, 8)); ?>
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">
                                    Generated At</p>
                                <p class="text-xs font-semibold text-slate-900">
                                    <?php echo date('F j, Y \a\t g:i A'); ?>
                                </p>
                                <div
                                    class="mt-4 px-3 py-1 bg-red-500/10 text-red-600 border border-red-500/20 rounded inline-block text-[9px] font-bold uppercase">
                                    Official Use Only</div>
                            </div>
                        </div>
                    </div>
                    <div
                        class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div
                                    class="p-3 <?php echo ($extractedData['content_tampered'] ?? false) ? 'bg-red-500/10' : 'bg-emerald-500/10'; ?> rounded-lg">
                                    <span
                                        class="material-symbols-outlined <?php echo ($extractedData['content_tampered'] ?? false) ? 'text-red-500' : 'text-emerald-500'; ?> text-2xl">fingerprint</span>
                                </div>
                                <div>
                                    <h3 class="text-slate-900 dark:text-white font-bold">
                                        <?php echo ($extractedData['content_tampered'] ?? false) ? 'TAMPERED File Content' : 'Digital Signature Verified'; ?>
                                    </h3>
                                    <p class="text-slate-500 dark:text-slate-400 text-sm">
                                        <?php echo ($extractedData['content_tampered'] ?? false) ? 'The file contents have been modified since download!' : 'The forensic watermark is intact and authentic.'; ?>
                                    </p>
                                </div>
                            </div>
                            <?php if ($extractedData['content_tampered'] ?? false): ?>
                            <span
                                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20">
                                <span class="material-symbols-outlined text-sm">warning</span>
                                Integrity: TAMPERED
                            </span>
                            <?php
    else: ?>
                            <span
                                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                <span class="material-symbols-outlined text-sm">verified</span>
                                Integrity: Valid
                            </span>
                            <?php
    endif; ?>
                        </div>
                    </div>

                    <!-- Decoded Forensic Payload -->
                    <div
                        class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-[18px]">data_object</span>
                            <h2 class="text-slate-900 dark:text-white text-sm font-bold uppercase tracking-wider">
                                Decoded
                                Forensic Payload</h2>
                        </div>
                        <div class="p-6 font-mono text-sm bg-slate-50 dark:bg-slate-950">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <p
                                        class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3">
                                        Leak Source (Downloader)</p>
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="material-symbols-outlined text-primary text-[18px]">person</span>
                                            <span class="text-slate-900 dark:text-white font-bold">
                                                <?php echo safe_html($extractedData['u_name'] ?? $extractedData['user_name'] ?? 'Unknown'); ?>
                                            </span>
                                        </div>
                                        <p class="text-slate-500 dark:text-slate-400 text-xs">Role: <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary border border-primary/20 uppercase">
                                                <?php echo safe_html($extractedData['u_role'] ?? 'N/A'); ?>
                                            </span>
                                        </p>
                                        <p class="text-slate-500 dark:text-slate-400 text-xs">User ID: <span
                                                class="text-slate-900 dark:text-white">
                                                <?php echo safe_html($extractedData['u_id'] ?? $extractedData['user_id'] ?? 'N/A'); ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <p
                                        class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3">
                                        Original Uploader</p>
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="material-symbols-outlined text-emerald-500 text-[18px]">badge</span>
                                            <span class="text-slate-900 dark:text-white font-bold">
                                                <?php echo safe_html($extractedData['f_owner_name'] ?? 'Unknown'); ?>
                                            </span>
                                        </div>
                                        <p class="text-slate-500 dark:text-slate-400 text-xs">Role: <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 uppercase">
                                                <?php echo safe_html($extractedData['f_owner_role'] ?? 'N/A'); ?>
                                            </span>
                                        </p>
                                        <p class="text-slate-500 dark:text-slate-400 text-xs">User ID: <span
                                                class="text-slate-900 dark:text-white">
                                                <?php echo safe_html($extractedData['f_owner_id'] ?? 'N/A'); ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <p
                                        class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3">
                                        Network Trace</p>
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="material-symbols-outlined text-primary text-[18px]">router</span>
                                            <span class="text-slate-900 dark:text-white font-bold">
                                                <?php
    $ip = $extractedData['ip'] ?? 'unknown';
    echo ($ip === '::1' || $ip === '127.0.0.1') ? 'localhost' : htmlspecialchars($ip);
?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-6 pt-6 border-t border-slate-200 dark:border-slate-800">
                                <p
                                    class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3">
                                    Event Metadata</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs">
                                    <p class="text-slate-500 dark:text-slate-400">Timestamp: <span
                                            class="text-slate-900 dark:text-white">
                                            <?php echo date('Y-m-d H:i:s', $extractedData['ts'] ?? $extractedData['timestamp'] ?? time()); ?>
                                        </span>
                                    </p>
                                    <p class="text-slate-500 dark:text-slate-400">File Reference ID: <span
                                            class="text-slate-900 dark:text-white">#
                                            <?php echo safe_html($extractedData['f_id'] ?? $extractedData['file_id'] ?? 'N/A'); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cryptographic Verification Section -->
                    <?php if ($hasCrypto): ?>
                    <div
                        class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center gap-2">
                            <span
                                class="material-symbols-outlined text-purple-500 text-[18px]">enhanced_encryption</span>
                            <h2 class="text-slate-900 dark:text-white text-sm font-bold uppercase tracking-wider">
                                Cryptographic
                                Verification</h2>
                        </div>

                        <?php if ($cryptoVerification && $cryptoVerification['valid'] === true): ?>
                        <!-- Valid -->
                        <div class="p-6 space-y-4">
                            <div class="flex items-start gap-4">
                                <div class="p-3 bg-emerald-500/10 rounded-lg flex-shrink-0">
                                    <span
                                        class="material-symbols-outlined text-emerald-500 text-2xl">verified_user</span>
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-slate-900 dark:text-white font-bold mb-1">Cryptographically
                                        Authenticated
                                    </h4>
                                    <p class="text-slate-500 dark:text-slate-400 text-sm">This watermark has been
                                        cryptographically verified and is authentic.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                <div
                                    class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg p-3 text-center">
                                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold mb-1">
                                        Signature
                                    </p>
                                    <p class="text-emerald-600 dark:text-emerald-400 font-bold text-sm">✓ VALID</p>
                                </div>
                                <div
                                    class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg p-3 text-center">
                                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold mb-1">
                                        Encryption</p>
                                    <p class="text-emerald-600 dark:text-emerald-400 font-bold text-sm">✓ VALID</p>
                                </div>
                                <div
                                    class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg p-3 text-center">
                                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold mb-1">
                                        Integrity
                                    </p>
                                    <?php if ($extractedData['content_tampered'] ?? false): ?>
                                    <p class="text-red-500 font-bold text-sm">🚨 ALTERED</p>
                                    <?php
            else: ?>
                                    <p class="text-emerald-600 dark:text-emerald-400 font-bold text-sm">✓ INTACT</p>
                                    <?php
            endif; ?>
                                </div>
                                <div
                                    class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg p-3 text-center">
                                    <p class="text-xs text-slate-500 dark:text-slate-400 uppercase font-semibold mb-1">
                                        Tamper
                                        Detection</p>
                                    <?php if ($extractedData['content_tampered'] ?? false): ?>
                                    <p class="text-red-500 font-bold text-sm">🚨 DETECTED</p>
                                    <?php
            else: ?>
                                    <p class="text-emerald-600 dark:text-emerald-400 font-bold text-sm">✓ SECURE</p>
                                    <?php
            endif; ?>
                                </div>
                            </div>

                            <!-- Crypto Details -->
                            <details class="group">
                                <summary
                                    class="cursor-pointer flex items-center gap-2 text-sm font-semibold text-primary hover:text-primary/80 transition-colors list-none">
                                    <span
                                        class="material-symbols-outlined text-sm group-open:rotate-90 transition-transform">chevron_right</span>
                                    View Cryptographic Details
                                </summary>
                                <div
                                    class="mt-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-lg p-4 font-mono text-xs text-slate-600 dark:text-slate-300 space-y-1">
                                    <p class="text-purple-500 font-bold mb-2">/* CRYPTOGRAPHIC METADATA */</p>
                                    <p>Verified At:
                                        <?php echo date('Y-m-d H:i:s', $cryptoVerification['verified_at']); ?>
                                    </p>
                                    <p>Signature:
                                        <?php echo substr($extractedData['crypto']['signature'], 0, 32); ?>...
                                    </p>
                                    <p>Key ID:
                                        <?php echo substr($extractedData['crypto']['key_id'], 0, 16); ?>...
                                    </p>
                                    <p>Nonce:
                                        <?php echo $extractedData['crypto']['nonce']; ?>
                                    </p>
                                    <p>Timestamp:
                                        <?php echo date('Y-m-d H:i:s', $extractedData['crypto']['timestamp']); ?>
                                    </p>
                                    <p class="mt-2 text-emerald-600 dark:text-emerald-400 font-bold">Status: All
                                        cryptographic
                                        checks passed ✓</p>
                                </div>
                            </details>

                            <!-- Security Features -->
                            <div
                                class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg p-4">
                                <p
                                    class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3">
                                    Security Features</p>
                                <div class="grid grid-cols-2 gap-2 text-xs text-slate-600 dark:text-slate-400">
                                    <?php foreach (['AES-256 Encryption', 'HMAC-SHA256 Signature', 'User-Specific Keys', 'Tamper Detection'] as $feature): ?>
                                    <div class="flex items-center gap-2">
                                        <span class="size-1.5 rounded-full bg-primary flex-shrink-0"></span>
                                        <span>
                                            <?php echo $feature; ?>
                                        </span>
                                    </div>
                                    <?php
            endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <?php
        else: ?>
                        <!-- Invalid -->
                        <div class="p-6">
                            <div class="flex items-start gap-4">
                                <div class="p-3 bg-red-500/10 rounded-lg flex-shrink-0">
                                    <span class="material-symbols-outlined text-red-500 text-2xl">gpp_bad</span>
                                </div>
                                <div>
                                    <h4 class="text-slate-900 dark:text-white font-bold mb-1">Cryptographic Verification
                                        Failed
                                    </h4>
                                    <p class="text-slate-500 dark:text-slate-400 text-sm mb-3">This watermark failed
                                        cryptographic verification. It may have been tampered with or forged.</p>
                                    <div class="bg-red-500/10 border border-red-500/20 rounded-lg p-3">
                                        <p class="text-red-500 text-sm font-semibold">⚠ WARNING</p>
                                        <p class="text-slate-500 dark:text-slate-400 text-xs mt-1">This file cannot be
                                            trusted
                                            as authentic evidence. The cryptographic signature is invalid.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
        endif; ?>
                    </div>
                    <?php
    endif; ?>

                    <!-- Professional Footer (Visible in PDF) -->
                    <div
                        class="hidden print-only-flex pt-10 mt-10 border-t border-slate-100 items-center justify-between opacity-60 px-6 pb-6">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px]">verified</span>
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-900">Verified by
                                StegaVault Forensics v2.1</span>
                        </div>
                        <span class="text-[9px] font-medium text-slate-500">Document Page 1 / (c) PGMN Inc.
                            <?php echo date('Y'); ?>
                        </span>
                    </div>
                </div>

                <?php
    $reportRef = 'SV-FOR-' . strtoupper(substr(md5(($fileName ?? 'REPORT') . time()), 0, 8));
    $isTampered = (bool)($extractedData['content_tampered'] ?? false);
    $ipRaw = $extractedData['ip'] ?? 'unknown';
    $ipResolved = ($ipRaw === '::1' || $ipRaw === '127.0.0.1') ? 'localhost' : $ipRaw;
    $eventTs = format_ts($extractedData['ts'] ?? $extractedData['timestamp'] ?? null);
    $cryptoPayload = isset($extractedData['crypto']) && is_array($extractedData['crypto']) ? $extractedData['crypto'] : null;
    $cryptoPublic = $cryptoPayload['public'] ?? [];
    $verificationStatus = 'N/A';
    if ($hasCrypto) {
        if ($cryptoVerification && ($cryptoVerification['valid'] ?? false) === true) {
            $verificationStatus = 'VALID';
        }
        elseif ($cryptoVerification) {
            $verificationStatus = 'INVALID';
        }
        else {
            $verificationStatus = 'UNVERIFIED';
        }
    }
?>

                <div id="pdfExportReport" class="pdf-export-source">
                    <div class="pdf-report-wrap">
                        <h1 class="pdf-title">StegaVault Forensic Audit Report</h1>
                        <table class="pdf-meta-table">
                            <tr>
                                <th>Report Reference</th>
                                <td>
                                    <?php echo safe_html($reportRef); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Generated At</th>
                                <td>
                                    <?php echo safe_html(date('Y-m-d H:i:s')); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Evidence File</th>
                                <td>
                                    <?php echo safe_html($fileName); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Evidence File Size</th>
                                <td>
                                    <?php echo safe_html(format_file_size($fileSize)); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Analysis Time</th>
                                <td>
                                    <?php echo safe_html($extractionTime !== null ? $extractionTime . ' ms' : 'N/A'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Integrity Result</th>
                                <td>
                                    <?php echo $isTampered ? 'TAMPERED' : 'VALID'; ?>
                                </td>
                            </tr>
                        </table>

                        <h2 class="pdf-section">Decoded Forensic Payload</h2>
                        <table class="pdf-table">
                            <tr>
                                <th>Leak Source User Name</th>
                                <td>
                                    <?php echo safe_html($extractedData['u_name'] ?? $extractedData['user_name'] ?? 'Unknown'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Leak Source User Role</th>
                                <td>
                                    <?php echo safe_html($extractedData['u_role'] ?? 'N/A'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Leak Source User ID</th>
                                <td>
                                    <?php echo safe_html($extractedData['u_id'] ?? $extractedData['user_id'] ?? 'N/A'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Original Uploader Name</th>
                                <td>
                                    <?php echo safe_html($extractedData['f_owner_name'] ?? 'Unknown'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Original Uploader Role</th>
                                <td>
                                    <?php echo safe_html($extractedData['f_owner_role'] ?? 'N/A'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Original Uploader ID</th>
                                <td>
                                    <?php echo safe_html($extractedData['f_owner_id'] ?? 'N/A'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Network IP</th>
                                <td>
                                    <?php echo safe_html($ipResolved); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Event Timestamp</th>
                                <td>
                                    <?php echo safe_html($eventTs); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>File Reference ID</th>
                                <td>
                                    <?php echo safe_html($extractedData['f_id'] ?? $extractedData['file_id'] ?? 'N/A'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Embedded Content Hash</th>
                                <td class="pdf-break">
                                    <?php echo safe_html($extractedData['content_hash'] ?? 'N/A'); ?>
                                </td>
                            </tr>
                        </table>

                        <h2 class="pdf-section">Cryptographic Metadata</h2>
                        <?php if ($hasCrypto && $cryptoPayload): ?>
                        <table class="pdf-table">
                            <tr>
                                <th>Verification Status</th>
                                <td>
                                    <?php echo safe_html($verificationStatus); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Verified At</th>
                                <td>
                                    <?php echo safe_html(isset($cryptoVerification['verified_at']) ? format_ts($cryptoVerification['verified_at']) : 'N/A'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Signature</th>
                                <td class="pdf-break">
                                    <?php echo safe_html($cryptoPayload['signature'] ?? 'N/A'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Key ID</th>
                                <td class="pdf-break">
                                    <?php echo safe_html($cryptoPayload['key_id'] ?? 'N/A'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Nonce</th>
                                <td class="pdf-break">
                                    <?php echo safe_html($cryptoPayload['nonce'] ?? 'N/A'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Crypto Timestamp</th>
                                <td>
                                    <?php echo safe_html(format_ts($cryptoPayload['timestamp'] ?? null)); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Public User ID</th>
                                <td>
                                    <?php echo safe_html($cryptoPublic['user_id'] ?? 'N/A'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Public Email</th>
                                <td>
                                    <?php echo safe_html($cryptoPublic['email'] ?? 'N/A'); ?>
                                </td>
                            </tr>
                        </table>
                        <h3 class="pdf-subsection">Full Crypto Payload (JSON)</h3>
                        <pre
                            class="pdf-json"><?php echo safe_html(json_encode($cryptoPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}'); ?></pre>
                        <?php
    else: ?>
                        <table class="pdf-table">
                            <tr>
                                <th>Metadata</th>
                                <td>No cryptographic metadata found in this watermark.</td>
                            </tr>
                        </table>
                        <?php
    endif; ?>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-4 pt-4 print:hidden">
                    <form method="POST" enctype="multipart/form-data" class="flex-1">
                        <input type="file" id="newFileInput" name="suspect_file"
                            accept=".png,.mp4,.pdf,.doc,.docx,.xls,.xlsx" style="display: none;"
                            onchange="this.form.submit()" />
                        <button type="button" onclick="document.getElementById('newFileInput').click()"
                            class="w-full flex items-center justify-center gap-2 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-900 dark:text-white font-bold py-3 px-6 rounded-lg transition-all border border-slate-200 dark:border-slate-700 shadow-sm">
                            <span class="material-symbols-outlined">refresh</span>
                            Analyze New Evidence
                        </button>
                    </form>
                    <button id="exportPdfBtn" onclick="generateForensicPDF()"
                        class="flex-1 flex items-center justify-center gap-2 bg-primary hover:bg-primary/90 text-white font-bold py-3 px-6 rounded-lg transition-all shadow-sm">
                        <span class="material-symbols-outlined">picture_as_pdf</span>
                        Export Forensic Report (PDF)
                    </button>
                </div>
            </div>

            <style>
                @media print {
                    .print\:hidden {
                        display: none !important;
                    }

                    .print-only-block {
                        display: block !important;
                    }

                    .print-only-flex {
                        display: flex !important;
                    }
                }

                .print-only-block,
                .print-only-flex {
                    display: none;
                }

                .pdf-export-source {
                    display: none;
                }

                .pdf-export-source.pdf-export-active {
                    display: block;
                }

                .pdf-export-runtime {
                    position: fixed;
                    inset: 0 auto auto 0;
                    width: 794px;
                    background: #ffffff;
                    color: #0f172a;
                    z-index: -1;
                    pointer-events: none;
                }

                .pdf-report-wrap {
                    padding: 28px;
                    font-family: Inter, sans-serif;
                    font-size: 12px;
                    line-height: 1.45;
                }

                .pdf-title {
                    font-size: 22px;
                    font-weight: 700;
                    margin: 0 0 14px;
                    color: #0f172a;
                }

                .pdf-section {
                    font-size: 14px;
                    font-weight: 700;
                    margin: 18px 0 8px;
                    color: #1e293b;
                }

                .pdf-subsection {
                    font-size: 12px;
                    font-weight: 700;
                    margin: 14px 0 6px;
                    color: #334155;
                }

                .pdf-meta-table,
                .pdf-table {
                    width: 100%;
                    border-collapse: collapse;
                    border: 1px solid #dbe2ea;
                }

                .pdf-meta-table th,
                .pdf-meta-table td,
                .pdf-table th,
                .pdf-table td {
                    border: 1px solid #dbe2ea;
                    padding: 6px 8px;
                    text-align: left;
                    vertical-align: top;
                }

                .pdf-meta-table th,
                .pdf-table th {
                    width: 220px;
                    font-weight: 700;
                    background: #f8fafc;
                    color: #334155;
                }

                .pdf-break {
                    word-break: break-word;
                }

                .pdf-json {
                    margin: 0;
                    padding: 10px;
                    border: 1px solid #dbe2ea;
                    background: #f8fafc;
                    font-family: ui-monospace, monospace;
                    font-size: 10px;
                    white-space: pre-wrap;
                    word-break: break-word;
                }
            </style>

            <script>
                async function generateForensicPDF() {
                    const btn = document.getElementById('exportPdfBtn');
                    const originalHTML = btn.innerHTML;
                    btn.innerHTML = 'Generating Professional Report...';
                    btn.disabled = true;

                    const {
                        jsPDF
                    } = window.jspdf;
                    const doc = new jsPDF('p', 'mm', 'a4');

                    const fileName = "SV-FORENSIC-<?php echo strtoupper(substr(md5($fileName ?? 'REPORT'), 0, 8)); ?>.pdf";

                    let y = 20; // vertical spacing control
                    const leftMargin = 20;
                    const rightMargin = 190;

                    try {

                        /* ================= HEADER ================= */
                        doc.setFontSize(20);
                        doc.setTextColor(0, 0, 0);
                        doc.text("StegaVault Digital Forensics", 105, y, {
                            align: "center"
                        });

                        y += 8;
                        doc.setFontSize(11);
                        doc.setTextColor(100);
                        doc.text("PGMN Inc. | Confidential Forensic Audit Report", 105, y, {
                            align: "center"
                        });

                        y += 10;
                        doc.setDrawColor(200);
                        doc.line(leftMargin, y, rightMargin, y);

                        y += 12;

                        /* ================= BASIC INFO ================= */
                        doc.setFontSize(12);
                        doc.setTextColor(0);

                        doc.text("Reference ID:", leftMargin, y);
                        doc.text("SV-FOR-<?php echo strtoupper(substr(md5($fileName . time()), 0, 8)); ?>", 70, y);

                        y += 8;
                        doc.text("Generated At:", leftMargin, y);
                        doc.text("<?php echo date('F j, Y g:i A'); ?>", 70, y);

                        y += 8;
                        doc.text("File Name:", leftMargin, y);
                        doc.text("<?php echo htmlspecialchars($fileName); ?>", 70, y);

                        y += 8;
                        doc.text("File Size:", leftMargin, y);
                        doc.text("<?php echo number_format($fileSize / 1024, 2); ?> KB", 70, y);

                        y += 15;

                        /* ================= FINDINGS ================= */
                        doc.setFontSize(14);
                        doc.setTextColor(0);
                        doc.text("Integrity Assessment Findings", leftMargin, y);

                        y += 8;

                            <?php if ($extractedData['content_tampered'] ?? false): ?>
                            doc.setTextColor(200, 0, 0);
                        doc.text("WARNING: CONTENT TAMPERING DETECTED", leftMargin, y);
                        y += 8;
                        doc.setTextColor(0);
                        doc.setFontSize(11);
                        doc.text(doc.splitTextToSize(
                            "The forensic analysis revealed that the visual content of the file has been altered since it was originally secured. The cryptographic signature is intact, but the image data does not match the original hash.",
                            170
                        ), leftMargin, y);
                        y += 20;
                            <?php
    else: ?>
                            doc.setTextColor(0, 150, 0);
                        doc.text("INTEGRITY VALIDATED", leftMargin, y);
                        y += 8;
                        doc.setTextColor(0);
                        doc.setFontSize(11);
                        doc.text(doc.splitTextToSize(
                            "The forensic watermark is perfectly intact. No modifications to the visual content or metadata have occurred since the file was originally downloaded. The digital signature was successfully verified.",
                            170
                        ), leftMargin, y);
                        y += 20;
                            <?php
    endif; ?>

                            /* ================= SOURCE INFO ================= */
                            doc.setFontSize(14);
                        doc.text("Source Identification", leftMargin, y);
                        y += 10;

                        doc.setFontSize(11);

                        doc.text("User:", leftMargin, y);
                        doc.text("<?php echo safe_html($extractedData['u_name'] ?? $extractedData['user_name'] ?? 'Unknown'); ?>", 60, y);
                        y += 8;

                        doc.text("User ID:", leftMargin, y);
                        doc.text("<?php echo safe_html($extractedData['u_id'] ?? $extractedData['user_id'] ?? 'N/A'); ?>", 60, y);
                        y += 8;

                        doc.text("Role:", leftMargin, y);
                        doc.text("<?php echo safe_html($extractedData['u_role'] ?? 'N/A'); ?>", 60, y);
                        y += 8;

                        doc.text("IP Address:", leftMargin, y);
                        doc.text("<?php echo htmlspecialchars($extractedData['ip'] ?? 'Unknown'); ?>", 60, y);
                        y += 15;

                            /* ================= CRYPTO METADATA ================= */
                            <?php if ($hasCrypto && $cryptoVerification && $cryptoVerification['valid'] === true): ?>
                            doc.setFontSize(14);
                        doc.text("Cryptographic Metadata", leftMargin, y);
                        y += 10;

                        doc.setFontSize(11);
                        doc.text("Verified At:", leftMargin, y);
                        doc.text("<?php echo date('Y-m-d H:i:s', $cryptoVerification['verified_at']); ?>", 60, y);
                        y += 8;

                        doc.text("Signature:", leftMargin, y);
                        doc.setFont("courier", "normal");
                        doc.text("<?php echo substr($extractedData['crypto']['signature'] ?? '', 0, 32); ?>...", 60, y);
                        doc.setFont("helvetica", "normal");
                        y += 8;

                        doc.text("Key ID:", leftMargin, y);
                        doc.setFont("courier", "normal");
                        doc.text("<?php echo substr($extractedData['crypto']['key_id'] ?? '', 0, 16); ?>...", 60, y);
                        doc.setFont("helvetica", "normal");
                        y += 8;

                        doc.text("Nonce:", leftMargin, y);
                        doc.setFont("courier", "normal");
                        doc.text("<?php echo htmlspecialchars($extractedData['crypto']['nonce'] ?? 'N/A'); ?>", 60, y);
                        doc.setFont("helvetica", "normal");
                        y += 8;

                        doc.text("Timestamp:", leftMargin, y);
                        doc.text("<?php echo isset($extractedData['crypto']['timestamp']) ? date('Y-m-d H:i:s', $extractedData['crypto']['timestamp']) : 'N/A'; ?>", 60, y);
                        y += 15;
                            <?php
    endif; ?>

                            /* ================= FOOTER ================= */
                            doc.setDrawColor(200);
                        doc.line(leftMargin, 270, rightMargin, 270);

                        doc.setFontSize(9);
                        doc.setTextColor(120);
                        doc.text("This document is a digitally generated forensic report issued by StegaVault Security Suite.", 105, 277, {
                            align: "center"
                        });
                        doc.text("© PGMN Inc. <?php echo date('Y'); ?> | StegaVault Forensics v2.1", 105, 283, {
                            align: "center"
                        });

                        doc.save(fileName);

                    } catch (err) {
                        console.error(err);
                        alert("Failed to generate report.");
                    }

                    btn.innerHTML = originalHTML;
                    btn.disabled = false;
                }
            </script>
            <?php
endif; ?>
        </div>
    </main>

    <!-- Security Shield -->
    <script>
        window.currentUser = {
            name: "<?php echo htmlspecialchars($user['name']); ?>",
            email: "<?php echo htmlspecialchars($user['email']); ?>"
        };
    </script>
    <script src="../js/security-shield.js"></script>
    <?php include '../includes/settings_modal.php'; ?>
</body>

</html>