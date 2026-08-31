# 用户登录/注册与会员账号体系 设计文档

> 状态：设计已确认，待评审 → 进入实现计划
> 日期：2026-09-01
> 关联：[后台 CMS](2026-08-29-mabseek-p5-backend-cms-design.md) · [论坛](2026-08-28-mabseek-p3c-forum-design.md)

## 1. 目标与范围

为站点新增**面向公众访客的会员账号体系**：用户可注册、登录、管理自己的账号；管理员可在后台管理会员。入口按钮放在导航栏「联系我们」之前。

**本轮范围（A + C）：**
- A：会员账号 —— 注册、登录、登出、账号资料/密码自助管理。
- C：后台会员管理 —— 管理员对会员的启用/停用/删除/重置密码。

**本轮不做（下轮独立 spec）：**
- B：论坛前台发帖 —— 依赖本轮的会员身份，作为后续 `spec 2` 单独设计与实现。

**最高约束（用户明确强调）：**
- **安全第一**：全程规避 SQL 注入、越权、XSS、暴力破解、会话劫持等常见风险。
- **一键部署可增量同步数据库结构**：`deploy-mabseek.sh` 无需为本功能改动迁移逻辑即可自动、幂等、增量地把新表结构同步到线上，且不丢历史数据。

## 2. 架构决策

### 2.1 账号隔离：共享 `users` 表 + `role` 列（方案 2，用户选定）

管理员与会员共用同一张 `users` 表，通过 `role` 区分（`'admin'` / `'member'`，新行默认 `'member'`）。

**风险与强制防护（必须实现，不可省略）：**
- 共表意味着一旦鉴权判断疏漏，会员可能触达后台 → **越权提权**。
- 因此后台入口 `admin.php` 与所有后台动作**必须**经过 `auth_is_admin()` 硬闸：`$_SESSION['role'] === 'admin'` 才放行；仅 `auth_check()`（已登录）不足以进入后台。
- 会员相关页面用 `member_check()`（已登录即可，不校验 role）。
- 前台会员登录端点（`public/login.php`）与后台管理员登录端点（`admin.php`）**分离**：前台登录成功后**不得**跳转至后台；后台入口不接受 `role='member'` 的会话。
- 注册接口**硬编码** `role='member'` 写入，绝不接受来自请求的 `role` 字段（防止构造参数提权）。
- 后台会员管理**不提供任何修改 role 的入口**，杜绝“把会员升级为管理员”的操作面。

### 2.2 会话

- 复用现有会话加固（`SESSION_NAME`、`session_regenerate_id(true)` 于登录/改密后、UA 绑定、`IDLE_TIMEOUT=1800`、`ABSOLUTE_TIMEOUT=28800`、SameSite=Strict）。
- 登录成功后 `$_SESSION` 增设 `role`，供 `auth_is_admin()` / `member_check()` 判读。

### 2.3 防刷/防爆破

- 复用现有 `login_attempts` 双阈值锁定：`(IP, user)` 维度 `LOGIN_MAX_FAILS=5`、纯 IP 维度 `LOGIN_IP_MAX_FAILS=20`，窗口/锁定 `900s`。前台会员登录接入同一套 `auth_record_attempt` / `auth_is_locked`。
- 注册接入 **GD 图形验证码** + **蜜罐字段**（见 §5）。

## 3. 数据模型

### 3.1 `users` 表最终形态

| 列 | 类型/约束 | 说明 |
|---|---|---|
| `id` | INTEGER PK | |
| `username` | TEXT NOT NULL, UNIQUE **COLLATE NOCASE** | 登录名，大小写不敏感唯一 |
| `password_hash` | TEXT NOT NULL | Argon2id |
| `must_change_password` | INTEGER NOT NULL DEFAULT 0 | 沿用（管理员首登改密/会员重置密码后置位） |
| `email` | TEXT NOT NULL DEFAULT '' | 部分唯一（非空唯一，NOCASE） |
| `phone` | TEXT NOT NULL DEFAULT '' | 部分唯一（非空唯一） |
| `nickname` | TEXT NOT NULL DEFAULT '' | 展示名，可空；输出一律 `e()` 转义 |
| `role` | TEXT NOT NULL DEFAULT 'member' | `'admin'` / `'member'` |
| `status` | TEXT NOT NULL DEFAULT 'active' | `'active'` / `'disabled'` |
| `created_at` | TEXT | |
| `updated_at` | TEXT | |

**索引：**
- `username`：建表即 `UNIQUE COLLATE NOCASE`。
- `email`：`CREATE UNIQUE INDEX IF NOT EXISTS idx_users_email ON users(email COLLATE NOCASE) WHERE email <> ''`（部分唯一，空值不冲突）。
- `phone`：`CREATE UNIQUE INDEX IF NOT EXISTS idx_users_phone ON users(phone) WHERE phone <> ''`。

