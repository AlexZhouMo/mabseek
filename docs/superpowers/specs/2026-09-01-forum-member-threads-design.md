# 论坛会员发帖（spec 2 / B 阶段）设计文档

> 状态：设计已确认，待评审 → 进入实现计划
> 日期：2026-09-01
> 关联：[会员账号体系 A+C](2026-09-01-user-auth-member-accounts-design.md) · [论坛页设计](2026-08-28-mabseek-p3c-forum-design.md)

## 1. 目标与范围

在 A 阶段会员身份之上，让**登录会员发布纯文本帖子**：前台按最新排列展示、有独立详情页；作者可编辑/删除自己的帖；管理员可下架/删除任意帖。

**本轮范围（B）：**
- 会员发帖（纯文本）、编辑/删除本人帖。
- 前台会员帖列表（forum.php 内新板块）+ 帖子详情页。
- 后台帖子治理（下架/恢复/删除，审计）。

**本轮不做（YAGNI，留后续独立 spec）：**
- 评论、点赞、关注、富文本正文、封面图、搜索接入。

**最高约束（沿用 A 阶段）：**
- **安全第一**：规避 SQL 注入、越权、XSS、爆刷、会话劫持。
- **一键部署可增量同步表结构**：`deploy-mabseek.sh` 无需改动，`migrate()` 幂等建表。

## 2. 架构决策

### 2.1 独立表 `forum_threads`（不复用 CMS 策展的 `forum_posts`）

`forum_posts` 是后台策展的卡片流（封面/角标/作者名等展示字段），与会员纯文本帖字段形态不同。混用会污染 CMS。故新建 `forum_threads`，与 `forum_posts` 并存、互不干扰。

### 2.2 审核模式：发布即公开 + 后台下架（用户选定）

会员发帖 `status='published'` 立即可见；管理员可将其置 `hidden`（下架）或删除。以频率限制 + 停用会员拦截防刷。

### 2.3 作者身份与展示

- 帖只存 `user_id`（外键 `REFERENCES users(id) ON DELETE CASCADE`）。展示名**不落库**，渲染时 JOIN `users` 取 `nickname`（空则回退 `username`），改名即时反映。
- **级联删除（用户选定）**：管理员删除会员（`member_delete`）时，其名下帖随外键级联删除。数据库已 `PRAGMA foreign_keys = ON`。

### 2.4 作者权限

作者可**编辑 + 删除**本人帖。所有作者操作强校验 `thread.user_id === $_SESSION 对应的用户 id`，越权不生效。

## 3. 数据模型

### 3.1 `forum_threads` 表

| 列 | 类型/约束 | 说明 |
|---|---|---|
| `id` | INTEGER PRIMARY KEY AUTOINCREMENT | |
| `user_id` | INTEGER NOT NULL, `REFERENCES users(id) ON DELETE CASCADE` | 作者 |
| `category` | TEXT NOT NULL | 白名单：`pit`/`proto`/`paper`/`bio`/`job` |
| `title` | TEXT NOT NULL | 1–120 字（`mb_strlen`） |
| `body` | TEXT NOT NULL | 纯文本 1–5000 字（`mb_strlen`）；输出 `nl2br(e())` |
| `status` | TEXT NOT NULL DEFAULT 'published' | `published`/`hidden` |
| `created_at` | TEXT NOT NULL | |
| `updated_at` | TEXT NOT NULL | |

**索引：** `CREATE INDEX IF NOT EXISTS idx_threads_status_created ON forum_threads(status, created_at DESC)`。

### 3.2 类别常量

复用现有 5 类（与 `app/admin/forum_posts.php` 一致的键与标签）：

```php
const THREAD_CATEGORIES = [
    'pit'   => '# 实验踩坑',
    'proto' => '# Protocol 分享',
    'paper' => '# 文献精读',
    'bio'   => '# 生信工具',
    'job'   => '# 求职招聘',
];
```

