# MabSeek P3a · Antibody Agent 页 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 把 `agent.html` 对齐到 P1/P2 外壳（七区导航 + 统一页脚 + 深/浅节奏），并新增「案例示范」深色带——3 张卡片各一个纯 CSS/JS 循环动图（一句话→候选序列 / 亲和力筛选重排 / 表位脉冲），叠加 SciencePal 外链；既有内容本体保留不改，全程无杜撰数值/机构名。

**Architecture:** 纯静态站，零后端。样式**增量**追加到 `style.css`（P2 段之后）；动图时序编排放入新文件 `assets/js/agent-cases.js`（以 `.case-anim` 存在为守卫，其它页面惰性）；`agent.html` 更新外壳与分区并新增 A3。页面局部 `<style>`（chat-ui/flow/agent-chip）保留不动。

**Tech Stack:** 原生 HTML / CSS（CSS 变量 + `@keyframes` + `prefers-reduced-motion` 兜底）/ 原生 JS。无构建、无依赖。复用 `main.js`（导航/toast/reveal）与 `agent-demo.js`（hero 对话演示）；不引入 `hero-anim.js`。验证用 `preview_*`（root `.claude/launch.json` 的 `mabseek` 项，端口 8777；预览标签可能缓存旧 css/js，取计算样式前需 cache-bust）。

**内容来源（权威，不杜撰）：** 既有内容本体源自 PRD-0808 + P1，仅迁移外壳/主题；A3 动图**仅演示过程与 UI 动效**，不出现任何数值/单位/百分比/机构名（`.ca2-bar` 宽度为纯样式、页面不显示数字）；SciencePal 为 PRD-0825 点名的参考外链。

---

## 文件结构

| 文件 | 动作 | 职责 |
|---|---|---|
| `assets/css/style.css` | 改（**末尾追加** P3a 段） | `.case-anim*` 卡片与舞台、三种动图关键帧（打字机 caret / 序列点亮 / 条重排 / 表位脉冲）、`prefers-reduced-motion` 兜底、`.chip-link` 外链、响应式 |
| `assets/js/agent-cases.js` | **新建** | 卡1 打字机+序列点亮循环、卡2 候选条重排循环；守卫式（无 `.case-anim` 不运行）；reduced-motion 显示静止终态 |
| `agent.html` | 改 | 导航→P1 七区；页脚→P1 页脚；A2/A5 加 `section-light`；**新增 A3 深色案例示范**；SciencePal chip 加外链；轻量强调色迁移；引入 `agent-cases.js` |

**不动：** `index.html` / `technology.html` / `education.html` / `forum.html` / `about.html` / `main.js` / `agent-demo.js` / `hero-anim.js`。

---

## Task 1: CSS — 案例示范动图样式（追加）

**Files:**
- Modify: `assets/css/style.css`（在文件**末尾**继续追加，不改动既有规则）

- [ ] **Step 1: 追加 P3a 样式段**

在 `assets/css/style.css` 末尾追加：

