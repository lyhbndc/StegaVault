<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.html');
    exit;
}

require_once '../includes/db.php';

$fileId = (int) ($_GET['id'] ?? 0);
$userId = (int) $_SESSION['user_id'];
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';

if ($fileId <= 0) {
    header('Location: projects.php');
    exit;
}

// Fetch the file — LEFT JOIN so files without a project still load
$stmt = $db->prepare("
    SELECT f.*, u.name AS uploader_name,
           p.name AS project_name, p.id AS project_id
    FROM files f
    JOIN users u ON f.user_id = u.id
    LEFT JOIN projects p ON f.project_id = p.id
    WHERE f.id = ?
");
$stmt->bind_param('i', $fileId);
$stmt->execute();
$file = $stmt->get_result()->fetch_assoc();

if (!$file) {
    header('Location: projects.php');
    exit;
}

$projectId = (int) ($file['project_id'] ?? 0);

// Access check (skip for admins)
if (!$isAdmin) {
    if ($projectId) {
        $ac = $db->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ?");
        $ac->bind_param('ii', $projectId, $userId);
        $ac->execute();
        if ($ac->get_result()->num_rows === 0) {
            header('Location: projects.php');
            exit;
        }
    } elseif ((int) $file['user_id'] !== $userId) {
        header('Location: projects.php');
        exit;
    }
}

// Fetch sibling files for prev/next navigation
if (!empty($file['folder_id'])) {
    $sibStmt = $db->prepare("SELECT id, original_name FROM files WHERE folder_id = ? ORDER BY upload_date DESC");
    $sibStmt->bind_param('i', $file['folder_id']);
} elseif ($projectId) {
    $sibStmt = $db->prepare("SELECT id, original_name FROM files WHERE project_id = ? AND folder_id IS NULL ORDER BY upload_date DESC");
    $sibStmt->bind_param('i', $projectId);
} else {
    $sibStmt = $db->prepare("SELECT id, original_name FROM files WHERE user_id = ? ORDER BY upload_date DESC");
    $sibStmt->bind_param('i', $userId);
}
$sibStmt->execute();
$siblings = $sibStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$currentIndex = array_search($fileId, array_column($siblings, 'id'));
$prevFile = ($currentIndex !== false && $currentIndex > 0) ? $siblings[$currentIndex - 1] : null;
$nextFile = ($currentIndex !== false && $currentIndex < count($siblings) - 1) ? $siblings[$currentIndex + 1] : null;
$totalFiles = count($siblings);
$fileNum = ($currentIndex !== false) ? $currentIndex + 1 : 1;

$mt = $file['mime_type'] ?? '';
$ext = strtolower(pathinfo($file['original_name'] ?? '', PATHINFO_EXTENSION));

// Detect by MIME type OR by extension (whichever matches)
$isImage = in_array($mt, ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'])
    || in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
$isVideo = ($mt === 'video/mp4' || strpos($mt, 'video/') === 0)
    || in_array($ext, ['mp4', 'webm', 'ogg', 'mov']);
$isPdf = ($mt === 'application/pdf') || $ext === 'pdf';

// Normalise mime for video tag
if ($isVideo && $mt === '')
    $mt = 'video/mp4';

$fname = htmlspecialchars($file['original_name'] ?? 'File');
$pname = htmlspecialchars($file['project_name'] ?? 'Project');
$backUrl = $projectId ? "projects.php?id={$projectId}" : "projects.php";
$isWatermarked = (int) ($file['watermarked'] ?? 0) === 1;
?>

<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <link rel="icon" type="image/png" href="../Assets/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $fname; ?> — StegaVault</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200"
        rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#6366f1'
                    }
                }
            }
        };
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #0f1117;
        }

        .nav-btn {
            transition: background .15s, opacity .15s;
        }

        .nav-btn:disabled {
            opacity: 0.25;
            cursor: not-allowed;
        }

        #previewArea img {
            max-height: calc(100vh - 56px);
            max-width: 100%;
            object-fit: contain;
        }

        #previewArea video {
            max-height: calc(100vh - 56px);
            max-width: 100%;
        }

        #previewArea iframe {
            width: 100%;
            height: calc(100vh - 56px);
            border: none;
        }
    </style>
