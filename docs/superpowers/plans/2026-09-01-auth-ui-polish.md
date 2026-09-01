# 认证页 UI 美化 + 导航入口修复 实现计划

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development 或 superpowers:executing-plans 逐任务实现。纯前端视觉改动，无 PHP 逻辑变更；验证以预览实机为主，PHP 测试仅做回归确认。

**Goal:** 导航会员入口只显「登录」且在浅/深底导航都清晰；登录/注册/账号页不再被 fixed 导航遮挡，并改为行业主流的居中卡片版式。

**Architecture:** 在 `style.css` 末尾新增 `.auth-*` 卡片样式与 `.nav-auth` 药丸（含 `.nav--on-dark` 深底覆盖）；`nav.php` 改文案；`login.php`/`register.php`/`account.php` 换外层结构与 class，PHP 逻辑（CSRF/验证码/蜜罐/校验/跳转）全部照旧。

**Tech Stack:** 服务端渲染 PHP 模板 + 原生 CSS，零构建。

**关联 spec:** `docs/superpowers/specs/2026-09-01-auth-ui-polish-design.md`

---

## Task 1: 新增 auth 卡片样式 + nav-auth 药丸（style.css）

**Files:** Modify: `public/assets/css/style.css`（末尾追加）

- [ ] **Step 1: 在 `public/assets/css/style.css` 末尾追加**

```css

/* ===== 认证页（登录/注册/账号）===== */
.auth-wrap {
  min-height: 100vh;
  display: flex; align-items: center; justify-content: center;
  padding: calc(var(--nav-h) + 40px) 20px 48px;
  background: var(--grad-aurora), var(--bg-soft);
}
.auth-card {
  width: 100%; max-width: 420px;
  background: #fff; border: 1px solid var(--line);
  border-radius: var(--radius-lg); box-shadow: var(--sh-lg);
  padding: 40px 36px;
}
.auth-card.wide { max-width: 560px; }
.auth-brand { display: flex; align-items: center; gap: 10px; margin-bottom: 22px; font-weight: 800; color: var(--ink); }
.auth-brand .logo { width: 36px; height: 36px; border-radius: 10px; background: var(--grad-purple); display: grid; place-items: center; color: #fff; }
.auth-title { font-size: 24px; font-weight: 800; color: var(--ink); margin-bottom: 6px; }
.auth-sub { font-size: 14px; color: var(--ink-3); margin-bottom: 26px; }
.auth-card .field { margin-bottom: 16px; }
.auth-card label { display: block; font-size: 13px; font-weight: 600; color: var(--ink-2); margin-bottom: 6px; }
.auth-card .input {
  width: 100%; padding: 12px 14px; border: 1px solid var(--line); border-radius: var(--radius-sm);
  font-size: 15px; background: #fff; color: var(--ink);
  transition: border-color .15s, box-shadow .15s;
}
.auth-card textarea.input { resize: vertical; }
.auth-card .input:focus { outline: none; border-color: var(--purple-400); box-shadow: 0 0 0 3px var(--purple-050); }
.auth-submit { width: 100%; justify-content: center; margin-top: 6px; }
.auth-alt { text-align: center; font-size: 14px; color: var(--ink-3); margin-top: 18px; }
.auth-alt a { color: var(--purple); font-weight: 600; }
.auth-error { background: #FDECEC; border: 1px solid #F5C2C2; color: #c0392b; border-radius: var(--radius-sm); padding: 10px 14px; font-size: 14px; margin-bottom: 18px; }
.auth-field-err { color: #c0392b; font-size: 12px; margin-top: 5px; }
.auth-captcha { display: flex; gap: 10px; align-items: center; }
.auth-captcha .input { flex: 1; }
.auth-captcha img { height: 44px; border-radius: var(--radius-sm); cursor: pointer; border: 1px solid var(--line); }
.auth-section-title { font-size: 17px; font-weight: 700; color: var(--ink); margin: 4px 0 16px; }

/* ===== 导航会员入口药丸（覆盖 .btn-ghost 的内边距）===== */
.nav-auth.btn-ghost {
  border: 1px solid var(--purple); color: var(--purple);
  padding: 7px 18px; border-radius: 999px; font-weight: 700; font-size: 14px;
  background: transparent;
}
.nav-auth.btn-ghost:hover { background: var(--purple-050); opacity: 1; }
.nav--on-dark .nav-auth.btn-ghost { border-color: rgba(255,255,255,.5); color: #fff; }
.nav--on-dark .nav-auth.btn-ghost:hover { border-color: var(--green-neon); color: var(--green-neon); background: transparent; }
```

- [ ] **Step 2: 校验 + 提交**

