# 设计文档：技术平台配图 + Agent 湿实验卡暗色化 + 全站图中文化

- 日期：2026-09-06
- 状态：已批准，待实现

## 背景

三部分需求：① technology.php（技术平台）三个 pillar 区块的媒体位当前是空 CSS 渐变占位块，需填入风格统一的真实图；② agent.php `#wetlab` 卡片白底需改暗色系；③ 全站含英文文字的配图重生为中文内容。站点为深色科技 UI（紫 #6D3BEB / neon green）。

## A. 技术平台三张 pillar 图（新建）

technology.php T3a/T3b/T3c 三区块的 `<div class="pillar-media" aria-hidden="true"></div>`（`.pillar-media` 高 300px，圆角，现为紫绿 radial 渐变 + 点阵占位）内加 `<img>`：

| 区块 | media 位置 | 主题 | 文件名 |
|---|---|---|---|
| T3a Data to train | 右（`.pillar`） | 数据/训练：抗体序列数据库、正负样本、数据流可视化 | `tech-data.png` |
| T3b AI for science | 左（`.pillar--rev`） | AI/算法：神经网络、生成式设计、亲和力预测 | `tech-ai.png` |
| T3c Wet lab to validate | 右（`.pillar`） | 自动化湿实验：实验室机械臂、表达纯化、功能验证 | `tech-wetlab.png` |

- 改动：`.pillar-media` 内加 `<img src=... alt=... onerror="this.parentElement.style.display='none'... ">`（或保留渐变作兜底）。图 3:2 或适配 300px 高、按 `object-fit:cover`。
- 需在 CSS 补 `.pillar-media img { width:100%; height:100%; object-fit:cover; }`。

## B. agent.php #wetlab 卡片暗色化

第 162 行 `#wetlab` 卡片 inline style：
- `background:#fff` → 暗色（`#0d1122` 系，呼应 `.pillar-media` 底色）。
- `border:1px solid var(--line)` → 暗色边框（`var(--line-dark)`）。
- 内部 `<h3>`（默认深色文字）→ 亮色（`#fff` 或 `var(--ink-on-dark)`）。
- `<p style="color:var(--ink-3)">` → 暗底可读的浅灰（`var(--ink-on-dark-2)`）。
- tag 标签在暗底的可读性检查，必要时微调。
- `antibody-structure.png` 本身已是暗色无字，不重生。

## C. 全站图中文化重生（9 张）

含英文文字的图重生为中文版，沿用原文件名（前端零改动）：

| 图 | 现英文内容 | 重生要点 |
|---|---|---|
| agent-hero.png | AI + WET-LAB PLATFORM 等大量 | 中文流程图：AI 抗体设计 / 实验室自动化 / 数据与模型学习 / 最优候选 |
| international.png | SCIENCE KNOWS NO BORDERS / RESEARCH HUB | 中文：全球科研合作网络、各洲研究中心 |
| location.png | ADVANCING ANTIBODY DISCOVERY / MEDICAL SCHOOL | 中文：抗体发现与创新、大学医学院研究实验室 |
| education-banner.png | VACCINE / FOR A STRONGER TOMORROW | 中文疫苗标签或去标签 |
| forum-adc.png | PUBLIC LECTURE RECAP / VACCINES & IMMUNITY | 中文：公开课回顾主视觉 |
| forum-bioinfo.png | （同批英文海报） | 中文：抗体设计工作坊 |
| forum-protocol.png | （同批英文海报） | 中文：AI 驱动的抗体发现讲座 |
| lab-scene.png | 虚构品牌 NEXGEN BIOSCIENCES | 去掉虚构品牌名（改中文或无字） |
| hero-home.jpg | 淡英文装饰字 AI-POWERED... | 中文装饰字或去掉 |

- **不动**：antibody-structure.png（无文字）。
- 中文文字**尽力生成 + 逐张检查**：AI 渲染中文常出错（错别字/乱码），生成后逐张视觉核对，明显错乱的重试或降级为少字/无字版本。

## 风格统一

全部深色科技抽象风，紫 #6D3BEB 绿 neon 渐变，与现有配图一致。技术平台三张与全站同风格。

## 流程与验证

1. 现有图备份到 `_backup/`（已 gitignore）。
2. image-gen-pro（gpt2, medium）逐张生成，带重试兜底网关 IncompleteRead。
3. 中文渲染逐张视觉检查。
4. 改 technology.php（3 处加 img）、style.css（`.pillar-media img` + wetlab 若抽出 class）、agent.php（wetlab inline style 改暗色）。
5. 浏览器逐页验证（technology / agent#wetlab / 各含图页），跑 `tests/run.php`。

## 非目标（YAGNI）

- 不重生 antibody-structure.png。
- 不改文案 snippet 内容（只改图与卡片样式）。
- 不改其它页面布局。
