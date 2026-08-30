# MabSeek P3c · 论坛页 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 把 `forum.html` 对齐 P1/P2 外壳（七区导航 + 统一页脚），并新增「AI 智能搜索」(嵌页头，纯 toast 演示) + 「每日/每周社区热榜」(独立整段，复用既有帖子标题 + 演示热度)，既有内容本体全部保留。

**Architecture:** 纯静态站，零后端。样式**增量**追加到 `style.css`（P3c 段，接在 P3b 段之后）。搜索模块的搜索按钮与示例 chip 带 `data-demo`，由 `main.js` 既有 `[data-demo]` 监听自动弹 toast——**搜索零新 JS**；仅热榜「每日/每周」tab 切换追加极少量 JS 到 forum 既有 inline `<script>`（风格同既有 chip-filter）。热榜条目**复用推荐流已有 8 篇帖子标题**，🔥数字为演示占位（与既有 `❤️328` 同性质），无真实机构名/研究数值。

**Tech Stack:** 原生 HTML / CSS（CSS 变量 + 工具类）。无构建、无依赖。复用 `main.js`（`[data-demo]`→toast、`.reveal`、`.nav` 汉堡）。验证用 `preview_*`（root `.claude/launch.json` 的 `mabseek` 项，端口 8777；预览标签可能缓存旧 css，取计算样式前需 cache-bust；取色/取高前注入 `*{transition:none!important}`）。

**内容来源（权威，不杜撰）：** 热榜标题 = 推荐流既有 8 篇 `.post` 标题原样复用；每日/每周为同一批标题的不同排序（各取 6 条）；名次为排名，🔥为演示占位数字。搜索示例问句取自站内主题。**无课时数/机构名/研究数据。** 用户已确认此来源可接受。

---

## 文件结构

| 文件 | 动作 | 职责 |
|---|---|---|
| `assets/css/style.css` | 改（**末尾追加** P3c 段） | `.forum-search/.fs-*` 搜索样式；`.hot-tabs/.hot-tab/.hot-list/.hot-row/.hot-rank/.hot-title/.hot-cat/.hot-fire` 热榜样式；响应式 |
| `forum.html` | 改 | 导航→P1 七区；hero 内插入 `.forum-search`；page-hero 后插入 `#hot` 热榜段；页脚→P1 页脚；inline `<script>` 追加热榜 tab 切换 |

**不动：** `index.html` / `technology.html` / `agent.html` / `education.html` / `about.html` / `main.js` / `knowledge-graph.js`；forum.html 既有页面局部 `<style>`、既有 chip-filter 逻辑、所有既有分区内容（推荐流 8 卡、四条内容线、关注动态、冷启动）。

---

## Task 1: CSS — 搜索 + 热榜样式（追加）

**Files:**
- Modify: `assets/css/style.css`（在文件**末尾**继续追加，不改动既有规则）

- [ ] **Step 1: 追加 P3c 样式段**

在 `assets/css/style.css` 末尾（现有 P3b 知识地图段之后）追加：

