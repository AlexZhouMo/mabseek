# 设计文档：下线论坛帖子/热榜 CMS 模块 + 清理冗余

- 日期：2026-09-05
- 状态：已批准，待实现

## 背景

MabSeek 后台历史上有两个论坛 CMS 假数据模块 `forum_posts`（论坛帖子）与 `forum_hot`（论坛热榜），用于早期前台演示。前台论坛页已改为真实会员帖流，这两个模块的前台渲染早已移除，但表、后台模块、seed、装配点仍保留（前几轮 spec 有意「仅停前台渲染、不下线」以减少牵连）。本次彻底下线这两个模块并清理所有冗余；后台「论坛」组的「发帖」重命名为「帖子管理」。

## 现状（forum_posts / forum_hot 的全部踪迹）

1. `app/admin/forum_posts.php`、`app/admin/forum_hot.php` — 两个 admin_crud 模块文件。
2. `app/db.php` `migrate()` — 两段 `CREATE TABLE IF NOT EXISTS`。
3. `app/admin/shell.php` — `$adminMenu` 论坛组含 `forum_posts` / `forum_hot`；`threads` label 为「发帖」。
4. `public/admin.php:52` — `$allowed` 白名单含这两个 key。
5. `app/admin/dashboard.php` — `$collectionLabels` 含这两个表的统计。
6. `app/repositories/Collection.php` — `COLUMNS` 含这两个表的列白名单。
7. `bin/seed.php` — 两段 `seed_collection('forum_hot', …)` / `('forum_posts', …)`。
8. `tests/test_db.php` — 表存在断言列表含 `forum_hot` / `forum_posts`。

`public/forum.php` 已无任何引用（前几轮清理完成），无需改。

## 需求与改动

### 菜单与命名（shell.php）
- 「论坛」组去掉 `forum_posts`、`forum_hot`，仅保留 `threads`。
- `threads` 的 label「发帖」→「帖子管理」。
- 保留「论坛」分组标题（单子项）。

### 删除文件
- `app/admin/forum_posts.php`
- `app/admin/forum_hot.php`

### 数据库（db.php `migrate()`）
- 删除 `forum_posts`、`forum_hot` 两段 `CREATE TABLE`。
- 新增 `DROP TABLE IF EXISTS forum_posts;` 与 `DROP TABLE IF EXISTS forum_hot;`（幂等；部署后老库自动清除这两张假数据表；数据无价值故不备份保留）。

### 装配点清理
- `public/admin.php`：`$allowed` 去 `'forum_posts'`、`'forum_hot'`（移除后访问这两个 module → 回落 dashboard）。
- `app/admin/dashboard.php`：`$collectionLabels` 去这两个表统计行。
- `app/repositories/Collection.php`：`COLUMNS` 去这两个表的列白名单（下线后无 Collection 访问它们）。
- `bin/seed.php`：删两段 `seed_collection`。

### 测试（tests/test_db.php）
- 表存在断言列表去掉 `forum_hot`、`forum_posts`。
- 新增反向断言：`forum_posts` / `forum_hot` 不在表列表中（验证 DROP 生效）。

## 安全与一致性
- DROP 仅针对这两张确定无用的假数据表，幂等（IF EXISTS），对未含该表的新库无影响。
- 白名单移除后，旧书签/直链访问 `?m=forum_posts` 安全回落 dashboard，不报错。
- 不触碰真实会员帖体系（`forum_threads` / `app/threads.php` / `threads` 模块逻辑）。

## 非目标（YAGNI）
- 不改真实帖治理模块 `threads` 的业务逻辑（仅改其菜单 label）。
- 不清理 snippets 中可能残留的 `forum.hot.*` / `forum.feed.*` 文案（前台已不引用；属独立文案数据，本次不牵连）。
- 不改前台 forum.php（已无引用）。

## 测试与验证
- `tests/run.php` 全套回归 + 新反向断言通过。
- 起服务人工验证：
  - 后台「论坛」组只剩「帖子管理」；点击进入即原发帖治理页，功能正常。
  - 访问 `admin.php?m=forum_posts` / `?m=forum_hot` → 回落 dashboard（白名单已移除）。
  - dashboard 统计不再含论坛帖子/论坛热榜两行。
  - 重建库后 `forum_posts` / `forum_hot` 表不存在。
