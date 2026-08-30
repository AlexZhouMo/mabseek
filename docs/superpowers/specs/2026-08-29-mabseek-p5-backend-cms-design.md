# MabSeek 后台内容管理系统（PHP + SQLite）Design Spec

**日期：** 2026-08-29
**阶段：** P5（把静态 DEMO 升级为可后台配置的动态站）
**范围决策（已与用户确认）：** 结构化集合 + 文案片段库 · PHP 动态渲染 · 公网生产（阿里云 Ubuntu · Nginx + PHP-FPM）· 图片上传（严格校验）· 代码组织方案 A（极简自研 MVC-lite，web 根隔离）

---

## 1. 目标

把当前 6 个纯静态 HTML 页面（index / technology / agent / education / forum / about）升级为 **PHP 动态渲染 + SQLite 存储 + 后台管理**的站点：

1. 文章、公告、简介文案等**尽可能全部**做到后台可编辑（`admin.php` 入口，账号 `admin` / 初始密码 `mabseek2026`）。
2. **不改变现有布局与风格**——markup 逐字保留，只把文字替换为从 DB 读取。
3. 自动把现有文案**写入数据库**（`bin/seed.php`），后台开箱即有全部现有内容。
4. 架构简洁高效，**严格做好风控与安全**（公网生产基线）。

**成功标准：** 改造后前台逐页与改造前逐字一致（样式、深浅底节奏、导航/页脚不变）；后台可增删改所有集合与文案片段并即时反映到前台；通过安全基线（见 §5）。

## 2. 约束

- **纯 PHP + SQLite**，零第三方框架 / 零 Composer 依赖（PHP 8.5，`pdo_sqlite` + `sqlite3` 已确认可用）。
- **不改变布局与风格**：不新增前台 CSS 框架；后台 UI 复用现有 `style.css` 令牌与组件。
- **不杜撰**（用户长期约束）：seed 内容 = 现有 HTML 原文，不新增未经核实信息。
- 公网生产环境，安全按最高标准做（§5）。

## 3. 架构（方案 A · web 根隔离）

```
mabseek/                      ← 仓库根（Nginx root 指向 public/）
├── public/                   ← ★ Web 根，唯一对外可达
│   ├── index.php  technology.php  agent.php
│   ├── education.php  forum.php  about.php
│   ├── admin.php             ← 后台唯一入口（登录→CRUD 前置控制器）
│   ├── assets/               ← 现有 css/js/images 原样迁入
│   │   └── images/uploads/   ← 上传目录（Nginx 禁止执行 PHP）
│   └── partials/             ← nav.php / footer.php（六页共用，带参数）
├── app/                      ← ★ web 根之外，不可被直接请求
│   ├── bootstrap.php         ← 配置 + 安全会话启动 + DB 连接
│   ├── config.php            ← 常量（DB 路径、超时、锁定阈值、上传上限等）
│   ├── db.php                ← PDO 单例 + 建表（migrate）
│   ├── auth.php              ← 登录 / 登出 / 会话校验 / 登录风控
│   ├── csrf.php              ← token 生成与校验
│   ├── helpers.php           ← e() 转义、snippet() 取文案、redirect()、flash()
│   ├── repositories/         ← news / forum_posts / forum_hot / team / partners / content_cards / snippets
│   └── admin/                ← 后台各模块（被 admin.php include）
├── data/                     ← ★ web 根之外
│   └── mabseek.sqlite        ← 数据库文件（0640，属主 php-fpm 用户）
├── bin/seed.php              ← 一次性把现有文案导入 DB（幂等）
├── deploy/nginx.conf.sample  ← 站点配置样例（rewrite + 目录保护 + 安全头）
└── docs/                     ← 现有 architecture.md / ui-style-guide.md + 本 spec
```

### 3.1 渲染

- 每个公开 `.php` 顶部 `require __DIR__/'../app/bootstrap.php'`，从 SQLite 读内容，填入**逐字保留的现有 markup**。
- 六页完全相同的导航/页脚抽为 `partials/nav.php`、`partials/footer.php`，用参数覆盖既有差异：
  - `$active`：当前页（决定 `.active` 高亮）。
  - `$navOnDark`：index / technology 为 `true`（`.nav--on-dark`）。
  - `$contactHref`：index / about 为 `#contact`，其余为 `index.php#contact`。
