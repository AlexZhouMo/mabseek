# MabSeek P3b · 教育页 设计规格

> 子阶段 P3b（P3 三子阶段之二：Agent → **教育** → 论坛）。做法：**统一外壳 + 增量新增**。头脑风暴确认后落档；下一步交由 writing-plans 生成实现计划。

## 1. 目标

把现有 `education.html`（P1 之前旧外壳）对齐 P1/P2 外壳（七区导航 + 统一页脚），并叠加 PRD-20260825 对教育页的唯一新增诉求——**「知识地图」板块**（采用 **Hello算法式章节树**）。既有内容本体（《疫苗的力量》元视频/弹幕/留言/课时卡/**已有交互式知识图谱**/配套资源/跨板块联动/讲座与成长资源）全部保留不改。

## 2. 范围与非目标

**做（In scope）**
- 外壳统一：导航→P1 七区；页脚→P1 页脚。
- 新增「知识地图 · 学习路径」分区（Hello算法式章节树，章→节，可展开）。

**不做（Out of scope / YAGNI）**
- 不加深色带：教育页本就是「白 / bg-soft」浅色交替 +（深色视频播放器组件），已是设计系统浅色侧；强塞深色会与既有浅色组件（info-card/player-wrap/accordion/力导向图 `#kg`）冲突。仅统一外壳，新分区用 `.section-light`。
- 不重写既有内容本体；不重排现有分区编号（1/2/3/4）。
- 不新增 JS（复用页面既有 accordion 脚本）。
- 不接入真实推理/后端；不做中英切换（P4）。
- 不改动 `index.html`/`technology.html`/`agent.html`/`forum.html`/`about.html`。

## 3. 内容来源（权威，不杜撰）

- 既有内容源自 PRD-0808 + P1，仅迁移外壳。
- 「知识地图」章节内容 = 把**站内已有主题**（课时卡标题 + 技术平台三支柱 / Agent 能力）重新组织为学习路径，外加少量**领域通用主题标签**（如「中和抗体」「抗体结构与功能」）。**无任何编造的数字、时长、课时数、百分比或事实**；每节描述为定性一句话。用户已确认此来源可接受（若日后有真实大纲可替换）。

## 4. 分区顺序（保留既有，插入一处）

页面顺序不变，仅在既有「2 · 教学核心工具（交互式知识图谱 `#graph`）」之后、「3 · 配套学习资源区」之前，插入新分区：

| 位置 | 分区 | 说明 |
|---|---|---|
| — | 导航 | 换 P1 七区（教育 active，普通 `.nav`，浅色首屏） |
| — | 页头 `.page-hero` | 保留 |
| — | 课程 Banner + 三栏 | 保留 |
| 1 | 课时播放专区（`#video`，含弹幕/留言/课时卡） | 保留 |
| 2 | 教学核心工具 · 交互式知识图谱（`#graph`，力导向图 + 三层） | 保留 |
| **新** | **学习路径 · 知识地图（`#map`，章节树）** | **新增，本规格核心** |
| 3 | 配套学习资源区（accordion） | 保留 |
| 4 | 跨板块联动 | 保留 |
| — | 学术讲座 + 成长资源 | 保留 |
| — | 页脚 | 换 P1 页脚 |

## 5. 外壳统一细则

