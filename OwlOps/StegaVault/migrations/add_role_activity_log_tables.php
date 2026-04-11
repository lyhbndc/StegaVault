<?php
/**
 * StegaVault - Database Migration for Role-Separated Activity Logs
 * File: migrations/add_role_activity_log_tables.php
 */

require_once __DIR__ . '/../includes/db.php';

echo "StegaVault - Role Activity Log Migration\n";
echo "======================================\n\n";

$mysqli = $db->getConnection();

$tables = [
    'activity_log_admin',
    'activity_log_employee',
    'activity_log_collaborator'
];

foreach ($tables as $table) {
    $sql = "
    CREATE TABLE IF NOT EXISTS {$table} (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action VARCHAR(100) NOT NULL,
        description TEXT,
        ip_address VARCHAR(45) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_action (action),
        INDEX idx_created_at (created_at),
        CONSTRAINT fk_{$table}_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";

    echo "Creating {$table}...\n";
    if ($mysqli->query($sql)) {
        echo "✅ {$table} ready\n\n";
    } else {
        echo "❌ Failed to create {$table}: " . $mysqli->error . "\n\n";
        exit(1);
    }
}

$copySql = [
    "INSERT INTO activity_log_admin (user_id, action, description, ip_address, created_at)
     SELECT al.user_id, al.action, al.description, al.ip_address, al.created_at
     FROM activity_log al
     INNER JOIN users u ON u.id = al.user_id
     WHERE u.role IN ('admin', 'super_admin')
       AND NOT EXISTS (
            SELECT 1 FROM activity_log_admin a
            WHERE a.user_id = al.user_id
              AND a.action = al.action
              AND COALESCE(a.description, '') = COALESCE(al.description, '')
              AND COALESCE(a.ip_address, '') = COALESCE(al.ip_address, '')
              AND a.created_at = al.created_at
       )",

    "INSERT INTO activity_log_employee (user_id, action, description, ip_address, created_at)
     SELECT al.user_id, al.action, al.description, al.ip_address, al.created_at
     FROM activity_log al
     INNER JOIN users u ON u.id = al.user_id
     WHERE u.role = 'employee'
       AND NOT EXISTS (
            SELECT 1 FROM activity_log_employee a
            WHERE a.user_id = al.user_id
              AND a.action = al.action
              AND COALESCE(a.description, '') = COALESCE(al.description, '')
              AND COALESCE(a.ip_address, '') = COALESCE(al.ip_address, '')
              AND a.created_at = al.created_at
       )",

    "INSERT INTO activity_log_collaborator (user_id, action, description, ip_address, created_at)
     SELECT al.user_id, al.action, al.description, al.ip_address, al.created_at
     FROM activity_log al
     INNER JOIN users u ON u.id = al.user_id
     WHERE u.role = 'collaborator'
       AND NOT EXISTS (
            SELECT 1 FROM activity_log_collaborator a
            WHERE a.user_id = al.user_id
              AND a.action = al.action
              AND COALESCE(a.description, '') = COALESCE(al.description, '')
              AND COALESCE(a.ip_address, '') = COALESCE(al.ip_address, '')
              AND a.created_at = al.created_at
       )"
];

echo "Backfilling existing activity_log records...\n";
foreach ($copySql as $sql) {
    if (!$mysqli->query($sql)) {
        echo "⚠️ Backfill warning: " . $mysqli->error . "\n";
    }
}

$adminCount = $mysqli->query("SELECT COUNT(*) AS c FROM activity_log_admin")->fetch_assoc()['c'] ?? 0;
$employeeCount = $mysqli->query("SELECT COUNT(*) AS c FROM activity_log_employee")->fetch_assoc()['c'] ?? 0;
$collaboratorCount = $mysqli->query("SELECT COUNT(*) AS c FROM activity_log_collaborator")->fetch_assoc()['c'] ?? 0;

echo "\nMigration completed!\n";
echo "Admin logs: {$adminCount}\n";
echo "Employee logs: {$employeeCount}\n";
echo "Collaborator logs: {$collaboratorCount}\n";

echo "\nNext step: update APIs/pages to use role-specific tables.\n";
