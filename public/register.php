<?php
require __DIR__ . '/../app/bootstrap.php';
header('Cache-Control: no-store, must-revalidate');

// 已登录直接去账号页
if (auth_check()) redirect('account.php');

$errors = [];
$in = ['username'=>'', 'email'=>'', 'phone'=>'', 'nickname'=>''];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify_or_die();
    // 蜜罐：正常用户留空，非空即机器人 → 静默拒绝
    if (trim((string)($_POST['website'] ?? '')) !== '') { redirect('login.php'); }

    $in['username'] = trim((string)($_POST['username'] ?? ''));
    $pass           = (string)($_POST['password'] ?? '');
    $in['email']    = trim((string)($_POST['email'] ?? ''));
    $in['phone']    = trim((string)($_POST['phone'] ?? ''));
    $in['nickname'] = trim((string)($_POST['nickname'] ?? ''));

    if (!captcha_answer_ok((string)($_POST['captcha'] ?? ''))) $errors['captcha'] = '验证码错误或已过期';
    if (!member_validate_username($in['username']))           $errors['username'] = '用户名需 3–20 位字母/数字/下划线';
    if (!member_validate_password($pass))                     $errors['password'] = '密码需 8–32 位且含字母与数字';
    if (!member_validate_email($in['email']))                 $errors['email']    = '邮箱格式不正确';
    if (!member_validate_phone($in['phone']))                 $errors['phone']    = '手机号格式不正确';
    if (!member_validate_nickname($in['nickname']))           $errors['nickname'] = '昵称最多 30 字';

    if (!$errors) {
        if (member_username_taken($in['username'])) $errors['username'] = '该用户名已被注册';
        if (member_email_taken($in['email']))       $errors['email']    = '该邮箱已被注册';
        if (member_phone_taken($in['phone']))       $errors['phone']    = '该手机号已被注册';
    }

    if (!$errors) {
        member_register($in['username'], $pass, $in['email'], $in['phone'], $in['nickname']);
        auth_login_user($in['username']);            // 注册成功自动登录
        $_SESSION['nick'] = $in['nickname'] !== '' ? $in['nickname'] : $in['username'];
        audit('member_register', 'user', $in['username']);
        redirect('account.php');
    }
}
$active = ''; $navOnDark = false;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>注册 · MabSeek</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/partials/nav.php'; ?>
<main class="container" style="max-width:460px;margin:48px auto">
  <h1 style="margin-bottom:20px">注册会员</h1>
  <form method="post" action="register.php" autocomplete="off">
    <?= csrf_field() ?>
    <div style="position:absolute;left:-9999px" aria-hidden="true">
      <label>请勿填写<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
    </div>
    <div class="field">
      <label>用户名</label>
      <input class="input" type="text" name="username" value="<?= e($in['username']) ?>" required>
      <?php if (isset($errors['username'])): ?><small style="color:#c0392b"><?= e($errors['username']) ?></small><?php endif; ?>
    </div>
    <div class="field">
      <label>密码（8–32 位，含字母与数字）</label>
      <input class="input" type="password" name="password" required autocomplete="new-password">
      <?php if (isset($errors['password'])): ?><small style="color:#c0392b"><?= e($errors['password']) ?></small><?php endif; ?>
    </div>
    <div class="field">
      <label>邮箱（可选）</label>
      <input class="input" type="email" name="email" value="<?= e($in['email']) ?>">
      <?php if (isset($errors['email'])): ?><small style="color:#c0392b"><?= e($errors['email']) ?></small><?php endif; ?>
    </div>
    <div class="field">
      <label>手机号（可选）</label>
      <input class="input" type="text" name="phone" value="<?= e($in['phone']) ?>">
      <?php if (isset($errors['phone'])): ?><small style="color:#c0392b"><?= e($errors['phone']) ?></small><?php endif; ?>
    </div>
    <div class="field">
      <label>昵称（可选）</label>
      <input class="input" type="text" name="nickname" value="<?= e($in['nickname']) ?>">
      <?php if (isset($errors['nickname'])): ?><small style="color:#c0392b"><?= e($errors['nickname']) ?></small><?php endif; ?>
    </div>
    <div class="field">
      <label>验证码</label>
      <div style="display:flex;gap:10px;align-items:center">
        <input class="input" type="text" name="captcha" required style="flex:1">
        <img src="captcha.php" alt="验证码" onclick="this.src='captcha.php?'+Date.now()" style="cursor:pointer;height:40px" title="点击刷新">
      </div>
      <?php if (isset($errors['captcha'])): ?><small style="color:#c0392b"><?= e($errors['captcha']) ?></small><?php endif; ?>
    </div>
    <button class="btn btn-purple" type="submit">注册并登录</button>
    <p style="margin-top:14px">已有账号？<a href="login.php">去登录</a></p>
  </form>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
