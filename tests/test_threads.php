<?php
putenv('MABSEEK_DB=:memory:');
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/threads.php';
db();

check(thread_validate_title('一个标题') === true, 'title 合法');
check(thread_validate_title('') === false, 'title 空被拒');
check(thread_validate_title('   ') === false, 'title 纯空白被拒');
check(thread_validate_title(str_repeat('字', 121)) === false, 'title 过长被拒');

check(thread_validate_body('正文内容') === true, 'body 合法');
check(thread_validate_body('') === false, 'body 空被拒');
check(thread_validate_body(str_repeat('字', 5001)) === false, 'body 过长被拒');

check(thread_valid_category('pit') === true, 'category 合法');
check(thread_valid_category('nope') === false, 'category 非法被拒');

// ── 创建 + 查询 + 公开列表 ──（用独立用户，末尾清理）
$now = iso_now();
db()->prepare('INSERT INTO users(username,password_hash,nickname,role,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?)')
    ->execute(['t_user', 'x', '发帖人', 'member', 'active', $now, $now]);
$uid = (int)db()->lastInsertId();

$tid = thread_create($uid, 'pit', '  我的第一帖  ', "第一行\n第二行");
check($tid > 0, '创建返回新 id');
$row = thread_get($tid);
check($row['status'] === 'published', '硬编码 status=published');
check((int)$row['user_id'] === $uid, 'user_id 落库正确');
check($row['title'] === '我的第一帖', 'title 已 trim');

$pub = thread_get_public($tid);
check($pub !== null && $pub['author_nickname'] === '发帖人', '公开详情附作者昵称');

$list = thread_list_published(20);
check(count($list) >= 1 && $list[0]['id'] === $tid, '公开列表含新帖、最新在前');

// 注入韧性（参数化，字符串原样入库）
$injTitle = "x'; DROP TABLE forum_threads;--";
$tid2 = thread_create($uid, 'bio', $injTitle, 'body-or-1-eq-1');
check(thread_get($tid2)['title'] === $injTitle, '注入串原样入库、参数化生效');

// 清理
db()->prepare('DELETE FROM forum_threads WHERE user_id = ?')->execute([$uid]);
db()->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
