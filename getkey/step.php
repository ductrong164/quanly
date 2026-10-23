<?php
require_once __DIR__ . '/../config.php';

$token = trim($_GET['t'] ?? '');
$step  = trim($_GET['step'] ?? '');

if (!$token) { header('Location: ' . SITE_URL . '/getkey/index.php'); exit; }

$db   = db();
$stmt = $db->prepare("SELECT * FROM getkey_sessions WHERE session_token=? AND completed=0 AND expires_at > NOW()");
$stmt->execute([$token]);
$sess = $stmt->fetch();

if (!$sess) {
    $check = $db->prepare("SELECT * FROM getkey_sessions WHERE session_token=? AND completed=1");
    $check->execute([$token]);
    $done = $check->fetch();
    if ($done && $done['key_issued']) {
        header("Location: " . SITE_URL . "/getkey/success.php?t=$token"); exit;
    }
    renderError('Link đã hết hạn hoặc không hợp lệ. Vui lòng lấy link mới.');
    exit;
}

// Layma callback: chỉ cấp key khi URL chứa sig hợp lệ.
if ($step === 'verify1') {
    $sig      = trim($_GET['sig'] ?? '');
    $sigValid = $sig !== '' && verifyBypassSig($token, $sig);

    if (!$sigValid) {
        renderError('Bạn chưa hoàn thành bước bypass Layma. Vui lòng mở link và hoàn thành trước.');
        exit;
    }

    issueKey($db, $sess, $token);
    exit;
}

// Bỏ màn hình chờ "Vượt Link Bypass". 
// Mở luôn link Layma, xong vượt xong nó tự redirect về callback verify1 để cấp key.
if (isset($_GET['layma_error']) || !isset($_GET['step'])) {
    $redirectTo = $sess['link1'] ?? (SITE_URL . '/getkey/index.php');
    header('Location: ' . $redirectTo);
    exit;
}

// Fallback an toàn nếu vì lý do nào đó vẫn vào đây.
header('Location: ' . ($sess['link1'] ?? (SITE_URL . '/getkey/index.php')));
exit;

function issueKey(PDO $db, array $sess, string $token): void {
    $key = generateKey();
    $exp = date('Y-m-d H:i:s', time() + $sess['duration_h'] * 3600);
    $db->prepare("INSERT INTO `keys` (app_id,key_value,duration_h,ip_bound,expires_at) VALUES (?,?,?,?,?)")
       ->execute([$sess['app_id'], $key, $sess['duration_h'], getClientIP(), $exp]);
    $db->prepare("UPDATE getkey_sessions SET completed=1, key_issued=? WHERE session_token=?")
       ->execute([$key, $token]);
    $db->prepare("INSERT INTO key_logs (key_value,app_id,action,ip,note) VALUES (?,?,'getkey',?,?)")
       ->execute([$key, $sess['app_id'], getClientIP(), 'GetKey ' . $sess['duration_h'] . 'h']);
    header("Location: " . SITE_URL . "/getkey/success.php?t=$token"); exit;
}

function renderError(string $msg): void {
    $siteUrl = SITE_URL;
    ?><!DOCTYPE html>
    <html lang="vi"><head><meta charset="UTF-8"><title>Lỗi</title>
    <link rel="stylesheet" href="<?= $siteUrl ?>/assets/css/main.css"></head>
    <body style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1.5rem">
    <div style="max-width:380px;width:100%;text-align:center">
      <div style="font-size:1.5rem;font-weight:700;background:linear-gradient(135deg,var(--cyan),var(--violet));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin-bottom:1rem">GetKey</div>
      <div class="alert alert-error" style="margin-bottom:1.25rem;text-align:left"><?= htmlspecialchars($msg) ?></div>
      <a href="<?= $siteUrl ?>/getkey/index.php" class="btn btn-ghost btn-block">← Quay lại</a>
    </div>
    </body></html><?php
}
