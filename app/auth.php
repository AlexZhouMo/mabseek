<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function auth_verify_credentials(string $user, string $pass): bool {
    $row = db()->prepare('SELECT password_hash FROM users WHERE username = ? COLLATE NOCASE LIMIT 1');
    $row->execute([$user]);
    $hash = $row->fetchColumn();
    if ($hash === false) {
        password_verify($pass, '$argon2id$v=19$m=65536,t=4,p=1$AAAAAAAAAAAAAAAA$AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'); // 时间恒定，防用户枚举
        return false;
    }
    return password_verify($pass, (string)$hash);
}

function auth_record_attempt(string $ip, string $user, bool $success): void {
    if ($success) {
        // 成功登录清零该 (IP,用户) 的历史失败，实现「连续失败」语义
        db()->prepare('DELETE FROM login_attempts WHERE ip = ? AND username = ? AND success = 0')
            ->execute([$ip, $user]);
    }
    db()->prepare('INSERT INTO login_attempts(ip,username,success,created_at) VALUES(?,?,?,?)')
        ->execute([$ip, $user, $success ? 1 : 0, iso_now()]);
}

function auth_is_locked(string $ip, string $user): bool {
    $since = date('c', time() - LOGIN_FAIL_WINDOW);
    // ① 单 IP 全局失败数：防攻击者轮换用户名规避 (IP,用户) 锁定，兼防 Argon2id 资源耗尽
    $qi = db()->prepare(
        'SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND success = 0 AND created_at >= ?'
    );
    $qi->execute([$ip, $since]);
    if ((int)$qi->fetchColumn() >= LOGIN_IP_MAX_FAILS) return true;
    // ② 单 (IP,用户) 失败数
    $q = db()->prepare(
        'SELECT COUNT(*) FROM login_attempts
         WHERE ip = ? AND username = ? AND success = 0 AND created_at >= ?'
    );
    $q->execute([$ip, $user, $since]);
    return (int)$q->fetchColumn() >= LOGIN_MAX_FAILS;
}

function auth_login_user(string $user): void {
    session_regenerate_id(true);               // 防会话固定
    $_SESSION['uid']  = $user;
    $_SESSION['role'] = auth_user_role($user);
    $_SESSION['ua']   = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200);
    $_SESSION['last'] = time();
    $_SESSION['born'] = time();
}

function auth_user_role(string $user): string {
    $q = db()->prepare('SELECT role FROM users WHERE username = ? COLLATE NOCASE LIMIT 1');
    $q->execute([$user]);
    $r = $q->fetchColumn();
    return $r === false ? '' : (string)$r;
}

function auth_is_admin(): bool {
    return ($_SESSION['role'] ?? '') === 'admin';
}

/** 会员页硬闸：未登录跳前台登录页；不校验 role（管理员亦可访问自己的账号页） */
function member_check(): void {
    if (!auth_check()) redirect('login.php');
}

function auth_logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function auth_check(): bool {
    if (!isset($_SESSION['uid']) || $_SESSION['uid'] === '') return false;
    $now = time();
    if ($now - ($_SESSION['last'] ?? 0) > IDLE_TIMEOUT)     { auth_logout(); return false; }
    if ($now - ($_SESSION['born'] ?? 0) > ABSOLUTE_TIMEOUT) { auth_logout(); return false; }
    if (($_SESSION['ua'] ?? '') !== substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200)) {
        auth_logout(); return false;
    }
    $_SESSION['last'] = $now;
    return true;
}

function audit(string $action, string $entity = '', string $entityId = ''): void {
    db()->prepare('INSERT INTO audit_log(user,action,entity,entity_id,ip,created_at) VALUES(?,?,?,?,?,?)')
        ->execute([$_SESSION['uid'] ?? '-', $action, $entity, $entityId, client_ip(), iso_now()]);
}

function auth_must_change_password(string $user): bool {
    $q = db()->prepare('SELECT must_change_password FROM users WHERE username = ? COLLATE NOCASE LIMIT 1');
    $q->execute([$user]);
    return (int)$q->fetchColumn() === 1;
}

function auth_change_password(string $user, string $newPass): void {
    db()->prepare('UPDATE users SET password_hash = ?, must_change_password = 0, updated_at = ? WHERE username = ? COLLATE NOCASE')
        ->execute([password_hash($newPass, PASSWORD_ARGON2ID), iso_now(), $user]);
}
