<?php
/**
 * StegaVault - Scheduled Backup Cron Script
 * File: api/backup_cron.php
 *
 * Runs via cron job — CLI only, no session required.
 * Schedule:  0 0,12 * * *  (midnight + noon, Manila time)
 *
 * Cron entry (run as www-data or ubuntu — adjust to your server):
 *   0 0,12 * * * TZ=Asia/Manila php /home/ubuntu/PHP/www/StegaVault/api/backup_cron.php >> /home/ubuntu/PHP/www/StegaVault/logs/backup_cron.log 2>&1
 */

// Block direct web access
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo json_encode(['error' => 'CLI only']);
    exit(1);
}

// ── Timezone ─────────────────────────────────────────────────────────────────
date_default_timezone_set('Asia/Manila');

// ── Bootstrap ─────────────────────────────────────────────────────────────────
define('CRON_MODE', true);

// Fake a minimal session so SuperAdminLogger can store the actor name
$_SESSION = [];
$_SESSION['name']  = 'Scheduled Cron';
$_SESSION['email'] = 'cron@system';
$_SESSION['role']  = 'super_admin';

$rootDir = dirname(__DIR__); // .../StegaVault

require_once $rootDir . '/includes/db.php';
require_once $rootDir . '/includes/SuperAdminLogger.php';

// ── Paths ─────────────────────────────────────────────────────────────────────
define('BACKUP_DIR',  $rootDir . '/backups/');
define('BACKUP_META', BACKUP_DIR . 'backups_meta.json');
define('LOG_DIR',     $rootDir . '/logs/');

// Ensure directories exist
foreach ([BACKUP_DIR, LOG_DIR] as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function cronLog(string $msg): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    echo $line;
}

function formatBytes(int $bytes): string
{
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}

function loadMeta(): array
{
    if (!file_exists(BACKUP_META)) return [];
    return json_decode(file_get_contents(BACKUP_META), true) ?? [];
}

function saveMeta(array $meta): void
{
    file_put_contents(BACKUP_META, json_encode(array_values($meta), JSON_PRETTY_PRINT));
}

// ── Writable check ────────────────────────────────────────────────────────────
if (!is_writable(BACKUP_DIR)) {
    cronLog('ERROR: Backup directory is not writable: ' . BACKUP_DIR);
    cronLog('Fix: sudo chown www-data:www-data ' . BACKUP_DIR . ' && sudo chmod 755 ' . BACKUP_DIR);
    exit(1);
}

// ── Run DB backup ─────────────────────────────────────────────────────────────
cronLog('=== StegaVault Scheduled Backup START ===');
cronLog('Timezone: ' . date_default_timezone_get() . ' | Time: ' . date('Y-m-d H:i:s'));

try {
    $pdo = $db->getConnection();

    // Discover public tables
    $tablesStmt = $pdo->query(
        "SELECT tablename FROM pg_catalog.pg_tables
         WHERE schemaname = 'public'
         ORDER BY tablename"
    );
    $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        cronLog('ERROR: No tables found in public schema. Aborting.');
        exit(1);
    }

    cronLog('Tables found: ' . count($tables) . ' — ' . implode(', ', $tables));

    $timestamp  = date('Ymd_His');
    $backupId   = 'SV-AUTO-' . date('Ymd-Hi') . '-' . strtolower(substr(bin2hex(random_bytes(2)), 0, 4));
    $dbFilename = 'db_' . $timestamp . '.sql';
    $dbFilepath = BACKUP_DIR . $dbFilename;

    // ── Build SQL export ──────────────────────────────────────────────────────
    $lines   = [];
    $lines[] = '-- StegaVault Database Backup (Scheduled)';
    $lines[] = '-- Generated : ' . date('Y-m-d H:i:s T');
    $lines[] = '-- Backup ID : ' . $backupId;
    $lines[] = '-- Tables    : ' . count($tables);
    $lines[] = '-- Type      : automatic (cron)';
    $lines[] = '-- NOTE: Restore by running this file in your Supabase SQL Editor';
    $lines[] = '--       or via psql. Existing rows with the same PK will be updated.';
    $lines[] = '';
    $lines[] = 'BEGIN;';
    $lines[] = '';

    $totalRows = 0;

    foreach ($tables as $table) {
        // Column list
        $colStmt = $pdo->prepare(
            "SELECT column_name, data_type
             FROM information_schema.columns
             WHERE table_schema = 'public' AND table_name = :t
             ORDER BY ordinal_position"
        );
        $colStmt->execute([':t' => $table]);
        $columns = $colStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($columns)) continue;

        $colNames   = array_column($columns, 'column_name');
        $quotedCols = array_map(fn($c) => '"' . $c . '"', $colNames);

        // Primary key columns for ON CONFLICT
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

        // Fetch rows
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
            $quotedPk   = array_map(fn($c) => '"' . $c . '"', $pkCols);
            $updateCols = array_filter($colNames, fn($c) => !in_array($c, $pkCols));
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
                if ($val === 't')   return 'TRUE';
                if ($val === 'f')   return 'FALSE';
                return "'" . str_replace("'", "''", (string) $val) . "'";
            }, array_values($row));

            $lines[] = 'INSERT INTO "' . $table . '" (' . implode(', ', $quotedCols) . ')'
                . ' VALUES (' . implode(', ', $values) . ')'
                . $conflictClause . ';';
        }

        $lines[] = '';

        cronLog('  Exported table: ' . $table . ' (' . count($rows) . ' rows)');
    }

    $lines[] = 'COMMIT;';

    $sqlContent = implode("\n", $lines);
    $written    = file_put_contents($dbFilepath, $sqlContent);

    if ($written === false) {
        cronLog('ERROR: Could not write backup file: ' . $dbFilepath);
        exit(1);
    }

    $filesize = filesize($dbFilepath);

    // ── Save metadata ─────────────────────────────────────────────────────────
    $meta = loadMeta();

    array_unshift($meta, [
        'id'         => $backupId,
        'filename'   => $dbFilename,
        'type'       => 'automatic',
        'size'       => $filesize,
        'size_label' => formatBytes($filesize),
        'tables'     => count($tables),
        'rows'       => $totalRows,
        'created_at' => date('Y-m-d H:i:s'),
        'created_by' => 'Scheduled Cron',
        'docker'     => null,
    ]);

    // Keep 30 most recent
    $meta = array_slice($meta, 0, 30);
    saveMeta($meta);

    SuperAdminLogger::log('backup_db_created', 'backup', [
        'backup_id'  => $backupId,
        'filename'   => $dbFilename,
        'tables'     => count($tables),
        'rows'       => $totalRows,
        'size'       => formatBytes($filesize),
        'created_by' => 'Scheduled Cron',
    ]);

    cronLog('Backup file: ' . $dbFilename);
    cronLog('Size       : ' . formatBytes($filesize));
    cronLog('Tables     : ' . count($tables));
    cronLog('Total rows : ' . $totalRows);
    cronLog('=== StegaVault Scheduled Backup COMPLETE ===');
    exit(0);

} catch (Exception $e) {
    cronLog('EXCEPTION: ' . $e->getMessage());
    cronLog('=== StegaVault Scheduled Backup FAILED ===');
    exit(1);
}
