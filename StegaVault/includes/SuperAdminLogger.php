<?php
/**
 * StegaVault - Super Admin Audit Logger
 * File: includes/SuperAdminLogger.php
 */

class SuperAdminLogger
{
    /**
     * Log an audit event.
     *
     * @param string $action    e.g. 'login_success', 'backup_db_created'
     * @param string $category  'auth' | 'backup' | 'admin' | 'mfa'
     * @param array  $details   Any extra key/value pairs to store as JSON
     */
    public static function log(string $action, string $category, array $details = []): void
    {
        global $db;

        try {
            $adminId    = isset($_SESSION['user_id'])  ? (int) $_SESSION['user_id'] : null;
            $adminName  = $_SESSION['name']  ?? 'Unknown';
            $adminEmail = $_SESSION['email'] ?? 'Unknown';

            // Resolve real IP (handles common reverse-proxy headers)
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR']
                ?? $_SERVER['HTTP_X_REAL_IP']
                ?? $_SERVER['REMOTE_ADDR']
                ?? 'unknown';
            // Take only the first IP if comma-separated
            $ip = trim(explode(',', $ip)[0]);

            $detailsJson = json_encode($details, JSON_UNESCAPED_UNICODE);

            $stmt = $db->prepare(
                "INSERT INTO super_admin_audit_log
                    (super_admin_id, super_admin_name, super_admin_email, action, category, details, ip_address)
                 VALUES (?, ?, ?, ?, ?, ?::jsonb, ?)"
            );
            $stmt->bind_param('issssss',
                $adminId,
                $adminName,
                $adminEmail,
                $action,
                $category,
                $detailsJson,
                $ip
            );
            $stmt->execute();
        } catch (Exception $e) {
            // Never let logging break the main request
            error_log('[SuperAdminLogger] Failed to write audit log: ' . $e->getMessage());
        }
    }
}
