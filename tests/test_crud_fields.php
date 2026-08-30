<?php
putenv('MABSEEK_DB=:memory:');
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/repositories/Collection.php';

// 捕获 news.php 声明的 $cfg，不触发真实渲染
if (!function_exists('admin_crud')) {
    function admin_crud(array $cfg): void { $GLOBALS['__captured_cfg'] = $cfg; }
}
require __DIR__ . '/../app/admin/news.php';
$cfg = $GLOBALS['__captured_cfg'] ?? [];

$fieldNames = array_map(fn($f) => $f['name'], $cfg['fields'] ?? []);
$cols = db()->query("PRAGMA table_info(news)")->fetchAll(PDO::FETCH_COLUMN, 1);
$editable = array_values(array_diff($cols, ['id', 'created_at', 'updated_at']));
sort($fieldNames);
sort($editable);
check($fieldNames === $editable, 'news 可编辑字段与表结构一致');