```css
/* ============================================================
   P3a Antibody Agent 页：案例示范循环动图 + SciencePal 外链（增量，不影响其它页面）
   ============================================================ */

/* A3 案例示范卡片（深色带内） */
.case-anim-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 40px; }
.case-anim { background: var(--card-dark); border: 1px solid var(--line-dark); border-radius: var(--radius);
  padding: 22px; display: flex; flex-direction: column; }
.case-anim h3 { color: #fff; font-size: 16px; }
.case-anim p { color: var(--ink-on-dark-2); font-size: 13px; margin-top: 6px; }
.case-stage { position: relative; height: 150px; margin-top: 16px; border-radius: 12px; overflow: hidden;
  background: radial-gradient(80% 100% at 50% 0%, rgba(124,77,255,.14), transparent 60%), #0b0f1e;
  border: 1px solid var(--line-dark); display: flex; align-items: center; justify-content: center; }

/* 卡1：一句话 → 候选序列（打字机 + 序列点亮） */
.ca1-prompt { position: absolute; top: 14px; left: 14px; right: 14px; font-size: 12px; color: var(--green-neon);
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace; white-space: nowrap; overflow: hidden; }
.ca1-prompt::after { content: "▍"; animation: ca1blink 1s step-end infinite; }
@keyframes ca1blink { 50% { opacity: 0; } }
.ca1-seq { display: flex; gap: 6px; margin-top: 18px; }
.ca1-seq i { width: 16px; height: 22px; border-radius: 4px; background: rgba(0,224,164,.18);
  border: 1px solid rgba(0,224,164,.35); opacity: .2; transition: opacity .3s, background .3s; }
.ca1-seq i.on { opacity: 1; background: var(--green-neon); }

/* 卡2：亲和力虚拟筛选排序（候选条重排，宽度为纯样式、不显示数值） */
.ca2-bars { position: relative; width: 78%; height: 110px; }
.ca2-bar { position: absolute; left: 0; top: 0; height: 16px; width: 0; border-radius: 8px;
  background: linear-gradient(90deg, var(--purple), var(--green-neon));
  transition: transform .6s cubic-bezier(.5,0,.2,1), width .6s ease; }

/* 卡3：结构 · 表位识别（纯 CSS 脉冲） */
.ca3-mol { position: relative; width: 110px; height: 110px; }
.ca3-anti { position: absolute; inset: 0; margin: auto; width: 70px; height: 70px; border-radius: 50%;
  background: radial-gradient(circle at 40% 40%, rgba(124,77,255,.5), rgba(124,77,255,.12));
  border: 1px solid rgba(124,77,255,.5); }
.ca3-epi { position: absolute; top: 14px; right: 8px; width: 26px; height: 26px; border-radius: 50%;
  background: var(--green-neon); box-shadow: 0 0 0 0 rgba(0,224,164,.5); animation: ca3pulse 2.2s ease-in-out infinite; }
@keyframes ca3pulse {
  0%, 100% { transform: scale(.85); box-shadow: 0 0 0 0 rgba(0,224,164,.5); }
  50% { transform: scale(1.1); box-shadow: 0 0 0 12px rgba(0,224,164,0); }
}

/* SciencePal 外链（矩阵浅色区内） */
.chip-link { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 700;
  color: var(--purple); margin-top: 6px; }

/* reduced-motion：动画停在静止终态 */
@media (prefers-reduced-motion: reduce) {
  .ca1-prompt::after { animation: none; }
  .ca1-seq i { opacity: 1; background: var(--green-neon); }
  .ca2-bar { transition: none; }
  .ca3-epi { animation: none; }
}

@media (max-width: 860px) { .case-anim-grid { grid-template-columns: 1fr; } }
```

- [ ] **Step 2: 校验括号平衡（不破坏既有规则）**

Run: `node -e "const s=require('fs').readFileSync('mabseek/assets/css/style.css','utf8');const o=(s.match(/{/g)||[]).length,c=(s.match(/}/g)||[]).length;console.log('open',o,'close',c,o===c?'OK':'MISMATCH')"`
Expected: `open N close N OK`（左右花括号数量相等）。

- [ ] **Step 3: 提交**

```bash
git add mabseek/assets/css/style.css
git commit -m "feat(mabseek): add P3a agent case-demo animation styles"
```

---

## Task 2: JS — 案例示范时序编排（新建）

**Files:**
- Create: `assets/js/agent-cases.js`

- [ ] **Step 1: 新建 `assets/js/agent-cases.js`，写入完整内容**

