# 会员账号体系（A + C）实现计划

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 为 MabSeek 新增面向公众访客的会员账号体系（注册/登录/登出/账号自助管理）与后台会员管理（启用/停用/删除/重置密码），全程安全第一、一键部署可增量同步表结构。

**Architecture:** 管理员与会员共用 `users` 表并以 `role` 列区分（`admin`/`member`，新行默认 `member`）。后台 `admin.php` 及所有后台动作经 `auth_is_admin()` 硬闸；会员页经 `member_check()`。前台登录端点与后台登录端点分离。schema 变更全部集中在 `app/db.php` 的 `migrate()`，幂等增量、非破坏，部署时由 `bin/seed.php` 触发。校验/注册/账号/会员管理等领域逻辑抽成可单测的纯函数，放入 `app/members.php`；图形验证码放入 `app/captcha.php`。

**Tech Stack:** PHP 8（零框架、零 Composer）、SQLite（pdo_sqlite）、Argon2id、GD（图形验证码）、服务端渲染原生 HTML/CSS、零依赖自研测试套件（`tests/run.php`）。

**关联设计文档:** `docs/superpowers/specs/2026-09-01-user-auth-member-accounts-design.md`

---

## 文件结构（决策锁定）

**新增:**
- `app/members.php` — 会员领域逻辑：字段校验（username/password/email/phone/nickname）、唯一性检查（NOCASE，可排除自身）、注册、按用户名/ID 查询、资料更新、改密、状态切换、删除、重置密码、会员列表。全部为可单测函数，不直接输出 HTML。
- `app/captcha.php` — 图形验证码：答案哈希入 session（一次性、10 分钟过期）、`captcha_answer_ok()` 比对逻辑（与图片渲染分离，便于单测）、GD 图片渲染。
- `public/register.php` — 会员注册页（GET 表单含 CSRF/验证码/蜜罐；POST 校验+建号+自动登录）。
- `public/login.php` — 会员登录页（首败后加验证码；停用账号拒登）。
- `public/logout.php` — 会员登出端点（仅 POST + CSRF）。
- `public/account.php` — 账号中心（查看资料、改邮箱/手机/昵称、改密）。
- `public/captcha.php` — 验证码图片端点（GET 输出 PNG）。
- `app/admin/members.php` — 后台会员管理（列表 + 停用/启用/删除/重置密码，均 POST+CSRF+`auth_is_admin()`）。
- `tests/test_members.php` — 校验/唯一性/注册/登录/越权/会员管理/注入韧性测试。
- `tests/test_captcha.php` — 验证码正确/错误/过期/一次性四态测试。

**修改:**
- `app/db.php` — `migrate()` 增列 + 唯一索引 + role/status 回填。
- `app/auth.php` — 凭证/查询改 NOCASE；`auth_login_user()` 写 `role`；新增 `auth_user_role()`、`auth_is_admin()`、`member_check()`。
- `app/config.php` — 新增验证码常量 `CAPTCHA_TTL`。
- `app/bootstrap.php` — require 新增的 `members.php`、`captcha.php`。
- `bin/seed.php` — seed 管理员显式写 `role='admin'`、`status='active'`。
- `public/admin.php` — 入口硬闸 `auth_is_admin()`；管理员登录拒绝非 admin；模块白名单加 `members`。
- `app/admin/shell.php` — 侧栏菜单加「会员管理」。
- `public/partials/nav.php` — 「联系我们」前插入会员入口（登录/注册 或 昵称+账号）。
- `deploy/deploy-mabseek.sh` — 扩展检查加 `gd`。

---

## Task 1: 数据库迁移（users 增列 + 唯一索引 + role/status 回填）

**Files:**
- Modify: `app/db.php:81-100`（users 建表段与 `migrate()` 末尾）
- Test: `tests/test_db.php`（追加断言，复用现有 :memory: 用例）

- [ ] **Step 1: 追加失败测试到 `tests/test_db.php`**

在 `tests/test_db.php` 末尾（`DROP TABLE t_addcol` 行之后）追加：

```php
// ── 会员体系迁移断言 ──
$ucols = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_COLUMN, 1);
foreach (['email','phone','nickname','role','status'] as $c) {
    check(in_array($c, $ucols, true), "users.$c 列存在");
}
// 唯一索引存在
$idx = $pdo->query("SELECT name FROM sqlite_master WHERE type='index'")->fetchAll(PDO::FETCH_COLUMN);
foreach (['idx_users_username','idx_users_email','idx_users_phone'] as $i) {
    check(in_array($i, $idx, true), "索引存在: $i");
}
// 迁移幂等：重复 migrate 不新增重复列
migrate($pdo);
$ucols2 = $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_COLUMN, 1);
check(count(array_keys($ucols2, 'role')) === 1, '重复 migrate 后 role 仅一列');
// role 回填：seed 管理员用户名的行被置为 admin
$pdo->prepare('INSERT INTO users(username,password_hash,role,status,created_at,updated_at) VALUES(?,?,?,?,?,?)')
    ->execute([SEED_ADMIN_USER, 'x', 'member', 'active', date('c'), date('c')]);
migrate($pdo);
$r = $pdo->prepare('SELECT role FROM users WHERE username = ? COLLATE NOCASE');
$r->execute([SEED_ADMIN_USER]);
check($r->fetchColumn() === 'admin', 'migrate 幂等回填 seed 管理员 role=admin');
$pdo->prepare('DELETE FROM users WHERE username = ? COLLATE NOCASE')->execute([SEED_ADMIN_USER]);  // 清理共享库
```

- [ ] **Step 2: 运行测试，确认失败**

Run: `php tests/run.php`
Expected: FAIL —「users.email 列存在」等断言失败（列/索引尚未创建）。

- [ ] **Step 3: 修改 `app/db.php` 的 users 建表段**

把 `app/db.php:81-86` 的 users 建表语句替换为（新库直接带全部列，username 内联 NOCASE 唯一）：

```php
    CREATE TABLE IF NOT EXISTS users (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      username TEXT NOT NULL UNIQUE COLLATE NOCASE, password_hash TEXT NOT NULL,
      must_change_password INTEGER NOT NULL DEFAULT 0,
      email TEXT NOT NULL DEFAULT '',
      phone TEXT NOT NULL DEFAULT '',
      nickname TEXT NOT NULL DEFAULT '',
      role TEXT NOT NULL DEFAULT 'member',
      status TEXT NOT NULL DEFAULT 'active',
      created_at TEXT NOT NULL, updated_at TEXT NOT NULL
    );
```

- [ ] **Step 4: 在 `migrate()` 末尾追加增量补列、索引与回填**

把 `app/db.php:99` 的 `add_column_if_missing($pdo, 'news', ...)` 一行之后（`}` 之前）追加：