```css
/* ============================================================
   P3c 论坛页：AI 智能搜索 + 每日/每周热榜（增量，不影响其它页面）
   ============================================================ */
/* --- AI 智能搜索（嵌入 page-hero） --- */
.forum-search { margin-top: 26px; max-width: 640px; }
.fs-box { display: flex; align-items: center; gap: 10px; background: #fff;
  border: 1px solid var(--line); border-radius: 999px; padding: 8px 8px 8px 18px;
  box-shadow: var(--sh-sm); }
.fs-ico { font-size: 17px; flex: 0 0 auto; }
.fs-input { flex: 1 1 auto; min-width: 0; border: 0; outline: 0; background: transparent;
  font-size: 15px; color: var(--ink); }
.fs-input::placeholder { color: var(--ink-3); }
.fs-btn { flex: 0 0 auto; }
.fs-examples { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
.fs-label { font-size: 13px; color: var(--ink-3); }
.fs-chip { font-size: 13px; font-weight: 600; color: var(--ink-2); background: #fff;
  border: 1px solid var(--line); border-radius: 999px; padding: 5px 13px; cursor: pointer; transition: .2s; }
.fs-chip:hover { border-color: var(--purple-400); color: var(--purple); }

/* --- 每日/每周社区热榜 --- */
.hot-tabs { display: flex; gap: 10px; margin-bottom: 20px; }
.hot-tab { font-size: 14px; font-weight: 700; color: var(--ink-2); background: #fff;
  border: 1px solid var(--line); border-radius: 999px; padding: 8px 18px; cursor: pointer; transition: .2s; }
.hot-tab:hover { border-color: var(--purple-400); color: var(--purple); }
.hot-tab.on { background: var(--grad-purple); color: #fff; border-color: transparent; box-shadow: var(--sh-purple); }
.hot-list { list-style: none; margin: 0; padding: 0; max-width: 720px; background: #fff;
  border: 1px solid var(--line); border-radius: var(--radius); box-shadow: var(--sh-sm); overflow: hidden; }
.hot-row { display: flex; align-items: center; gap: 14px; padding: 14px 20px;
  border-bottom: 1px solid var(--line); cursor: pointer; transition: background .18s; }
.hot-row:last-child { border-bottom: 0; }
.hot-row:hover { background: var(--purple-050); }
.hot-rank { flex: 0 0 24px; text-align: center; font-weight: 800; font-size: 15px; color: var(--ink-3); }
.hot-row:nth-child(1) .hot-rank { color: var(--purple); }
.hot-row:nth-child(2) .hot-rank { color: var(--purple-600); }
.hot-row:nth-child(3) .hot-rank { color: var(--green-600); }
.hot-title { flex: 1 1 auto; font-size: 14.5px; font-weight: 600; color: var(--ink);
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.hot-cat { flex: 0 0 auto; font-size: 12px; font-weight: 600; color: var(--purple); }
.hot-fire { flex: 0 0 auto; font-size: 12.5px; color: var(--ink-3); white-space: nowrap; }
@media (max-width: 640px) {
  .fs-box { flex-wrap: wrap; border-radius: var(--radius); padding: 12px; }
  .fs-btn { width: 100%; }
  .hot-title { white-space: normal; }
  .hot-cat { display: none; }
}
```

- [ ] **Step 2: 校验括号平衡（不破坏既有规则）**

Run: `node -e "const s=require('fs').readFileSync('mabseek/assets/css/style.css','utf8');const o=(s.match(/{/g)||[]).length,c=(s.match(/}/g)||[]).length;console.log('open',o,'close',c,o===c?'OK':'MISMATCH')"`
Expected: `open N close N OK`（左右花括号数量相等；追加前为 288/288，本段新增 24 个 `{`/`}` 对，追加后应为 312/312 OK）。

- [ ] **Step 3: 提交**

```bash
git add mabseek/assets/css/style.css
git commit -m "feat(mabseek): add P3c forum search + hot-list styles"
```

---

## Task 2: forum.html — 外壳统一 + 搜索 + 热榜

**Files:**
- Modify: `forum.html`

> 先 `Read` `mabseek/forum.html` 全文，确保 Edit 精确匹配。保留 `<head>` 内页面局部 `<style>` 块与所有既有分区内容，仅做下列 5 处编辑。

- [ ] **Step 1: 替换导航块**

将 `forum.html` 中从 `<!-- 导航 -->` 到其 `</header>` 的整块（现为 5 项旧导航 + 「发帖」CTA）替换为 P1 七区导航（`论坛` active，普通 `.nav`，CTA「联系我们」）：

```html
<!-- 导航 -->
<header class="nav">
  <div class="container">
    <a class="brand" href="index.html"><span class="logo"><svg viewBox="0 0 24 24" fill="none"><path d="M7 3v4c0 2.8 2.2 5 5 5s5 2.2 5 5v4M17 3v4c0 2.8-2.2 5-5 5" stroke="white" stroke-width="2" stroke-linecap="round"/></svg></span><span>MabSeek<small>抗体求索 · 清华大学医学院</small></span></a>
    <nav class="nav-links">
      <a href="index.html">首页</a>
      <a href="technology.html">技术平台</a>
      <a href="agent.html">Antibody Agent</a>
      <a href="education.html">教育</a>
      <a href="forum.html" class="active">论坛</a>
      <a href="about.html">了解我们</a>
    </nav>
    <div class="nav-actions"><a href="index.html#contact" class="btn btn-green">联系我们</a></div>
    <button class="nav-toggle" aria-label="菜单"><span></span><span></span><span></span></button>
  </div>
</header>
```

- [ ] **Step 2: 在 page-hero 内插入 AI 智能搜索模块**

定位 page-hero 内的 lead 段落（现为 `<p class="lead reveal d2">小红书式内容流……互助求助的氛围。</p>`）。在该 `</p>` **之后**、page-hero 的 `</div>`（`.container` 闭合）**之前**，插入搜索模块：