```js
/* MabSeek · Antibody Agent 页「案例示范」循环动图
   守卫式：无 .case-anim 元素则不运行（其它页面惰性、零副作用）。
   prefers-reduced-motion: reduce 时显示静止终态、不启动循环。 */
(function () {
  if (!document.querySelector('.case-anim')) return;
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* 卡1：打字机提示 + 候选序列逐格点亮，循环 */
  var prompt = document.querySelector('.ca1-prompt');
  var seq = document.querySelectorAll('.ca1-seq i');
  var promptText = '描述靶点与研发目标…';
  if (prompt && seq.length) {
    if (reduce) {
      prompt.textContent = promptText;
      for (var s0 = 0; s0 < seq.length; s0++) { seq[s0].classList.add('on'); }
    } else {
      var run1 = function () {
        var ti = 0;
        prompt.textContent = '';
        for (var s = 0; s < seq.length; s++) { seq[s].classList.remove('on'); }
        var typer = setInterval(function () {
          prompt.textContent = promptText.slice(0, ti + 1);
          ti++;
          if (ti >= promptText.length) {
            clearInterval(typer);
            var si = 0;
            var filler = setInterval(function () {
              if (seq[si]) { seq[si].classList.add('on'); }
              si++;
              if (si >= seq.length) { clearInterval(filler); setTimeout(run1, 1600); }
            }, 170);
          }
        }, 90);
      };
      run1();
    }
  }

  /* 卡2：候选条按位次循环重排（宽度纯样式，不显示数值） */
  var bars = document.querySelectorAll('.ca2-bar');
  if (bars.length) {
    var widths = [92, 74, 60, 48, 36];
    for (var b = 0; b < bars.length; b++) { bars[b].style.width = widths[b] + '%'; }
    var slots = [0, 1, 2, 3, 4];
    var place = function () {
      for (var i = 0; i < bars.length; i++) {
        bars[i].style.transform = 'translateY(' + (slots[i] * 22) + 'px)';
      }
    };
    place();
    if (!reduce) { setInterval(function () { slots.unshift(slots.pop()); place(); }, 1800); }
  }
})();
```

- [ ] **Step 2: 语法自检**

Run: `node --check mabseek/assets/js/agent-cases.js && echo OK`
Expected: `OK`

- [ ] **Step 3: 提交**

```bash
git add mabseek/assets/js/agent-cases.js
git commit -m "feat(mabseek): add P3a agent case-demo animation script"
```

---

## Task 3: agent.html — 外壳统一 + 新增 A3 + SciencePal 外链

**Files:**
- Modify: `agent.html`

> 保留 `<head>` 内页面局部 `<style>` 块（chat-ui / flow / agent-chip）与既有分区内容本体，仅按下列 4 处编辑。

- [ ] **Step 1: 替换导航块**

将 `agent.html` 中 `<!-- 导航 -->` 到其 `</header>` 的整块替换为（P1 七区导航，`.nav` 保持普通、不加 `nav--on-dark`，因 hero 为浅色）：

```html
<!-- 导航 -->
<header class="nav">
  <div class="container">
    <a class="brand" href="index.html"><span class="logo"><svg viewBox="0 0 24 24" fill="none"><path d="M7 3v4c0 2.8 2.2 5 5 5s5 2.2 5 5v4M17 3v4c0 2.8-2.2 5-5 5" stroke="white" stroke-width="2" stroke-linecap="round"/></svg></span><span>MabSeek<small>抗体求索 · 清华大学医学院</small></span></a>
    <nav class="nav-links">
      <a href="index.html">首页</a>
      <a href="technology.html">技术平台</a>
      <a href="agent.html" class="active">Antibody Agent</a>
      <a href="education.html">教育</a>
      <a href="forum.html">论坛</a>
      <a href="about.html">了解我们</a>
    </nav>
    <div class="nav-actions"><a href="index.html#contact" class="btn btn-green">联系我们</a></div>
    <button class="nav-toggle" aria-label="菜单"><span></span><span></span><span></span></button>
  </div>
</header>
```

- [ ] **Step 2: 核心能力分区加 `section-light`，强调色迁移**

将核心能力分区开头：
```html
<!-- 核心能力 -->
<section class="section">
```
改为：
```html
<!-- 核心能力 -->
<section class="section section-light">
```
并把该分区标题里的 `<span class="grad-text">贯穿抗体发现全流程</span>` 改为 `<span class="txt-green">贯穿抗体发现全流程</span>`。

- [ ] **Step 3: 在「核心能力」分区 `</section>` 之后、「干湿闭环」分区之前，插入新增 A3 案例示范分区**

