# MabSeek 抗体求索 · UI 风格文档

> 版本：2026-08-28（P1–P4 完成后基线）
> 项目：MabSeek 抗体求索门户 · 清华大学医学院
> 权威来源：`assets/css/style.css`（约 570 行）+ 各页 `<head>` 局部 `<style>`

本文档定义 MabSeek 门户的视觉语言：色彩、渐变、阴影、圆角、间距、字体、组件样式与响应式规则。架构约定见 [architecture.md](architecture.md)。

**设计一句话**：紫 + 荧光绿 —— 科技与趣味的平衡（全站页脚版权行标语）。

---

## 1. 设计令牌（`:root`）

全站样式基于 CSS 自定义属性。令牌集中定义在 `style.css` 顶部（品牌令牌）+ P1 段（深色主题令牌）。

### 1.1 品牌色

| 令牌 | 值 | 用途 |
|---|---|---|
| `--purple` | `#6D3BEB` | **主品牌紫**，按钮/强调/链接 |
| `--purple-600` | `#7C4DFF` | 亮紫（渐变端） |
| `--purple-400` | `#9B7BFF` | 悬停边框 |
| `--purple-100` | `#EDE7FF` | 浅紫描边/背景 |
| `--purple-050` | `#F5F1FF` | 极浅紫底（标签/图标底） |
| `--green` | `#00E0A4` | **主品牌荧光绿**，成功/强调/CTA |
| `--green-600` | `#12D6A0` | 深荧光绿 |
| `--green-400` | `#4FE9C4` | 亮绿（渐变端） |
| `--green-100` | `#D6FBF0` | 浅绿底 |

> 深色底上另用 `#06a97c` 作绿色文字（对比度足够），非令牌但全站一致复用。

### 1.2 中性色（Ink 墨阶）

| 令牌 | 值 | 用途 |
|---|---|---|
| `--ink` | `#10132B` | 主文字（近黑蓝） |
| `--ink-2` | `#3B4062` | 次要文字 |
| `--ink-3` | `#6B7192` | 辅助/说明文字 |
| `--line` | `#E7E9F3` | 描边/分割线 |
| `--bg` | `#FFFFFF` | 主背景 |
| `--bg-soft` | `#F6F7FB` | 浅灰区背景 |
| `--bg-soft-2` | `#F0F1F8` | 更深浅灰 |

### 1.3 深色主题令牌（P1）

| 令牌 | 值 | 用途 |
|---|---|---|
| `--bg-dark` | `#070A14` | 深色区背景（近黑） |
| `--ink-on-dark` | `#FFFFFF` | 深底主文字 |
| `--ink-on-dark-2` | `#9AA3C2` | 深底次要文字 |
| `--line-dark` | `#23324A` | 深底描边 |
| `--card-dark` | `#0D1122` | 深底卡片 |
| `--green-neon` | `#00E0A4` | 深底荧光绿高亮（`.txt-neon`） |
| `--green-solid` | `#12B98C` | 深底实心绿 |

### 1.4 渐变

| 令牌 | 定义 | 用途 |
|---|---|---|
| `--grad-brand` | 紫 → `#4b6bff` → 绿，120° | 品牌主渐变（头像/图块/装饰） |
| `--grad-purple` | 紫系线性 | 紫色按钮/芯片 |
| `--grad-green` | 绿系线性 | 绿色按钮（主 CTA） |
| `--grad-soft` | 浅色渐变 | 柔和背景 |
| `--grad-aurora` | 多点径向（多色斑） | 英雄区极光背景 |

### 1.5 阴影 / 圆角 / 尺寸

| 类别 | 令牌 |
|---|---|
| 阴影 | `--sh-sm` / `--sh` / `--sh-lg`（中性层次）；`--sh-purple` / `--sh-green`（彩色投影，用于强调按钮/芯片） |
| 圆角 | `--radius:18px`（默认）/ `--radius-sm:12px` / `--radius-lg:28px`；胶囊统一 `999px` |
| 布局 | `--maxw:1200px`（容器最大宽）/ `--nav-h:72px`（导航高） |

### 1.6 字体