```html
    <div class="forum-search reveal d3">
      <div class="fs-box">
        <span class="fs-ico">🔍</span>
        <input class="fs-input" type="text" placeholder="用一句话描述你想找的，比如「ELISA 背景高怎么排查」" aria-label="AI 智能搜索">
        <button class="btn btn-green fs-btn" data-demo="正式版将接入语义搜索，理解你的问题意图并返回最相关的帖子">搜索</button>
      </div>
      <div class="fs-examples">
        <span class="fs-label">试试：</span>
        <span class="fs-chip" data-demo="正式版将接入语义搜索：理解意图，返回最相关的帖子">纳米抗体适合 AI 设计吗</span>
        <span class="fs-chip" data-demo="正式版将接入语义搜索：理解意图，返回最相关的帖子">双抗结构设计有哪些坑</span>
        <span class="fs-chip" data-demo="正式版将接入语义搜索：理解意图，返回最相关的帖子">怎么排查细胞培养污染</span>
      </div>
    </div>
```

（`data-demo` 由 `main.js` 既有 `[data-demo]` 监听接管 → 点搜索/点 chip 弹 toast，无需新 JS。）

- [ ] **Step 3: 在 page-hero 分区之后、话题标签+推荐流分区之前，插入热榜新分区**

定位 page-hero `<section class="page-hero">` 的**闭合 `</section>`**，在其后、`<!-- 话题标签 + 推荐流 -->` 注释之前，插入：

```html
<!-- ============ 社区热榜（每日/每周，新增） ============ -->
<section class="section bg-soft" id="hot">
  <div class="container">
    <div style="margin-bottom:28px">
      <span class="eyebrow reveal">社区热榜</span>
      <h2 class="section-title reveal d1">大家这几天都在看什么</h2>
      <p class="section-sub reveal d2">按互动热度聚合近期高关注的帖子，帮你快速跟上社区正在讨论的话题。</p>
    </div>
    <div class="hot-tabs reveal">
      <button class="hot-tab on" data-tab="day">每日热榜</button>
      <button class="hot-tab" data-tab="week">每周热榜</button>
    </div>
    <ol class="hot-list reveal d1" data-list="day">
      <li class="hot-row" data-demo="打开帖子详情"><span class="hot-rank">1</span><span class="hot-title">顶刊拆解：双抗结构设计的三种范式与踩坑</span><span class="hot-cat"># 文献精读</span><span class="hot-fire">🔥 1.2k</span></li>
      <li class="hot-row" data-demo="打开帖子详情"><span class="hot-rank">2</span><span class="hot-title">ELISA 总是背景高？这 5 个洗板细节 90% 的人忽略了</span><span class="hot-cat"># 实验踩坑</span><span class="hot-fire">🔥 980</span></li>
      <li class="hot-row" data-demo="打开帖子详情"><span class="hot-rank">3</span><span class="hot-title">AlphaFold3 跑 Nb 表位分析：参数与结果解读实录</span><span class="hot-cat"># 生信工具</span><span class="hot-fire">🔥 856</span></li>
      <li class="hot-row" data-demo="打开帖子详情"><span class="hot-rank">4</span><span class="hot-title">抗体序列特征分析：从 FASTA 到可视化的一条龙脚本</span><span class="hot-cat"># 生信工具</span><span class="hot-fire">🔥 742</span></li>
      <li class="hot-row" data-demo="打开帖子详情"><span class="hot-rank">5</span><span class="hot-title">细胞培养污染排查手册：那些论文里不写的坑</span><span class="hot-cat"># 实验踩坑</span><span class="hot-fire">🔥 610</span></li>
      <li class="hot-row" data-demo="打开帖子详情"><span class="hot-rank">6</span><span class="hot-title">ADC 偶联比 DAR 老是不稳定？记录一次踩坑复盘</span><span class="hot-cat"># 实验踩坑</span><span class="hot-fire">🔥 523</span></li>
    </ol>
    <ol class="hot-list reveal d1" data-list="week" hidden>
      <li class="hot-row" data-demo="打开帖子详情"><span class="hot-rank">1</span><span class="hot-title">2026 抗体研发岗求职时间线 + 面经合集（持续更新）</span><span class="hot-cat"># 求职招聘</span><span class="hot-fire">🔥 5.6k</span></li>
      <li class="hot-row" data-demo="打开帖子详情"><span class="hot-rank">2</span><span class="hot-title">纳米抗体（VHH）综述：为什么它是 AI 设计的最佳试验田</span><span class="hot-cat"># 文献精读</span><span class="hot-fire">🔥 4.8k</span></li>
      <li class="hot-row" data-demo="打开帖子详情"><span class="hot-rank">3</span><span class="hot-title">顶刊拆解：双抗结构设计的三种范式与踩坑</span><span class="hot-cat"># 文献精读</span><span class="hot-fire">🔥 4.2k</span></li>
      <li class="hot-row" data-demo="打开帖子详情"><span class="hot-rank">4</span><span class="hot-title">抗体序列特征分析：从 FASTA 到可视化的一条龙脚本</span><span class="hot-cat"># 生信工具</span><span class="hot-fire">🔥 3.9k</span></li>
      <li class="hot-row" data-demo="打开帖子详情"><span class="hot-rank">5</span><span class="hot-title">ELISA 总是背景高？这 5 个洗板细节 90% 的人忽略了</span><span class="hot-cat"># 实验踩坑</span><span class="hot-fire">🔥 3.1k</span></li>
      <li class="hot-row" data-demo="打开帖子详情"><span class="hot-rank">6</span><span class="hot-title">AlphaFold3 跑 Nb 表位分析：参数与结果解读实录</span><span class="hot-cat"># 生信工具</span><span class="hot-fire">🔥 2.7k</span></li>
    </ol>
  </div>
</section>
```

