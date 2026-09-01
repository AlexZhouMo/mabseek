<?php
require __DIR__ . '/../app/bootstrap.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}
csrf_verify_or_die();
if (auth_check()) audit('member_logout');
auth_logout();
redirect('login.php');