- **字体栈** `--font`：系统栈，含 PingFang SC / Microsoft YaHei（中文优先），无 Web Font 依赖，零字体请求。
- **标题**：`h1`–`h4` 字重 800，`letter-spacing:-.01em`。
- **正文**：`line-height:1.65`。
- **流式字号**：`.section-title` 用 `clamp(28px, …, 44px)`，随视口平滑缩放。

---

## 2. 布局与工具类

| 类 | 作用 |
|---|---|
| `.container` | 居中，`max-width:1200px`，左右 padding 24px |
| `.section` | 上下 96px 区块间距 |
| `.section-sm` | 上下 64px（紧凑区块） |
| `.bg-soft` | 浅灰背景区 |
| `.text-center` | 居中文本 |
| `.grad-text` | 文字用品牌渐变填充（`background-clip:text`） |
| `.eyebrow`（+`.green`） | 小标签眉题（区块上方的分类标签） |
| `.section-title` | 区块主标题（clamp 流式） |
| `.section-sub` | 区块副标题/说明 |
| `.grid-2` / `.grid-3` / `.grid-4` | 2/3/4 列网格 |

---

## 3. 组件规范

### 3.1 按钮 `.btn`

- 基础：胶囊形（`border-radius:999px`）、字重 700、内边距舒适、`transition` 平滑。
- 变体：
  - `.btn-green` — 绿色渐变，**主 CTA**（「联系我们」「提交反馈」「搜索」）。
  - `.btn-purple` — 紫色，次级主行动。
  - `.btn-outline` — 描边，弱行动（「了解更多」「加载更多」）。
  - `.btn-ghost` — 无底透明。
  - `.btn-lg` — 大号尺寸。

### 3.2 导航 `.nav`

- 固定顶部，高度 72px，毛玻璃背景（`backdrop-filter: blur`）。
- `.nav.scrolled`：滚动后加阴影 / 实底。
- `.nav--on-dark`：深色首屏页（index / technology）专用——首屏透明白字，滚动后转深底。
- `.brand` / `.logo`：品牌区（内联 SVG 抗体 logo + 「MabSeek 抗体求索 · 清华大学医学院」）。
- `.nav-links a`（+`.active` 当前页高亮）。
- `.nav-toggle`：移动端汉堡（三横），`≤720px` 显示，`.nav.open` 展开菜单。

### 3.3 卡片与内容块

| 类 | 说明 |
|---|---|
| `.card` | 通用卡片：白底、`--line` 描边、`--radius` 圆角、`--sh-sm` 阴影，悬停上浮/加深。 |
| `.metric` | 指标卡（大数字 + 说明）。 |
| `.case` | 案例卡。 |
| `.logo-wall` | 合作机构 logo 墙。 |
| `.page-hero` | 内页页头（浅色，含 breadcrumb + eyebrow + 标题 + lead + 锚点 tag-row）。 |
| `.tag`（+`.green`） | 胶囊标签/锚点。 |

### 3.4 页脚 `.footer`

- 四列 `.footer-grid`：品牌 + 社交 / 探索 / 社区 / 联系。
- `.footer-social`：Emoji 图标（`data-demo` → toast）。
- `.footer-bottom`：两段——版权行 + 标语「紫 + 荧光绿 · 科技与趣味的平衡」。
- 全站六页页脚内容完全一致。

### 3.5 表单（联系 / 反馈）

- `.feedback` 容器 + `.fb-row`（并排双输入）+ `textarea` + `.fb-submit`（绿色按钮）。
- 置于 `.section-dark #contact` 深色区，白字 + 荧光绿高亮标题（`.txt-neon`）。
- 提交由 `main.js` 前端模拟：toast 致谢 + `reset()`。

---

## 4. 深 / 浅底节奏

页面通过交替背景带制造视觉节奏：

| 类 | 效果 |
|---|---|
| `.section-dark` | 深底（`--bg-dark`）+ 白字，用于英雄区、痛点、联系区、案例示范 |
| `.section-light` | 浅底白区 |
| `.bg-soft` | 浅灰过渡区 |
| `.txt-neon` / `.txt-green` | 深底上的荧光绿 / 绿色高亮文字 |

