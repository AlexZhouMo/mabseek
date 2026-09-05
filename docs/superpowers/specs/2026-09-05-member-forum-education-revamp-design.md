# 设计文档：会员必填邮箱 · 教育/论坛门禁 · 论坛统一真实帖 · 教育往期回顾

日期：2026-09-05
状态：已与用户确认，待写实施计划

## 背景

MabSeek 是纯 PHP 8 + SQLite、无框架零依赖的实验室对外网站 + CMS 后台。本次一次性交付六项需求，涉及注册校验、页面门禁、论坛模块重构、教育页裁剪与新增「往期回顾」。

调查发现的关键现状：

- **注册**：邮箱当前为选填。`app/members.php` 的 `member_validate_email()` 对空值 `return true`，前台 `public/register.php` 邮箱 input 无 `required`，label 为「邮箱（可选）」。只有用户名/密码必填。
- **登录态**：会员与管理员共用 session，`auth_check()`（`app/auth.php`）判是否登录，`member_check()` 是硬闸（未登录 → 跳 `login.php`）。导航 `public/partials/nav.php` 用 `!empty($_SESSION['uid'])` 判断显示登录/账号按钮。
- **论坛页 `public/forum.php`** 混合三套内容：
  - `forum_hot`（社区热榜，CMS 静态运营位）
  - `forum_posts`（推荐流，CMS 静态演示卡片，含封面/标签字段，标签筛选是纯前端显隐，点击不进详情）
  - `forum_threads`（真实会员帖，表结构仅 `id/user_id/category/title/body/status/created_at/updated_at`，**无封面无标签**，用枚举 `THREAD_CATEGORIES` 分类，有独立详情页 `public/thread.php?id=`）
