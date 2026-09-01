<?php
require __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/admin/upload.php';   // handle_upload()
header('Cache-Control: no-store, must-revalidate');
member_check();                                       // 未登录 → 重定向登录

while (ob_get_level() > 0) { ob_end_clean(); }        // 丢弃缓冲，输出干净 JSON
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405); header('Allow: POST'); exit('Method Not Allowed');
}
csrf_verify_or_die();
header('Content-Type: application/json; charset=utf-8');

// 与发帖一致：停用账号不得上传（防被停用会员绕过发帖限制堆积图片）
$me = member_find_by_username((string)($_SESSION['uid'] ?? ''));
if (!$me || ($me['status'] ?? '') !== 'active') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => '账号已被停用，无法上传。'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $url = handle_upload('file');
    echo json_encode(
        $url !== null ? ['ok' => true, 'url' => $url] : ['ok' => false, 'error' => '未选择文件'],
        JSON_UNESCAPED_UNICODE
    );
} catch (\RuntimeException $ex) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $ex->getMessage()], JSON_UNESCAPED_UNICODE);
}
