<?php
declare(strict_types=1);

function e(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function nl2br_e(?string $s): string {
    return nl2br(e($s));
}
function iso_now(): string {
    return date('c');
}
function client_ip(): string {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
function redirect(string $to): never {
    header('Location: ' . $to);
    exit;
}
function flash_set(string $type, string $msg): void {
    $_SESSION['__flash'][] = ['type' => $type, 'msg' => $msg];
}
function flash_take(): array {
    $f = $_SESSION['__flash'] ?? [];
    unset($_SESSION['__flash']);
    return $f;
}
function old(string $key, string $default = ''): string {
    return (string)($_SESSION['__old'][$key] ?? $default);
}
function old_set(array $data): void { $_SESSION['__old'] = $data; }
function old_clear(): void { unset($_SESSION['__old']); }
