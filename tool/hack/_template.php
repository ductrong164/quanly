<?php
/**
 * File xác thực tập trung cho tất cả tool game.
 * Yêu cầu: đã định nghĩa hằng số GAME_NAME trước khi include.
 * Nếu chưa đăng nhập, chưa có fingerprint hoặc key không active -> chặn truy cập.
 */

// Đảm bảo không thể truy cập trực tiếp
if (!defined('GAME_NAME')) {
    header('HTTP/1.0 403 Forbidden');
    die('Access denied');
}

session_start();
require_once __DIR__ . '/../../api/config.php';

// 1. Kiểm tra đăng nhập
if (!isLoggedIn()) {
    header('Location: /index.php?error=login');
    exit;
}

// 2. Lấy fingerprint từ cookie (do JS đặt)
$fingerprint = $_COOKIE['tgm_fingerprint'] ?? '';
if (empty($fingerprint)) {
    header('Location: /index.php?error=fingerprint');
    exit;
}

$user_id = $_SESSION['user_id'];

// 3. Kiểm tra key active cho fingerprint này
$stmt = $conn->prepare("
    SELECT key_code, expires_at 
    FROM user_keys 
    WHERE user_id = ? AND fingerprint = ? AND expires_at > NOW()
    LIMIT 1
");
$stmt->bind_param("is", $user_id, $fingerprint);
$stmt->execute();
$active = $stmt->get_result()->fetch_assoc();

if (!$active) {
    // Không có key hoặc key hết hạn / sai fingerprint
    header('Location: /index.php?error=no_active_key');
    exit;
}

// Lưu thông tin key vào hằng số để các tool có thể dùng nếu cần
define('ACTIVE_KEY', $active['key_code']);
define('KEY_EXPIRY', $active['expires_at']);

// Mọi thứ hợp lệ, cho phép hiển thị tool
$userData = [
    'username' => $_SESSION['username'] ?? 'Khách',
    'user_token' => $_SESSION['user_token'] ?? '',
    'session_id' => session_id(),
    'csrf_token' => $_SESSION['csrf_token'] ?? '',
    'time' => time()
];
$securePayload = 'TGAI_' . base64_encode(json_encode($userData));
?>
<style>.swal2-container { z-index: 999999 !important; }</style>
<script src="/assets/js/algorithm.js"></script>
<script>
window.SECURE_PAYLOAD = "<?= $securePayload ?>";
window.GAME_ID = "<?= GAME_NAME ?>";
</script>
