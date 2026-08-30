# MabSeek P2 技术平台页重构 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 把 `technology.html` 从「建设中」占位页重构为完整技术平台页——AI 智能中枢编排的**干湿闭环架构图（悬停/点击展开详情）** + **Data / AI / Wet lab 三支柱交替带** + **经典案例（安巴韦/罗米司韦单抗，仅定性、不编数字）**，每层内链 `agent.html`，沿用 P1 深/浅主题与暗色页脚。

**Architecture:** 纯静态站，零后端。样式**增量**追加到 `style.css`（P1 追加段之后），复用 P1 的 `.section-dark`/`.section-light`/`.nav--on-dark`/`.txt-neon`/`.link-more`/`.btn*` 与基础 `.eyebrow`/`.section-title`/`.section-sub`/`.reveal`/`.footer`。架构图详情展开：桌面用纯 CSS `:hover`/`:focus-within`，触屏用 `main.js` 增量追加的点击 toggle（`.open` 类）。不引入 `hero-anim.js`（本页无 canvas）。

**Tech Stack:** 原生 HTML / CSS（CSS 变量 + 工具类）/ 原生 JS。无构建、无依赖。验证用 `preview_*`（root `.claude/launch.json` 的 `mabseek` 项，端口 8777；预览标签可能缓存旧 css/js，取计算样式前需 cache-bust）。

**内容来源（权威，不杜撰）：** 架构与三支柱内容取自 PRD 技术平台段（paras 040–048）+ P1 已确立的四大痛点解法（VLP、正/负样本数据库、从头设计、结构/亲和力预测、成药性评估 Agent、干湿闭环）；案例仅保留硬事实"安巴韦/罗米司韦单抗 = 中国首个自主知识产权新冠中和抗体、已获批上市"，难度/速度/成本三维度**仅定性、无数值**。

---

## 文件结构

| 文件 | 动作 | 职责 |
|---|---|---|
| `assets/css/style.css` | 改（追加） | P2 段：`.tech-hero`、`.arch-*` 架构图 + 展开详情、`.pillar*` 三支柱交替带、`.case-dims/.case-dim` 案例三维、响应式 |
| `assets/js/main.js` | 改（追加） | `.arch-node` 触屏点击 toggle（`.open`） |
| `technology.html` | 改（整页重构） | 占位页 → T1 英雄 + T2 架构 + T3 三支柱 + T4 案例 + 暗色页脚 |

不动：`index.html` / `agent.html` / `education.html` / `forum.html` / `about.html` / `hero-anim.js`。

---

## Task 1: CSS — 架构图 / 三支柱 / 案例样式

**Files:**
- Modify: `assets/css/style.css`（在文件**末尾**继续追加，不改动既有规则）

- [ ] **Step 1: 追加 P2 样式段**

在 `assets/css/style.css` 末尾追加：

