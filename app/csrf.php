<?php
declare(strict_types=1);
require_once __DIR__ . '/helpers.php';  // csrf_field() 依赖 e()；解除加载顺序耦合

function csrf_token(): string {
    if (empty($_SESSION['__csrf'])) {
        $_SESSION['__csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['__csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}
function csrf_check(?string $token): bool {
    return is_string($token) && !empty($_SESSION['__csrf'])
        && hash_equals($_SESSION['__csrf'], $token);
}
function csrf_verify_or_die(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !csrf_check($_POST['_csrf'] ?? null)) {
        http_response_code(403);
        exit('CSRF 校验失败');
    }
}