```php
    // 老库增量补列（新库已带；均为代码常量，无用户输入）
    add_column_if_missing($pdo, 'users', "email TEXT NOT NULL DEFAULT ''");
    add_column_if_missing($pdo, 'users', "phone TEXT NOT NULL DEFAULT ''");
    add_column_if_missing($pdo, 'users', "nickname TEXT NOT NULL DEFAULT ''");
    add_column_if_missing($pdo, 'users', "role TEXT NOT NULL DEFAULT 'member'");
    add_column_if_missing($pdo, 'users', "status TEXT NOT NULL DEFAULT 'active'");
    // 唯一索引：username 大小写不敏感唯一（对老库亦生效，无需重建表）；email/phone 部分唯一（空值不冲突）
    $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_users_username ON users(username COLLATE NOCASE)");
    $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_users_email ON users(email COLLATE NOCASE) WHERE email <> ''");
    $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_users_phone ON users(phone) WHERE phone <> ''");
    // 升级陷阱防护：新增 role 默认 'member' 会把老库管理员锁在后台外，故以 SEED_ADMIN_USER 常量为键幂等回填
    $pdo->prepare("UPDATE users SET role='admin' WHERE username = ? COLLATE NOCASE AND role <> 'admin'")
        ->execute([SEED_ADMIN_USER]);
    $pdo->prepare("UPDATE users SET status='active' WHERE username = ? COLLATE NOCASE AND status = ''")
        ->execute([SEED_ADMIN_USER]);
```

> 注意：`migrate()` 内不得调用 `iso_now()`——`tests/test_db.php` 只 require 了 `db.php`/`Collection.php`，未加载 `helpers.php`。回填不写 `updated_at`，避免耦合。

- [ ] **Step 5: 运行测试，确认通过**

Run: `php tests/run.php`
Expected: PASS — 新增断言全绿，且原有断言不回归。

- [ ] **Step 6: 提交**

```bash
git add app/db.php tests/test_db.php
git commit -m "feat(auth): users 表增量迁移——role/status/email/phone/nickname + NOCASE 唯一索引 + 管理员回填"
```

---

## Task 2: 配置常量 + bootstrap 装配占位

**Files:**
- Modify: `app/config.php:28`（环境段前）
- Modify: `app/bootstrap.php:8-10`

- [ ] **Step 1: 在 `app/config.php` 新增验证码常量**

在 `app/config.php:27`（`SEED_ADMIN_PASS` 行之后、`// ── 环境` 段之前）插入：

```php

// ── 验证码 ──
const CAPTCHA_TTL = 600;                          // 图形验证码有效期 10 分钟
```

- [ ] **Step 2: 提交**

```bash
git add app/config.php
git commit -m "feat(auth): 新增 CAPTCHA_TTL 验证码有效期常量"
```

> `app/bootstrap.php` 的 require 行将在 Task 4（members.php）与 Task 6（captcha.php）创建文件时一并添加，避免 require 不存在的文件导致致命错误。

---

## Task 3: auth.php 角色与鉴权辅助

**Files:**
- Modify: `app/auth.php:6-15`（NOCASE 查询）、`app/auth.php:44-50`（login_user 写 role）、`app/auth.php:78-87`（NOCASE）、并新增函数
- Test: `tests/test_auth.php`（追加）

- [ ] **Step 1: 追加失败测试到 `tests/test_auth.php`**

在 `tests/test_auth.php` 末尾追加：

```php
// ── 角色与鉴权辅助 ──
$pdo->exec("UPDATE users SET role='admin' WHERE username='admin'");
check(auth_user_role('admin') === 'admin', 'auth_user_role 读取 admin 角色');
check(auth_user_role('ADMIN') === 'admin', 'auth_user_role 大小写不敏感');
check(auth_user_role('ghost') === '', '不存在用户返回空角色');

$_SESSION = [];
$_SESSION['role'] = 'admin';
check(auth_is_admin() === true, 'role=admin 通过 auth_is_admin');
$_SESSION['role'] = 'member';
check(auth_is_admin() === false, 'role=member 不通过 auth_is_admin');
$_SESSION = [];
check(auth_is_admin() === false, '无会话不通过 auth_is_admin');
```

- [ ] **Step 2: 运行测试，确认失败**

Run: `php tests/run.php`
Expected: FAIL —「Call to undefined function auth_user_role()」。

- [ ] **Step 3: 凭证与查询改为 NOCASE**

`app/auth.php:7` 的 SQL 改为：

```php
    $row = db()->prepare('SELECT password_hash FROM users WHERE username = ? COLLATE NOCASE LIMIT 1');
```

`app/auth.php:79`（`auth_must_change_password`）改为：

```php
    $q = db()->prepare('SELECT must_change_password FROM users WHERE username = ? COLLATE NOCASE LIMIT 1');
```

`app/auth.php:85`（`auth_change_password` 的 UPDATE）改为：

```php
        ->execute([password_hash($newPass, PASSWORD_ARGON2ID), iso_now(), $user]);
```
（此行 SQL 的 WHERE 改为 `WHERE username = ? COLLATE NOCASE`）——即把该 prepare 语句改为：

```php
    db()->prepare('UPDATE users SET password_hash = ?, must_change_password = 0, updated_at = ? WHERE username = ? COLLATE NOCASE')
```

- [ ] **Step 4: `auth_login_user` 写入 role，并新增三个函数**

把 `app/auth.php:44-50` 的 `auth_login_user` 替换为：

```php
function auth_login_user(string $user): void {
    session_regenerate_id(true);               // 防会话固定
    $_SESSION['uid']  = $user;
    $_SESSION['role'] = auth_user_role($user);
    $_SESSION['ua']   = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200);
    $_SESSION['last'] = time();
    $_SESSION['born'] = time();
}

function auth_user_role(string $user): string {
    $q = db()->prepare('SELECT role FROM users WHERE username = ? COLLATE NOCASE LIMIT 1');
    $q->execute([$user]);
    $r = $q->fetchColumn();
    return $r === false ? '' : (string)$r;
}

function auth_is_admin(): bool {
    return ($_SESSION['role'] ?? '') === 'admin';
}

/** 会员页硬闸：未登录跳前台登录页；不校验 role（管理员亦可访问自己的账号页） */
function member_check(): void {
    if (!auth_check()) redirect('login.php');
}
```

- [ ] **Step 5: 运行测试，确认通过**

Run: `php tests/run.php`
Expected: PASS — 新增断言全绿，原有 auth 断言不回归。

- [ ] **Step 6: 提交**

```bash
git add app/auth.php tests/test_auth.php
git commit -m "feat(auth): NOCASE 凭证查询 + 会话写 role + auth_user_role/auth_is_admin/member_check"
```

---

## Task 4: members.php 字段校验函数

**Files:**
- Create: `app/members.php`
- Modify: `app/bootstrap.php:8`（require auth 之后）
- Test: `tests/test_members.php`

- [ ] **Step 1: 创建 `tests/test_members.php`（仅校验部分，失败）**

