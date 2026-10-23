<?php
require_once __DIR__ . '/config.php';

$key = trim($_GET['key'] ?? '');
$cached = isset($_GET['cached']);

if ($key === '') {
    header('Location: index.php');
    exit;
}

$stmt = db()->prepare("SELECT k.key_value, k.expires_at, a.app_name FROM keys k JOIN apps a ON a.app_id = k.app_id WHERE k.key_value = ? LIMIT 1");
$stmt->bind_param('s', $key);
$stmt->execute();
$info = $stmt->get_result()->fetch_assoc();

if (!$info) {
    header('Location: index.php?err=key_not_found');
    exit;
}

$expires = new DateTime($info['expires_at']);
$now = new DateTime();
$diff = $now->diff($expires);
$hours_left = ($diff->h + $diff->days * 24);
$mins_left = $diff->i;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Key của bạn – GetKey</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="bg-glow"></div>

<div class="page-wrapper">
  <header class="site-header">
    <h1 class="logo">Get<span>Key</span></h1>
    <p class="tagline">Key của bạn đã sẵn sàng</p>
  </header>

  <main>
    <div class="card card-done">
      <div class="success-icon">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
          <polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
      </div>

      <h2 class="card-title">Key đã tạo thành công!</h2>
      <?php if ($cached): ?>
      <p class="card-desc" style="color:#a78bfa">✦ Key cũ còn hiệu lực, không cần vượt link lại.</p>
      <?php endif; ?>

      <div class="key-meta">
        <span class="badge badge-app"><?= htmlspecialchars($info['app_name']) ?></span>
        <span class="badge badge-time">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
          </svg>
          Còn <?= $hours_left ?>h <?= $mins_left ?>p
        </span>
      </div>

      <div class="key-box" id="keyBox">
        <code id="keyValue"><?= htmlspecialchars($info['key_value']) ?></code>
        <button class="copy-btn" onclick="copyKey()" title="Sao chép">
          <svg id="copyIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
          </svg>
        </button>
      </div>

      <p class="expire-note">Hết hạn lúc: <strong><?= $expires->format('H:i d/m/Y') ?></strong></p>
      <a href="index.php" class="btn-secondary">← Lấy key khác</a>
    </div>
  </main>
</div>

<script>
function copyKey() {
  const val = document.getElementById('keyValue').textContent;
  navigator.clipboard.writeText(val).then(() => {
    const btn = document.querySelector('.copy-btn');
    btn.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>`;
    setTimeout(() => {
      btn.innerHTML = `<svg id="copyIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>`;
    }, 2000);
  });
}
</script>
</body>
</html>
