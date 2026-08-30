# MabSeek P3b · 教育页 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 把 `education.html` 对齐 P1/P2 外壳（七区导航 + 统一页脚），并在既有「交互式知识图谱」(`#graph`) 之后新增「学习路径 · 知识地图」章节树（Hello算法式，章→节，**复用页面既有 accordion 组件、零新 JS**，四章十二节、内容取自站内主题+领域标签、无数值/机构名）；既有内容本体全部保留不改。

**Architecture:** 纯静态站，零后端。样式**增量**追加到 `style.css`（P3a 段之后）；知识地图分区复用页面既有 `.accordion/.acc-item/.acc-head/.acc-body` 结构，展开/收起由 education.html 既有内联脚本（按 `.acc-head` 全选绑定）自动接管，加载时对 `.acc-item.open` 设置 maxHeight——**不新增 JS**。教育页保持既有浅色节奏，不加深色带。

**Tech Stack:** 原生 HTML / CSS（CSS 变量 + 工具类）。无构建、无依赖、无新增脚本。复用 `main.js`/`knowledge-graph.js`/既有内联脚本。验证用 `preview_*`（root `.claude/launch.json` 的 `mabseek` 项，端口 8777；预览标签可能缓存旧 css，取计算样式前需 cache-bust）。

**内容来源（权威，不杜撰）：** 章节内容 = 站内已有主题（课时卡标题 + 技术平台三支柱 / Agent 能力）重组为学习路径 + 少量领域通用主题标签；每节描述为定性一句话；**无课时数、时长、百分比、机构名等数值/硬事实**。用户已确认此来源可接受。

---

## 文件结构

| 文件 | 动作 | 职责 |
|---|---|---|
| `assets/css/style.css` | 改（**末尾追加** P3b 段） | `.kmap` 章节树样式：`.kmap-node` 节行布局、`.desc`、`.kmap-link(.green)`、`.kmap` 内 `.acc-head` 微调、响应式 |
| `education.html` | 改 | 导航→P1 七区；页脚→P1 页脚；`#graph` 分区后插入 `#map` 知识地图分区（复用 `.accordion`） |

**不动：** `index.html` / `technology.html` / `agent.html` / `forum.html` / `about.html` / `main.js` / `knowledge-graph.js` / education.html 既有内联脚本（弹幕 + accordion）。

---

## Task 1: CSS — 知识地图章节树样式（追加）

**Files:**
- Modify: `assets/css/style.css`（在文件**末尾**继续追加，不改动既有规则）

- [ ] **Step 1: 追加 P3b 样式段**

在 `assets/css/style.css` 末尾追加：

```css
/* ============================================================
   P3b 教育页：知识地图章节树（增量，复用 .accordion 组件，不影响其它页面）
   ============================================================ */
.kmap { margin-top: 8px; }
.kmap .acc-head { font-size: 15px; }
.kmap .acc-body .inner { padding-top: 4px; }
.kmap-node { display: flex; align-items: baseline; gap: 12px; flex-wrap: wrap;
  padding: 12px 0; border-bottom: 1px dashed var(--line); }
.kmap-node:last-child { border-bottom: 0; }
.kmap-node .kt { font-weight: 700; font-size: 14px; flex: 0 0 auto; }
.kmap-node .desc { font-size: 13px; color: var(--ink-3); flex: 1 1 200px; }
.kmap-node .kmap-links { display: flex; gap: 14px; flex: 0 0 auto; }
.kmap-link { font-size: 12.5px; font-weight: 700; color: var(--purple); white-space: nowrap; }
.kmap-link.green { color: var(--green); }
@media (max-width: 640px) {
  .kmap-node { flex-direction: column; gap: 6px; }
  .kmap-node .kmap-links { margin-top: 2px; }
}
```

- [ ] **Step 2: 校验括号平衡（不破坏既有规则）**

Run: `node -e "const s=require('fs').readFileSync('mabseek/assets/css/style.css','utf8');const o=(s.match(/{/g)||[]).length,c=(s.match(/}/g)||[]).length;console.log('open',o,'close',c,o===c?'OK':'MISMATCH')"`
Expected: `open N close N OK`（左右花括号数量相等）。

