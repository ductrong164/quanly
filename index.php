<?php
require_once __DIR__ . '/config.php';

// Lấy danh sách app đang active
$apps = db()->query("SELECT app_id, app_name FROM apps WHERE active=1 ORDER BY app_name")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GetKey – Nhận mã kích hoạt</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="bg-glow"></div>

<div class="page-wrapper">
  <header class="site-header">
    <h1 class="logo">Get<span>Key</span></h1>
    <p class="tagline">Chọn ứng dụng để nhận mã kích hoạt</p>
  </header>

  <main>
    <div class="card">

      <!-- Badge thời gian -->
      <div class="badge-row">
        <span class="badge badge-time">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
          </svg>
          12 GIỜ
        </span>
      </div>

      <h2 class="card-title">Key 12 Giờ</h2>
      <p class="card-desc">Vượt 1 link layma — key tự động lưu sau khi hoàn thành.</p>

      <!-- Steps indicator -->
      <div class="steps">
        <div class="step">
          <div class="step-dot pending"></div>
          <span>Layma</span>
        </div>
        <div class="step-arrow">→</div>
        <div class="step">
          <div class="step-dot done"></div>
          <span>Nhận Key</span>
        </div>
      </div>

      <!-- Form -->
      <form id="getkeyForm" action="start.php" method="POST">
        <label class="field-label">CHỌN ỨNG DỤNG</label>
        <div class="select-wrapper" id="selectWrapper">
          <div class="select-display" id="selectDisplay">
            <?php if (!empty($apps)): ?>
              <?= htmlspecialchars($apps[0]['app_name']) ?>
            <?php else: ?>
              — Chọn App —
            <?php endif; ?>
          </div>
          <input type="hidden" name="app_id" id="appIdInput"
                 value="<?= !empty($apps) ? htmlspecialchars($apps[0]['app_id']) : '' ?>">
          <div class="dropdown" id="dropdown">
            <?php foreach ($apps as $app): ?>
            <div class="dropdown-item" data-id="<?= htmlspecialchars($app['app_id']) ?>">
              <?= htmlspecialchars($app['app_name']) ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <input type="hidden" name="hwid" id="hwidInput">

        <button type="submit" class="btn-primary" id="submitBtn">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
          </svg>
          Lấy Key 12 Giờ
        </button>
      </form>

    </div><!-- /card -->
  </main>
</div>

<script src="assets/js/main.js"></script>
</body>
</html>
