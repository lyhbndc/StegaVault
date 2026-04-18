<?php
/**
 * StegaVault - Super Admin Backup & Restore API
 * File: api/super_admin_backup.php
 */

session_start();

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Auth check — super admins only
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'data' => null, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/SuperAdminLogger.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Use host-level backup storage
define('BACKUP_DIR', '/opt/backups/');
define('BACKUP_META', BACKUP_DIR . 'backups_meta.json');

// App uploads directory
define('UPLOADS_DIR', dirname(__DIR__) . '/uploads/');

if (!is_dir(BACKUP_DIR)) {
    @mkdir(BACKUP_DIR, 0755, true);
}

function sendResponse($success, $data = null, $error = null, $code = 200)
{
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'data'    => $data,
        'error'   => $error
    ]);
    exit;
}

function formatBytes($bytes)
{
    $bytes = (int)$bytes;
    if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return round($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}

function loadMeta()
{
    if (!file_exists(BACKUP_META)) {
        return [];
    }

    $json = file_get_contents(BACKUP_META);
    $data = json_decode($json, true);

    return is_array($data) ? $data : [];
}

function saveMeta(array $meta)
{
    file_put_contents(BACKUP_META, json_encode(array_values($meta), JSON_PRETTY_PRINT));
}

function safeRelativePath(string $path): string
{
    $path = str_replace(["..\\", "../"], '', $path);
    return ltrim($path, '/\\');
}

function dockerAvailable(): bool
{
    if (!function_exists('exec')) return false;
    exec('docker info 2>&1', $out, $code);
    return $code === 0;
}

function dockerStatus(): array
{
    if (!dockerAvailable()) {
        return [
            'available' => false,
            'reason'    => 'Docker is not accessible from this PHP process'
        ];
    }

    exec('docker volume ls --format "{{.Name}}" 2>&1', $volumes, $code);

    $stegaVolumes = array_values(array_filter($volumes ?? [], function ($v) {
        return stripos($v, 'stegavault') !== false;
    }));

    return [
        'available' => true,
        'volumes'   => $stegaVolumes,
        'total'     => count($stegaVolumes),
    ];
}

// ─────────────────────────────────────────────
// CREATE DATABASE BACKUP USING /opt/backup.sh
// ─────────────────────────────────────────────
if ($method === 'POST' && ($action === 'create' || $action === 'run_backup')) {
    try {
        $script = '/opt/backup.sh';

        if (!file_exists($script)) {
            sendResponse(false, null, 'Backup script not found at /opt/backup.sh', 500);
        }

        if (!is_executable($script)) {
            sendResponse(false, null, 'Backup script is not executable', 500);
        }

        $before = glob(BACKUP_DIR . '*', GLOB_ONLYDIR) ?: [];

        $output = shell_exec('bash /opt/backup.sh 2>&1');

        if ($output === null) {
            sendResponse(false, null, 'Backup command failed to execute', 500);
        }

        $after = glob(BACKUP_DIR . '*', GLOB_ONLYDIR) ?: [];
        $newDirs = array_values(array_diff($after, $before));

if (!empty($newDirs)) {
    rsort($newDirs);
    $latestDir = $newDirs[0];
} else {
    sendResponse(false, null, 'Backup script did not create a new backup folder. Output: ' . trim($output), 500);
}

        $dbFile     = $latestDir . '/database.dump';
        $filesFile  = $latestDir . '/files.tar.gz';
        $configFile = $latestDir . '/config.tar.gz';

        if (!file_exists($dbFile)) {
            sendResponse(false, null, 'Backup folder created, but database.dump was not found. Output: ' . $output, 500);
        }

        $backupId = 'SV-' . date('Ymd-Hi') . '-' . strtolower(substr(bin2hex(random_bytes(2)), 0, 4));

        $tableCount = 0;
        $rowCount = 0;

        try {
            $pdo = $db->getConnection();
            $tStmt = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE'");
            $tableCount = (int) $tStmt->fetchColumn();

            $rStmt = $pdo->query("SELECT COALESCE(SUM(n_live_tup),0)::bigint FROM pg_stat_user_tables WHERE schemaname = 'public'");
            $rowCount = (int) $rStmt->fetchColumn();
        } catch (Exception $e) {
            $tableCount = 0;
            $rowCount = 0;
        }

        $folderName = basename($latestDir);
        $meta = loadMeta();

        $newEntries = [];

        $newEntries[] = [
            'id'         => $backupId,
            'filename'   => $folderName . '/database.dump',
            'type'       => 'manual',
            'size'       => filesize($dbFile),
            'size_label' => formatBytes(filesize($dbFile)),
            'tables'     => $tableCount,
            'rows'       => $rowCount,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $_SESSION['name'] ?? 'System',
        ];

        if (file_exists($filesFile)) {
            $newEntries[] = [
                'id'         => $backupId . '-FILES',
                'filename'   => $folderName . '/files.tar.gz',
                'type'       => 'files',
                'size'       => filesize($filesFile),
                'size_label' => formatBytes(filesize($filesFile)),
                'files'      => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $_SESSION['name'] ?? 'System',
            ];
        }

        if (file_exists($configFile)) {
            $newEntries[] = [
                'id'         => $backupId . '-CONFIG',
                'filename'   => $folderName . '/config.tar.gz',
                'type'       => 'config',
                'size'       => filesize($configFile),
                'size_label' => formatBytes(filesize($configFile)),
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $_SESSION['name'] ?? 'System',
            ];
        }

        $meta = array_merge($newEntries, $meta);
        $meta = array_slice($meta, 0, 50);
        saveMeta($meta);

        if (class_exists('SuperAdminLogger')) {
            SuperAdminLogger::log('backup_db_created', 'backup', [
                'backup_id' => $backupId,
                'filename'  => $folderName . '/database.dump',
                'size'      => formatBytes(filesize($dbFile)),
            ]);
        }

        sendResponse(true, [
            'backup_id' => $backupId,
            'filename'  => $folderName . '/database.dump',
            'size'      => formatBytes(filesize($dbFile)),
            'tables'    => $tableCount,
            'rows'      => $rowCount,
            'message'   => 'Backup created successfully',
            'output'    => trim($output),
        ]);
    } catch (Exception $e) {
        sendResponse(false, null, 'Backup failed: ' . $e->getMessage(), 500);
    }
}


// ─────────────────────────────────────────────
// CREATE FILES BACKUP (uploads folder → .zip)
// ─────────────────────────────────────────────
if ($method === 'POST' && $action === 'create_files') {
    try {
        if (!class_exists('ZipArchive')) {
            sendResponse(false, null, 'ZipArchive PHP extension is not enabled on this server.', 500);
        }

        if (!is_dir(UPLOADS_DIR)) {
            sendResponse(false, null, 'Uploads directory not found at: ' . UPLOADS_DIR, 500);
        }

        set_time_limit(300);

        $timestamp   = date('Ymd_His');
        $backupId    = 'SV-FILES-' . date('Ymd-Hi') . '-' . strtolower(substr(bin2hex(random_bytes(2)), 0, 4));
        $zipFilename = 'files_' . $timestamp . '.zip';
        $zipFilepath = BACKUP_DIR . $zipFilename;

        $fileCount = 0;
        $zip = new ZipArchive();

        if ($zip->open($zipFilepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            sendResponse(false, null, 'Could not create ZIP file at: ' . $zipFilepath, 500);
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(UPLOADS_DIR, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || !$file->isReadable()) {
                continue;
            }

            $realPath     = $file->getRealPath();
            $relativePath = 'uploads/' . ltrim(substr($realPath, strlen(UPLOADS_DIR)), '/\\');

            $zip->addFile($realPath, $relativePath);
            $fileCount++;
        }

        $zip->close();

        if (!file_exists($zipFilepath)) {
            sendResponse(false, null, 'ZIP file was not created. Check server permissions.', 500);
        }

        $filesize = filesize($zipFilepath);
        $meta = loadMeta();

        array_unshift($meta, [
            'id'         => $backupId,
            'filename'   => $zipFilename,
            'type'       => 'files',
            'size'       => $filesize,
            'size_label' => formatBytes($filesize),
            'files'      => $fileCount,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $_SESSION['name'] ?? 'System',
        ]);

        $meta = array_slice($meta, 0, 50);
        saveMeta($meta);

        if (class_exists('SuperAdminLogger')) {
            SuperAdminLogger::log('backup_files_created', 'backup', [
                'backup_id' => $backupId,
                'filename'  => $zipFilename,
                'files'     => $fileCount,
                'size'      => formatBytes($filesize),
            ]);
        }

        sendResponse(true, [
            'backup_id' => $backupId,
            'filename'  => $zipFilename,
            'size'      => formatBytes($filesize),
            'files'     => $fileCount,
            'message'   => 'Files backup created successfully',
        ]);
    } catch (Exception $e) {
        sendResponse(false, null, 'Files backup failed: ' . $e->getMessage(), 500);
    }
}

// ─────────────────────────────────────────────
// LIST BACKUPS
// ─────────────────────────────────────────────
if ($method === 'GET' && $action === 'list') {
    $meta = loadMeta();

    $meta = array_values(array_filter($meta, function ($b) {
        return isset($b['filename']) && file_exists(BACKUP_DIR . $b['filename']);
    }));

    saveMeta($meta);

    sendResponse(true, [
        'backups' => $meta,
        'total'   => count($meta)
    ]);
}

// ─────────────────────────────────────────────
// DOWNLOAD BACKUP
// ─────────────────────────────────────────────
if ($method === 'GET' && $action === 'download') {
    header_remove('Content-Type');

    $filename = $_GET['file'] ?? '';
    if (empty($filename)) {
        header('Content-Type: application/json');
        sendResponse(false, null, 'No file specified', 400);
    }

    $filename = safeRelativePath($filename);
    $filepath = BACKUP_DIR . $filename;

    if (!file_exists($filepath) || !is_file($filepath)) {
        header('Content-Type: application/json');
        sendResponse(false, null, 'File not found', 404);
    }

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: no-cache');
    readfile($filepath);
    exit;
}

// ─────────────────────────────────────────────
// DELETE BACKUP
// ─────────────────────────────────────────────
if ($method === 'POST' && $action === 'delete') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $filename = $input['filename'] ?? '';

    if (empty($filename)) {
        sendResponse(false, null, 'No filename specified', 400);
    }

    $filename = safeRelativePath($filename);
    $filepath = BACKUP_DIR . $filename;

    if (file_exists($filepath) && is_file($filepath)) {
        unlink($filepath);
    }

    $meta = loadMeta();
    $meta = array_values(array_filter($meta, function ($b) use ($filename) {
        return ($b['filename'] ?? '') !== $filename;
    }));
    saveMeta($meta);

    if (class_exists('SuperAdminLogger')) {
        SuperAdminLogger::log('backup_deleted', 'backup', ['filename' => $filename]);
    }

    sendResponse(true, ['message' => 'Backup deleted']);
}

// ─────────────────────────────────────────────
// RESTORE DATABASE BACKUP
// NOTE: current EC2 database backup is database.dump,
// so SQL replay restore does not apply to that format.
// This keeps old .sql restore support only.
// ─────────────────────────────────────────────
if ($method === 'POST' && $action === 'restore') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $filename = $input['filename'] ?? '';

    if (empty($filename)) {
        sendResponse(false, null, 'No filename specified', 400);
    }

    $filename = safeRelativePath($filename);
    $filepath = BACKUP_DIR . $filename;

    if (!file_exists($filepath)) {
        sendResponse(false, null, 'Backup file not found', 404);
    }

    if (!str_ends_with(strtolower($filepath), '.dump')) {
        sendResponse(false, null, 'This restore endpoint only supports .dump backups right now.', 400);
    }

    if (!file_exists('/opt/restore.sh')) {
        sendResponse(false, null, 'Restore script not found at /opt/restore.sh', 500);
    }

    if (!is_executable('/opt/restore.sh')) {
        sendResponse(false, null, 'Restore script is not executable', 500);
    }

    try {
        $cmd = 'bash /opt/restore.sh ' . escapeshellarg($filepath) . ' 2>&1';
        $output = shell_exec($cmd);

        if ($output === null) {
            sendResponse(false, null, 'Restore command failed to execute', 500);
        }

        // Optional: fix sequences after restore
        try {
            $db->getConnection()->exec("DO \$\$
DECLARE r RECORD;
BEGIN
  FOR r IN
    SELECT table_name, column_name,
      pg_get_serial_sequence(format('%I.%I', table_schema, table_name), column_name) AS seq
    FROM information_schema.columns
    WHERE table_schema = 'public' AND column_default LIKE 'nextval%'
  LOOP
    IF r.seq IS NOT NULL THEN
      EXECUTE format('SELECT setval(%L, COALESCE((SELECT MAX(%I) FROM %I.%I), 0) + 1, false);',
        r.seq, r.column_name, 'public', r.table_name);
    END IF;
  END LOOP;
END \$\$;");
        } catch (Exception \$e) { /* sequence fix is optional */ }

        if (class_exists('SuperAdminLogger')) {
            SuperAdminLogger::log('backup_db_restored', 'backup', [
                'filename' => $filename,
                'output'   => trim($output),
            ]);
        }

        sendResponse(true, [
            'message'    => 'Database restored successfully',
            'filename'   => $filename,
            'statements' => 1,
            'output'     => trim($output),
        ]);
    } catch (Exception $e) {
        sendResponse(false, null, 'Restore failed: ' . $e->getMessage(), 500);
    }
}
// ─────────────────────────────────────────────
// RESTORE FILES BACKUP (.zip → uploads/)
// ─────────────────────────────────────────────
if ($method === 'POST' && $action === 'restore_files') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $filename = $input['filename'] ?? '';

    if (empty($filename)) {
        sendResponse(false, null, 'No filename specified', 400);
    }

    $filename = safeRelativePath($filename);
    $filepath = BACKUP_DIR . $filename;

    if (!file_exists($filepath)) {
        sendResponse(false, null, 'Backup file not found', 404);
    }

    if (!str_ends_with(strtolower($filepath), '.zip')) {
        sendResponse(false, null, 'Not a valid files backup', 400);
    }

    if (!class_exists('ZipArchive')) {
        sendResponse(false, null, 'ZipArchive PHP extension is not enabled on this server.', 500);
    }

    $extractTo = dirname(UPLOADS_DIR);
    $zip = new ZipArchive();

    if ($zip->open($filepath) !== true) {
        sendResponse(false, null, 'Cannot open ZIP archive', 500);
    }

    $zip->extractTo($extractTo);
    $zip->close();

    if (class_exists('SuperAdminLogger')) {
        SuperAdminLogger::log('backup_files_restored', 'backup', ['filename' => $filename]);
    }

    sendResponse(true, [
        'message'  => 'Files restored successfully',
        'filename' => $filename,
    ]);
}