- [ ] **Step 3: 提交**

```bash
git add mabseek/assets/css/style.css
git commit -m "feat(mabseek): add P3b education knowledge-map tree styles"
```

---

## Task 2: education.html — 外壳统一 + 新增知识地图分区

**Files:**
- Modify: `education.html`

> 先 `Read` `mabseek/education.html` 全文，确保 Edit 精确匹配。保留 `<head>` 内页面局部 `<style>` 块与所有既有分区内容，仅做下列 3 处编辑。

- [ ] **Step 1: 替换导航块**

将 `education.html` 中 `<!-- 导航 -->` 到其 `</header>` 的整块替换为（P1 七区导航，`教育` active，普通 `.nav`）：

```html
<!-- 导航 -->
<header class="nav">
  <div class="container">
    <a class="brand" href="index.html"><span class="logo"><svg viewBox="0 0 24 24" fill="none"><path d="M7 3v4c0 2.8 2.2 5 5 5s5 2.2 5 5v4M17 3v4c0 2.8-2.2 5-5 5" stroke="white" stroke-width="2" stroke-linecap="round"/></svg></span><span>MabSeek<small>抗体求索 · 清华大学医学院</small></span></a>
    <nav class="nav-links">
      <a href="index.html">首页</a>
      <a href="technology.html">技术平台</a>
      <a href="agent.html">Antibody Agent</a>
      <a href="education.html" class="active">教育</a>
      <a href="forum.html">论坛</a>
      <a href="about.html">了解我们</a>
    </nav>
    <div class="nav-actions"><a href="index.html#contact" class="btn btn-green">联系我们</a></div>
    <button class="nav-toggle" aria-label="菜单"><span></span><span></span><span></span></button>
  </div>
</header>
```

- [ ] **Step 2: 在「交互式知识图谱」分区之后、「配套学习资源」分区之前，插入知识地图新分区**

定位既有 `<!-- 交互式知识图谱 -->` 分区（`<section class="section" id="graph">`）的**闭合 `</section>`**，在其后、`<!-- 配套学习资源 -->` 注释之前，插入：

