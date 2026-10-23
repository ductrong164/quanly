<?php
require_once __DIR__ . '/../config.php';
$token = trim($_GET['t'] ?? '');
$db    = db();
$stmt  = $db->prepare("SELECT gs.*, a.app_name, k.expires_at, k.duration_h FROM getkey_sessions gs LEFT JOIN apps a ON gs.app_id=a.app_id LEFT JOIN `keys` k ON gs.key_issued=k.key_value WHERE gs.session_token=? AND gs.completed=1");
$stmt->execute([$token]);
$sess  = $stmt->fetch();
if (!$sess || !$sess['key_issued']) { header('Location: ' . SITE_URL . '/getkey/index.php'); exit; }
$key     = $sess['key_issued'];
$expires = $sess['expires_at'] ? date('d/m/Y H:i:s', strtotime($sess['expires_at'])) : '—';
$dur     = $sess['duration_h'];
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Key Retrieved — KillAuth</title>
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/main.css">
<style>
@keyframes pulse-glow {
  0%,100% { box-shadow: 0 0 20px rgba(16,217,138,.15), inset 0 0 20px rgba(16,217,138,.04); }
  50%     { box-shadow: 0 0 40px rgba(16,217,138,.3),  inset 0 0 30px rgba(16,217,138,.08); }
}
.key-box { animation: pulse-glow 2.5s ease-in-out infinite; }
</style>
</head>
<body>
<div class="gk-hero">
  <div class="gk-card" style="border-color:rgba(16,217,138,.3);box-shadow:0 32px 80px rgba(0,0,0,.4), 0 0 40px rgba(16,217,138,.1)">
    <div class="gk-logo">KillAuth</div>
    <div class="gk-sub" style="color:var(--green)">Key successfully generated</div>

    <!-- Success icon -->
    <div style="text-align:center;margin:1.5rem 0 1rem">
      <div style="display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;border-radius:50%;background:rgba(16,217,138,.1);border:2px solid rgba(16,217,138,.3)">
        <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="var(--green)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
      </div>
    </div>

    <!-- Key box -->
    <div class="key-box">
      <div>
        <div class="key-val" id="keyVal"><?= htmlspecialchars($key) ?></div>
        <div class="key-expires">
          <?php if ($dur == 12): ?>
            <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="vertical-align:middle"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Expires: <?= $expires ?> (12h)
          <?php else: ?>
            <svg width="11" height="11" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="vertical-align:middle"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Expires: <?= $expires ?> (24h)
          <?php endif; ?>
        </div>
      </div>
      <button onclick="copyKey()" id="copyBtn" class="btn btn-ghost btn-sm" style="flex-shrink:0">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
        Copy
      </button>
    </div>

    <!-- Info -->
    <div class="card" style="margin-top:1.25rem;border-color:rgba(0,212,255,.15);padding:1rem 1.25rem">
      <div style="display:flex;flex-direction:column;gap:.5rem">
        <div class="flex gap-4" style="justify-content:space-between">
          <span class="text-xs text-muted">Application</span>
          <span class="text-xs"><strong><?= htmlspecialchars($sess['app_name'] ?? $sess['app_id']) ?></strong></span>
        </div>
        <div class="flex gap-4" style="justify-content:space-between">
          <span class="text-xs text-muted">Duration</span>
          <span class="badge <?= $dur==24?'badge-violet':'badge-cyan' ?>"><?= $dur ?>h</span>
        </div>
        <div class="flex gap-4" style="justify-content:space-between">
          <span class="text-xs text-muted">Device Lock</span>
          <span class="text-xs text-green">Bound on first use</span>
        </div>
      </div>
    </div>

    <div class="alert alert-info" style="margin-top:1.25rem;font-size:var(--f-xs)">
      <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      This key is locked to one device. Sharing will result in a ban.
    </div>
  </div>
</div>
<script>
function copyKey() {
  const k = document.getElementById('keyVal').textContent;
  navigator.clipboard.writeText(k).then(() => {
    const btn = document.getElementById('copyBtn');
    btn.innerHTML = '<svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Copied!';
    btn.style.color = 'var(--green)';
    btn.style.borderColor = 'rgba(16,217,138,.4)';
    setTimeout(() => {
      btn.innerHTML = '<svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg> Copy';
      btn.style.color = ''; btn.style.borderColor = '';
    }, 2000);
  });
}
</script>
</body>
</html>
