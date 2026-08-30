# MabSeek P1 首页重构 + 全局外壳 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 按 `PRD-20260825.docx` 把 MabSeek 首页重构为「黑白交替 + 动画英雄区」的 5 层级页面，并落地全站可复用的 7 板块导航、深/浅双主题配色系统与页脚，不破坏尚未改版的老页面。

**Architecture:** 纯静态站（`python -m http.server`），零后端。配色以**增量**方式扩展现有 `style.css` 令牌，新增 `.section-dark` / `.section-light` 区块工具类；老页面（education/agent/forum/about）默认浅色、保持不动。首页英雄区动画用独立的 `assets/js/hero-anim.js`（仅首页引入，用新的 canvas id，避免影响 `main.js` 里给 agent 页用的 `#hero-canvas` 粒子网络）。所有"AI 搜索/快速反馈"类交互一律前端模拟（沿用现有 `data-demo` + toast 机制）。

**Tech Stack:** 原生 HTML / CSS（CSS 变量 + 工具类）/ 原生 JS（Canvas 2D、IntersectionObserver），无构建、无依赖。验证通过 `preview_*` 浏览器工具完成（本项目无单元测试框架）。

**参考色值（spec §3）：** 深色底 `#070A14`；深色区绿 `#00E0A4`（荧光）；浅色区绿 `#12B98C`（非荧光）；紫 `#7C4DFF`；深色区次文字 `#9AA3C2`、卡片描边 `#23324A`。

**内容来源：** 团队/痛点/案例/指标等取自 PRD 权威内容；L4 近况复用现有 `about.html#news` 条目。不新造事实数据。

---

## 文件结构

| 文件 | 动作 | 职责 |
|---|---|---|
| `.claude/launch.json` | 改 | 预览目录 `mabseek-website` → `mabseek` |
| `assets/css/style.css` | 改（追加） | 深色令牌、`.section-dark`/`.section-light`、暗色导航/页脚、Hero-C 布局、痛点四格、近况滚动、快速反馈表单样式 |
| `assets/js/hero-anim.js` | 新建 | 首页英雄区"靶点→抗体组装对接"Canvas 动画（含 reduced-motion 降级） |
| `assets/js/main.js` | 改（追加） | 快速反馈表单前端模拟（`#contact-form`） |
| `technology.html` | 新建 | 技术平台占位页（深色外壳，P2 替换） |
| `index.html` | 改（重构） | 7 板块导航 + 五层级 + 暗色页脚 |

老页面 `education.html` / `agent.html` / `forum.html` / `about.html` 本轮**不改**。

---

## Task 1: 修复预览目录并建立基线

**Files:**
- Modify: `.claude/launch.json`

- [ ] **Step 1: 修正 launch.json 的预览目录**

把 `--directory` 的值从错误的 `mabseek-website` 改为实际目录 `mabseek`。

将 `.claude/launch.json` 内容改为：

```json
{
  "version": "0.0.1",
  "configurations": [
    { "name": "mabseek", "runtimeExecutable": "python3", "runtimeArgs": ["-m", "http.server", "8777", "--directory", "/Users/zhoumo/Documents/Claude/mabseek"], "port": 8777 }
  ]
}
```

- [ ] **Step 2: 启动预览并确认现状**

用 `preview_start`（name: `mabseek`）启动，然后 `preview_eval` 执行 `window.location.href = 'http://localhost:8777/index.html'`。
Expected：现有旧版首页正常加载，`preview_console_logs` 无报错。这是改版前的基线。

- [ ] **Step 3: 提交**

```bash
git add .claude/launch.json
git commit -m "fix(mabseek): point preview dir to mabseek/"
```

---

## Task 2: CSS — 深/浅主题令牌与区块工具类

**Files:**
- Modify: `assets/css/style.css`（在文件末尾追加新段落；不改动既有规则）

- [ ] **Step 1: 追加主题令牌**

在 `assets/css/style.css` **末尾**追加（保留既有 `:root` 不动，用第二个 `:root` 追加变量）：

```css
/* ============================================================
   P1 改版：深/浅双主题（增量，不影响老页面）
   ============================================================ */
:root {
  --bg-dark: #070A14;          /* 深色区背景（近黑深蓝） */
  --ink-on-dark: #FFFFFF;      /* 深色区主文字 */
  --ink-on-dark-2: #9AA3C2;    /* 深色区次文字 */
  --line-dark: #23324A;        /* 深色区描边 */
  --card-dark: #0D1122;        /* 深色区卡片底 */
  --green-neon: #00E0A4;       /* 深色区绿（荧光） */
  --green-solid: #12B98C;      /* 浅色区绿（非荧光） */
}
```

- [ ] **Step 2: 追加区块工具类**

紧接着追加：

