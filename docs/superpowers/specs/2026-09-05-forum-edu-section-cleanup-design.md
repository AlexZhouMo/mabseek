# 设计文档：论坛/教育板块清理与标题对齐

- 日期：2026-09-05
- 状态：已批准，待实现

## 背景

MabSeek 是纯 PHP 8 + SQLite、无框架零依赖的实验室对外网站 + CMS 后台。本次对论坛页与教育页做三处调整：删除两个演示性板块、统一一个板块的标题结构。属于纯前台展示层清理，不涉及数据模型、后台或安全逻辑。

## 现状

- **论坛页 `public/forum.php`**：底部有一个 `<!-- 关注动态 + 冷启动 -->` section（约 138–161 行），`grid-2` 并排两块——左「关注动态」（`snip('forum.follow.*')`）、右「冷启动」紫色统计卡（`snip('forum.cold.*')`）。均为静态演示内容。
- **教育页 `public/education.php`**：
  - `<!-- 跨板块联动 -->` section（约 256–271 行），紫色渐变大卡，走 `snip('edu.link.*')`，静态演示。
  - 「课时播放专区」section（`id="video"`），标题块两行：eyebrow=`snip('edu.video.eyebrow')`（当前值「1 · 课时播放专区」）+ `section-title`。
  - 「往期回顾」section（`id="review"`），标题块三行：eyebrow「往期回顾」+ section-title + section-sub 说明。
- **snippet 文案**在 `bin/seed.php` 与运行时 `snippets` 表。既有惯例：删板块时前台停引用，snippets 数据保留不动（避免牵连 seed / 后台文案管理）。
- **弹幕 JS**（education.php 底部 `<script>`）服务的是课时专区的 `#danmaku`，与「跨板块联动」板块无关。

## 需求与改动

### 需求 A：论坛删「关注动态 + 冷启动」

- `public/forum.php`：删除整段 `<!-- 关注动态 + 冷启动 -->` section（左「关注动态」+ 右「冷启动」并排区一并删除）。
- `snip('forum.follow.*')` / `snip('forum.cold.*')` 引用随之消失；`snippets` 表数据保留不动。

**验证**：论坛页不再出现「关注动态」与「冷启动」；页面其余板块（帖流、四条内容线）正常。

### 需求 B：教育删「4 · 跨板块联动」

- `public/education.php`：删除整段 `<!-- 跨板块联动 -->` section。
- `snip('edu.link.*')` 引用消失；`snippets` 数据保留。
- 弹幕 JS 不受影响（保留）。

**验证**：教育页不再出现「跨板块联动」；弹幕仍在课时专区正常运行；无 JS 报错。

### 需求 C：课时专区改标题文案 + 对齐往期回顾

- eyebrow 文案「1 · 课时播放专区」→「课时播放专区」：
  - 改 `bin/seed.php` 中 `edu.video.eyebrow` 默认值。
  - 更新运行时 `snippets` 表中该条记录（去「1 · 」前缀），使现有库即时生效。
- 标题块补第三行 `section-sub`，与往期回顾三行结构对齐：
  - 在 `education.php` 课时专区标题块 `<h2 class="section-title …">` 后增加 `<p class="section-sub reveal d2">元视频点播、实时弹幕与专属留言区，边看边学边讨论。</p>`。
  - 该 sub 文案硬编码（减少 seed 牵连，与往期回顾板块一致做法）。

**验证**：课时专区眉题显示「课时播放专区」（无「1 ·」）；标题块为三行（eyebrow + title + sub），与往期回顾结构一致。

## 非目标（YAGNI）

- 不清理被删板块的 `snippets` 数据（仅前台停引用）。
- 不改动被删板块之外的任何逻辑、样式或后台。
- 不为课时专区 sub 文案新增 snippet（硬编码，随板块内敛）。

## 测试

- `tests/run.php` 全套回归通过（本次改动为纯展示层，不新增单元测试）。
- 手动验证各需求的验收点（见各节「验证」）。
