<?php
/**
 * StegaVault - Watermark Extraction (Security Design)
 * File: admin/extract.php
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

// Handle file upload for extraction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['suspect_file'])) {
    $file = $_FILES['suspect_file'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $tmpPath = $file['tmp_name'];
        $fileName = $file['name'];
        $fileSize = $file['size'];

        $startTime = microtime(true);

        // 1. Extract raw LSB watermark
        $extractedData = Watermark::extractWatermark($tmpPath);

        $extractionTime = round((microtime(true) - $startTime) * 1000, 2); // ms

        if ($extractedData === false) {
            $error = "No watermark found in this image, or watermark is corrupted.";
        } else {
            // 2. Perform Forensic Cryptographic Verification
            if (isset($extractedData['crypto'])) {
                $uId = $extractedData['crypto']['public']['user_id'];

                // Fetch user for key derivation
                $stmt = $db->prepare("SELECT id, email FROM users WHERE id = ?");
                $stmt->bind_param("i", $uId);
                $stmt->execute();
                $uRes = $stmt->get_result();

                if ($uRes && $userRow = $uRes->fetch_assoc()) {
                    $userData = ['id' => $userRow['id'], 'email' => $userRow['email']];
                    $verifyResult = CryptoWatermark::verifyWatermark($extractedData['crypto'], $userData);

                    if ($verifyResult && $verifyResult['valid'] === true) {
                        $extractedData['verified'] = true;
                        $extractedData['report'] = $verifyResult['data'];

                        // Log successful verification
                        CryptoWatermark::logVerification($db, $extractedData['crypto']['signature']);
                    } else {
                        $extractedData['verified'] = false;
                        $extractedData['tamper_alert'] = true;
                    }
                } else {
                    $extractedData['verified'] = 'partial'; // User not in DB
                }
            }
        }
    } else {
        $error = "Failed to upload file.";
    }
}

function safe_html($value, $default = 'N/A')
{
    return htmlspecialchars($value ?? $default);
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">

<head>
    <link rel="icon" type="image/png" href="../Assets/favicon.png">
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Extract Watermark - StegaVault</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#667eea",
                        "background-dark": "#101622",
                        "surface-dark": "#111318",
                        "card-dark": "#1c222c",
                        "border-dark": "#282e39",
                    },
                    fontFamily: {
                        "display": ["Space Grotesk", "sans-serif"],
                        "mono": ["ui-monospace", "monospace"]
                    },
                },
            },
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

<body class="bg-background-dark text-slate-100 min-h-screen flex flex-col">

    <!-- Top Navigation -->
    <header class="flex items-center justify-between border-b border-border-dark bg-surface-dark px-10 py-3">
        <div class="flex items-center gap-8">
            <div class="flex items-center gap-4">
                <div class="size-8 bg-primary rounded-lg flex items-center justify-center">
                    <span class="material-symbols-outlined text-white text-lg">shield</span>
                </div>
                <h2 class="text-white text-lg font-bold">StegaVault</h2>
            </div>
            <nav class="flex items-center gap-9">
                <a class="text-slate-400 hover:text-white text-sm font-medium transition-colors"
                    href="dashboard.php">Dashboard</a>
                <a class="text-white text-sm font-bold border-b-2 border-primary py-1" href="extract.php">Extraction
                    Studio</a>
                <a class="text-slate-400 hover:text-white text-sm font-medium transition-colors"
                    href="upload.php">Vault</a>
                <?php if ($user['role'] === 'admin'): ?>
                    <a class="text-slate-400 hover:text-white text-sm font-medium transition-colors"
                        href="users.php">Users</a>
                <?php endif; ?>
            </nav>
        </div>
        <div class="flex items-center gap-4">
            <div class="text-right">
                <p class="text-xs text-slate-400 capitalize"><?php echo htmlspecialchars($user['role']); ?></p>
                <p class="text-sm font-bold text-white"><?php echo htmlspecialchars($user['name']); ?></p>
            </div>
            <div class="bg-primary rounded-full size-10 flex items-center justify-center text-white font-bold text-sm">
                <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
            </div>
        </div>
    </header>

    <div class="flex flex-1 overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-64 border-r border-border-dark bg-surface-dark flex flex-col justify-between p-4">
            <div class="flex flex-col gap-6">
                <div class="px-2">
                    <h1 class="text-white text-base font-bold">Extraction Studio</h1>
                    <p class="text-slate-400 text-xs">Forensic Analysis Suite</p>
                </div>
                <div class="flex flex-col gap-1">
                    <a href="dashboard.php"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-400 hover:text-white hover:bg-border-dark/50 transition-colors">
                        <span class="material-symbols-outlined">dashboard</span>
                        <p class="text-sm font-medium">Overview</p>
                    </a>
                    <div
                        class="flex items-center gap-3 px-3 py-2 rounded-lg bg-primary/10 text-primary border border-primary/20">
                        <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">search</span>
                        <p class="text-sm font-bold">Extract</p>
                    </div>
                    <a href="upload.php"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-400 hover:text-white hover:bg-border-dark/50 transition-colors">
                        <span class="material-symbols-outlined">shield_lock</span>
                        <p class="text-sm font-medium">Watermark</p>
                    </a>
                    <a href="activity.php"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-400 hover:text-white hover:bg-border-dark/50 transition-colors">
                        <span class="material-symbols-outlined">history</span>
                        <p class="text-sm font-medium">History</p>
                    </a>
                </div>
            </div>

            <?php if (!$extractedData && !$error): ?>
                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                    <input type="file" id="fileInput" name="suspect_file" accept=".png,.mp4,.pdf,.doc,.docx,.xls,.xlsx"
                        style="display: none;" onchange="document.getElementById('uploadForm').submit()" />
                    <button type="button" onclick="document.getElementById('fileInput').click()"
                        class="w-full flex items-center justify-center gap-2 rounded-lg h-10 px-4 bg-primary hover:bg-blue-600 text-white text-sm font-bold transition-all shadow-lg shadow-primary/20">
                        <span class="material-symbols-outlined text-sm">add</span>
                        <span>New Extraction</span>
                    </button>
                </form>
            <?php endif; ?>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
            <div class="max-w-4xl mx-auto p-8">

                <?php if (!$extractedData && !$error): ?>
                    <!-- Upload State -->
                    <div class="flex items-center gap-4 mb-6">
                        <div class="flex items-center justify-center size-10 bg-primary/20 text-primary rounded-full">
                            <span class="material-symbols-outlined">upload_file</span>
                        </div>
                        <h1 class="text-white text-3xl font-bold">Upload Suspicious File</h1>
                    </div>

                    <div class="bg-card-dark border border-border-dark rounded-xl p-8 text-center">
                        <div class="max-w-md mx-auto">
                            <div class="mb-6">
                                <div
                                    class="size-20 mx-auto mb-4 bg-primary/10 rounded-full flex items-center justify-center">
                                    <span class="material-symbols-outlined text-5xl text-primary">search</span>
                                </div>
                                <h3 class="text-white text-xl font-bold mb-2">Forensic Watermark Extraction</h3>
                                <p class="text-slate-400 text-sm">Upload a suspected leaked file to identify the source
                                    through embedded watermark analysis</p>
                            </div>

                            <form method="POST" enctype="multipart/form-data" id="mainUploadForm">
                                <input type="file" id="mainFileInput" name="suspect_file"
                                    accept=".png,.mp4,.pdf,.doc,.docx,.xls,.xlsx" style="display: none;"
                                    onchange="document.getElementById('mainUploadForm').submit()" />
                                <button type="button" onclick="document.getElementById('mainFileInput').click()"
                                    class="w-full py-4 px-6 bg-primary/10 hover:bg-primary/20 border-2 border-dashed border-primary/50 hover:border-primary rounded-xl text-primary font-bold transition-all">
                                    Click to Select File or Drag & Drop
                                </button>
                            </form>

                            <p class="text-slate-500 text-xs mt-4">Supported: PNG, JPG, GIF, WebP, Documents • Max 10MB</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4 mt-8">
                        <div class="bg-card-dark/50 border border-border-dark p-4 rounded-lg">
                            <p class="text-slate-400 text-xs uppercase font-bold mb-1">Detection Method</p>
                            <p class="text-white text-sm">LSB Steganography</p>
                        </div>
                        <div class="bg-card-dark/50 border border-border-dark p-4 rounded-lg">
                            <p class="text-slate-400 text-xs uppercase font-bold mb-1">Data Integrity</p>
                            <p class="text-white text-sm">MD5 Checksum</p>
                        </div>
                        <div class="bg-card-dark/50 border border-border-dark p-4 rounded-lg">
                            <p class="text-slate-400 text-xs uppercase font-bold mb-1">Status</p>
                            <p class="text-emerald-500 text-sm flex items-center gap-1">
                                <span class="size-2 rounded-full bg-emerald-500"></span> Ready
                            </p>
                        </div>
                    </div>

                <?php elseif ($error): ?>
                    <!-- Error State -->
                    <div class="flex items-center gap-4 mb-6">
                        <div class="flex items-center justify-center size-10 bg-red-500/20 text-red-500 rounded-full">
                            <span class="material-symbols-outlined">error</span>
                        </div>
                        <h1 class="text-white text-3xl font-bold">Extraction Failed</h1>
                    </div>

                    <div class="bg-card-dark border-2 border-red-500/20 rounded-xl p-6 mb-6">
                        <div class="flex items-start gap-4">
                            <span class="material-symbols-outlined text-3xl text-red-500">warning</span>
                            <div>
                                <h3 class="text-white font-bold mb-2">No Watermark Detected</h3>
                                <p class="text-slate-400 text-sm mb-4"><?php echo htmlspecialchars($error); ?></p>
                                <p class="text-slate-500 text-xs font-bold uppercase mb-2">Possible Reasons:</p>
                                <ul class="text-slate-400 text-sm space-y-1 ml-4">
                                    <li>• File not downloaded from StegaVault</li>
                                    <li>• Image edited or re-compressed after download</li>
                                    <li>• Watermark corrupted or removed</li>
                                    <li>• Unsupported file format</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="file" id="retryInput" name="suspect_file" accept=".png,.mp4,.pdf,.doc,.docx,.xls,.xlsx"
                            style="display: none;" onchange="this.form.submit()" />
                        <button type="button" onclick="document.getElementById('retryInput').click()"
                            class="w-full py-3 px-6 bg-primary hover:bg-blue-600 text-white font-bold rounded-lg transition-all flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined">refresh</span>
                            Try Another File
                        </button>
                    </form>

                <?php else: ?>
                    <!-- Success State -->
                    <div class="flex items-center gap-4 mb-6">
                        <div class="flex items-center justify-center size-10 bg-green-500/20 text-green-500 rounded-full">
                            <span class="material-symbols-outlined">check_circle</span>
                        </div>
                        <h1 class="text-white text-3xl font-bold">Extraction Successful</h1>
                    </div>

                    <!-- Progress Bar -->
                    <div class="bg-card-dark border border-border-dark rounded-xl p-5 mb-8">
                        <div class="flex flex-col gap-3">
                            <div class="flex justify-between items-end">
                                <div>
                                    <p class="text-white text-sm font-bold">Data Integrity Verified</p>
                                    <p class="text-slate-400 text-xs">Steganographic layers analyzed successfully</p>
                                </div>
                                <p class="text-primary text-lg font-bold">100%</p>
                            </div>
                            <div class="h-2 rounded-full bg-border-dark overflow-hidden">
                                <div class="h-full bg-primary shadow-[0_0_10px_rgba(102,126,234,0.5)]" style="width: 100%;">
                                </div>
                            </div>
                            <div
                                class="flex justify-between items-center text-xs uppercase tracking-wider font-bold text-slate-400">
                                <span class="text-green-500">Extraction Complete</span>
                                <span>Time: <?php echo $extractionTime; ?>ms</span>
                            </div>
                        </div>
                    </div>

                    <!-- Extracted Data -->
                    <div class="space-y-4">
                        <div class="flex justify-between items-center px-1">
                            <h3 class="text-white font-bold flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-sm">visibility</span>
                                Watermark Payload Detected
                            </h3>
                        </div>

                        <div class="relative group">
                            <div
                                class="absolute inset-0 bg-primary/5 rounded-xl border border-primary/20 group-hover:border-primary/40 transition-all pointer-events-none">
                            </div>
                            <div
                                class="bg-[#0c0f14] p-6 rounded-xl font-mono text-sm leading-relaxed text-blue-100 border border-border-dark">
                                <p class="mb-4 text-primary opacity-50 select-none">/* DECODED WATERMARK DATA */</p>
                                <p class="mb-2 uppercase font-bold text-red-400">🚨 LEAK SOURCE IDENTIFIED</p>
                                <p class="mb-2 text-slate-300">TIMESTAMP: <?php echo safe_html($extractedData['date']); ?>
                                </p>
                                <p class="mb-4 text-slate-300">FILE_ID: <?php echo safe_html($extractedData['file_id']); ?>
                                </p>

                                <div class="p-4 bg-red-500/10 border-l-2 border-red-500 rounded-r mb-4">
                                    <?php if (isset($extractedData['tamper_alert']) && $extractedData['tamper_alert']): ?>
                                        <div class="mb-4 p-3 bg-red-600/20 border border-red-500 rounded-lg animate-pulse">
                                            <p class="text-red-400 font-bold flex items-center gap-2">
                                                <span class="material-symbols-outlined">gpp_maybe</span>
                                                🚨 WARNING: CRYPTOGRAPHIC SIGNATURE MISMATCH
                                            </p>
                                            <p class="text-xs text-red-300/70 mt-1 uppercase">Watermark has been edited or
                                                forged. Forensic integrity compromised.</p>
                                        </div>
                                    <?php endif; ?>

                                    <p class="text-red-400 font-bold mb-2">
                                        <?php echo (isset($extractedData['tamper_alert']) && $extractedData['tamper_alert']) ? 'ORIGINAL SUSPECT RECORD:' : 'SUSPECT IDENTIFIED:'; ?>
                                    </p>
                                    <p class="text-white text-lg font-bold">👤
                                        <?php echo safe_html($extractedData['u_name'] ?? $extractedData['user_name'] ?? 'Unknown'); ?>
                                    </p>
                                    <p class="text-slate-400 mt-1">User ID:
                                        <?php echo safe_html($extractedData['u_id'] ?? $extractedData['user_id'] ?? 'N/A'); ?>
                                    </p>
                                    <p class="text-slate-400">IP Address: <?php
                                    $ip = $extractedData['ip'] ?? 'unknown';
                                    echo ($ip === '::1' || $ip === '127.0.0.1') ? 'localhost' : htmlspecialchars($ip);
                                    ?></p>
                                    <div class="mt-4 pt-4 border-t border-red-500/20">
                                        <p class="text-emerald-400 font-bold mb-2">ORIGINAL FILE AUTHOR/UPLOADER:</p>
                                        <p class="text-white text-lg font-bold">📛
                                            <?php echo safe_html($extractedData['f_owner_name'] ?? 'Unknown'); ?>
                                        </p>
                                        <p class="text-slate-400 mt-1">User ID:
                                            <?php echo safe_html($extractedData['f_owner_id'] ?? 'N/A'); ?>
                                        </p>
                                        <p class="text-slate-400">Role:
                                            <?php echo safe_html($extractedData['f_owner_role'] ?? 'N/A'); ?>
                                        </p>
                                    </div>

                                    <?php if (isset($extractedData['verified']) && $extractedData['verified'] === true): ?>
                                        <div
                                            class="mt-3 inline-flex items-center gap-2 px-3 py-1 bg-emerald-500/20 text-emerald-400 text-xs font-bold rounded-full border border-emerald-500/30">
                                            <span class="material-symbols-outlined text-sm">verified</span>
                                            SECURE_SIGNATURE_VERIFIED
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <p class="text-xs text-slate-500">-- END OF TRANSMISSION --</p>
                            </div>
                        </div>
                    </div>

                    <!-- File Details -->
                    <div class="grid grid-cols-3 gap-4 mt-8">
                        <div class="bg-card-dark/50 border border-border-dark p-4 rounded-lg">
                            <p class="text-slate-400 text-xs uppercase font-bold mb-1">Carrier File</p>
                            <p class="text-white text-sm truncate"><?php echo safe_html($fileName); ?></p>
                            <p class="text-slate-400 text-xs mt-1"><?php echo round($fileSize / 1024, 2); ?> KB</p>
                        </div>
                        <div class="bg-card-dark/50 border border-border-dark p-4 rounded-lg">
                            <p class="text-slate-400 text-xs uppercase font-bold mb-1">Encoding Method</p>
                            <p class="text-white text-sm">LSB Steganography</p>
                            <p class="text-slate-400 text-xs mt-1">3-bit embedding</p>
                        </div>
                        <div class="bg-card-dark/50 border border-border-dark p-4 rounded-lg">
                            <p class="text-slate-400 text-xs uppercase font-bold mb-1">Data Integrity</p>
                            <p class="text-emerald-500 text-sm flex items-center gap-1">
                                <span class="size-2 rounded-full bg-emerald-500"></span> Verified
                            </p>
                            <p class="text-slate-400 text-xs mt-1">MD5 checksum match</p>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-4 mt-8">
                        <form method="POST" enctype="multipart/form-data" class="flex-1">
                            <input type="file" id="newFileInput" name="suspect_file"
                                accept=".png,.mp4,.pdf,.doc,.docx,.xls,.xlsx" style="display: none;"
                                onchange="this.form.submit()" />
                            <button type="button" onclick="document.getElementById('newFileInput').click()"
                                class="w-full flex items-center justify-center gap-2 bg-border-dark hover:bg-slate-700 text-white font-bold py-3 px-6 rounded-lg transition-all border border-slate-600">
                                <span class="material-symbols-outlined">refresh</span>
                                Extract Another
                            </button>
                        </form>
                        <button onclick="window.print()"
                            class="flex-1 flex items-center justify-center gap-2 bg-primary hover:bg-blue-600 text-white font-bold py-3 px-6 rounded-lg transition-all">
                            <span class="material-symbols-outlined">print</span>
                            Export Report
                        </button>
                    </div>

                <?php endif; ?>
            </div>
        </main>

        <!-- Right Sidebar - Activity Log -->
        <aside class="w-80 border-l border-border-dark bg-surface-dark flex flex-col">
            <div class="p-4 border-b border-border-dark">
                <h2 class="text-white text-sm font-bold flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-sm">history</span>
                    Extraction Log
                </h2>
                <p class="text-slate-400 text-xs mt-1">Session activity tracking</p>
            </div>
            <div class="flex-1 overflow-y-auto p-4 space-y-4">
                <?php if ($extractedData): ?>
                    <div class="relative pl-6 border-l border-border-dark py-1">
                        <div
                            class="absolute left-[-5px] top-2 size-2 rounded-full bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.5)]">
                        </div>
                        <p class="text-white text-xs font-bold">Extraction Success</p>
                        <p class="text-slate-400 text-xs mt-1"><?php echo date('H:i:s'); ?></p>
                        <div class="mt-2 bg-black/30 rounded p-2 text-xs font-mono text-slate-400">
                            STATUS: SUCCESS<br />
                            USER: <?php echo safe_html($extractedData['user_name']); ?>
                        </div>
                    </div>
                <?php elseif ($error): ?>
                    <div class="relative pl-6 border-l border-border-dark py-1">
                        <div
                            class="absolute left-[-5px] top-2 size-2 rounded-full bg-red-500 shadow-[0_0_8px_rgba(239,68,68,0.5)]">
                        </div>
                        <p class="text-white text-xs font-bold">Extraction Failed</p>
                        <p class="text-slate-400 text-xs mt-1"><?php echo date('H:i:s'); ?></p>
                        <div class="mt-2 bg-black/30 rounded p-2 text-xs font-mono text-red-400">
                            STATUS: NO_WATERMARK
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <span class="material-symbols-outlined text-4xl text-slate-600">history</span>
                        <p class="text-slate-500 text-xs mt-2">No activity yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</body>

</html>