```php
<?php
putenv('MABSEEK_DB=:memory:');
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/members.php';
db();

// ── 字段校验：正例/反例 ──
check(member_validate_username('alice_01') === true, 'username 合法');
check(member_validate_username('ab') === false, 'username 过短被拒');
check(member_validate_username('has space') === false, 'username 含空格被拒');
check(member_validate_username(str_repeat('a', 21)) === false, 'username 过长被拒');

check(member_validate_password('abc12345') === true, 'password 含字母+数字合法');
check(member_validate_password('abcdefgh') === false, 'password 缺数字被拒');
check(member_validate_password('12345678') === false, 'password 缺字母被拒');
check(member_validate_password('a1b2c3') === false, 'password 过短被拒');
check(member_validate_password('a1' . str_repeat('x', 31)) === false, 'password 过长被拒');

check(member_validate_email('') === true, 'email 可空');
check(member_validate_email('a@b.com') === true, 'email 合法');
check(member_validate_email('not-an-email') === false, 'email 非法被拒');

check(member_validate_phone('') === true, 'phone 可空');
check(member_validate_phone('13800138000') === true, 'phone 合法');
check(member_validate_phone('12345') === false, 'phone 非法被拒');
check(member_validate_phone('23800138000') === false, 'phone 非 1[3-9] 段被拒');

check(member_validate_nickname('小明') === true, 'nickname 合法');
check(member_validate_nickname(str_repeat('字', 31)) === false, 'nickname 过长被拒');
```

- [ ] **Step 2: 运行测试，确认失败**

Run: `php tests/run.php`
Expected: FAIL —「Failed opening required '.../app/members.php'」。

- [ ] **Step 3: 创建 `app/members.php`（先只放校验函数）**

```php
<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

// ── 字段校验（服务端为准，前端仅辅助）──
function member_validate_username(string $v): bool {
    return (bool)preg_match('/^[a-zA-Z0-9_]{3,20}$/', $v);
}
function member_validate_password(string $v): bool {
    $len = mb_strlen($v);
    return $len >= 8 && $len <= 32
        && preg_match('/[A-Za-z]/', $v) === 1
        && preg_match('/\d/', $v) === 1;
}
function member_validate_email(string $v): bool {
    if ($v === '') return true;
    return mb_strlen($v) <= 254 && filter_var($v, FILTER_VALIDATE_EMAIL) !== false;
}
function member_validate_phone(string $v): bool {
    if ($v === '') return true;
    return (bool)preg_match('/^1[3-9]\d{9}$/', $v);
}
function member_validate_nickname(string $v): bool {
    return mb_strlen($v) <= 30;
}
```

- [ ] **Step 4: 在 `app/bootstrap.php` require members.php**

`app/bootstrap.php:8`（`require_once __DIR__ . '/auth.php';` 之后）新增一行：

```php
require_once __DIR__ . '/members.php';
```

- [ ] **Step 5: 运行测试，确认通过**

Run: `php tests/run.php`
Expected: PASS — 校验断言全绿。

- [ ] **Step 6: 提交**

```bash
git add app/members.php app/bootstrap.php tests/test_members.php
git commit -m "feat(members): 字段校验函数 username/password/email/phone/nickname + bootstrap 装配"
```

---

## Task 5: members.php 唯一性 + 注册 + 查询 + 账号更新

**Files:**
- Modify: `app/members.php`（追加函数）
- Test: `tests/test_members.php`（追加）

- [ ] **Step 1: 追加失败测试**

在 `tests/test_members.php` 末尾追加：

```php
// ── 注册 + 唯一性 + 查询 + 更新 ──（用独立用户名，测试末尾清理）
$mid = member_register('m_alice', 'abc12345', 'alice@x.com', '13800138000', '小A');
check($mid > 0, '注册返回新 id');
$row = member_find_by_username('m_alice');
check($row !== null && $row['role'] === 'member', '注册硬编码 role=member');
check($row['status'] === 'active', '注册默认 status=active');
check($row['password_hash'] !== 'abc12345', '落库是哈希而非明文');
check(auth_verify_credentials('m_alice', 'abc12345') === true, '注册后可用该账号登录');
check(auth_verify_credentials('M_ALICE', 'abc12345') === true, '登录用户名大小写不敏感');

// 唯一性（NOCASE）
check(member_username_taken('M_Alice') === true, 'username 唯一 NOCASE 命中');
check(member_email_taken('ALICE@X.COM') === true, 'email 唯一 NOCASE 命中');
check(member_phone_taken('13800138000') === true, 'phone 唯一命中');
check(member_email_taken('') === false, '空 email 不判重');
check(member_email_taken('alice@x.com', $mid) === false, '排除自身后不判重');

// 更新资料
member_update_profile($mid, 'alice2@x.com', '13900139000', '小A2');
$row2 = member_find_by_username('m_alice');
check($row2['email'] === 'alice2@x.com' && $row2['nickname'] === '小A2', '资料更新生效');

// 改密
member_change_password($mid, 'newpass99');
check(auth_verify_credentials('m_alice', 'newpass99') === true, '改密后新密码可登录');
check(auth_verify_credentials('m_alice', 'abc12345') === false, '改密后旧密码失效');

// 注入韧性：含 SQL 元字符原样入库、正常拒绝、无异常
$inj = member_username_taken("x' OR '1'='1");
check($inj === false, '注入串不导致 SQL 错误、按普通字符串处理');

// 清理共享库
db()->prepare('DELETE FROM users WHERE username = ? COLLATE NOCASE')->execute(['m_alice']);
```

- [ ] **Step 2: 运行测试，确认失败**

Run: `php tests/run.php`
Expected: FAIL —「Call to undefined function member_register()」。

- [ ] **Step 3: 在 `app/members.php` 追加函数**

```php
// ── 查询 ──
function member_find_by_username(string $u): ?array {
    $q = db()->prepare('SELECT * FROM users WHERE username = ? COLLATE NOCASE LIMIT 1');
    $q->execute([$u]);
    return $q->fetch() ?: null;
}
function member_get(int $id): ?array {
    $q = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $q->execute([$id]);
    return $q->fetch() ?: null;
}

// ── 唯一性（NOCASE；空值不判重；可排除自身 id）──
function member_username_taken(string $u, ?int $excludeId = null): bool {
    $sql = 'SELECT COUNT(*) FROM users WHERE username = ? COLLATE NOCASE';
    $p = [$u];
    if ($excludeId !== null) { $sql .= ' AND id <> ?'; $p[] = $excludeId; }
    $q = db()->prepare($sql); $q->execute($p);
    return (int)$q->fetchColumn() > 0;
}
function member_email_taken(string $e, ?int $excludeId = null): bool {
    if ($e === '') return false;
    $sql = 'SELECT COUNT(*) FROM users WHERE email = ? COLLATE NOCASE';
    $p = [$e];
    if ($excludeId !== null) { $sql .= ' AND id <> ?'; $p[] = $excludeId; }
    $q = db()->prepare($sql); $q->execute($p);
    return (int)$q->fetchColumn() > 0;
}
function member_phone_taken(string $ph, ?int $excludeId = null): bool {
    if ($ph === '') return false;
    $sql = 'SELECT COUNT(*) FROM users WHERE phone = ?';
    $p = [$ph];
    if ($excludeId !== null) { $sql .= ' AND id <> ?'; $p[] = $excludeId; }
    $q = db()->prepare($sql); $q->execute($p);
    return (int)$q->fetchColumn() > 0;
}

// ── 注册（硬编码 role/status，绝不接受请求传入）──
function member_register(string $username, string $password, string $email, string $phone, string $nickname): int {
    $now = iso_now();
    db()->prepare(
        'INSERT INTO users(username,password_hash,must_change_password,email,phone,nickname,role,status,created_at,updated_at)
         VALUES(?,?,0,?,?,?,\'member\',\'active\',?,?)'
    )->execute([
        $username, password_hash($password, PASSWORD_ARGON2ID),
        $email, $phone, $nickname, $now, $now,
    ]);
    return (int)db()->lastInsertId();
}

// ── 账号自助 ──
function member_update_profile(int $id, string $email, string $phone, string $nickname): void {
    db()->prepare('UPDATE users SET email = ?, phone = ?, nickname = ?, updated_at = ? WHERE id = ?')
        ->execute([$email, $phone, $nickname, iso_now(), $id]);
}
function member_change_password(int $id, string $newPass): void {
    db()->prepare('UPDATE users SET password_hash = ?, must_change_password = 0, updated_at = ? WHERE id = ?')
        ->execute([password_hash($newPass, PASSWORD_ARGON2ID), iso_now(), $id]);
}
```

