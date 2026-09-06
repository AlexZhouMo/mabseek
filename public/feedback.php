<?php
require __DIR__ . '/../app/bootstrap.php';
header('Cache-Control: no-store, must-revalidate');

// 来源页白名单（回退 index.php）
$from = (string)($_POST['from'] ?? $_GET['from'] ?? 'index.php');
if (!in_array($from, ['index.php', 'about.php'], true)) $from = 'index.php';
$back = $from . '#contact';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405); header('Allow: POST'); redirect($back);
}
csrf_verify_or_die();

// 蜜罐：正常用户留空，非空即机器人 → 静默丢弃
if (trim((string)($_POST['website'] ?? '')) !== '') { redirect($from . '?fb=ok#contact'); }

$name    = (string)($_POST['name'] ?? '');
$email   = (string)($_POST['email'] ?? '');
$message = (string)($_POST['message'] ?? '');

if (!feedback_can_submit_now(client_ip())) {
    redirect($from . '?fb=rate#contact');
}
if (!feedback_validate_name($name)
    || !feedback_validate_email($email)
    || !feedback_validate_message($message)) {
    redirect($from . '?fb=err#contact');
}

feedback_create($name, $email, $message, client_ip());
audit('feedback_create');
redirect($from . '?fb=ok#contact');
