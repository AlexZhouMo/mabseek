<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

/** 读取 SciencePal 配置：环境变量优先，其次 config.local.php 常量。未配置密钥则 enabled=false。 */
function sciencepal_config(): array {
    $key  = getenv('SCIENCEPAL_PARTNER_KEY') ?: (defined('SCIENCEPAL_PARTNER_KEY') ? SCIENCEPAL_PARTNER_KEY : '');
    $base = getenv('SCIENCEPAL_API_BASE') ?: (defined('SCIENCEPAL_API_BASE') ? SCIENCEPAL_API_BASE : 'https://sciencepal.ai/api/partner');
    $base = rtrim((string)$base, '/');
    return ['enabled' => $key !== '', 'key' => (string)$key, 'base' => $base];
}

/** 底层请求：curl + X-Partner-Key + JSON。失败不抛异常，统一返回结构。密钥/密码不入日志。 */
function sciencepal_request(string $method, string $path, array $payload): array {
    $cfg = sciencepal_config();
    if (!$cfg['enabled']) return ['ok' => false, 'http' => 0, 'body' => [], 'error' => 'disabled'];
    $url = $cfg['base'] . $path;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'X-Partner-Key: ' . $cfg['key']],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $raw  = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $cerr = curl_error($ch);
    curl_close($ch);
    if ($raw === false) return ['ok' => false, 'http' => 0, 'body' => [], 'error' => 'network:' . $cerr];
    $body = json_decode((string)$raw, true);
    if (!is_array($body)) $body = [];
    return ['ok' => $http >= 200 && $http < 300, 'http' => $http, 'body' => $body, 'error' => ''];
}

/** 开通账号 POST /users。返回 status: created|exists|error（+http）。 */
function sciencepal_provision(string $email, string $password): array {
    $r = sciencepal_request('POST', '/users', ['email' => $email, 'password' => $password]);
    if (!$r['ok']) {
        return ['status' => 'error', 'http' => $r['http'], 'error' => $r['error'] ?: ('http ' . $r['http'])];
    }
    $status = (string)($r['body']['status'] ?? '');
    if ($status === 'created') return ['status' => 'created', 'http' => $r['http'], 'user_id' => (string)($r['body']['user_id'] ?? '')];
    if ($status === 'exists')  return ['status' => 'exists',  'http' => $r['http']];
    return ['status' => 'error', 'http' => $r['http'], 'error' => 'unexpected:' . $status];
}

/** 同步密码 PUT /users/password。返回 status: updated|notfound|forbidden|error（+http）。 */
function sciencepal_sync_password(string $email, string $newPassword): array {
    $r = sciencepal_request('PUT', '/users/password', ['email' => $email, 'new_password' => $newPassword]);
    if ($r['ok'] && (string)($r['body']['status'] ?? '') === 'updated') return ['status' => 'updated', 'http' => $r['http']];
    if ($r['http'] === 404) return ['status' => 'notfound', 'http' => 404];
    if ($r['http'] === 403) return ['status' => 'forbidden', 'http' => 403];
    return ['status' => 'error', 'http' => $r['http'], 'error' => $r['error'] ?: ('http ' . $r['http'])];
}

// ── 同步状态仓储 ──
function scp_sync_get(int $userId): ?array {
    $q = db()->prepare('SELECT * FROM sciencepal_sync WHERE user_id = ? LIMIT 1');
    $q->execute([$userId]);
    return $q->fetch() ?: null;
}
/** upsert：存在则更新 status/last_error/attempts+1/synced_at，否则插入。 */
function scp_sync_upsert(int $userId, string $email, string $status, string $error = ''): void {
    $now = iso_now();
    $syncedAt = in_array($status, ['created', 'exists', 'updated'], true) ? $now : '';
    $existing = scp_sync_get($userId);
    if ($existing) {
        db()->prepare('UPDATE sciencepal_sync SET email=?, status=?, last_error=?, attempts=attempts+1, synced_at=CASE WHEN ?<>\'\' THEN ? ELSE synced_at END, updated_at=? WHERE user_id=?')
            ->execute([$email, $status, $error, $syncedAt, $syncedAt, $now, $userId]);
    } else {
        db()->prepare('INSERT INTO sciencepal_sync(user_id,email,status,last_error,attempts,synced_at,created_at,updated_at) VALUES(?,?,?,?,1,?,?,?)')
            ->execute([$userId, $email, $status, $error, $syncedAt, $now, $now]);
    }
}