</head>

<body class="bg-[#0f1117] text-white h-screen flex flex-col overflow-hidden">

    <!-- ── Top Bar ─────────────────────────────────────────────────────────── -->
    <header
        class="h-14 flex-shrink-0 flex items-center justify-between px-4 border-b border-white/10 bg-[#15181f]/90 backdrop-blur-sm z-10">

        <!-- Left: back + breadcrumb -->
        <div class="flex items-center gap-3 min-w-0">
            <a href="<?php echo $backUrl; ?>"
                class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-colors flex-shrink-0"
                title="Back to project">
                <span class="material-symbols-outlined text-[20px]">arrow_back</span>
            </a>
            <div class="flex items-center gap-1 text-sm min-w-0">
                <a href="<?php echo $backUrl; ?>"
                    class="text-slate-400 hover:text-white transition-colors truncate max-w-[140px]">
                    <?php echo $pname; ?>
                </a>
                <span class="text-slate-600 flex-shrink-0">/</span>
                <span class="text-white font-medium truncate max-w-[220px]"><?php echo $fname; ?></span>
                <button id="fileDropdownBtn" onclick="toggleDropdown()"
                    class="ml-1 p-0.5 rounded text-slate-500 hover:text-white transition-colors flex-shrink-0">
                    <span class="material-symbols-outlined text-[16px]">expand_more</span>
                </button>
            </div>
        </div>

        <!-- Center: prev / counter / next -->
        <div class="flex items-center gap-1 absolute left-1/2 -translate-x-1/2">
            <?php if ($prevFile): ?>
                <a href="preview.php?id=<?php echo $prevFile['id']; ?>&project_id=<?php echo $projectId; ?>"
                    class="nav-btn p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10" title="Previous (←)">
                    <span class="material-symbols-outlined text-[20px]">chevron_left</span>
                </a>
            <?php else: ?>
                <button disabled class="nav-btn p-1.5 rounded-lg text-slate-400">
                    <span class="material-symbols-outlined text-[20px]">chevron_left</span>
                </button>
            <?php endif; ?>

            <span class="text-sm text-slate-400 px-2 tabular-nums"><?php echo $fileNum; ?> of
                <?php echo $totalFiles; ?></span>

            <?php if ($nextFile): ?>
                <a href="preview.php?id=<?php echo $nextFile['id']; ?>&project_id=<?php echo $projectId; ?>"
                    class="nav-btn p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10" title="Next (→)">
                    <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                </a>
            <?php else: ?>
                <button disabled class="nav-btn p-1.5 rounded-lg text-slate-400">
                    <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                </button>
            <?php endif; ?>
        </div>

        <!-- Right: download + more -->
        <div class="flex items-center gap-2 flex-shrink-0">
            <!-- <a href="../api/download.php?file_id=<?php echo $fileId; ?>"
                class="flex items-center gap-2 px-4 py-1.5 bg-primary hover:bg-primary/90 text-white text-sm font-semibold rounded-lg transition-colors">
                <span class="material-symbols-outlined text-[18px]">download</span>
                Download
            </a> -->
            <span
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-semibold border <?php echo $isWatermarked ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-amber-500/10 text-amber-400 border-amber-500/30'; ?>">
                <span
                    class="material-symbols-outlined text-[14px]"><?php echo $isWatermarked ? 'verified' : 'warning'; ?></span>
                <?php echo $isWatermarked ? 'Watermarked' : 'Not Watermarked'; ?>
            </span>
            <div class="text-xs text-slate-500 font-medium ml-1">
                <?php
                $sz = $file['file_size'] ?? 0;
                echo $sz >= 1048576 ? round($sz / 1048576, 1) . ' MB' : round($sz / 1024, 1) . ' KB';
                ?>
            </div>
        </div>
    </header>

    <!-- ── File dropdown ────────────────────────────────────────────────────── -->
    <div id="fileDropdown"
        class="hidden fixed top-14 left-1/2 -translate-x-1/2 z-50 w-72 bg-[#1e2130] border border-white/10 rounded-xl shadow-2xl overflow-hidden max-h-80 overflow-y-auto">
        <?php foreach ($siblings as $i => $sib): ?>
            <a href="preview.php?id=<?php echo $sib['id']; ?>&project_id=<?php echo $projectId; ?>"
                class="flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-white/5 transition-colors <?php echo $sib['id'] === $fileId ? 'bg-primary/20 text-primary' : 'text-slate-300'; ?>">
                <span class="text-slate-500 text-xs tabular-nums w-5 text-right"><?php echo $i + 1; ?></span>
                <span class="truncate"><?php echo htmlspecialchars($sib['original_name']); ?></span>
                <?php if ($sib['id'] === $fileId): ?><span
                        class="material-symbols-outlined text-[14px] ml-auto flex-shrink-0">check</span><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- ── Preview Area ─────────────────────────────────────────────────────── -->
    <main id="previewArea" class="flex-1 flex items-center justify-center overflow-hidden bg-[#0a0b0f] relative">
        <?php if ($isImage): ?>
            <img src="../api/view.php?id=<?php echo $fileId; ?>" alt="<?php echo $fname; ?>"
                class="max-h-full max-w-full object-contain select-none" draggable="false">

        <?php elseif ($isVideo): ?>
            <video controls autoplay class="max-h-full max-w-full">
                <source src="../api/view.php?id=<?php echo $fileId; ?>" type="<?php echo htmlspecialchars($mt); ?>">
                Your browser does not support video.
            </video>

        <?php elseif ($isPdf): ?>
            <iframe src="../api/view.php?id=<?php echo $fileId; ?>" class="w-full h-full"></iframe>

        <?php else: ?>
            <!-- Generic doc placeholder -->
            <div class="flex flex-col items-center gap-6 text-center px-8">
                <div class="size-24 rounded-3xl bg-blue-500/10 flex items-center justify-center">
                    <span class="material-symbols-outlined text-5xl text-blue-400">description</span>
                </div>
                <div>
                    <p class="text-white text-lg font-semibold mb-1"><?php echo $fname; ?></p>
                    <p class="text-slate-400 text-sm mb-6">Preview not available for this file type</p>
                    <a href="../api/download.php?file_id=<?php echo $fileId; ?>"
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary hover:bg-primary/90 text-white font-semibold rounded-xl transition-colors">
                        <span class="material-symbols-outlined text-[20px]">download</span>
                        Download File
                    </a>
                </div>
                <div class="text-xs text-slate-600">
                    Uploaded by <?php echo htmlspecialchars($file['uploader_name']); ?>
                    · <?php echo date('M d, Y', strtotime($file['upload_date'])); ?>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Backdrop to close dropdown -->
    <div id="dropdownBackdrop" class="hidden fixed inset-0 z-40" onclick="closeDropdown()"></div>

    <script>
        // Keyboard navigation
        document.addEventListener('keydown', e => {
            if (e.key === 'ArrowLeft' && <?php echo $prevFile ? 'true' : 'false'; ?>) {
                location.href = 'preview.php?id=<?php echo $prevFile ? $prevFile['id'] : ''; ?>&project_id=<?php echo $projectId; ?>';
            }
            if (e.key === 'ArrowRight' && <?php echo $nextFile ? 'true' : 'false'; ?>) {
                location.href = 'preview.php?id=<?php echo $nextFile ? $nextFile['id'] : ''; ?>&project_id=<?php echo $projectId; ?>';
            }
            if (e.key === 'Escape') closeDropdown();
        });

        function toggleDropdown() {
            const d = document.getElementById('fileDropdown');
            const bg = document.getElementById('dropdownBackdrop');
            const hidden = d.classList.contains('hidden');
            d.classList.toggle('hidden', !hidden);
            bg.classList.toggle('hidden', !hidden);
        }

        function closeDropdown() {
            document.getElementById('fileDropdown').classList.add('hidden');
            document.getElementById('dropdownBackdrop').classList.add('hidden');
        }
    </script>
</body>

</html>