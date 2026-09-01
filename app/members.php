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
    if ($v === '') return true;
    return mb_strlen($v) <= 254 && filter_var($v, FILTER_VALIDATE_EMAIL) !== false;
}
function member_validate_phone(string $v): bool {
    if ($v === '') return true;
    return (bool)preg_match('/^1[3-9]\d{9}$/', $v);
}
function member_validate_nickname(string $v): bool {
    return mb_strlen($v) <= 30;
}
