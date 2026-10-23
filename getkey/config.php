<?php
// ============================================================
//  KillAuth — Configuration (Fixed)
// ============================================================

define('DB_HOST', $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'mysql-3155e277-bolaductrong164-000d.j.aivencloud.com');
define('DB_PORT', $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '12196');
define('DB_USER', $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'avnadmin');
define('DB_PASS', $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: 'AVNS_2bfFk9QgVOlKkLdPu8y');
define('DB_NAME', $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'defaultdb');

define('SITE_URL', $_ENV['SITE_URL'] ?? getenv('SITE_URL') ?: 'https://killcheat.onrender.com');
define('SITE_NAME', 'KillAuth');
define('ADMIN_TOKEN', 'killauth_admin_' . md5('change_this_secret_2024'));

define('LAYMA_TOKEN', '2cddb02194b74ac4fbb46dfe153ebc41');
define('LAYMA_API', 'https://api.layma.net/api/admin/shortlink/quicklink');
define('BYPASS_HMAC_SECRET', 'killcheat_bypass_hmac_' . DB_PASS);

// ── GitHub Pages Bypass Redirector ──────────────────────────────────────────
// Đổi thành URL GitHub Pages của bạn sau khi deploy
define('GITHUB_BYPASS_URL', 'https://YOUR_USERNAME.github.io/killauth-bypass/');


function db(): PDO {
    static $pdo;
    if (!$pdo) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE              => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE   => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES     => false,
                PDO::ATTR_TIMEOUT              => 5,    // fail fast, không hang 30s
                PDO::MYSQL_ATTR_SSL_CA         => true, // Aiven yêu cầu SSL
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

function generateKey(): string {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $suffix = '';
    for ($i = 0; $i < 8; $i++) $suffix .= $chars[random_int(0, strlen($chars) - 1)];
    return 'killcheat_' . $suffix;
}

function generateToken(int $length = 32): string {
    return bin2hex(random_bytes($length));
}

function getClientIP(): string {
    foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) {
            $ip = trim(explode(',', $_SERVER[$k])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}

function getDeviceFingerprint(): string {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ip = getClientIP();
    return md5($ua . $ip);
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function isAdminLoggedIn(): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireAdmin(): void {
    if (!isAdminLoggedIn()) {
        header('Location: ' . rtrim(SITE_URL, '/') . '/admin/login.php');
        exit;
    }
}


function generateBypassSig(string $token): string {
    return hash_hmac('sha256', $token, BYPASS_HMAC_SECRET);
}

function verifyBypassSig(string $token, string $sig): bool {
    return hash_equals(generateBypassSig($token), $sig);
}

function shortenUrl(string $url, string $fallback): string {
    $apiUrl = LAYMA_API . '?tokenUser=' . LAYMA_TOKEN
            . '&format=json'
            . '&url=' . urlencode($url)
            . '&link_du_phong=' . urlencode($fallback);
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    if ($res) {
        $data = json_decode($res, true);
        if (!empty($data['shortLink'])) return $data['shortLink'];
        if (!empty($data['short_link'])) return $data['short_link'];
        if (!empty($data['data']['short_link'])) return $data['data']['short_link'];
    }
    return $fallback;
}