```html
<!-- ============ A3 案例示范（深，循环动图，新增） ============ -->
<section class="section section-dark">
  <div class="container">
    <div class="text-center" style="margin-bottom:8px">
      <span class="eyebrow reveal">案例示范</span>
      <h2 class="section-title reveal d1">看 Antibody Agent <span class="txt-neon">怎么工作</span></h2>
      <p class="section-sub reveal d2">三段循环演示，直观呈现从需求到候选、筛选与结构理解的过程（示意动效，非真实结果数据）。</p>
    </div>
    <div class="case-anim-grid reveal d2">
      <div class="case-anim">
        <h3>一句话 → 候选序列</h3>
        <p>用自然语言描述靶点与目标，Agent 逐步生成候选序列。</p>
        <div class="case-stage" aria-hidden="true">
          <div class="ca1-prompt"></div>
          <div class="ca1-seq"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
        </div>
      </div>
      <div class="case-anim">
        <h3>亲和力虚拟筛选排序</h3>
        <p>动手实验前完成虚拟打分与排序，把候选按优先级重排。</p>
        <div class="case-stage" aria-hidden="true">
          <div class="ca2-bars"><span class="ca2-bar"></span><span class="ca2-bar"></span><span class="ca2-bar"></span><span class="ca2-bar"></span><span class="ca2-bar"></span></div>
        </div>
      </div>
      <div class="case-anim">
        <h3>结构 · 表位识别</h3>
        <p>结构建模与表位识别，理解「结合在哪里、为什么结合」。</p>
        <div class="case-stage" aria-hidden="true">
          <div class="ca3-mol"><span class="ca3-anti"></span><span class="ca3-epi"></span></div>
        </div>
      </div>
    </div>
  </div>
</section>
```

- [ ] **Step 4: 智能体矩阵分区加 `section-light`；SciencePal chip 加外链**

将智能体矩阵分区开头：
```html
<!-- 智能体矩阵 -->
<section class="section" id="agents">
```
改为：
```html
<!-- 智能体矩阵 -->
<section class="section section-light" id="agents">
```
并把 SciencePal chip：
```html
<div class="agent-chip reveal d2"><span class="ai" style="background:var(--purple-050)">🎓</span><div><h4>SciencePal Agent</h4><p>生成式 AI 课程助手</p></div></div>
```
改为：
```html
<div class="agent-chip reveal d2"><span class="ai" style="background:var(--purple-050)">🎓</span><div><h4>SciencePal Agent</h4><p>生成式 AI 课程助手</p><a class="chip-link" href="https://sciencepal.ai" target="_blank" rel="noopener">了解 SciencePal ↗</a></div></div>
```

- [ ] **Step 5: 替换页脚为 P1 页脚**

将 `agent.html` 中 `<!-- 页脚 -->` 到其 `</footer>` 的整块替换为：

```html
<!-- 页脚 -->
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
```

- [ ] **Step 6: 引入 `agent-cases.js`**

将文件末尾脚本块：
```html
<script src="assets/js/main.js"></script>
<script src="assets/js/agent-demo.js"></script>
```
改为：
```html
<script src="assets/js/main.js"></script>
<script src="assets/js/agent-demo.js"></script>
<script src="assets/js/agent-cases.js"></script>
```

- [ ] **Step 7: 浏览器验证**

预览服务器（root `.claude/launch.json` 的 `mabseek` 项，端口 8777；若未运行 `preview_start` name=`mabseek`）。导航到 `http://localhost:8777/agent.html`，reload。**取计算样式前先 cache-bust**（重挂带 `?ts=` 查询串的 style.css，并确保加载了 `agent-cases.js`）。
- `preview_snapshot`：导航为 7 项（首页/技术平台/Antibody Agent/教育/论坛/了解我们）且 `Antibody Agent` active；出现「案例示范」标题与三张卡（一句话→候选序列 / 亲和力虚拟筛选排序 / 结构·表位识别）；页脚为「探索/社区/联系」三列。
- A3 深色：`preview_inspect` A3 `section.section-dark` 背景 ≈ `rgb(7,10,20)`；核心能力/矩阵为浅色。
- 动图运行：`preview_inspect` 某个 `.ca1-seq i` 一段时间后 className 出现 `on`（序列点亮）；`.ca2-bar` 的内联 `transform` 随时间变化（重排）；`.ca3-epi` 存在脉冲动画规则。
- SciencePal 外链：`preview_inspect` `.chip-link` 的 `href` = `https://sciencepal.ai`、`target` = `_blank`。
- 不杜撰复核：A3 文案与舞台内**无任何数值/百分比/机构名**（`preview_snapshot` A3 区文本仅定性流程标签）。
- `preview_console_logs` level=error 为空。

