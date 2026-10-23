<?php
// ============================================================
//  CONFIG - GetKey System
// ============================================================

define('DB_HOST', 'mysql-3155e277-bolaductrong164-000d.j.aivencloud.com');
define('DB_USER', 'avnadmin');
define('DB_PASS', 'AVNS_2bfFk9QgVOlKkLdPu8y');
define('DB_NAME', 'defaultdb');
define('DB_PORT', '12196');

define('SITE_URL',      'https://killcheat.onrender.com');
define('KEY_EXPIRE_H',  12);   // key sống bao nhiêu giờ

// Layma shortlink
define('LAYMA_TOKEN',   '2cddb02194b74ac4fbb46dfe153ebc41');
define('LAYMA_API',     'https://api.layma.net/api/admin/shortlink/quicklink');

// Trang callback sau khi vượt link xong
define('CALLBACK_URL',  SITE_URL . '/getkey/callback.php');

// ============================================================
//  Kết nối DB (MySQLi)
// ============================================================
function db(): mysqli {
    static $conn = null;
    if ($conn === null) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT);
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

// ============================================================
//  Khởi tạo bảng nếu chưa có
// ============================================================
function init_tables(): void {
    $db = db();
    $db->query("
        CREATE TABLE IF NOT EXISTS `keys` (
            `id`         INT AUTO_INCREMENT PRIMARY KEY,
            `app_id`     VARCHAR(64)  NOT NULL,
            `key_value`  VARCHAR(64)  NOT NULL UNIQUE,
            `hwid`       VARCHAR(128) NOT NULL,
            `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `expires_at` DATETIME     NOT NULL,
            INDEX idx_app_hwid (`app_id`, `hwid`),
            INDEX idx_key     (`key_value`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $db->query("
        CREATE TABLE IF NOT EXISTS `pending_sessions` (
            `token`      VARCHAR(64)  PRIMARY KEY,
            `app_id`     VARCHAR(64)  NOT NULL,
            `hwid`       VARCHAR(128) NOT NULL,
            `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $db->query("
        CREATE TABLE IF NOT EXISTS `apps` (
            `app_id`   VARCHAR(64)  PRIMARY KEY,
            `app_name` VARCHAR(128) NOT NULL,
            `active`   TINYINT(1)   NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    // Seed app mặc định
    $db->query("
        INSERT IGNORE INTO `apps` (`app_id`, `app_name`) VALUES ('kill_cheat', 'Kill Cheat')
    ");
}

session_start();
init_tables();