- [ ] **Step 4: 运行测试，确认通过**

Run: `php tests/run.php`
Expected: PASS。

- [ ] **Step 5: 提交**

```bash
git add app/members.php tests/test_members.php
git commit -m "feat(members): 唯一性检查 + 注册(硬编码 role=member) + 资料/改密自助"
```

---

## Task 6: members.php 后台会员操作 + captcha.php

**Files:**
- Modify: `app/members.php`（追加后台操作）
- Create: `app/captcha.php`
- Modify: `app/bootstrap.php`（require captcha.php）
- Test: `tests/test_members.php`（追加）、`tests/test_captcha.php`

- [ ] **Step 1: 追加后台操作失败测试到 `tests/test_members.php`**

在清理行之前追加（即 `DELETE ... m_alice` 之前）：

```php
// ── 后台会员管理 ──
member_set_status($mid, 'disabled');
check(member_get($mid)['status'] === 'disabled', '停用生效');
member_set_status($mid, 'active');
check(member_get($mid)['status'] === 'active', '启用生效');

$tmp = member_reset_password($mid);
check(strlen($tmp) >= 10, '重置返回临时密码明文');
check(auth_verify_credentials('m_alice', $tmp) === true, '临时密码可登录');
check(member_get($mid)['must_change_password'] === 1, '重置后置位 must_change_password');

$list = member_list();
check(is_array($list), 'member_list 返回数组');
check(!array_key_exists('password_hash', $list[0] ?? ['x'=>1]), 'member_list 不含 password_hash');

$before = count(member_list());
member_delete($mid);
check(member_get($mid) === null, '删除后账号消失');
check(count(member_list()) === $before - 1, '列表减少一条');
```

> 因为 Task 5 末尾会 `DELETE m_alice`，请把该清理行移动到本段之后（本段已 `member_delete($mid)`，清理行可保留作兜底，幂等无害）。

- [ ] **Step 2: 创建 `tests/test_captcha.php`（失败）**

```php
<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/captcha.php';
$_SESSION = [];

captcha_store('AB12');
check(captcha_answer_ok('ab12') === true, '验证码正确(大小写不敏感)');

captcha_store('AB12');
check(captcha_answer_ok('zzzz') === false, '验证码错误');

captcha_store('AB12');
check(captcha_answer_ok('ab12') === true && captcha_answer_ok('ab12') === false, '一次性：用后即失效');

$_SESSION['__captcha'] = ['hash' => hash('sha256', 'ab12'), 'exp' => 1];  // 远古过期
check(captcha_answer_ok('ab12') === false, '过期验证码被拒');
```

- [ ] **Step 3: 运行测试，确认失败**

Run: `php tests/run.php`
Expected: FAIL —「undefined function member_set_status()」「Failed opening .../app/captcha.php」。

- [ ] **Step 4: 在 `app/members.php` 追加后台操作**

```php
// ── 后台会员管理（C）──
function member_set_status(int $id, string $status): void {
    $status = $status === 'disabled' ? 'disabled' : 'active';   // 只允许两值
    db()->prepare("UPDATE users SET status = ?, updated_at = ? WHERE id = ? AND role = 'member'")
        ->execute([$status, iso_now(), $id]);
}
function member_delete(int $id): void {
    db()->prepare("DELETE FROM users WHERE id = ? AND role = 'member'")->execute([$id]);
}
/** 重置密码：CSPRNG 生成临时密码，写哈希 + must_change_password=1，返回明文（仅本次展示一次，不落库明文、不入日志） */
function member_reset_password(int $id): string {
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';   // 去除易混字符
    $tmp = '';
    for ($i = 0; $i < 12; $i++) $tmp .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    db()->prepare("UPDATE users SET password_hash = ?, must_change_password = 1, updated_at = ? WHERE id = ? AND role = 'member'")
        ->execute([password_hash($tmp, PASSWORD_ARGON2ID), iso_now(), $id]);
    return $tmp;
}
/** 仅列 role='member'，不含 password_hash */
function member_list(): array {
    return db()->query(
        "SELECT id, username, email, phone, nickname, status, created_at
         FROM users WHERE role = 'member' ORDER BY id DESC"
    )->fetchAll();
}
```

- [ ] **Step 5: 创建 `app/captcha.php`**

```php
<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

/** 生成验证码答案（去除易混字符），4 位 */
function captcha_generate(): string {
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $s = '';
    for ($i = 0; $i < 4; $i++) $s .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    return $s;
}

/** 答案哈希后存 session（非明文），带过期戳 */
function captcha_store(string $answer): void {
    $_SESSION['__captcha'] = [
        'hash' => hash('sha256', strtolower($answer)),
        'exp'  => time() + CAPTCHA_TTL,
    ];
}

/** 比对：不区分大小写；一次性（无论对错用后即清）；过期即失败 */
function captcha_answer_ok(string $input): bool {
    $c = $_SESSION['__captcha'] ?? null;
    unset($_SESSION['__captcha']);                 // 一次性：先清除，防重放
    if (!is_array($c) || !isset($c['hash'], $c['exp'])) return false;
    if (time() > (int)$c['exp']) return false;     // 过期
    return hash_equals((string)$c['hash'], hash('sha256', strtolower($input)));
}

/** 渲染 PNG（GD）；图片本身不单测 */
function captcha_render_png(string $answer): void {
    $w = 120; $h = 40;
    $im = imagecreatetruecolor($w, $h);
    $bg = imagecolorallocate($im, 245, 245, 250);
    imagefilledrectangle($im, 0, 0, $w, $h, $bg);
    for ($i = 0; $i < 4; $i++) {
        $col = imagecolorallocate($im, random_int(60, 140), random_int(60, 140), random_int(120, 200));
        imagestring($im, 5, 12 + $i * 26, random_int(6, 14), $answer[$i], $col);
    }
    for ($i = 0; $i < 6; $i++) {
        $lc = imagecolorallocate($im, random_int(160, 210), random_int(160, 210), random_int(160, 210));
        imageline($im, random_int(0, $w), random_int(0, $h), random_int(0, $w), random_int(0, $h), $lc);
    }
    header('Content-Type: image/png');
    header('Cache-Control: no-store, must-revalidate');
    imagepng($im);
    imagedestroy($im);
}
```

