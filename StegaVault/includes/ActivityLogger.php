<?php

/**
 * StegaVault - Activity Logger (Role-Separated Tables)
 * File: includes/ActivityLogger.php
 */

if (!function_exists('getRoleActivityTable')) {
    function getRoleActivityTable(string $role): string
    {
        return match (strtolower(trim($role))) {
            'admin', 'super_admin' => 'activity_log_admin',
            'collaborator' => 'activity_log_collaborator',
            default => 'activity_log_employee',
        };
    }
}

if (!function_exists('getUserRoleForActivityLog')) {
    function getUserRoleForActivityLog($db, int $userId): string
    {
        if ($userId <= 0) {
            return 'employee';
        }

        $stmt = $db->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
        if (!$stmt) {
            return 'employee';
        }

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        return $row['role'] ?? 'employee';
    }
}

if (!function_exists('insertActivityRow')) {
    function insertActivityRow($db, string $tableName, int $userId, string $action, string $description, ?string $ipAddress = null): bool
    {
        $allowedTables = [
            'activity_log_admin',
            'activity_log_employee',
            'activity_log_collaborator',
            'activity_log'
        ];

        if (!in_array($tableName, $allowedTables, true)) {
            return false;
        }

        $sql = "INSERT INTO {$tableName} (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('isss', $userId, $action, $description, $ipAddress);
        return $stmt->execute();
    }
}

if (!function_exists('logActivityEvent')) {
    function logActivityEvent($db, int $userId, string $action, string $description, ?string $ipAddress = null, ?string $role = null, bool $writeLegacy = false): bool
    {
        $resolvedRole = $role ?: getUserRoleForActivityLog($db, $userId);
        $roleTable = getRoleActivityTable($resolvedRole);

        $ok = insertActivityRow($db, $roleTable, $userId, $action, $description, $ipAddress);

        if ($writeLegacy) {
            insertActivityRow($db, 'activity_log', $userId, $action, $description, $ipAddress);
        }

        return $ok;
    }
}