Run: `php tests/run.php` — 预期 `176 passed, 0 failed`（不回归）。
```bash
git add public/assets/css/style.css
git commit -m "feat(auth-ui): 认证卡片样式 + 导航药丸(浅底紫描边/深底白描边)"
```

---

## Task 2: 导航文案改「登录」（nav.php）

**Files:** Modify: `public/partials/nav.php`

- [ ] **Step 1: 改文案**

把 `public/partials/nav.php` 的登录态未登录分支：
```php
      <a href="login.php" class="btn btn-ghost nav-auth">登录 / 注册</a>
```
改为：
```php
      <a href="login.php" class="btn btn-ghost nav-auth">登录</a>
```
（已登录分支「👤 昵称」不变。）

- [ ] **Step 2: 校验 + 提交**

Run: `php -l public/partials/nav.php`
```bash
git add public/partials/nav.php
git commit -m "feat(auth-ui): 导航未登录入口文案改为「登录」"
```

---

## Task 3: 登录页居中卡片（login.php）

**Files:** Modify: `public/login.php`（仅替换 `<body>` 内主体结构，PHP 顶部逻辑不动）

- [ ] **Step 1: 替换 `login.php` 中 `<body>` 到 `</body>` 之间的主体**

把 `<?php include __DIR__ . '/partials/nav.php'; ?>` 之后、`<?php include __DIR__ . '/partials/footer.php'; ?>` 之前的 `<main>...</main>` 整段替换为：

```php
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
```

> 顶部 PHP（`$error`/`$oldUser`/`$needCaptcha` 等）与 `<head>`、nav/footer include 均保持不变。

- [ ] **Step 2: 校验 + 提交**

Run: `php -l public/login.php`
```bash
git add public/login.php
git commit -m "feat(auth-ui): 登录页居中卡片版式"
```

---

## Task 4: 注册页居中卡片（register.php）

**Files:** Modify: `public/register.php`（仅替换主体结构）

- [ ] **Step 1: 替换 `register.php` 的 `<main>...</main>` 主体为**

```php
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-brand"><span class="logo">🧬</span><span>MabSeek</span></div>
    <div class="auth-title">注册会员</div>
    <div class="auth-sub">加入 MabSeek 社区，参与论坛讨论与分享。</div>
    <form method="post" action="register.php" autocomplete="off">
      <?= csrf_field() ?>
      <div style="position:absolute;left:-9999px" aria-hidden="true">
        <label>请勿填写<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
      </div>
      <div class="field">
        <label>用户名</label>
        <input class="input" type="text" name="username" value="<?= e($in['username']) ?>" required autofocus>
        <?php if (isset($errors['username'])): ?><div class="auth-field-err"><?= e($errors['username']) ?></div><?php endif; ?>
      </div>
      <div class="field">
        <label>密码（8–32 位，含字母与数字）</label>
        <input class="input" type="password" name="password" required autocomplete="new-password">
        <?php if (isset($errors['password'])): ?><div class="auth-field-err"><?= e($errors['password']) ?></div><?php endif; ?>
      </div>
      <div class="field">
        <label>邮箱（可选）</label>
        <input class="input" type="email" name="email" value="<?= e($in['email']) ?>">
        <?php if (isset($errors['email'])): ?><div class="auth-field-err"><?= e($errors['email']) ?></div><?php endif; ?>
      </div>
      <div class="field">
        <label>手机号（可选）</label>
        <input class="input" type="text" name="phone" value="<?= e($in['phone']) ?>">
        <?php if (isset($errors['phone'])): ?><div class="auth-field-err"><?= e($errors['phone']) ?></div><?php endif; ?>
      </div>
      <div class="field">
        <label>昵称（可选）</label>
        <input class="input" type="text" name="nickname" value="<?= e($in['nickname']) ?>">
        <?php if (isset($errors['nickname'])): ?><div class="auth-field-err"><?= e($errors['nickname']) ?></div><?php endif; ?>
      </div>
      <div class="field">
        <label>验证码</label>
        <div class="auth-captcha">
          <input class="input" type="text" name="captcha" required>
          <img src="captcha.php" alt="验证码" onclick="this.src='captcha.php?'+Date.now()" title="点击刷新">
        </div>
        <?php if (isset($errors['captcha'])): ?><div class="auth-field-err"><?= e($errors['captcha']) ?></div><?php endif; ?>
      </div>
      <button class="btn btn-purple auth-submit" type="submit">注册并登录</button>
    </form>
    <div class="auth-alt">已有账号？<a href="login.php">去登录</a></div>
  </div>
</div>
```

> 顶部 PHP（`$errors`/`$in`/蜜罐处理）与 head、nav/footer include 不变。蜜罐字段仍屏外隐藏。

