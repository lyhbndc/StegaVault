<?php
/**
 * Super admin session timeout guard.
 * Include AFTER session_start() and the role check in every protected OwlOps page.
 */

define('SA_SESSION_TIMEOUT', 1800); // 30 minutes

if (isset($_SESSION['sa_last_activity']) && (time() - $_SESSION['sa_last_activity']) > SA_SESSION_TIMEOUT) {
    session_unset();
    session_destroy();
    header('Location: login.php?reason=timeout');
    exit;
}

$_SESSION['sa_last_activity'] = time();
