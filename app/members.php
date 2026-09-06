<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

// ── 字段校验（服务端为准，前端仅辅助）──
function member_validate_username(string $v): bool {
    return (bool)preg_match('/^[a-zA-Z0-9_]{3,20}$/', $v);
}
function member_validate_password(string $v): bool {
    $len = mb_strlen($v);
    return $len >= 8 && $len <= 32
        && preg_match('/[A-Za-z]/', $v) === 1
        && preg_match('/\d/', $v) === 1;
}
function member_validate_email(string $v): bool {
    return $v !== '' && mb_strlen($v) <= 254 && filter_var($v, FILTER_VALIDATE_EMAIL) !== false;
}
function member_validate_phone(string $v): bool {
    if ($v === '') return true;
    return (bool)preg_match('/^1[3-9]\d{9}$/', $v);
}
function member_validate_nickname(string $v): bool {
    return mb_strlen($v) <= 30;
}

// ── 查询 ──
function member_find_by_username(string $u): ?array {
    $q = db()->prepare('SELECT * FROM users WHERE username = ? COLLATE NOCASE LIMIT 1');
    $q->execute([$u]);
    return $q->fetch() ?: null;
}
function member_get(int $id): ?array {
    $q = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $q->execute([$id]);
    return $q->fetch() ?: null;
}

// ── 唯一性（NOCASE；空值不判重；可排除自身 id）──
function member_username_taken(string $u, ?int $excludeId = null): bool {
    $sql = 'SELECT COUNT(*) FROM users WHERE username = ? COLLATE NOCASE';
    $p = [$u];
    if ($excludeId !== null) { $sql .= ' AND id <> ?'; $p[] = $excludeId; }
    $q = db()->prepare($sql); $q->execute($p);
    return (int)$q->fetchColumn() > 0;
}
function member_email_taken(string $e, ?int $excludeId = null): bool {
    if ($e === '') return false;
    $sql = 'SELECT COUNT(*) FROM users WHERE email = ? COLLATE NOCASE';
    $p = [$e];
    if ($excludeId !== null) { $sql .= ' AND id <> ?'; $p[] = $excludeId; }
    $q = db()->prepare($sql); $q->execute($p);
    return (int)$q->fetchColumn() > 0;
}
function member_phone_taken(string $ph, ?int $excludeId = null): bool {
    if ($ph === '') return false;
    $sql = 'SELECT COUNT(*) FROM users WHERE phone = ?';
    $p = [$ph];
    if ($excludeId !== null) { $sql .= ' AND id <> ?'; $p[] = $excludeId; }
    $q = db()->prepare($sql); $q->execute($p);
    return (int)$q->fetchColumn() > 0;
}

// ── 注册（硬编码 role/status，绝不接受请求传入）──
function member_register(string $username, string $password, string $email, string $phone, string $nickname): int {
    $now = iso_now();
    db()->prepare(
        'INSERT INTO users(username,password_hash,must_change_password,email,phone,nickname,role,status,created_at,updated_at)
         VALUES(?,?,0,?,?,?,\'member\',\'active\',?,?)'
    )->execute([
        $username, password_hash($password, PASSWORD_ARGON2ID),
        $email, $phone, $nickname, $now, $now,
    ]);
    return (int)db()->lastInsertId();
}

// ── 账号自助 ──
function member_update_profile(int $id, string $email, string $phone, string $nickname): void {
    db()->prepare('UPDATE users SET email = ?, phone = ?, nickname = ?, updated_at = ? WHERE id = ?')
        ->execute([$email, $phone, $nickname, iso_now(), $id]);
}
function member_change_password(int $id, string $newPass): void {
    db()->prepare('UPDATE users SET password_hash = ?, must_change_password = 0, updated_at = ? WHERE id = ?')
        ->execute([password_hash($newPass, PASSWORD_ARGON2ID), iso_now(), $id]);
}

// ── 后台会员管理（C）──
function member_set_status(int $id, string $status): void {
    $status = $status === 'disabled' ? 'disabled' : 'active';   // 只允许两值
    db()->prepare("UPDATE users SET status = ?, updated_at = ? WHERE id = ? AND role = 'member'")
        ->execute([$status, iso_now(), $id]);
}
function member_delete(int $id): void {
    db()->prepare("DELETE FROM users WHERE id = ? AND role = 'member'")->execute([$id]);
}
/** 重置密码：CSPRNG 生成临时密码，写哈希 + must_change_password=1，返回明文（仅本次展示一次，不落库明文、不入日志） */
function member_reset_password(int $id): string {
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';   // 去除易混字符
    $tmp = '';
    for ($i = 0; $i < 12; $i++) $tmp .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    db()->prepare("UPDATE users SET password_hash = ?, must_change_password = 1, updated_at = ? WHERE id = ? AND role = 'member'")
        ->execute([password_hash($tmp, PASSWORD_ARGON2ID), iso_now(), $id]);
    return $tmp;
}
/** 仅列 role='member'，不含 password_hash */
function member_list(): array {
    return db()->query(
        "SELECT id, username, email, phone, nickname, status, created_at
         FROM users WHERE role = 'member' ORDER BY id DESC"
    )->fetchAll();
}