```html
<!-- ============ 学习路径 · 知识地图（章节树，新增） ============ -->
<section class="section section-light" id="map">
  <div class="container">
    <div style="margin-bottom:34px">
      <span class="eyebrow reveal">学习路径 · 知识地图</span>
      <h2 class="section-title reveal d1">像翻书一样，顺着章节走完抗体与疫苗</h2>
      <p class="section-sub reveal d2">与关系型「知识图谱」互补：图谱看知识点之间的关联，地图给你一条从基础到 AI 抗体发现的学习路径。点开任一章展开小节，可直达对应视频片段或唤起 Antibody Agent。</p>
    </div>
    <div class="accordion kmap reveal">
      <div class="acc-item open">
        <div class="acc-head">第 1 章 · 免疫与疫苗基础 <span class="arrow">▾</span></div>
        <div class="acc-body"><div class="inner">
          <div class="kmap-node"><span class="kt">疫苗发展史与分类</span><span class="desc">从病毒免疫到疫苗研发的知识起点</span><span class="kmap-links"><a class="kmap-link" href="#video">▶ 视频片段</a><a class="kmap-link green" href="agent.html">🤖 问 Agent</a></span></div>
          <div class="kmap-node"><span class="kt">免疫系统如何识别抗原</span><span class="desc">先天与适应性免疫的基本机制</span><span class="kmap-links"><a class="kmap-link" href="#video">▶ 视频片段</a><a class="kmap-link green" href="agent.html">🤖 问 Agent</a></span></div>
          <div class="kmap-node"><span class="kt">疫苗免疫应答基础</span><span class="desc">免疫记忆如何建立</span><span class="kmap-links"><a class="kmap-link" href="#video">▶ 视频片段</a><a class="kmap-link green" href="agent.html">🤖 问 Agent</a></span></div>
        </div></div>
      </div>
      <div class="acc-item">
        <div class="acc-head">第 2 章 · 抗体与中和机制 <span class="arrow">▾</span></div>
        <div class="acc-body"><div class="inner">
          <div class="kmap-node"><span class="kt">抗体在疫苗中的作用</span><span class="desc">抗体如何提供保护</span><span class="kmap-links"><a class="kmap-link" href="#video">▶ 视频片段</a><a class="kmap-link green" href="agent.html">🤖 问 Agent</a></span></div>
          <div class="kmap-node"><span class="kt">抗体结构与功能</span><span class="desc">可变区、恒定区与识别原理</span><span class="kmap-links"><a class="kmap-link" href="#video">▶ 视频片段</a><a class="kmap-link green" href="agent.html">🤖 问 Agent</a></span></div>
          <div class="kmap-node"><span class="kt">中和抗体</span><span class="desc">阻断病原体的关键机制</span><span class="kmap-links"><a class="kmap-link" href="#video">▶ 视频片段</a><a class="kmap-link green" href="agent.html">🤖 问 Agent</a></span></div>
        </div></div>
      </div>
      <div class="acc-item">
        <div class="acc-head">第 3 章 · AI 抗体发现 <span class="arrow">▾</span></div>
        <div class="acc-body"><div class="inner">
          <div class="kmap-node"><span class="kt">抗体序列设计</span><span class="desc">从一句话需求到候选序列</span><span class="kmap-links"><a class="kmap-link" href="#video">▶ 视频片段</a><a class="kmap-link green" href="agent.html">🤖 问 Agent</a></span></div>
          <div class="kmap-node"><span class="kt">结构与亲和力预测</span><span class="desc">动手实验前的虚拟筛选</span><span class="kmap-links"><a class="kmap-link" href="#video">▶ 视频片段</a><a class="kmap-link green" href="agent.html">🤖 问 Agent</a></span></div>
          <div class="kmap-node"><span class="kt">成药性评估</span><span class="desc">早期规避开发风险</span><span class="kmap-links"><a class="kmap-link" href="#video">▶ 视频片段</a><a class="kmap-link green" href="agent.html">🤖 问 Agent</a></span></div>
        </div></div>
      </div>
      <div class="acc-item">
        <div class="acc-head">第 4 章 · 干湿闭环与验证 <span class="arrow">▾</span></div>
        <div class="acc-body"><div class="inner">
          <div class="kmap-node"><span class="kt">VLP 抗原制备</span><span class="desc">保持天然构象</span><span class="kmap-links"><a class="kmap-link" href="#video">▶ 视频片段</a><a class="kmap-link green" href="agent.html">🤖 问 Agent</a></span></div>
          <div class="kmap-node"><span class="kt">高通量分离与表征</span><span class="desc">自动化湿实验验证</span><span class="kmap-links"><a class="kmap-link" href="#video">▶ 视频片段</a><a class="kmap-link green" href="agent.html">🤖 问 Agent</a></span></div>
          <div class="kmap-node"><span class="kt">数据回流训练</span><span class="desc">越用越准的闭环</span><span class="kmap-links"><a class="kmap-link" href="#video">▶ 视频片段</a><a class="kmap-link green" href="agent.html">🤖 问 Agent</a></span></div>
        </div></div>
      </div>
    </div>
  </div>
</section>
```

- [ ] **Step 3: 替换页脚为 P1 页脚**

将 `education.html` 中 `<!-- 页脚 -->` 到其 `</footer>` 的整块替换为：

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

- [ ] **Step 4: 浏览器验证**

预览服务器（root `.claude/launch.json` 的 `mabseek` 项，端口 8777；若未运行 `preview_start` name=`mabseek`）。导航到 `http://localhost:8777/education.html`，reload。**取计算样式前先 cache-bust**（重挂带 `?ts=` 查询串的 style.css，确认服务的是含 `.kmap` 的最新 css）。
- `preview_snapshot`：导航 7 项（首页/技术平台/Antibody Agent/教育/论坛/了解我们）且 `教育` active；出现「学习路径 · 知识地图」标题与四章（第 1–4 章）；页脚为「探索/社区/联系」三列。
- 章节树展开：第 1 章默认展开（`.acc-item.open`，其 `.acc-body` 计算 `max-height` > 0）；`preview_click` 第 2 章的 `.acc-head` → 其 `.acc-body` 展开；再点收起。
- 既有资源区 accordion 仍正常（页面存在两处 `.accordion`，脚本按 `.acc-head` 全选绑定，互不干扰）：`preview_click` 资源区某 `.acc-head` 可正常展开/收起。
- 既有「知识图谱」`#kg` 力导向图仍渲染；弹幕仍运行（`preview_console_logs` 无报错即可）。
- A `#map` 分区文本**无数值/机构名**（仅定性主题与描述）。
- 迷你链接：知识地图内 `▶ 视频片段` href=`#video`、`🤖 问 Agent` href=`agent.html`；导航「联系我们」→`index.html#contact`；`preview_network` filter=failed 为空。
- `preview_console_logs` level=error 为空。

