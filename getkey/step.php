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

// ── CALLBACK TỪ LAYMA: verify1 ───────────────────────────────────────────────
// Chỉ cấp key khi:
//   1. step=verify1 có trong URL
//   2. sig=<hmac> có trong URL và khớp với token
// Layma chỉ đơn giản redirect về URL đích — URL đích đã chứa sig
// User tự gõ URL mà không có sig đúng → bị chặn hoàn toàn
if ($step === 'verify1') {
    $sig      = trim($_GET['sig'] ?? '');
    $sigValid = $sig !== '' && verifyBypassSig($token, $sig);

    if (!$sigValid) {
        // Ai đó gõ thẳng URL /step.php?t=TOKEN&step=verify1 mà không có sig đúng
        renderError('Bạn chưa hoàn thành bước bypass Layma. Vui lòng mở link và hoàn thành trước.');
        exit;
    }

    // Sig hợp lệ → Layma đã redirect đúng → cấp key
    issueKey($db, $sess, $token);
    exit;
}

// ── Layma API lỗi khi tạo session ────────────────────────────────────────────
if (isset($_GET['layma_error'])) {
    $laymaErrorMsg = 'Hệ thống bypass tạm thời không khả dụng. Vui lòng thử lại sau vài phút.';
    showLandingPage($sess['link1'], $token, $sess['duration_h'], $laymaErrorMsg);
    exit;
}

// ── DEFAULT: hiển thị trang landing với link Layma ───────────────────────────
showLandingPage($sess['link1'], $token, $sess['duration_h']);

// =============================================================================

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

