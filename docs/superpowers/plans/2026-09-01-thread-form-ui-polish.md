# 发帖/编辑页按注册页规范重构 实现计划

> **For agentic workers:** 纯前端结构 + class 改动，复用已有 `.auth-*` 样式；无 PHP 逻辑变更，无新增 CSS。用 executing-plans 或内联执行；验证以预览实机为主，PHP 测试仅回归。

**Goal:** 把 `thread-new.php` 与 `thread-edit.php` 的裸表单重构为注册页同款居中卡片，解决固定导航遮挡并统一视觉。

**Architecture:** 复用 `style.css` 已有的 `.auth-wrap` / `.auth-card.wide` / `.auth-*` 体系，仅替换两页主表单的外层结构与 class，PHP 顶部逻辑与 head、nav/footer include 不变。

**Tech Stack:** PHP 服务端模板 + 既有原生 CSS。

**关联 spec:** `docs/superpowers/specs/2026-09-01-thread-form-ui-polish-design.md`

---

## Task 1: 重构发帖页 thread-new.php

**Files:** Modify: `public/thread-new.php`（仅替换 `<main>…</main>` 主体）

- [ ] **Step 1: 替换主体**

把 `thread-new.php` 中 `<?php include __DIR__ . '/partials/nav.php'; ?>` 之后、`<?php include __DIR__ . '/partials/footer.php'; ?>` 之前的 `<main class="container" style="max-width:640px;margin:48px auto"> … </main>` 整段替换为：

```php
<div class="auth-wrap">
  <div class="auth-card wide">
    <div class="auth-brand"><span class="logo">🧬</span><span>MabSeek</span></div>
    <div class="auth-title">发布帖子</div>
    <div class="auth-sub">分享经验、提问求助，与社区一起成长。</div>
    <?php if ($err): ?><div class="auth-error"><?= e($err) ?></div><?php endif; ?>
    <form method="post" action="thread-new.php">
      <?= csrf_field() ?>
      <div class="field">
        <label>分类</label>
        <select class="input" name="category" required>
<?php foreach (THREAD_CATEGORIES as $k => $label): ?>
          <option value="<?= e($k) ?>"<?= $in['category'] === $k ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>标题（≤120 字）</label>
        <input class="input" type="text" name="title" maxlength="120" value="<?= e($in['title']) ?>" required autofocus>
      </div>
      <div class="field">
        <label>正文（纯文本，≤5000 字）</label>
        <textarea class="input" name="body" rows="10" maxlength="5000" required><?= e($in['body']) ?></textarea>
      </div>
      <button class="btn btn-purple auth-submit" type="submit">发布</button>
    </form>
    <div class="auth-alt"><a href="forum.php">取消，返回论坛</a></div>
  </div>
</div>
```

> 顶部 PHP（`member_check`、停用/限流校验、`$err`/`$in`、`thread_create` 跳转）与 head、nav/footer include 保持不变。

- [ ] **Step 2: 校验 + 提交**

Run: `php -l public/thread-new.php`
```bash
git add public/thread-new.php
git commit -m "feat(forum-ui): 发帖页重构为居中卡片(复用 auth-card)"
```

---

## Task 2: 重构编辑页 thread-edit.php

**Files:** Modify: `public/thread-edit.php`（仅替换主表单 `<main>…</main>`；404 片段不动）

- [ ] **Step 1: 替换主表单主体**

把 `thread-edit.php` 结尾处的主表单 `<main class="container" style="max-width:640px;margin:48px auto"> … </main>`（含 `<h1>编辑帖子</h1>` 的那段，**不是**上方 404 分支里的 `<main>`）替换为：

```php
<div class="auth-wrap">
  <div class="auth-card wide">
    <div class="auth-brand"><span class="logo">🧬</span><span>MabSeek</span></div>
    <div class="auth-title">编辑帖子</div>
    <div class="auth-sub">修改后保存即更新。</div>
    <?php if ($err): ?><div class="auth-error"><?= e($err) ?></div><?php endif; ?>
    <form method="post" action="thread-edit.php?id=<?= (int)$id ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="act" value="update">
      <div class="field">
        <label>分类</label>
        <select class="input" name="category" required>
<?php foreach (THREAD_CATEGORIES as $k => $label): ?>
          <option value="<?= e($k) ?>"<?= $in['category'] === $k ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>标题（≤120 字）</label>
        <input class="input" type="text" name="title" maxlength="120" value="<?= e($in['title']) ?>" required autofocus>
      </div>
      <div class="field">
        <label>正文（纯文本，≤5000 字）</label>
        <textarea class="input" name="body" rows="10" maxlength="5000" required><?= e($in['body']) ?></textarea>
      </div>
      <button class="btn btn-purple auth-submit" type="submit">保存</button>
    </form>
    <div class="auth-alt"><a href="thread.php?id=<?= (int)$id ?>">取消</a></div>
  </div>
</div>
```

> 顶部 PHP（作者 404 校验、`act=delete`/`act=update`、`$err`/`$in`）、head、nav/footer include 与「帖子不存在」404 片段均保持不变。

- [ ] **Step 2: 校验 + 提交**

Run: `php -l public/thread-edit.php`
```bash
git add public/thread-edit.php
git commit -m "feat(forum-ui): 编辑页重构为居中卡片(复用 auth-card)"
```

---

## Task 3: 实机验证

- [ ] **Step 1: 回归测试**

Run: `php tests/run.php` — 预期 `176 passed, 0 failed`。

- [ ] **Step 2: 预览实机验证**

启动 `mabseek-php` + `php bin/seed.php`，登录一个会员：
1. `thread-new.php`：标题不被导航遮挡、卡片居中；品牌头 + 标题 + 副标题 + 分类下拉 + 标题 + 正文 textarea（可拉伸、紫色 focus）+ 全宽紫按钮 + 取消链接。
2. 发帖成功跳详情；留空触发 `.auth-error` 顶部条。
3. `thread-edit.php`（作者本人）同款版式；保存跳详情、取消回详情。
4. 移动端（≤960px）卡片自适应不溢出。

- [ ] **Step 3: 确认干净**

Run: `git status --short`

---

## Self-Review（对照 spec）

- §3 版式（auth-wrap + auth-card.wide + brand/title/sub/error/submit/alt）→ Task 1、2。✅
- §4.1 thread-new 逻辑不变、仅换结构 → Task 1。✅
- §4.2 thread-edit 逻辑不变、404 片段保留 → Task 2。✅
- §5 验证 → Task 3。✅

**占位符：** 无。**一致性：** 复用的 `.auth-wrap/.auth-card.wide/.auth-brand/.auth-title/.auth-sub/.auth-error/.auth-submit/.auth-alt` 均已在 style.css 定义（上一轮认证美化）。✅
