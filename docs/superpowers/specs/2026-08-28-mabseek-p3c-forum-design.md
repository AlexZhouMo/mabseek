# MabSeek P3c · 论坛页 Design Spec

**日期：** 2026-08-28
**阶段：** P3c（P3 三子阶段的最后一个：Agent → 教育 → **论坛**）
**页面：** `forum.html`

## 1. 目标

把 `forum.html` 对齐 P1/P2 外壳（七区导航 + 双主题体系下的统一页脚），并新增两个 PRD-20260825 要求的模块：

1. **AI 智能搜索**（语义搜索，非关键词）——嵌入页头。
2. **每日 / 每周社区热榜**——独立整段。

既有内容本体（推荐流、四条内容线、关注动态 + 冷启动）**全部保留、不重写**。采用「统一外壳 + 增量新增」策略，与 P3a（Agent）、P3b（教育）一致。

## 2. 背景与现状

`forum.html` 是 **P1 之前的旧外壳**：

- 导航 5 项、顺序旧（首页 / 教育 / Antibody Agent / 论坛 / 网站介绍），CTA 为「✍️ 发帖」。
- 页头 `.page-hero`（浅色）+ eyebrow + 标题 + lead。
- 话题标签 chip 过滤条 + 小红书式 masonry 推荐流（`column-count`，8 张 `.post` 卡）。
- 四条内容线（`.line-card` × 4）。
- 关注动态 + 冷启动（`.grid-2`）。
- 旧版页脚（产品/探索/关于 三列）。
- 页面局部 `<style>`（`.filter-bar/.chip-f/.feed/.post/.line-card` 等）+ 页尾 inline `<script>`（chip 过滤：点 chip 切换 `.on` 并按 `data-cat` show/hide `.post`）。

参考站 **丁香园 DXY**（PRD image26.png）仅作**版式参考**：顶部醒目搜索 + 排行榜式热榜。**不搬运其文字、数据或帖子内容。**

## 3. 约束（标准约束，全程遵守）

- **纯静态站**，零后端；所有交互前端模拟（`data-demo` + toast，沿用全站惯例）。
- **不杜撰**：不出现真实机构名、研究数值或硬事实。社交热度数字（🔥、❤️）为演示占位，与页面既有 `❤️328` 同性质。
- 参考站（丁香园）仅版式参考，不搬运内容。
- 增量样式**追加**到 `style.css` 末尾（P3b 段之后），不改既有规则；`node -e` 校验花括号平衡。
- i18n（中英切换）留到 P4，本阶段不做。
- 执行方式：master 分支上 subagent-driven-development（用户已就本项目授权）。

## 4. 外壳统一

### 4.1 导航（替换）

将 `<!-- 导航 -->` 到其 `</header>` 整块替换为 P1 七区导航：

- 链接顺序：`首页(index.html) / 技术平台(technology.html) / Antibody Agent(agent.html) / 教育(education.html) / 论坛(forum.html)[active] / 了解我们(about.html)`。
- 右侧 CTA：`<a href="index.html#contact" class="btn btn-green">联系我们</a>`（与 agent/education 一致，替换旧「发帖」）。
- 普通 `.nav`（`.page-hero` 为浅色，与 education 同）；保留 `.nav-toggle` 汉堡。
- 实现时用 `preview_inspect` 确认浅色 hero 上导航文字可读；若 hero 实为深色再改用 `nav--on-dark`。

### 4.2 页脚（替换）

将 `<!-- 页脚 -->` 到其 `</footer>` 整块替换为 P1 统一页脚（与 education.html 同款）：品牌简介 + `.footer-social`；「探索」列（技术平台 / Antibody Agent / 教育）；「社区」列（论坛 / 了解我们 / 新闻活动 `about.html#news`）；「联系」列（联系我们 `index.html#contact` / `mailto:contact@mabseek.org` / 清华大学医学院）；`.footer-bottom` 两段版权行。

## 5. 新增模块

### 5.1 AI 智能搜索（嵌入 page-hero）

在 `.page-hero .container` 内、`.lead` 段落**之后**插入搜索模块：

```
<div class="forum-search reveal d3">
  <div class="fs-box">
    <span class="fs-ico">🔍</span>
    <input class="fs-input" type="text" placeholder="用一句话描述你想找的，比如「ELISA 背景高怎么排查」" aria-label="AI 智能搜索">
    <button class="btn btn-green fs-btn" data-demo="正式版将接入语义搜索，理解你的问题意图">搜索</button>
  </div>
  <div class="fs-examples">
    <span class="fs-label">试试：</span>
    <span class="fs-chip" data-demo="正式版将接入语义搜索：理解意图，返回最相关的帖子">纳米抗体适合 AI 设计吗</span>
    <span class="fs-chip" data-demo="正式版将接入语义搜索：理解意图，返回最相关的帖子">双抗结构设计有哪些坑</span>
    <span class="fs-chip" data-demo="正式版将接入语义搜索：理解意图，返回最相关的帖子">怎么排查细胞培养污染</span>
  </div>
</div>
```

- **语义 ≠ 关键词** 的信号：占位文案是一句自然语言问句；示例 chip 均为完整问句；toast 文案强调「理解意图」。
- **交互**：`input` 不提交表单；点「搜索」按钮或任一 `.fs-chip` → 由全站 `main.js` 的 `data-demo` 机制弹 toast。**不新增搜索相关 JS。**
- 示例问句取自站内既有帖子主题，不新造硬事实。

### 5.2 每日 / 每周社区热榜（独立整段）

在 page-hero `</section>` **之后**、既有话题标签 + 推荐流 `<section>` **之前**，插入新分区 `<section class="section" id="hot">`：