- [ ] **Step 6: 在 `app/bootstrap.php` require captcha.php**

`app/bootstrap.php` 中 `require_once __DIR__ . '/members.php';` 之后新增：

```php
require_once __DIR__ . '/captcha.php';
```

- [ ] **Step 7: 运行测试，确认通过**

Run: `php tests/run.php`
Expected: PASS — members 后台操作与 captcha 四态断言全绿。

- [ ] **Step 8: 提交**

```bash
git add app/members.php app/captcha.php app/bootstrap.php tests/test_members.php tests/test_captcha.php
git commit -m "feat(members): 后台会员操作(停用/删除/重置密码/列表) + GD 图形验证码(一次性/过期)"
```

---

## Task 7: 后台入口硬闸 auth_is_admin + 拒绝非 admin 登录

**Files:**
- Modify: `public/admin.php:22-48`、`public/admin.php:52`

- [ ] **Step 1: 管理员登录 POST 增加 role 校验**

把 `public/admin.php:35` 的条件：

```php
        } elseif ($u !== '' && auth_verify_credentials($u, $p)) {
```
改为：
```php
        } elseif ($u !== '' && auth_verify_credentials($u, $p) && auth_user_role($u) === 'admin') {
```
> 效果：会员账号即使密码正确也无法从后台登录，落入统一模糊错误分支并记失败，不泄露账号是否存在。

- [ ] **Step 2: 入口硬闸叠加 role 判断**

把 `public/admin.php:23` 的：

```php
if (!auth_check()) {
```
改为：
```php
if (!auth_check() || !auth_is_admin()) {
```
> 效果：持 `role='member'` 会话访问 `admin.php` 会被引导到管理员登录页，不进入后台。

- [ ] **Step 3: 模块白名单加入 members**

把 `public/admin.php:52` 的白名单：

```php
$allowed = ['dashboard','news','forum_posts','forum_hot','team','partners','cards','snippets','password'];
```
改为：
```php
$allowed = ['dashboard','news','forum_posts','forum_hot','team','partners','cards','snippets','members','password'];
```

- [ ] **Step 4: 手动验证 + 回归测试**

Run: `php tests/run.php`
Expected: PASS（不回归）。

Run: `php bin/seed.php && php -S localhost:8778 -t public`（另开终端）
预期：管理员 admin 仍能登录后台；用未来注册的会员账号无法登录 `admin.php`。

- [ ] **Step 5: 提交**

```bash
git add public/admin.php
git commit -m "feat(auth): admin.php 硬闸 auth_is_admin + 拒绝非 admin 登录 + members 模块白名单"
```

---

## Task 8: 前台注册页 public/register.php

**Files:**
- Create: `public/register.php`

- [ ] **Step 1: 创建 `public/register.php`**

```php
<?php
require __DIR__ . '/../app/bootstrap.php';
header('Cache-Control: no-store, must-revalidate');

// 已登录直接去账号页
if (auth_check()) redirect('account.php');

$errors = [];
$in = ['username'=>'', 'email'=>'', 'phone'=>'', 'nickname'=>''];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify_or_die();
    // 蜜罐：正常用户留空，非空即机器人 → 静默拒绝
    if (trim((string)($_POST['website'] ?? '')) !== '') { redirect('login.php'); }

    $in['username'] = trim((string)($_POST['username'] ?? ''));
    $pass           = (string)($_POST['password'] ?? '');
    $in['email']    = trim((string)($_POST['email'] ?? ''));
    $in['phone']    = trim((string)($_POST['phone'] ?? ''));
    $in['nickname'] = trim((string)($_POST['nickname'] ?? ''));

    if (!captcha_answer_ok((string)($_POST['captcha'] ?? ''))) $errors['captcha'] = '验证码错误或已过期';
    if (!member_validate_username($in['username']))           $errors['username'] = '用户名需 3–20 位字母/数字/下划线';
    if (!member_validate_password($pass))                     $errors['password'] = '密码需 8–32 位且含字母与数字';
    if (!member_validate_email($in['email']))                 $errors['email']    = '邮箱格式不正确';
    if (!member_validate_phone($in['phone']))                 $errors['phone']    = '手机号格式不正确';
    if (!member_validate_nickname($in['nickname']))           $errors['nickname'] = '昵称最多 30 字';

    if (!$errors) {
        if (member_username_taken($in['username'])) $errors['username'] = '该用户名已被注册';
        if (member_email_taken($in['email']))       $errors['email']    = '该邮箱已被注册';
        if (member_phone_taken($in['phone']))       $errors['phone']    = '该手机号已被注册';
    }

    if (!$errors) {
        member_register($in['username'], $pass, $in['email'], $in['phone'], $in['nickname']);
        auth_login_user($in['username']);            // 注册成功自动登录
        $_SESSION['nick'] = $in['nickname'] !== '' ? $in['nickname'] : $in['username'];
        audit('member_register', 'user', $in['username']);
        redirect('account.php');
    }
}
$active = ''; $navOnDark = false;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>注册 · MabSeek</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/partials/nav.php'; ?>
<main class="container" style="max-width:460px;margin:48px auto">
  <h1 style="margin-bottom:20px">注册会员</h1>
  <form method="post" action="register.php" autocomplete="off">
    <?= csrf_field() ?>
    <div style="position:absolute;left:-9999px" aria-hidden="true">
      <label>请勿填写<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
    </div>
    <div class="field">
      <label>用户名</label>
      <input class="input" type="text" name="username" value="<?= e($in['username']) ?>" required>
      <?php if (isset($errors['username'])): ?><small style="color:#c0392b"><?= e($errors['username']) ?></small><?php endif; ?>
    </div>
    <div class="field">
      <label>密码（8–32 位，含字母与数字）</label>
      <input class="input" type="password" name="password" required autocomplete="new-password">
      <?php if (isset($errors['password'])): ?><small style="color:#c0392b"><?= e($errors['password']) ?></small><?php endif; ?>
    </div>
    <div class="field">
      <label>邮箱（可选）</label>
      <input class="input" type="email" name="email" value="<?= e($in['email']) ?>">
      <?php if (isset($errors['email'])): ?><small style="color:#c0392b"><?= e($errors['email']) ?></small><?php endif; ?>
    </div>
    <div class="field">
      <label>手机号（可选）</label>
      <input class="input" type="text" name="phone" value="<?= e($in['phone']) ?>">
      <?php if (isset($errors['phone'])): ?><small style="color:#c0392b"><?= e($errors['phone']) ?></small><?php endif; ?>
    </div>
    <div class="field">
      <label>昵称（可选）</label>
      <input class="input" type="text" name="nickname" value="<?= e($in['nickname']) ?>">
      <?php if (isset($errors['nickname'])): ?><small style="color:#c0392b"><?= e($errors['nickname']) ?></small><?php endif; ?>
    </div>
    <div class="field">
      <label>验证码</label>
      <div style="display:flex;gap:10px;align-items:center">
        <input class="input" type="text" name="captcha" required style="flex:1">
        <img src="captcha.php" alt="验证码" onclick="this.src='captcha.php?'+Date.now()" style="cursor:pointer;height:40px" title="点击刷新">
      </div>
      <?php if (isset($errors['captcha'])): ?><small style="color:#c0392b"><?= e($errors['captcha']) ?></small><?php endif; ?>
    </div>
    <button class="btn btn-purple" type="submit">注册并登录</button>
    <p style="margin-top:14px">已有账号？<a href="login.php">去登录</a></p>
  </form>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
```