```css
/* ============================================================
   P2 技术平台页：架构图 / 三支柱 / 案例（增量，不影响其它页面）
   ============================================================ */

/* T1 技术平台紧凑英雄区（深） */
.tech-hero { padding: calc(var(--nav-h) + 72px) 0 64px;
  background: radial-gradient(70% 90% at 50% 0%, rgba(124,77,255,.20), transparent 60%), var(--bg-dark); }
.tech-hero h1 { color: #fff; font-size: clamp(28px, 4.4vw, 50px); margin-top: 16px; }
.tech-hero .section-sub { color: var(--ink-on-dark-2); margin-top: 16px; max-width: 720px; }

/* T2 整体架构图 */
.arch-diagram { display: grid; grid-template-columns: minmax(0,1fr) minmax(0,2fr) minmax(0,1fr);
  gap: 18px; align-items: center; margin-top: 40px; }
.arch-core { display: flex; flex-direction: column; gap: 14px; }
.arch-pair { display: grid; grid-template-columns: 1fr auto 1fr; gap: 14px; align-items: center; }
.arch-io { display: flex; flex-direction: column; gap: 12px; }
.arch-node { position: relative; background: var(--card-dark); border: 1px solid var(--line-dark);
  border-radius: var(--radius); padding: 18px; cursor: pointer; outline: none;
  transition: border-color .25s, box-shadow .25s, transform .25s; }
.arch-node:hover, .arch-node:focus-within, .arch-node.open {
  border-color: var(--green-neon); box-shadow: 0 16px 40px rgba(0,224,164,.16); transform: translateY(-2px); }
.arch-hub { text-align: center; border-color: rgba(124,77,255,.5);
  background: linear-gradient(135deg, rgba(124,77,255,.22), rgba(0,224,164,.12)); }
.arch-node .an-badge { display: inline-block; font-size: 12px; font-weight: 800; letter-spacing: .06em;
  color: #04231b; background: var(--green-neon); padding: 3px 12px; border-radius: 999px; margin-bottom: 8px; }
.arch-node .an-title { color: #fff; font-weight: 800; font-size: 16px; }
.arch-node .an-sub { color: var(--ink-on-dark-2); font-size: 13px; margin-top: 4px; }
.arch-detail { max-height: 0; opacity: 0; overflow: hidden;
  transition: max-height .3s ease, opacity .3s ease, margin-top .3s ease; }
.arch-node:hover .arch-detail, .arch-node:focus-within .arch-detail, .arch-node.open .arch-detail {
  max-height: 260px; opacity: 1; margin-top: 12px; }
.arch-detail ul { display: grid; gap: 6px; }
.arch-detail li { color: var(--ink-on-dark-2); font-size: 13px; padding-left: 16px; position: relative; }
.arch-detail li::before { content: "▸"; position: absolute; left: 0; color: var(--green-neon); }
.arch-detail p { color: var(--ink-on-dark-2); font-size: 13px; }
.arch-flow { text-align: center; color: var(--green-neon); font-size: 13px; font-weight: 700; }
.arch-loop { text-align: center; white-space: nowrap; color: var(--green-neon); font-size: 12px; font-weight: 700; }
@media (max-width: 860px) {
  .arch-diagram { grid-template-columns: 1fr; }
  .arch-pair { grid-template-columns: 1fr; }
  .arch-loop { padding: 4px 0; }
}

/* T3 三支柱交替带 */
.pillar { display: grid; grid-template-columns: 1fr 1fr; gap: 48px; align-items: center; }
.pillar--rev .pillar-text { order: 2; }
.pillar-ico { display: inline-grid; place-items: center; width: 52px; height: 52px; border-radius: 14px;
  font-size: 24px; margin-bottom: 14px; background: rgba(124,77,255,.12); }
.pillar-points { display: grid; gap: 10px; margin: 18px 0; }
.pillar-points li { position: relative; padding-left: 26px; }
.pillar-points li::before { content: "✓"; position: absolute; left: 0; font-weight: 800; }
.section-light .pillar-points li::before { color: var(--green-solid); }
.section-dark .pillar-points li { color: var(--ink-on-dark-2); }
.section-dark .pillar-points li::before { color: var(--green-neon); }
.pillar-media { height: 300px; border-radius: var(--radius-lg); position: relative; overflow: hidden;
  border: 1px solid var(--line-dark);
  background: radial-gradient(60% 70% at 30% 30%, rgba(124,77,255,.35), transparent 60%),
              radial-gradient(50% 60% at 80% 70%, rgba(0,224,164,.30), transparent 60%), #0d1122; }
.pillar-media::after { content: ""; position: absolute; inset: 0;
  background-image: radial-gradient(rgba(255,255,255,.12) 1px, transparent 1px); background-size: 22px 22px; }
.section-light .pillar-media { border-color: var(--line); }
@media (max-width: 860px) {
  .pillar { grid-template-columns: 1fr; gap: 28px; }
  .pillar--rev .pillar-text { order: 0; }
  .pillar-media { height: 200px; }
}

/* T4 案例三维 */
.case-dims { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 36px 0; }
.case-dim { background: var(--card-dark); border: 1px solid var(--line-dark); border-radius: var(--radius); padding: 24px; }
.case-dim .cd-k { display: inline-block; font-size: 12px; font-weight: 800; letter-spacing: .08em;
  color: #04231b; background: var(--green-neon); padding: 4px 12px; border-radius: 999px; margin-bottom: 12px; }
.case-dim p { color: var(--ink-on-dark-2); font-size: 14px; }
@media (max-width: 860px) { .case-dims { grid-template-columns: 1fr; } }
```

- [ ] **Step 2: 提交**

```bash
git add mabseek/assets/css/style.css
git commit -m "feat(mabseek): add P2 technology page styles (arch/pillars/case)"
```

---

## Task 2: JS — 架构节点触屏点击展开

**Files:**
- Modify: `assets/js/main.js`（在末尾 IIFE 内、`window.mabToast = showToast;` 之前追加；紧接 P1 的反馈表单块之后）

- [ ] **Step 1: 追加架构节点点击 toggle**

