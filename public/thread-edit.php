<?php
require __DIR__ . '/../app/bootstrap.php';
header('Cache-Control: no-store, must-revalidate');
member_check();

$me = member_find_by_username((string)($_SESSION['uid'] ?? ''));
if (!$me) { auth_logout(); redirect('login.php'); }
$uid = (int)$me['id'];

$id = (int)($_GET['id'] ?? 0);
$t = $id > 0 ? thread_get($id) : null;
// 仅作者本人；他人或不存在一律 404
if (!$t || (int)$t['user_id'] !== $uid) {
    http_response_code(404);
    $active = 'forum'; $navOnDark = false;
    include __DIR__ . '/partials/nav.php';
    echo '<main class="container" style="max-width:640px;margin:64px auto;text-align:center"><h1>帖子不存在</h1><p><a href="forum.php">返回论坛</a></p></main>';
    include __DIR__ . '/partials/footer.php';
    exit;
}

$err = null;
$in = ['category' => $t['category'], 'title' => $t['title'], 'body' => $t['body']];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify_or_die();
    $act = (string)($_POST['act'] ?? 'update');

    if ($act === 'delete') {
        thread_delete($id, $uid);
        audit('thread_delete', 'thread', (string)$id);
        redirect('forum.php');
    }

    $in['category'] = (string)($_POST['category'] ?? '');
    $in['title']    = (string)($_POST['title'] ?? '');
    $in['body']     = (string)($_POST['body'] ?? '');
    if (!thread_valid_category($in['category']))    $err = '请选择有效分类。';
    elseif (!thread_validate_title($in['title']))   $err = '标题需 1–120 字。';
    elseif (!thread_validate_body($in['body']))     $err = '正文需 1–5000 字。';
    else {
        thread_update($id, $uid, $in['category'], $in['title'], $in['body']);
        audit('thread_update', 'thread', (string)$id);
        redirect('thread.php?id=' . $id);
    }
}
$active = 'forum'; $navOnDark = false;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>编辑帖子 · MabSeek 论坛</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/partials/nav.php'; ?>
<main class="container" style="max-width:640px;margin:48px auto">
  <h1 style="margin-bottom:20px">编辑帖子</h1>
  <?php if ($err): ?><p style="color:#c0392b"><?= e($err) ?></p><?php endif; ?>
  <form method="post" action="thread-edit.php?id=<?= (int)$id ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="act" value="update">
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
      <input class="input" type="text" name="title" maxlength="120" value="<?= e($in['title']) ?>" required>
    </div>
    <div class="field">
      <label>正文（纯文本，≤5000 字）</label>
      <textarea class="input" name="body" rows="10" maxlength="5000" required><?= e($in['body']) ?></textarea>
    </div>
    <button class="btn btn-purple" type="submit">保存</button>
    <a href="thread.php?id=<?= (int)$id ?>" style="margin-left:12px">取消</a>
  </form>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