- [ ] **Step 2: 手动验证**

Run: `php bin/seed.php && php -S localhost:8778 -t public`
预期：访问 `http://localhost:8778/register.php` 显示表单；提交合法数据→自动登录并跳 `account.php`；重复用户名/错误验证码→逐字段报错。

- [ ] **Step 3: 提交**

```bash
git add public/register.php
git commit -m "feat(members): 前台注册页(校验+唯一+验证码+蜜罐)，成功自动登录"
```

---

## Task 9: 前台登录页 public/login.php + 登出 public/logout.php

**Files:**
- Create: `public/login.php`
- Create: `public/logout.php`

- [ ] **Step 1: 创建 `public/login.php`**

```php
<?php
require __DIR__ . '/../app/bootstrap.php';
header('Cache-Control: no-store, must-revalidate');

if (auth_check()) redirect('account.php');

$error = null;
$oldUser = '';
$needCaptcha = !empty($_SESSION['__login_fail']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify_or_die();
    $u = trim((string)($_POST['username'] ?? ''));
    if (strlen($u) > 190) $u = substr($u, 0, 190);
    $p = (string)($_POST['password'] ?? '');
    $oldUser = $u;
    $ip = client_ip();

    $captchaOk = !$needCaptcha || captcha_answer_ok((string)($_POST['captcha'] ?? ''));

    if (auth_is_locked($ip, $u)) {
        $error = '尝试过于频繁，请 15 分钟后再试。';
    } elseif (!$captchaOk) {
        $error = '验证码错误或已过期';
    } elseif ($u !== '' && auth_verify_credentials($u, $p)) {
        $row = member_find_by_username($u);
        if ($row && $row['status'] === 'disabled') {
            auth_record_attempt($ip, $u, false);
            $error = '该账号已被停用，请联系管理员。';
        } else {
            auth_record_attempt($ip, $u, true);
            unset($_SESSION['__login_fail']);
            auth_login_user($row['username']);        // 存库中规范用户名
            $_SESSION['nick'] = ($row['nickname'] ?? '') !== '' ? $row['nickname'] : $row['username'];
            audit('member_login');
            redirect('account.php');
        }
    } else {
        auth_record_attempt($ip, $u, false);
        $_SESSION['__login_fail'] = 1;                // 首败后要求验证码
        $needCaptcha = true;
        $error = '用户名或密码错误';
    }
}
$active = ''; $navOnDark = false;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>登录 · MabSeek</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/partials/nav.php'; ?>
<main class="container" style="max-width:420px;margin:48px auto">
  <h1 style="margin-bottom:20px">会员登录</h1>
  <?php if ($error): ?><p style="color:#c0392b"><?= e($error) ?></p><?php endif; ?>
  <form method="post" action="login.php">
    <?= csrf_field() ?>
    <div class="field">
      <label>用户名</label>
      <input class="input" type="text" name="username" value="<?= e($oldUser) ?>" required>
    </div>
    <div class="field">
      <label>密码</label>
      <input class="input" type="password" name="password" required autocomplete="current-password">
    </div>
    <?php if ($needCaptcha): ?>
    <div class="field">
      <label>验证码</label>
      <div style="display:flex;gap:10px;align-items:center">
        <input class="input" type="text" name="captcha" required style="flex:1">
        <img src="captcha.php" alt="验证码" onclick="this.src='captcha.php?'+Date.now()" style="cursor:pointer;height:40px" title="点击刷新">
      </div>
    </div>
    <?php endif; ?>
    <button class="btn btn-purple" type="submit">登录</button>
    <p style="margin-top:14px">还没有账号？<a href="register.php">去注册</a></p>
  </form>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
```

- [ ] **Step 2: 创建 `public/logout.php`（仅 POST + CSRF）**

```php
<?php
require __DIR__ . '/../app/bootstrap.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}
csrf_verify_or_die();
if (auth_check()) audit('member_logout');
auth_logout();
redirect('login.php');
```

- [ ] **Step 3: 手动验证**

Run: `php bin/seed.php && php -S localhost:8778 -t public`
预期：注册一个会员→登出→在 `login.php` 用其账号登录成功；故意输错一次→出现验证码；停用账号（后台做完 Task 12 后）→提示已停用。

- [ ] **Step 4: 提交**

```bash
git add public/login.php public/logout.php
git commit -m "feat(members): 前台会员登录(渐进验证码/停用拒登) + POST 登出端点"
```

---

## Task 10: 账号中心 public/account.php

**Files:**
- Create: `public/account.php`

- [ ] **Step 1: 创建 `public/account.php`**

```php
<?php
require __DIR__ . '/../app/bootstrap.php';
header('Cache-Control: no-store, must-revalidate');
member_check();                                   // 未登录跳 login.php

$me = member_find_by_username((string)($_SESSION['uid'] ?? ''));
if (!$me) { auth_logout(); redirect('login.php'); }

$msg = null; $err = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify_or_die();
    $do = (string)($_POST['do'] ?? '');

    if ($do === 'profile') {
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $nick  = trim((string)($_POST['nickname'] ?? ''));
        if (!member_validate_email($email))         $err = '邮箱格式不正确';
        elseif (!member_validate_phone($phone))     $err = '手机号格式不正确';
        elseif (!member_validate_nickname($nick))   $err = '昵称最多 30 字';
        elseif (member_email_taken($email, (int)$me['id'])) $err = '该邮箱已被占用';
        elseif (member_phone_taken($phone, (int)$me['id'])) $err = '该手机号已被占用';
        else {
            member_update_profile((int)$me['id'], $email, $phone, $nick);
            $_SESSION['nick'] = $nick !== '' ? $nick : $me['username'];
            audit('member_update_profile');
            $msg = '资料已更新';
            $me = member_get((int)$me['id']);
        }
    } elseif ($do === 'password') {
        $cur  = (string)($_POST['current'] ?? '');
        $new  = (string)($_POST['new'] ?? '');
        $new2 = (string)($_POST['confirm'] ?? '');
        if (!auth_verify_credentials($me['username'], $cur)) $err = '当前密码不正确';
        elseif (!member_validate_password($new))            $err = '新密码需 8–32 位且含字母与数字';
        elseif ($new !== $new2)                             $err = '两次输入的新密码不一致';
        elseif ($new === $cur)                              $err = '新密码不能与当前密码相同';
        else {
            member_change_password((int)$me['id'], $new);
            session_regenerate_id(true);           // 改密后刷新会话 ID
            audit('member_change_password');
            $msg = '密码已更新';
        }
    }
}
$active = ''; $navOnDark = false;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>账号中心 · MabSeek</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/partials/nav.php'; ?>
<main class="container" style="max-width:560px;margin:48px auto">
  <div style="display:flex;justify-content:space-between;align-items:center">
    <h1>账号中心</h1>
    <form method="post" action="logout.php"><?= csrf_field() ?><button class="btn btn-ghost" type="submit">退出登录</button></form>
  </div>
  <?php if ($msg): ?><p style="color:#12b98c"><?= e($msg) ?></p><?php endif; ?>
  <?php if ($err): ?><p style="color:#c0392b"><?= e($err) ?></p><?php endif; ?>

  <section style="margin-top:24px">
    <h3>基本资料</h3>
    <p style="color:#888">用户名：<?= e($me['username']) ?>（不可修改）</p>
    <form method="post" action="account.php">
      <?= csrf_field() ?>
      <input type="hidden" name="do" value="profile">
      <div class="field"><label>邮箱</label><input class="input" type="email" name="email" value="<?= e($me['email']) ?>"></div>
      <div class="field"><label>手机号</label><input class="input" type="text" name="phone" value="<?= e($me['phone']) ?>"></div>
      <div class="field"><label>昵称</label><input class="input" type="text" name="nickname" value="<?= e($me['nickname']) ?>"></div>
      <button class="btn btn-purple" type="submit">保存资料</button>
    </form>
  </section>

  <section style="margin-top:32px">
    <h3>修改密码</h3>
    <form method="post" action="account.php">
      <?= csrf_field() ?>
      <input type="hidden" name="do" value="password">
      <div class="field"><label>当前密码</label><input class="input" type="password" name="current" required autocomplete="current-password"></div>
      <div class="field"><label>新密码（8–32 位，含字母与数字）</label><input class="input" type="password" name="new" required autocomplete="new-password"></div>
      <div class="field"><label>确认新密码</label><input class="input" type="password" name="confirm" required autocomplete="new-password"></div>
      <button class="btn btn-purple" type="submit">修改密码</button>
    </form>
  </section>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
```

