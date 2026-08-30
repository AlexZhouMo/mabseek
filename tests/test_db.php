<?php
// 用临时库验证 schema 创建
putenv('MABSEEK_DB=:memory:');
require_once __DIR__ . '/../app/db.php';
$pdo = db();
$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")
              ->fetchAll(PDO::FETCH_COLUMN);
foreach (['audit_log','content_cards','forum_hot','forum_posts','login_attempts','news','partners','snippets','team_members','users'] as $t) {
    check(in_array($t, $tables, true), "table exists: $t");
}
// PDO 严格模式
check($pdo->getAttribute(PDO::ATTR_ERRMODE) === PDO::ERRMODE_EXCEPTION, 'ERRMODE_EXCEPTION set');