- 前台只渲染 `published=1` 的集合项，按 `sort` 升序。

### 3.2 URL

- Nginx `try_files` 提供整洁 URL：`/`→`index.php`、`/about`→`about.php` 等。
- 旧 `/index.html`、`/about.html` … **301 重定向**到新地址，避免死链。
- 布局与样式零改动，仅 URL 后缀变化（对访客基本无感）。

## 4. 数据模型（SQLite）

所有表含 `id INTEGER PRIMARY KEY AUTOINCREMENT`；集合表含 `sort INTEGER DEFAULT 0`、`published INTEGER DEFAULT 1`、`created_at`/`updated_at`（TEXT，ISO8601）。

### 4.1 内容集合表

| 表 | 用途 | 关键字段 |
|---|---|---|
| `news` | 新闻与活动（about #news，5 条） | `date_day`(如"08")、`date_ym`(如"2026·08")、`category`(res/edu/daily)、`title`、`summary`、`image`(可空)、`sort`、`published` |
| `forum_posts` | 论坛内容流（forum #feed，8 条） | `category`、`cover_type`(img/grad)、`cover_ref`(图片路径或 emoji)、`cover_variant`(''/g2/g3)、`title`、`tags`(逗号分隔)、`author_name`、`author_avatar_char`、`author_avatar_style`(可空)、`likes`、`sort`、`published` |
| `forum_hot` | 每日/每周热榜（forum #hot） | `list`(day/week)、`rank`、`title`、`category`、`heat`(如"1.2k")、`sort` |
| `team_members` | 核心团队（about，双负责人） | `name`、`affiliation`、`direction`、`role_label`、`role_type`(science/ai→配色)、`avatar_char`、`avatar_variant`(''/g2)、`sort` |
| `partners` | 合作机构 logo 墙（index） | `name`、`logo_image`(可空)、`sort` |
| `content_cards` | 通用卡片组（一表覆盖多处重复卡片） | `group`(pain/capability/achievement/line…)、`icon`、`title`、`body`、`extra`(JSON，如"→解法"行/标签/图标底色)、`sort` |

`content_cards.group` 已知取值（seed 时确定）：`home_pain`（首页四大痛点，extra 含"→解法"行）、`home_capability`（我们能做什么）、`about_achievement`（about 四张成果卡）、`forum_line`（论坛四条内容线，extra 含条目列表与图标底色）。其余重复卡片按需归组。

### 4.2 文案片段表

| 表 | 用途 |
|---|---|
| `snippets` | key-value 文案库。字段：`skey`(唯一，如 `home.hero.title`)、`value`(TEXT)、`grp`(按页分组，如 home/tech/about)、`label`(中文标签)、`type`(text/textarea)、`sort` |

`snippets` 收纳所有零散可编辑文字：各页 Hero 标题/副标题、区块 eyebrow/标题/说明、面包屑尾段、联系邮箱、页脚品牌简介与标语等。key 命名 `<page>.<section>.<field>`。

### 4.3 安全与风控表

| 表 | 字段 |
|---|---|
| `users` | `username`(唯一)、`password_hash`、`created_at`、`updated_at` |
| `login_attempts` | `ip`、`username`、`success`(0/1)、`created_at` |
| `audit_log` | `user`、`action`、`entity`、`entity_id`、`ip`、`created_at` |

### 4.4 范围边界（留在代码，不进后台）

知识图谱 SVG 节点数据（`knowledge-graph.js`）、首页/Agent 的 Canvas 动画（`hero-anim.js`、`main.js` 粒子网络）、Agent 对话演示脚本（`agent-demo.js`、`agent-cases.js`）、教育播放器/弹幕/折叠面板等**交互组件的结构与参数**硬编在 JS/模板中——它们是功能而非"文案"，纳入后台会破坏布局且收益低。

## 5. 安全设计（公网生产 · 最高基线）

### 5.1 认证与会话
- 密码 `password_hash()`（Argon2id）；`mabseek2026` 仅作初始种子，哈希入库，不存明文；后台可改密。
- 登录成功 `session_regenerate_id(true)`（防固定）；会话 cookie `HttpOnly + Secure + SameSite=Strict`。
- 空闲超时 30 分钟 + 绝对超时（如 8 小时）；会话绑定 User-Agent 弱指纹。