function showLandingPage(string $laymaLink, string $token, int $dur, string $errorMsg = ''): void {
    $siteUrl = SITE_URL;
    // Detect if link1 is a layma link or fallback
    $isLaymaLink = (
        strpos($laymaLink, 'layma.net') !== false ||
        strpos($laymaLink, 'layma.io')  !== false ||
        strpos($laymaLink, 'lm.la')     !== false ||
        strpos($laymaLink, 'github.io') !== false   // GitHub Pages redirector
    );
    ?><!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>GetKey — Vượt Link</title>
<link rel="stylesheet" href="<?= $siteUrl ?>/assets/css/main.css">
<style>
.step-wrap {
  min-height: 100vh;
  display: flex; align-items: center; justify-content: center;
  padding: 1.5rem;
}
.step-card {
  width: 100%; max-width: 400px;
  background: var(--surface);
  border: 1px solid rgba(0,212,255,.2);
  border-radius: var(--r-xl);
  padding: 2rem;
  box-shadow: 0 24px 64px rgba(0,0,0,.4), 0 0 40px rgba(0,212,255,.06);
}
.s-logo {
  text-align: center; margin-bottom: 1.75rem;
  font-size: 1.5rem; font-weight: 700;
  background: linear-gradient(135deg,var(--cyan),var(--violet));
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.step-single {
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 1.5rem; gap: .75rem;
}
.s-dot-active {
  width: 36px; height: 36px; border-radius: 50%;
  border: 2px solid var(--cyan);
  background: rgba(0,212,255,.1);
  color: var(--cyan); font-size: .75rem; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 0 0 5px rgba(0,212,255,.1);
}
.step-label-text { font-size: var(--f-sm); font-weight: 600; color: var(--text-2); }
.dur-pill {
  display: inline-flex; align-items: center; gap: .35rem;
  font-size: .62rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
  padding: .25rem .8rem; border-radius: 99px; margin-bottom: 1.35rem;
  background: rgba(0,212,255,.1); color: var(--cyan); border: 1px solid rgba(0,212,255,.25);
}
.info-box {
  background: var(--surface2); border: 1px solid var(--border);
  border-radius: var(--r-md); padding: 1rem 1.1rem;
  margin-bottom: 1.35rem;
}
.info-title { font-weight: 600; font-size: var(--f-base); margin-bottom: .3rem; }
.info-desc  { font-size: var(--f-xs); color: var(--text-2); line-height: 1.65; }
.btn-open {
  display: flex; align-items: center; justify-content: center; gap: .55rem;
  width: 100%; padding: .95rem 1.25rem;
  background: linear-gradient(135deg, var(--cyan-dim), var(--cyan));
  color: var(--void); font-weight: 700; font-size: var(--f-sm);
  border-radius: var(--r-md); border: none; cursor: pointer;
  text-decoration: none; transition: all .18s;
  box-shadow: 0 4px 20px rgba(0,212,255,.3);
  letter-spacing: .01em;
}
.btn-open:hover { transform: translateY(-1px); box-shadow: 0 6px 28px rgba(0,212,255,.45); }
.btn-open:active { transform: none; }
.btn-open.disabled-layma {
  opacity: .45; cursor: not-allowed; pointer-events: none;
  background: var(--surface2); color: var(--text-2);
  box-shadow: none;
}
.warn-note {
  margin-top: 1rem;
  background: rgba(245,158,11,.07); border: 1px solid rgba(245,158,11,.2);
  border-radius: var(--r-md); padding: .75rem 1rem;
  font-size: var(--f-xs); color: var(--amber); line-height: 1.6;
  display: flex; gap: .6rem; align-items: flex-start;
}
.warn-note svg { flex-shrink: 0; margin-top: .1rem; }
.error-note {
  margin-bottom: 1rem;
  background: rgba(239,68,68,.08); border: 1px solid rgba(239,68,68,.25);
  border-radius: var(--r-md); padding: .85rem 1rem;
  font-size: var(--f-xs); color: #f87171; line-height: 1.6;
  display: flex; gap: .6rem; align-items: flex-start;
}
.step-footer {
  margin-top: 1.25rem; text-align: center;
  font-size: var(--f-xs); color: var(--text-3);
}
</style>
</head>
<body>
<div class="step-wrap">
  <div class="step-card">

    <div class="s-logo">KillAuth</div>

    <div class="step-single">
      <div class="s-dot-active">1</div>
      <div class="step-label-text">Vượt Link Bypass</div>
    </div>

    <div style="text-align:center">
      <span class="dur-pill">
        <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?= $dur ?> Giờ
      </span>
    </div>

    <?php if ($errorMsg): ?>
    <div class="error-note">
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
      <span><?= htmlspecialchars($errorMsg) ?></span>
    </div>
    <?php endif; ?>

    <?php if ($isLaymaLink): ?>
    <div class="info-box">
      <div class="info-title">Hoàn thành tác vụ trên trang Layma</div>
      <div class="info-desc">
        Nhấn nút bên dưới để mở trang bypass Layma.<br>
        Hoàn thành các bước trên trang đó — key sẽ <strong>tự động được cấp</strong> và bạn sẽ được chuyển về đây.
      </div>
    </div>

    <a href="<?= htmlspecialchars($laymaLink) ?>"
       class="btn-open" id="openBtn" onclick="handleOpen()">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
      </svg>
      Mở Link Bypass
    </a>

    <div class="warn-note">
      <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
      </svg>
      <span>Sau khi hoàn thành Layma, trang sẽ <strong>tự động chuyển</strong> về đây để nhận key. Không cần làm gì thêm.</span>
    </div>

    <?php else: ?>
    <!-- Layma API failed when session was created — show disabled state -->
    <div class="info-box">
      <div class="info-title" style="color:var(--amber)">Bypass Tạm Thời Không Khả Dụng</div>
      <div class="info-desc">
        Hệ thống đang gặp sự cố kết nối với Layma. Vui lòng <strong>quay lại và thử tạo link mới</strong>.
      </div>
    </div>

    <a class="btn-open disabled-layma">
      <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
      </svg>
      Bypass Không Khả Dụng
    </a>

    <div style="margin-top:1rem;text-align:center">
      <a href="<?= $siteUrl ?>/getkey/index.php" class="btn btn-ghost" style="font-size:var(--f-xs)">
        ← Quay lại và thử lại
      </a>
    </div>
    <?php endif; ?>

    <div class="step-footer">
      Key có hiệu lực <strong style="color:var(--cyan)"><?= $dur ?> giờ</strong> — khóa theo thiết bị
    </div>

  </div>
</div>

<script>
function handleOpen() {
  var btn = document.getElementById('openBtn');
  if (!btn) return;
  setTimeout(function() {
    btn.innerHTML = '<svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Đã mở — hoàn thành trên trang Layma...';
    btn.style.background = 'linear-gradient(135deg,#1a8a4a,var(--green))';
    btn.style.boxShadow  = '0 4px 20px rgba(16,217,138,.3)';
    btn.style.pointerEvents = 'none';
  }, 300);
}
</script>
</body>
</html><?php
}

function renderError(string $msg): void {
    $siteUrl = SITE_URL;
    ?><!DOCTYPE html>
<html lang="vi"><head><meta charset="UTF-8"><title>Lỗi</title>
<link rel="stylesheet" href="<?= $siteUrl ?>/assets/css/main.css"></head>
<body style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1.5rem">
<div style="max-width:380px;width:100%;text-align:center">
  <div style="font-size:1.5rem;font-weight:700;background:linear-gradient(135deg,var(--cyan),var(--violet));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin-bottom:1.25rem">KillAuth</div>
  <div class="alert alert-error" style="margin-bottom:1.25rem;text-align:left"><?= htmlspecialchars($msg) ?></div>
  <a href="<?= $siteUrl ?>/getkey/index.php" class="btn btn-ghost btn-block">← Quay lại</a>
</div>
</body></html><?php
}
