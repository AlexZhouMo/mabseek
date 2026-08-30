# MabSeek P3a · Antibody Agent 页 设计规格

> 子阶段 P3a（P3 三子阶段之首：**Agent → 教育 → 论坛**）。做法：**统一外壳 + 增量新增**。本规格经头脑风暴确认后落档；下一步交由 writing-plans 生成实现计划。

## 1. 目标

把现有 `agent.html`（P1 之前的旧外壳）对齐到 P1/P2 设计系统，并叠加 PRD-20260825 对本页的唯一新增诉求——**案例示范（每个做成循环动图）**。PRD 评语：「现在的版本还可以，可以加一些案例示范，然后每个都是一个动图」；参考 Bohrium（更简洁）与 SciencePal 2.0（内容参考）。

## 2. 范围与非目标

**做（In scope）**
- 外壳统一：导航换成 P1 七区导航；页脚换成 P1 页脚。
- 主题统一：引入深/浅分区节奏；强调色迁移到 `.txt-neon`/`.txt-green`。
- 新增「案例示范」分区：3 张卡片，每张一个纯 CSS/JS 循环动图（演示流程/UI 动效，非结果数据）。
- 智能体矩阵中为 SciencePal Agent 增加一个带标注的外链 `https://sciencepal.ai`。

**不做（Out of scope / YAGNI）**
- 不接入真实推理（保持 `data-demo` + toast 前端模拟）。
- 不做中英切换（留到 P4）。
- 不改动 `education.html`/`forum.html`/`index.html`/`technology.html`/`about.html`。
- 不重写既有对话演示（`agent-demo.js`）、四大能力、干湿闭环、智能体矩阵的内容本体——PRD 说「现在的版本还可以」，仅统一外壳 + 叠加新增。
- 不新增任何数字/指标/客户名（不杜撰）。

## 3. 内容来源（权威，不杜撰）

- 页面既有内容本体保留（源自 PRD-0808 + P1 已确立事实），仅做外壳/主题层的迁移。
- 案例示范动图**只演示过程与界面动效**，不展示任何具体数值、结果指标、客户或机构名。卡片标题/说明为定性流程描述。
- SciencePal 外链是 PRD-0825 明确点名的内容参考来源，作为「了解更多」出站链接，不搬运其文案/数据。

## 4. 分区节奏（section rhythm）

保留既有分区的内容本体，按深/浅交替重排，并在核心能力之后插入新增的案例示范深色带：

| # | 分区 | 主题 | 说明 |
|---|---|---|---|
| A1 | Hero + 对话演示 | 浅（简洁，Bohrium 向） | 保留两栏（文案 + 实时对话演示 `#chat-feed`）；导航用普通 `.nav`（深色文字）。清理为更简洁的排版。 |
| A2 | 核心能力 | 浅 | 四大能力卡（文献检索问答/抗体序列设计/亲和力预测/结构分析），保留。 |
| **A3** | **案例示范（新增）** | **深 `.section-dark`** | **3 张卡片，每张一个循环动图。核心新增。** |
| A4 | 干湿闭环 | 浅 `bg-soft` | 5 步流程 + 湿实验平台下单块，保留。 |
| A5 | 智能体矩阵 | 浅 | 8 个 agent chip，保留；SciencePal chip 增加外链。 |
| A6 | CTA | 渐变品牌块 | 保留。 |

> Hero 保持浅色是对 Bohrium「简洁、清爽」的忠实还原，也与「现在的版本还可以」一致；A3 深色带用于视觉节奏对比，让动图更聚焦。

## 5. 外壳统一细则