```css
/* 深色区块 */
.section-dark { background: var(--bg-dark); color: var(--ink-on-dark); }
.section-dark .section-title,
.section-dark h1, .section-dark h2, .section-dark h3, .section-dark h4 { color: var(--ink-on-dark); }
.section-dark .section-sub,
.section-dark p { color: var(--ink-on-dark-2); }
.section-dark .card { background: var(--card-dark); border-color: var(--line-dark); box-shadow: none; }
.section-dark .card:hover { border-color: var(--green-neon); box-shadow: 0 16px 40px rgba(0,224,164,.14); }
.section-dark .card p { color: var(--ink-on-dark-2); }
.section-dark .eyebrow { color: var(--green-neon); background: rgba(0,224,164,.08); border-color: rgba(0,224,164,.25); }
.section-dark .btn-green { background: var(--green-neon); color: #04231b; box-shadow: 0 10px 30px rgba(0,224,164,.3); }
.section-dark .btn-outline { background: transparent; color: #fff; border-color: rgba(255,255,255,.35); box-shadow: none; }
.section-dark .btn-outline:hover { border-color: var(--green-neon); color: var(--green-neon); }
.section-dark a.link-more { color: var(--green-neon); }

/* 浅色区块（绿改用非荧光） */
.section-light { background: #fff; color: var(--ink); }
.section-light .eyebrow.green { color: var(--green-solid); background: #E7F7F1; border-color: #BDEBDD; }
.section-light .btn-green { background: var(--green-solid); color: #fff; box-shadow: 0 10px 30px rgba(18,185,140,.22); }
.section-light a.link-more { color: var(--green-solid); }

/* 通用：区块内"了解更多"链接 */
.link-more { font-weight: 700; display: inline-flex; align-items: center; gap: 6px; }
.txt-neon { color: var(--green-neon); }
.txt-green { color: var(--green-solid); }
```

- [ ] **Step 2b: 提交**

```bash
git add assets/css/style.css
git commit -m "feat(mabseek): add dark/light theme tokens and section utilities"
```

---

## Task 3: CSS — 暗色导航/页脚 + Hero-C + 痛点四格 + 近况滚动 + 反馈表单

**Files:**
- Modify: `assets/css/style.css`（继续在末尾追加）

- [ ] **Step 1: 追加暗色导航修饰**

```css
/* 深色页面顶部导航：hero 上透明，滚动后实底深色 */
.nav--on-dark { background: transparent; backdrop-filter: none; }
.nav--on-dark .brand,
.nav--on-dark .nav-links a { color: rgba(255,255,255,.9); }
.nav--on-dark .nav-links a:hover,
.nav--on-dark .nav-links a.active { color: var(--green-neon); background: rgba(0,224,164,.1); }
.nav--on-dark .brand small { color: var(--ink-on-dark-2); }
.nav--on-dark .nav-toggle span { background: #fff; }
.nav--on-dark.scrolled { background: rgba(7,10,20,.85); backdrop-filter: blur(16px) saturate(160%); border-color: var(--line-dark); box-shadow: 0 6px 24px rgba(0,0,0,.4); }
@media (max-width: 720px) {
  .nav--on-dark.open .nav-links { background: #0d1122; border-bottom-color: var(--line-dark); }
}
```

- [ ] **Step 2: 追加 Hero-C 布局**

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

- [ ] **Step 3: 追加痛点四格 + 近况滚动 + 反馈表单样式**

```css
/* L3 四大痛点四格 */
.pain-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-top: 36px; }
.pain-cell { border: 1px solid var(--line-dark); border-radius: var(--radius); padding: 22px 18px; background: var(--card-dark); }
.pain-cell .p-title { color: #fff; font-weight: 800; font-size: 16px; margin-bottom: 8px; }
.pain-cell .p-fix { color: var(--green-neon); font-size: 13px; font-weight: 700; }
.pain-cell p { color: var(--ink-on-dark-2); font-size: 13px; margin-top: 8px; }
@media (max-width: 960px) { .pain-grid { grid-template-columns: repeat(2,1fr) !important; } }
@media (max-width: 560px) { .pain-grid { grid-template-columns: 1fr !important; } }

/* L4 近况横向滚动 */
.news-scroller { display: flex; gap: 20px; overflow-x: auto; padding: 8px 4px 20px; scroll-snap-type: x mandatory; -webkit-overflow-scrolling: touch; }
.news-card { scroll-snap-align: start; flex: 0 0 300px; background: #fff; border: 1px solid var(--line); border-radius: var(--radius); overflow: hidden; box-shadow: var(--sh-sm); transition: transform .25s, box-shadow .25s; cursor: pointer; }
.news-card:hover { transform: translateY(-4px); box-shadow: var(--sh); }
.news-card .nc-thumb { height: 150px; background: var(--bg-soft-2); display: grid; place-items: center; color: var(--ink-3); font-size: 13px; }
.news-card .nc-body { padding: 16px 18px; }
.news-card .nc-date { font-size: 12px; color: var(--green-solid); font-weight: 700; }
.news-card .nc-body h4 { font-size: 15px; margin: 6px 0; }
.news-card .nc-body p { font-size: 13px; color: var(--ink-3); }

/* L5 快速反馈表单 */
.feedback { max-width: 560px; margin: 28px auto 0; display: grid; gap: 12px; text-align: left; }
.feedback .fb-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.feedback input, .feedback textarea { width: 100%; background: rgba(255,255,255,.06); border: 1px solid var(--line-dark); border-radius: 12px; padding: 12px 14px; color: #fff; font-family: var(--font); font-size: 14px; }
.feedback input::placeholder, .feedback textarea::placeholder { color: #6b7392; }
.feedback input:focus, .feedback textarea:focus { outline: none; border-color: var(--green-neon); }
.feedback textarea { min-height: 96px; resize: vertical; }
.feedback .fb-submit { justify-self: start; }
@media (max-width: 560px) { .feedback .fb-row { grid-template-columns: 1fr; } }
```

