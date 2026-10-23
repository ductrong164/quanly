<?php
require_once __DIR__ . '/config.php';

$token = trim($_GET['token'] ?? '');

if ($token === '') {
    http_response_code(400);
    header('Location: index.php?err=no_token');
    exit;
}

$stmt = db()->prepare("SELECT app_id, device_hash FROM getkey_sessions WHERE session_token = ? LIMIT 1");
$stmt->bind_param('s', $token);
$stmt->execute();
$session = $stmt->get_result()->fetch_assoc();

if (!$session) {
    header('Location: index.php?err=invalid_token');
    exit;
}

$app_id = $session['app_id'];
$hwid   = $session['device_hash'];

$del = db()->prepare("DELETE FROM getkey_sessions WHERE session_token = ?");
$del->bind_param('s', $token);
$del->execute();

$chk = db()->prepare("SELECT key_value FROM issued_keys WHERE app_id = ? AND device_hash = ? AND expires_at > NOW() ORDER BY expires_at DESC LIMIT 1");
$chk->bind_param('ss', $app_id, $hwid);
$chk->execute();
$exist = $chk->get_result()->fetch_assoc();

if ($exist) {
    header('Location: done.php?key=' . urlencode($exist['key_value']) . '&cached=1');
    exit;
}

$key_value = strtoupper(bin2hex(random_bytes(10)));
$expires_at = date('Y-m-d H:i:s', time() + KEY_EXPIRE_H * 3600);

$ins = db()->prepare("INSERT INTO issued_keys (app_id, key_value, device_hash, expires_at) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE expires_at = VALUES(expires_at)");
$ins->bind_param('ssss', $app_id, $key_value, $hwid, $expires_at);
$ins->execute();

header('Location: done.php?key=' . urlencode($key_value));
exit;
