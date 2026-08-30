<?php
putenv('MABSEEK_DB=:memory:');
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/auth.php';
$pdo = db();
$now = iso_now();
$pdo->prepare('INSERT INTO users(username,password_hash,created_at,updated_at) VALUES(?,?,?,?)')
    ->execute(['admin', password_hash('secret', PASSWORD_ARGON2ID), $now, $now]);

check(auth_verify_credentials('admin', 'secret') === true,  'correct password verifies');
check(auth_verify_credentials('admin', 'nope')  === false, 'wrong password fails');
check(auth_verify_credentials('ghost', 'x')      === false, 'unknown user fails');

// 风控：记 5 次失败后锁定
for ($i = 0; $i < LOGIN_MAX_FAILS; $i++) { auth_record_attempt('1.2.3.4', 'admin', false); }
check(auth_is_locked('1.2.3.4', 'admin') === true, 'locked after max fails');
check(auth_is_locked('9.9.9.9', 'admin') === false, 'other ip not locked');

// 成功登录清零该 (IP,用户) 失败计数
auth_record_attempt('1.2.3.4', 'admin', true);
check(auth_is_locked('1.2.3.4', 'admin') === false, 'success resets fail counter');

// 单 IP 全局锁：轮换用户名也无法规避
for ($i = 0; $i < LOGIN_IP_MAX_FAILS; $i++) { auth_record_attempt('5.5.5.5', 'user' . $i, false); }
check(auth_is_locked('5.5.5.5', 'never-seen') === true, 'ip-global lock blocks username rotation');

// must_change_password：默认 0，改密后仍为 0
check(auth_must_change_password('admin') === false, 'seeded flag defaults false in test');
$pdo->exec("UPDATE users SET must_change_password = 1 WHERE username = 'admin'");
check(auth_must_change_password('admin') === true, 'must_change flag reads true');
auth_change_password('admin', 'newsecret');
check(auth_must_change_password('admin') === false, 'change_password clears must_change flag');
check(auth_verify_credentials('admin', 'newsecret') === true, 'new password verifies after change');
