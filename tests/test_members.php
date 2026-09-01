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
