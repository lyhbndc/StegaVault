<?php
/**
 * StegaVault - Super Admin Backup & Restore API
 * File: api/super_admin_backup.php
 *
 * Handles database export/import via PHP + PDO (Supabase-compatible)
 * and Docker volume snapshots via shell exec.
 */

session_start();

error_reporting(0);
ini_set('display_errors', 0);

// Auth check — super admins only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../includes/db.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Backup storage directory (outside web-accessible paths is ideal;
// .htaccess below blocks direct HTTP access as a second layer)
define('BACKUP_DIR', realpath(__DIR__ . '/..') . '/backups/');
define('BACKUP_META', BACKUP_DIR . 'backups_meta.json');

if (!is_dir(BACKUP_DIR)) {
    mkdir(BACKUP_DIR, 0750, true);
}

function sendResponse($success, $data = null, $error = null, $code = 200)
{
    http_response_code($code);
    echo json_encode(['success' => $success, 'data' => $data, 'error' => $error]);
    exit;
}

function formatBytes($bytes)
{
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}

function loadMeta()
{
    if (!file_exists(BACKUP_META)) return [];
    return json_decode(file_get_contents(BACKUP_META), true) ?? [];
}

function saveMeta(array $meta)
{
    file_put_contents(BACKUP_META, json_encode(array_values($meta), JSON_PRETTY_PRINT));
}

// ─────────────────────────────────────────────
// CREATE BACKUP
// ─────────────────────────────────────────────
if ($method === 'POST' && $action === 'create') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $includeDocker = !empty($input['include_docker']);

    try {
        $pdo = $db->getConnection();

        // Discover all public tables
        $tablesStmt = $pdo->query(
            "SELECT tablename FROM pg_catalog.pg_tables
             WHERE schemaname = 'public'
             ORDER BY tablename"
        );
        $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($tables)) {
            sendResponse(false, null, 'No tables found in public schema', 500);
        }

        $timestamp   = date('Ymd_His');
        $backupId    = 'SV-' . date('Ymd-Hi') . '-' . strtolower(substr(bin2hex(random_bytes(2)), 0, 4));
        $dbFilename  = 'db_' . $timestamp . '.sql';
        $dbFilepath  = BACKUP_DIR . $dbFilename;

        // ── Build SQL export ──────────────────────────────────
        $lines   = [];
        $lines[] = '-- StegaVault Database Backup';
        $lines[] = '-- Generated : ' . date('Y-m-d H:i:s T');
        $lines[] = '-- Backup ID : ' . $backupId;
        $lines[] = '-- Tables    : ' . count($tables);
        $lines[] = '-- NOTE: Restore by running this file in your Supabase SQL Editor';
        $lines[] = '--       or via psql. Existing rows with the same PK will be updated.';
        $lines[] = '';
        $lines[] = 'BEGIN;';
        $lines[] = '';

        $totalRows = 0;

        foreach ($tables as $table) {
            // Get column list
            $colStmt = $pdo->prepare(
                "SELECT column_name, data_type
                 FROM information_schema.columns
                 WHERE table_schema = 'public' AND table_name = :t
                 ORDER BY ordinal_position"
            );
            $colStmt->execute([':t' => $table]);
            $columns = $colStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($columns)) continue;

            $colNames    = array_column($columns, 'column_name');
            $quotedCols  = array_map(fn($c) => '"' . $c . '"', $colNames);

            // Get primary key columns for ON CONFLICT
            $pkStmt = $pdo->prepare(
                "SELECT kcu.column_name
                 FROM information_schema.table_constraints tc
                 JOIN information_schema.key_column_usage kcu
                   ON tc.constraint_name = kcu.constraint_name
                  AND tc.table_schema    = kcu.table_schema
                 WHERE tc.constraint_type = 'PRIMARY KEY'
                   AND tc.table_schema    = 'public'
                   AND tc.table_name      = :t
                 ORDER BY kcu.ordinal_position"
            );
            $pkStmt->execute([':t' => $table]);
            $pkCols = $pkStmt->fetchAll(PDO::FETCH_COLUMN);

            // Fetch all rows
            $rowsStmt = $pdo->query('SELECT * FROM "' . $table . '"');
            $rows     = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);

            $lines[] = '-- ── Table: ' . $table . ' (' . count($rows) . ' rows) ──────────────';

            if (empty($rows)) {
                $lines[] = '';
                continue;
            }

            $totalRows += count($rows);

            // Build upsert INSERT
            $conflictClause = '';
            if (!empty($pkCols)) {
                $quotedPk       = array_map(fn($c) => '"' . $c . '"', $pkCols);
                $updateCols     = array_filter($colNames, fn($c) => !in_array($c, $pkCols));
                if (!empty($updateCols)) {
                    $updateParts    = array_map(fn($c) => '"' . $c . '" = EXCLUDED."' . $c . '"', $updateCols);
                    $conflictClause = "\n    ON CONFLICT (" . implode(', ', $quotedPk) . ")"
                        . " DO UPDATE SET " . implode(', ', $updateParts);
                } else {
                    $conflictClause = "\n    ON CONFLICT (" . implode(', ', $quotedPk) . ") DO NOTHING";
                }
            }

            foreach ($rows as $row) {
                $values = array_map(function ($val) {
                    if ($val === null)  return 'NULL';
                    if ($val === true)  return 'TRUE';
                    if ($val === false) return 'FALSE';
                    // Detect boolean strings from PDO
                    if ($val === 't')   return 'TRUE';
                    if ($val === 'f')   return 'FALSE';
                    return "'" . str_replace("'", "''", (string) $val) . "'";
                }, array_values($row));

                $lines[] = 'INSERT INTO "' . $table . '" (' . implode(', ', $quotedCols) . ')'
                    . ' VALUES (' . implode(', ', $values) . ')'
                    . $conflictClause . ';';
            }

            $lines[] = '';
        }

        $lines[] = 'COMMIT;';

        $sqlContent = implode("\n", $lines);
        file_put_contents($dbFilepath, $sqlContent);

        // ── Docker volume backup (optional) ──────────────────
        $dockerResult = null;
        if ($includeDocker) {
            $dockerResult = backupDockerVolumes($timestamp, $backupId);
        }

        // ── Save metadata ─────────────────────────────────────
        $filesize = filesize($dbFilepath);
        $meta     = loadMeta();

        array_unshift($meta, [
            'id'           => $backupId,
            'filename'     => $dbFilename,
            'type'         => 'manual',
            'size'         => $filesize,
            'size_label'   => formatBytes($filesize),
            'tables'       => count($tables),
            'rows'         => $totalRows,
            'created_at'   => date('Y-m-d H:i:s'),
            'created_by'   => $_SESSION['name'],
            'docker'       => $dockerResult,
        ]);

        // Keep 30 most recent
        $meta = array_slice($meta, 0, 30);
        saveMeta($meta);

        sendResponse(true, [
            'backup_id'  => $backupId,
            'filename'   => $dbFilename,
            'size'       => formatBytes($filesize),
            'tables'     => count($tables),
            'rows'       => $totalRows,
            'docker'     => $dockerResult,
            'message'    => 'Backup created successfully',
        ]);

    } catch (Exception $e) {
        sendResponse(false, null, 'Backup failed: ' . $e->getMessage(), 500);
    }
}

