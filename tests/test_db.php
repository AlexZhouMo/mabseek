<?php
// 用临时库验证 schema 创建
putenv('MABSEEK_DB=:memory:');
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/repositories/Collection.php';
$pdo = db();
$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")
              ->fetchAll(PDO::FETCH_COLUMN);
foreach (['audit_log','content_cards','login_attempts','news','partners','snippets','team_members','users'] as $t) {
    check(in_array($t, $tables, true), "table exists: $t");
}
// 已下线的论坛 CMS 假数据表：应被 migrate DROP 掉，不存在
foreach (['forum_posts','forum_hot'] as $t) {
    check(!in_array($t, $tables, true), "table dropped: $t");
}
// PDO 严格模式
check($pdo->getAttribute(PDO::ATTR_ERRMODE) === PDO::ERRMODE_EXCEPTION, 'ERRMODE_EXCEPTION set');

// body 列存在（新库）
$newsCols = $pdo->query("PRAGMA table_info(news)")->fetchAll(PDO::FETCH_COLUMN, 1);
check(in_array('body', $newsCols, true), 'news.body 列存在');

// 迁移幂等：重复 migrate 不报错、body 只有一列
migrate($pdo);
$cols2 = $pdo->query("PRAGMA table_info(news)")->fetchAll(PDO::FETCH_COLUMN, 1);
check(count(array_keys($cols2, 'body')) === 1, '重复 migrate 后 body 仅一列');

// Collection 能写读 body
$id = (new Collection('news'))->create([
    'date_day'=>'01','date_ym'=>'2026·09','category'=>'res',
    'title'=>'T','summary'=>'S','body'=>'<p>正文</p>','sort'=>0,'published'=>1,
]);
try {
    $row = (new Collection('news'))->find($id);
    check(($row['body'] ?? '') === '<p>正文</p>', 'Collection 读写 news.body');
} finally {
    (new Collection('news'))->delete($id);   // 清理:共享 :memory: 连接,勿污染后续 test_repositories 的计数断言
}

// 直接覆盖 add_column_if_missing 的 ALTER 分支（老库补列路径）
$pdo->exec("CREATE TABLE t_addcol (id INTEGER PRIMARY KEY)");
add_column_if_missing($pdo, 't_addcol', "note TEXT NOT NULL DEFAULT ''");
$tc = $pdo->query("PRAGMA table_info(t_addcol)")->fetchAll(PDO::FETCH_COLUMN, 1);
check(in_array('note', $tc, true), 'add_column_if_missing 真的补上了缺失列');
add_column_if_missing($pdo, 't_addcol', "note TEXT NOT NULL DEFAULT ''");
$tc2 = $pdo->query("PRAGMA table_info(t_addcol)")->fetchAll(PDO::FETCH_COLUMN, 1);
check(count(array_keys($tc2, 'note')) === 1, 'add_column_if_missing 幂等：note 仍一列');
$pdo->exec("DROP TABLE t_addcol");   // 清理，勿污染共享库

// ── 会员体系迁移断言 ──
$ucols = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_COLUMN, 1);
foreach (['email','phone','nickname','role','status'] as $c) {
    check(in_array($c, $ucols, true), "users.$c 列存在");
}
// 唯一索引存在
$idx = $pdo->query("SELECT name FROM sqlite_master WHERE type='index'")->fetchAll(PDO::FETCH_COLUMN);
foreach (['idx_users_username','idx_users_email','idx_users_phone'] as $i) {
    check(in_array($i, $idx, true), "索引存在: $i");
}
// 迁移幂等：重复 migrate 不新增重复列
migrate($pdo);
$ucols2 = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_COLUMN, 1);
check(count(array_keys($ucols2, 'role')) === 1, '重复 migrate 后 role 仅一列');
// role 回填：seed 管理员用户名的行被置为 admin
$pdo->prepare('DELETE FROM users WHERE username = ? COLLATE NOCASE')->execute([SEED_ADMIN_USER]);  // 清 test_auth 遗留的共享库行
$pdo->prepare('INSERT INTO users(username,password_hash,role,status,created_at,updated_at) VALUES(?,?,?,?,?,?)')
    ->execute([SEED_ADMIN_USER, 'x', 'member', 'active', date('c'), date('c')]);
migrate($pdo);
$r = $pdo->prepare('SELECT role FROM users WHERE username = ? COLLATE NOCASE');
$r->execute([SEED_ADMIN_USER]);
check($r->fetchColumn() === 'admin', 'migrate 幂等回填 seed 管理员 role=admin');
$pdo->prepare('DELETE FROM users WHERE username = ? COLLATE NOCASE')->execute([SEED_ADMIN_USER]);  // 清理共享库

// ── forum_threads 迁移断言 ──
$tables2 = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
check(in_array('forum_threads', $tables2, true), 'forum_threads 表存在');
$tcols = $pdo->query("PRAGMA table_info(forum_threads)")->fetchAll(PDO::FETCH_COLUMN, 1);
foreach (['user_id','category','title','body','status','created_at','updated_at'] as $c) {
    check(in_array($c, $tcols, true), "forum_threads.$c 列存在");
}
$idx2 = $pdo->query("SELECT name FROM sqlite_master WHERE type='index'")->fetchAll(PDO::FETCH_COLUMN);
check(in_array('idx_threads_status_created', $idx2, true), '索引 idx_threads_status_created 存在');
migrate($pdo);
$tcols2 = $pdo->query("PRAGMA table_info(forum_threads)")->fetchAll(PDO::FETCH_COLUMN, 1);
check(count(array_keys($tcols2, 'title')) === 1, '重复 migrate 后 title 仅一列');
