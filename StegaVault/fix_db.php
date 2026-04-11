<?php
echo "Starting database fix...\n";
require_once 'includes/db.php';

// Check connection
if ($db->getConnection()->connect_error) {
    die("Connection failed: " . $db->getConnection()->connect_error);
}

echo "Connected to database.\n";

// 1. Projects Table
$check = $db->query("SHOW TABLES LIKE 'projects'");
if ($check->num_rows == 0) {
    echo "Creating 'projects' table...\n";
    $sql = "CREATE TABLE projects (
      id int(11) NOT NULL AUTO_INCREMENT,
      name varchar(255) NOT NULL,
      description text DEFAULT NULL,
      color varchar(7) DEFAULT '#6366f1',
      created_by int(11) DEFAULT NULL,
      created_at timestamp NOT NULL DEFAULT current_timestamp(),
      updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      status enum('active','archived','completed') DEFAULT 'active',
      PRIMARY KEY (id),
      KEY idx_status (status),
      KEY idx_created_by (created_by)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($db->query($sql)) {
        echo "✅ 'projects' table created.\n";
    } else {
        echo "❌ Error creating 'projects': " . $db->getConnection()->error . "\n";
    }
} else {
    echo "✅ 'projects' table already exists.\n";
}

// 2. Project Members
$check = $db->query("SHOW TABLES LIKE 'project_members'");
if ($check->num_rows == 0) {
    echo "Creating 'project_members' table...\n";
    $sql = "CREATE TABLE project_members (
      id int(11) NOT NULL AUTO_INCREMENT,
      project_id int(11) NOT NULL,
      user_id int(11) NOT NULL,
      role varchar(20) DEFAULT 'member',
      joined_at timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (id),
      UNIQUE KEY unique_member (project_id, user_id),
      KEY idx_project_id (project_id),
      KEY idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($db->query($sql)) {
        echo "✅ 'project_members' table created.\n";
    } else {
        echo "❌ Error creating 'project_members': " . $db->getConnection()->error . "\n";
    }
} else {
    echo "✅ 'project_members' table already exists.\n";
}

// 3. Files Column
$res = $db->query("SHOW COLUMNS FROM files LIKE 'project_id'");
if ($res->num_rows == 0) {
    echo "Adding 'project_id' to files...\n";
    if ($db->query("ALTER TABLE files ADD COLUMN project_id INT DEFAULT NULL")) {
         echo "✅ Added 'project_id' column to files.\n";
         $db->query("ALTER TABLE files ADD INDEX idx_files_project (project_id)");
    } else {
         echo "❌ Error adding column: " . $db->getConnection()->error . "\n";
    }
} else {
    echo "✅ 'files' table has 'project_id'.\n";
}

echo "Database fix completed.\n";
?>
