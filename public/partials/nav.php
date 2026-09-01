<?php
/** @var string $active @var bool $navOnDark @var string $contactHref */
$navOnDark = $navOnDark ?? false;
$contactHref = $contactHref ?? 'index.php#contact';
$links = [
  'index'      => ['首页', 'index.php'],
  'technology' => ['技术平台', 'technology.php'],
  'agent'      => ['Antibody Agent', 'agent.php'],
  'education'  => ['教育', 'education.php'],
  'forum'      => ['论坛', 'forum.php'],
  'about'      => ['了解我们', 'about.php'],
];
?>
<header class="nav<?= $navOnDark ? ' nav--on-dark' : '' ?>">
  <div class="container">
    <a class="brand" href="index.php"><span class="logo"><svg viewBox="0 0 24 24" fill="none"><path d="M7 3v4c0 2.8 2.2 5 5 5s5 2.2 5 5v4M17 3v4c0 2.8-2.2 5-5 5" stroke="white" stroke-width="2" stroke-linecap="round"/></svg></span><span>MabSeek<small>抗体求索 · 清华大学医学院</small></span></a>
    <nav class="nav-links">
<?php foreach ($links as $key => [$label, $href]): ?>
      <a href="<?= $href ?>"<?= $active === $key ? ' class="active"' : '' ?>><?= $label ?></a>
<?php endforeach; ?>
    </nav>
    <div class="nav-actions">
<?php if (!empty($_SESSION['uid'])): ?>
      <a href="account.php" class="btn btn-ghost nav-auth">👤 <?= e($_SESSION['nick'] ?? $_SESSION['uid']) ?></a>
<?php else: ?>
      <a href="login.php" class="btn btn-ghost nav-auth">登录</a>
<?php endif; ?>
      <a href="<?= e($contactHref) ?>" class="btn btn-green">联系我们</a>
    </div>
    <button class="nav-toggle" aria-label="菜单"><span></span><span></span><span></span></button>
  </div>
</header>
