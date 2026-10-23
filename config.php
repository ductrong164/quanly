<?php
// ============================================================
//  CONFIG - GetKey System
// ============================================================

define('DB_HOST', 'mysql-3155e277-bolaductrong164-000d.j.aivencloud.com');
define('DB_USER', 'avnadmin');
define('DB_PASS', 'AVNS_2bfFk9QgVOlKkLdPu8y');
define('DB_NAME', 'defaultdb');
define('DB_PORT', '12196');

define('SITE_URL', 'https://quanly-r5cj.onrender.com/');
define('KEY_EXPIRE_H', 12);

define('LAYMA_TOKEN', '2cddb02194b74ac4fbb46dfe153ebc41');
define('LAYMA_API', 'https://api.layma.net/api/admin/shortlink/quicklink');
define('CALLBACK_URL', SITE_URL . 'callback.php');

function db(): mysqli {
    static $conn = null;

    if ($conn === null) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int) DB_PORT);
        $conn->set_charset('utf8mb4');
    }

    return $conn;
}

function init_tables(): void {
    $db = db();

    $db->query("CREATE TABLE IF NOT EXISTS `admins` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(64) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->query("CREATE TABLE IF NOT EXISTS `apps` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `app_id` VARCHAR(64) NOT NULL UNIQUE,
        `app_name` VARCHAR(128) NOT NULL,
        `app_secret` VARCHAR(128) NOT NULL,
        `status` ENUM('active','paused','maintenance') DEFAULT 'active',
        `version` VARCHAR(32) DEFAULT '1.0.0',
        `update_url` VARCHAR(512) DEFAULT NULL,
        `force_update` TINYINT(1) DEFAULT 0,
        `announcement` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->query("CREATE TABLE IF NOT EXISTS `issued_keys` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `app_id` VARCHAR(64) NOT NULL,
        `key_value` VARCHAR(128) NOT NULL UNIQUE,
        `duration_h` INT NOT NULL DEFAULT 12,
        `device_hash` VARCHAR(64) DEFAULT NULL,
        `ip_bound` VARCHAR(64) DEFAULT NULL,
        `is_banned` TINYINT(1) DEFAULT 0,
        `ban_reason` VARCHAR(255) DEFAULT NULL,
        `used_at` TIMESTAMP NULL DEFAULT NULL,
        `expires_at` TIMESTAMP NULL DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_key_value` (`key_value`),
        INDEX `idx_app_id` (`app_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->query("CREATE TABLE IF NOT EXISTS `getkey_sessions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `session_token` VARCHAR(128) NOT NULL UNIQUE,
        `app_id` VARCHAR(64) NOT NULL,
        `device_hash` VARCHAR(64) DEFAULT NULL,
        `duration_h` INT NOT NULL DEFAULT 12,
        `step` INT DEFAULT 0,
        `link1` VARCHAR(512) DEFAULT NULL,
        `link2` VARCHAR(512) DEFAULT NULL,
        `completed` TINYINT(1) DEFAULT 0,
        `key_issued` VARCHAR(128) DEFAULT NULL,
        `ip` VARCHAR(64) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `expires_at` TIMESTAMP NULL DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->query("CREATE TABLE IF NOT EXISTS `notifications` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `app_id` VARCHAR(64) DEFAULT NULL,
        `title` VARCHAR(255) NOT NULL,
        `message` TEXT NOT NULL,
        `type` ENUM('info','warning','success','error') DEFAULT 'info',
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->query("CREATE TABLE IF NOT EXISTS `key_logs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `key_value` VARCHAR(128) NOT NULL,
        `app_id` VARCHAR(64) NOT NULL,
        `action` ENUM('created','verified','banned','unbanned','expired','getkey') NOT NULL,
        `ip` VARCHAR(64) DEFAULT NULL,
        `device_hash` VARCHAR(64) DEFAULT NULL,
        `note` VARCHAR(255) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->query("INSERT IGNORE INTO `admins` (`username`, `password`) VALUES ('admin', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')");
    $db->query("INSERT IGNORE INTO `apps` (`app_id`, `app_name`, `app_secret`, `status`) VALUES ('kill_cheat', 'Kill Cheat', 'kill_cheat_secret', 'active')");
}

session_start();
init_tables();