在 `assets/js/main.js` 中 P1 追加的「快速反馈表单」块之后、`window.mabToast = showToast;` 之前，追加：

```js
  /* ---------- 技术平台架构图：触屏/点击展开节点详情（桌面 hover 走 CSS） ---------- */
  var archNodes = document.querySelectorAll('.arch-node');
  for (var ni = 0; ni < archNodes.length; ni++) {
    (function (node) {
      node.addEventListener('click', function () { node.classList.toggle('open'); });
    })(archNodes[ni]);
  }
```

- [ ] **Step 2: 提交**

```bash
git add mabseek/assets/js/main.js
git commit -m "feat(mabseek): toggle arch node detail on tap (technology page)"
```

---

## Task 3: technology.html 整页重构

**Files:**
- Modify（整页重写）: `technology.html`

- [ ] **Step 1: 用完整页面覆盖占位页**

将 `technology.html` 全文替换为：

```html
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>技术平台 · MabSeek 抗体求索 | 清华大学医学院</title>
<meta name="description" content="MabSeek 技术平台：AI 智能中枢编排的干湿闭环架构、Data / AI / Wet lab 三大能力与经典案例（安巴韦/罗米司韦单抗）。">
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

<!-- ============ T1 页头英雄区（深） ============ -->
<section class="tech-hero section-dark">
  <div class="container">
    <span class="eyebrow reveal">技术平台</span>
    <h1 class="reveal d1">从数据到验证，<span class="txt-neon">一条自主可控的抗体发现闭环</span></h1>
    <p class="section-sub reveal d2">AI 智能中枢编排干实验设计与湿实验验证，端到端可追溯——把分散数月的流程，压缩为连续、可下单的闭环。</p>
    <div class="reveal d3" style="margin-top:24px"><a href="agent.html" class="btn btn-green">免费试用 Antibody Agent →</a></div>
  </div>
</section>

<!-- ============ T2 整体技术架构（深，悬停/点击展开） ============ -->
<section class="section section-dark">
  <div class="container">
    <div class="text-center">
      <span class="eyebrow reveal">整体技术架构</span>
      <h2 class="section-title reveal d1">以 <span class="txt-neon">AI 智能中枢</span> 编排的干湿闭环</h2>
      <p class="section-sub reveal d2">把鼠标移到任一环节查看详情（触屏点击展开）。</p>
    </div>
    <div class="arch-diagram reveal d2">
      <div class="arch-io">
        <div class="arch-node" tabindex="0">
          <div class="an-title">靶点 / 科学家</div>
          <div class="an-sub">闭环起点</div>
          <div class="arch-detail"><p>科学家提出靶点与研发目标，平台将其转化为可计算、可验证的设计任务。</p></div>
        </div>
      </div>
      <div class="arch-core">
        <div class="arch-node arch-hub" tabindex="0">
          <div class="an-badge">AI 智能中枢</div>
          <div class="an-title">编排干湿实验 · 统一数据流</div>
          <div class="arch-detail"><ul><li>统一编排 AI 设计与湿实验验证</li><li>数据在干湿之间闭环回流</li><li>全流程可追溯</li></ul></div>
        </div>
        <div class="arch-flow">↓ 编排 ↓</div>
        <div class="arch-pair">
          <div class="arch-node" tabindex="0">
            <div class="an-title">干实验</div>
            <div class="an-sub">AI 从头设计</div>
            <div class="arch-detail"><ul><li>从头序列设计</li><li>结构与亲和力预测</li><li>成药性评估</li></ul></div>
          </div>
          <div class="arch-loop">← 干湿闭环迭代 →</div>
          <div class="arch-node" tabindex="0">
            <div class="an-title">湿实验</div>
            <div class="an-sub">自动化验证</div>
            <div class="arch-detail"><ul><li>VLP 抗原制备</li><li>高通量分离与表征</li><li>数据回流训练</li></ul></div>
          </div>
        </div>
      </div>
      <div class="arch-io">
        <div class="arch-node" tabindex="0">
          <div class="an-title">数据与外部模型</div>
          <div class="an-sub">接入与训练</div>
          <div class="arch-detail"><p>整合正 / 负结合抗体序列数据与外部模型，为 AI 提供精准训练基准。</p></div>
        </div>
      </div>
    </div>
    <div class="text-center" style="margin-top:28px"><a href="agent.html" class="btn btn-outline reveal">进入 Antibody Agent →</a></div>
  </div>
</section>

<!-- ============ T3a Data to train（浅） ============ -->
<section class="section section-light">
  <div class="container">
    <div class="pillar reveal">
      <div class="pillar-text">
        <span class="pillar-ico">🗂️</span>
        <span class="eyebrow green">Data to train · 强调数据</span>
        <h2 class="section-title"><span class="txt-green">Data</span> to train</h2>
        <p class="section-sub">模型的上限由数据决定。我们不止收集正样本，更补齐负样本与完整物理信息，从源头避免"垃圾进垃圾出"。</p>
        <ul class="pillar-points">
          <li>正 / 负结合抗体序列数据库</li>
          <li>完整物理信息，而非只有正样本</li>
          <li>为 AI 提供精准训练基准</li>
        </ul>
        <a href="agent.html" class="link-more">进入 Antibody Agent →</a>
      </div>
      <div class="pillar-media" aria-hidden="true"></div>
    </div>
  </div>
</section>

<!-- ============ T3b AI for science（深） ============ -->
<section class="section section-dark">
  <div class="container">
    <div class="pillar pillar--rev reveal">
      <div class="pillar-text">
        <span class="pillar-ico">🧠</span>
        <span class="eyebrow">AI for science · 强调算法</span>
        <h2 class="section-title"><span class="txt-neon">AI</span> for science</h2>
        <p class="section-sub">以领域模型完成从头设计与精准预测，让抗体从"反复试错"变成"精准编程"。</p>
        <ul class="pillar-points">
          <li>从头抗体序列设计</li>
          <li>结构与亲和力预测</li>
          <li>成药性评估 Agent，早期规避风险</li>
        </ul>
        <a href="agent.html" class="link-more">进入 Antibody Agent →</a>
      </div>
      <div class="pillar-media" aria-hidden="true"></div>
    </div>
  </div>
</section>

<!-- ============ T3c Wet lab to validate（浅） ============ -->
<section class="section section-light">
  <div class="container">
    <div class="pillar reveal">
      <div class="pillar-text">
        <span class="pillar-ico">🧪</span>
        <span class="eyebrow green">Wet lab to validate · 强调自动化湿实验</span>
        <h2 class="section-title"><span class="txt-green">Wet lab</span> to validate</h2>
        <p class="section-sub">自动化湿实验平台承接 AI 设计，快速验证并把真实数据回流，形成越用越准的闭环。</p>
        <ul class="pillar-points">
          <li>VLP 抗原制备，保持天然构象</li>
          <li>高通量分离与表征</li>
          <li>干湿闭环，数据回流训练</li>
        </ul>
        <a href="agent.html" class="link-more">进入 Antibody Agent →</a>
      </div>
      <div class="pillar-media" aria-hidden="true"></div>
    </div>
  </div>
</section>

<!-- ============ T4 经典案例（深） ============ -->
<section class="section section-dark">
  <div class="container">
    <div class="text-center">
      <span class="eyebrow reveal">经典案例</span>
      <h2 class="section-title reveal d1">安巴韦单抗 / 罗米司韦单抗</h2>
      <p class="section-sub reveal d2">中国首个自主知识产权新冠中和抗体，已获批上市。</p>
    </div>
    <div class="case-dims reveal d2">
      <div class="case-dim">
        <div class="cd-k">难度</div>
        <p>面向难攻克靶点，要求高度特异性、以及复杂构建形式的抗体。</p>
      </div>
      <div class="case-dim">
        <div class="cd-k">速度</div>
        <p>AI 从头生成叠加干湿闭环，显著压缩从设计到验证的研发周期。</p>
      </div>
      <div class="case-dim">
        <div class="cd-k">成本</div>
        <p>减少反复试错、降低后期失败率，从而大幅缩减综合研发成本。</p>
      </div>
    </div>
    <div class="text-center"><a href="agent.html" class="btn btn-green reveal">用 Antibody Agent 开始你的项目 →</a></div>
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
        <li><a href="index.html#contact">联系我们</a></li>
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
</body>
</html>
```