- [ ] **Step 8: 提交**

```bash
git add mabseek/agent.html
git commit -m "feat(mabseek): harmonize agent page shell + add case-demo section"
```

---

## Task 4: 整体验收与响应式

**Files:** 无（仅验证 + 必要微调）

- [ ] **Step 1: 桌面走查**

reload `agent.html`（cache-bust）：
- `preview_screenshot` 通读：黑白节奏 Hero（浅）→核心能力（浅）→**案例示范（深）**→干湿闭环（浅 bg-soft）→矩阵（浅）→CTA；三张动图各自循环播放，视觉连贯。
- 导航 `Antibody Agent` 高亮 active。
- 逐一 `preview_click`：导航「联系我们」→ `index.html#contact`（HTTP 200）；hero「免费试用」按钮、CTA 按钮锚点可用；页脚链接 `preview_network` filter=failed 应为空。
- hero 对话演示（`#chat-feed`）仍正常滚动（`agent-demo.js` 未受影响）。

- [ ] **Step 2: reduced-motion 复核**

`preview_eval` 注入 `prefers-reduced-motion` 模拟（或用支持的 emulation），reload：
- `.ca1-seq i` 全部为 `on`（静止终态）；`.ca1-prompt` 文本为完整 `描述靶点与研发目标…`；`.ca2-bar` 有宽度且静止；`.ca3-epi` 无脉冲。
- 无 JS 报错。

- [ ] **Step 3: 移动端响应式**

`preview_resize` preset=`mobile`，reload：
- `preview_screenshot`：导航折叠为汉堡；A3 `.case-anim-grid` 堆叠为单列；动图舞台不溢出。
- `preview_click` `.nav-toggle` 展开菜单。
- 完成 `preview_resize` preset=`desktop` 复位。

- [ ] **Step 4: 最终提交（如有微调）**

```bash
git add -A
git commit -m "chore(mabseek): P3a agent page responsive + verification fixes"
```

（若 Step 1–3 无需改动，可跳过本步、不建空提交。）

---

## Self-Review 记录

- **Spec 覆盖**：§4 分区节奏 → Task3(Step2/3/4 加 section-light + 新增 A3)；§5 外壳统一 → Task3(Step1 导航 / Step5 页脚 / Step2 强调色)；§6 A3 动图 → Task1(CSS)+Task2(JS)+Task3(Step3 结构)；§6.2 reduced-motion → Task1(媒体查询)+Task2(reduce 分支)+Task4(Step2 复核)；§7 SciencePal 外链 → Task3(Step4)+Task1(.chip-link)；§8 文件改动 → 全覆盖；§9 验证 → Task3(Step7)/Task4。无遗漏。
- **占位符扫描**：无 TBD/TODO；所有步给出完整代码或完整替换块。
- **一致性**：类名 `.case-anim-grid/.case-anim/.case-stage/.ca1-prompt/.ca1-seq(>i.on)/.ca2-bars/.ca2-bar/.ca3-mol/.ca3-anti/.ca3-epi/.chip-link`（Task1 定义、Task2 选择、Task3 使用）一致；复用类 `.nav/.nav-links/.footer/.footer-grid/.footer-social/.footer-bottom/.section-dark/.section-light/.btn/.btn-green/.eyebrow/.section-title/.section-sub/.txt-neon/.txt-green/.reveal` 均已在既有 css 定义（P1/P2）。CSS 变量 `--card-dark/--line-dark/--ink-on-dark-2/--green-neon/--purple/--radius` 均已存在（P2 用同款）。
- **不杜撰**：A3 文案与舞台仅定性流程标签，无数值/单位/百分比/机构名；`.ca2-bar` 宽度值仅为 CSS 内联样式、页面不渲染为文字；SciencePal 为 PRD-0825 点名参考外链。
- **YAGNI**：不接入真实推理、不做 i18n、不改其它页面、不重写既有内容本体。