### 5.1 导航 → P1 七区
- `.nav-links` 顺序：**首页 / 技术平台 / Antibody Agent / 教育 / 论坛 / 了解我们**；`教育` 标 `active`。
- 动作区：`<a href="index.html#contact" class="btn btn-green">联系我们</a>`（替换旧「免费试用 Agent」）。
- `<header class="nav">`（浅色首屏，**不加** `nav--on-dark`）。
- 修正旧导航：旧 5 项（首页/教育/Antibody Agent/论坛/**网站介绍**）→ 新 7 项，「网站介绍」→「了解我们」。

### 5.2 页脚 → P1 页脚
- 换 `index.html`/`technology.html`/`agent.html` 同款 `.footer` + `.footer-grid`（品牌简介 + 探索/社区/联系 + `.footer-social` + `.footer-bottom` 两行，底部文案「紫 + 荧光绿 · 科技与趣味的平衡」）。

## 6. 「知识地图」章节树详设

### 6.1 结构与复用
- 分区：`<section class="section section-light" id="map">`，标准 `.eyebrow`（文案「学习路径 · 知识地图」，**不带数字编号**以免重排）+ `.section-title` + `.section-sub`（说明它与关系型「知识图谱」互补：图谱看关联、地图顺路径）。
- 章节树**复用页面既有 accordion 组件**：`<div class="accordion kmap">`，每**章** = `.acc-item`（章 1 加 `open` 默认展开），`.acc-head`（章标题 + `.arrow` ▾），`.acc-body > .inner` 内放该章的**节**列表。
- 展开/收起：**复用 education.html 既有内联脚本**（`document.querySelectorAll('.acc-head')` 已绑定所有 `.acc-head`；`.acc-item.open .acc-body` 在加载时设置 maxHeight）。**新增零 JS**。

### 6.2 节（section）行
- 每节一行 `.kmap-node`：节标题 + 一句定性简介 + 两个迷你入口链接：
  - `▶ 视频片段` → 锚点 `#video`（跳课时播放专区）
  - `🤖 问 Agent` → `agent.html`
- 迷你链接复用 `.mini-link` 或新增 `.kmap-link`（样式追加）。

### 6.3 章节内容（定性，无数值）
- **第 1 章 · 免疫与疫苗基础**
  - 疫苗发展史与分类 —— 从病毒免疫到疫苗研发的知识起点
  - 免疫系统如何识别抗原 —— 先天与适应性免疫的基本机制
  - 疫苗免疫应答基础 —— 免疫记忆如何建立
- **第 2 章 · 抗体与中和机制**
  - 抗体在疫苗中的作用 —— 抗体如何提供保护
  - 抗体结构与功能 —— 可变区、恒定区与识别原理
  - 中和抗体 —— 阻断病原体的关键机制
- **第 3 章 · AI 抗体发现**
  - 抗体序列设计 —— 从一句话需求到候选序列
  - 结构与亲和力预测 —— 动手实验前的虚拟筛选
  - 成药性评估 —— 早期规避开发风险
- **第 4 章 · 干湿闭环与验证**
  - VLP 抗原制备 —— 保持天然构象
  - 高通量分离与表征 —— 自动化湿实验验证
  - 数据回流训练 —— 越用越准的闭环

> 简介均为定性描述；**无课时数、时长、百分比、机构名等数值/硬事实**。

### 6.4 无障碍与主题
- 分区浅色，章节树在白底卡内，文字对比达标；`.arrow` 旋转沿用既有 `.acc-item.open .acc-head .arrow` 规则。
- 无新增动画；accordion 展开为用户触发的 maxHeight 过渡（既有行为），不受 reduced-motion 影响诉求。

## 7. 文件改动

| 文件 | 动作 | 职责 |
|---|---|---|
| `education.html` | 改 | 导航→P1 七区；页脚→P1 页脚；`#graph` 后插入 `#map` 知识地图分区（复用 `.accordion`） |
| `assets/css/style.css` | 改（**末尾追加** P3b 段） | `.kmap` 章节树样式：`.kmap-node`（节行布局）、`.kmap-node .desc`、`.kmap-link`、响应式；`.kmap` 内 `.acc-head`/`.acc-body` 的少量视觉微调（不改既有 `.accordion` 全局规则） |

**复用不改：** `assets/js/main.js`、`assets/js/knowledge-graph.js`、education.html 既有内联脚本（弹幕 + accordion）。

**不动：** `index.html` / `technology.html` / `agent.html` / `forum.html` / `about.html`。

## 8. 验证要点

- `preview_snapshot`：导航 7 项、`教育` active；出现「学习路径 · 知识地图」标题与四章章节树；页脚为探索/社区/联系三列。
- 章节树交互：`preview_click` 某 `.acc-head`（知识地图内的章）→ 对应 `.acc-body` 展开（maxHeight>0）；再点收起。确认既有资源区 accordion 仍正常（两处 accordion 互不干扰）。
- 既有「知识图谱」`#kg` 力导向图仍渲染（`knowledge-graph.js` 未受影响）；弹幕仍运行。
- 内容**无数值/机构名**（不杜撰复核）：知识地图区文本仅定性主题与描述。
- 迷你链接：`▶ 视频片段`→`#video`、`🤖 问 Agent`→`agent.html`；`preview_network` filter=failed 为空。
- 移动端（375px）：导航折叠汉堡；知识地图章节树单列不溢出。
- `preview_console_logs` level=error 为空。

## 9. Self-Review 记录

- **占位符扫描**：无 TBD/TODO。
- **一致性**：新类 `.kmap/.kmap-node/.kmap-node .desc/.kmap-link`（§6 定义、§7 使用）；复用 P1 外壳类 `.nav/.nav-links/.footer/.footer-grid/.footer-social/.footer-bottom/.btn/.btn-green/.eyebrow/.section-title/.section-sub/.section-light`；复用页面既有 `.accordion/.acc-item/.acc-head/.acc-body/.inner/.arrow/.mini-link`（其展开 JS 已存在于 education.html 内联脚本）。
- **不杜撰**：知识地图仅定性主题 + 站内已有内容重组 + 领域通用标签；无数值/时长/课时数/机构名。
- **零新 JS**：复用既有 accordion 脚本；新增两处 accordion 不冲突（脚本按 `.acc-head` 全选绑定，天然覆盖新章节）。
- **范围**：单页、聚焦，适合单一实现计划。
