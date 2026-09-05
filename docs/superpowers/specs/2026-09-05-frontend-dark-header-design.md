# 设计文档：首页文案微调 + 前台 header 统一深色

- 日期：2026-09-05
- 状态：已批准，待实现

## 背景

MabSeek 前台各页顶部导航（`public/partials/nav.php`）通过页面变量 `$navOnDark` 切换浅/深色：首页与技术平台为 `true`（深色「透明浮动 → 滚动变深」效果，因其顶部本身是深色 hero），其余页为 `false`（浅色半透明白底导航）。本次：① 首页近况板块标题文案微调；② 所有前台页 header 统一为深色方案。

## 需求 A：文案「新闻 · 发表 · 活动」→「新闻 · 活动」

- 该文案是 snippet `home.news.title`，前台首页 [index.php] 用 `snip('home.news.title')` 渲染（近况板块标题）。
- 改动：
  - `bin/seed.php` 中 `home.news.title` 默认值改为「新闻 · 活动」。
  - 运行时 `snippets` 表该记录 UPDATE（去掉「发表 · 」），使现有库即时生效。

## 需求 B：前台 header 统一深色

### 现状与问题

- `.nav`：浅色半透明白底（`rgba(255,255,255,.72)` + 毛玻璃），深色文字。
- `.nav--on-dark`：**顶部透明**（`background:transparent`）+ 白色文字/logo；滚动后（`.scrolled`）才变深色毛玻璃。依赖页面顶部为深色背景（首页/technology 的深色 hero）。
- 直接给浅色页翻 `$navOnDark=true` 会导致「白字浮在浅色页头上、几乎不可见」，直到滚动才可读——错误对比度。

### 方案：新增实心深色 header

- **CSS（style.css）**：新增 `.nav--solid-dark`——顶部即不透明深色背景（`rgba(7,10,20,.92)` + `backdrop-filter: blur(16px) saturate(160%)` + `border-bottom: 1px solid rgba(255,255,255,.08)`）。文字/logo/按钮/移动端汉堡的白色规则复用现有 `.nav--on-dark`（solid-dark 与 on-dark 同时挂载，共享白字规则，仅顶部背景不同）。
- **nav.php**：新增变量 `$navSolidDark`（默认 false）。class 渲染：
  - `$navOnDark` → `nav--on-dark`（透明浮动，首页/technology）。
  - `$navSolidDark` → `nav--on-dark nav--solid-dark`（实心黑 + 复用白字）。
- **各页变量设置**：
  - 保持 `$navOnDark=true`（透明浮动）：`index.php`、`technology.php`。
  - 改为 `$navSolidDark=true`（实心黑）：`about.php`、`agent.php`、`education.php`、`forum.php`、`account.php`、`login.php`、`register.php`、`news.php`、`thread.php`（含 404 分支与正常分支两处）、`thread-new.php`、`thread-edit.php`（含 404 分支与正常分支两处）、`edu-review.php`（含 404 分支与正常分支两处）。
- **页头背景不动**：黑 header 压在浅色 page-hero 上方（fixed 定位，page-hero 已有 nav-h 顶部留白），呈现「黑 header + 浅色内容」，对比正常。

### 一致性

- `$navOnDark` 与 `$navSolidDark` 互斥；nav.php 优先判断：两者其一为 true 即挂 `nav--on-dark`（白字），`$navSolidDark` 额外挂 `nav--solid-dark`（实心背景）。
- logo 透明规则（`.nav--on-dark .brand-logo`）对两种深色模式均生效。

## 非目标（YAGNI）

- 不改后台（admin）header。
- 不改各页 page-hero / 内容区背景色（仅改 header）。
- 首页/技术平台的透明浮动效果保持不变。

## 测试与验证

- `tests/run.php` 全套回归（纯展示层，不新增单测）。
- 起服务逐页人工验证：
  - 首页/技术平台：header 透明浮动、滚动变深（不变）。
  - 其余所有前台页：header 实心深色、白字可读、当前项绿色高亮、logo 透明。
  - 首页近况板块标题显示「新闻 · 活动」。
  - 移动端（窄视口）汉堡菜单深色展开正常。