- [ ] **Step 2: 浏览器验证**

预览服务器（root `.claude/launch.json` 的 `mabseek` 项，端口 8777；若未运行 `preview_start` name=`mabseek`）。导航到 `http://localhost:8777/technology.html`，reload。**取计算样式前先 cache-bust**（重挂带 `?ts=` 查询串的 style.css / main.js，确认服务的是含 `.arch-node`/`.pillar` 的最新 css）。
- `preview_snapshot`：出现「从数据到验证…」（T1）、「以 AI 智能中枢 编排的干湿闭环」(T2)、三支柱标题（Data to train / AI for science / Wet lab to validate）、「安巴韦单抗 / 罗米司韦单抗」(T4)。
- 架构悬停：`preview_eval` 对 `.arch-hub` 派发 `mouseover` 后，`preview_inspect` 其 `.arch-detail` 的 `max-height`/`opacity` 应从 0 变为展开（或直接验证 `.arch-node:hover .arch-detail` 规则存在；桌面 hover 由 CSS 保证）。
- 架构点击（触屏路径）：`preview_click` 一个 `.arch-node`，确认其获得 `.open` 类（`preview_inspect` className 含 `open`）。
- `preview_inspect` T2 `section.section-dark` 背景 ≈ `rgb(7,10,20)`；T3a `section.section-light` 背景 `rgb(255,255,255)`（黑白交替）。
- 案例层文案**无任何数值**（仅「中国首个自主知识产权新冠中和抗体、已获批上市」为硬事实）。
- `preview_console_logs` level=error 为空。

