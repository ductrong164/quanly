<?php
// Tắt hiển thị lỗi ra ngoài trong môi trường production
error_reporting(0);
ini_set('display_errors', 0);

// Secure Session Configuration (Must be before session_start)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}

// Encryption Constants (Used in frontend scripts)
if (!defined('ENCRYPTION_KEY')) {
    define('ENCRYPTION_KEY', 'tgm_secure_default_key_32_bytes_len');
}
if (!defined('ENCRYPTION_IV_LEN')) {
    define('ENCRYPTION_IV_LEN', 16);
}

// Google OAuth 2.0 Configuration
define('GOOGLE_CLIENT_ID', '846171090786-efpel3bvdrqq7vp5dmv1p2jonjha4c9s.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
// We dynamically generate the redirect URL based on the current domain
define('GOOGLE_REDIRECT_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/api/google_callback.php");

// CSRF Protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function checkCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return;
    }
    $token = trim($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (empty($token)) {
        jsonResponse('error', 'Yêu cầu thiếu mã bảo mật (CSRF token)');
    }
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        jsonResponse('error', 'Cảnh báo bảo mật: Token không hợp lệ. Vui lòng tải lại trang.');
    }
}

// ========== KẾT NỐI DATABASE ==========
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'taixiu_db';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Lỗi kết nối cơ sở dữ liệu']));
}
$conn->set_charset("utf8mb4");

// ========== HÀM PHẢN HỒI JSON ==========
function jsonResponse($status, $message, $data = []) {
    // Thêm security headers
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    $output = ['status' => $status, 'message' => $message, 'data' => $data];
    echo json_encode($output);
    exit;
}

// ========== BẢO VỆ API KHỎI TRUY CẬP TRỰC TIẾP ==========
// Ngăn chặn request trực tiếp từ bên ngoài, không phải từ website
function requireApiAccess() {
    // Kiểm tra nếu request từ browser trực tiếp (không phải AJAX)
    $isXhr     = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                 && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    $hasOrigin = !empty($_SERVER['HTTP_ORIGIN']);
    $hasReferer = !empty($_SERVER['HTTP_REFERER']);

    // Cho phép nếu là AJAX, hoặc có Referer/Origin hợp lệ, hoặc là CLI
    if (php_sapi_name() === 'cli') return;

    $site_host = explode(':', $_SERVER['HTTP_HOST'] ?? '')[0];

    if ($hasReferer) {
        $referer_host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
        if ($referer_host !== $site_host) {
            jsonResponse('error', 'Truy cập không được phép');
        }
    }

    if ($hasOrigin) {
        $origin_host = parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_HOST);
        if ($origin_host !== $site_host) {
            header('HTTP/1.1 403 Forbidden');
            jsonResponse('error', 'Truy cập không được phép');
        }
    }
}

// ========== RATE LIMITING CHUNG CHO API ==========
function rateLimitApi($key, $limit = 30, $window = 60) {
    $rate_key = 'rate_' . $key . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    if (!isset($_SESSION[$rate_key])) {
        $_SESSION[$rate_key] = ['count' => 0, 'reset' => time() + $window];
    }
    if (time() > $_SESSION[$rate_key]['reset']) {
        $_SESSION[$rate_key] = ['count' => 0, 'reset' => time() + $window];
    }
    $_SESSION[$rate_key]['count']++;
    if ($_SESSION[$rate_key]['count'] > $limit) {
        jsonResponse('error', 'Bạn đang thao tác quá nhanh. Vui lòng thử lại sau.');
    }
}

