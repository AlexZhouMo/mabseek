<?php
require __DIR__ . '/../app/bootstrap.php';
$active = 'about'; $navOnDark = false; $navSolidDark = true; $contactHref = 'index.php#contact';

$id   = (int)($_GET['id'] ?? 0);
$rows = $id > 0 ? (new Collection('news'))->published('id = ?', [$id]) : [];
$item = $rows[0] ?? null;

$tagMap = ['res'=>'科研类','edu'=>'育人类','daily'=>'日常活动'];
if ($item === null) {
    http_response_code(404);
    $pageTitle = '未找到该新闻 · MabSeek 抗体求索';
} else {
    $pageTitle = $item['title'] . ' · MabSeek 抗体求索';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<?php if ($item !== null): ?>
<meta name="description" content="<?= e($item['summary']) ?>">
<?php else: ?>
<meta name="robots" content="noindex">
<?php endif; ?>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🧬</text></svg>">
</head>
<body>

<?php include __DIR__ . '/partials/nav.php'; ?>

<?php if ($item === null): ?>
<section class="page-hero">
  <div class="container">
    <div class="breadcrumb"><a href="index.php">首页</a> / <a href="about.php#news">新闻与活动</a> / 未找到</div>
    <h1>未找到该新闻</h1>
    <p class="lead">该新闻可能已下线或不存在。</p>
    <p style="margin-top:18px"><a href="about.php#news" class="btn btn-outline">返回新闻与活动</a></p>
  </div>
</section>
<?php else:
    $tagText  = $tagMap[$item['category']] ?? '';
    $tagClass = $item['category'] === 'res' ? 'tag green' : 'tag';
    $body = (string)($item['body'] ?? '');
?>
<article class="section">
  <div class="container" style="max-width:820px">
    <div class="breadcrumb reveal"><a href="index.php">首页</a> / <a href="about.php#news">新闻与活动</a></div>
    <div class="reveal" style="margin:14px 0 10px">
      <?php if ($tagText !== ''): ?><span class="<?= $tagClass ?>" style="font-size:12px"><?= e($tagText) ?></span><?php endif; ?>
      <span style="color:var(--ink-3);font-size:13px;margin-left:10px"><?= e($item['date_ym']) ?> · <?= e($item['date_day']) ?></span>
    </div>
    <h1 class="reveal d1" style="font-size:30px;line-height:1.3"><?= e($item['title']) ?></h1>
<?php if (!empty($item['image'])): ?>
    <img class="reveal d2" src="<?= e($item['image']) ?>" alt="<?= e($item['title']) ?>" style="width:100%;border-radius:var(--radius-lg);margin:22px 0;box-shadow:var(--sh)">
<?php endif; ?>
    <div class="article-body reveal d2">
<?php if (trim($body) !== ''): ?>
      <?= $body /* 已在写入时净化，直出 */ ?>
<?php else: ?>
      <p><?= e($item['summary']) ?></p>
<?php endif; ?>
    </div>
    <div style="margin-top:34px"><a href="about.php#news" class="link-more">← 返回新闻与活动</a></div>
  </div>
</article>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>
