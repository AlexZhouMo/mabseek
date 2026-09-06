<?php
/** @var string $active @var bool $navOnDark @var string $contactHref */
$navOnDark = $navOnDark ?? false;
$navSolidDark = $navSolidDark ?? false;
$contactHref = $contactHref ?? 'index.php#contact';
$links = [
  'index'      => ['首页', 'index.php'],
  'technology' => ['技术平台', 'technology.php'],
  'agent'      => ['Antibody Agent', 'agent.php'],
];
if (!empty($_SESSION['uid'])) {                    // 教育/论坛仅登录可见
  $links['education'] = ['教育', 'education.php'];
  $links['forum']     = ['论坛', 'forum.php'];
}
$links['about'] = ['了解我们', 'about.php'];
?>
<header class="nav<?= ($navOnDark || $navSolidDark) ? ' nav--on-dark' : '' ?><?= $navSolidDark ? ' nav--solid-dark' : '' ?>">
  <div class="container">
    <a class="brand" href="index.php"><img class="brand-logo" src="assets/images/logo.webp" alt="MabSeek 抗体求索 · 清华大学医学院"></a>
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