- [ ] **Step 2: 手动验证**

Run: `php -S localhost:8778 -t public`
预期：登录后访问 `account.php`；改昵称/邮箱→提示已更新且导航显示新昵称；改密后需用新密码；未登录访问→跳 `login.php`。

- [ ] **Step 3: 提交**

```bash
git add public/account.php
git commit -m "feat(members): 账号中心(资料/改密自助，唯一性排除自身，改密刷新会话)"
```

---

## Task 11: 验证码图片端点 public/captcha.php

**Files:**
- Create: `public/captcha.php`

- [ ] **Step 1: 创建 `public/captcha.php`**

```php
<?php
require __DIR__ . '/../app/bootstrap.php';
$answer = captcha_generate();
captcha_store($answer);
captcha_render_png($answer);
```

- [ ] **Step 2: 手动验证**

Run: `php -S localhost:8778 -t public`
预期：`http://localhost:8778/captcha.php` 返回一张 PNG 干扰图；`register.php`/`login.php` 中图片正常显示、点击刷新。

- [ ] **Step 3: 提交**

```bash
git add public/captcha.php
git commit -m "feat(members): 验证码图片端点 captcha.php"
```

---

## Task 12: 后台会员管理 app/admin/members.php + 侧栏菜单

**Files:**
- Create: `app/admin/members.php`
- Modify: `app/admin/shell.php:4-14`（菜单）

- [ ] **Step 1: 创建 `app/admin/members.php`**

> 由 `admin.php` 在外壳输出后 include（勿加 `declare`/`require`）。POST 动作先丢弃外壳缓冲再 PRG 重定向，风格参照 `app/admin/password.php`。

```php
<?php
// 后台会员管理（C）：仅操作 role='member'；不显示 password_hash；无 role 修改入口。
// 动作均 POST + CSRF，且已处于 admin.php 的 auth_is_admin() 硬闸之内。

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    while (ob_get_level() > 0) { ob_end_clean(); }
    csrf_verify_or_die();
    $act = (string)($_POST['act'] ?? '');
    $id  = (int)($_POST['id'] ?? 0);
    $target = $id > 0 ? member_get($id) : null;

    if (!$target || ($target['role'] ?? '') !== 'member') {
        flash_set('error', '目标会员不存在');
        redirect('admin.php?m=members');
    }

    if ($act === 'disable') {
        member_set_status($id, 'disabled');
        audit('member_disable', 'user', (string)$id);
        flash_set('ok', '已停用会员：' . $target['username']);
    } elseif ($act === 'enable') {
        member_set_status($id, 'active');
        audit('member_enable', 'user', (string)$id);
        flash_set('ok', '已启用会员：' . $target['username']);
    } elseif ($act === 'delete') {
        member_delete($id);
        audit('member_delete', 'user', (string)$id);
        flash_set('ok', '已删除会员：' . $target['username']);
    } elseif ($act === 'reset') {
        $tmp = member_reset_password($id);
        audit('member_reset_password', 'user', (string)$id);
        // 临时密码仅本次展示一次（flash 存 session，随下次渲染即清；不落库明文、不入日志）
        flash_set('warn', "已重置「{$target['username']}」的密码，临时密码：{$tmp}（请立即转交并让其登录后修改）");
    } else {
        flash_set('error', '未知操作');
    }
    redirect('admin.php?m=members');
}

$members = member_list();
?>
<div class="card">
  <h3 style="margin-bottom:16px">会员管理（共 <?= count($members) ?> 人）</h3>
  <table class="admin-table">
    <thead><tr><th>ID</th><th>用户名</th><th>昵称</th><th>邮箱</th><th>手机号</th><th>状态</th><th>注册时间</th><th>操作</th></tr></thead>
    <tbody>
<?php foreach ($members as $m): ?>
      <tr>
        <td><?= (int)$m['id'] ?></td>
        <td><?= e($m['username']) ?></td>
        <td><?= e($m['nickname']) ?></td>
        <td><?= e($m['email']) ?></td>
        <td><?= e($m['phone']) ?></td>
        <td><?= $m['status'] === 'disabled' ? '<span style="color:#c0392b">已停用</span>' : '正常' ?></td>
        <td><?= e($m['created_at']) ?></td>
        <td style="display:flex;gap:6px;flex-wrap:wrap">
<?php if ($m['status'] === 'disabled'): ?>
          <form method="post" action="admin.php?m=members"><?= csrf_field() ?><input type="hidden" name="act" value="enable"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button class="abtn" type="submit">启用</button></form>
<?php else: ?>
          <form method="post" action="admin.php?m=members"><?= csrf_field() ?><input type="hidden" name="act" value="disable"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button class="abtn" type="submit">停用</button></form>
<?php endif; ?>
          <form method="post" action="admin.php?m=members" onsubmit="return confirm('确认重置该会员密码？')"><?= csrf_field() ?><input type="hidden" name="act" value="reset"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button class="abtn" type="submit">重置密码</button></form>
          <form method="post" action="admin.php?m=members" onsubmit="return confirm('确认删除该会员？不可恢复')"><?= csrf_field() ?><input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button class="abtn" type="submit" style="color:#c0392b">删除</button></form>
        </td>
      </tr>
<?php endforeach; ?>
<?php if (!$members): ?>
      <tr><td colspan="8" style="color:#888">暂无会员</td></tr>
<?php endif; ?>
    </tbody>
  </table>
</div>
```

