# 设计文档：技术平台 pillar 图完整显示

- 日期：2026-09-06
- 状态：已批准，待实现

## 问题

technology.php 三个 pillar 区块的配图（tech-data/tech-ai/tech-wetlab.png，均 1536×1024 的信息图，含大量文字与流程条）被裁切显示不全。原因：`.pillar-media` 固定 `height:300px` + `.pillar-media img` 用 `object-fit:cover`；桌面下容器约 552×300，图等比铺满宽度后高度约 368px，被 cover 上下各裁掉约 34px，标题/底部流程条边缘看不全。

## 方案（改 public/assets/css/style.css）

- `.pillar-media`（476 行）：去掉固定 `height:300px`，改为 `min-height:300px`（图缺失时靠渐变兜底维持高度；有图时由图撑开）。保留圆角/边框/overflow/渐变背景/`::after` 点阵。
- `.pillar-media img`（482 行）：`height:100%` → `height:auto`，`object-fit:cover` 去掉（width:100% 撑满列宽，高度按原比例自然）。
- 移动端 `@media (max-width:860px)`（487 行）：`.pillar-media { height:200px }` → `min-height:180px`（同理，单列下图按比例撑开，兜底保底）。
- `.pillar` 已 `align-items:center`，图变高后与左侧文字垂直居中，无需改。

## 影响

- 三张信息图完整显示（桌面约 552×368），无裁切、无留边。
- 图缺失（onerror 移除 img）时，`.pillar-media` 靠 `min-height` + 渐变背景维持占位。

## 非目标

- 不重生图片、不改图片内容。
- 不改 technology.php 结构（img 标签保持）。
- 不改其它页面。

## 验证

- 浏览器桌面 + 移动端确认三张图完整显示、无裁切。
- 确认图缺失兜底块仍有合理高度。
- `tests/run.php` 回归（纯 CSS 改动，预期不影响）。
