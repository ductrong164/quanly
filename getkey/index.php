<?php
require_once __DIR__ . '/../config.php';
$apps = db()->query("SELECT app_id, app_name FROM apps WHERE status='active' ORDER BY app_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appId = trim($_POST['app_id'] ?? '');

    if ($appId) {
        $db    = db();
        $check = $db->prepare("SELECT app_id FROM apps WHERE app_id=? AND status='active'");
        $check->execute([$appId]);
        if ($check->fetch()) {
            $token      = generateToken();
            $exp        = date('Y-m-d H:i:s', time() + 86400);
            // HMAC sig: only Layma passes this through — user cannot forge it
            $sig        = generateBypassSig($token);
            $dest1      = SITE_URL . '/getkey/step.php?t=' . $token . '&step=verify1&sig=' . $sig;
            // Dùng GitHub Pages làm redirector — không phụ thuộc Layma
            $link1Short = GITHUB_BYPASS_URL . '#' . urlencode($dest1);

            $db->prepare("INSERT INTO getkey_sessions (session_token,app_id,duration_h,step,link1,link2,expires_at) VALUES (?,?,12,0,?,NULL,?)")
               ->execute([$token, $appId, $link1Short, $exp]);

            header('Location: ' . SITE_URL . '/getkey/step.php?t=' . urlencode($token));
            exit;
        }
    }
    header('Location: ' . SITE_URL . '/getkey/index.php?err=1');
    exit;
}

$err = isset($_GET['err']);
?><!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>GetKey — KillAuth</title>
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/main.css">
<style>
.gk-single-card {
  width: 100%; max-width: 440px;
  background: var(--surface);
  border: 1px solid rgba(0,212,255,.25);
  border-radius: var(--r-xl);
  padding: 2rem;
  display: flex; flex-direction: column; gap: 1.2rem;
  position: relative; overflow: hidden;
  box-shadow: 0 0 40px rgba(0,212,255,.08);
}
.gk-single-card::before {
  content:''; position:absolute; inset:0; pointer-events:none; border-radius:var(--r-xl);
  background: radial-gradient(ellipse 80% 60% at 50% -20%, rgba(0,212,255,.09) 0%, transparent 70%);
}
.dur-badge {
  font-size:var(--f-xs); font-weight:700; letter-spacing:.07em; text-transform:uppercase;
  padding:.3rem .9rem; border-radius:99px; display:inline-flex; align-items:center; gap:.35rem;
  background:rgba(0,212,255,.12); color:var(--cyan); border:1px solid rgba(0,212,255,.3);
}
.bypass-chips { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; }
.chip {
  display:inline-flex; align-items:center; gap:.3rem;
  font-size:var(--f-xs); font-weight:600; padding:.22rem .65rem; border-radius:99px;
  background:var(--surface2); border:1px solid var(--border); color:var(--text-2);
}
.chip.green { color:var(--green); border-color:rgba(16,217,138,.25); background:rgba(16,217,138,.07); }
.chip-sep { color:var(--text-3); font-size:.75rem; }
.gk-select {
  width:100%; background:var(--surface2); border:1px solid var(--border);
  border-radius:var(--r-md); padding:.7rem 1rem;
  color:var(--text-1); font-family:inherit; font-size:var(--f-base);
  outline:none; appearance:none; cursor:pointer;
  transition:border-color .18s, box-shadow .18s;
}
.gk-select:focus { border-color:var(--cyan); box-shadow:0 0 0 3px rgba(0,212,255,.12); }
.btn-loading { position:relative; pointer-events:none; opacity:.8; }
.btn-loading::after {
  content:''; position:absolute; right:1rem; top:50%; transform:translateY(-50%);
  width:14px; height:14px; border:2px solid transparent;
  border-top-color:currentColor; border-radius:50%;
  animation: spin .6s linear infinite;
}
@keyframes spin { to { transform:translateY(-50%) rotate(360deg); } }
</style>
</head>
<body>
<div class="gk-hero" style="padding:2rem 1rem">
  <div style="text-align:center;margin-bottom:2rem">
    <div class="gk-logo">GetKey</div>
    <div class="gk-sub">Chọn ứng dụng để nhận mã kích hoạt</div>
  </div>

  <?php if ($err): ?>
    <div class="alert alert-error" style="max-width:440px;margin-bottom:1.25rem">
      Ứng dụng không hợp lệ hoặc không hoạt động. Thử lại.
    </div>
  <?php endif; ?>

  <?php if (!$apps): ?>
    <div class="alert alert-warning" style="max-width:440px">Chưa có ứng dụng nào hoạt động.</div>
  <?php else: ?>

  <div class="gk-single-card">
    <div>
      <span class="dur-badge">
        <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        12 Giờ
      </span>
    </div>
    <div>
      <div style="font-size:var(--f-lg);font-weight:700;margin-bottom:.3rem">Key 12 Giờ</div>
      <div style="font-size:var(--f-sm);color:var(--text-2);line-height:1.55">Vượt 1 link bypass — key tự động lưu sau khi hoàn thành.</div>
    </div>
    <div class="bypass-chips">
      <span class="chip">
        <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101"/></svg>
        Bypass 1
      </span>
      <span class="chip-sep">→</span>
      <span class="chip green">
        <svg width="10" height="10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>
        Nhận Key
      </span>
    </div>
    <form method="POST" onsubmit="setLoading(this)">
      <div style="margin-bottom:.85rem">
        <label style="font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-2);display:block;margin-bottom:.45rem">Chọn Ứng Dụng</label>
        <select name="app_id" class="gk-select" required>
          <option value="" disabled selected>— Chọn App —</option>
          <?php foreach ($apps as $a): ?>
            <option value="<?= htmlspecialchars($a['app_id']) ?>"><?= htmlspecialchars($a['app_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary btn-block" style="font-size:var(--f-sm);padding:.75rem 1rem">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
        Lấy Key 12 Giờ
      </button>
    </form>
  </div>

  <?php endif; ?>
</div>

<script>
function setLoading(form) {
  var btn = form.querySelector('button[type="submit"]');
  if (btn) {
    btn.disabled = true;
    btn.classList.add('btn-loading');
    btn.textContent = 'Đang xử lý...';
  }
}
</script>
</body>
</html>
