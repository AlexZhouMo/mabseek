<?php
require __DIR__ . '/../app/bootstrap.php';
header('Cache-Control: no-store, must-revalidate');
member_check();

$me = member_find_by_username((string)($_SESSION['uid'] ?? ''));
if (!$me) { auth_logout(); redirect('login.php'); }
$uid = (int)$me['id'];

$err = null;
$in = ['category' => 'pit', 'title' => '', 'body' => '', 'cover' => ''];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify_or_die();
    $in['category'] = (string)($_POST['category'] ?? '');
    $in['title']    = (string)($_POST['title'] ?? '');
    $in['body']     = (string)($_POST['body'] ?? '');
    $in['cover']    = trim((string)($_POST['cover'] ?? ''));
    $cleanBody      = sanitize_html($in['body']);      // 服务端权威净化

    if (($me['status'] ?? '') !== 'active')            $err = '账号已被停用，无法发帖。';
    elseif (!thread_valid_category($in['category']))   $err = '请选择有效分类。';
    elseif (!thread_validate_title($in['title']))      $err = '标题需 1–120 字。';
    elseif (!thread_validate_body($cleanBody))         $err = '正文需 1–5000 字。';
    elseif ($in['cover'] === '')                       $err = '请上传缩略图。';
    elseif (!thread_valid_cover($in['cover']))         $err = '缩略图无效，请重新上传。';
    elseif (!thread_can_post_now($uid))                $err = '发帖过于频繁，请稍后再试。';
    else {
        $tid = thread_create($uid, $in['category'], $in['title'], $cleanBody, $in['cover']);
        audit('thread_create', 'thread', (string)$tid);
        redirect('thread.php?id=' . $tid);
    }
}
$active = 'forum'; $navOnDark = false; $navSolidDark = true;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>发帖 · MabSeek 论坛</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/partials/nav.php'; ?>
<div class="auth-wrap">
  <div class="auth-card wide">
    <div class="auth-brand"><span class="logo">🧬</span><span>MabSeek</span></div>
    <div class="auth-title">发布帖子</div>
    <div class="auth-sub">分享经验、提问求助，与社区一起成长。</div>
    <?php if ($err): ?><div class="auth-error"><?= e($err) ?></div><?php endif; ?>
    <form method="post" action="thread-new.php">
      <?= csrf_field() ?>
      <div class="field">
        <label>分类</label>
        <select class="input" name="category" required>
<?php foreach (THREAD_CATEGORIES as $k => $label): ?>
          <option value="<?= e($k) ?>"<?= $in['category'] === $k ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>标题（≤120 字）</label>
        <input class="input" type="text" name="title" maxlength="120" value="<?= e($in['title']) ?>" required autofocus>
      </div>
      <div class="field">
        <label>缩略图（必填）</label>
<?php $coverName = 'cover'; $coverValue = $in['cover']; include __DIR__ . '/partials/cover-field.php'; ?>
      </div>
      <div class="field">
        <label>正文（支持富文本，≤5000 字）</label>
<?php
        $rtName = 'body'; $rtValue = $in['body']; $rtUploadUrl = 'upload.php'; $rtRequired = true;
        include __DIR__ . '/partials/richtext-field.php';
?>
      </div>
      <button class="btn btn-purple auth-submit" type="submit">发布</button>
    </form>
    <div class="auth-alt"><a href="forum.php">取消，返回论坛</a></div>
  </div>
</div>
<script src="assets/js/richtext.js" defer></script>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
