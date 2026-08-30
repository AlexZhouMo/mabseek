<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/admin/upload.php';

check(upload_ext_for_mime('image/png') === 'png', 'png mime → png');
check(upload_ext_for_mime('image/gif') === null, 'gif rejected');
check(upload_ext_for_mime('image/jpeg') === 'jpg', 'jpeg → jpg');
check(upload_ext_for_mime('image/webp') === 'webp', 'webp → webp');

$n = upload_random_name('png');
check(preg_match('/^[a-f0-9]{32}\.png$/', $n) === 1, 'random name format');