### 3.2 字段校验规则（服务端为准，前端仅辅助）

| 字段 | 规则 |
|---|---|
| `username` | `^[a-zA-Z0-9_]{3,20}$` |
| `password` | 长度 8–32，且**至少含 1 字母 + 1 数字**（`preg_match('/[A-Za-z]/')` 且 `preg_match('/\d/')`，长度用 `mb_strlen`） |
| `email` | `filter_var($v, FILTER_VALIDATE_EMAIL)`，且长度上限（如 ≤254） |
| `phone` | `^1[3-9]\d{9}$`（中国大陆手机号） |
| `nickname` | 可空；`mb_strlen ≤ 30`；输出 `e()` 转义 |

- 唯一性冲突（用户名/邮箱/手机号）→ 明确逐字段报错，NOCASE 比较。
- **注册成功后自动登录**（用户选定）。

## 4. 迁移与部署（增量、幂等、非破坏）

### 4.1 `migrate()` 是唯一 schema 权威

所有结构变更集中在 `app/db.php` 的 `migrate(PDO)`，在**每次 Web 请求**（bootstrap 预热）与**每次部署**（`bin/seed.php` → `db()` → `migrate()`）都幂等执行。

迁移步骤（全部幂等）：
1. `users` 建表语句补齐新列的 `DEFAULT`（新库直接带全部列）。
2. 老库增量补列：对 `email` / `phone` / `nickname` / `role` / `status` 逐一调用 `add_column_if_missing($pdo,'users', "<col> ... DEFAULT ...")`（先 PRAGMA 判断，再 ALTER；`$table`/`$ddl` 均为代码常量，绝不含用户输入）。
3. 建部分唯一索引：`idx_users_email`、`idx_users_phone`（均 `IF NOT EXISTS`）。
4. **升级陷阱防护（关键）**：新增 `role` 列默认 `'member'`，老库既有管理员行会被赋成 `'member'`，若不处理将**把管理员锁在后台外**。故迁移中幂等回填：`UPDATE users SET role='admin' WHERE username = <SEED_ADMIN_USER 常量> AND role <> 'admin'`；同理 `status` 回填为 `'active'`。

> 说明：本站 `users` 表在本功能上线前只有 seed 管理员一行，`username` 不可自助修改，故以 `SEED_ADMIN_USER` 常量为键回填是可靠且幂等的。

### 4.2 `seed.php`

- 创建/更新 seed 管理员时**显式**写入 `role='admin'`、`status='active'`（与迁移回填互为保险）。

### 4.3 `deploy-mabseek.sh`

- **无需改动迁移逻辑**：3/8 步已备份 DB，5/8 步已执行 `php bin/seed.php` 触发 `migrate()`，DB/uploads 全程保留。
- **唯一改动**：扩展检查行加入 `gd`（GD 图形验证码依赖）：`for ext in pdo_sqlite sodium dom gd`；缺失则明确报错中止，提示安装 `php-gd`。

## 5. 验证码与防刷（GD 图形验证码）

- `public/captcha.php`：用 GD 生成干扰图，**答案哈希后存 session**（非明文），一次性（校验后即失效）、**10 分钟过期**。
- 比对逻辑与图片渲染**分离**：`captcha_answer_ok($input)` 独立函数，便于单测（图片本身不单测）。
- 校验：不区分大小写比较哈希；用后即从 session 清除，防重放。
- **蜜罐字段**：注册表单含一个 CSS 隐藏字段，正常用户留空；非空即判为机器人静默拒绝。
- **渐进式验证码**：登录页在**首次失败后**才要求验证码，降低正常用户摩擦、同时阻断爆破。

## 6. 前台页面与端点

| 文件 | 职责 |
|---|---|
| `public/register.php` | 注册：GET 显示表单（含 CSRF、验证码、蜜罐）；POST 校验格式+唯一+验证码，成功则建 `role='member'` 用户并**自动登录**，跳转账号页。 |
| `public/login.php` | 会员登录：用户名+密码（首败后加验证码）；`status='disabled'` 拒登并提示；成功 `auth_login_user()` 设 `role` 并跳账号页。 |
| `public/account.php` | 账号中心：查看资料；改 `email`/`phone`/`nickname`（唯一性校验**排除自身** `id <> ?`）；改密（校验当前密码，成功后 `session_regenerate_id`）。 |
| 登出 | **POST + CSRF**（不走 GET，防 CSRF 登出）。 |
| `public/captcha.php` | 见 §5。 |

