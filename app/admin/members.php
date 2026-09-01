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
  <h3 style="margin-bottom:16px">会员管理（共 <?= count($members) ?> 人）</h3>
  <table class="admin-table">
    <thead><tr><th>ID</th><th>用户名</th><th>昵称</th><th>邮箱</th><th>手机号</th><th>状态</th><th>注册时间</th><th>操作</th></tr></thead>
    <tbody>
<?php foreach ($members as $m): ?>
      <tr>
        <td><?= (int)$m['id'] ?></td>
        <td><?= e($m['username']) ?></td>
        <td><?= e($m['nickname']) ?></td>
        <td><?= e($m['email']) ?></td>
        <td><?= e($m['phone']) ?></td>
        <td><?= $m['status'] === 'disabled' ? '<span style="color:#c0392b">已停用</span>' : '正常' ?></td>
        <td><?= e($m['created_at']) ?></td>
        <td style="display:flex;gap:6px;flex-wrap:wrap">
<?php if ($m['status'] === 'disabled'): ?>
          <form method="post" action="admin.php?m=members"><?= csrf_field() ?><input type="hidden" name="act" value="enable"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button class="abtn" type="submit">启用</button></form>
<?php else: ?>
          <form method="post" action="admin.php?m=members"><?= csrf_field() ?><input type="hidden" name="act" value="disable"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button class="abtn" type="submit">停用</button></form>
<?php endif; ?>
          <form method="post" action="admin.php?m=members" onsubmit="return confirm('确认重置该会员密码？')"><?= csrf_field() ?><input type="hidden" name="act" value="reset"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button class="abtn" type="submit">重置密码</button></form>
          <form method="post" action="admin.php?m=members" onsubmit="return confirm('确认删除该会员？不可恢复')"><?= csrf_field() ?><input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button class="abtn" type="submit" style="color:#c0392b">删除</button></form>
        </td>
      </tr>
<?php endforeach; ?>
<?php if (!$members): ?>
      <tr><td colspan="8" style="color:#888">暂无会员</td></tr>
<?php endif; ?>
    </tbody>
  </table>
</div>
