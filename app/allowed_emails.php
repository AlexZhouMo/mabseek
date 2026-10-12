<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/members.php';  // 复用 member_validate_email / member_username_taken / member_register 等

// ─────────────────────────── 查询 ───────────────────────────
function allowed_email_find(string $email): ?array {
    $email = trim($email);
    if ($email === '') return null;
    $q = db()->prepare('SELECT * FROM allowed_emails WHERE email = ? COLLATE NOCASE LIMIT 1');
    $q->execute([$email]);
    return $q->fetch() ?: null;
}

function allowed_email_get(int $id): ?array {
    $q = db()->prepare('SELECT * FROM allowed_emails WHERE id = ? LIMIT 1');
    $q->execute([$id]);
    return $q->fetch() ?: null;
}

function allowed_email_list(array $filter = []): array {
    $where = [];
    $args  = [];
    if (!empty($filter['category'])) { $where[] = 'category = ?'; $args[] = $filter['category']; }
    if (!empty($filter['status']))   { $where[] = 'status = ?';   $args[] = $filter['status']; }
    if (!empty($filter['search'])) {
        $where[] = '(email LIKE ? OR name LIKE ? OR student_id LIKE ?)';
        $like    = '%' . $filter['search'] . '%';
        array_push($args, $like, $like, $like);
    }
    $sql = 'SELECT * FROM allowed_emails';
    if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
    $sql .= ' ORDER BY id DESC';
    $q = db()->prepare($sql);
    $q->execute($args);
    return $q->fetchAll();
}

// ─────────────────────── 准入校验（register.php 调）───────────────────────
/** 回 ['ok'=>bool, 'reason'=>'', 'row'=>?array]。ok=true 仅当 row 存在且 status=active。 */
function allowed_email_can_register(string $email): array {
    $row = allowed_email_find($email);
    if (!$row) return ['ok' => false, 'reason' => 'not_in_whitelist', 'row' => null];
    if ((string)$row['status'] !== 'active') {
        return ['ok' => false, 'reason' => 'status_' . $row['status'], 'row' => $row];
    }
    return ['ok' => true, 'reason' => '', 'row' => $row];
}

// ─────────────────────────── 写入 ───────────────────────────
/** upsert：带 id 则 update；空邮箱按 (name, student_id) 幂等查重；有邮箱按 email NOCASE 查重。 */
function allowed_email_upsert(array $row): int {
    $now        = iso_now();
    $email      = trim((string)($row['email'] ?? ''));
    $category   = (string)($row['category'] ?? '');
    $name       = (string)($row['name'] ?? '');
    $studentId  = (string)($row['student_id'] ?? '');
    $note       = (string)($row['note'] ?? '');
    $status     = (string)($row['status'] ?? ($email === '' ? 'pending' : 'active'));

    // 已有 id → update
    if (!empty($row['id'])) {
        db()->prepare('UPDATE allowed_emails SET email=?, category=?, name=?, student_id=?, status=?, note=?, updated_at=? WHERE id=?')
            ->execute([$email, $category, $name, $studentId, $status, $note, $now, (int)$row['id']]);
        return (int)$row['id'];
    }

    // 有邮箱 → 按邮箱查重（NOCASE）
    if ($email !== '') {
        $existing = allowed_email_find($email);
        if ($existing) return (int)$existing['id'];
    } else {
        // 空邮箱 → 按 (name, student_id) 幂等查重
        $q = db()->prepare("SELECT id FROM allowed_emails WHERE email='' AND name=? AND student_id=? LIMIT 1");
        $q->execute([$name, $studentId]);
        $existingId = $q->fetchColumn();
        if ($existingId !== false) return (int)$existingId;
    }

    db()->prepare('INSERT INTO allowed_emails(email,category,name,student_id,status,note,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?)')
        ->execute([$email, $category, $name, $studentId, $status, $note, $now, $now]);
    return (int)db()->lastInsertId();
}

function allowed_email_mark_provisioned(int $id, int $userId): void {
    db()->prepare("UPDATE allowed_emails SET status='provisioned', provisioned_user_id=?, updated_at=? WHERE id=?")
        ->execute([$userId, iso_now(), $id]);
}

function allowed_email_set_status(int $id, string $status): void {
    $allowed = ['active', 'pending', 'disabled', 'provisioned'];
    if (!in_array($status, $allowed, true)) return;
    db()->prepare('UPDATE allowed_emails SET status=?, updated_at=? WHERE id=?')
        ->execute([$status, iso_now(), $id]);
}

function allowed_email_delete(int $id): void {
    db()->prepare('DELETE FROM allowed_emails WHERE id=?')->execute([$id]);
}

// ─────────────── 用户名自动生成（一键建号用） ───────────────
/** 邮箱 @ 前缀 → 过滤非 [a-zA-Z0-9_] → 截断 20 位 → <3 位时 fallback 到 u_学号 或随机后缀 → 冲突追加 _N。
 *  生成结果需通过 member_validate_username 校验，否则调用方应改用手动建号。 */
function allowed_email_gen_username(string $email, string $studentId = ''): string {
    $prefix = explode('@', $email, 2)[0] ?? '';
    $clean  = preg_replace('/[^a-zA-Z0-9_]/', '', $prefix) ?? '';
    if (mb_strlen($clean) > 20) $clean = substr($clean, 0, 20);

    // 太短，用学号或随机后缀补足到 ≥3 位
    if (mb_strlen($clean) < 3) {
        if ($studentId !== '' && preg_match('/^[a-zA-Z0-9_]+$/', $studentId)) {
            $cand = 'u_' . $studentId;
            if (mb_strlen($cand) > 20) $cand = substr($cand, 0, 20);
            $clean = $cand;
        } else {
            $clean = $clean . '_' . random_int(100, 999);
        }
    }

    // 冲突追加 _2 / _3 / _N
    $base = $clean;
    $n    = 2;
    while (member_username_taken($clean)) {
        $suffix  = '_' . $n;
        $maxBase = 20 - strlen($suffix);
        $clean   = substr($base, 0, max(1, $maxBase)) . $suffix;
        $n++;
        if ($n > 999) break;   // 兜底，几乎不可能触发
    }
    return $clean;
}