// ─────────────────────────────────────────────
// LIST BACKUPS
// ─────────────────────────────────────────────
if ($method === 'GET' && $action === 'list') {
    $meta = loadMeta();

    // Remove entries whose files were deleted outside the app
    $meta = array_values(array_filter($meta, fn($b) => file_exists(BACKUP_DIR . $b['filename'])));
    saveMeta($meta);

    sendResponse(true, ['backups' => $meta, 'total' => count($meta)]);
}

// ─────────────────────────────────────────────
// DOWNLOAD BACKUP
// ─────────────────────────────────────────────
if ($method === 'GET' && $action === 'download') {
    // Override Content-Type for file streaming
    header_remove('Content-Type');

    $filename = basename($_GET['file'] ?? '');
    if (empty($filename)) {
        header('Content-Type: application/json');
        sendResponse(false, null, 'No file specified', 400);
    }

    // Only allow .sql files from the backup directory
    if (!preg_match('/^db_\d{8}_\d{6}\.sql$/', $filename)) {
        header('Content-Type: application/json');
        sendResponse(false, null, 'Invalid filename', 400);
    }

    $filepath = BACKUP_DIR . $filename;
    if (!file_exists($filepath)) {
        header('Content-Type: application/json');
        sendResponse(false, null, 'File not found', 404);
    }

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: no-cache');
    readfile($filepath);
    exit;
}

