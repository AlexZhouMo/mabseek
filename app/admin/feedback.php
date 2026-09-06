<?php
// 后台联系反馈查看：只读列表 + 标记已处理/未处理 + 删除。
// 动作均 POST + CSRF，且已处于 admin.php 的 auth_is_admin() 硬闸之内。

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    while (ob_get_level() > 0) { ob_end_clean(); }
    csrf_verify_or_die();
    $act = (string)($_POST['act'] ?? '');
    $id  = (int)($_POST['id'] ?? 0);
    $target = $id > 0 ? feedback_get($id) : null;

    if (!$target) {
        flash_set('error', '目标反馈不存在');
        redirect('admin.php?m=feedback');
    }

    if ($act === 'done') {
        feedback_set_status($id, 'done');
        audit('feedback_done', 'feedback', (string)$id);
        flash_set('ok', '已标记为已处理');
    } elseif ($act === 'reopen') {
        feedback_set_status($id, 'new');
        audit('feedback_reopen', 'feedback', (string)$id);
        flash_set('ok', '已标记为未处理');
    } elseif ($act === 'delete') {
        feedback_delete($id);
        audit('feedback_delete', 'feedback', (string)$id);
        flash_set('ok', '已删除该反馈');
    } else {
        flash_set('error', '未知操作');
    }
    redirect('admin.php?m=feedback');
}

$rows = feedback_list();
$newCount = 0;
foreach ($rows as $r) if (($r['status'] ?? '') !== 'done') $newCount++;
?>
<div class="card">
  <div class="page-head">
    <h3>联系反馈</h3>
    <span class="page-head-meta">共 <?= count($rows) ?> 条 · 未处理 <?= $newCount ?> 条</span>
  </div>
  <div class="table-wrap">
  <table class="data-table">
    <thead><tr><th>ID</th><th>称呼</th><th>邮箱</th><th>内容</th><th>状态</th><th>IP</th><th>时间</th><th class="col-actions">操作</th></tr></thead>
    <tbody>
<?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><span class="col-primary"><?= e($r['name']) ?></span></td>
        <td><?= e($r['email']) ?></td>
        <td style="max-width:360px;white-space:pre-wrap;word-break:break-word"><?= e($r['message']) ?></td>
        <td><?= ($r['status'] ?? '') === 'done' ? '<span class="tag">已处理</span>' : '<span class="tag tag-danger">未处理</span>' ?></td>
        <td><?= e($r['ip']) ?></td>
        <td><?= e($r['created_at']) ?></td>
        <td class="col-actions">
          <span class="row-actions">
<?php if (($r['status'] ?? '') === 'done'): ?>
          <form method="post" action="admin.php?m=feedback" style="display:inline;margin:0"><?= csrf_field() ?><input type="hidden" name="act" value="reopen"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="abtn abtn-sm abtn-default" type="submit">标为未处理</button></form>
<?php else: ?>
          <form method="post" action="admin.php?m=feedback" style="display:inline;margin:0"><?= csrf_field() ?><input type="hidden" name="act" value="done"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="abtn abtn-sm abtn-default" type="submit">标为已处理</button></form>
<?php endif; ?>
          <form method="post" action="admin.php?m=feedback" style="display:inline;margin:0" onsubmit="return confirm('确认删除该反馈？不可恢复')"><?= csrf_field() ?><input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="abtn abtn-sm abtn-danger" type="submit">删除</button></form>
          </span>
        </td>
      </tr>
<?php endforeach; ?>
<?php if (!$rows): ?>
      <tr><td colspan="8" class="empty">暂无反馈</td></tr>
<?php endif; ?>
    </tbody>
  </table>
  </div>
</div>
