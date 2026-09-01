<?php
require __DIR__ . '/../app/bootstrap.php';
$id = (int)($_GET['id'] ?? 0);
$t = $id > 0 ? thread_get_public($id) : null;

// 当前登录会员 id（用于判断是否作者）
$myId = 0;
if (auth_check()) {
    $me = member_find_by_username((string)($_SESSION['uid'] ?? ''));
    $myId = $me ? (int)$me['id'] : 0;
}

if (!$t) {
    http_response_code(404);
    $active = 'forum'; $navOnDark = false;
    include __DIR__ . '/partials/nav.php';
    echo '<main class="container" style="max-width:640px;margin:64px auto;text-align:center"><h1>帖子不存在</h1><p><a href="forum.php">返回论坛</a></p></main>';
    include __DIR__ . '/partials/footer.php';
    exit;
}
$author = ($t['author_nickname'] ?? '') !== '' ? $t['author_nickname'] : ($t['author_username'] ?? '');
$catLabel = THREAD_CATEGORIES[$t['category']] ?? $t['category'];
$isOwner = $myId > 0 && (int)$t['user_id'] === $myId;
$active = 'forum'; $navOnDark = false;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($t['title']) ?> · MabSeek 论坛</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/partials/nav.php'; ?>
<main class="container" style="max-width:720px;margin:48px auto">
  <div class="breadcrumb"><a href="index.php">首页</a> / <a href="forum.php">论坛</a></div>
  <span class="eyebrow"><?= e($catLabel) ?></span>
  <h1 style="margin:10px 0 8px"><?= e($t['title']) ?></h1>
  <p style="color:var(--ink-3);font-size:14px;margin-bottom:24px"><?= e($author) ?> · <?= e($t['created_at']) ?></p>
  <article style="line-height:1.9;color:var(--ink-2)"><?= sanitize_html($t['body']) ?></article>
<?php if ($isOwner): ?>
  <div style="display:flex;gap:10px;margin-top:32px">
    <a class="btn btn-outline" href="thread-edit.php?id=<?= (int)$t['id'] ?>">编辑</a>
    <form method="post" action="thread-edit.php?id=<?= (int)$t['id'] ?>" onsubmit="return confirm('确认删除这篇帖子？')">
      <?= csrf_field() ?><input type="hidden" name="act" value="delete">
      <button class="btn btn-outline" type="submit" style="color:#c0392b">删除</button>
    </form>
  </div>
<?php endif; ?>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