- [ ] **Step 4: 提交**

```bash
git add assets/css/style.css
git commit -m "feat(mabseek): add dark nav/hero-C/pain-grid/news-scroller/feedback styles"
```

---

## Task 4: JS — 英雄区"靶点→抗体组装对接"动画

**Files:**
- Create: `assets/js/hero-anim.js`

- [ ] **Step 1: 新建 hero-anim.js**

创建 `assets/js/hero-anim.js`，内容如下（独立 IIFE，仅作用于 `#antibody-canvas`；散点粒子先在四周随机分布，逐步汇聚成 Y 形抗体，再朝右侧"靶点"点云对接，循环往复；`prefers-reduced-motion` 时只绘制一帧静态对接图）：

```js
/* MabSeek 首页英雄区：靶点 → 抗体 逐步组装并对接 的循环动画。纯 Canvas2D，无依赖。 */
(function () {
  'use strict';
  var canvas = document.getElementById('antibody-canvas');
  if (!canvas || !canvas.getContext) return;
  var ctx = canvas.getContext('2d');
  var DPR = Math.min(window.devicePixelRatio || 1, 2);
  var W, H, cx, cy;
  var PURPLE = '124,77,255', NEON = '0,224,164', BLUE = '75,107,255';

  // 抗体 Y 形骨架的目标锚点（相对中心的比例坐标），粒子最终归位于此
  var YSHAPE = [
    [0, 0.18], [0, 0.05], [0, -0.08],            // 主干 (Fc)
    [-0.13, -0.22], [-0.22, -0.32], [-0.30, -0.4],// 左臂 (Fab)
    [0.13, -0.22], [0.22, -0.32], [0.30, -0.4]    // 右臂 (Fab)
  ];
  var particles = [];   // 组装抗体的粒子
  var target = [];      // 右侧"靶点"点云

  function build() {
    W = canvas.clientWidth; H = canvas.clientHeight;
    canvas.width = W * DPR; canvas.height = H * DPR;
    ctx.setTransform(DPR, 0, 0, DPR, 0, 0);
    cx = W * 0.42; cy = H * 0.56;
    var scale = Math.min(W, H) * 0.62;

    particles = YSHAPE.map(function (p) {
      return {
        hx: cx + p[0] * scale, hy: cy + p[1] * scale,           // 归位坐标
        x: Math.random() * W, y: Math.random() * H,             // 起始随机
        r: 6 + Math.random() * 4,
        c: [PURPLE, NEON, BLUE][(Math.random() * 3) | 0]
      };
    });

    target = [];
    var tx = cx + scale * 0.62, ty = cy - scale * 0.36, tr = scale * 0.16;
    for (var i = 0; i < 26; i++) {
      var a = Math.random() * Math.PI * 2, rad = Math.random() * tr;
      target.push({ x: tx + Math.cos(a) * rad, y: ty + Math.sin(a) * rad, r: 2 + Math.random() * 3 });
    }
  }

  function drawTarget() {
    for (var i = 0; i < target.length; i++) {
      var t = target[i];
      ctx.beginPath(); ctx.arc(t.x, t.y, t.r, 0, Math.PI * 2);
      ctx.fillStyle = 'rgba(255,255,255,.45)'; ctx.fill();
    }
  }

  function drawAntibody(prog) {
    // prog: 0→1 组装进度。连接骨架线
    ctx.lineWidth = 2;
    ctx.strokeStyle = 'rgba(' + NEON + ',' + (0.5 * prog) + ')';
    var seg = [[0,1],[1,2],[2,3],[3,4],[4,5],[2,6],[6,7],[7,8]];
    for (var s = 0; s < seg.length; s++) {
      var a = particles[seg[s][0]], b = particles[seg[s][1]];
      ctx.beginPath(); ctx.moveTo(a.x, a.y); ctx.lineTo(b.x, b.y); ctx.stroke();
    }
    for (var i = 0; i < particles.length; i++) {
      var p = particles[i];
      ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fillStyle = 'rgba(' + p.c + ',.85)'; ctx.fill();
      ctx.beginPath(); ctx.arc(p.x, p.y, p.r + 5, 0, Math.PI * 2);
      ctx.fillStyle = 'rgba(' + p.c + ',.12)'; ctx.fill();
    }
  }

  var t0 = null, CYCLE = 6000; // 6s 一循环
  function frame(ts) {
    if (t0 === null) t0 = ts;
    var loop = ((ts - t0) % CYCLE) / CYCLE;         // 0→1
    var assemble = Math.min(loop / 0.55, 1);         // 前 55% 组装
    var eased = 1 - Math.pow(1 - assemble, 3);
    var dock = loop > 0.6 ? Math.min((loop - 0.6) / 0.25, 1) : 0; // 60%~85% 对接位移

    ctx.clearRect(0, 0, W, H);
    // 背景光晕
    var g = ctx.createRadialGradient(cx, cy, 0, cx, cy, Math.max(W, H) * 0.5);
    g.addColorStop(0, 'rgba(124,77,255,.16)'); g.addColorStop(1, 'rgba(7,10,20,0)');
    ctx.fillStyle = g; ctx.fillRect(0, 0, W, H);

    var shift = dock * (W * 0.06);
    for (var i = 0; i < particles.length; i++) {
      var p = particles[i];
      p.x += ((p.hx + shift) - p.x) * 0.06 * (0.3 + eased);
      p.y += (p.hy - p.y) * 0.06 * (0.3 + eased);
    }
    drawTarget();
    drawAntibody(eased);
    requestAnimationFrame(frame);
  }

  function staticFrame() {
    // reduced-motion：直接归位并画一帧
    ctx.clearRect(0, 0, W, H);
    for (var i = 0; i < particles.length; i++) { particles[i].x = particles[i].hx; particles[i].y = particles[i].hy; }
    drawTarget(); drawAntibody(1);
  }

  build();
  window.addEventListener('resize', function () { build(); if (reduced) staticFrame(); });
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (reduced) staticFrame(); else requestAnimationFrame(frame);
})();
```

