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
$active = ''; $navOnDark = false; $navSolidDark = true;
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
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-brand"><span class="logo">🧬</span><span>MabSeek</span></div>
    <div class="auth-title">欢迎回来</div>
    <div class="auth-sub">登录后可发帖、管理你的账号资料。</div>
    <?php if ($error): ?><div class="auth-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="login.php">
      <?= csrf_field() ?>
      <div class="field">
        <label>用户名</label>
        <input class="input" type="text" name="username" value="<?= e($oldUser) ?>" required autofocus>
      </div>
      <div class="field">
        <label>密码</label>
        <input class="input" type="password" name="password" required autocomplete="current-password">
      </div>
<?php if ($needCaptcha): ?>
      <div class="field">
        <label>验证码</label>
        <div class="auth-captcha">
          <input class="input" type="text" name="captcha" required>
          <img src="captcha.php" alt="验证码" onclick="this.src='captcha.php?'+Date.now()" title="点击刷新">
        </div>
      </div>
<?php endif; ?>
      <button class="btn btn-purple auth-submit" type="submit">登录</button>
    </form>
    <div class="auth-alt">还没有账号？<a href="register.php">去注册</a></div>
  </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
