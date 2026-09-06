<?php
require __DIR__ . '/../app/bootstrap.php';
member_check();                                   // 教育为登录可见模块，详情页同样门禁

$id = (int)($_GET['id'] ?? 0);
$r  = $id > 0 ? (new Collection('edu_reviews'))->find($id) : null;
if (!$r || (int)($r['published'] ?? 0) !== 1) {   // find 不校验发布状态，此处显式校验
    http_response_code(404);
    $active = 'education'; $navOnDark = false; $navSolidDark = true;
    include __DIR__ . '/partials/nav.php';
    echo '<main class="container" style="max-width:640px;margin:64px auto;text-align:center"><h1>内容不存在</h1><p><a href="education.php">返回教育</a></p></main>';
    include __DIR__ . '/partials/footer.php';
    exit;
}
$active = 'education'; $navOnDark = false; $navSolidDark = true;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($r['title']) ?> · MabSeek 教育</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/partials/nav.php'; ?>
<main class="container" style="max-width:760px;margin:48px auto">
  <div class="breadcrumb"><a href="index.php">首页</a> / <a href="education.php">教育</a> / 往期回顾</div>
  <h1 style="margin:10px 0 12px"><?= e($r['title']) ?></h1>
<?php if (($r['summary'] ?? '') !== ''): ?>
  <p style="color:var(--ink-3);font-size:15px;margin-bottom:24px"><?= e($r['summary']) ?></p>
<?php endif; ?>
<?php if (($r['cover'] ?? '') !== ''): ?>
  <img src="<?= e($r['cover']) ?>" alt="" style="width:100%;border-radius:var(--radius);margin-bottom:24px" onerror="this.style.display='none'">
<?php endif; ?>
  <article style="line-height:1.9;color:var(--ink-2)"><?= sanitize_html($r['body']) ?></article>
  <div style="margin-top:32px"><a class="btn btn-outline" href="education.php">← 返回教育</a></div>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
