<?php
require __DIR__ . '/../app/bootstrap.php';
header('Cache-Control: no-store, must-revalidate');

if (auth_check()) redirect('account.php');

$error = null;
$oldUser = '';
$needCaptcha = !empty($_SESSION['__login_fail']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify_or_die();
    $u = trim((string)($_POST['username'] ?? ''));
    if (strlen($u) > 190) $u = substr($u, 0, 190);
    $p = (string)($_POST['password'] ?? '');
    $oldUser = $u;
    $ip = client_ip();

    $captchaOk = !$needCaptcha || captcha_answer_ok((string)($_POST['captcha'] ?? ''));

    if (auth_is_locked($ip, $u)) {
        $error = '尝试过于频繁，请 15 分钟后再试。';
    } elseif (!$captchaOk) {
        $error = '验证码错误或已过期';
    } elseif ($u !== '' && auth_verify_credentials($u, $p)) {
        $row = member_find_by_username($u);
        if ($row && $row['status'] === 'disabled') {
            auth_record_attempt($ip, $u, false);
            $error = '该账号已被停用，请联系管理员。';
        } else {
            auth_record_attempt($ip, $u, true);
            unset($_SESSION['__login_fail']);
            auth_login_user($row['username']);        // 存库中规范用户名
            $_SESSION['nick'] = ($row['nickname'] ?? '') !== '' ? $row['nickname'] : $row['username'];
            audit('member_login');
            redirect('account.php');
        }
    } else {
        auth_record_attempt($ip, $u, false);
        $_SESSION['__login_fail'] = 1;                // 首败后要求验证码
        $needCaptcha = true;
        $error = '用户名或密码错误';
    }
}
$active = ''; $navOnDark = false;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>登录 · MabSeek</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/partials/nav.php'; ?>
<main class="container" style="max-width:420px;margin:48px auto">
  <h1 style="margin-bottom:20px">会员登录</h1>
  <?php if ($error): ?><p style="color:#c0392b"><?= e($error) ?></p><?php endif; ?>
  <form method="post" action="login.php">
    <?= csrf_field() ?>
    <div class="field">
      <label>用户名</label>
      <input class="input" type="text" name="username" value="<?= e($oldUser) ?>" required>
    </div>
    <div class="field">
      <label>密码</label>
      <input class="input" type="password" name="password" required autocomplete="current-password">
    </div>
    <?php if ($needCaptcha): ?>
    <div class="field">
      <label>验证码</label>
      <div style="display:flex;gap:10px;align-items:center">
        <input class="input" type="text" name="captcha" required style="flex:1">
        <img src="captcha.php" alt="验证码" onclick="this.src='captcha.php?'+Date.now()" style="cursor:pointer;height:40px" title="点击刷新">
      </div>
    </div>
    <?php endif; ?>
    <button class="btn btn-purple" type="submit">登录</button>
    <p style="margin-top:14px">还没有账号？<a href="register.php">去注册</a></p>
  </form>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