- [ ] **Step 2: 侧栏菜单加入「会员管理」**

把 `app/admin/shell.php:4-14` 的 `$adminMenu` 数组中 `'snippets' => '文案片段',` 之后插入一行：

```php
    'members'     => '会员管理',
```

- [ ] **Step 3: 手动验证**

Run: `php bin/seed.php && php -S localhost:8778 -t public`
预期：管理员登录后侧栏出现「会员管理」；先在 `register.php` 注册一个会员，回后台列表可见；停用后该会员在 `login.php` 无法登录；重置密码显示一次性临时密码，用其登录后被强制改密逻辑拦截（会员改密走 account.php）；删除后列表消失。列表**不含**密码列。

- [ ] **Step 4: 提交**

```bash
git add app/admin/members.php app/admin/shell.php
git commit -m "feat(members): 后台会员管理页(停用/启用/重置/删除, 审计, 一次性临时密码) + 侧栏菜单"
```

---

## Task 13: 导航入口 public/partials/nav.php

**Files:**
- Modify: `public/partials/nav.php:22`

- [ ] **Step 1: 在「联系我们」前插入会员入口**

把 `public/partials/nav.php:22`：

```php
    <div class="nav-actions"><a href="<?= e($contactHref) ?>" class="btn btn-green">联系我们</a></div>
```
替换为：
```php
    <div class="nav-actions">
<?php if (!empty($_SESSION['uid'])): ?>
      <a href="account.php" class="btn btn-ghost">👤 <?= e($_SESSION['nick'] ?? $_SESSION['uid']) ?></a>
<?php else: ?>
      <a href="login.php" class="btn btn-ghost">登录 / 注册</a>
<?php endif; ?>
      <a href="<?= e($contactHref) ?>" class="btn btn-green">联系我们</a>
    </div>
```
> `.btn-ghost` 已存在于 `style.css:109`；`.nav-actions` 为 flex + gap，无需新增 CSS。移动端 `style.css:273` 已统一隐藏 `.nav-actions .btn`，会员入口随之隐藏，行为一致。

- [ ] **Step 2: 手动验证**

Run: `php -S localhost:8778 -t public`
预期：未登录时各前台页导航显示「登录 / 注册」；登录后显示「👤 昵称」并链到 `account.php`；按钮不错位、不换行。

- [ ] **Step 3: 提交**

```bash
git add public/partials/nav.php
git commit -m "feat(members): 导航栏「联系我们」前插入会员入口(登录态显示昵称)"
```

---

## Task 14: 部署脚本加 gd + 全量收尾验证

**Files:**
- Modify: `deploy/deploy-mabseek.sh:96,99`

- [ ] **Step 1: 扩展检查加入 gd**

把 `deploy/deploy-mabseek.sh:96`：

```bash
for ext in pdo_sqlite sodium dom; do
```
改为：
```bash
for ext in pdo_sqlite sodium dom gd; do
```

把 `deploy/deploy-mabseek.sh:99` 的 die 提示补上 `php-gd`：

```bash
[ -z "$MISSING" ] || die "缺少 PHP 扩展:$MISSING （apt-get install -y php-sqlite3 php-sodium php-xml php-gd && systemctl restart php*-fpm）"
```

- [ ] **Step 2: seed.php 显式写 role/status（迁移回填的双保险）**

把 `bin/seed.php:12-13` 的 INSERT：

```php
    $pdo->prepare('INSERT INTO users(username,password_hash,must_change_password,created_at,updated_at) VALUES(?,?,1,?,?)')
        ->execute([SEED_ADMIN_USER, password_hash(SEED_ADMIN_PASS, PASSWORD_ARGON2ID), $now, $now]);
```
改为：
```php
    $pdo->prepare('INSERT INTO users(username,password_hash,must_change_password,role,status,created_at,updated_at) VALUES(?,?,1,?,?,?,?)')
        ->execute([SEED_ADMIN_USER, password_hash(SEED_ADMIN_PASS, PASSWORD_ARGON2ID), 'admin', 'active', $now, $now]);
```

- [ ] **Step 3: 全量测试**

Run: `php tests/run.php`
Expected: PASS — 全部通过（原 73 项 + 新增 members/captcha/db/auth 断言），`0 failed`。

- [ ] **Step 4: 端到端手动验证**

Run: `php bin/seed.php && php -S localhost:8778 -t public`
预期完整回归：
1. 管理员登录后台正常；会员账号无法登录后台。
2. 注册→自动登录→账号中心改资料/改密→登出→重新登录。
3. 首次登录失败后出现验证码；连续失败触发锁定提示。
4. 后台会员管理停用/启用/重置/删除均生效并写审计。
5. 导航入口按登录态正确切换。

- [ ] **Step 5: 提交**

```bash
git add deploy/deploy-mabseek.sh bin/seed.php
git commit -m "chore(deploy): 依赖检查加 php-gd（验证码）+ seed 管理员显式 role/status"
```

---

## Self-Review（对照 spec 核查）

**1. Spec 覆盖:**
- §2.1 账号隔离/防提权 → Task 3（auth_is_admin/member_check）、Task 5（注册硬编码 role）、Task 7（后台硬闸+拒绝非 admin+无 role 入口）、Task 12（管理页不改 role）。✅
- §2.2 会话（登录/改密 regenerate、role 入会话）→ Task 3、Task 10。✅
- §2.3 防刷（双阈值锁定复用 + 渐进验证码 + 蜜罐）→ Task 9、Task 8、Task 6。✅
- §3 数据模型（列/NOCASE/部分唯一索引/校验规则）→ Task 1、Task 4。✅
- §4 迁移与部署（幂等增量、role 回填陷阱、seed 双保险、deploy gd）→ Task 1、Task 14。✅
- §5 验证码（哈希存 session、一次性、10 分钟过期、比对与渲染分离、蜜罐、渐进）→ Task 6、Task 8/9/11。✅
- §6 前台页面与端点（register/login/account/logout/captcha + 鉴权辅助 + 导航入口）→ Task 8–11、Task 13、Task 3。✅
- §7 后台会员管理 → Task 12。✅
- §8 安全清单（PDO 预处理、e() 转义、CSRF、越权、爆破、会话、Argon2id、恒定时间、模糊错误）→ 贯穿 Task 3/5/7/8/9/10/12。✅
- §9 测试策略（校验/唯一性/注册/验证码四态/登录/越权/会员管理/注入韧性）→ Task 1/3/4/5/6 的测试步骤。✅
- §10 文件清单 → 与本计划文件结构一一对应。✅

**2. 占位符扫描:** 无 TBD/TODO；每个代码步骤均含完整代码。✅

**3. 类型一致性:** `auth_user_role`/`auth_is_admin`/`member_check`/`member_register`/`member_find_by_username`/`member_get`/`member_username_taken`/`member_email_taken`/`member_phone_taken`/`member_update_profile`/`member_change_password`/`member_set_status`/`member_delete`/`member_reset_password`/`member_list`/`captcha_generate`/`captcha_store`/`captcha_answer_ok`/`captcha_render_png` 在定义与调用处签名一致。✅
