<?php
/**
 * StegaVault - Watermark Verification Interface
 * File: admin/verify_watermark.php
 */

session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/watermark.php';
require_once __DIR__ . '/../includes/CryptoWatermark.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.html');
    exit;
}

$verificationResult = null;
$extractedWatermark = null;
$error = null;

// Handle file upload for verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['watermarked_file'])) {
    $uploadedFile = $_FILES['watermarked_file'];

    if ($uploadedFile['error'] === UPLOAD_ERR_OK) {
        $tempPath = $uploadedFile['tmp_name'];

        // Extract watermark
        $extractedWatermark = Watermark::extractWatermark($tempPath);

        if ($extractedWatermark && isset($extractedWatermark['crypto'])) {
            // Get user data for verification
            $userId = $extractedWatermark['crypto']['public']['user_id'];

            $stmt = $db->prepare("SELECT id, email FROM users WHERE id = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();

                $userData = [
                    'id' => $user['id'],
                    'email' => $user['email']
                ];

                // Verify cryptographic watermark
                $verificationResult = CryptoWatermark::verifyWatermark($extractedWatermark['crypto'], $userData);

                // Log verification
                if ($verificationResult && $verificationResult['valid'] === true) {
                    CryptoWatermark::logVerification($db, $extractedWatermark['crypto']['signature']);
                }
            } else {
                $error = "User not found in database";
            }
        } else if ($extractedWatermark) {
            $error = "This watermark does not contain cryptographic data (legacy watermark)";
        } else {
            $error = "No watermark found in this file";
        }
    } else {
        $error = "File upload error: " . $uploadedFile['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="icon" type="image/png" href="../Assets/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watermark Verification - StegaVault</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .report-box {
            background: #1a1a2e;
            color: #00ff88;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            line-height: 1.6;
            white-space: pre-wrap;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .upload-zone {
            border: 2px dashed rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }

        .upload-zone:hover {
            border-color: rgba(255, 255, 255, 0.6);
            background: rgba(255, 255, 255, 0.05);
        }
    </style>
</head>

<body class="p-6">
    <div class="max-w-5xl mx-auto">
        <!-- Header -->
        <div class="glass rounded-2xl p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white mb-2">🔐 Cryptographic Watermark Verification</h1>
                    <p class="text-gray-200">Upload a watermarked file to verify its authenticity and extract forensic
                        data</p>
                </div>
                <a href="dashboard.php"
                    class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg transition">
                    ← Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Upload Form -->
        <div class="glass rounded-2xl p-8 mb-6">
            <h2 class="text-2xl font-semibold text-white mb-4">Upload Watermarked File</h2>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <div class="upload-zone rounded-xl p-8 text-center">
                    <input type="file" name="watermarked_file" id="fileInput"
                        accept="image/png,image/jpeg,image/jpg,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,.pdf,.doc,.docx,.xls,.xlsx"
                        required class="hidden">
                    <label for="fileInput" class="cursor-pointer">
                        <div class="text-white mb-2">
                            <svg class="w-16 h-16 mx-auto mb-4 opacity-70" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                        </div>
                        <p class="text-xl text-white font-medium">Click to select file</p>
                        <p class="text-gray-300 mt-2">Images and Documents format supported</p>
                    </label>
                </div>
                <div id="fileName" class="text-white text-center hidden"></div>
                <button type="submit"
                    class="w-full bg-gradient-to-r from-green-400 to-blue-500 hover:from-green-500 hover:to-blue-600 text-white font-semibold py-3 rounded-xl transition shadow-lg">
                    🔍 Verify Watermark
                </button>
            </form>
        </div>

        <!-- Error Display -->
        <?php if ($error): ?>
            <div class="glass rounded-2xl p-6 mb-6 border-l-4 border-red-500">
                <div class="flex items-start">
                    <span class="text-3xl mr-3">❌</span>
                    <div>
                        <h3 class="text-xl font-semibold text-white mb-2">Verification Failed</h3>
                        <p class="text-gray-200"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Verification Result -->
        <?php if ($verificationResult): ?>
            <div class="glass rounded-2xl p-6 mb-6">
                <?php if ($verificationResult['valid'] === true): ?>
                    <div class="border-l-4 border-green-500 pl-4 mb-6">
                        <div class="flex items-center mb-2">
                            <span class="text-4xl mr-3">✅</span>
                            <h2 class="text-2xl font-bold text-white">Watermark Verified - AUTHENTIC</h2>
                        </div>
                        <p class="text-gray-200">This watermark is cryptographically valid and has not been tampered with.</p>
                    </div>

                    <!-- Detailed Report -->
                    <div class="report-box">
                        <?php echo CryptoWatermark::generateReport($verificationResult); ?>
                    </div>

                    <!-- Technical Details -->
                    <details class="mt-6">
                        <summary class="text-white font-semibold cursor-pointer hover:text-gray-200 transition">
                            📊 Technical Details (JSON)
                        </summary>
                        <div class="report-box mt-4">
                            <?php echo json_encode($verificationResult['data'], JSON_PRETTY_PRINT); ?>
                        </div>
                    </details>

                <?php else: ?>
                    <div class="border-l-4 border-red-500 pl-4">
                        <div class="flex items-center mb-2">
                            <span class="text-4xl mr-3">❌</span>
                            <h2 class="text-2xl font-bold text-white">Watermark Invalid - TAMPERED OR FORGED</h2>
                        </div>
                        <p class="text-gray-200">This watermark failed cryptographic verification. It may have been tampered
                            with or forged.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Extracted Watermark (Raw) -->
        <?php if ($extractedWatermark): ?>
            <details class="glass rounded-2xl p-6">
                <summary class="text-white font-semibold cursor-pointer hover:text-gray-200 transition text-lg">
                    🔬 Raw Extracted Watermark Data
                </summary>
                <div class="report-box mt-4">
                    <?php echo json_encode($extractedWatermark, JSON_PRETTY_PRINT); ?>
                </div>
            </details>
        <?php endif; ?>

        <!-- Info Box -->
        <div class="glass rounded-2xl p-6 mt-6">
            <h3 class="text-xl font-semibold text-white mb-3">ℹ️ About Cryptographic Watermarks</h3>
            <div class="text-gray-200 space-y-2">
                <p><strong>Security Features:</strong></p>
                <ul class="list-disc list-inside ml-4 space-y-1">
                    <li>AES-256 encryption of sensitive data</li>
                    <li>HMAC-SHA256 digital signatures for authenticity</li>
                    <li>User-specific key derivation (PBKDF2)</li>
                    <li>Tamper detection through cryptographic checksums</li>
                    <li>Timestamp verification to prevent backdating</li>
                    <li>Complete forensic audit trail</li>
                </ul>
                <p class="mt-4"><strong>What gets verified:</strong></p>
                <ul class="list-disc list-inside ml-4 space-y-1">
                    <li>User identity (ID, name, email hash, role)</li>
                    <li>File identity (ID, original hash, type)</li>
                    <li>Download metadata (timestamp, IP, session)</li>
                    <li>Cryptographic signature integrity</li>
                    <li>Encryption validity</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // File input handler
        document.getElementById('fileInput').addEventListener('change', function (e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                const fileNameDiv = document.getElementById('fileName');
                fileNameDiv.textContent = '📁 Selected: ' + fileName;
                fileNameDiv.classList.remove('hidden');
            }
        });
    </script>
</body>

</html>