**导航入口**（`public/partials/nav.php`）：在 `.nav-actions` 内、「联系我们」按钮**之前**插入会员入口——未登录显示「登录/注册」，已登录显示昵称/用户名 + 「账号」入口。样式沿用现有按钮体系，保证不错位/不换行。

**鉴权辅助**（`app/auth.php` 扩展）：
- `auth_login_user($user)` 扩展为同时写入 `$_SESSION['role']`。
- 新增 `auth_is_admin(): bool`（`role==='admin'`）。
- 新增 `member_check()`：未登录跳 `login.php`；不校验 role。
- `admin.php` 入口改用 `auth_is_admin()` 硬闸（原 `auth_check()` 之上叠加 role 判断）。

## 7. 后台会员管理（C）

- 专用页 `app/admin/members.php`（**非** `admin_crud` 通用引擎，避免暴露危险字段）。
- 列表**仅列 `role='member'`** 的用户；**不显示 `password_hash`**；不显示/不提供 `role` 修改入口。
- 动作（均 POST + CSRF + `auth_is_admin()` 闸）：
  - **停用/启用**：切换 `status`；停用后该会员立即无法登录。
  - **删除**：删除会员行。
  - **重置密码**：CSPRNG 生成临时密码，写 Argon2id 哈希 + `must_change_password=1`，**临时密码仅在本次响应明文展示一次**（不落库明文、不入日志）。
- 所有动作写 `audit_log`（操作者、目标、动作、时间、IP）。

## 8. 安全清单（贯穿实现）

- **SQL 注入**：全部查询用 PDO 预处理占位符；表名/列名/DDL 仅用代码常量，绝不拼接用户输入。
- **XSS**：所有用户可控文本输出经 `e()`；`nickname` 等展示字段一律转义。
- **CSRF**：所有写操作（注册/登录/登出/改资料/改密/后台动作）`csrf_verify_or_die()`。
- **越权/提权**：后台 `auth_is_admin()` 硬闸；注册硬编码 role；无 role 修改入口；前台登录不跳后台。
- **爆破**：复用 `login_attempts` 双阈值锁定 + 渐进验证码 + 蜜罐。
- **会话**：登录/改密后 `session_regenerate_id(true)`；UA 绑定；idle/absolute 超时；SameSite=Strict。
- **口令**：Argon2id；对不存在用户走恒定时间校验，避免用户名枚举。
- **信息泄露**：登录失败提示不区分“用户不存在/密码错”；错误页不暴露内部细节（`APP_DEBUG=false`）。

## 9. 测试策略（`tests/run.php`，零依赖）

> 共享测试 DB（`:memory:` 或临时文件）有污染风险：每个用例**插入的行务必清理**，或每例独立建库。

- **校验规则**：username/password/email/phone 的正例与反例（边界长度、缺字母/数字、非法邮箱、非 1[3-9] 手机号）。
- **唯一性**：重复 username/email/phone 被拒，且 NOCASE 生效（`Alice` vs `alice`）。
- **注册**：成功后落库 `role='member'`、`status='active'`、**存的是哈希而非明文**、可用该账号登录、自动登录后会话含 role。
- **验证码**：`captcha_answer_ok` 正确/错误/**过期**/**一次性（用后失效）** 四态。
- **登录**：正确、错误、锁定（触发双阈值）、`status='disabled'` 拒登、首败后要求验证码。
- **越权**：`role='member'` 会话过不了 `auth_is_admin()`；`role='admin'` 通过。
- **会员管理**：停用后无法登录；删除后账号消失；重置密码后旧密码失效、新哈希可登录且 `must_change_password=1`。
- **注入韧性**：向注册/登录喂含 `'`、`--`、`;`、`" OR 1=1` 的输入，断言原样入库/正常拒绝、无 SQL 报错（佐证参数化生效）。

## 10. 文件清单（实现计划锚点）

**新增：**
- `public/register.php`、`public/login.php`、`public/account.php`、`public/captcha.php`
- `app/admin/members.php`
- 测试用例（并入 `tests/run.php` 或新增 `tests/auth_test.php`）

**修改：**
- `app/db.php`：`migrate()` 增列 + 索引 + role/status 回填。
- `app/auth.php`：`auth_login_user` 写 role；新增 `auth_is_admin()`、`member_check()`。
- `bin/seed.php`：seed 管理员显式 `role='admin'`、`status='active'`。
- `public/partials/nav.php`：「联系我们」前插入会员入口。
- `admin.php`：入口改 `auth_is_admin()` 硬闸；接入 `members.php` 菜单。
- `app/config.php`：如需新增验证码相关常量（过期时长等）。
- `deploy/deploy-mabseek.sh`：扩展检查加 `gd`。