放入 `app/config.php`（供校验与前台下拉共用）。

### 3.3 校验规则（服务端为准）

| 字段 | 规则 |
|---|---|
| `title` | trim 后 `mb_strlen` 在 1–120 |
| `body` | trim 后 `mb_strlen` 在 1–5000 |
| `category` | 必须是 `THREAD_CATEGORIES` 的键 |

## 4. 迁移与部署

- 在 `app/db.php` 的 `migrate()` 内 `CREATE TABLE IF NOT EXISTS forum_threads(...)` + `CREATE INDEX IF NOT EXISTS idx_threads_status_created`。幂等、增量、非破坏。
- `migrate()` 内**不得**调用 `iso_now()`（`tests/test_db.php` 未加载 helpers.php）；建表/建索引仅用常量 SQL。
- `deploy-mabseek.sh` **无需改动**：部署时 `bin/seed.php` → `db()` → `migrate()` 自动建表，DB/uploads 全程保留。

## 5. 领域逻辑 —— 新文件 `app/threads.php`（可单测）

在 `app/bootstrap.php` 于 `members.php` 之后 `require_once`。

**防刷常量**（放 `app/config.php`）：
```php
const THREAD_RATE_MIN_SECONDS = 60;    // 两帖最小间隔 60 秒
const THREAD_RATE_DAILY_MAX   = 20;    // 单会员 24 小时最多 20 帖
```

**函数：**
- `thread_validate_title(string): bool` / `thread_validate_body(string): bool` / `thread_valid_category(string): bool`
- `thread_create(int $userId, string $category, string $title, string $body): int` — 硬编码 `status='published'`，写 created/updated_at，返回新 id。
- `thread_get(int $id): ?array` — 原始行（含 hidden），供后台/作者校验。
- `thread_get_public(int $id): ?array` — 仅 `status='published'`，JOIN users 附 `author_nickname`/`author_username`；否则 null。
- `thread_list_published(int $limit, int $offset = 0): array` — `status='published'` 按 `created_at DESC, id DESC`，JOIN users 取作者名。
- `thread_update(int $id, int $userId, string $category, string $title, string $body): bool` — **仅当该帖 user_id === $userId** 才更新，返回是否更新。
- `thread_delete(int $id, int $userId): bool` — 仅作者本人删除，返回是否删除。
- `thread_recent_count_by_user(int $userId, int $sinceSeconds): int` — 统计窗口内该用户发帖数（限流用）。
- `thread_can_post_now(int $userId): bool` — 组合上面两个阈值判断（60 秒内 0 帖 且 24 小时内 < 20 帖）。
- 后台：`thread_admin_list(): array`（全部含 hidden，JOIN users，按 created_at DESC）、`thread_set_status(int $id, string $status): void`（只允许 published/hidden）、`thread_admin_delete(int $id): void`。

## 6. 前台页面与端点

| 文件 | 职责 |
|---|---|
| `public/forum.php`（修改） | 推荐流下方新增「会员发布」板块：列 `thread_list_published(20)`（标题+作者+时间+类别，链接到 `thread.php?id=`）；顶部「发帖」按钮——`auth_check()` 为真跳 `thread-new.php`，否则跳 `login.php`。空列表显示引导文案。 |
| `public/thread.php?id=` | 详情：`thread_get_public(id)`；不存在/hidden → `http_response_code(404)` + 提示。渲染标题、作者名、时间、类别标签、`nl2br(e($body))`。若 `auth_check()` 且该帖 user_id === 当前会员 id → 显示「编辑」链接与「删除」表单（POST → thread-edit.php act=delete）。 |
| `public/thread-new.php` | `member_check()`；发帖前校验 `member_find_by_username($_SESSION['uid'])['status']==='active'`（停用拒发）与 `thread_can_post_now`（限流）。GET 显示表单（类别下拉+标题+正文，含 CSRF）；POST 校验格式→`thread_create`→跳 `thread.php?id=新`。 |
| `public/thread-edit.php?id=` | `member_check()`；仅作者（`thread_get(id).user_id === 当前 id`，否则 404/403）。GET 预填表单；POST `act=update` → `thread_update`；POST `act=delete` → `thread_delete` 后跳 `forum.php`。全程 CSRF。 |

