<?php
// 修改密码：验证当前密码 → 校验新密码（≥10 位、两次一致、异于旧密码）→ Argon2id 重哈希
// （auth_change_password 同时清除强制改密标记）→ session_regenerate_id → 审计 → PRG。
// 由 admin.php 在外壳输出后 include（勿加 declare / 勿 require）。首次登录强制改密也走此模块。
// 安全：任何失败仅回显文案提示，绝不回填密码字段（密码不进入 old/session）。

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    while (ob_get_level() > 0) { ob_end_clean(); }   // 丢弃外壳缓冲，稍后干净重定向
    csrf_verify_or_die();
    $uid  = (string)($_SESSION['uid'] ?? '');
    $cur  = (string)($_POST['current'] ?? '');
    $new  = (string)($_POST['new'] ?? '');
    $new2 = (string)($_POST['confirm'] ?? '');
    $len  = function_exists('mb_strlen') ? mb_strlen($new) : strlen($new);

    $err = null;
    if (!auth_verify_credentials($uid, $cur)) {
        $err = '当前密码不正确';
    } elseif ($len < 10) {
        $err = '新密码长度至少 10 位';
    } elseif ($new !== $new2) {
        $err = '两次输入的新密码不一致';
    } elseif ($new === $cur) {
        $err = '新密码不能与当前密码相同';
    }

    if ($err !== null) {
        flash_set('error', $err);
        redirect('admin.php?m=password');
    }

    auth_change_password($uid, $new);
    session_regenerate_id(true);          // 改密后刷新会话 ID，防会话固定
    audit('change_password');
    flash_set('ok', '密码已更新');
    redirect('admin.php?m=dashboard');
}

$mustChange = auth_must_change_password((string)($_SESSION['uid'] ?? ''));
?>
<div class="card" style="max-width:480px">
  <h3 style="margin-bottom:16px">修改密码</h3>
<?php if ($mustChange): ?>
  <p class="notice">首次登录请先修改初始密码，再使用其他功能。</p>
<?php endif; ?>
  <form method="post" action="admin.php?m=password">
    <?= csrf_field() ?>
    <div class="field">
      <label class="field-label">当前密码</label>
      <input type="password" name="current" class="input" required autocomplete="current-password">
    </div>
    <div class="field">
      <label class="field-label">新密码（至少 10 位）</label>
      <input type="password" name="new" minlength="10" class="input" required autocomplete="new-password">
    </div>
    <div class="field">
      <label class="field-label">确认新密码</label>
      <input type="password" name="confirm" minlength="10" class="input" required autocomplete="new-password">
    </div>
    <button class="abtn abtn-primary" type="submit">保存</button>
  </form>
</div>
