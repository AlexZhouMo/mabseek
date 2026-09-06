<?php
// 后台论坛发帖治理：下架/恢复/删除任意帖。均 POST + CSRF，已在 admin.php 的 auth_is_admin() 硬闸内。

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    while (ob_get_level() > 0) { ob_end_clean(); }
    csrf_verify_or_die();
    $act = (string)($_POST['act'] ?? '');
    $id  = (int)($_POST['id'] ?? 0);
    $target = $id > 0 ? thread_get($id) : null;

    if (!$target) {
        flash_set('error', '目标帖子不存在');
        redirect('admin.php?m=threads');
    }
    if ($act === 'hide') {
        thread_set_status($id, 'hidden');
        audit('thread_hide', 'thread', (string)$id);
        flash_set('ok', '已下架帖子 #' . $id);
    } elseif ($act === 'show') {
        thread_set_status($id, 'published');
        audit('thread_show', 'thread', (string)$id);
        flash_set('ok', '已恢复帖子 #' . $id);
    } elseif ($act === 'delete') {
        thread_admin_delete($id);
        audit('thread_admin_delete', 'thread', (string)$id);
        flash_set('ok', '已删除帖子 #' . $id);
    } else {
        flash_set('error', '未知操作');
    }
    redirect('admin.php?m=threads');
}

$threads = thread_admin_list();
?>
<div class="card">
  <div class="page-head">
    <h3>论坛发帖</h3>
    <span class="page-head-meta">共 <?= count($threads) ?> 篇</span>
  </div>
  <div class="table-wrap">
  <table class="data-table">
    <thead><tr><th>ID</th><th>标题</th><th>作者</th><th>分类</th><th>状态</th><th>时间</th><th class="col-actions">操作</th></tr></thead>
    <tbody>
<?php foreach ($threads as $t):
      $author = ($t['author_nickname'] ?? '') !== '' ? $t['author_nickname'] : ($t['author_username'] ?? '（已注销）');
      $catLabel = THREAD_CATEGORIES[$t['category']] ?? $t['category'];
?>
      <tr>
        <td><?= (int)$t['id'] ?></td>
        <td><a href="thread.php?id=<?= (int)$t['id'] ?>" target="_blank" class="col-primary"><?= e($t['title']) ?></a></td>
        <td><?= e($author) ?></td>
        <td><span class="tag"><?= e($catLabel) ?></span></td>
        <td><?= $t['status'] === 'hidden' ? '<span class="tag tag-danger">已下架</span>' : '<span class="tag">公开</span>' ?></td>
        <td><?= e($t['created_at']) ?></td>
        <td class="col-actions">
          <span class="row-actions">
<?php if ($t['status'] === 'hidden'): ?>
          <form method="post" action="admin.php?m=threads" style="display:inline;margin:0"><?= csrf_field() ?><input type="hidden" name="act" value="show"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><button class="abtn abtn-sm abtn-default" type="submit">恢复</button></form>
<?php else: ?>
          <form method="post" action="admin.php?m=threads" style="display:inline;margin:0"><?= csrf_field() ?><input type="hidden" name="act" value="hide"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><button class="abtn abtn-sm abtn-default" type="submit">下架</button></form>
<?php endif; ?>
          <form method="post" action="admin.php?m=threads" style="display:inline;margin:0" onsubmit="return confirm('确认删除该帖？不可恢复')"><?= csrf_field() ?><input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><button class="abtn abtn-sm abtn-danger" type="submit">删除</button></form>
          </span>
        </td>
      </tr>
<?php endforeach; ?>
<?php if (!$threads): ?>
      <tr><td colspan="7" class="empty">暂无帖子</td></tr>
<?php endif; ?>
    </tbody>
  </table>
  </div>
</div>