- **会员帖分类**：`app/config.php` 常量 `THREAD_CATEGORIES` = `pit`(# 实验踩坑) / `proto`(# Protocol 分享) / `paper`(# 文献精读) / `bio`(# 生信工具) / `job`(# 求职招聘)。
- **教育页 `public/education.php`**：标题文字走 `snippets` 表；结构化内容中只有 `edu_info`/`edu_lecture`/`edu_grow` 三组走 `content_cards`，其余（知识图谱、学习路径地图、配套资源、课时卡片）是硬编码 HTML。
- **admin 后台**：有成熟的 `admin_crud($cfg)` 引擎（`app/admin/crud.php`），新增内容模块照抄 `app/admin/news.php` 即可，支持 `text/textarea/select/checkbox/image/richtext` 字段，含富文本图片上传与 `sanitize_html()` 净化。模块需在 `public/admin.php` 白名单和 `app/admin/shell.php` 菜单注册。表迁移在 `app/db.php` 的 `migrate()`，用 `CREATE TABLE IF NOT EXISTS` 与 `add_column_if_missing()`。仓储 `app/repositories/Collection.php` 的 `COLUMNS` 常量登记每表可写列白名单。

## 已确认的设计决策

1. 邮箱改为必填（仅约束新注册，不强制回填老账号）。
2. 教育、论坛登录后可见：页面顶部加 `member_check()` 硬闸；导航栏对未登录用户隐藏这两个入口。
3. 论坛统一到**真实会员帖 `forum_threads`**：删除社区热榜与 CMS 推荐流演示区，论坛页只保留一个真实帖子流。标签筛选用现有 5 类分类做真实联动，默认 8 条，加载更多用后端 JSON 分页（Ajax），点击进已有详情页。
4. 发帖增加缩略图且必填（新帖必填；编辑旧帖也要求补传；旧帖无封面时列表用占位）。详情页不展示封面。
5. 教育页删除「2 · 教学核心工具」「学习路径 · 知识地图」「3 · 配套学习资源区」三个板块。
6. 教育页新增「往期回顾」：新建独立表 `edu_reviews`；后台可传标题（必填）、缩略图（可不传）、摘要（可不写）、图文正文；缩略图与摘要在**保存时派生**并落库；新建详情页 `public/edu-review.php?id=`。

---

## 详细设计

### 需求 1：邮箱改为必填

**改动点**

- `public/register.php`
  - 邮箱 `<input>` 增加 `required`；label 由「邮箱（可选）」改为「邮箱」。
  - POST 处理分支：邮箱为空时报错「请填写邮箱」（与现有用户名/密码校验风格一致）。
- `app/members.php`
  - `member_validate_email()` 删除 `if ($v === '') return true;`，空值即判为不合法（返回 false）。保留长度与 `FILTER_VALIDATE_EMAIL` 校验。
  - `member_email_taken()` 不改（本就对非空判重）。

**影响面**：仅新注册与"注册时校验"。已存在 email 为空的老账号不受影响，不做强制回填。

**验证**：注册页留空邮箱应被拦截并提示；填非法邮箱被拦截；填合法且未被占用的邮箱可注册成功。

### 需求 2：教育、论坛登录后可见

**改动点**

- `public/education.php`、`public/forum.php`：在 `require bootstrap.php` 之后、渲染之前调用 `member_check()`（未登录 → 跳 `login.php`）。与 `thread-new.php` 门禁同一套。
- `public/partials/nav.php`：`$links` 中 `education`、`forum` 两项仅在已登录（`!empty($_SESSION['uid'])`）时加入渲染；未登录时导航不出现这两个入口。其余入口（首页/技术平台/Agent/了解我们）不变。

**验证**：未登录直接访问 `education.php`/`forum.php` 应跳登录页；未登录时导航栏无「教育」「论坛」；登录后两项出现且可访问。

### 需求 3：论坛统一到真实会员帖

**删除**

- `public/forum.php` 中：
  - 社区热榜区 `#hot`（含 `forum_hot` 数据加载 `$hotAll/$hotDay/$hotWeek` 与热榜切换 JS）。
  - CMS 推荐流区 `#feed`（含 `forum_posts` 数据加载 `$posts`、静态标签筛选 JS、假「加载更多」按钮）。
  - 顶部相应的 `Collection('forum_hot')` / `Collection('forum_posts')` 取数。
- 说明：`forum_hot` / `forum_posts` 两张表与其后台模块（`app/admin/forum_hot.php`、`app/admin/forum_posts.php`）本次**保留不动**（避免牵连后台白名单/菜单与 seed），仅前台论坛页不再渲染它们。如需彻底下线可后续单独处理。

**表结构变更**

- `app/db.php` `migrate()`：对 `forum_threads` 用 `add_column_if_missing($pdo, 'forum_threads', "cover TEXT NOT NULL DEFAULT ''")` 增量补列（兼容老库）。
- `app/repositories/Collection.php`：`forum_threads` 不走 Collection（仓储在 `app/threads.php`），无需改 `COLUMNS`。

**标签筛选（真实联动，用现有 5 类）**

- 筛选栏渲染「全部」+ `THREAD_CATEGORIES` 五项，`data-f` 用分类 key（`all/pit/proto/paper/bio/job`）。

**默认 8 条 + 加载更多（后端 JSON 分页）**

- 常量：每页条数 `FORUM_PAGE_SIZE = 8`（放 `app/config.php`）。
- 首屏：`public/forum.php` 用 `thread_list_published(FORUM_PAGE_SIZE, 0)` 渲染前 8 条（当前分类为「全部」）。同时需要知道总数或"是否还有更多"以决定按钮显隐。
- 新增 JSON 端点 `public/threads-api.php`：
  - 顶部 `member_check()`（与页面门禁一致，登录才可拉取）。
  - 入参：`category`（`all` 或五个 key 之一，白名单校验，非法归为 `all`）、`offset`（int ≥ 0）。
  - 逻辑：`app/threads.php` 扩展/新增按分类+分页查询（`thread_list_published` 增加可选 `category` 参数，或新增 `thread_list_by_category($cat, $limit, $offset)`）。多取一条判断 `hasMore`。
  - 返回 JSON：`{ ok:true, items:[{id,title,cover,category,catLabel,author,created_at}], hasMore:bool }`。作者名沿用现有联表逻辑（`author_nickname` 优先，回落 `author_username`）。字段均转义/安全，正文不返回（列表不需要）。
- 前端 JS（写在 `forum.php` 内联或独立小文件）：
  - 点标签：置为当前分类，`offset=0` 重新拉取并**替换**列表，更新「加载更多」显隐。
  - 点「加载更多」：`offset += 已加载条数`，拉下一批**追加**到列表；`hasMore=false` 时隐藏按钮。
  - 卡片模板与首屏 PHP 渲染保持一致（缩略图 + 分类标签 + 标题 + 作者·时间），整卡链到 `thread.php?id=`。

**缩略图展示**

- 列表卡片展示 `cover`；`cover` 为空（旧帖）时用默认渐变占位块（复用现有 `.cover.grad` 样式思路）。

**详情页**：沿用现有 `public/thread.php?id=`，不展示封面（本次不改详情页封面区）。

**验证**：论坛页只剩真实帖流；首屏 8 条；点分类只显示该类帖并从第 1 批重新计数；加载更多逐批追加、无更多时按钮消失；点卡片进详情；旧帖无封面显示占位。

### 需求 4：发帖增加缩略图（必填）

**改动点**

- 复用需求 3 新增的 `forum_threads.cover` 字段。
- `public/thread-new.php`：
  - 表单加缩略图上传控件：文件选择 → 通过现有 `public/upload.php`（会员登录 + CSRF + 图片校验，返回 `{ok,url}`）上传 → URL 写入隐藏字段 `cover` → 展示预览。
  - 服务端：`cover` 必填校验（为空则拒绝并提示「请上传缩略图」）；`thread_create()` 落库 `cover`。
- `public/thread-edit.php`：
  - 同样加缩略图控件，展示当前封面（若有）。
  - 编辑保存时 `cover` 必填（旧帖补传）；`thread_update()` 落库 `cover`。
- `app/threads.php`：`thread_create()` / `thread_update()` 增加 `cover` 参数与写库。
- 安全：`cover` 落库前校验为站内上传路径（`assets/images/uploads/` 前缀），拒绝外链，防止存任意 URL。

**验证**：发帖不传缩略图被拦截；上传后预览正确、发布后列表显示该封面；编辑旧帖要求补传缩略图。

### 需求 5：教育页删除三个板块

**改动点**

- `public/education.php` 删除以下区块及其内部硬编码 HTML：
  - 「2 · 教学核心工具」知识图谱区 `#graph`（及页面对 `knowledge-graph.js` 的 `<script>` 引用）。
  - 「学习路径 · 知识地图」区 `#map`。
  - 「3 · 配套学习资源区」`#res`。
- 清理仅被上述区块使用的 `snip('edu.kg.*')` / `snip('edu.map.*')` / `snip('edu.res.*')` 引用（页面层删除引用即可；`snippets` 表数据保留不动，避免牵连 seed）。
- 其余板块（Hero、课程 Banner、课时播放专区、跨板块联动、学术讲座、成长资源）保留。

**验证**：教育页不再出现这三个板块；页面无 JS 报错（确认 `knowledge-graph.js` 引用已移除）；其余板块正常。

### 需求 6：教育页新增「往期回顾」+ 后台管理

**新建表 `edu_reviews`**（`app/db.php` `migrate()` 加 `CREATE TABLE IF NOT EXISTS`，仿 `news` 表约定）

```sql
CREATE TABLE IF NOT EXISTS edu_reviews (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  title      TEXT NOT NULL,
  summary    TEXT NOT NULL DEFAULT '',
  cover      TEXT NOT NULL DEFAULT '',
  body       TEXT NOT NULL DEFAULT '',
  sort       INTEGER DEFAULT 0,
  published  INTEGER DEFAULT 1,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL
);
```

**仓储登记**

- `app/repositories/Collection.php` `COLUMNS` 加：
  `'edu_reviews' => ['title','summary','cover','body','sort','published']`。

**后台模块 `app/admin/edu_reviews.php`**（仿 `news.php`）

- `$cfg` 字段：
  - `title`（text，required）
  - `cover`（image，可选）
  - `summary`（textarea，可选）
  - `body`（richtext，图文正文）
  - `sort`（text）
  - `published`（checkbox）
- 调 `admin_crud($cfg)`。
- 在 `public/admin.php:52` 白名单加 `'edu_reviews'`；`app/admin/shell.php` `$adminMenu` 加菜单项「教育 · 往期回顾」。

**保存时派生（缩略图 / 摘要）**

- 通用 `admin_crud` 无派生能力，采用**最小侵入**方案：给 `$cfg` 增加可选 `'derive' => callable` 钩子，在 `admin_crud_save()` 净化后、写库前调用，允许模块回填字段。`edu_reviews.php` 提供该回调：
  - `cover` 为空 → 用正则从 `body`（已净化 HTML）提取第一张 `<img src="...">` 的 src；无图则留空（前台用占位）。
  - `summary` 为空 → 取 `body` 的纯文本（`strip_tags`）前 30 字 + `…`（不足 30 字则不加省略号）。
- 派生逻辑对多字节安全（`mb_substr`）。

**教育页渲染**

- `public/education.php` 新增「往期回顾」板块：`(new Collection('edu_reviews'))->published()`，卡片展示缩略图（空则占位）+ 标题 + 摘要，整卡链到 `edu-review.php?id=`。板块标题文案可硬编码或走新 `snip('edu.review.*')`（本设计用硬编码，减少 seed 牵连）。

**详情页 `public/edu-review.php?id=`**（新建）

- 取 `?id=`，用 `Collection('edu_reviews')->find($id)` 且校验 `published=1`（仿 `thread.php` 的取数与 404 处理）。
- 顶部展示标题（可含封面大图与摘要），正文经 `sanitize_html()` 二次净化后直出 HTML。
- 复用站点导航/页脚与现有页面风格。
- 门禁：教育为登录可见模块，此详情页同样加 `member_check()`。

**seed（可选）**

- `bin/seed.php` 可加 `edu_reviews` 示例若干条（幂等），便于本地预览。若时间紧可留空表，教育页走空状态。

**验证**：后台可新增/编辑/删除往期回顾；不传缩略图时列表显示正文首图（无首图则占位）；不写摘要时显示正文前 30 字+省略号；教育页展示卡片；点卡片进详情页且正文正确净化；未登录访问详情页被拦截。

---

## 数据库变更汇总

- `forum_threads`：`add_column_if_missing` 增列 `cover TEXT NOT NULL DEFAULT ''`。
- 新表 `edu_reviews`（见上）。
- 两者均通过 `app/db.php` `migrate()` 幂等执行，兼容老库。

## 安全与一致性

- 沿用既有安全基线：CSRF、`member_check()` 门禁、图片上传校验（≤2MB、jpeg/png/webp、finfo+getimagesize、随机文件名）、富文本 `sanitize_html()` 双净化（存时+渲染时）。
- `cover` 落库校验为站内上传路径前缀，拒绝外链。
- JSON 端点走同一门禁，仅返回列表所需字段，不泄露隐藏帖或未发布内容。

## 测试

- `tests/run.php` 补充：邮箱必填校验、`edu_reviews` 派生逻辑（缩略图/摘要默认值）、`cover` 站内路径校验、分类分页查询。
- 手动验证各需求的验收点（见各节「验证」）。

## 非目标（YAGNI）

- 不做自由多标签体系（用现有 5 类枚举）。
- 不彻底下线 `forum_hot`/`forum_posts` 表与其后台模块（仅前台停渲染）。
- 不清空 `snippets` 中被删板块的文案数据（仅前台停引用）。
- 不改论坛详情页封面展示。
- 不对老账号强制回填邮箱。
