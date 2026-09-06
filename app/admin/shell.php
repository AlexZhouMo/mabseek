<?php
/** @var string $module (set by admin.php) */
$module = $module ?? 'dashboard';
$adminMenu = [
    '_top' => ['dashboard' => '仪表盘'],
    '内容' => ['news' => '新闻与活动', 'cards' => '内容卡片', 'snippets' => '文案片段', 'team' => '团队成员', 'partners' => '合作伙伴'],
    '教育' => ['edu_reviews' => '往期回顾'],
    '论坛' => ['threads' => '帖子管理'],
    '系统' => ['members' => '会员管理', 'feedback' => '联系反馈', 'password' => '修改密码'],
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>MabSeek 管理后台</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="admin">
<header class="admin-topbar">
  <div class="admin-brand">
    <span class="logo"><svg viewBox="0 0 24 24" fill="none"><path d="M7 3v4c0 2.8 2.2 5 5 5s5 2.2 5 5v4M17 3v4c0 2.8-2.2 5-5 5" stroke="var(--purple)" stroke-width="2" stroke-linecap="round"/></svg></span>
    <span>MabSeek 管理后台</span>
  </div>
  <div class="admin-user">
    <span class="who"><?= e($_SESSION['uid']) ?></span>
    <form method="post" action="admin.php?action=logout" class="logout-form">
      <?= csrf_field() ?>
      <button type="submit">退出</button>
    </form>
  </div>
</header>
<div class="admin-body">
  <nav class="admin-sidebar">
<?php foreach ($adminMenu as $group => $items): ?>
<?php if ($group !== '_top'): ?>
    <div class="nav-group-title"><?= e($group) ?></div>
<?php endif; ?>
<?php foreach ($items as $key => $label): ?>
    <a href="admin.php?m=<?= $key ?>"<?= $module === $key ? ' class="on"' : '' ?>><?= e($label) ?></a>
<?php endforeach; ?>
<?php endforeach; ?>
  </nav>
  <main class="admin-main">
    <div class="container">
<?php foreach (flash_take() as $f): ?>
      <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
<?php endforeach; ?>