- [ ] **Step 2: 提交**

```bash
git add assets/js/hero-anim.js
git commit -m "feat(mabseek): add antibody assembly hero animation"
```

（该文件将在 Task 7 由 `index.html` 引入并联调；此步先落盘。）

---

## Task 5: JS — 快速反馈表单前端模拟

**Files:**
- Modify: `assets/js/main.js`（在末尾 IIFE 内、`window.mabToast = showToast;` 之前追加）

- [ ] **Step 1: 追加表单模拟处理**

在 `assets/js/main.js` 的 `showToast` 定义之后、`window.mabToast = showToast;` 这一行**之前**，追加：

```js
  /* ---------- 快速反馈表单：前端模拟提交（无后端） ---------- */
  var fbForm = document.getElementById('contact-form');
  if (fbForm) {
    fbForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var name = (fbForm.querySelector('[name="name"]') || {}).value || '';
      showToast('已收到你的反馈' + (name ? ('，' + name) : '') + '！我们会尽快通过邮件联系你。');
      fbForm.reset();
    });
  }
```

- [ ] **Step 2: 提交**

```bash
git add assets/js/main.js
git commit -m "feat(mabseek): simulate contact feedback form submit"
```

---

## Task 6: 新建技术平台占位页

**Files:**
- Create: `technology.html`

- [ ] **Step 1: 创建 technology.html**

创建 `technology.html`，内容如下（深色外壳，含完整 7 板块导航与暗色页脚，标"建设中"）：

