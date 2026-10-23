<?php
require_once __DIR__ . '/config.php';

$app_id = trim($_POST['app_id'] ?? '');
$hwid   = trim($_POST['hwid'] ?? '');

if ($app_id === '' || $hwid === '') {
    header('Location: index.php?err=missing');
    exit;
}

$stmt = db()->prepare("SELECT app_id FROM apps WHERE app_id = ? AND status = 'active' LIMIT 1");
$stmt->bind_param('s', $app_id);
$stmt->execute();

if ($stmt->get_result()->num_rows === 0) {
    header('Location: index.php?err=invalid_app');
    exit;
}

$stmt2 = db()->prepare("SELECT key_value, expires_at FROM issued_keys WHERE app_id = ? AND device_hash = ? AND expires_at > NOW() ORDER BY expires_at DESC LIMIT 1");
$stmt2->bind_param('ss', $app_id, $hwid);
$stmt2->execute();
$existing = $stmt2->get_result()->fetch_assoc();

if ($existing) {
    header('Location: done.php?key=' . urlencode($existing['key_value']) . '&cached=1');
    exit;
}

$token = bin2hex(random_bytes(24));
$callback = CALLBACK_URL . '?token=' . urlencode($token);
$backup = SITE_URL . 'index.php';

$short = null;

if (function_exists('curl_init')) {
    try {
        $layma_url = LAYMA_API
            . '?tokenUser=' . urlencode(LAYMA_TOKEN)
            . '&format=text'
            . '&url=' . urlencode($callback)
            . '&link_du_phong=' . urlencode($backup);

        $ch = curl_init($layma_url);
        if ($ch === false) {
            throw new RuntimeException('curl_init failed');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response !== false && $httpCode === 200) {
            $short = trim((string) $response);
        }
    } catch (Throwable $e) {
        $short = null;
    }
}

if (!$short || !preg_match('#^https?://#i', $short)) {
    // Fallback: nếu Layma không hoạt động, vẫn cho user tiếp tục lấy key
    header('Location: callback.php?token=' . urlencode($token));
    exit;
}

$expires_at = date('Y-m-d H:i:s', time() + 12 * 3600);
$db = db();
$stmt3 = $db->prepare("INSERT INTO getkey_sessions (session_token, app_id, device_hash, duration_h, step, link1, expires_at) VALUES (?, ?, ?, 12, 0, ?, ?)");
$stmt3->bind_param('sssss', $token, $app_id, $hwid, $short, $expires_at);
$stmt3->execute();

header('Location: ' . $short);
exit;
