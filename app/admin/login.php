<?php /** @var ?string $error @var string $oldUser */ ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>登录 · MabSeek 管理后台</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="admin login-page">
<div class="login-wrap">
  <div class="card login-card">
    <div class="login-brand">
      <span class="logo"><svg viewBox="0 0 24 24" fill="none"><path d="M7 3v4c0 2.8 2.2 5 5 5s5 2.2 5 5v4M17 3v4c0 2.8-2.2 5-5 5" stroke="var(--purple)" stroke-width="2" stroke-linecap="round"/></svg></span>
      <span>MabSeek 管理后台</span>
    </div>
<?php if (!empty($error)): ?>
    <div class="login-error"><?= e($error) ?></div>
<?php endif; ?>
    <form method="post" action="admin.php" autocomplete="on">
      <?= csrf_field() ?>
      <div class="field">
        <label class="field-label" for="username">用户名</label>
        <input id="username" name="username" type="text" class="input" autocomplete="username" required autofocus value="<?= e($oldUser ?? '') ?>">
      </div>
      <div class="field">
        <label class="field-label" for="password">密码</label>
        <input id="password" name="password" type="password" class="input" autocomplete="current-password" required>
      </div>
      <button type="submit" class="abtn abtn-primary">登录</button>
    </form>
  </div>
</div>
</body>
</html>
