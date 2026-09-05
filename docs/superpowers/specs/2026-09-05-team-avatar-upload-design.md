# 设计文档：团队成员头像上传 + 移除头像样式字段

- 日期：2026-09-05
- 状态：已批准，待实现

## 背景

MabSeek 后台「团队成员」（`team_members`，走 `admin_crud` 引擎，模块 `app/admin/team.php`）当前用「头像字（avatar_char）+ 头像样式（avatar_variant：默认/样式2）」渲染文字头像。前台 `about.php` 团队卡渲染逻辑**已支持** `avatar_img`（有图显图、无图显文字头像），但表无该列、Collection 白名单无该列，导致 seed 中的 `avatar_img` 值被静默丢弃，图片头像功能从未生效。本次：① 让头像上传真正落库贯通；② 移除 `avatar_variant`（含「样式2」）。

## 现状

- **表 `team_members`**（`app/db.php`）：列 `avatar_char`、`avatar_variant`，**无 `avatar_img`**。
- **Collection COLUMNS**（`app/repositories/Collection.php`）：`team_members` 含 `avatar_char`、`avatar_variant`，**无 `avatar_img`**。
- **后台 `app/admin/team.php`**：`avatar_char`（text，必填）、`avatar_variant`（select：''=默认 / 'g2'=样式2）。
- **前台 `public/about.php`**（团队卡，约 75–90 行）：
  - `$phClass = 'ph' . (avatar_variant ? ' '.variant : '')`；
  - `if (!empty($m['avatar_img']))` → `<img class="ph">`，否则 `<div class="$phClass">avatar_char</div>`。
- **seed `bin/seed.php`**：team 两条写了 `avatar_img`（`assets/images/team/zhang.png`、`ma.png`，文件均存在）与 `avatar_variant`，但 avatar_img 因白名单被丢弃。
- 图片头像文件 `public/assets/images/team/zhang.png`、`ma.png` 存在。

## 需求 A：头像上传落库

- **db.php `migrate()`**：`add_column_if_missing($pdo, 'team_members', "avatar_img TEXT NOT NULL DEFAULT ''")`（须在建表后；兼容老库）。
- **Collection.php**：`team_members` COLUMNS 加 `'avatar_img'`。
- **team.php**：新增字段 `['name'=>'avatar_img','label'=>'头像图片（可选，留空用默认文字头像）','type'=>'image']`（走现有 `handle_upload`，与 news 配图同机制；返回站内上传路径，无外链风险）。

## 需求 B：移除头像样式字段（去掉样式2）

- **team.php**：删除 `avatar_variant` 字段（默认/样式2 下拉整个移除）。
- **Collection.php**：`team_members` COLUMNS 去掉 `avatar_variant`。
- **db.php 建表**：`team_members` `CREATE TABLE` 语句去掉 `avatar_variant` 列（新库不再建）。老库残留该列不主动删除（SQLite 删列需重建表，风险大；残留列无代码引用，无害）。
- **about.php**：`$phClass` 去掉 avatar_variant 逻辑，文字头像统一 `class="ph"`（默认紫色渐变）。
- **seed.php**：team 两条去掉 `avatar_variant` 键（保留 avatar_img，现可正常落库）。

## 保留

- `avatar_char`（头像字）保留：不传图时的文字头像内容。
- 前台「有图显图、无图显文字」的渲染分支保留。

## 非目标（YAGNI）

- 不删老库 `avatar_variant` 残留列。
- 不改前台团队卡的其它样式/布局。
- 不动其它 CMS 模块。

## 测试与验证

- `tests/run.php` 全套回归 + `tests/test_repositories.php` 若涉及 team_members 列断言需同步（去 variant、加 avatar_img）。
- 起服务验证：
  - 后台团队成员表单有「头像图片」上传控件、无「头像样式」下拉。
  - 上传图片保存 → 前台该成员显示上传的头像图。
  - 不传图片 → 前台显示默认样式文字头像（avatar_char，紫色渐变，无 g2 变体）。
  - 重建库后 seed 的张/马显示真实头像图（zhang.png / ma.png）。