**作者当前 id 获取**：`$me = member_find_by_username((string)$_SESSION['uid']); $uid = (int)$me['id'];`。

## 7. 后台治理 —— `app/admin/threads.php`

- 列 `thread_admin_list()`（全部，含 hidden、作者名、时间、状态）。
- 动作（POST + CSRF + 已在 `auth_is_admin()` 硬闸内）：
  - **下架/恢复**：`thread_set_status(id, 'hidden'|'published')`。
  - **删除**：`thread_admin_delete(id)`。
  - 均写 `audit_log`（操作者、动作、目标 id、IP）。
- 接入 `public/admin.php` 模块白名单加 `threads`；`app/admin/shell.php` 侧栏菜单加「论坛发帖」。
- 风格参照 `app/admin/members.php`（POST 先清外壳缓冲后 PRG 重定向）。

## 8. 安全清单（贯穿实现）

- **SQL 注入**：全部查询 PDO 预处理；表名/列名/DDL 仅代码常量。
- **XSS**：纯文本，标题/正文/作者名输出一律 `e()`；正文 `nl2br(e())`。
- **CSRF**：发帖/编辑/删除/后台动作全部 `csrf_verify_or_die()`。
- **越权**：作者操作强校验 `user_id` 匹配；后台 `auth_is_admin()` 硬闸；hidden 帖不对外。
- **爆刷**：`member_check()` + 停用拦截 + 60 秒/24 小时双阈值限流。
- **信息泄露**：不存在与 hidden 一律 404，不区分；`APP_DEBUG=false`。
- **注册权控**：发帖硬编码 `user_id`（取自会话，不接受请求传入）、`status='published'`（不接受请求传入）。

## 9. 测试策略（`tests/run.php`，零依赖）

> 共享 :memory: 库有污染风险：插入的 users/threads 行务必在用例末尾清理。

`tests/test_threads.php`：
- **校验**：title/body/category 正例反例（边界长度、空、越界、非法类别）。
- **创建**：`thread_create` 落库 `status='published'`、`user_id` 正确、created/updated_at 非空。
- **公开列表/详情**：`thread_list_published` 仅含 published、按时间倒序、含作者名；`thread_get_public` 对 hidden 返回 null。
- **越权**：`thread_update`/`thread_delete` 传入非作者 user_id → 返回 false 且数据不变；作者本人 → 成功。
- **限流**：连续创建后 `thread_can_post_now` 在 60 秒内返回 false；`thread_recent_count_by_user` 计数正确。
- **后台**：`thread_set_status` 下架后不在公开列表；`thread_admin_delete` 删除后 `thread_get` 为 null。
- **级联**：删除某会员后其帖随之消失（验证外键级联）。
- **注入韧性**：标题/正文喂 `'`、`--`、`" OR 1=1` 等，断言原样入库/正常拒绝、无 SQL 报错。

## 10. 文件清单（实现计划锚点）

**新增：**
- `app/threads.php`
- `public/thread.php`、`public/thread-new.php`、`public/thread-edit.php`
- `app/admin/threads.php`
- `tests/test_threads.php`

**修改：**
- `app/db.php`：`migrate()` 建 `forum_threads` 表 + 索引。
- `app/config.php`：`THREAD_CATEGORIES`、`THREAD_RATE_MIN_SECONDS`、`THREAD_RATE_DAILY_MAX`。
- `app/bootstrap.php`：`require_once app/threads.php`。
- `public/forum.php`：新增「会员发布」板块 + 发帖入口。
- `public/admin.php`：模块白名单加 `threads`。
- `app/admin/shell.php`：侧栏菜单加「论坛发帖」。