> 注：既有「四条内容线」分区用的是 `<section class="section bg-soft">`。热榜段也用 `bg-soft` 会与其相邻的推荐流（白底 `.section`）形成节奏；page-hero 为浅色、推荐流为白、热榜 `bg-soft`、内容线 `bg-soft`——实现时 Step 5 验证整体节奏，如相邻两段同为 `bg-soft` 显得平，可将热榜段改为普通 `<section class="section" id="hot">`（去掉 `bg-soft`）。默认先用 `bg-soft`。

- [ ] **Step 4: 替换页脚为 P1 页脚**

将 `forum.html` 中从 `<!-- 页脚 -->` 到其 `</footer>` 的整块替换为 P1 统一页脚：

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

- [ ] **Step 5: 在既有 inline `<script>` 内追加热榜 tab 切换**

定位 forum.html 页尾既有 `<script>`（含 chip 过滤逻辑）。在该 `<script>` 的**闭合 `</script>` 之前**、既有 chip-filter 代码之后，追加热榜切换逻辑（风格一致、无依赖）：

```javascript
// 热榜：每日 / 每周 切换
var hotTabs = document.querySelectorAll('.hot-tab');
var hotLists = document.querySelectorAll('.hot-list');
hotTabs.forEach(function (tab) {
  tab.addEventListener('click', function () {
    hotTabs.forEach(function (x) { x.classList.remove('on'); });
    tab.classList.add('on');
    var t = tab.getAttribute('data-tab');
    hotLists.forEach(function (l) {
      l.hidden = (l.getAttribute('data-list') !== t);
    });
  });
});
```

（`hidden` 属性走浏览器默认 `display:none`，无需额外 CSS。）

- [ ] **Step 6: 浏览器验证**

预览服务器（root `.claude/launch.json` 的 `mabseek` 项，端口 8777；若未运行 `preview_start` name=`mabseek`）。导航到 `http://localhost:8777/forum.html`，reload。**取计算样式前先 cache-bust**（重挂带 `?ts=` 查询串的 style.css，确认服务的是含 `.forum-search`/`.hot-list` 的最新 css）。
- `preview_snapshot`：导航 6 项（首页/技术平台/Antibody Agent/教育/论坛/了解我们）且 `论坛` active；hero 内出现搜索框 + 3 个示例 chip；出现「社区热榜」标题与「每日热榜/每周热榜」两个 tab；页脚为「探索/社区/联系」三列。
- 搜索交互：`preview_click` `.fs-btn` → 出现 toast（`#mab-toast` 文本含「语义搜索」）；`preview_click` 某 `.fs-chip` → 同样弹 toast。
- 热榜交互：默认 `data-list="day"` 列表可见、`data-list="week"` 列表 `hidden`；`preview_click` 「每周热榜」`.hot-tab[data-tab=week]` → week 列表显示、day 列表隐藏、tab `.on` 迁移；再点「每日热榜」还原。
- 前三名徽标：`*{transition:none!important}` 注入后 `preview_inspect` `.hot-list[data-list=day] .hot-row:nth-child(1) .hot-rank` 颜色为紫 rgb(109,59,235)；:nth-child(3) 为绿系。
- 既有 chip-filter 仍工作：`preview_click` 某 `.chip-f`（如 `# 文献精读`）→ 推荐流按 `data-cat` 过滤；确认与热榜 tab 互不干扰。
- 不杜撰核查：`#hot` 内帖子标题与推荐流 `.post h4` 标题一致（复用）；除名次与 🔥 演示数字外无机构名/研究数值。
- `preview_network` filter=failed 为空；`preview_console_logs` level=error 为空。

