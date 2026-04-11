<?php
/**
 * StegaVault - Cleanup Legacy activity_log Table
 * File: migrations/cleanup_legacy_activity_log.php
 *
 * What it does:
 * 1) Creates archive table: activity_log_legacy_archive (if needed)
 * 2) Copies all rows from activity_log into archive (INSERT IGNORE)
 * 3) Drops legacy activity_log table
 */

require_once __DIR__ . '/../includes/db.php';

echo "StegaVault - Legacy Activity Log Cleanup\n";
echo "========================================\n\n";

$mysqli = $db->getConnection();

function tableExists(mysqli $mysqli, string $table): bool
{
    $safeTable = $mysqli->real_escape_string($table);
    $result = $mysqli->query("SHOW TABLES LIKE '{$safeTable}'");

    return $result && $result->num_rows > 0;
}

$legacyTable = 'activity_log';
$archiveTable = 'activity_log_legacy_archive';

if (!tableExists($mysqli, $legacyTable)) {
    echo "ℹ️ Legacy table '{$legacyTable}' does not exist. Nothing to clean.\n";
    exit(0);
}

echo "Found legacy table '{$legacyTable}'.\n";

if (!tableExists($mysqli, $archiveTable)) {
    echo "Creating archive table '{$archiveTable}'...\n";
    if (!$mysqli->query("CREATE TABLE {$archiveTable} LIKE {$legacyTable}")) {
        echo "❌ Failed to create archive table: " . $mysqli->error . "\n";
        exit(1);
    }
    echo "✅ Archive table created.\n";
} else {
    echo "Archive table '{$archiveTable}' already exists.\n";
}

$legacyCountResult = $mysqli->query("SELECT COUNT(*) AS c FROM {$legacyTable}");
$legacyCount = (int)($legacyCountResult->fetch_assoc()['c'] ?? 0);

echo "Legacy rows to archive: {$legacyCount}\n";

$copySql = "INSERT IGNORE INTO {$archiveTable} SELECT * FROM {$legacyTable}";
if (!$mysqli->query($copySql)) {
    echo "❌ Failed to archive legacy rows: " . $mysqli->error . "\n";
    exit(1);
}

$archiveCountResult = $mysqli->query("SELECT COUNT(*) AS c FROM {$archiveTable}");
$archiveCount = (int)($archiveCountResult->fetch_assoc()['c'] ?? 0);

echo "Archive table row count: {$archiveCount}\n";

if ($archiveCount < $legacyCount) {
    echo "❌ Safety check failed: archive row count is less than legacy row count. Aborting drop.\n";
    exit(1);
}

echo "Dropping legacy table '{$legacyTable}'...\n";
if (!$mysqli->query("DROP TABLE {$legacyTable}")) {
    echo "❌ Failed to drop legacy table: " . $mysqli->error . "\n";
    exit(1);
}

echo "✅ Legacy table dropped successfully.\n\n";
echo "Cleanup complete. Activity logging now relies on role-separated tables only.\n";