### 5.1 导航 → P1 七区导航
- 结构与 `index.html`/`technology.html` 完全一致的 `.nav-links`，顺序：**首页 / 技术平台 / Antibody Agent / 教育 / 论坛 / 了解我们**。
- `Antibody Agent` 标 `class="active"`。
- 动作区：`<a href="index.html#contact" class="btn btn-green">联系我们</a>`（替换旧的「免费试用 Agent」动作按钮；页内「免费试用」入口仍通过 hero/CTA 按钮提供）。
- Hero 为浅色 → 用普通 `.nav`（**不加** `nav--on-dark`）。
- 修正旧导航问题：旧的 5 项（首页/教育/Antibody Agent/论坛/**网站介绍**）→ 新 7 项，且「网站介绍」改「了解我们」。

### 5.2 页脚 → P1 页脚
- 用 `index.html`/`technology.html` 同款 `.footer` + `.footer-grid`（探索 / 社区 / 联系 三列 + 品牌简介 + `.footer-social` + `.footer-bottom` 两行）。
- 替换旧页脚的「产品 / 探索 / 关于」列与旧底部文案（旧为「紫 + 亮绿」，新统一「紫 + 荧光绿 · 科技与趣味的平衡」）。

### 5.3 主题/强调色
- Hero 大标题强调片段：`grad-text` → 保留 `grad-text`（浅色区仍适用）或迁移到 `.txt-green`（浅底绿）。**决定：** 浅色区强调统一用 `.txt-green`（与 P2 浅色支柱一致）；A3 深色区标题强调用 `.txt-neon`。
- 页面局部 `<style>`（chat-ui / flow / agent-chip）保留不动——其 token 仍存在，渲染正常。仅在必要处补 A3 新样式与外链样式。

## 6. A3 案例示范（核心新增）详设

### 6.1 结构
深色分区，含标准 `.eyebrow`/`.section-title`/`.section-sub` 头部 + 3 张卡片网格（`.case-anim` 复用/新增类）。每卡：
1. **一句话 → 候选序列**：提示文字「打字机」式出现，随后 mock 序列 token 逐个流入/拼装成一行。演示「从自然语言到候选序列」的过程。
2. **亲和力虚拟筛选排序**：一组候选「条」在循环中重新排序/重排（无数值，仅相对高度与位次变化），演示「实验前虚拟筛选与排序」。
3. **结构 · 表位识别**：抽象的抗体—抗原形状，表位高亮区脉冲呼吸，演示「结合在哪里」。

### 6.2 动图实现
- 纯 CSS/JS 循环动画（PRD 选定「CSS/JS 模拟循环动画」）。
- 优先 CSS `@keyframes` 循环；确需时序编排的（打字机、序列流入、条重排）用一支独立脚本 `assets/js/agent-cases.js`，**以元素存在为守卫**（`document.querySelector('.case-anim')` 存在才运行），确保在其它页面惰性、零副作用。
- **`prefers-reduced-motion: reduce`** 时：动画暂停并显示静止终态（与首页 hero、P2 一致）。
- 动图内**不出现任何数值/单位/百分比/机构名**；文字仅定性流程标签。

### 6.3 无障碍
- 动图容器 `aria-hidden="true"`（纯装饰演示）；卡片标题/说明为真实语义文本。

## 7. SciencePal 外链

- 位置：智能体矩阵中 `SciencePal Agent` chip。
- 形式：chip 内或其下增加一个带标注的出站链接 `了解 SciencePal ↗ → https://sciencepal.ai`，`target="_blank" rel="noopener"`。
- 仅作参考指引，不复制其站点文案/数据。

## 8. 文件改动

| 文件 | 动作 | 职责 |
|---|---|---|
| `agent.html` | 改 | 导航→P1 七区；页脚→P1 页脚；分区加 `.section-dark/.section-light`；A3 新增分区；SciencePal 外链；强调色迁移 |
| `assets/css/style.css` | 改（**末尾追加** P3a 段） | `.case-anim` 卡片 + 三种动图（打字机/序列流入/条重排/表位脉冲）关键帧 + `prefers-reduced-motion` 兜底 + 外链样式 + 响应式 |
| `assets/js/agent-cases.js` | 新建 | 案例示范的时序编排（守卫式，元素不存在则不运行） |

**复用不改：** `assets/js/main.js`（导航/toast/reveal）、`assets/js/agent-demo.js`（hero 对话演示）、`assets/js/hero-anim.js`（本页 hero 无 canvas，不引入）。

**不动：** `index.html` / `technology.html` / `education.html` / `forum.html` / `about.html`。

## 9. 验证要点

- `preview_snapshot`：导航为 7 项且 `Antibody Agent` active；出现「案例示范」标题与 3 张卡；页脚为探索/社区/联系三列。
- A3 为深色（`section.section-dark` 背景 ≈ `rgb(7,10,20)`）；A2/A4 为浅色，黑白节奏连贯。
- 动图：三张卡各自循环播放；`preview_resize` 或模拟 `prefers-reduced-motion` 时动画停在静止终态。
- 文案与动图**无任何数值/机构名**（不杜撰复核）。
- SciencePal 外链存在、`href=https://sciencepal.ai`、`target=_blank`。
- 各 CTA/入口链接可用（hero「免费试用」按钮、导航「联系我们」→`index.html#contact`、页脚链接）；`preview_network` filter=failed 为空。
- 移动端（375px）：导航折叠汉堡；A3 卡片单列；动图不溢出。
- `preview_console_logs` level=error 为空。

## 10. Self-Review 记录

- **占位符扫描**：无 TBD/TODO。
- **一致性**：A3 类名 `.case-anim`（§6 定义、§8 使用）；外壳类复用 P1 已存在的 `.nav/.nav-links/.footer/.footer-grid/.footer-bottom/.section-dark/.section-light/.btn/.btn-green/.txt-neon/.txt-green/.eyebrow/.section-title`。
- **不杜撰**：动图与文案仅定性，无数值/机构名；SciencePal 为 PRD 点名的参考外链。
- **范围**：单页、聚焦，适合单一实现计划。
