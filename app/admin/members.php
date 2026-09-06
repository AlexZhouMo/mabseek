<?php
// 后台会员管理（C）：仅操作 role='member'；不显示 password_hash；无 role 修改入口。
// 动作均 POST + CSRF，且已处于 admin.php 的 auth_is_admin() 硬闸之内。

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    while (ob_get_level() > 0) { ob_end_clean(); }
    csrf_verify_or_die();
    $act = (string)($_POST['act'] ?? '');
    $id  = (int)($_POST['id'] ?? 0);
    $target = $id > 0 ? member_get($id) : null;

    if (!$target || ($target['role'] ?? '') !== 'member') {
        flash_set('error', '目标会员不存在');
        redirect('admin.php?m=members');
    }

    if ($act === 'disable') {
        member_set_status($id, 'disabled');
        audit('member_disable', 'user', (string)$id);
        flash_set('ok', '已停用会员：' . $target['username']);
    } elseif ($act === 'enable') {
        member_set_status($id, 'active');
        audit('member_enable', 'user', (string)$id);
        flash_set('ok', '已启用会员：' . $target['username']);
    } elseif ($act === 'delete') {
        member_delete($id);
        audit('member_delete', 'user', (string)$id);
        flash_set('ok', '已删除会员：' . $target['username']);
    } elseif ($act === 'reset') {
        $tmp = member_reset_password($id);
        audit('member_reset_password', 'user', (string)$id);
        // 临时密码仅本次展示一次（flash 存 session，随下次渲染即清；不落库明文、不入日志）
        flash_set('warn', "已重置「{$target['username']}」的密码，临时密码：{$tmp}（请立即转交并让其登录后修改）");
    } else {
        flash_set('error', '未知操作');
    }
    redirect('admin.php?m=members');
}

$members = member_list();
?>
<div class="card">
  <div class="page-head">
    <h3>会员管理</h3>
    <span class="page-head-meta">共 <?= count($members) ?> 人</span>
  </div>
  <div class="table-wrap">
  <table class="data-table">
    <thead><tr><th>ID</th><th>用户名</th><th>昵称</th><th>邮箱</th><th>手机号</th><th>状态</th><th>注册时间</th><th class="col-actions">操作</th></tr></thead>
    <tbody>
<?php foreach ($members as $m): ?>
      <tr>
        <td><?= (int)$m['id'] ?></td>
        <td><span class="col-primary"><?= e($m['username']) ?></span></td>
        <td><?= e($m['nickname']) ?></td>
        <td><?= e($m['email']) ?></td>
        <td><?= e($m['phone']) ?></td>
        <td><?= $m['status'] === 'disabled' ? '<span class="tag tag-danger">已停用</span>' : '<span class="tag">正常</span>' ?></td>
        <td><?= e($m['created_at']) ?></td>
        <td class="col-actions">
          <span class="row-actions">
<?php if ($m['status'] === 'disabled'): ?>
          <form method="post" action="admin.php?m=members" style="display:inline;margin:0"><?= csrf_field() ?><input type="hidden" name="act" value="enable"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button class="abtn abtn-sm abtn-default" type="submit">启用</button></form>
<?php else: ?>
          <form method="post" action="admin.php?m=members" style="display:inline;margin:0"><?= csrf_field() ?><input type="hidden" name="act" value="disable"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button class="abtn abtn-sm abtn-default" type="submit">停用</button></form>
<?php endif; ?>
          <form method="post" action="admin.php?m=members" style="display:inline;margin:0" onsubmit="return confirm('确认重置该会员密码？')"><?= csrf_field() ?><input type="hidden" name="act" value="reset"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button class="abtn abtn-sm abtn-default" type="submit">重置密码</button></form>
          <form method="post" action="admin.php?m=members" style="display:inline;margin:0" onsubmit="return confirm('确认删除该会员？不可恢复')"><?= csrf_field() ?><input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button class="abtn abtn-sm abtn-danger" type="submit">删除</button></form>
          </span>
        </td>
      </tr>
<?php endforeach; ?>
<?php if (!$members): ?>
      <tr><td colspan="8" class="empty">暂无会员</td></tr>
<?php endif; ?>
    </tbody>
  </table>
  </div>
</div>
