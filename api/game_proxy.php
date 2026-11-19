<?php

// ================= UTF-8 =================
header('Content-Type: application/json; charset=utf-8');

// ================= CORS =================
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Requested-With');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// ================= OPTIONS =================
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ================= TIME =================
date_default_timezone_set('Asia/Ho_Chi_Minh');
set_time_limit(20);

require_once __DIR__ . '/api_map.php';

// ================= INPUT =================
$game = strtolower(trim($_POST['game'] ?? ''));
$type = strtolower(trim($_POST['type'] ?? ''));
$action = strtolower(trim($_POST['action'] ?? ''));

// Baccarat lobby routing
if (strpos($game, 'baccarat_') === 0) {
    $baccaratType = str_replace('baccarat_', '', $game);
    $internalMap = [
        'ae' => 'sexy',
        'dg' => 'dg',
        'wm' => 'wm',
        'evo' => 'evo'
    ];
    if (isset($internalMap[$baccaratType]) && in_array($action, ['history', 'predict'])) {
        $prefix = $internalMap[$baccaratType];
        $type = $prefix . '_' . $action;
        $game = 'baccarat';
    }
}

// ================= VALIDATE =================
if ($game === 'xocdia88') {
    http_response_code(503);
    die(json_encode([
        'status' => false,
        'error'  => 'Hệ thống XÓC ĐĨA 88 đang được bảo trì. Vui lòng quay lại sau.'
    ], JSON_UNESCAPED_UNICODE));
}

if ($game === '' || $type === '') {

    http_response_code(400);

    die(json_encode([
        'status' => false,
        'error'  => 'Missing game/type'
    ], JSON_UNESCAPED_UNICODE));
}

if (!isset($GAME_API_MAP[$game])) {

    http_response_code(404);

    die(json_encode([
        'status' => false,
        'error'  => 'Game not found'
    ], JSON_UNESCAPED_UNICODE));
}

if (!isset($GAME_API_MAP[$game][$type])) {

    http_response_code(404);

    die(json_encode([
        'status' => false,
        'error'  => 'Type not found'
    ], JSON_UNESCAPED_UNICODE));
}

$url = $GAME_API_MAP[$game][$type];

// ================= CACHE =================
$cacheDir = __DIR__ . '/cache';

if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0777, true);
}

$cacheKey  = md5($url);
$cacheFile = __DIR__ . '/cache/' . md5($game . '_' . $action . '_' . ($_POST['table'] ?? '')) . '.json';

$cacheTime = 2;

// đọc cache
if (
    file_exists($cacheFile)
    && (time() - filemtime($cacheFile)) < $cacheTime
) {

    readfile($cacheFile);
    exit;
}

// ================= FETCH API =================
function fetch_api($url)
{
    if (!function_exists('curl_init')) {

        return [
            'response' => false,
            'error'    => 'cURL extension not enabled',
            'code'     => 500
        ];
    }

    $ch = curl_init();

    if ($ch === false) {

        return [
            'response' => false,
            'error'    => 'Cannot init cURL',
            'code'     => 500
        ];
    }

    curl_setopt_array($ch, [

        CURLOPT_URL => $url,

        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_ENCODING => '',

        CURLOPT_MAXREDIRS => 5,

        CURLOPT_TIMEOUT => 3,

        CURLOPT_CONNECTTIMEOUT => 2,

        CURLOPT_FOLLOWLOCATION => true,

        CURLOPT_AUTOREFERER => true,

        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

        CURLOPT_SSL_VERIFYPEER => false,

        CURLOPT_SSL_VERIFYHOST => false,

        CURLOPT_TCP_KEEPALIVE => 1,

        CURLOPT_TCP_KEEPIDLE => 2,

        CURLOPT_TCP_KEEPINTVL => 2,

        CURLOPT_USERAGENT =>
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125 Safari/537.36',

        CURLOPT_HTTPHEADER => [
            'Accept: application/json, text/plain, */*',
            'Accept-Language: vi-VN,vi;q=0.9,en;q=0.8',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'Connection: keep-alive'
        ]
    ]);

    $response = curl_exec($ch);

    $error = curl_error($ch);

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // FIX lỗi curl_close
    if (is_resource($ch) || $ch instanceof CurlHandle) {
        curl_close($ch);
    }

    return [
        'response' => $response,
        'error'    => $error,
        'code'     => $httpCode
    ];
}

// ================= RETRY =================
$result = false;

$lastError = '';

$lastCode = 0;

$maxRetry = 1;

for ($i = 0; $i < $maxRetry; $i++) {

    $api = fetch_api($url);

    $lastError = $api['error'];

    $lastCode = $api['code'];

    if (
        empty($api['error']) &&
        $api['response'] !== false &&
        $api['code'] >= 200 &&
        $api['code'] < 300
    ) {

        $result = $api['response'];

        break;
    }

    usleep(300000);
}

// ================= FAILED =================
if ($result === false) {

    http_response_code(500);

    die(json_encode([
        'status'    => false,
        'error'     => 'API request failed',
        'http_code' => $lastCode,
        'message'   => $lastError,
        'url'       => $url
    ], JSON_UNESCAPED_UNICODE));
}

// ================= SAVE CACHE =================
@file_put_contents($cacheFile, $result, LOCK_EX);

// ================= OUTPUT =================
echo json_encode(['data' => base64_encode($result)]);
