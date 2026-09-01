# 首页英雄区改为图片背景（左文右图 · 响应式）实现计划

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 把首页英雄区从全屏 Canvas 动画改为抗体图片背景（图案锚右）+ 左对齐文字 + 跟随文字的 CTA，并在任意窗口宽度下保持可读与不裁没。

**Architecture:** 纯前端改动。`.hero-c` 用 CSS `background-image`（`cover` + `right center`）承载图片，`::before` 叠一层从左到右的深色渐变遮罩保证文字可读；文字块左对齐、垂直居中、收在站点容器内并再收窄以避开右侧图案。移除 `<canvas>`、其 `<script>` 引用与 `hero-anim.js`。

**Tech Stack:** PHP 模板（`public/index.php`）、CSS（`public/assets/css/style.css`）、静态图片资源。无自动化前端测试——验证以浏览器多宽度目视 + 控制台/服务端无报错为准。

**关联规格：** `docs/superpowers/specs/2026-09-02-home-hero-image-background-design.md`

**已就绪的前置资源：** `public/assets/images/hero-home.jpg`（真 JPEG，1672×941，198KB）已处理并放置到位（当前未跟踪，Task 2 提交时纳入）。

---

## 文件结构

- **修改** `public/index.php` — 英雄区标记：删除 `<canvas>`，把 CTA 移入 `.hero-c-inner`；删除页尾 `hero-anim.js` 的 `<script>`。
- **修改** `public/assets/css/style.css` — 重写 `.hero-c` 系列样式；删除 `#antibody-canvas` 规则。
- **删除** `public/assets/js/hero-anim.js` — 专用于该动画，删除。
- **新增（已就位）** `public/assets/images/hero-home.jpg` — 英雄区背景图，随 Task 2 一并 `git add`。

---

## Task 1: 预检（图片就位 + 引用排查）

**Files:** 只读检查，无修改。

- [ ] **Step 1: 确认背景图存在且为 JPEG**

Run:
```bash
ls -la public/assets/images/hero-home.jpg && sips -g pixelWidth -g pixelHeight -g format public/assets/images/hero-home.jpg
```
Expected: 文件存在，`format: jpeg`，宽高约 1672×941，体积约 200KB（≤400KB）。
若文件缺失：停止并上报 NEEDS_CONTEXT（不要自造占位图）。

- [ ] **Step 2: 排查 canvas/动画的全部引用点**

Run:
```bash
grep -rn "hero-anim\|antibody-canvas" public
```
Expected: 命中仅出现在三处——`public/index.php`（`<canvas>` 与 `<script>`）、`public/assets/css/style.css`（`#antibody-canvas`）、`public/assets/js/hero-anim.js`（文件自身）。
若在其它文件（如别的页面或 `main.js`）也引用了 `antibody-canvas`/`hero-anim`，停止并上报 NEEDS_CONTEXT——说明有额外依赖，计划需调整。

无需提交（纯检查）。

---

## Task 2: 重写英雄区标记与样式

**Files:**
- Modify: `public/index.php`（英雄区 `<section class="hero-c">`，约 22–31 行；页尾 `<script src="assets/js/hero-anim.js">`）
- Modify: `public/assets/css/style.css`（`.hero-c` 系列，约 338–351 行）
- Add: `public/assets/images/hero-home.jpg`（已就位，本任务纳入版本库）

- [ ] **Step 1: 改写 index.php 英雄区标记**

