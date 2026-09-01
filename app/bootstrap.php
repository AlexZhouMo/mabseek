<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/members.php';
require_once __DIR__ . '/threads.php';
require_once __DIR__ . '/captcha.php';
require_once __DIR__ . '/repositories/Collection.php';
require_once __DIR__ . '/repositories/Snippets.php';

// 运行时错误策略：生产不外泄错误，仅写日志
if (APP_DEBUG) {
    error_reporting(E_ALL); ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL); ini_set('display_errors', '0'); ini_set('log_errors', '1');
}

// 安全会话（仅 web 请求，CLI 跳过）
if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    // 会话加固：仅 Cookie 传递、拒绝外部注入的会话 ID（防会话固定/采纳）
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'domain' => '',
        'secure' => $https, 'httponly' => true, 'samesite' => 'Strict',
    ]);
    session_start();
}

// 预热 DB（触发建表）
db();