```html
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>技术平台 · MabSeek 抗体求索 | 清华大学医学院</title>
<meta name="description" content="MabSeek 技术平台：整体技术架构、Data / AI / Wet lab 三大能力与经典案例。">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🧬</text></svg>">
</head>
<body>
<header class="nav nav--on-dark">
  <div class="container">
    <a class="brand" href="index.html">
      <span class="logo"><svg viewBox="0 0 24 24" fill="none"><path d="M7 3v4c0 2.8 2.2 5 5 5s5 2.2 5 5v4M17 3v4c0 2.8-2.2 5-5 5" stroke="white" stroke-width="2" stroke-linecap="round"/></svg></span>
      <span>MabSeek<small>抗体求索 · 清华大学医学院</small></span>
    </a>
    <nav class="nav-links">
      <a href="index.html">首页</a>
      <a href="technology.html" class="active">技术平台</a>
      <a href="agent.html">Antibody Agent</a>
      <a href="education.html">教育</a>
      <a href="forum.html">论坛</a>
      <a href="about.html">了解我们</a>
    </nav>
    <div class="nav-actions"><a href="index.html#contact" class="btn btn-green">联系我们</a></div>
    <button class="nav-toggle" aria-label="菜单"><span></span><span></span><span></span></button>
  </div>
</header>

<section class="section-dark" style="min-height:100vh;display:flex;align-items:center;justify-content:center;text-align:center">
  <div class="container">
    <span class="eyebrow">技术平台</span>
    <h1 class="section-title" style="margin-top:16px">技术平台页正在建设中</h1>
    <p class="section-sub" style="margin:14px auto 26px">我们正在整理整体技术架构、Data / AI / Wet lab 三大能力与经典案例，敬请期待。</p>
    <a href="index.html" class="btn btn-green">← 返回首页</a>
  </div>
</section>

<footer class="footer">
  <div class="container">
    <div class="footer-bottom" style="border:0;margin:0;padding:0;justify-content:center">
      <span>© 2026 MabSeek 抗体求索 · 清华大学医学院实验室. 保留所有权利。</span>
    </div>
  </div>
</footer>
<script src="assets/js/main.js"></script>
</body>
</html>
```

- [ ] **Step 2: 浏览器验证**

`preview_eval` 执行 `window.location.href='http://localhost:8777/technology.html'`；`preview_snapshot` 确认页面显示"技术平台页正在建设中"与"返回首页"按钮；`preview_console_logs` level=error 应为空。

- [ ] **Step 3: 提交**

```bash
git add technology.html
git commit -m "feat(mabseek): add technology.html placeholder page"
```

---

## Task 7: index.html 重构 — 导航 + L1 英雄区

**Files:**
- Modify: `index.html`（整页重构；本 Task 完成 `<head>`、导航、L1，并引入脚本）

- [ ] **Step 1: 替换 `<head>` 与导航为 7 板块 + 暗色**

将 `index.html` 从第 1 行到 `</header>` 结束（原第 34 行）整体替换为：

```html
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MabSeek 抗体求索 · AI 驱动的抗体发现平台 | 清华大学医学院</title>
<meta name="description" content="清华团队 × AI 大模型，让抗体发现从反复试错变成精准编程。MabSeek 抗体求索 · 清华大学医学院。">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🧬</text></svg>">
</head>
<body>

<!-- ============ 导航 ============ -->
<header class="nav nav--on-dark">
  <div class="container">
    <a class="brand" href="index.html">
      <span class="logo"><svg viewBox="0 0 24 24" fill="none"><path d="M7 3v4c0 2.8 2.2 5 5 5s5 2.2 5 5v4M17 3v4c0 2.8-2.2 5-5 5" stroke="white" stroke-width="2" stroke-linecap="round"/></svg></span>
      <span>MabSeek<small>抗体求索 · 清华大学医学院</small></span>
    </a>
    <nav class="nav-links">
      <a href="index.html" class="active">首页</a>
      <a href="technology.html">技术平台</a>
      <a href="agent.html">Antibody Agent</a>
      <a href="education.html">教育</a>
      <a href="forum.html">论坛</a>
      <a href="about.html">了解我们</a>
    </nav>
    <div class="nav-actions"><a href="#contact" class="btn btn-green">联系我们</a></div>
    <button class="nav-toggle" aria-label="菜单"><span></span><span></span><span></span></button>
  </div>
</header>
```

- [ ] **Step 2: 替换 L1 英雄区**

紧接 `</header>` 之后，替换原 Hero `<section class="hero">...</section>`（原第 36–59 行）为排布 C：

```html
<!-- ============ L1 英雄区（深） ============ -->
<section class="hero-c">
  <canvas id="antibody-canvas"></canvas>
  <div class="hero-c-inner">
    <span class="eyebrow reveal">🧬 清华团队 × AI 大模型</span>
    <h1 class="reveal d1">让抗体发现<br>从"反复试错"变成 <span class="txt-neon">"精准编程"</span></h1>
    <p class="hero-c-sub reveal d2">从头设计一支完美结合靶点的抗体——AI 生成 + 干湿闭环验证，一站直达。</p>
  </div>
  <a href="agent.html" class="btn btn-green btn-lg hero-c-cta reveal d3">免费试用 Antibody Agent →</a>
</section>
```

- [ ] **Step 3: 在 `</body>` 前引入脚本**

确认文件末尾脚本引入为（在 `main.js` 之外新增 `hero-anim.js`）：

```html
<script src="assets/js/main.js"></script>
<script src="assets/js/hero-anim.js"></script>
</body>
</html>
```

（本 Task 先让 L1 以下的旧内容临时保留也可正常显示；L2–L5 在 Task 8–9 替换。）

- [ ] **Step 4: 浏览器验证 L1**

