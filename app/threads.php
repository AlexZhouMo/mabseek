<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function thread_validate_title(string $v): bool { $n = mb_strlen(trim($v)); return $n >= 1 && $n <= 120; }
function thread_validate_body(string $v): bool { $n = mb_strlen(trim(strip_tags($v))); return $n >= 1 && $n <= 5000; }
function thread_valid_category(string $v): bool { return array_key_exists($v, THREAD_CATEGORIES); }
/** 缩略图必须是站内上传路径（拒绝外链，防止存任意 URL）。 */
function thread_valid_cover(string $v): bool {
    return $v !== '' && str_starts_with($v, UPLOAD_URL . '/');
}

function thread_create(int $userId, string $category, string $title, string $body, string $cover): int {
    $now = iso_now();
    db()->prepare("INSERT INTO forum_threads(user_id,category,title,body,cover,status,created_at,updated_at)
                   VALUES(?,?,?,?,?,'published',?,?)")
        ->execute([$userId, $category, trim($title), trim($body), $cover, $now, $now]);
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
/** 按分类分页查询已发布帖；$category 为 null 或 'all' 查全部，否则按分类精确过滤。 */
function thread_list_by_category(?string $category, int $limit, int $offset = 0): array {
    $where = "t.status = 'published'";
    $params = [];
    if ($category !== null && $category !== 'all') {
        $where .= ' AND t.category = ?';
        $params[] = $category;
    }
    $params[] = $limit;
    $params[] = $offset;
    $q = db()->prepare(
        "SELECT t.*, u.nickname AS author_nickname, u.username AS author_username
         FROM forum_threads t JOIN users u ON u.id = t.user_id
         WHERE $where
         ORDER BY t.created_at DESC, t.id DESC LIMIT ? OFFSET ?");
    $q->execute($params);
    return $q->fetchAll();
}
function thread_update(int $id, int $userId, string $category, string $title, string $body, string $cover): bool {
    $st = db()->prepare('UPDATE forum_threads SET category=?, title=?, body=?, cover=?, updated_at=? WHERE id=? AND user_id=?');
    $st->execute([$category, trim($title), trim($body), $cover, iso_now(), $id, $userId]);
    return $st->rowCount() > 0;
}
function thread_delete(int $id, int $userId): bool {
    $st = db()->prepare('DELETE FROM forum_threads WHERE id=? AND user_id=?');
    $st->execute([$id, $userId]);
    return $st->rowCount() > 0;
}
function thread_recent_count_by_user(int $userId, int $sinceSeconds): int {
    $since = date('c', time() - $sinceSeconds);
    $q = db()->prepare('SELECT COUNT(*) FROM forum_threads WHERE user_id = ? AND created_at >= ?');
    $q->execute([$userId, $since]);
    return (int)$q->fetchColumn();
}
function thread_can_post_now(int $userId): bool {
    if (thread_recent_count_by_user($userId, THREAD_RATE_MIN_SECONDS) > 0) return false;
    if (thread_recent_count_by_user($userId, 86400) >= THREAD_RATE_DAILY_MAX) return false;
    return true;
}
function thread_admin_list(): array {
    return db()->query(
        "SELECT t.*, u.nickname AS author_nickname, u.username AS author_username
         FROM forum_threads t LEFT JOIN users u ON u.id = t.user_id
         ORDER BY t.created_at DESC, t.id DESC")->fetchAll();
}
function thread_set_status(int $id, string $status): void {
    $status = $status === 'hidden' ? 'hidden' : 'published';
    db()->prepare('UPDATE forum_threads SET status = ?, updated_at = ? WHERE id = ?')
        ->execute([$status, iso_now(), $id]);
}
function thread_admin_delete(int $id): void {
    db()->prepare('DELETE FROM forum_threads WHERE id = ?')->execute([$id]);
}