- [ ] **Step 2: 校验 + 提交**

Run: `php -l public/register.php`
```bash
git add public/register.php
git commit -m "feat(auth-ui): 注册页居中卡片版式(字段级错误)"
```

---

## Task 5: 账号中心复用卡片风格（account.php）

**Files:** Modify: `public/account.php`（仅替换主体结构）

- [ ] **Step 1: 替换 `account.php` 的 `<main>...</main>` 主体为**

```php
<div class="auth-wrap" style="align-items:flex-start">
  <div style="width:100%;max-width:560px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <div class="auth-title" style="margin:0">账号中心</div>
      <form method="post" action="logout.php"><?= csrf_field() ?><button class="btn btn-ghost nav-auth" type="submit">退出登录</button></form>
    </div>
    <?php if ($msg): ?><div class="auth-error" style="background:var(--green-100);border-color:var(--green-400);color:#0a7a5c"><?= e($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="auth-error"><?= e($err) ?></div><?php endif; ?>

    <div class="auth-card wide" style="margin-bottom:20px">
      <div class="auth-section-title">基本资料</div>
      <p style="color:var(--ink-3);font-size:13px;margin-bottom:16px">用户名：<?= e($me['username']) ?>（不可修改）</p>
      <form method="post" action="account.php">
        <?= csrf_field() ?>
        <input type="hidden" name="do" value="profile">
        <div class="field"><label>邮箱</label><input class="input" type="email" name="email" value="<?= e($me['email']) ?>"></div>
        <div class="field"><label>手机号</label><input class="input" type="text" name="phone" value="<?= e($me['phone']) ?>"></div>
        <div class="field"><label>昵称</label><input class="input" type="text" name="nickname" value="<?= e($me['nickname']) ?>"></div>
        <button class="btn btn-purple auth-submit" type="submit">保存资料</button>
      </form>
    </div>

    <div class="auth-card wide">
      <div class="auth-section-title">修改密码</div>
      <form method="post" action="account.php">
        <?= csrf_field() ?>
        <input type="hidden" name="do" value="password">
        <div class="field"><label>当前密码</label><input class="input" type="password" name="current" required autocomplete="current-password"></div>
        <div class="field"><label>新密码（8–32 位，含字母与数字）</label><input class="input" type="password" name="new" required autocomplete="new-password"></div>
        <div class="field"><label>确认新密码</label><input class="input" type="password" name="confirm" required autocomplete="new-password"></div>
        <button class="btn btn-purple auth-submit" type="submit">修改密码</button>
      </form>
    </div>
  </div>
</div>
```

> 顶部 PHP（`$me`/`$msg`/`$err`/两个 `do` 分支）与 head、nav/footer include 不变。

- [ ] **Step 2: 校验 + 提交**

Run: `php -l public/account.php`
```bash
git add public/account.php
git commit -m "feat(auth-ui): 账号中心复用卡片风格"
```

---

## Task 6: 实机验证

- [ ] **Step 1: 回归测试**

Run: `php tests/run.php` — 预期 `176 passed, 0 failed`（纯前端改动，逻辑不受影响）。

- [ ] **Step 2: 预览实机验证**

启动 `mabseek-php` + `php bin/seed.php`：
1. 首页深色 hero 导航：「登录」药丸白描边白字清晰可见；hover 转荧光绿。
2. 登录页（浅底导航）：「登录」药丸紫描边清晰。
3. 登录页标题「欢迎回来」不再被导航遮挡，卡片视口居中。
4. 登录/注册卡片：品牌头 + 标题 + 输入 focus 紫色高亮 + 全宽紫按钮 + 底部次要链接。
5. 移动端（resize≤960）：入口可见、卡片自适应不溢出。
6. 走登录 / 注册（触发字段错误 + 验证码）/ 改密流程，确认功能与错误提示正常。

- [ ] **Step 3: 确认工作树干净**

Run: `git status --short`

---

## Self-Review（对照 spec）

- §2 导航文案「登录」+ 双底药丸 → Task 1、2。✅
- §3 遮挡修复（`.auth-wrap` padding-top）→ Task 1（CSS）+ Task 3/4/5（结构）。✅
- §4 卡片视觉规范 → Task 1。✅
- §5 三模板改动（login/register/account，逻辑不变）→ Task 3/4/5。✅
- §6 无逻辑改动 + 验证 → Task 6。✅

**占位符扫描：** 无。**一致性：** 类名 `.auth-wrap/.auth-card/.auth-brand/.auth-title/.auth-sub/.auth-error/.auth-field-err/.auth-captcha/.auth-alt/.auth-submit/.auth-section-title/.nav-auth` 在 CSS 定义与模板使用处一致。✅