`preview_eval` 跳 `http://localhost:8777/index.html` 并 `window.location.reload()`。
- `preview_screenshot`：英雄区深色背景、标题居中、右下角绿色 CTA、Canvas 有抗体组装动画。
- `preview_inspect` 选择 `.hero-c`，确认 `background-color` 约为 `rgb(7, 10, 20)`。
- `preview_inspect` 选择 `#antibody-canvas`，确认 `width`>0 且渲染。
- `preview_console_logs` level=error 为空。

- [ ] **Step 5: 提交**

```bash
git add index.html
git commit -m "feat(mabseek): rebuild homepage nav + hero-C (L1)"
```

---

## Task 8: index.html — L2 我们能做什么 + L3 四大痛点

**Files:**
- Modify: `index.html`

- [ ] **Step 1: 替换为 L2 + L3**

将 L1 之后、原有的"硬指标 / 能做什么 / 标杆案例 / 合作伙伴"等旧 section（原第 61 行起至"板块导览"section 结束前）中，**紧跟 hero 的两段**替换为下面的 L2、L3。（操作方式：删除 hero 之后、页脚之前的全部旧 `<section>`，改由 Task 8、Task 9 提供的 L2–L5 取代。本 Task 先加入 L2、L3。）

```html
<!-- ============ L2 我们能做什么（浅） ============ -->
<section class="section section-light">
  <div class="container">
    <div style="max-width:820px">
      <span class="eyebrow green reveal">我们能做什么</span>
      <h2 class="section-title reveal d1">把靶点交给 AI，<span class="txt-green">从设计到验证一条闭环</span></h2>
      <p class="section-sub reveal d2">AI 从头序列设计 · 结构与亲和力预测 · 干湿闭环一站交付。过去分散在多个团队、数月起步的流程，被压缩为连续、可追溯、可下单的闭环。</p>
      <a href="technology.html" class="link-more reveal d3" style="margin-top:18px">进入技术平台，了解完整技术优势 →</a>
    </div>
  </div>
</section>

<!-- ============ L3 四大痛点 × MabSeek 解法（深） ============ -->
<section class="section section-dark">
  <div class="container">
    <div class="text-center">
      <span class="eyebrow reveal">痛点共鸣 · 针对性解法</span>
      <h2 class="section-title reveal d1">抗体发现行业的四大痛点，<span class="txt-neon">各有解法</span></h2>
    </div>
    <div class="pain-grid">
      <div class="pain-cell reveal">
        <div class="p-title">难成药靶点</div>
        <p>GPCR、离子通道等跨膜靶点结构复杂、表达量低，抗原极难制备。</p>
        <div class="p-fix">→ VLP 技术保持天然构象，结合湿实验平台高效分离</div>
      </div>
      <div class="pain-cell reveal d1">
        <div class="p-title">AI 模型数据</div>
        <p>公开库多为正样本，缺负样本与完整物理信息，模型"垃圾进垃圾出"。</p>
        <div class="p-fix">→ 正/负结合抗体序列构建完整数据库，提供精准训练基准</div>
      </div>
      <div class="pain-cell reveal d2">
        <div class="p-title">干湿结合</div>
        <p>计算与实验割裂，候选分子从设计到验证需数月，试错成本高昂。</p>
        <div class="p-fix">→ 干湿闭环，命中率提升至 30%，周期缩短至 35 天</div>
      </div>
      <div class="pain-cell reveal d3">
        <div class="p-title">成药性评估</div>
        <p>成药性风险高，后期易聚集、免疫原性高，临床前废弃率高。</p>
        <div class="p-fix">→ 成药性评估 Agent，在设计早期多维评估、从源头规避风险</div>
      </div>
    </div>
    <div class="text-center" style="margin-top:30px">
      <a href="technology.html" class="btn btn-outline reveal">查看实际案例 →</a>
    </div>
  </div>
</section>
```

- [ ] **Step 2: 浏览器验证 L2 + L3**

reload 后：
- `preview_snapshot` 确认出现"把靶点交给 AI"（L2）与四个痛点标题（难成药靶点/AI 模型数据/干湿结合/成药性评估）。
- `preview_inspect` 选择 L3 的 `section.section-dark`，确认背景约 `rgb(7,10,20)`；选择 L2 的 `section.section-light` 背景为 `rgb(255,255,255)`（验证黑白交替）。
- `preview_inspect` 选择 L2 内 `.link-more`，确认 color 约为 `rgb(18,185,140)`（浅色区非荧光绿）。
- `preview_console_logs` level=error 为空。

- [ ] **Step 3: 提交**

```bash
git add index.html
git commit -m "feat(mabseek): homepage L2 (what we do) + L3 (four pains)"
```

---

## Task 9: index.html — L4 近况滚动 + L5 合作/联系/反馈 + 暗色页脚

**Files:**
- Modify: `index.html`

- [ ] **Step 1: 在 L3 之后加入 L4 + L5，并替换页脚**