- [ ] **Step 3: 提交**

```bash
git add mabseek/technology.html
git commit -m "feat(mabseek): rebuild technology page (arch/pillars/case)"
```

---

## Task 4: 整体验收与响应式

**Files:** 无（仅验证 + 必要微调）

- [ ] **Step 1: 桌面走查**

reload `technology.html`（cache-bust）：
- `preview_screenshot` 通读：黑白节奏 深(T1)→深(T2)→浅(T3a)→深(T3b)→浅(T3c)→深(T4)，视觉连贯；架构图三列布局（左输入/中枢+干湿/右数据）。
- 导航 `技术平台` 高亮 active。
- 逐一 `preview_click` 各层 Agent 入口（T1 CTA、T2 outline、三支柱 link-more、T4 CTA）与导航/页脚链接，`preview_network` filter=failed 应为空；Agent 入口均跳 `agent.html`（HTTP 200）。

- [ ] **Step 2: 架构展开交互复核**

- 桌面：`preview_inspect` 规则 `.arch-node:hover .arch-detail` 存在且展开态 `max-height:260px; opacity:1`。
- 触屏：`preview_click` `.arch-node` → 该节点 className 含 `open`；再点一次移除 `open`（toggle）。

- [ ] **Step 3: 移动端响应式**

`preview_resize` preset=`mobile`，reload：
- `preview_screenshot`：导航折叠为汉堡；架构图堆叠为单列（`.arch-diagram`/`.arch-pair` 单列）；三支柱 `.pillar` 单列（媒体在文字下方）；案例三维 `.case-dims` 单列。
- `preview_click` `.nav-toggle` 展开深色菜单；`preview_click` 一个 `.arch-node` 确认点击展开详情在窄屏可用。
- 完成 `preview_resize` preset=`desktop` 复位。

- [ ] **Step 4: 最终提交（如有微调）**

```bash
git add -A
git commit -m "chore(mabseek): P2 technology page responsive + verification fixes"
```

（若 Step 1–3 无需改动，可跳过本步、不建空提交。）

---

## Self-Review 记录

- **Spec 覆盖**：§2 决策（悬停展开/仅定性/内链 agent.html）→ Task1(hover CSS)+Task2(点击 toggle)+Task3(案例仅定性、Agent 入口内链)；§3 T1–T4 → Task3；§4 样式 → Task1；§5 脚本 → Task2；§6 文件改动 → 全覆盖；§8 验证 → Task3/Task4。无遗漏。
- **占位符扫描**：无 TBD/TODO；所有步给出完整代码。
- **一致性**：类名 `.arch-diagram/.arch-node/.arch-hub/.arch-detail/.arch-flow/.arch-loop/.arch-io/.arch-core/.arch-pair`（Task1 定义、Task3 使用）、`.pillar/.pillar--rev/.pillar-text/.pillar-ico/.pillar-points/.pillar-media`（Task1/Task3）、`.case-dims/.case-dim/.cd-k`（Task1/Task3）一致；复用类 `.section-dark/.section-light/.nav--on-dark/.eyebrow(.green)/.section-title/.section-sub/.txt-neon/.txt-green/.link-more/.btn/.btn-green/.btn-outline/.reveal/.footer` 均已在既有 css 定义。Agent 入口链接统一 `agent.html`（T2 outline、三支柱 link-more、T1/T4 CTA）。
- **不杜撰**：案例三维仅定性文字，无数值；唯一硬事实为"中国首个自主知识产权新冠中和抗体/已上市"（PRD para 055）。