结构：
- 区头：`<span class="eyebrow">社区热榜</span>` + `<h2 class="section-title">大家这几天都在看什么</h2>`（无数值）。
- 切换 tab：`<div class="hot-tabs">` 内两个 `<button class="hot-tab on" data-tab="day">每日热榜</button>` / `<button class="hot-tab" data-tab="week">每周热榜</button>`。
- 两份榜单：`<ol class="hot-list" data-list="day">`（默认显示）与 `<ol class="hot-list" data-list="week" hidden>`。
- 每行 `<li class="hot-row">`：`<span class="hot-rank">1</span>` + `<span class="hot-title">…帖子标题…</span>` + `<span class="hot-cat"># 分类</span>` + `<span class="hot-fire">🔥 1.2k</span>`；整行 `data-demo="打开帖子详情"`。

**内容来源（不杜撰）：** 榜单条目标题**直接复用推荐流已有的 8 篇 `.post` 标题**，不新造内容。每日榜与每周榜为**同一批标题的不同排序**（各取 5-6 条）。名次 1..N 为排名；`🔥` 数字为演示占位（如 `1.2k / 980 / 760`…），与既有 `❤️328` 同性质。**无真实机构名 / 研究数据。**

**交互 JS（极少量新增）：** 在 forum 既有页尾 `<script>` 内追加热榜 tab 切换逻辑——点 `.hot-tab` 时切换其 `.on`，并按 `data-tab` / `data-list` 显示对应 `.hot-list`、隐藏另一份（用 `hidden` 属性或 `style.display`）。风格与既有 chip-filter 脚本一致，不引入依赖。

## 6. 样式（增量追加 style.css）

在 `assets/css/style.css` **末尾**（P3b 段之后）追加 P3c 段，仅新增下列类，不改既有规则：

- **搜索**：`.forum-search`（上边距）、`.fs-box`（flex 行、白底、圆角、边框/阴影、内边距）、`.fs-ico`、`.fs-input`（flex:1、无边框、透明底）、`.fs-btn`（不收缩）、`.fs-examples`（flex 换行）、`.fs-label`（灰）、`.fs-chip`（胶囊、边框、hover 变紫、cursor pointer）。
- **热榜**：`#hot .hot-tabs`（flex gap）、`.hot-tab`（胶囊按钮，`.on` 用品牌渐变/紫底白字）、`.hot-list`（去列表默认样式，白卡容器可选）、`.hot-row`（flex 对齐、行间分隔线 `--line`、hover 微高亮、cursor pointer）、`.hot-rank`（定宽，前三名 `:nth-child(1..3)` 用紫/绿高亮徽标，其余灰）、`.hot-title`（flex:1，单行省略可选）、`.hot-cat`（小号紫字）、`.hot-fire`（小号、`--ink-3`，🔥 前缀）。
- **响应式** `@media (max-width:640px)`：`.fs-box` 允许换行 / `.fs-btn` 占满；`.hot-row` 允许 `.hot-cat` 换行或隐藏，标题不溢出。

复用既有令牌与工具类：`--purple/--green/--ink-2/--ink-3/--line/--radius/--sh-sm`、`.section/.container/.eyebrow/.section-title/.btn/.btn-green/.reveal`。花括号平衡以 `node -e` 校验。

## 7. 文件改动清单

| 文件 | 动作 | 职责 |
|---|---|---|
| `forum.html` | 改 | 导航→P1 七区；hero 内插入 `.forum-search`；page-hero 后插入 `#hot` 热榜段；页脚→P1 页脚；页尾 inline `<script>` 追加热榜 tab 切换 |
| `assets/css/style.css` | 改（**末尾追加** P3c 段） | `.forum-search/.fs-*` 搜索样式、`#hot .hot-*` 热榜样式、响应式 |

**不动：** `index.html` / `technology.html` / `agent.html` / `education.html` / `about.html` / `main.js` / `knowledge-graph.js`；forum.html 既有 chip-filter 逻辑与所有既有分区内容（推荐流 8 卡、四条内容线、关注动态、冷启动）。

## 8. 验证（preview_*，端口 8777）

> 预览标签可能缓存旧 css，取计算样式前先 cache-bust（重挂带 `?ts=` 的 style.css）；取色/取高前注入 `*{transition:none!important}` 以避开过渡态误读（P3b 已验证的坑）。

- 导航 6 链接（首页/技术平台/Antibody Agent/教育/论坛/了解我们）且「论坛」active；CTA「联系我们」→ `index.html#contact`。
- hero 内出现搜索框 + 3 示例 chip；点搜索按钮 / 点 chip 触发 toast（`data-demo`）。
- `#hot` 位于 hero 之后、推荐流之前；`每日/每周` tab 切换正常（点「每周」显示 week 榜、隐藏 day 榜；再点「每日」还原）；前三名徽标高亮。
- 热榜条目标题与推荐流标题一致（复用），无新造机构名/研究数值；数字仅 🔥 演示占位。
- 既有 chip-filter 仍工作（点话题 chip 过滤推荐流），与热榜 tab 互不干扰。
- 页脚为「探索/社区/联系」三列；`preview_network` filter=failed 为空；`preview_console_logs` level=error 为空。
- 移动端 `preview_resize` preset=mobile：导航折叠汉堡；搜索框与热榜行窄屏不溢出；`preview_click` `.nav-toggle` 可展开菜单。完成后 `preview_resize` preset=desktop 复位。

## 9. YAGNI（明确不做）

- 不接真实语义搜索后端；搜索仅 toast 演示。
- 不重写既有推荐流 / 内容线 / 关注动态；不改其它页面。
- 热榜不做真实实时排序、不引入数据文件；不加深色带。
- 不做 i18n（留 P4）。