// ─────────────────────────────────────────────
// FULL RESTORE
// Only supports .sql backups for now
// ─────────────────────────────────────────────
if ($method === 'POST' && $action === 'full_restore') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $filename = $input['filename'] ?? '';

    if (empty($filename)) {
        sendResponse(false, null, 'No filename specified', 400);
    }

    $filename = safeRelativePath($filename);
    $filepath = BACKUP_DIR . $filename;

    if (!file_exists($filepath)) {
        sendResponse(false, null, 'Backup file not found', 404);
    }

    if (!str_ends_with(strtolower($filepath), '.dump')) {
        sendResponse(false, null, 'This full restore endpoint only supports .dump backups right now.', 400);
    }

    if (!file_exists('/opt/restore.sh')) {
        sendResponse(false, null, 'Restore script not found at /opt/restore.sh', 500);
    }

    if (!is_executable('/opt/restore.sh')) {
        sendResponse(false, null, 'Restore script is not executable', 500);
    }

    try {
        $cmd = 'bash /opt/restore.sh ' . escapeshellarg($filepath) . ' 2>&1';
        $output = shell_exec($cmd);

        if ($output === null) {
            sendResponse(false, null, 'Full restore command failed to execute', 500);
        }

        try {
            $db->getConnection()->exec("DO \$\$
DECLARE r RECORD;
BEGIN
  FOR r IN
    SELECT table_name, column_name,
      pg_get_serial_sequence(format('%I.%I', table_schema, table_name), column_name) AS seq
    FROM information_schema.columns
    WHERE table_schema = 'public' AND column_default LIKE 'nextval%'
  LOOP
    IF r.seq IS NOT NULL THEN
      EXECUTE format('SELECT setval(%L, COALESCE((SELECT MAX(%I) FROM %I.%I), 0) + 1, false);',
        r.seq, r.column_name, 'public', r.table_name);
    END IF;
  END LOOP;
END \$\$;");
        } catch (Exception \$e) { /* sequence fix is optional */ }

        if (class_exists('SuperAdminLogger')) {
            SuperAdminLogger::log('backup_db_full_restored', 'backup', [
                'filename' => $filename,
                'output'   => trim($output),
            ]);
        }

        sendResponse(true, [
            'message'    => 'Full restore completed successfully',
            'filename'   => $filename,
            'tables'     => 'public',
            'statements' => 1,
            'output'     => trim($output),
        ]);
    } catch (Exception $e) {
        sendResponse(false, null, 'Full restore failed: ' . $e->getMessage(), 500);
    }
}
// ─────────────────────────────────────────────
// UPLOADS FOLDER SIZE
// ─────────────────────────────────────────────
if ($method === 'GET' && $action === 'uploads_size') {
    if (!is_dir(UPLOADS_DIR)) {
        sendResponse(false, null, 'Uploads directory not found');
    }

    $totalSize = 0;
    $fileCount = 0;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(UPLOADS_DIR, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $totalSize += $file->getSize();
            $fileCount++;
        }
    }

    sendResponse(true, [
        'size'  => formatBytes($totalSize),
        'bytes' => $totalSize,
        'files' => $fileCount,
    ]);
}

// ─────────────────────────────────────────────
// DOCKER STATUS
// ─────────────────────────────────────────────
if ($method === 'GET' && $action === 'docker_status') {
    sendResponse(true, dockerStatus());
}

sendResponse(false, null, 'Unknown action', 400);