将 `public/index.php` 中现有的：
```php
<section class="hero-c">
  <canvas id="antibody-canvas"></canvas>
  <div class="hero-c-inner">
    <span class="eyebrow reveal"><?= snip('home.hero.eyebrow') ?></span>
    <h1 class="reveal d1"><?= snip_raw('home.hero.title') ?></h1>
    <p class="hero-c-sub reveal d2"><?= snip('home.hero.sub') ?></p>
  </div>
  <a href="agent.php" class="btn btn-green btn-lg hero-c-cta reveal d3"><?= snip('home.hero.cta') ?></a>
</section>
```
替换为（删除 canvas；CTA 移入 inner，跟随文字）：
```php
<section class="hero-c">
  <div class="hero-c-inner">
    <span class="eyebrow reveal"><?= snip('home.hero.eyebrow') ?></span>
    <h1 class="reveal d1"><?= snip_raw('home.hero.title') ?></h1>
    <p class="hero-c-sub reveal d2"><?= snip('home.hero.sub') ?></p>
    <a href="agent.php" class="btn btn-green btn-lg hero-c-cta reveal d3"><?= snip('home.hero.cta') ?></a>
  </div>
</section>
```

- [ ] **Step 2: 删除 index.php 页尾的动画脚本引用**

在 `public/index.php` 末尾，删除这一行（保留其上方的 `main.js`）：
```php
<script src="assets/js/hero-anim.js"></script>
```

- [ ] **Step 3: 重写 style.css 的 .hero-c 系列样式**

在 `public/assets/css/style.css` 中，将现有整段：
```css
/* 首页英雄区（排布 C：标题上居中 + 动画全屏背景 + 右下角 CTA） */
.hero-c { position: relative; min-height: 88vh; display: flex; flex-direction: column;
  align-items: center; justify-content: flex-start; text-align: center; overflow: hidden;
  padding: calc(var(--nav-h) + 72px) 24px 40px; background: var(--bg-dark); }
#antibody-canvas { position: absolute; inset: 0; z-index: 0; width: 100%; height: 100%; }
.hero-c .hero-c-inner { position: relative; z-index: 2; max-width: 900px; }
.hero-c h1 { font-size: clamp(30px, 5.4vw, 62px); line-height: 1.14; color: #fff; }
.hero-c .hero-c-sub { color: var(--ink-on-dark-2); font-size: clamp(15px,2vw,19px); margin-top: 20px; }
.hero-c .hero-c-cta { position: absolute; right: 28px; bottom: 28px; z-index: 3; }
@media (max-width: 720px) {
  .hero-c { min-height: 92vh; }
  .hero-c .hero-c-cta { right: 50%; transform: translateX(50%); bottom: 22px; }
}
```
替换为：
```css
/* 首页英雄区：图片背景（图案锚右）+ 左对齐文字 */
.hero-c {
  position: relative;
  min-height: 88vh;
  display: flex;
  align-items: center;                 /* 竖向居中 */
  overflow: hidden;
  padding: calc(var(--nav-h) + 72px) 24px 40px;
  background-color: var(--bg-dark);    /* 兜底：图未加载时不塌陷 */
  background-image: url('../images/hero-home.jpg');
  background-size: cover;
  background-position: right center;   /* 焦点锚右，抗体不被裁没 */
  background-repeat: no-repeat;
}
/* 左侧深色渐变遮罩：保证文字可读，同时右侧抗体透出（颜色对齐 --bg-dark #070A14 = rgb(7,10,20)） */
.hero-c::before {
  content: "";
  position: absolute; inset: 0; z-index: 1;
  background: linear-gradient(90deg,
    rgba(7,10,20,.92) 0%, rgba(7,10,20,.72) 32%, rgba(7,10,20,.42) 55%, rgba(7,10,20,0) 78%);
}
.hero-c .hero-c-inner {
  position: relative; z-index: 2;
  width: 100%;
  max-width: var(--maxw);              /* 站点容器最大宽度（现为 1200px），与页面栅格一致 */
  margin: 0 auto;
  text-align: left;
}
/* 文字实际内容再收窄，避开右侧图案 */
.hero-c .hero-c-inner > * { max-width: min(560px, 52vw); }
.hero-c h1 { font-size: clamp(30px, 5.4vw, 62px); line-height: 1.14; color: #fff; }
.hero-c .hero-c-sub { color: var(--ink-on-dark-2); font-size: clamp(15px,2vw,19px); margin-top: 20px; }
.hero-c .hero-c-cta { display: inline-flex; margin-top: 28px; }

@media (max-width: 720px) {
  .hero-c { min-height: 92vh; }
  /* 窄屏：加强遮罩、文字占满，抗体作氛围隐约可见 */
  .hero-c::before {
    background: linear-gradient(90deg,
      rgba(7,10,20,.95) 0%, rgba(7,10,20,.88) 55%, rgba(7,10,20,.7) 100%);
  }
  .hero-c .hero-c-inner > * { max-width: 100%; }
}
```

