<?php
// 用临时库验证 schema 创建
putenv('MABSEEK_DB=:memory:');
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/repositories/Collection.php';
$pdo = db();
$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")
              ->fetchAll(PDO::FETCH_COLUMN);
foreach (['audit_log','content_cards','forum_hot','forum_posts','login_attempts','news','partners','snippets','team_members','users'] as $t) {
    check(in_array($t, $tables, true), "table exists: $t");
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