### 5.2 登录风控（防爆破）
- `login_attempts` 按 IP + 用户名记失败次数；连续失败 5 次触发锁定（锁 15 分钟），锁定期直接拒绝。
- 失败信息统一模糊（"用户名或密码错误"），不泄露账号是否存在；成功/失败均写 `audit_log`。

### 5.3 注入与 XSS
- 全站 PDO 预处理语句；`ERRMODE_EXCEPTION` + `ATTR_EMULATE_PREPARES=false`。
- 所有 DB 文本输出经 `e()`（`htmlspecialchars`，UTF-8，`ENT_QUOTES`）；不接受/不渲染原始 HTML；正文类字段纯文本存储、前台 `e()` + `nl2br` 呈现。
- 全表单 CSRF token（每会话，`hash_equals` 校验）；写操作 PRG。

### 5.4 文件上传（严格白名单）
- 仅 jpg/png/webp；`finfo` 验真实 MIME + `getimagesize()` 二次确认；大小上限 2MB。
- 随机文件名（禁用原名）；存 `public/assets/images/uploads/`；Nginx 对该目录禁止执行 PHP（只当静态文件）。

### 5.5 服务器 / Nginx（`deploy/nginx.conf.sample`）
- root → `public/`；`app/`、`data/`、`bin/` 在 web 根外，物理不可请求。
- 安全头：`Content-Security-Policy`、`X-Content-Type-Options:nosniff`、`X-Frame-Options:DENY`、`Referrer-Policy:strict-origin-when-cross-origin`、`Strict-Transport-Security`。
- 强制 HTTPS；`server_tokens off`；上传目录 `location` 关闭 PHP 执行。
- `data/mabseek.sqlite` 权限 0640，属主 php-fpm 运行用户。

### 5.6 运行时
- 生产 `display_errors=Off`，错误记日志文件，前台通用错误页。
- seed 与建账逻辑幂等：库中已有 admin 则不重复创建（保护改过的密码）。

## 6. 后台功能（admin.php）

- **入口**：`admin.php` 唯一后台入口。未登录→登录表单；已登录→左侧菜单 + 内容区单页式后台。UI 复用现有 `style.css`。
- **模块**：仪表盘（条目概览 + 最近审计 + 当前登录）、新闻与活动、论坛内容流、论坛热榜、核心团队、合作机构、通用卡片、文案片段、修改密码。
- **交互约定**：写操作 POST + CSRF；成功 PRG；列表支持发布/下架与排序；删除二次确认；关键操作写 `audit_log`；表单回填 `e()` 转义。

## 7. 现有文案自动入库（seed）

- `bin/seed.php` 把当前六页文字逐条写入 DB，前台渲染逐字一致。
- Seed 数据以 **PHP 数组常量**内联在 `bin/seed.php`（不做 HTML 解析）：逐页通读现有 HTML 手工抄录为结构化数组——news 5 条、forum_posts 8 条、forum_hot 12 条、team 2 条、partners 若干、content_cards 各分组、snippets 全部零散文案。
- 执行按表 INSERT；幂等（唯一键/内容指纹跳过已存在），可安全重跑。
- 建 admin：`users` 空则插入 `admin` + 哈希；已存在不动。

## 8. 验证（保证零改动）

- Seed 后逐页 `preview_*` 打开 PHP 版页面，与改造前对比：导航 6 项、页脚、各区块文字、新闻/论坛/团队条目逐字一致，深浅底节奏与样式不变。
- `preview_console_logs` / `preview_network` 无错；`e()` 转义不影响中文与标点。
- 后台走查：登录（错误密码触发计数/锁定）、各集合 CRUD + 发布/排序即时反映前台、文案片段保存生效、图片上传校验、改密、CSRF（缺 token 被拒）。

## 9. YAGNI（明确不做）

- 不做多用户/角色权限（单管理员）；不做富文本/HTML 正文（纯文本）。
- 不把知识图谱/Canvas/对话演示等交互组件数据纳入后台（§4.4）。
- 不引第三方框架 / 前端 CSS 框架；不改前台视觉。
- 不做 i18n（延续既有决策）；不接第三方存储/CDN（图片存本地上传目录）。

## 10. 交付物

`public/*.php`(6) + `admin.php`、`partials/nav.php`+`footer.php`、`app/`（bootstrap/config/db/auth/csrf/helpers + repositories + admin 模块）、`data/`、`bin/seed.php`、`deploy/nginx.conf.sample`、更新 `docs/`。