在 L3 section 之后、原页脚之前，加入 L4、L5；并将原页脚整体替换为下方页脚（保持在深色语境）。删除 L3 之后到旧页脚之间残留的所有旧 section。

```html
<!-- ============ L4 我们的近况（浅） ============ -->
<section class="section section-light">
  <div class="container">
    <div class="text-center" style="margin-bottom:8px">
      <span class="eyebrow green reveal">我们的近况</span>
      <h2 class="section-title reveal d1">新闻 · 发表 · 活动</h2>
    </div>
    <div class="news-scroller reveal d2">
      <div class="news-card" data-demo="打开新闻详情：平台技术升级">
        <div class="nc-thumb">🧬 平台技术升级</div>
        <div class="nc-body"><div class="nc-date">2026 · 08</div><h4>亲和力预测模型精度再提升</h4><p>最新一轮迭代显著提升虚拟筛选命中率，缩短候选分子验证周期。</p></div>
      </div>
      <div class="news-card" data-demo="打开新闻详情：交互式课程上线">
        <div class="nc-thumb">🎓 交互式课程</div>
        <div class="nc-body"><div class="nc-date">2026 · 07</div><h4>《疫苗的力量》交互式课程上线</h4><p>元视频 + 三层知识图谱，面向学生、从业者与公众开放基础版。</p></div>
      </div>
      <div class="news-card" data-demo="打开新闻详情：顶刊发表">
        <div class="nc-thumb">📄 学术发表</div>
        <div class="nc-body"><div class="nc-date">2026 · 06</div><h4>研究成果发表于领域顶级期刊</h4><p>抗体设计与免疫机制相关工作获同行高度评价。</p></div>
      </div>
      <div class="news-card" data-demo="打开新闻详情：国际交流">
        <div class="nc-thumb">🌏 国际交流</div>
        <div class="nc-body"><div class="nc-date">2026 · 05</div><h4>中印尼疫苗与基因组联合研究进展</h4><p>持续推进 PRA 国际科研合作与联合实验室建设。</p></div>
      </div>
    </div>
    <div class="text-center"><a href="about.html#news" class="link-more reveal">进入「了解我们」查看全部近况 →</a></div>
  </div>
</section>

<!-- ============ L5 合作伙伴 + 联系我们（深） ============ -->
<section class="section section-dark" id="contact">
  <div class="container">
    <div class="text-center" style="margin-bottom:36px">
      <span class="eyebrow reveal">合作伙伴 · 联系我们</span>
      <h2 class="section-title reveal d1">与顶尖机构<span class="txt-neon">共建生态</span></h2>
    </div>
    <div class="logo-wall reveal" style="margin-bottom:40px">
      <a class="logo-chip" data-demo="清华大学联合实验室详情"><span class="mark">清</span>清华大学</a>
      <a class="logo-chip" data-demo="百济神州合作详情"><span class="mark">BJ</span>百济神州</a>
      <a class="logo-chip" data-demo="Eijkman 研究所合作"><span class="mark">EJ</span>Eijkman 研究所<small>印度尼西亚</small></a>
      <a class="logo-chip" data-demo="PRA 国际联盟合作"><span class="mark">PRA</span>PRA 国际联盟</a>
    </div>
    <div class="text-center">
      <h3 style="color:#fff;font-size:22px">有靶点或合作意向？给我们留个言</h3>
      <p class="section-sub" style="margin:10px auto 0">contact@mabseek.org · 清华大学医学院</p>
    </div>
    <form class="feedback" id="contact-form">
      <div class="fb-row">
        <input type="text" name="name" placeholder="你的称呼" required>
        <input type="email" name="email" placeholder="邮箱" required>
      </div>
      <textarea name="message" placeholder="简单描述你的靶点 / 需求 / 合作意向" required></textarea>
      <button type="submit" class="btn btn-green fb-submit">提交反馈</button>
    </form>
  </div>
</section>

<!-- ============ 页脚 ============ -->
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <div class="brand"><span class="logo"><svg viewBox="0 0 24 24" fill="none"><path d="M7 3v4c0 2.8 2.2 5 5 5s5 2.2 5 5v4M17 3v4c0 2.8-2.2 5-5 5" stroke="white" stroke-width="2" stroke-linecap="round"/></svg></span><span>MabSeek<small>抗体求索 · 清华大学医学院</small></span></div>
        <p>清华团队 × AI 大模型，让抗体发现从反复试错变成精准编程。</p>
        <div class="footer-social" style="margin-top:18px">
          <span title="微信" data-demo="扫码关注 MabSeek 公众号">💬</span>
          <span title="邮箱" data-demo="联系邮箱：contact@mabseek.org">✉️</span>
        </div>
      </div>
      <div><h5>探索</h5><ul>
        <li><a href="technology.html">技术平台</a></li>
        <li><a href="agent.html">Antibody Agent</a></li>
        <li><a href="education.html">教育</a></li>
      </ul></div>
      <div><h5>社区</h5><ul>
        <li><a href="forum.html">论坛</a></li>
        <li><a href="about.html">了解我们</a></li>
        <li><a href="about.html#news">新闻活动</a></li>
      </ul></div>
      <div><h5>联系</h5><ul>
        <li><a href="#contact">联系我们</a></li>
        <li><a href="mailto:contact@mabseek.org">contact@mabseek.org</a></li>
        <li>清华大学医学院</li>
      </ul></div>
    </div>
    <div class="footer-bottom">
      <span>© 2026 MabSeek 抗体求索 · 清华大学医学院实验室. 保留所有权利。</span>
      <span>紫 + 荧光绿 · 科技与趣味的平衡</span>
    </div>
  </div>
</footer>

<script src="assets/js/main.js"></script>
<script src="assets/js/hero-anim.js"></script>
</body>
</html>
```

