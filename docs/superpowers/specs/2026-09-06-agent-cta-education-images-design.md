# 设计文档：Agent CTA 美化 + Education 图片纯图例化

- 日期：2026-09-06
- 状态：已批准，待实现

## 需求 1：agent.php 底部 CTA 深色科技美化（纯 CSS）

现状（agent.php ~198 行）：`.section-sm` 内一个 inline style 块，`background:var(--grad-brand)`（紫色线性渐变）+ 白字标题/副标 + 绿按钮，朴素。

改为深色科技质感，呼应站点深色风与 `.pillar-media`：
- 抽出 class `.agent-cta`（替换超长 inline style）放入 style.css。
- 底色深色（`#0d1122`/`#070A14`）+ 紫绿双径向光晕（radial-gradient 紫 rgba(124,77,255,.35) + 绿 rgba(0,224,164,.30)，类似 pillar-media）。
- `::after` 细微点阵纹理（复用 pillar-media 手法）。
- 1px 半透明紫边框 + `var(--sh-lg)` 阴影。
- 标题白字、副标浅灰（`var(--ink-on-dark-2)`）、按钮保留 `.btn-green`（深底更突出）。
- 内容需 `position:relative;z-index:1` 压在纹理之上。

## 需求 2：education.php 图片去文字、纯 AI 图例（5 张重生）

education 页涉及图全部重生为**无文字纯视觉图**，深色科技抽象风、紫绿渐变，沿用原文件名：

| 文件 | education 用途 | 重生要点 |
|---|---|---|
| education-banner.png | banner(340px)/播放器(16:9)/01课/03课缩略图(16:10) 复用4处 | 无文字疫苗/免疫抽象视觉，16:9，构图适合多比例裁切 |
| lab-scene.png | 02课缩略图(16:10) | 无文字实验室科研场景 |
| forum-adc.png | 回顾卡片1(16:9) | 无文字疫苗与免疫抽象视觉 |
| forum-bioinfo.png | 回顾卡片2(16:9) | 无文字抗体设计抽象视觉 |
| forum-protocol.png | 回顾卡片3(16:9) | 无文字 AI 抗体发现抽象视觉 |

- **不重生** `antibody-structure.png`（04课缩略图）：本就无文字，且同时用在 agent 湿实验卡，重生风险大于收益。
- **比例处理**：图无文字后，各位置的 `object-fit:cover` 裁切不再切到关键文字，视觉自然，解决「显示不全」。不改 HTML/CSS 布局。
- **中文文字**：本需求是去文字，生成时明确要求「无任何文字、纯视觉」，逐张检查确认无残留文字。

## 影响与注意

- forum-×3 同时用作 edu 回顾封面（education.php 回顾卡片），去文字后回顾封面变纯图例（已确认接受）。
- forum-×3 无其它页面引用（论坛封面走数据库 cover，非这三张文件）。
- education-banner 中文版（含「疫苗的力量」标题）被无文字版替换——顶部 banner 的标题文字本就有 HTML overlay（`.course-banner .overlay h2`）承载，去掉图内文字不影响标题展示。

## 非目标（YAGNI）

- 不重生 antibody-structure.png、不改 agent 湿实验卡。
- 不改 education.php 的布局/比例 CSS（靠无文字图自然适配）。
- 不改文案 snippet。

## 流程与验证

1. 现有图备份到 `_backup/`（覆盖前）。
2. CTA：改 style.css（抽 `.agent-cta` class + 深色质感）+ agent.php 换 class。
3. 图：image-gen-pro（gpt2）生成 5 张无文字图，带重试兜底 IncompleteRead；逐张视觉检查确认无文字。
4. 浏览器验证：
   - agent 底部 CTA 深色科技质感生效。
   - education 各图无文字、各比例位置无关键内容裁切。
   - `tests/run.php` 回归。
