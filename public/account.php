<?php
require __DIR__ . '/../app/bootstrap.php';
header('Cache-Control: no-store, must-revalidate');
member_check();                                   // 未登录跳 login.php

$me = member_find_by_username((string)($_SESSION['uid'] ?? ''));
if (!$me) { auth_logout(); redirect('login.php'); }

$msg = null; $err = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify_or_die();
    $do = (string)($_POST['do'] ?? '');

    if ($do === 'profile') {
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $nick  = trim((string)($_POST['nickname'] ?? ''));
        if (!member_validate_email($email))         $err = '邮箱格式不正确';
        elseif (!member_validate_phone($phone))     $err = '手机号格式不正确';
        elseif (!member_validate_nickname($nick))   $err = '昵称最多 30 字';
        elseif (member_email_taken($email, (int)$me['id'])) $err = '该邮箱已被占用';
        elseif (member_phone_taken($phone, (int)$me['id'])) $err = '该手机号已被占用';
        else {
            member_update_profile((int)$me['id'], $email, $phone, $nick);
            $_SESSION['nick'] = $nick !== '' ? $nick : $me['username'];
            audit('member_update_profile');
            $msg = '资料已更新';
            $me = member_get((int)$me['id']);
        }
    } elseif ($do === 'password') {
        $cur  = (string)($_POST['current'] ?? '');
        $new  = (string)($_POST['new'] ?? '');
        $new2 = (string)($_POST['confirm'] ?? '');
        if (!auth_verify_credentials($me['username'], $cur)) $err = '当前密码不正确';
        elseif (!member_validate_password($new))            $err = '新密码需 8–32 位且含字母与数字';
        elseif ($new !== $new2)                             $err = '两次输入的新密码不一致';
        elseif ($new === $cur)                              $err = '新密码不能与当前密码相同';
        else {
            member_change_password((int)$me['id'], $new);
            session_regenerate_id(true);           // 改密后刷新会话 ID
            audit('member_change_password');
            $msg = '密码已更新';
        }
    }
}
$active = ''; $navOnDark = false;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>账号中心 · MabSeek</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/partials/nav.php'; ?>
<main class="container" style="max-width:560px;margin:48px auto">
  <div style="display:flex;justify-content:space-between;align-items:center">
    <h1>账号中心</h1>
    <form method="post" action="logout.php"><?= csrf_field() ?><button class="btn btn-ghost" type="submit">退出登录</button></form>
  </div>
  <?php if ($msg): ?><p style="color:#12b98c"><?= e($msg) ?></p><?php endif; ?>
  <?php if ($err): ?><p style="color:#c0392b"><?= e($err) ?></p><?php endif; ?>

  <section style="margin-top:24px">
    <h3>基本资料</h3>
    <p style="color:#888">用户名：<?= e($me['username']) ?>（不可修改）</p>
    <form method="post" action="account.php">
      <?= csrf_field() ?>
      <input type="hidden" name="do" value="profile">
      <div class="field"><label>邮箱</label><input class="input" type="email" name="email" value="<?= e($me['email']) ?>"></div>
      <div class="field"><label>手机号</label><input class="input" type="text" name="phone" value="<?= e($me['phone']) ?>"></div>
      <div class="field"><label>昵称</label><input class="input" type="text" name="nickname" value="<?= e($me['nickname']) ?>"></div>
      <button class="btn btn-purple" type="submit">保存资料</button>
    </form>
  </section>

  <section style="margin-top:32px">
    <h3>修改密码</h3>
    <form method="post" action="account.php">
      <?= csrf_field() ?>
      <input type="hidden" name="do" value="password">
      <div class="field"><label>当前密码</label><input class="input" type="password" name="current" required autocomplete="current-password"></div>
      <div class="field"><label>新密码（8–32 位，含字母与数字）</label><input class="input" type="password" name="new" required autocomplete="new-password"></div>
      <div class="field"><label>确认新密码</label><input class="input" type="password" name="confirm" required autocomplete="new-password"></div>
      <button class="btn btn-purple" type="submit">修改密码</button>
    </form>
  </section>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