- [ ] **Step 2: 浏览器验证 L4 + L5 + 页脚**

reload 后：
- `preview_snapshot` 确认出现"新闻 · 发表 · 活动"（L4）与"与顶尖机构共建生态"（L5）与反馈表单。
- `preview_fill` 分别填 `#contact-form [name="name"]`=`测试`、`[name="email"]`=`a@b.com`、`[name="message"]`=`测试留言`，`preview_click` 提交按钮 `#contact-form .fb-submit`，`preview_snapshot` 确认出现 toast "已收到你的反馈，测试！…"。
- `preview_inspect` 选择 `.news-scroller`，确认 `overflow-x: auto`。
- `preview_console_logs` level=error 为空。

- [ ] **Step 3: 提交**

```bash
git add index.html
git commit -m "feat(mabseek): homepage L4 (news) + L5 (partners/contact) + dark footer"
```

---

## Task 10: 整体验收与响应式

**Files:** 无（仅验证 + 必要微调）

- [ ] **Step 1: 桌面整页走查**

reload `index.html`：
- `preview_screenshot` 通读全页，确认黑白交替顺序为 深(L1)→浅(L2)→深(L3)→浅(L4)→深(L5)，视觉连贯。
- 逐一 `preview_click` 导航 7 项与页脚链接：首页/技术平台(→placeholder)/Antibody Agent/教育/论坛/了解我们均可跳转、无 404（`preview_network` filter=failed 应为空）。

- [ ] **Step 2: 导航滚动态**

`preview_eval` 执行 `window.scrollTo(0, 600)`；`preview_inspect` 选择 `header.nav`，确认已加 `scrolled` 类且背景变为半透明深色（`background-color` 含 alpha 的深色）。

- [ ] **Step 3: reduced-motion 降级**

`preview_eval` 执行：
```js
(function(){var s=document.createElement('style');s.textContent='@media(){}';return matchMedia('(prefers-reduced-motion: reduce)').matches})()
```
说明：无法在真实环境强制切换系统偏好；改为代码审查确认 `hero-anim.js` 的 `reduced` 分支调用 `staticFrame()`。人工确认该分支存在即可（已在 Task 4 代码内）。

- [ ] **Step 4: 移动端响应式**

`preview_resize` preset=`mobile`，reload：
- `preview_screenshot` 确认导航折叠为汉堡按钮；`preview_click` `.nav-toggle`，`preview_snapshot` 确认菜单展开为深色。
- 确认痛点四格变为单列、近况可横向滑动、英雄区 CTA 居中。
- 完成后 `preview_resize` preset=`desktop` 复位。

- [ ] **Step 5: 最终提交（如有微调）**

```bash
git add -A
git commit -m "chore(mabseek): P1 homepage responsive + verification fixes"
```

（若 Step 1–4 无需改动，可跳过本步。）

---

## Self-Review 记录

- **Spec 覆盖**：§2 决策（静态/i18n占位/内容权威）→ 贯穿各 Task；§3 配色 → Task 2；§4.1 导航 → Task 7 Step1；§4.2 占位页 → Task 6；§4.3 页脚 → Task 9；§5 L1–L5 → Task 7–9；§6 动画 → Task 4；§7 文件改动 → 全部 Task 覆盖（launch.json=Task1）；§9 验证 → 各 Task 验证步 + Task 10。无遗漏。
- **占位符扫描**：无 TBD/TODO；所有代码步给出完整代码。
- **一致性**：canvas id 统一 `#antibody-canvas`（Task4 定义、Task7 使用、Task3 样式）；表单 id 统一 `#contact-form`（Task5 处理、Task9 标记）；类名 `.section-dark/.section-light/.pain-grid/.news-scroller/.feedback/.link-more/.txt-neon/.txt-green/.nav--on-dark` 在 Task2/3 定义、Task7–9 使用，一致。
