<?php

/**
 * StegaVault - Database Connection (Supabase PostgreSQL via PDO)
 * File: includes/db.php
 * 
 * NOTE: This file includes a shim to support legacy mysqli function calls
 * (bind_param, get_result, etc.) while using PDO and PostgreSQL.
 */

// Define MySQLi constants if they don't exist
if (!defined('MYSQLI_ASSOC'))
    define('MYSQLI_ASSOC', 1);
if (!defined('MYSQLI_NUM'))
    define('MYSQLI_NUM', 2);
if (!defined('MYSQLI_BOTH'))
    define('MYSQLI_BOTH', 3);

require_once __DIR__ . '/config.php';

class Database
{
    private $conn = null;
    public $error = '';

    public function __construct()
    {
        try {
            $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";sslmode=require";
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 5,
            ]);
        }
        catch (PDOException $e) {
            die(json_encode([
                'success' => false,
                'error' => 'DB_ERROR: ' . $e->getMessage(),
                'details' => $e->getMessage()
            ]));
        }
    }

    public function getConnection()
    {
        return $this->conn;
    }

    public function query($sql)
    {
        try {
            $stmt = $this->conn->query($sql);
            $this->error = '';
            return new DatabaseStatement($stmt, $this);
        }
        catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Query error: " . $e->getMessage() . " | SQL: " . $sql);
            return false;
        }
    }

    public function prepare($sql)
    {
        try {
            $stmt = $this->conn->prepare($sql);
            $this->error = '';
            return new DatabaseStatement($stmt, $this);
        }
        catch (PDOException $e) {
            $this->error = $e->getMessage();
            error_log("Prepare error: " . $e->getMessage() . " | SQL: " . $sql);
            return false;
        }
    }

    public function escape($value)
    {
        return addslashes((string)$value);
    }

    public function lastInsertId()
    {
        return $this->conn->lastInsertId();
    }

    public function close()
    {
        $this->conn = null;
    }

    public function __destruct()
    {
        $this->close();
    }
}

/**
 * Shim class to make PDOStatement act like mysqli_stmt
 */
class DatabaseStatement
{
    private $stmt;
    private $db;
    private $params = [];

    public function __construct($stmt, $db)
    {
        $this->stmt = $stmt;
        $this->db = $db;
    }

    // Shim for mysqli_stmt::bind_param
    public function bind_param($types, ...$vars)
    {
        $this->params = $vars;
        return true;
    }

    // Shim for mysqli_stmt::execute
    public function execute($params = null)
    {
        try {
            $result = $this->stmt->execute($params ?? $this->params);
            if (!$result) {
                $info = $this->stmt->errorInfo();
                $this->db->error = $info[2] ?? 'Unknown execution error';
                error_log("Execute failed: " . $this->db->error);
            }
            return $result;
        }
        catch (PDOException $e) {
            $this->db->error = $e->getMessage();
            error_log("Execute exception: " . $this->db->error);
            return false;
        }
    }

    // Shim for mysqli_stmt::get_result
    public function get_result()
    {
        return $this;
    }

    // Shim for mysqli_result::fetch_assoc
    public function fetch_assoc()
    {
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Shim for mysqli_result::fetch_row
    public function fetch_row()
    {
        $row = $this->stmt->fetch(PDO::FETCH_NUM);
        return $row ?: null;
    }

    // Proxy for mysqli_result::num_rows and mysqli_stmt::num_rows
    public function __get($name)
    {
        switch ($name) {
            case 'num_rows':
                return $this->stmt->rowCount();
            case 'insert_id':
                return $this->db->lastInsertId();
            case 'error':
                $info = $this->stmt->errorInfo();
                return $info[2] ?? '';
            default:
                return null;
        }
    }

    public function fetch_all($mode = MYSQLI_ASSOC)
    {
        $pdoMode = PDO::FETCH_ASSOC;
        if ($mode === MYSQLI_NUM)
            $pdoMode = PDO::FETCH_NUM;
        if ($mode === MYSQLI_BOTH)
            $pdoMode = PDO::FETCH_BOTH;

        return $this->stmt->fetchAll($pdoMode);
    }
}

// Create global database instance
try {
    $db = new Database();
}
catch (Exception $e) {
    die(json_encode([
        'success' => false,
        'error' => 'Failed to initialize database: ' . $e->getMessage()
    ]));
} 