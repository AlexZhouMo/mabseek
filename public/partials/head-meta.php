<?php
/**
 * 输出前端 JS 需要的 session meta 标签。
 * - user-logged-in: 0 未登录 / 1 已登录
 * - user-email: 已登录用户邮箱（未登录为空串）
 * 使用方式：各前端页面 <head> 内 include。
 */
?>
<meta name="user-logged-in" content="<?= !empty($_SESSION['uid']) ? '1' : '0' ?>">
<meta name="user-email" content="<?= e((string)($_SESSION['email'] ?? '')) ?>">
