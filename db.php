<?php

// Check if setup has run (config.php exists)
if (!file_exists(__DIR__ . '/config.php')) {
    // If we are not already on setup.php, redirect (only if not CLI)
    if (PHP_SAPI !== 'cli' && basename($_SERVER['PHP_SELF'] ?? '') != 'setup.php') {
        header("Location: setup.php");
        exit;
    }
}

// Only include config if it exists to avoid errors on the setup page itself if included there
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

class Database {
    private $pdo;
    private $error;

    public function __construct() {
        // If credentials are defined (from config.php)
        if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER')) {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ];

            try {
                $this->pdo = new PDO($dsn, DB_USER, defined('DB_PASS') ? DB_PASS : '', $options);
            } catch (PDOException $e) {
                $this->error = $e->getMessage();
                // If connection fails, it might be that config is stale or DB dropped.
                // We'll die with error, user might need to delete config.php to reset.
                die("Database Connection Error: " . $this->error . " <br><a href='setup.php'>Re-run Setup</a> (Note: You may need to delete config.php manually if credentials changed)");
            }
        }
    }

    public function query($sql) {
        if ($this->pdo) {
            return $this->pdo->prepare($sql);
        }
        return null;
    }
    
    public function getPdo() {
        return $this->pdo;
    }
    
     public function prepare($sql) {
        if ($this->pdo) {
            return $this->pdo->prepare($sql);
        }
        return null;
    }
    
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    public function commit() {
        return $this->pdo->commit();
    }
    
    public function rollBack() {
        return $this->pdo->rollBack();
    }
}

// Global helper
// Don't instantiate if we are ON the setup page and just including this file for class def (though setup doesn't strictly need this file)
// Better: Only instantiate if config exists.
if (file_exists(__DIR__ . '/config.php')) {
    try {
        $db = new Database();
        $pdo = $db->getPdo();
        
        // Fetch Global Settings
        $settingsStmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
        $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);
        
        // Default values if settings table empty
        if (!$settings) {
            $settings = [
                'church_name' => 'Apostolic Faith WECA',
                'dept_name' => 'ICT Department',
                'app_name' => 'Tasks Manager'
            ];
        }

        // Schema Self-Healing (Auto-Migration)
        // Check for 'status' in 'funds'
        try {
            $pdo->query("SELECT status FROM funds LIMIT 1");
        } catch (Exception $e) {
            $pdo->exec("ALTER TABLE `funds` ADD COLUMN `status` ENUM('Active', 'Retired') DEFAULT 'Active' AFTER `description` ");
            $pdo->exec("UPDATE `funds` SET `status` = 'Active' WHERE `status` IS NULL OR `status` = '' ");
        }
        // Check for 'assigned_to' in 'tasks'
        try {
            $pdo->query("SELECT assigned_to FROM tasks LIMIT 1");
        } catch (Exception $e) {
            $pdo->exec("ALTER TABLE `tasks` ADD COLUMN `assigned_to` VARCHAR(255) DEFAULT NULL AFTER `description` ");
        }

    } catch (Exception $e) {
        die("Fatal Error: " . $e->getMessage());
    }
}
