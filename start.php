<?php
require_once __DIR__ . '/config.php';

// ── Validate input ────────────────────────────────────────────
$app_id = trim($_POST['app_id'] ?? '');
$hwid   = trim($_POST['hwid']   ?? '');

if ($app_id === '' || $hwid === '') {
    header('Location: index.php?err=missing');
    exit;
}

// Kiểm app tồn tại
$stmt = db()->prepare("SELECT app_id FROM apps WHERE app_id=? AND active=1 LIMIT 1");
$stmt->bind_param('s', $app_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    header('Location: index.php?err=invalid_app');
    exit;
}

// ── Kiểm HWID đã có key còn hạn chưa ────────────────────────
$stmt2 = db()->prepare("
    SELECT key_value, expires_at FROM `keys`
    WHERE app_id=? AND hwid=? AND expires_at > NOW()
    ORDER BY expires_at DESC LIMIT 1
");
$stmt2->bind_param('ss', $app_id, $hwid);
$stmt2->execute();
$existing = $stmt2->get_result()->fetch_assoc();

if ($existing) {
    // Redirect thẳng tới trang hiển thị key
    header('Location: done.php?key=' . urlencode($existing['key_value']) . '&cached=1');
    exit;
}

// ── Tạo session token ─────────────────────────────────────────
$token = bin2hex(random_bytes(24));

$stmt3 = db()->prepare("INSERT INTO pending_sessions (token, app_id, hwid) VALUES (?,?,?)");
$stmt3->bind_param('sss', $token, $app_id, $hwid);
$stmt3->execute();

// ── Tạo Layma shortlink ───────────────────────────────────────
$callback = CALLBACK_URL . '?token=' . urlencode($token);
$backup   = SITE_URL . '/getkey/index.php';

$layma_url = LAYMA_API
    . '?tokenUser=' . urlencode(LAYMA_TOKEN)
    . '&format=text'
    . '&url='           . urlencode($callback)
    . '&link_du_phong=' . urlencode($backup);

$ch = curl_init($layma_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$short = trim(curl_exec($ch));
$code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($code !== 200 || !str_starts_with($short, 'http')) {
    // Fallback: redirect thẳng không qua layma
    header('Location: callback.php?token=' . urlencode($token));
    exit;
}

// Redirect user tới link layma
header('Location: ' . $short);
exit;