// ─────────────────────────────────────────────
// DELETE BACKUP
// ─────────────────────────────────────────────
if ($method === 'POST' && $action === 'delete') {
    $input    = json_decode(file_get_contents('php://input'), true) ?? [];
    $filename = basename($input['filename'] ?? '');

    if (empty($filename)) sendResponse(false, null, 'No filename specified', 400);

    $filepath = BACKUP_DIR . $filename;
    if (file_exists($filepath)) {
        unlink($filepath);
    }

    $meta = loadMeta();
    $meta = array_values(array_filter($meta, fn($b) => $b['filename'] !== $filename));
    saveMeta($meta);

    sendResponse(true, ['message' => 'Backup deleted']);
}

// ─────────────────────────────────────────────
// RESTORE BACKUP
// ─────────────────────────────────────────────
if ($method === 'POST' && $action === 'restore') {
    $input    = json_decode(file_get_contents('php://input'), true) ?? [];
    $filename = basename($input['filename'] ?? '');

    if (empty($filename)) sendResponse(false, null, 'No filename specified', 400);

    $filepath = BACKUP_DIR . $filename;
    if (!file_exists($filepath)) sendResponse(false, null, 'Backup file not found', 404);

    try {
        $pdo = $db->getConnection();
        $sql = file_get_contents($filepath);

        // Strip comment lines and split into individual statements
        $lines      = explode("\n", $sql);
        $statements = [];
        $current    = '';

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || strpos($trimmed, '--') === 0) continue;

            $current .= ' ' . $trimmed;

            if (substr(rtrim($current), -1) === ';') {
                $stmt = trim($current);
                // Skip transaction control — PDO handles that
                if (!in_array(strtoupper($stmt), ['BEGIN;', 'COMMIT;', 'ROLLBACK;'])) {
                    $statements[] = $stmt;
                }
                $current = '';
            }
        }

        $pdo->beginTransaction();

        $executed = 0;
        foreach ($statements as $stmt) {
            $pdo->exec($stmt);
            $executed++;
        }

        $pdo->commit();

        sendResponse(true, [
            'message'    => 'Database restored successfully',
            'statements' => $executed,
            'filename'   => $filename,
        ]);

    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        sendResponse(false, null, 'Restore failed: ' . $e->getMessage(), 500);
    }
}

// ─────────────────────────────────────────────
// DOCKER STATUS
// ─────────────────────────────────────────────
if ($method === 'GET' && $action === 'docker_status') {
    sendResponse(true, dockerStatus());
}

// ─────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────

function dockerAvailable(): bool
{
    if (!function_exists('exec')) return false;
    exec('docker info 2>&1', $out, $code);
    return $code === 0;
}

function dockerStatus(): array
{
    if (!dockerAvailable()) {
        return ['available' => false, 'reason' => 'Docker is not accessible from this PHP process'];
    }

    exec('docker volume ls --format "{{.Name}}" 2>&1', $volumes, $code);
    $stegaVolumes = array_filter($volumes ?? [], fn($v) => stripos($v, 'stegavault') !== false);

    return [
        'available' => true,
        'volumes'   => array_values($stegaVolumes),
        'total'     => count($stegaVolumes),
    ];
}

function backupDockerVolumes(string $timestamp, string $backupId): array
{
    if (!dockerAvailable()) {
        return ['success' => false, 'reason' => 'Docker not accessible'];
    }

    exec('docker volume ls --format "{{.Name}}" 2>&1', $allVolumes);
    $stegaVolumes = array_filter($allVolumes ?? [], fn($v) => stripos($v, 'stegavault') !== false);

    if (empty($stegaVolumes)) {
        return ['success' => false, 'reason' => 'No stegavault Docker volumes found'];
    }

    $backed = [];
    foreach ($stegaVolumes as $vol) {
        $archiveName = 'docker_' . $vol . '_' . $timestamp . '.tar.gz';
        $archivePath = BACKUP_DIR . $archiveName;

        $cmd = 'docker run --rm'
            . ' -v ' . escapeshellarg($vol) . ':/data:ro'
            . ' -v ' . escapeshellarg(BACKUP_DIR) . ':/backup'
            . ' alpine tar czf /backup/' . escapeshellarg($archiveName) . ' -C /data . 2>&1';

        exec($cmd, $out, $code);

        $backed[] = [
            'volume'   => $vol,
            'filename' => $archiveName,
            'success'  => ($code === 0),
            'size'     => file_exists($archivePath) ? formatBytes(filesize($archivePath)) : null,
        ];
    }

    return ['success' => true, 'volumes' => $backed];
}

sendResponse(false, null, 'Unknown action', 400);