- [ ] **Step 4: 语法自检 + 无残留引用**

Run:
```bash
php -l public/index.php && grep -n "antibody-canvas\|hero-anim" public/index.php public/assets/css/style.css
```
Expected: `No syntax errors detected in public/index.php`；grep **无任何输出**（index.php 和 style.css 里 canvas/动画引用已清除）。

- [ ] **Step 5: 提交（含图片资源）**

```bash
git add public/index.php public/assets/css/style.css public/assets/images/hero-home.jpg
git commit -m "feat(home): 英雄区改为图片背景(左文右图), 移除画布动画标记与样式"
```

---

## Task 3: 删除动画脚本文件

**Files:**
- Delete: `public/assets/js/hero-anim.js`

- [ ] **Step 1: 再次确认无引用后删除**

Run:
```bash
grep -rn "hero-anim" public || echo "NO_REFERENCES"
```
Expected: 输出 `NO_REFERENCES`（Task 2 已移除 index.php 的引用；此处确认全仓库无引用）。
若仍有引用：停止并上报——先清引用再删文件。

- [ ] **Step 2: 删除文件**

Run:
```bash
git rm public/assets/js/hero-anim.js
```
Expected: `rm 'public/assets/js/hero-anim.js'`。

- [ ] **Step 3: 提交**

```bash
git commit -m "chore(home): 删除弃用的英雄区画布动画 hero-anim.js"
```

---

## Task 4: 浏览器多宽度验证

**Files:** 无修改（验收）。

- [ ] **Step 1: 起本地服务**

用 preview 工具启动 `mabseek-php`（`.claude/launch.json` 已配置，端口 8778），或：
```bash
php -S localhost:8778 -t public
```

- [ ] **Step 2: 服务端无错误**

访问 `http://localhost:8778/index.php`，确认 HTTP 200 且服务端日志无 PHP 报错。
Run（若用 CLI 服务）: `curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8778/index.php`
Expected: `200`。

- [ ] **Step 3: 桌面 1440 宽度目视**

在 1440×900 视口加载首页：
- 文字左对齐、垂直居中，清晰可读；
- 抗体图案锚在右侧，未被裁掉主体；
- CTA 按钮在副标题下方、左对齐；
- 无横向滚动条。
截图存档。

- [ ] **Step 4: 平板 768 宽度目视**

在 768 宽度：文字仍左对齐清晰、无与图案重叠、无溢出。截图存档。

- [ ] **Step 5: 手机 375 宽度目视**

在 375 宽度：遮罩加强、文字占满且清晰可读、抗体作氛围隐约可见、无横向滚动条、CTA 可点。截图存档。

- [ ] **Step 6: 控制台无报错**

确认浏览器控制台无 JS 报错（尤其确认删除 `hero-anim.js` 后 `main.js` 的 `reveal` 动画等仍正常、无 404）。

- [ ] **Step 7: 回归**

Run: `php tests/run.php`
Expected: 全部 PASS（本改动不涉及后端逻辑，用作无回归的兜底确认）。

无需提交（纯验收）。

---

## 完成后

- 三档宽度截图确认无误。
- 若使用分支，按 `superpowers:finishing-a-development-branch` 决定合并/PR。