// ========== KIỂM TRA ĐĂNG NHẬP ==========
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// ========== LẤY THÔNG TIN USER THEO ID ==========
function getUserById($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT id, username, password, balance, avatar, nickname, email, is_email_verified, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// ========== LOG LOGIN HISTORY ==========
function logUserLogin($user_id) {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    // Đơn giản hóa OS Detection
    $os = 'Unknown OS';
    if (preg_match('/windows|win32/i', $ua)) {
        $os = 'Windows';
    } elseif (preg_match('/macintosh|mac os x/i', $ua)) {
        $os = 'Mac OS';
    } elseif (preg_match('/linux/i', $ua)) {
        $os = 'Linux';
    } elseif (preg_match('/android/i', $ua)) {
        $os = 'Android';
    } elseif (preg_match('/iphone|ipad|ipod/i', $ua)) {
        $os = 'iOS';
    }

    $stmt = $conn->prepare("INSERT INTO login_history (user_id, ip_address, user_agent, os_info) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $ip, $ua, $os);
    $stmt->execute();
}

// ========== MAPPING ENDPOINT CHO TỪNG GAME ==========
$GAME_API_MAP = [
    'hitclub' => [
        'taixiu_hu'  => 'http://160.250.137.251:8002/api/dudoan/taixiu',
        'taixiu_md5' => 'http://160.250.137.251:8002/api/dudoan/md5',
        'sicbo'      => 'http://160.250.137.251:5000/sicbo/hitclub'
    ],
    'b52' => [
        'taixiu_hu'  => 'http://160.250.137.251:3535/api/dudoan/taixiu',
        'taixiu_md5' => 'http://160.250.137.251:3535/api/dudoan/md5',
        'sicbo'      => 'http://160.250.137.251:5000/sicbo/b52'
    ],
    'rikvip' => [
        'taixiu_hu' => 'http://160.250.137.251:3636/api/dudoan/taixiu'
    ],
    'sumclub' => [
        'taixiu_hu'  => 'http://160.250.137.251:1000/dudoan/txhu',
        'taixiu_md5' => 'http://160.250.137.251:1000/dudoan/txmd5',
        'sicbo'      => 'http://160.250.137.251:5000/sicbo/sumclub'
    ],
    'lc79' => [
        'taixiu_hu'  => 'http://160.250.137.251:3010/api/dudoan/taixiu',
        'taixiu_md5' => 'http://160.250.137.251:3010/api/dudoan/md5'
    ],
    '68gb' => [
        'taixiu_md5' => 'http://160.250.137.251:9020/dudoan/txmd5',
        'taixiu_hu'  => 'http://160.250.137.251:9020/dudoan/txxanh'
    ],
    'luck8' => [
        'taixiu_hu'  => 'http://160.250.137.251:9000/dudoan/txhu',
        'taixiu_md5' => 'http://160.250.137.251:9000/dudoan/txmd5',
        'taixiu_3p'  => 'http://160.250.137.251:9000/dudoan/tx3p',
        'sicbo'      => 'http://160.250.137.251:5000/sicbo/luckywin'
    ],
    'xocdia88' => [
        'taixiu_hu'  => 'http://160.250.137.251:4001/api/dudoan/taixiu',
        'taixiu_md5' => 'http://160.250.137.251:4001/api/dudoan/md5'
    ],
    'betvip' => [
        'taixiu_hu'  => 'http://160.250.137.251:6767/api/dudoan/taixiu',
        'taixiu_md5' => 'http://160.250.137.251:6767/api/dudoan/md5'
    ],
    'sunwin' => [
        'taixiu' => 'http://160.250.137.251:3001/api/sunwin',
        'sicbo'  => 'http://160.250.137.251:5000/sicbo/sunwin'
    ]
];

// ========== KIỂM TRA BẢO TRÌ ==========
$isCli = (php_sapi_name() === 'cli');
if (!$isCli) {
    $configSettingsFile = dirname(__DIR__) . '/admin/settings.json';
    if (file_exists($configSettingsFile)) {
        $configSettings = json_decode(file_get_contents($configSettingsFile), true) ?: [];
        if (!empty($configSettings['maintenance_mode'])) {
            $isConfigAdmin = isset($_SESSION['admin_id']);
            $configBypass  = $_POST['bypass'] ?? '';
            $currentUri    = $_SERVER['REQUEST_URI'] ?? '';
            $isAdminPage   = (strpos($currentUri, '/admin/') !== false);

            if (!$isConfigAdmin && $configBypass !== 'toolgameai_admin' && !$isAdminPage) {
                $maintenanceMsg = $configSettings['maintenance_message'] ?? 'Hệ thống đang bảo trì!';
                if (
                    (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ||
                    (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) ||
                    (strpos($currentUri, '/api/') !== false)
                ) {
                    jsonResponse('error', 'BẢO TRÌ: ' . $maintenanceMsg);
                } else {
                    $maintenancePath = dirname(__DIR__) . '/maintenance.php';
                    if (file_exists($maintenancePath)) {
                        include $maintenancePath;
                    } else {
                        echo '<h3>' . htmlspecialchars($maintenanceMsg) . '</h3>';
                    }
                    exit;
                }
            }
        }
    }
}
?>