- [ ] **Step 7: 提交**

```bash
git add mabseek/forum.html
git commit -m "feat(mabseek): harmonize forum shell + add AI search & hot-list"
```

---

## Task 3: 整体验收与响应式

**Files:** 无（仅验证 + 必要微调）

- [ ] **Step 1: 桌面走查**

reload `forum.html`（cache-bust）：
- `preview_screenshot` 通读：页头（标语 + 搜索框）→ 社区热榜（每日榜）→ 推荐流（chip + masonry 8 卡）→ 四条内容线 → 关注动态 + 冷启动 → 页脚，视觉连贯；若热榜段与相邻内容线均 `bg-soft` 显得平，按 Task2 Step3 备注把 `#hot` 改为普通 `.section`（去 `bg-soft`）并重验。
- 导航 `论坛` 高亮 active；CTA「联系我们」`preview_click` → 跳 `index.html#contact`。
- `preview_click` 页脚各链接 → `preview_network` filter=failed 应为空。

- [ ] **Step 2: 双列表 / 双脚本复核**

- `preview_click` 「每周」「每日」tab 反复切换，列表 `hidden` 正确迁移，`.on` 唯一。
- `preview_click` 推荐流 chip（`# 生信工具` 等）过滤正常；确认切换热榜 tab 不影响推荐流过滤态，反之亦然（两段脚本互不干扰）。
- `preview_click` 一个 `.hot-row` → 弹 toast「打开帖子详情」。

- [ ] **Step 3: 移动端响应式**

`preview_resize` preset=`mobile`，reload：
- `preview_screenshot`：导航折叠为汉堡；搜索框窄屏换行（按钮占满一行）不溢出；热榜行标题换行不溢出、`.hot-cat` 隐藏。
- `preview_click` `.nav-toggle` 展开菜单；`preview_click` 「每周」tab 窄屏可切换。
- 完成 `preview_resize` preset=`desktop` 复位。

- [ ] **Step 4: 最终提交（如有微调）**

```bash
git add -A
git commit -m "chore(mabseek): P3c forum page responsive + verification fixes"
```

（若 Step 1–3 无需改动，可跳过本步、不建空提交。）

---

## Self-Review 记录

- **Spec 覆盖**：§4.1 导航 → Task2(Step1)；§4.2 页脚 → Task2(Step4)；§5.1 AI 搜索（框/示例 chip/toast 演示/零新 JS）→ Task1(CSS `.forum-search/.fs-*`)+Task2(Step2 HTML)；§5.2 热榜（独立段/每日每周 tab/复用标题/演示数字/极少新 JS）→ Task1(CSS `.hot-*`)+Task2(Step3 HTML + Step5 JS)；§6 样式增量追加 → Task1；§7 文件改动 → 全覆盖；§8 验证 → Task2(Step6)/Task3。无遗漏。
- **占位符扫描**：无 TBD/TODO；所有步给出完整代码或完整替换块（含全部 12 条热榜 HTML + 完整 CSS + 完整 JS）。
- **一致性**：新类 `.forum-search/.fs-box/.fs-ico/.fs-input/.fs-btn/.fs-examples/.fs-label/.fs-chip/.hot-tabs/.hot-tab(.on)/.hot-list/.hot-row/.hot-rank/.hot-title/.hot-cat/.hot-fire`（Task1 定义、Task2 使用）一致；复用 P1 外壳类 `.nav/.nav-links/.footer/.footer-grid/.footer-social/.footer-bottom/.btn/.btn-green/.eyebrow/.section-title/.section-sub/.section/.bg-soft/.reveal` 与既有 forum 类均已存在；CSS 变量 `--line/--ink/--ink-2/--ink-3/--purple/--purple-400/--purple-600/--purple-050/--green-600/--grad-purple/--sh-sm/--sh-purple/--radius` 均在 `:root` 已定义（已核对）。
- **不杜撰**：热榜标题原样复用推荐流 8 篇既有标题；每日/每周为不同排序；除名次与 🔥 演示数字（与既有 `❤️328` 同性质）外无机构名/研究数据。搜索示例问句取自站内主题。
- **最少新 JS**：搜索靠 `main.js` 既有 `[data-demo]`→toast，零新 JS；仅热榜 tab 切换追加 ~10 行到既有 inline `<script>`，风格同既有 chip-filter，无依赖。
- **YAGNI**：不接语义搜索后端、不重写既有推荐流/内容线/关注动态、不改其它页面、不加深色带、不做 i18n。
