<?php
require_once __DIR__ . '/../includes/db.php';

echo "--- Database Column Check ---\n";
try {
    $res = $db->query("SELECT column_name, data_type 
                       FROM information_schema.columns 
                       WHERE table_name = 'projects' 
                       AND table_schema = 'public'");
    $cols = $res->fetch_all();
    if (empty($cols)) {
        echo "No columns found for 'projects' table in public schema.\n";
    } else {
        foreach ($cols as $col) {
            echo "Column: " . $col['column_name'] . " (" . $col['data_type'] . ")\n";
        }
    }

    echo "\n--- Testing Update Query ---\n";
    $testId = 1; // Assuming ID 1 exists
    $newStatus = 'active';
    $stmt = $db->prepare("UPDATE projects SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $newStatus, $testId);
    if ($stmt->execute()) {
        echo "Update query executed successfully.\n";
        // Check rows affected
        $conn = $db->getConnection();
        // Since it's PDO via shim, rowCount() is in the statement object shim
        // but we can just check if it worked.
    } else {
        echo "Update query FAILED: " . $db->error . "\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
