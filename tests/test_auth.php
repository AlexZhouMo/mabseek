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

// ── 角色与鉴权辅助 ──
$pdo->exec("UPDATE users SET role='admin' WHERE username='admin'");
check(auth_user_role('admin') === 'admin', 'auth_user_role 读取 admin 角色');
check(auth_user_role('ADMIN') === 'admin', 'auth_user_role 大小写不敏感');
check(auth_user_role('ghost') === '', '不存在用户返回空角色');

$_SESSION = [];
$_SESSION['role'] = 'admin';
check(auth_is_admin() === true, 'role=admin 通过 auth_is_admin');
$_SESSION['role'] = 'member';
check(auth_is_admin() === false, 'role=member 不通过 auth_is_admin');
$_SESSION = [];
check(auth_is_admin() === false, '无会话不通过 auth_is_admin');

// ── 登录后跳转目标:email 已填进首页,空则进补全页 ──
check(auth_post_login_dest(['email' => 'a@b.com']) === 'index.php',
    'email 已填 → index.php');
check(auth_post_login_dest(['email' => '']) === 'account.php?complete=1',
    'email 空字符串 → 补全页');
check(auth_post_login_dest(['email' => '   ']) === 'account.php?complete=1',
    'email 纯空白 → 补全页(trim 后为空)');
check(auth_post_login_dest(['email' => null]) === 'account.php?complete=1',
    'email 为 null → 补全页');
check(auth_post_login_dest([]) === 'account.php?complete=1',
    '无 email 键 → 补全页');
check(auth_post_login_dest(null) === 'account.php?complete=1',
    'row 为 null → 补全页(防御)');
