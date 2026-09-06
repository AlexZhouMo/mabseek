<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

// ── 字段校验（服务端为准）──
function feedback_validate_name(string $v): bool { $n = mb_strlen(trim($v)); return $n >= 1 && $n <= 50; }
function feedback_validate_email(string $v): bool {
    return $v !== '' && mb_strlen($v) <= 254 && filter_var($v, FILTER_VALIDATE_EMAIL) !== false;
}
function feedback_validate_message(string $v): bool { $n = mb_strlen(trim($v)); return $n >= 1 && $n <= 2000; }

// ── 落库 ──
function feedback_create(string $name, string $email, string $message, string $ip): int {
    db()->prepare(
        "INSERT INTO feedback(name,email,message,status,ip,created_at) VALUES(?,?,?,'new',?,?)"
    )->execute([trim($name), trim($email), trim($message), $ip, iso_now()]);
    return (int)db()->lastInsertId();
}

// ── 频率限制（同 IP）──
function feedback_recent_count_by_ip(string $ip, int $sinceSeconds): int {
    $since = date('c', time() - $sinceSeconds);
    $q = db()->prepare('SELECT COUNT(*) FROM feedback WHERE ip = ? AND created_at >= ?');
    $q->execute([$ip, $since]);
    return (int)$q->fetchColumn();
}
function feedback_can_submit_now(string $ip): bool {
    if (feedback_recent_count_by_ip($ip, FEEDBACK_RATE_MIN_SECONDS) > 0) return false;
    if (feedback_recent_count_by_ip($ip, 86400) >= FEEDBACK_RATE_DAILY_MAX) return false;
    return true;
}

// ── 后台 ──
function feedback_list(): array {
    return db()->query('SELECT * FROM feedback ORDER BY created_at DESC, id DESC')->fetchAll();
}
function feedback_get(int $id): ?array {
    $q = db()->prepare('SELECT * FROM feedback WHERE id = ? LIMIT 1');
    $q->execute([$id]);
    return $q->fetch() ?: null;
}
function feedback_set_status(int $id, string $status): void {
    $status = $status === 'done' ? 'done' : 'new';   // 只允许两值
    db()->prepare('UPDATE feedback SET status = ? WHERE id = ?')->execute([$status, $id]);
}
function feedback_delete(int $id): void {
    db()->prepare('DELETE FROM feedback WHERE id = ?')->execute([$id]);
}