- [ ] **Step 5: 提交**

```bash
git add mabseek/education.html
git commit -m "feat(mabseek): harmonize education shell + add knowledge-map tree"
```

---

## Task 3: 整体验收与响应式

**Files:** 无（仅验证 + 必要微调）

- [ ] **Step 1: 桌面走查**

reload `education.html`（cache-bust）：
- `preview_screenshot` 通读：页头 → banner+三栏 → 课时播放（含深色播放器）→ 知识图谱 → **知识地图（新增，浅色）** → 配套资源 → 跨板块联动 → 讲座/成长，视觉连贯。
- 导航 `教育` 高亮 active。
- 逐一 `preview_click`：知识地图内 `▶ 视频片段` 滚动到 `#video`；`🤖 问 Agent` 跳 `agent.html`（HTTP 200）；导航「联系我们」→`index.html#contact`；页脚链接 `preview_network` filter=failed 应为空。

- [ ] **Step 2: 双 accordion 复核**

- `preview_click` 知识地图第 3、4 章 `.acc-head` 展开/收起，maxHeight 随 open 态切换。
- `preview_click` 配套资源区三项 `.acc-head`，确认仍独立正常（与知识地图互不影响）。

- [ ] **Step 3: 移动端响应式**

`preview_resize` preset=`mobile`，reload：
- `preview_screenshot`：导航折叠为汉堡；知识地图章节树在窄屏 `.kmap-node` 竖排（标题/描述/链接换行不溢出）。
- `preview_click` `.nav-toggle` 展开菜单；`preview_click` 一章 `.acc-head` 确认窄屏可展开。
- 完成 `preview_resize` preset=`desktop` 复位。

- [ ] **Step 4: 最终提交（如有微调）**

```bash
git add -A
git commit -m "chore(mabseek): P3b education page responsive + verification fixes"
```

（若 Step 1–3 无需改动，可跳过本步、不建空提交。）

---

## Self-Review 记录

- **Spec 覆盖**：§4/§5.1 导航 → Task2(Step1)；§5.2 页脚 → Task2(Step3)；§6 知识地图（结构/复用 accordion/节行/章节内容）→ Task1(CSS)+Task2(Step2 HTML)；§7 文件改动 → 全覆盖；§8 验证 → Task2(Step4)/Task3。无遗漏。
- **占位符扫描**：无 TBD/TODO；所有步给出完整代码或完整替换块（含全部 12 节 HTML）。
- **一致性**：新类 `.kmap/.kmap-node/.kmap-node .kt/.desc/.kmap-links/.kmap-link(.green)`（Task1 定义、Task2 使用）一致；复用 P1 外壳类 `.nav/.nav-links/.footer/.footer-grid/.footer-social/.footer-bottom/.btn/.btn-green/.eyebrow/.section-title/.section-sub/.section-light` 与页面既有 `.accordion/.acc-item(.open)/.acc-head/.acc-body/.inner/.arrow` 均已存在；CSS 变量 `--line/--ink-3/--purple/--green` 教育页既有 CSS 已用。
- **不杜撰**：`#map` 仅定性主题 + 站内内容重组 + 领域通用标签；无课时数/时长/百分比/机构名。
- **零新 JS**：展开靠 education.html 既有内联脚本（`.acc-head` 全选绑定 + 加载时 `.acc-item.open` 设 maxHeight），新增第 1 章 `open` 会自动展开；两处 accordion 不冲突。
- **YAGNI**：不加深色带、不重写既有内容、不改其它页面、不接后端、不做 i18n。
