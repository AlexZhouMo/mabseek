<?php
if (session_status() !== PHP_SESSION_ACTIVE) { $_SESSION = []; }
require_once __DIR__ . '/../app/csrf.php';
$t1 = csrf_token();
check(strlen($t1) >= 32, 'token length ok');
check(csrf_token() === $t1, 'token stable within session');
check(csrf_check($t1) === true, 'valid token passes');
check(csrf_check('bogus') === false, 'wrong token fails');
