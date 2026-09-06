<?php
require __DIR__ . '/../app/bootstrap.php';
header('Cache-Control: no-store, must-revalidate');
member_check();                                       // 未登录 → 重定向登录（与页面门禁一致）

while (ob_get_level() > 0) { ob_end_clean(); }        // 输出干净 JSON
header('Content-Type: application/json; charset=utf-8');

// 分类：白名单校验，非法归为 all
$category = (string)($_GET['category'] ?? 'all');
if ($category !== 'all' && !array_key_exists($category, THREAD_CATEGORIES)) {
    $category = 'all';
}
$offset = max(0, (int)($_GET['offset'] ?? 0));

// 多取一条判断是否还有更多
$rows    = thread_list_by_category($category, FORUM_PAGE_SIZE + 1, $offset);
$hasMore = count($rows) > FORUM_PAGE_SIZE;
$rows    = array_slice($rows, 0, FORUM_PAGE_SIZE);

$items = array_map(static function (array $t): array {
    $author = ($t['author_nickname'] ?? '') !== '' ? $t['author_nickname'] : ($t['author_username'] ?? '');
    return [
        'id'         => (int)$t['id'],
        'title'      => (string)$t['title'],
        'cover'      => (string)($t['cover'] ?? ''),
        'category'   => (string)$t['category'],
        'catLabel'   => THREAD_CATEGORIES[$t['category']] ?? (string)$t['category'],
        'author'     => (string)$author,
        'created_at' => (string)$t['created_at'],
    ];
}, $rows);

echo json_encode(['ok' => true, 'items' => $items, 'hasMore' => $hasMore], JSON_UNESCAPED_UNICODE);