典型节奏（首页）：深（英雄）→ 浅 → 深（痛点）→ 浅（近况）→ 深（联系）。深色页（index / technology）配 `.nav--on-dark`。

---

## 5. 动效与渐显

| 机制 | 说明 |
|---|---|
| `.reveal` → `.reveal.in` | 初始 `opacity:0; translateY(28px)`，进入视口后由 `main.js` 的 IntersectionObserver 加 `.in` 淡入上移。 |
| `.reveal.d1`–`.d4` | 阶梯延迟，制造依次浮现的错落感。 |
| 数字滚动 | `[data-num]` count-up 缓动（注：当前页面暂未使用该属性，逻辑保留）。 |
| Canvas 动画 | 首页粒子网络（`#hero-canvas`）、抗体组装（`#antibody-canvas`）；Agent 对话/案例循环动图。 |
| SVG 交互 | 教育页知识图谱节点高亮。 |

### 5.1 无障碍 / 减弱动效

**全站每个动画模块都尊重 `@media (prefers-reduced-motion: reduce)`**：

- Canvas 动画：不启动，直接绘制静止终态帧。
- 案例示范（`.case-anim`）：CSS 提供静止终态。
- `.reveal`：无 IntersectionObserver 时直接对全部元素加 `.in`（内容始终可读，不因脚本缺失而空白）。

---

## 6. 响应式断点

| 断点 | 变化 |
|---|---|
| `≤960px` | 多列网格塌陷为单列；指标区 2 列；论坛瀑布流 `column-count:2`。 |
| `≤720px` | 导航收为汉堡菜单（`.nav.open .nav-links` 展开）；`.leads` 双人卡竖排。 |
| 组件级（`860 / 640 / 600 / 560`） | 各页局部 `<style>` 内的一次性微调（如论坛 `≤600px` 瀑布流单列、教育播放器布局）。 |

> 全站 `overflow-x:hidden`，防止任何窄屏横向溢出。

---

## 7. 图标与图像

- **图标**：Emoji（🧪🧬💊🔬🎓🙋📦💡🌏🤝 等）+ 内联 SVG（品牌 logo）。无图标字体。
- **favicon**：内联 data-URI SVG 🧬。
- **位图**：`assets/images/`，`<img>` 普遍带 `onerror` 兜底 → 品牌渐变块 / emoji / 隐藏容器。缺图不破版（例：about 位置图缺 `location.png` 时降级为 🏛️ 渐变块）。
- **头像/图块占位**：姓氏首字 / emoji + `--grad-brand` 或双色线性渐变（如 `linear-gradient(135deg,#12D6A0,#4b6bff)`）。

---

## 8. 内容与文案色彩约定（不杜撰）

视觉呈现须与「不杜撰」原则一致：

- 真实人物（张林琦 / 马维英）仅用姓名 + 定性方向 + 角色标签（`.role` / `.role.green`），**不配硬数值指标**（无 `data-num`）；辅以免责说明。
- 演示性数字（命中率、KD、热榜 🔥、获赞 ❤️、每周更新篇数）以普通文本呈现，多数由 `data-demo` 或「示意动效，非真实结果数据」等语境标注；不作为经核实事实强调。
- 机构名（清华 / 北大 / 中科院 / 协和 / Eijkman / PRA）以合作方向定性表述。

---

## 9. 令牌速查（复制即用）

```css
/* 品牌 */
--purple:#6D3BEB;  --green:#00E0A4;
/* 墨阶 */
--ink:#10132B; --ink-2:#3B4062; --ink-3:#6B7192; --line:#E7E9F3;
/* 底色 */
--bg:#FFFFFF; --bg-soft:#F6F7FB; --bg-dark:#070A14;
/* 圆角 */
--radius:18px; --radius-sm:12px; --radius-lg:28px;
/* 布局 */
--maxw:1200px; --nav-h:72px;
/* 渐变 */
--grad-brand: linear-gradient(120deg, #6D3BEB, #4b6bff, #00E0A4);
```

新增样式请遵循 [architecture.md](architecture.md) 的「分阶段增量」纪律：追加到 `style.css` 末尾并加阶段注释，或写入页面局部 `<style>`；一次性微调优先页面局部，勿改既有全局规则。
