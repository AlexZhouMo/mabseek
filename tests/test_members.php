<?php
putenv('MABSEEK_DB=:memory:');
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/members.php';
db();

// ── 字段校验：正例/反例 ──
check(member_validate_username('alice_01') === true, 'username 合法');
check(member_validate_username('ab') === false, 'username 过短被拒');
check(member_validate_username('has space') === false, 'username 含空格被拒');
check(member_validate_username(str_repeat('a', 21)) === false, 'username 过长被拒');

check(member_validate_password('abc12345') === true, 'password 含字母+数字合法');
check(member_validate_password('abcdefgh') === false, 'password 缺数字被拒');
check(member_validate_password('12345678') === false, 'password 缺字母被拒');
check(member_validate_password('a1b2c3') === false, 'password 过短被拒');
check(member_validate_password('a1' . str_repeat('x', 31)) === false, 'password 过长被拒');

check(member_validate_email('') === true, 'email 可空');
check(member_validate_email('a@b.com') === true, 'email 合法');
check(member_validate_email('not-an-email') === false, 'email 非法被拒');

check(member_validate_phone('') === true, 'phone 可空');
check(member_validate_phone('13800138000') === true, 'phone 合法');
check(member_validate_phone('12345') === false, 'phone 非法被拒');
check(member_validate_phone('23800138000') === false, 'phone 非 1[3-9] 段被拒');

check(member_validate_nickname('小明') === true, 'nickname 合法');
check(member_validate_nickname(str_repeat('字', 31)) === false, 'nickname 过长被拒');

// ── 注册 + 唯一性 + 查询 + 更新 ──（用独立用户名，测试末尾清理）
$mid = member_register('m_alice', 'abc12345', 'alice@x.com', '13800138000', '小A');
check($mid > 0, '注册返回新 id');
$row = member_find_by_username('m_alice');
check($row !== null && $row['role'] === 'member', '注册硬编码 role=member');
check($row['status'] === 'active', '注册默认 status=active');
check($row['password_hash'] !== 'abc12345', '落库是哈希而非明文');
check(auth_verify_credentials('m_alice', 'abc12345') === true, '注册后可用该账号登录');
check(auth_verify_credentials('M_ALICE', 'abc12345') === true, '登录用户名大小写不敏感');

// 唯一性（NOCASE）
check(member_username_taken('M_Alice') === true, 'username 唯一 NOCASE 命中');
check(member_email_taken('ALICE@X.COM') === true, 'email 唯一 NOCASE 命中');
check(member_phone_taken('13800138000') === true, 'phone 唯一命中');
check(member_email_taken('') === false, '空 email 不判重');
check(member_email_taken('alice@x.com', $mid) === false, '排除自身后不判重');

// 更新资料
member_update_profile($mid, 'alice2@x.com', '13900139000', '小A2');
$row2 = member_find_by_username('m_alice');
check($row2['email'] === 'alice2@x.com' && $row2['nickname'] === '小A2', '资料更新生效');

// 改密
member_change_password($mid, 'newpass99');
check(auth_verify_credentials('m_alice', 'newpass99') === true, '改密后新密码可登录');
check(auth_verify_credentials('m_alice', 'abc12345') === false, '改密后旧密码失效');

// 注入韧性：含 SQL 元字符原样处理、正常拒绝、无异常
check(member_username_taken("x' OR '1'='1") === false, '注入串不导致 SQL 错误、按普通字符串处理');

// 清理共享库
db()->prepare('DELETE FROM users WHERE username = ? COLLATE NOCASE')->execute(['m_alice']);
