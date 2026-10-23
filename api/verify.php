<?php
require_once dirname(__DIR__) . '/config.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$key    = trim($_REQUEST['key'] ?? '');
$hwid   = trim($_REQUEST['hwid'] ?? '');
$app_id = trim($_REQUEST['app_id'] ?? '');

if ($key === '' || $hwid === '' || $app_id === '') {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu tham số']);
    exit;
}

$stmt = db()->prepare("SELECT key_value, device_hash, expires_at FROM issued_keys WHERE key_value = ? AND app_id = ? AND expires_at > NOW() LIMIT 1");
$stmt->bind_param('ss', $key, $app_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    echo json_encode(['status' => 'invalid', 'message' => 'Key không hợp lệ hoặc đã hết hạn']);
    exit;
}

if ($row['device_hash'] !== $hwid) {
    echo json_encode(['status' => 'hwid_mismatch', 'message' => 'HWID không khớp']);
    exit;
}

echo json_encode([
    'status' => 'valid',
    'message' => 'Key hợp lệ',
    'expires_at' => $row['expires_at'],
]);
