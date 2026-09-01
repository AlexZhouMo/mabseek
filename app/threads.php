<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function thread_validate_title(string $v): bool { $n = mb_strlen(trim($v)); return $n >= 1 && $n <= 120; }
function thread_validate_body(string $v): bool { $n = mb_strlen(trim($v)); return $n >= 1 && $n <= 5000; }
function thread_valid_category(string $v): bool { return array_key_exists($v, THREAD_CATEGORIES); }

function thread_create(int $userId, string $category, string $title, string $body): int {
    $now = iso_now();
    db()->prepare("INSERT INTO forum_threads(user_id,category,title,body,status,created_at,updated_at)
                   VALUES(?,?,?,?,'published',?,?)")
        ->execute([$userId, $category, trim($title), trim($body), $now, $now]);
    return (int)db()->lastInsertId();
}
function thread_get(int $id): ?array {
    $q = db()->prepare('SELECT * FROM forum_threads WHERE id = ? LIMIT 1');
    $q->execute([$id]);
    return $q->fetch() ?: null;
}
function thread_get_public(int $id): ?array {
    $q = db()->prepare(
        "SELECT t.*, u.nickname AS author_nickname, u.username AS author_username
         FROM forum_threads t JOIN users u ON u.id = t.user_id
         WHERE t.id = ? AND t.status = 'published' LIMIT 1");
    $q->execute([$id]);
    return $q->fetch() ?: null;
}
function thread_list_published(int $limit, int $offset = 0): array {
    $q = db()->prepare(
        "SELECT t.*, u.nickname AS author_nickname, u.username AS author_username
         FROM forum_threads t JOIN users u ON u.id = t.user_id
         WHERE t.status = 'published'
         ORDER BY t.created_at DESC, t.id DESC LIMIT ? OFFSET ?");
    $q->execute([$limit, $offset]);
    return $q->fetchAll();
}
