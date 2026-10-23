<?php
require_once __DIR__ . '/config.php';

$token = trim($_GET['token'] ?? '');

if ($token === '') {
    http_response_code(400);
    header('Location: index.php?err=no_token');
    exit;
}

// ── Lấy pending session ───────────────────────────────────────
$stmt = db()->prepare("
    SELECT app_id, hwid, created_at FROM pending_sessions
    WHERE token=? LIMIT 1
");
$stmt->bind_param('s', $token);
$stmt->execute();
$session = $stmt->get_result()->fetch_assoc();

if (!$session) {
    header('Location: index.php?err=invalid_token');
    exit;
}

// Token chỉ dùng 1 lần – xoá ngay
db()->prepare("DELETE FROM pending_sessions WHERE token=?")->execute([$token]) ;
// (dùng execute() array syntax yêu cầu PHP 8.1+ hoặc dùng bind_param)
$del = db()->prepare("DELETE FROM pending_sessions WHERE token=?");
$del->bind_param('s', $token);
$del->execute();

$app_id = $session['app_id'];
$hwid   = $session['hwid'];

// ── Kiểm HWID đã có key còn hạn không (race condition guard) ──
$chk = db()->prepare("
    SELECT key_value FROM `keys`
    WHERE app_id=? AND hwid=? AND expires_at > NOW()
    ORDER BY expires_at DESC LIMIT 1
");
$chk->bind_param('ss', $app_id, $hwid);
$chk->execute();
$exist = $chk->get_result()->fetch_assoc();

if ($exist) {
    header('Location: done.php?key=' . urlencode($exist['key_value']) . '&cached=1');
    exit;
}

// ── Tạo key mới ───────────────────────────────────────────────
$key_value  = strtoupper(bin2hex(random_bytes(10)));   // 20 ký tự hex
$expires_at = date('Y-m-d H:i:s', time() + KEY_EXPIRE_H * 3600);

$ins = db()->prepare("
    INSERT INTO `keys` (app_id, key_value, hwid, expires_at)
    VALUES (?,?,?,?)
    ON DUPLICATE KEY UPDATE expires_at=VALUES(expires_at)
");
$ins->bind_param('ssss', $app_id, $key_value, $hwid, $expires_at);
$ins->execute();

header('Location: done.php?key=' . urlencode($key_value));
exit;
