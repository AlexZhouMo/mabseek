# MabSeek 后台内容管理系统（PHP + SQLite）Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 把 MabSeek 6 个静态 HTML 页面升级为 PHP 动态渲染 + SQLite 存储 + 后台管理站点，前台逐字保留现有布局/风格，`admin.php` 可编辑所有文案与集合，现有文案自动入库。

**Architecture:** 方案 A「极简自研 MVC-lite + web 根隔离」。`public/` 为唯一对外可达的 Nginx web 根；`app/`（引导/配置/DB/认证/CSRF/仓库/后台模块）、`data/`（SQLite）、`bin/`（seed）在 web 根之外，物理不可请求。每个公开 `.php` 顶部 `require '../app/bootstrap.php'`，从 SQLite 读内容填入逐字保留的 markup。零第三方框架、零 Composer。

**Tech Stack:** PHP 8.5、SQLite（`pdo_sqlite`）、Nginx + PHP-FPM、Argon2id 密码哈希、原生 HTML/CSS/JS（复用现有 `assets/`）。

**权威规格：** [../specs/2026-08-29-mabseek-p5-backend-cms-design.md](../specs/2026-08-29-mabseek-p5-backend-cms-design.md)

**关键约束（必须遵守）：**
- **不改变布局与风格**：markup 逐字保留，只把硬编码文字替换为从 DB 读取。前台渲染结果须与现有 `.html` 逐字节一致（除 URL 后缀）。
- **不杜撰**：seed 内容 = 现有 HTML 原文，禁止新增/改写未经核实信息。
- **安全按公网生产最高基线**：见 §Task 5/6/25/27。
- **密码 `mabseek2026` 仅作初始种子**，Argon2id 哈希入库，永不存明文。

---

## 文件结构总览

实现后目录：

```
mabseek/
├── public/                      ← Nginx root
│   ├── index.php technology.php agent.php education.php forum.php about.php
│   ├── admin.php                ← 后台唯一入口
│   ├── partials/nav.php footer.php
│   └── assets/                  ← 由现有 assets/ 迁入（含 images/uploads/）
├── app/
│   ├── config.php bootstrap.php db.php helpers.php csrf.php auth.php
│   ├── repositories/ (Collection.php + Snippets.php)
│   └── admin/ (login.php shell.php dashboard.php + 各模块.php + upload.php)
├── data/                        ← mabseek.sqlite（运行时生成，.gitignore）
├── bin/seed.php
├── tests/run.php                ← 无框架 PHP 断言运行器
├── deploy/nginx.conf.sample
└── docs/…
```

**说明：** 现有 6 个 `.html` 保留在 `mabseek/` 根下不动，作为转换的「黄金参照」直到验证通过；`assets/` 通过 `git mv` 迁入 `public/assets/`（保持相对路径 `assets/...` 不变）。转换完成、验证一致后，最后一个任务再删除根下旧 `.html`。

---

## Task 1: 项目骨架 + 配置常量

**Files:**
- Create: `mabseek/app/config.php`
- Create: `mabseek/.gitignore`（若不存在则创建，存在则追加）
- Create: 空目录占位 `mabseek/data/.gitkeep`、`mabseek/public/assets/images/uploads/.gitkeep`

- [ ] **Step 1: 创建目录并迁移 assets**

```bash
cd mabseek
mkdir -p public/partials app/repositories app/admin data bin tests deploy
git mv assets public/assets
mkdir -p public/assets/images/uploads
touch data/.gitkeep public/assets/images/uploads/.gitkeep
```

Expected: `public/assets/css/style.css` 等存在；`git status` 显示 assets 移动。

- [ ] **Step 2: 写 config.php**

```php
<?php
declare(strict_types=1);

// ── 路径 ──
const APP_ROOT   = __DIR__;                       // .../mabseek/app
const BASE_ROOT  = __DIR__ . '/..';               // .../mabseek
const DATA_DIR   = BASE_ROOT . '/data';
const DB_PATH    = DATA_DIR . '/mabseek.sqlite';
const UPLOAD_DIR = BASE_ROOT . '/public/assets/images/uploads';
const UPLOAD_URL = 'assets/images/uploads';       // 前台相对 URL 前缀

// ── 会话 / 风控 ──
const SESSION_NAME       = 'mabseek_sid';
const IDLE_TIMEOUT       = 1800;                  // 空闲 30 分钟
const ABSOLUTE_TIMEOUT   = 28800;                 // 绝对 8 小时
const LOGIN_MAX_FAILS    = 5;                      // 连续失败上限
const LOGIN_LOCK_SECONDS = 900;                    // 锁定 15 分钟
const LOGIN_FAIL_WINDOW  = 900;                    // 统计窗口 15 分钟

// ── 上传 ──
const UPLOAD_MAX_BYTES = 2 * 1024 * 1024;         // 2MB
const UPLOAD_ALLOWED   = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

// ── 初始管理员 ──
const SEED_ADMIN_USER = 'admin';
const SEED_ADMIN_PASS = 'mabseek2026';            // 仅 seed 时哈希入库，绝不落库明文

// ── 环境（生产设 false）──
const APP_DEBUG = false;
```

- [ ] **Step 3: 写 .gitignore（追加）**

```gitignore
data/*.sqlite
data/*.sqlite-*
public/assets/images/uploads/*
!public/assets/images/uploads/.gitkeep
!data/.gitkeep
```

- [ ] **Step 4: 校验 PHP 环境**

Run: `php -v && php -m | grep -E 'pdo_sqlite|sqlite3'`
Expected: PHP 8.x；输出含 `pdo_sqlite` 与 `sqlite3`。

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "feat(mabseek): P5 scaffolding — dirs, config, move assets into public/"
```

---

## Task 2: 数据库连接 + 建表（migrate）

**Files:**
- Create: `mabseek/app/db.php`
- Test: `mabseek/tests/run.php`（新建断言运行器）+ `mabseek/tests/test_db.php`

- [ ] **Step 1: 写无框架测试运行器 tests/run.php**

```php
<?php
declare(strict_types=1);
// 用法: php tests/run.php  —— 依次 require 所有 test_*.php，统计断言
error_reporting(E_ALL);
ini_set('display_errors', '1');
$GLOBALS['__pass'] = 0; $GLOBALS['__fail'] = 0;
function check(bool $cond, string $msg): void {
    if ($cond) { $GLOBALS['__pass']++; echo "  ✓ $msg\n"; }
    else       { $GLOBALS['__fail']++; echo "  ✗ FAIL: $msg\n"; }
}
foreach (glob(__DIR__ . '/test_*.php') as $f) {
    echo "\n# " . basename($f) . "\n";
    require $f;
}
echo "\n==== {$GLOBALS['__pass']} passed, {$GLOBALS['__fail']} failed ====\n";
exit($GLOBALS['__fail'] > 0 ? 1 : 0);
```

- [ ] **Step 2: 写 test_db.php（失败先行）**

```php
<?php
// 用临时库验证 schema 创建
putenv('MABSEEK_DB=:memory:');
require __DIR__ . '/../app/db.php';
$pdo = db();
$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")
              ->fetchAll(PDO::FETCH_COLUMN);
foreach (['audit_log','content_cards','forum_hot','forum_posts','login_attempts','news','partners','snippets','team_members','users'] as $t) {
    check(in_array($t, $tables, true), "table exists: $t");
}
// PDO 严格模式
check($pdo->getAttribute(PDO::ATTR_ERRMODE) === PDO::ERRMODE_EXCEPTION, 'ERRMODE_EXCEPTION set');
```

- [ ] **Step 3: 运行，确认失败**

Run: `php tests/run.php`
Expected: FAIL —「db() not defined」或找不到 `app/db.php` 内容。

- [ ] **Step 4: 写 db.php**

```php
<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $envDb = getenv('MABSEEK_DB');                 // 测试可用 :memory:
    $dsnPath = $envDb ?: DB_PATH;
    if ($dsnPath !== ':memory:' && !is_dir(dirname($dsnPath))) {
        mkdir(dirname($dsnPath), 0750, true);
    }
    $pdo = new PDO('sqlite:' . $dsnPath, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA foreign_keys = ON');
    migrate($pdo);
    return $pdo;
}

function migrate(PDO $pdo): void {
    $pdo->exec(<<<SQL
    CREATE TABLE IF NOT EXISTS news (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      date_day TEXT NOT NULL, date_ym TEXT NOT NULL,
      category TEXT NOT NULL, title TEXT NOT NULL, summary TEXT NOT NULL,
      image TEXT, sort INTEGER DEFAULT 0, published INTEGER DEFAULT 1,
      created_at TEXT NOT NULL, updated_at TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS forum_posts (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      category TEXT NOT NULL, cover_type TEXT NOT NULL, cover_ref TEXT NOT NULL,
      cover_variant TEXT DEFAULT '', toptag TEXT DEFAULT '',
      title TEXT NOT NULL, tags TEXT DEFAULT '',
      author_name TEXT NOT NULL, author_avatar_char TEXT NOT NULL,
      author_avatar_style TEXT DEFAULT '', likes TEXT DEFAULT '',
      sort INTEGER DEFAULT 0, published INTEGER DEFAULT 1,
      created_at TEXT NOT NULL, updated_at TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS forum_hot (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      list TEXT NOT NULL, rank INTEGER NOT NULL, title TEXT NOT NULL,
      category TEXT NOT NULL, heat TEXT NOT NULL,
      sort INTEGER DEFAULT 0, published INTEGER DEFAULT 1,
      created_at TEXT NOT NULL, updated_at TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS team_members (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      name TEXT NOT NULL, affiliation TEXT NOT NULL, direction TEXT NOT NULL,
      role_label TEXT NOT NULL, role_type TEXT NOT NULL,
      avatar_char TEXT NOT NULL, avatar_variant TEXT DEFAULT '',
      sort INTEGER DEFAULT 0, published INTEGER DEFAULT 1,
      created_at TEXT NOT NULL, updated_at TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS partners (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      name TEXT NOT NULL, mark TEXT NOT NULL, sub TEXT DEFAULT '',
      logo_image TEXT, demo TEXT DEFAULT '',
      sort INTEGER DEFAULT 0, published INTEGER DEFAULT 1,
      created_at TEXT NOT NULL, updated_at TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS content_cards (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      grp TEXT NOT NULL, icon TEXT DEFAULT '', title TEXT NOT NULL,
      body TEXT DEFAULT '', extra TEXT DEFAULT '',
      sort INTEGER DEFAULT 0, published INTEGER DEFAULT 1,
      created_at TEXT NOT NULL, updated_at TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS snippets (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      skey TEXT NOT NULL UNIQUE, value TEXT NOT NULL DEFAULT '',
      grp TEXT DEFAULT '', label TEXT DEFAULT '', type TEXT DEFAULT 'text',
      sort INTEGER DEFAULT 0,
      created_at TEXT NOT NULL, updated_at TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS users (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      username TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL,
      created_at TEXT NOT NULL, updated_at TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS login_attempts (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      ip TEXT NOT NULL, username TEXT NOT NULL, success INTEGER NOT NULL,
      created_at TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS audit_log (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user TEXT NOT NULL, action TEXT NOT NULL, entity TEXT DEFAULT '',
      entity_id TEXT DEFAULT '', ip TEXT DEFAULT '', created_at TEXT NOT NULL
    );
    CREATE INDEX IF NOT EXISTS idx_attempts_ip ON login_attempts(ip, created_at);
    SQL);
}
```

- [ ] **Step 5: 运行测试，确认通过**

Run: `php tests/run.php`
Expected: PASS — 10 张表存在 + ERRMODE 断言通过。

- [ ] **Step 6: Commit**

```bash
git add app/db.php tests/run.php tests/test_db.php
git commit -m "feat(mabseek): SQLite connection + schema migrate + test runner"
```

---

## Task 3: 通用助手 helpers.php

**Files:**
- Create: `mabseek/app/helpers.php`
- Test: `mabseek/tests/test_helpers.php`

- [ ] **Step 1: 写 test_helpers.php（失败先行）**

```php
<?php
require_once __DIR__ . '/../app/helpers.php';
check(e('<a>&"') === '&lt;a&gt;&amp;&quot;', 'e() escapes html + quotes');
check(e(null) === '', 'e(null) === empty string');
check(nl2br_e("a\nb") === "a<br />\nb", 'nl2br_e escapes then breaks');
echo iso_now() . "\n";
check(preg_match('/^\d{4}-\d\d-\d\dT/', iso_now()) === 1, 'iso_now ISO8601');
```

- [ ] **Step 2: 运行，确认失败**

Run: `php tests/run.php`
Expected: FAIL —「e() not defined」。

- [ ] **Step 3: 写 helpers.php**

```php
<?php
declare(strict_types=1);

function e(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function nl2br_e(?string $s): string {
    return nl2br(e($s));
}
function iso_now(): string {
    return date('c');
}
function client_ip(): string {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
function redirect(string $to): never {
    header('Location: ' . $to);
    exit;
}
function flash_set(string $type, string $msg): void {
    $_SESSION['__flash'][] = ['type' => $type, 'msg' => $msg];
}
function flash_take(): array {
    $f = $_SESSION['__flash'] ?? [];
    unset($_SESSION['__flash']);
    return $f;
}
function old(string $key, string $default = ''): string {
    return (string)($_SESSION['__old'][$key] ?? $default);
}
function old_set(array $data): void { $_SESSION['__old'] = $data; }
function old_clear(): void { unset($_SESSION['__old']); }
```

- [ ] **Step 4: 运行测试，确认通过**

Run: `php tests/run.php`
Expected: PASS — e/nl2br_e/iso_now 断言通过。

- [ ] **Step 5: Commit**

```bash
git add app/helpers.php tests/test_helpers.php
git commit -m "feat(mabseek): output-escaping + flash/old/redirect helpers"
```

---

## Task 4: CSRF 令牌

**Files:**
- Create: `mabseek/app/csrf.php`
- Test: `mabseek/tests/test_csrf.php`

- [ ] **Step 1: 写 test_csrf.php（失败先行）**

```php
<?php
if (session_status() !== PHP_SESSION_ACTIVE) { $_SESSION = []; }
require_once __DIR__ . '/../app/csrf.php';
$t1 = csrf_token();
check(strlen($t1) >= 32, 'token length ok');
check(csrf_token() === $t1, 'token stable within session');
check(csrf_check($t1) === true, 'valid token passes');
check(csrf_check('bogus') === false, 'wrong token fails');
```

> 注：测试环境无真实 session，`csrf.php` 用 `$_SESSION` 数组即可（运行器为 CLI，`$_SESSION` 作普通全局数组存在）。

- [ ] **Step 2: 运行，确认失败**

Run: `php tests/run.php`
Expected: FAIL —「csrf_token not defined」。

- [ ] **Step 3: 写 csrf.php**

```php
<?php
declare(strict_types=1);

function csrf_token(): string {
    if (empty($_SESSION['__csrf'])) {
        $_SESSION['__csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['__csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}
function csrf_check(?string $token): bool {
    return is_string($token) && !empty($_SESSION['__csrf'])
        && hash_equals($_SESSION['__csrf'], $token);
}
function csrf_verify_or_die(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !csrf_check($_POST['_csrf'] ?? null)) {
        http_response_code(403);
        exit('CSRF 校验失败');
    }
}
```

- [ ] **Step 4: 运行测试，确认通过**

Run: `php tests/run.php`
Expected: PASS。

- [ ] **Step 5: Commit**

```bash
git add app/csrf.php tests/test_csrf.php
git commit -m "feat(mabseek): CSRF token generation + hash_equals verification"
```

---

## Task 5: 认证 + 登录风控 auth.php

**Files:**
- Create: `mabseek/app/auth.php`
- Test: `mabseek/tests/test_auth.php`

- [ ] **Step 1: 写 test_auth.php（失败先行）**

```php
<?php
putenv('MABSEEK_DB=:memory:');
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/auth.php';
$pdo = db();
$now = iso_now();
$pdo->prepare('INSERT INTO users(username,password_hash,created_at,updated_at) VALUES(?,?,?,?)')
    ->execute(['admin', password_hash('secret', PASSWORD_ARGON2ID), $now, $now]);

check(auth_verify_credentials('admin', 'secret') === true,  'correct password verifies');
check(auth_verify_credentials('admin', 'nope')  === false, 'wrong password fails');
check(auth_verify_credentials('ghost', 'x')      === false, 'unknown user fails');

// 风控：记 5 次失败后锁定
for ($i = 0; $i < LOGIN_MAX_FAILS; $i++) { auth_record_attempt('1.2.3.4', 'admin', false); }
check(auth_is_locked('1.2.3.4', 'admin') === true, 'locked after max fails');
check(auth_is_locked('9.9.9.9', 'admin') === false, 'other ip not locked');
```

- [ ] **Step 2: 运行，确认失败**

Run: `php tests/run.php`
Expected: FAIL —「auth_verify_credentials not defined」。

- [ ] **Step 3: 写 auth.php**

```php
<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function auth_verify_credentials(string $user, string $pass): bool {
    $row = db()->prepare('SELECT password_hash FROM users WHERE username = ? LIMIT 1');
    $row->execute([$user]);
    $hash = $row->fetchColumn();
    if ($hash === false) {
        password_verify($pass, '$argon2id$v=19$m=65536,t=4,p=1$AAAAAAAAAAAAAAAA$AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'); // 时间恒定，防用户枚举
        return false;
    }
    return password_verify($pass, (string)$hash);
}

function auth_record_attempt(string $ip, string $user, bool $success): void {
    db()->prepare('INSERT INTO login_attempts(ip,username,success,created_at) VALUES(?,?,?,?)')
        ->execute([$ip, $user, $success ? 1 : 0, iso_now()]);
}

function auth_is_locked(string $ip, string $user): bool {
    $since = date('c', time() - LOGIN_FAIL_WINDOW);
    $q = db()->prepare(
        'SELECT COUNT(*) FROM login_attempts
         WHERE ip = ? AND username = ? AND success = 0 AND created_at >= ?'
    );
    $q->execute([$ip, $user, $since]);
    return (int)$q->fetchColumn() >= LOGIN_MAX_FAILS;
}

function auth_login_user(string $user): void {
    session_regenerate_id(true);               // 防会话固定
    $_SESSION['uid']  = $user;
    $_SESSION['ua']   = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200);
    $_SESSION['last'] = time();
    $_SESSION['born'] = time();
}

function auth_logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function auth_check(): bool {
    if (empty($_SESSION['uid'])) return false;
    $now = time();
    if ($now - ($_SESSION['last'] ?? 0) > IDLE_TIMEOUT)     { auth_logout(); return false; }
    if ($now - ($_SESSION['born'] ?? 0) > ABSOLUTE_TIMEOUT) { auth_logout(); return false; }
    if (($_SESSION['ua'] ?? '') !== substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200)) {
        auth_logout(); return false;
    }
    $_SESSION['last'] = $now;
    return true;
}

function auth_require(): void {
    if (!auth_check()) redirect('admin.php');
}

function audit(string $action, string $entity = '', string $entityId = ''): void {
    db()->prepare('INSERT INTO audit_log(user,action,entity,entity_id,ip,created_at) VALUES(?,?,?,?,?,?)')
        ->execute([$_SESSION['uid'] ?? '-', $action, $entity, $entityId, client_ip(), iso_now()]);
}

function auth_change_password(string $user, string $newPass): void {
    db()->prepare('UPDATE users SET password_hash = ?, updated_at = ? WHERE username = ?')
        ->execute([password_hash($newPass, PASSWORD_ARGON2ID), iso_now(), $user]);
}
```

- [ ] **Step 4: 运行测试，确认通过**

Run: `php tests/run.php`
Expected: PASS — 凭据校验 + 锁定断言通过。

- [ ] **Step 5: Commit**

```bash
git add app/auth.php tests/test_auth.php
git commit -m "feat(mabseek): Argon2id auth + login rate-limit/lockout + audit + session hardening"
```

---

## Task 6: 引导 bootstrap.php（安全会话启动）

**Files:**
- Create: `mabseek/app/bootstrap.php`

- [ ] **Step 1: 写 bootstrap.php**

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/repositories/Collection.php';
require_once __DIR__ . '/repositories/Snippets.php';

// 运行时错误策略
if (APP_DEBUG) {
    error_reporting(E_ALL); ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL); ini_set('display_errors', '0'); ini_set('log_errors', '1');
}

// 安全会话（仅 web 请求，CLI 跳过）
if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'domain' => '',
        'secure' => $https, 'httponly' => true, 'samesite' => 'Strict',
    ]);
    session_start();
}

// 预热 DB（触发建表）
db();
```

- [ ] **Step 2: Lint**

Run: `php -l app/bootstrap.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add app/bootstrap.php
git commit -m "feat(mabseek): bootstrap — secure session + wiring"
```

---

## Task 7: 仓库层（Collection + Snippets）

**Files:**
- Create: `mabseek/app/repositories/Collection.php`
- Create: `mabseek/app/repositories/Snippets.php`
- Test: `mabseek/tests/test_repositories.php`

- [ ] **Step 1: 写 test_repositories.php（失败先行）**

```php
<?php
putenv('MABSEEK_DB=:memory:');
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/repositories/Collection.php';
require_once __DIR__ . '/../app/repositories/Snippets.php';

$news = new Collection('news');
$id = $news->create(['date_day'=>'08','date_ym'=>'2026·08','category'=>'res','title'=>'T','summary'=>'S','image'=>null,'sort'=>1,'published'=>1]);
check($id > 0, 'create returns id');
check($news->find($id)['title'] === 'T', 'find returns row');
$news->update($id, ['title'=>'T2']);
check($news->find($id)['title'] === 'T2', 'update persists');
check(count($news->published()) === 1, 'published lists 1');
$news->update($id, ['published'=>0]);
check(count($news->published()) === 0, 'unpublished excluded');
$news->delete($id);
check($news->find($id) === null, 'delete removes');

Snippets::set('home.hero.title','Hello','home','标题');
check(Snippets::get('home.hero.title') === 'Hello', 'snippet get');
check(Snippets::get('missing','def') === 'def', 'snippet default');
```

- [ ] **Step 2: 运行，确认失败**

Run: `php tests/run.php`
Expected: FAIL —「class Collection not found」。

- [ ] **Step 3: 写 Collection.php**

```php
<?php
declare(strict_types=1);

final class Collection {
    // 各表允许写入的列（白名单，防批量赋值）
    private const COLUMNS = [
        'news'          => ['date_day','date_ym','category','title','summary','image','sort','published'],
        'forum_posts'   => ['category','cover_type','cover_ref','cover_variant','toptag','title','tags','author_name','author_avatar_char','author_avatar_style','likes','sort','published'],
        'forum_hot'     => ['list','rank','title','category','heat','sort','published'],
        'team_members'  => ['name','affiliation','direction','role_label','role_type','avatar_char','avatar_variant','sort','published'],
        'partners'      => ['name','mark','sub','logo_image','demo','sort','published'],
        'content_cards' => ['grp','icon','title','body','extra','sort','published'],
    ];

    public function __construct(private string $table) {
        if (!isset(self::COLUMNS[$this->table])) {
            throw new InvalidArgumentException("unknown collection: {$this->table}");
        }
    }

    private function cols(): array { return self::COLUMNS[$this->table]; }

    private function filter(array $data): array {
        return array_intersect_key($data, array_flip($this->cols()));
    }

    public function all(?string $where = null, array $params = []): array {
        $sql = "SELECT * FROM {$this->table}";
        if ($where) $sql .= " WHERE $where";
        $sql .= ' ORDER BY sort ASC, id ASC';
        $st = db()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /** 前台用：仅 published，可加额外条件 */
    public function published(?string $extraWhere = null, array $params = []): array {
        $where = 'published = 1' . ($extraWhere ? " AND ($extraWhere)" : '');
        return $this->all($where, $params);
    }

    public function find(int $id): ?array {
        $st = db()->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function create(array $data): int {
        $data = $this->filter($data);
        $now = iso_now();
        $data['created_at'] = $now; $data['updated_at'] = $now;
        $keys = array_keys($data);
        $ph   = implode(',', array_fill(0, count($keys), '?'));
        $sql  = "INSERT INTO {$this->table} (" . implode(',', $keys) . ") VALUES ($ph)";
        db()->prepare($sql)->execute(array_values($data));
        return (int)db()->lastInsertId();
    }

    public function update(int $id, array $data): void {
        $data = $this->filter($data);
        if (!$data) return;
        $data['updated_at'] = iso_now();
        $set = implode(',', array_map(fn($k) => "$k = ?", array_keys($data)));
        $sql = "UPDATE {$this->table} SET $set WHERE id = ?";
        db()->prepare($sql)->execute([...array_values($data), $id]);
    }

    public function delete(int $id): void {
        db()->prepare("DELETE FROM {$this->table} WHERE id = ?")->execute([$id]);
    }

    public function count(): int {
        return (int)db()->query("SELECT COUNT(*) FROM {$this->table}")->fetchColumn();
    }
}
```

- [ ] **Step 4: 写 Snippets.php**

```php
<?php
declare(strict_types=1);

final class Snippets {
    private static ?array $cache = null;

    private static function load(): array {
        if (self::$cache === null) {
            $rows = db()->query('SELECT skey, value FROM snippets')->fetchAll();
            self::$cache = [];
            foreach ($rows as $r) self::$cache[$r['skey']] = $r['value'];
        }
        return self::$cache;
    }

    public static function get(string $key, string $default = ''): string {
        $all = self::load();
        return array_key_exists($key, $all) ? (string)$all[$key] : $default;
    }

    /** 幂等 upsert；seed 与后台共用 */
    public static function set(string $key, string $value, string $grp = '', string $label = '', string $type = 'text', int $sort = 0): void {
        $now = iso_now();
        db()->prepare(
            'INSERT INTO snippets(skey,value,grp,label,type,sort,created_at,updated_at)
             VALUES(:k,:v,:g,:l,:t,:s,:n,:n)
             ON CONFLICT(skey) DO UPDATE SET value=excluded.value, updated_at=:n'
        )->execute([':k'=>$key, ':v'=>$value, ':g'=>$grp, ':l'=>$label, ':t'=>$type, ':s'=>$sort, ':n'=>$now]);
        self::$cache = null;
    }

    /** 仅当不存在时写入（seed 保护后台已改文案） */
    public static function seed(string $key, string $value, string $grp, string $label, string $type = 'text', int $sort = 0): void {
        $exists = db()->prepare('SELECT 1 FROM snippets WHERE skey = ?');
        $exists->execute([$key]);
        if ($exists->fetchColumn()) return;
        self::set($key, $value, $grp, $label, $type, $sort);
    }

    public static function updateValue(int $id, string $value): void {
        db()->prepare('UPDATE snippets SET value = ?, updated_at = ? WHERE id = ?')
            ->execute([$value, iso_now(), $id]);
        self::$cache = null;
    }

    public static function allGrouped(): array {
        $rows = db()->query('SELECT * FROM snippets ORDER BY grp, sort, id')->fetchAll();
        $out = [];
        foreach ($rows as $r) $out[$r['grp']][] = $r;
        return $out;
    }
}

/** 模板便捷函数：转义输出文案片段 */
function snip(string $key, string $default = ''): string {
    return e(Snippets::get($key, $default));
}
/** 原样（不转义，用于本身含既定 markup 的极少数片段——默认不用） */
function snip_raw(string $key, string $default = ''): string {
    return Snippets::get($key, $default);
}
```

- [ ] **Step 5: 运行测试，确认通过**

Run: `php tests/run.php`
Expected: PASS — CRUD + published 过滤 + snippet get/default 断言通过。

- [ ] **Step 6: Commit**

```bash
git add app/repositories tests/test_repositories.php
git commit -m "feat(mabseek): Collection + Snippets repositories with column whitelist"
```

---

## Task 8: 现有文案入库 bin/seed.php（幂等 · 逐字）

**Files:**
- Create: `mabseek/bin/seed.php`

**原则：** 所有 `value` / `title` / `body` 等文本 **逐字抄录**自现有 HTML（见根下 6 个 `.html`）。数字、标点、空格、emoji 一律照搬，禁止改写。此任务是「不杜撰」与「不改风格」的核心。

- [ ] **Step 1: 写 seed.php —— 头部 + admin + 集合**

```php
<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap.php';

echo "Seeding MabSeek DB...\n";
$pdo = db();

// ── 1) 初始管理员（幂等：已存在不动，保护改过的密码）──
$has = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ((int)$has === 0) {
    $now = iso_now();
    $pdo->prepare('INSERT INTO users(username,password_hash,created_at,updated_at) VALUES(?,?,?,?)')
        ->execute([SEED_ADMIN_USER, password_hash(SEED_ADMIN_PASS, PASSWORD_ARGON2ID), $now, $now]);
    echo "  + admin created\n";
} else {
    echo "  = users exist, skip\n";
}

/** 集合幂等插入：按 (sort) 顺序，若表已有等量行则跳过整表 */
function seed_collection(string $table, array $rows): void {
    $c = new Collection($table);
    if ($c->count() >= count($rows)) { echo "  = $table has data, skip\n"; return; }
    foreach ($rows as $r) $c->create($r);
    echo "  + $table: " . count($rows) . " rows\n";
}

// ── 2) news（about.html #news，5 条，逐字）──
seed_collection('news', [
    ['date_day'=>'08','date_ym'=>'2026·08','category'=>'res','title'=>'MabSeek 平台技术升级：亲和力预测模型精度再提升','summary'=>'最新一轮迭代显著提升虚拟筛选命中率，缩短候选分子验证周期。','image'=>null,'sort'=>1,'published'=>1],
    ['date_day'=>'07','date_ym'=>'2026·07','category'=>'edu','title'=>'《疫苗的力量》交互式课程正式上线','summary'=>'元视频 + 三层知识图谱，面向学生、从业者与公众开放基础版。','image'=>null,'sort'=>2,'published'=>1],
    ['date_day'=>'06','date_ym'=>'2026·06','category'=>'res','title'=>'实验室最新研究成果发表于领域顶级期刊','summary'=>'抗体设计与免疫机制相关工作获同行高度评价。','image'=>null,'sort'=>3,'published'=>1],
    ['date_day'=>'05','date_ym'=>'2026·05','category'=>'daily','title'=>'组会纪实与学术沙龙：师生共话前沿方向','summary'=>'平台宣传片发布，记录实验室日常科研与交流活动。','image'=>null,'sort'=>4,'published'=>1],
    ['date_day'=>'04','date_ym'=>'2026·04','category'=>'edu','title'=>'学生斩获学术竞赛奖项，人才培养成果显著','summary'=>'科普讲座与公益授课走进高校，扩大科学影响力。','image'=>null,'sort'=>5,'published'=>1],
]);

// ── 3) team_members（about.html 双负责人，逐字）──
seed_collection('team_members', [
    ['name'=>'张林琦','affiliation'=>'清华大学 · 实验室负责人','direction'=>'长期从事抗体工程、疫苗研发与病毒免疫研究，为 MabSeek 奠定抗体科学与湿实验验证的专业根基。','role_label'=>'科学负责人 · 抗体 / 疫苗 / 病毒免疫','role_type'=>'science','avatar_char'=>'张','avatar_variant'=>'','sort'=>1,'published'=>1],
    ['name'=>'马维英','affiliation'=>'清华大学 · 实验室负责人','direction'=>'深耕人工智能、大模型与机器学习，为 MabSeek 的 AI 抗体设计、预测与分析能力提供核心算法支撑。','role_label'=>'AI 负责人 · 人工智能 / 大模型 / 机器学习','role_type'=>'ai','avatar_char'=>'马','avatar_variant'=>'g2','sort'=>2,'published'=>1],
]);

// ── 4) partners（index.html logo 墙，逐字）──
seed_collection('partners', [
    ['name'=>'清华大学','mark'=>'清','sub'=>'','logo_image'=>null,'demo'=>'清华大学联合实验室详情','sort'=>1,'published'=>1],
    ['name'=>'北京大学','mark'=>'北','sub'=>'','logo_image'=>null,'demo'=>'北京大学联合实验室详情','sort'=>2,'published'=>1],
    ['name'=>'Eijkman 研究所','mark'=>'EJ','sub'=>'印度尼西亚','logo_image'=>null,'demo'=>'Eijkman 研究所合作','sort'=>3,'published'=>1],
    ['name'=>'PRA 国际联盟','mark'=>'PRA','sub'=>'','logo_image'=>null,'demo'=>'PRA 国际联盟合作','sort'=>4,'published'=>1],
]);
```

- [ ] **Step 2: 追加 forum_hot（12 条，逐字）**

```php
// ── 5) forum_hot（forum.html #hot，每日 6 + 每周 6，逐字）──
seed_collection('forum_hot', [
    ['list'=>'day','rank'=>1,'title'=>'顶刊拆解：双抗结构设计的三种范式与踩坑','category'=>'# 文献精读','heat'=>'🔥 1.2k','sort'=>1,'published'=>1],
    ['list'=>'day','rank'=>2,'title'=>'ELISA 总是背景高？这 5 个洗板细节 90% 的人忽略了','category'=>'# 实验踩坑','heat'=>'🔥 980','sort'=>2,'published'=>1],
    ['list'=>'day','rank'=>3,'title'=>'AlphaFold3 跑 Nb 表位分析：参数与结果解读实录','category'=>'# 生信工具','heat'=>'🔥 856','sort'=>3,'published'=>1],
    ['list'=>'day','rank'=>4,'title'=>'抗体序列特征分析：从 FASTA 到可视化的一条龙脚本','category'=>'# 生信工具','heat'=>'🔥 742','sort'=>4,'published'=>1],
    ['list'=>'day','rank'=>5,'title'=>'细胞培养污染排查手册：那些论文里不写的坑','category'=>'# 实验踩坑','heat'=>'🔥 610','sort'=>5,'published'=>1],
    ['list'=>'day','rank'=>6,'title'=>'ADC 偶联比 DAR 老是不稳定？记录一次踩坑复盘','category'=>'# 实验踩坑','heat'=>'🔥 523','sort'=>6,'published'=>1],
    ['list'=>'week','rank'=>1,'title'=>'2026 抗体研发岗求职时间线 + 面经合集（持续更新）','category'=>'# 求职招聘','heat'=>'🔥 5.6k','sort'=>7,'published'=>1],
    ['list'=>'week','rank'=>2,'title'=>'纳米抗体（VHH）综述：为什么它是 AI 设计的最佳试验田','category'=>'# 文献精读','heat'=>'🔥 4.8k','sort'=>8,'published'=>1],
    ['list'=>'week','rank'=>3,'title'=>'顶刊拆解：双抗结构设计的三种范式与踩坑','category'=>'# 文献精读','heat'=>'🔥 4.2k','sort'=>9,'published'=>1],
    ['list'=>'week','rank'=>4,'title'=>'抗体序列特征分析：从 FASTA 到可视化的一条龙脚本','category'=>'# 生信工具','heat'=>'🔥 3.9k','sort'=>10,'published'=>1],
    ['list'=>'week','rank'=>5,'title'=>'ELISA 总是背景高？这 5 个洗板细节 90% 的人忽略了','category'=>'# 实验踩坑','heat'=>'🔥 3.1k','sort'=>11,'published'=>1],
    ['list'=>'week','rank'=>6,'title'=>'AlphaFold3 跑 Nb 表位分析：参数与结果解读实录','category'=>'# 生信工具','heat'=>'🔥 2.7k','sort'=>12,'published'=>1],
]);
```

- [ ] **Step 3: 追加 forum_posts（8 条，逐字）**

> `cover_type`：`img`=有图（带 onerror 降级），`grad`=纯渐变块。`cover_ref`：img 存图片路径，grad 存 emoji。`cover_variant`：''/g2/g3。`toptag`：封面左上角小标签（img 卡有，grad 卡无）。`author_avatar_style`：非默认头像底色的内联 style（空=默认紫）。

```php
// ── 6) forum_posts（forum.html #feed，8 条，逐字）──
seed_collection('forum_posts', [
    ['category'=>'proto','cover_type'=>'img','cover_ref'=>'assets/images/forum-protocol.png','cover_variant'=>'','toptag'=>'# Protocol','title'=>'ELISA 总是背景高？这 5 个洗板细节 90% 的人忽略了','tags'=>'#实验踩坑,#Protocol分享','author_name'=>'资深博后 · 李','author_avatar_char'=>'博','author_avatar_style'=>'','likes'=>'❤️ 328','sort'=>1,'published'=>1],
    ['category'=>'bio','cover_type'=>'img','cover_ref'=>'assets/images/forum-bioinfo.png','cover_variant'=>'g2','toptag'=>'# 生信工具','title'=>'抗体序列特征分析：从 FASTA 到可视化的一条龙脚本','tags'=>'#生信工具,#Protocol分享','author_name'=>'生信小王','author_avatar_char'=>'生','author_avatar_style'=>'background:var(--grad-green);color:#04352a','likes'=>'❤️ 501','sort'=>2,'published'=>1],
    ['category'=>'paper','cover_type'=>'grad','cover_ref'=>'📄','cover_variant'=>'g3','toptag'=>'','title'=>'顶刊拆解：双抗结构设计的三种范式与踩坑','tags'=>'#文献精读,#前沿热点','author_name'=>'某 PI · 匿名','author_avatar_char'=>'PI','author_avatar_style'=>'','likes'=>'❤️ 742','sort'=>3,'published'=>1],
    ['category'=>'pit','cover_type'=>'img','cover_ref'=>'assets/images/forum-adc.png','cover_variant'=>'','toptag'=>'# 前沿','title'=>'ADC 偶联比 DAR 老是不稳定？记录一次踩坑复盘','tags'=>'#实验踩坑,#ADC','author_name'=>'产业老兵','author_avatar_char'=>'产','author_avatar_style'=>'background:var(--grad-green);color:#04352a','likes'=>'❤️ 289','sort'=>4,'published'=>1],
    ['category'=>'job','cover_type'=>'grad','cover_ref'=>'💼','cover_variant'=>'','toptag'=>'','title'=>'2026 抗体研发岗求职时间线 + 面经合集（持续更新）','tags'=>'#求职招聘,#科研生活','author_name'=>'校友 · 张','author_avatar_char'=>'校','author_avatar_style'=>'','likes'=>'❤️ 613','sort'=>5,'published'=>1],
    ['category'=>'paper','cover_type'=>'grad','cover_ref'=>'🦠','cover_variant'=>'g2','toptag'=>'','title'=>'纳米抗体（VHH）综述：为什么它是 AI 设计的最佳试验田','tags'=>'#文献精读,#纳米抗体','author_name'=>'研一萌新','author_avatar_char'=>'研','author_avatar_style'=>'','likes'=>'❤️ 176','sort'=>6,'published'=>1],
    ['category'=>'pit','cover_type'=>'grad','cover_ref'=>'🔬','cover_variant'=>'g3','toptag'=>'','title'=>'细胞培养污染排查手册：那些论文里不写的坑','tags'=>'#实验踩坑,#避坑指南','author_name'=>'养细胞的人','author_avatar_char'=>'养','author_avatar_style'=>'background:var(--grad-green);color:#04352a','likes'=>'❤️ 455','sort'=>7,'published'=>1],
    ['category'=>'bio','cover_type'=>'grad','cover_ref'=>'🧠','cover_variant'=>'','toptag'=>'','title'=>'AlphaFold3 跑 Nb 表位分析：参数与结果解读实录','tags'=>'#生信工具,#AI制药','author_name'=>'算法同学','author_avatar_char'=>'算','author_avatar_style'=>'','likes'=>'❤️ 388','sort'=>8,'published'=>1],
]);
```

- [ ] **Step 4: 追加 content_cards（分组，逐字）**

> `extra` 存 JSON。`home_pain`：`{"fix":"→解法行"}`。`about_achievement`/`about_platform_agent`：无 extra。`forum_line`：`{"ico_style":"…","items":["…","…","…"]}`。`edu_grow` 等按需。

```php
// ── 7) content_cards（各分组，逐字）──
// 7a. 首页四大痛点（index.html .pain-grid）
seed_collection('content_cards', array_merge([
    ['grp'=>'home_pain','icon'=>'','title'=>'难成药靶点','body'=>'GPCR、离子通道等跨膜靶点结构复杂、表达量低，抗原极难制备。','extra'=>json_encode(['fix'=>'→ VLP 技术保持天然构象，结合湿实验平台高效分离'], JSON_UNESCAPED_UNICODE),'sort'=>1,'published'=>1],
    ['grp'=>'home_pain','icon'=>'','title'=>'AI 模型数据','body'=>'公开库多为正样本，缺负样本与完整物理信息，模型"垃圾进垃圾出"。','extra'=>json_encode(['fix'=>'→ 正/负结合抗体序列构建完整数据库，提供精准训练基准'], JSON_UNESCAPED_UNICODE),'sort'=>2,'published'=>1],
    ['grp'=>'home_pain','icon'=>'','title'=>'干湿结合','body'=>'计算与实验割裂，候选分子从设计到验证需数月，试错成本高昂。','extra'=>json_encode(['fix'=>'→ 干湿闭环，以显著提升命中率、缩短验证周期为目标（示意）'], JSON_UNESCAPED_UNICODE),'sort'=>3,'published'=>1],
    ['grp'=>'home_pain','icon'=>'','title'=>'成药性评估','body'=>'成药性风险高，后期易聚集、免疫原性高，临床前废弃率高。','extra'=>json_encode(['fix'=>'→ 成药性评估 Agent，在设计早期多维评估、从源头规避风险'], JSON_UNESCAPED_UNICODE),'sort'=>4,'published'=>1],
    // 7b. about 四张成果卡（about.html #team .grid-2）
    ['grp'=>'about_achievement','icon'=>'📄','title'=>'学术论文成果','body'=>'系列研究成果发表于领域顶级期刊，内化沉淀入平台知识库，支撑 Antibody Agent 的文献问答能力。','extra'=>'','sort'=>5,'published'=>1],
    ['grp'=>'about_achievement','icon'=>'🧾','title'=>'专利成果汇总','body'=>'抗体设计算法与湿实验方法相关专利，构成平台核心技术壁垒。','extra'=>'','sort'=>6,'published'=>1],
    ['grp'=>'about_achievement','icon'=>'🏆','title'=>'科研奖项与荣誉','body'=>'承担国家级科研项目，获多项学术与产业化荣誉。','extra'=>'','sort'=>7,'published'=>1],
    ['grp'=>'about_achievement','icon'=>'👩‍🔬','title'=>'核心团队','body'=>'由博士研究团队与湿实验平台工程师组成，产学研深度融合。','extra'=>'','sort'=>8,'published'=>1],
    // 7c. 论坛四条内容线（forum.html .line-card）
    ['grp'=>'forum_line','icon'=>'🎓','title'=>'校友传承线','body'=>'','extra'=>json_encode(['ico_style'=>'background:var(--purple-050);color:var(--purple)','items'=>['校友留言墙：科研感悟、成长心得、毕业寄语','校友风采录：发展去向、职业简介、成长故事','校友动态联动：前后辈互助传承体系']], JSON_UNESCAPED_UNICODE),'sort'=>9,'published'=>1],
    ['grp'=>'forum_line','icon'=>'🙋','title'=>'问答求助线','body'=>'','extra'=>json_encode(['ico_style'=>'background:var(--green-100);color:#06a97c','items'=>['实验技术求助：Western、ELISA、细胞培养','生信分析求助：序列分析、分子模拟、代码报错','文献求助：找不到全文、看不懂关键论文']], JSON_UNESCAPED_UNICODE),'sort'=>10,'published'=>1],
    ['grp'=>'forum_line','icon'=>'📦','title'=>'干货分享线','body'=>'','extra'=>json_encode(['ico_style'=>'background:var(--purple-050);color:var(--purple)','items'=>['实验 Protocol 库：经过验证的实操流程','工具与资源：软件、数据库、脚本推荐','文献精读 + 避坑指南']], JSON_UNESCAPED_UNICODE),'sort'=>11,'published'=>1],
    ['grp'=>'forum_line','icon'=>'💡','title'=>'话题讨论线','body'=>'','extra'=>json_encode(['ico_style'=>'background:var(--green-100);color:#06a97c','items'=>['前沿热点：ADC、双抗、纳米抗体、AI 制药','产业动态：新药获批、融资并购、行业趋势','科研生活：读博日常、压力调节、师生关系']], JSON_UNESCAPED_UNICODE),'sort'=>12,'published'=>1],
]));
```

- [ ] **Step 5: 追加 snippets（全部零散文案，逐字）**

> 按页分组。key 命名 `<page>.<section>.<field>`。以下覆盖各页 Hero/eyebrow/标题/说明/联系信息/页脚。**逐字**抄录，含 emoji 与标点。此列表须完整覆盖每页非集合类可编辑文字（见每页转换任务的「snippet 清单」核对）。

```php
// ── 8) snippets（零散文案，逐字）──
$S = [
    // 全站页脚（footer.php 共用）
    ['footer.brand.tagline','清华团队 × AI 大模型，让抗体发现从反复试错变成精准编程。','common','页脚品牌简介','textarea'],
    ['footer.copyright','© 2026 MabSeek 抗体求索 · 清华大学医学院实验室. 保留所有权利。','common','页脚版权行','text'],
    ['footer.slogan','紫 + 荧光绿 · 科技与趣味的平衡','common','页脚标语','text'],
    ['contact.email','contact@mabseek.org','common','联系邮箱','text'],
    ['contact.org','清华大学医学院','common','联系单位','text'],

    // index.html
    ['home.hero.eyebrow','🧬 清华团队 × AI 大模型','home','首页 Hero 眉题','text'],
    ['home.hero.title','让抗体发现<br>从"反复试错"变成 <span class="txt-neon">"精准编程"</span>','home','首页 Hero 标题(含标记)','textarea'],
    ['home.hero.sub','从头设计一支完美结合靶点的抗体——AI 生成 + 干湿闭环验证，一站直达。','home','首页 Hero 副标题','textarea'],
    ['home.hero.cta','免费试用 Antibody Agent →','home','首页 Hero 按钮','text'],
    ['home.can.eyebrow','我们能做什么','home','能做什么 眉题','text'],
    ['home.can.title','把靶点交给 AI，<span class="txt-green">从设计到验证一条闭环</span>','home','能做什么 标题(含标记)','textarea'],
    ['home.can.sub','AI 从头序列设计 · 结构与亲和力预测 · 干湿闭环一站交付。过去分散在多个团队、数月起步的流程，被压缩为连续、可追溯、可下单的闭环。','home','能做什么 说明','textarea'],
    ['home.can.link','进入技术平台，了解完整技术优势 →','home','能做什么 链接','text'],
    ['home.pain.eyebrow','痛点共鸣 · 针对性解法','home','痛点 眉题','text'],
    ['home.pain.title','抗体发现行业的四大痛点，<span class="txt-neon">各有解法</span>','home','痛点 标题(含标记)','textarea'],
    ['home.pain.cta','查看实际案例 →','home','痛点 按钮','text'],
    ['home.news.eyebrow','我们的近况','home','近况 眉题','text'],
    ['home.news.title','新闻 · 发表 · 活动','home','近况 标题','text'],
    ['home.news.link','进入「了解我们」查看全部近况 →','home','近况 链接','text'],
    ['home.contact.eyebrow','合作伙伴 · 联系我们','home','联系 眉题','text'],
    ['home.contact.title','与顶尖机构<span class="txt-neon">共建生态</span>','home','联系 标题(含标记)','textarea'],
    ['home.contact.h3','有靶点或合作意向？给我们留个言','home','联系 小标题','text'],
    // 首页近况横滚卡（结构固定，文字可编辑）——沿用 news 集合渲染？否：首页卡片文案与 about 不同，用 snippet
    ['home.newscard.1.thumb','🧬 平台技术升级','home','近况卡1 缩略','text'],
    ['home.newscard.1.date','2026 · 08','home','近况卡1 日期','text'],
    ['home.newscard.1.title','亲和力预测模型精度再提升','home','近况卡1 标题','text'],
    ['home.newscard.1.body','最新一轮迭代显著提升虚拟筛选命中率，缩短候选分子验证周期。','home','近况卡1 正文','textarea'],
    ['home.newscard.2.thumb','🎓 交互式课程','home','近况卡2 缩略','text'],
    ['home.newscard.2.date','2026 · 07','home','近况卡2 日期','text'],
    ['home.newscard.2.title','《疫苗的力量》交互式课程上线','home','近况卡2 标题','text'],
    ['home.newscard.2.body','元视频 + 三层知识图谱，面向学生、从业者与公众开放基础版。','home','近况卡2 正文','textarea'],
    ['home.newscard.3.thumb','📄 学术发表','home','近况卡3 缩略','text'],
    ['home.newscard.3.date','2026 · 06','home','近况卡3 日期','text'],
    ['home.newscard.3.title','研究成果发表于领域顶级期刊','home','近况卡3 标题','text'],
    ['home.newscard.3.body','抗体设计与免疫机制相关工作获同行高度评价。','home','近况卡3 正文','textarea'],
    ['home.newscard.4.thumb','🌏 国际交流','home','近况卡4 缩略','text'],
    ['home.newscard.4.date','2026 · 05','home','近况卡4 日期','text'],
    ['home.newscard.4.title','中印尼疫苗与基因组联合研究进展','home','近况卡4 标题','text'],
    ['home.newscard.4.body','持续推进 PRA 国际科研合作与联合实验室建设。','home','近况卡4 正文','textarea'],
];
foreach ($S as $s) Snippets::seed($s[0], $s[1], $s[2], $s[3], $s[4] ?? 'text');
echo "  + snippets(home/common): " . count($S) . " seeded (idempotent)\n";
```

> **注：** technology / agent / education / forum / about 页的 snippet 数组在同一文件继续追加（`$S2 = [...]; foreach (...) Snippets::seed(...)`）。实现者须按下方每页转换任务的「snippet 清单」把该页所有零散文案补全到 seed。为避免本计划过长，各页 snippet 的键与逐字值在对应页任务（Task 10–15）内以「snippet 清单」表格给出——实现该页前，先把清单补进 `bin/seed.php` 再转换模板。

- [ ] **Step 6: 运行 seed，检查输出与库**

Run:
```bash
php bin/seed.php
php -r 'require "app/bootstrap.php"; foreach(["news","forum_posts","forum_hot","team_members","partners","content_cards"] as $t){printf("%s=%d\n",$t,(new Collection($t))->count());} printf("snippets=%d\n",(int)db()->query("SELECT COUNT(*) FROM snippets")->fetchColumn());'
```
Expected: `news=5 forum_posts=8 forum_hot=12 team_members=2 partners=4 content_cards=12 snippets≥…`；再次运行 `php bin/seed.php` 全部显示 `= … skip`（幂等）。

- [ ] **Step 7: Commit**

```bash
git add bin/seed.php
git commit -m "feat(mabseek): idempotent seed of admin + collections + home/common snippets (verbatim)"
```

---

## Task 9: 共用 partials（nav.php / footer.php）

**Files:**
- Create: `mabseek/public/partials/nav.php`
- Create: `mabseek/public/partials/footer.php`

**参数契约（各页 include 前设变量）：**
- `$active`：`'index'|'technology'|'agent'|'education'|'forum'|'about'`（决定 `.active`）。
- `$navOnDark`：bool，index/technology 为 true（加 `.nav--on-dark`）。
- `$contactHref`：index/about 为 `'#contact'`，其余为 `'index.php#contact'`。

- [ ] **Step 1: 写 nav.php（逐字复制现有 `<header class="nav">`，仅动态化 3 处）**

```php
<?php
/** @var string $active @var bool $navOnDark @var string $contactHref */
$navOnDark = $navOnDark ?? false;
$contactHref = $contactHref ?? 'index.php#contact';
$links = [
  'index'      => ['首页', 'index.php'],
  'technology' => ['技术平台', 'technology.php'],
  'agent'      => ['Antibody Agent', 'agent.php'],
  'education'  => ['教育', 'education.php'],
  'forum'      => ['论坛', 'forum.php'],
  'about'      => ['了解我们', 'about.php'],
];
?>
<header class="nav<?= $navOnDark ? ' nav--on-dark' : '' ?>">
  <div class="container">
    <a class="brand" href="index.php"><span class="logo"><svg viewBox="0 0 24 24" fill="none"><path d="M7 3v4c0 2.8 2.2 5 5 5s5 2.2 5 5v4M17 3v4c0 2.8-2.2 5-5 5" stroke="white" stroke-width="2" stroke-linecap="round"/></svg></span><span>MabSeek<small>抗体求索 · 清华大学医学院</small></span></a>
    <nav class="nav-links">
<?php foreach ($links as $key => [$label, $href]): ?>
      <a href="<?= $href ?>"<?= $active === $key ? ' class="active"' : '' ?>><?= $label ?></a>
<?php endforeach; ?>
    </nav>
    <div class="nav-actions"><a href="<?= e($contactHref) ?>" class="btn btn-green">联系我们</a></div>
    <button class="nav-toggle" aria-label="菜单"><span></span><span></span><span></span></button>
  </div>
</header>
```

> **注意 markup 差异：** index/technology 现有源使用多行 brand（含换行缩进），其余页 brand 为单行。渲染后的 DOM 等价（空白差异对布局无影响）。验证时以 DOM 结构与文字一致为准，不逐字节比对空白。

- [ ] **Step 2: 写 footer.php（逐字复制现有 `<footer class="footer">`，动态化文案片段）**

```php
<?php /* 全站统一页脚 */ ?>
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <div class="brand"><span class="logo"><svg viewBox="0 0 24 24" fill="none"><path d="M7 3v4c0 2.8 2.2 5 5 5s5 2.2 5 5v4M17 3v4c0 2.8-2.2 5-5 5" stroke="white" stroke-width="2" stroke-linecap="round"/></svg></span><span>MabSeek<small>抗体求索 · 清华大学医学院</small></span></div>
        <p><?= snip('footer.brand.tagline') ?></p>
        <div class="footer-social" style="margin-top:18px">
          <span title="微信" data-demo="扫码关注 MabSeek 公众号">💬</span>
          <span title="邮箱" data-demo="联系邮箱：<?= snip('contact.email') ?>">✉️</span>
        </div>
      </div>
      <div><h5>探索</h5><ul>
        <li><a href="technology.php">技术平台</a></li>
        <li><a href="agent.php">Antibody Agent</a></li>
        <li><a href="education.php">教育</a></li>
      </ul></div>
      <div><h5>社区</h5><ul>
        <li><a href="forum.php">论坛</a></li>
        <li><a href="about.php">了解我们</a></li>
        <li><a href="about.php#news">新闻活动</a></li>
      </ul></div>
      <div><h5>联系</h5><ul>
        <li><a href="index.php#contact">联系我们</a></li>
        <li><a href="mailto:<?= snip('contact.email') ?>"><?= snip('contact.email') ?></a></li>
        <li><?= snip('contact.org') ?></li>
      </ul></div>
    </div>
    <div class="footer-bottom">
      <span><?= snip('footer.copyright') ?></span>
      <span><?= snip('footer.slogan') ?></span>
    </div>
  </div>
</footer>
```

- [ ] **Step 3: Lint**

Run: `php -l public/partials/nav.php && php -l public/partials/footer.php`
Expected: 两个 `No syntax errors detected`.

- [ ] **Step 4: Commit**

```bash
git add public/partials
git commit -m "feat(mabseek): shared nav + footer partials (parameterized, verbatim markup)"
```

---

## Task 10: index.php（首页转换）

**Files:**
- Create: `mabseek/public/index.php`
- Modify: `mabseek/bin/seed.php`（确认 home/common snippet 清单已补全）

**转换规则：** 复制 `index.html` → `index.php`；顶部加 bootstrap 与页参数；`<header>` 换成 `include nav.php`；`<footer>` 换成 `include footer.php`；`.html` 链接改 `.php`；硬编码文字按下方清单替换为 `snip()`；四大痛点循环渲染 `content_cards.home_pain`；合作伙伴循环渲染 `partners`。

**index snippet 清单（seed 中已在 Task 8 Step 5 提供）：** `home.hero.*`、`home.can.*`、`home.pain.eyebrow/title/cta`、`home.news.eyebrow/title/link`、`home.contact.*`、`home.newscard.1..4.*`、`footer.*`、`contact.*`。转换前确认 `bin/seed.php` 已含全部并已 `php bin/seed.php`。

- [ ] **Step 1: 写 index.php 顶部 + 英雄区 + 能做什么**

```php
<?php
require __DIR__ . '/../app/bootstrap.php';
$active = 'index'; $navOnDark = true; $contactHref = '#contact';
$painCards = (new Collection('content_cards'))->published("grp = 'home_pain'");
$partners  = (new Collection('partners'))->published();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MabSeek 抗体求索 · AI 驱动的抗体发现平台 | 清华大学医学院</title>
<meta name="description" content="清华团队 × AI 大模型，让抗体发现从反复试错变成精准编程。MabSeek 抗体求索 · 清华大学医学院。">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🧬</text></svg>">
</head>
<body>

<?php include __DIR__ . '/partials/nav.php'; ?>

<!-- ============ L1 英雄区（深） ============ -->
<section class="hero-c">
  <canvas id="antibody-canvas"></canvas>
  <div class="hero-c-inner">
    <span class="eyebrow reveal"><?= snip('home.hero.eyebrow') ?></span>
    <h1 class="reveal d1"><?= snip_raw('home.hero.title') ?></h1>
    <p class="hero-c-sub reveal d2"><?= snip('home.hero.sub') ?></p>
  </div>
  <a href="agent.php" class="btn btn-green btn-lg hero-c-cta reveal d3"><?= snip('home.hero.cta') ?></a>
</section>

<!-- ============ L2 我们能做什么（浅） ============ -->
<section class="section section-light">
  <div class="container">
    <div style="max-width:820px">
      <span class="eyebrow green reveal"><?= snip('home.can.eyebrow') ?></span>
      <h2 class="section-title reveal d1"><?= snip_raw('home.can.title') ?></h2>
      <p class="section-sub reveal d2"><?= snip('home.can.sub') ?></p>
      <a href="technology.php" class="link-more reveal d3" style="margin-top:18px"><?= snip('home.can.link') ?></a>
    </div>
  </div>
</section>
```

> `snip_raw` 用于本身含既定 `<br>`/`<span class="txt-neon">` 标记的标题片段（这些标记是**布局**而非用户内容，seed 值由我们逐字控制，非外部输入，安全）。纯文本片段一律用 `snip`（转义）。

- [ ] **Step 2: 写四大痛点区（循环 content_cards）**

```php
<!-- ============ L3 四大痛点 × MabSeek 解法（深） ============ -->
<section class="section section-dark">
  <div class="container">
    <div class="text-center">
      <span class="eyebrow reveal"><?= snip('home.pain.eyebrow') ?></span>
      <h2 class="section-title reveal d1"><?= snip_raw('home.pain.title') ?></h2>
    </div>
    <div class="pain-grid">
<?php foreach ($painCards as $i => $c): $ex = json_decode($c['extra'] ?: '{}', true); ?>
      <div class="pain-cell reveal<?= $i ? ' d' . $i : '' ?>">
        <div class="p-title"><?= e($c['title']) ?></div>
        <p><?= e($c['body']) ?></p>
        <div class="p-fix"><?= e($ex['fix'] ?? '') ?></div>
      </div>
<?php endforeach; ?>
    </div>
    <div class="text-center" style="margin-top:30px">
      <a href="technology.php" class="btn btn-outline reveal"><?= snip('home.pain.cta') ?></a>
    </div>
  </div>
</section>
```

> 现有 markup：第 1 张 `reveal`，其后 `reveal d1/d2/d3`——`$i` 从 0 起，`$i?' d'.$i:''` 精确复现。

- [ ] **Step 3: 写近况横滚区（4 张卡用 home.newscard.* snippet）**

```php
<!-- ============ L4 我们的近况（浅） ============ -->
<section class="section section-light">
  <div class="container">
    <div class="text-center" style="margin-bottom:8px">
      <span class="eyebrow green reveal"><?= snip('home.news.eyebrow') ?></span>
      <h2 class="section-title reveal d1"><?= snip('home.news.title') ?></h2>
    </div>
    <div class="news-scroller reveal d2">
<?php for ($n = 1; $n <= 4; $n++): ?>
      <div class="news-card" data-demo="打开新闻详情：<?= $n === 1 ? '平台技术升级' : ($n === 2 ? '交互式课程上线' : ($n === 3 ? '顶刊发表' : '国际交流')) ?>">
        <div class="nc-thumb"><?= snip("home.newscard.$n.thumb") ?></div>
        <div class="nc-body"><div class="nc-date"><?= snip("home.newscard.$n.date") ?></div><h4><?= snip("home.newscard.$n.title") ?></h4><p><?= snip("home.newscard.$n.body") ?></p></div>
      </div>
<?php endfor; ?>
    </div>
    <div class="text-center"><a href="about.php#news" class="link-more reveal"><?= snip('home.news.link') ?></a></div>
  </div>
</section>
```

> `data-demo` 文案是各卡不同的固定串，保留原样（属交互提示，非可编辑文案，硬编即可）。

- [ ] **Step 4: 写合作伙伴 + 联系区（循环 partners）+ 页脚 + 脚本**

```php
<!-- ============ L5 合作伙伴 + 联系我们（深） ============ -->
<section class="section section-dark" id="contact">
  <div class="container">
    <div class="text-center" style="margin-bottom:36px">
      <span class="eyebrow reveal"><?= snip('home.contact.eyebrow') ?></span>
      <h2 class="section-title reveal d1"><?= snip_raw('home.contact.title') ?></h2>
    </div>
    <div class="logo-wall reveal" style="margin-bottom:40px">
<?php foreach ($partners as $p): ?>
      <a class="logo-chip" data-demo="<?= e($p['demo']) ?>"><span class="mark"><?= e($p['mark']) ?></span><?= e($p['name']) ?><?= $p['sub'] !== '' ? '<small>' . e($p['sub']) . '</small>' : '' ?></a>
<?php endforeach; ?>
    </div>
    <div class="text-center">
      <h3 style="color:#fff;font-size:22px"><?= snip('home.contact.h3') ?></h3>
      <p class="section-sub" style="margin:10px auto 0"><?= snip('contact.email') ?> · <?= snip('contact.org') ?></p>
    </div>
    <form class="feedback" id="contact-form">
      <div class="fb-row">
        <input type="text" name="name" placeholder="你的称呼" required>
        <input type="email" name="email" placeholder="邮箱" required>
      </div>
      <textarea name="message" placeholder="简单描述你的靶点 / 需求 / 合作意向" required></textarea>
      <button type="submit" class="btn btn-green fb-submit">提交反馈</button>
    </form>
  </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>

<script src="assets/js/main.js"></script>
<script src="assets/js/hero-anim.js"></script>
</body>
</html>
```

- [ ] **Step 5: Lint + 预览验证**

Run: `php -l public/index.php`
Expected: `No syntax errors detected`.

- [ ] **Step 6: 启动预览并核对**

在 `.claude/launch.json` 确认/新增名为 `mabseek-php` 的配置（`runtimeExecutable: "php"`, `runtimeArgs: ["-S","localhost:8777","-t","public"]`, `port: 8777`）。用 `preview_start` 起服务，`preview_snapshot` 打开 `/index.php`，与根下 `index.html`（`python3 -m http.server` 或直接比对源）逐区核对：导航 6 项 + CTA、Hero 三行、能做什么、四大痛点 4 格（含 →解法）、近况 4 卡、logo 墙 4 个、联系表单、页脚。`preview_console_logs` 无错。

- [ ] **Step 7: Commit**

```bash
git add public/index.php .claude/launch.json bin/seed.php
git commit -m "feat(mabseek): dynamic index.php (pain/partners from DB, copy from snippets)"
```

---

## Task 11: technology.php

**Files:**
- Create: `mabseek/public/technology.php`
- Modify: `mabseek/bin/seed.php`（补 tech snippet 清单）

**snippet 清单（补入 seed，逐字）：** 键 → 值
- `tech.hero.eyebrow` → `技术平台`
- `tech.hero.title` → `从数据到验证，<span class="txt-neon">一条自主可控的抗体发现闭环</span>`
- `tech.hero.sub` → `AI 智能中枢编排干实验设计与湿实验验证，端到端可追溯——把分散数月的流程，压缩为连续、可下单的闭环。`
- `tech.hero.cta` → `免费试用 Antibody Agent →`
- `tech.arch.eyebrow` → `整体技术架构`
- `tech.arch.title` → `以 <span class="txt-neon">AI 智能中枢</span> 编排的干湿闭环`
- `tech.arch.sub` → `把鼠标移到任一环节查看详情（触屏点击展开）。`
- `tech.arch.cta` → `进入 Antibody Agent →`
- `tech.case.eyebrow` → `经典案例`
- `tech.case.title` → `安巴韦单抗 / 罗米司韦单抗`
- `tech.case.sub` → `中国首个自主知识产权新冠中和抗体，已获批上市。`
- `tech.case.cta` → `用 Antibody Agent 开始你的项目 →`
- 三支柱各字段：`tech.p1.*`（Data to train）、`tech.p2.*`（AI for science）、`tech.p3.*`（Wet lab to validate），字段 `eyebrow/title/sub/point1/point2/point3/link`，值逐字取自 `technology.html` 第 96–152 行。

> **架构图节点（arch-node 详情）与三维案例（case-dim）** 属交互结构+说明，**硬编在模板**（§4.4 范围边界，留在代码）。仅上方 eyebrow/title/sub/CTA 与三支柱文字走 snippet。

- [ ] **Step 1: 补 tech snippets 到 bin/seed.php 并运行**

在 seed.php 追加 `$S_tech = [...]`（按上方清单，逐字），`foreach Snippets::seed`。Run: `php bin/seed.php`。

- [ ] **Step 2: 写 technology.php**

复制 `technology.html`；顶部：
```php
<?php
require __DIR__ . '/../app/bootstrap.php';
$active = 'technology'; $navOnDark = true; $contactHref = 'index.php#contact';
?>
```
`<head>` 逐字保留（title/description 不变）；`<header>`→`include nav.php`；三支柱与页头/案例区的 eyebrow/title/sub/points/link/cta 用 `snip`/`snip_raw`（含标记的用 `snip_raw`）；架构图整块 `arch-diagram` 逐字保留；`agent.html`→`agent.php`、`index.html#contact` 已由 nav 处理；`<footer>`→`include footer.php`；脚本 `main.js` 保留。

- [ ] **Step 3: Lint + 预览核对**

Run: `php -l public/technology.php`；`preview` 打开 `/technology.php`，核对页头、架构图（悬停/点击展开正常）、三支柱三段、经典案例三维、CTA、页脚。`preview_console_logs` 无错。

- [ ] **Step 4: Commit**

```bash
git add public/technology.php bin/seed.php
git commit -m "feat(mabseek): dynamic technology.php (copy from snippets; arch diagram kept in code)"
```

---

## Task 12: agent.php

**Files:**
- Create: `mabseek/public/agent.php`
- Modify: `mabseek/bin/seed.php`（补 agent snippet 清单）
- Modify: `mabseek/app/repositories/*` 无需改

**snippet 清单（补入 seed，逐字，取自 `agent.html`）：** Hero eyebrow/title/lead/两个 CTA/note；四大能力标题区 eyebrow/title；案例示范 eyebrow/title/sub；干湿闭环 eyebrow/title/sub；wetlab 卡标题/正文/tag/按钮；智能体矩阵 eyebrow/title/sub；CTA 区标题/副/按钮。键前缀 `agent.`。

> **四大能力 4 张 card**、**智能体矩阵 8 个 chip**、**flow 5 步**、**案例示范 .case-anim**（含动画结构）——建议：四大能力与智能体矩阵文字**走 content_cards**（分组 `agent_capability`、`agent_matrix`）以便后台增删改；flow 五步与 case-anim 结构含大量内联 style 与动画锚点，**硬编在模板**（结构即功能）。据此本任务需在 seed 增补两组 content_cards。

**content_cards 增补（seed，逐字）：**
- `agent_capability`（4 条，字段 icon/title/body，extra 存 `{"ico_class":""|"green"}`）：
  - 🔍/文献检索问答/`接入领域知识库与全文文献，秒级定位机制、方法与前沿进展，答案可溯源。`/ico_class ``
  - 🧬/抗体序列设计/`基于靶点一句话生成候选序列，自动完成人源化与可开发性优化。`/ico_class `green`
  - 📈/亲和力预测/`结合能计算 + 机器学习打分，动手实验前完成虚拟筛选与排序。`/ico_class ``
  - 🧩/结构分析/`结构建模、表位识别与对接分析，理解「为什么结合、结合在哪里」。`/ico_class `green`
- `agent_matrix`（8 条，字段 icon/title/body，extra 存 `{"ai_bg":"var(--purple-050)|var(--green-100)","active":true|false,"link_href":"","link_text":""}`）：逐字取自 `agent.html` 第 192–201 行（Antibody Agent active + SciencePal 带外链 `https://sciencepal.ai`「了解 SciencePal ↗」）。

- [ ] **Step 1: 补 agent snippets + 两组 content_cards 到 seed，运行**

Run: `php bin/seed.php`（幂等）。校验 `content_cards` 新分组计数：
```bash
php -r 'require "app/bootstrap.php"; foreach(["agent_capability","agent_matrix"] as $g){$q=db()->prepare("SELECT COUNT(*) FROM content_cards WHERE grp=?");$q->execute([$g]);printf("%s=%d\n",$g,$q->fetchColumn());}'
```
Expected: `agent_capability=4  agent_matrix=8`.

- [ ] **Step 2: 写 agent.php**

复制 `agent.html`；顶部 bootstrap + `$active='agent'; $navOnDark=false; $contactHref='index.php#contact';` + 取 `$caps=(new Collection('content_cards'))->published("grp='agent_capability'"); $matrix=(new Collection('content_cards'))->published("grp='agent_matrix'");`。`<head>` 内含页面专属 `<style>`，**逐字保留**。`<header>`→nav include。Hero/各区 eyebrow/title/sub/CTA 走 snip。四大能力 `grid-4` 循环 `$caps`（复现 `reveal`/`d1..d3` 与 `.ico`/`.ico green`）。智能体矩阵两个 `grid-4` 循环 `$matrix`（前 4 / 后 4；复现 active、ai 底色、SciencePal 外链）。flow 五步 + case-anim + wetlab 卡结构逐字保留（wetlab 卡文字走 snip）。`<footer>`→include。脚本 `main.js`+`agent-demo.js`+`agent-cases.js` 保留。

- [ ] **Step 3: Lint + 预览核对**

Run: `php -l public/agent.php`；预览 `/agent.php`：对话演示动画正常、四大能力 4 卡、案例示范 3 段动图、干湿闭环 5 步 + wetlab 卡、矩阵 8 chip（Antibody active、SciencePal 外链）、CTA、页脚。console 无错。

- [ ] **Step 4: Commit**

```bash
git add public/agent.php bin/seed.php
git commit -m "feat(mabseek): dynamic agent.php (capabilities+matrix from DB; demos kept in code)"
```

---

## Task 13: education.php

**Files:**
- Create: `mabseek/public/education.php`
- Modify: `mabseek/bin/seed.php`（补 edu snippet 清单）

**范围：** 播放器/弹幕/知识图谱/知识地图（章节树）/折叠资源为**交互组件，硬编在模板**（§4.4）。可后台化的是：页头、课程 Banner 文案、三栏 info-card（课程简介/主讲团队/育人理念）、各区 eyebrow/title/sub、跨板块联动文案、讲座/成长资源两列卡片。

**snippet 清单（补 seed，逐字，取自 `education.html`）：** `edu.hero.*`（breadcrumb 尾段/eyebrow/title/lead）、`edu.banner.*`（tag/标题/副）、`edu.video.eyebrow/title`、`edu.kg.eyebrow/title/sub`、`edu.map.eyebrow/title/sub`、`edu.res.eyebrow/title/sub`、`edu.link.tag/title/body/cta`、`edu.lecture.*`、`edu.grow.*`。

**content_cards 增补（seed，逐字）：**
- `edu_info`（3 条 info-card）：课程简介（ul 列表 4 项存 extra.items）、主讲团队（结构特殊：含两个 team-mini + 一段说明，建议此卡**整体硬编**，不入 content_cards）、育人理念（ul 4 项）。→ 仅「课程简介」「育人理念」入 `edu_info`（extra.items 数组 + icon + ico_style）；「主讲团队」卡硬编保留（含头像结构）。
- `edu_lecture`（3 条：官方讲座/民间交流沙龙/二次传播，字段 icon/title/body）。
- `edu_grow`（3 条：成长干货资源库/会议与培训/资助与基金，字段 icon/title/body）。
- 课时卡（lesson-card 4 张）、章节树（kmap）、资源折叠（accordion）——含大量交互锚点与 data-demo，**硬编保留**。

- [ ] **Step 1: 补 edu snippets + edu_info/edu_lecture/edu_grow 到 seed，运行**

Run: `php bin/seed.php`；校验三组计数（`edu_info=2 edu_lecture=3 edu_grow=3`）。

- [ ] **Step 2: 写 education.php**

复制 `education.html`；顶部 bootstrap + `$active='education'; $navOnDark=false; $contactHref='index.php#contact';` + 取 `edu_info/edu_lecture/edu_grow`。`<head>` 专属 `<style>` 逐字保留。`<header>`→nav。页头/Banner/三栏（简介+理念循环，团队卡硬编）/各区标题走 snip；讲座与成长资源两列循环对应 content_cards（复现 `.card` + `<h3>` + `<p>` 与 eyebrow 颜色）。播放器/弹幕/知识图谱/知识地图/资源折叠整块逐字保留。`<footer>`→include。脚本 `main.js`+`knowledge-graph.js`+尾部内联（弹幕/折叠）逐字保留。

- [ ] **Step 3: Lint + 预览核对**

Run: `php -l public/education.php`；预览 `/education.php`：弹幕滚动、折叠面板展开、知识图谱交互、课时卡、章节树、三栏、讲座/成长两列、跨板块联动、页脚。console 无错。

- [ ] **Step 4: Commit**

```bash
git add public/education.php bin/seed.php
git commit -m "feat(mabseek): dynamic education.php (info/lecture/grow from DB; widgets kept in code)"
```

---

## Task 14: forum.php

**Files:**
- Create: `mabseek/public/forum.php`
- Modify: `mabseek/bin/seed.php`（补 forum snippet 清单）

**snippet 清单（补 seed，逐字，取自 `forum.html`）：** `forum.hero.*`（breadcrumb 尾/eyebrow/title(含 br+grad)/lead）、搜索框 placeholder + 三个示例 chip 文案、`forum.hot.eyebrow/title/sub`、每日/每周 tab 文案、`forum.feed.note`（算法+编辑推荐…）、加载更多按钮、四条内容线 eyebrow/title(含 grad)、关注动态区文案（eyebrow/title/body + 关注卡 张老师/12.8k 获赞）、冷启动卡（tag/title/body + 5–10/每周官方更新/4/核心内容线）。

**渲染集合：** 热榜 → `forum_hot`（day/week 两 `<ol>`，按 list 过滤、rank 排序）；内容流 → `forum_posts`（8 帖，复现 cover img/grad + variant + toptag + tags + 头像 + likes + data-cat + data-demo）；四条内容线 → `content_cards.forum_line`（Task 8 已 seed）。

- [ ] **Step 1: 补 forum snippets 到 seed，运行**

Run: `php bin/seed.php`。

- [ ] **Step 2: 写 forum.php —— 页头 + 热榜（循环 forum_hot）**

```php
<?php
require __DIR__ . '/../app/bootstrap.php';
$active = 'forum'; $navOnDark = false; $contactHref = 'index.php#contact';
$hotAll = (new Collection('forum_hot'))->published();
$hotDay  = array_values(array_filter($hotAll, fn($r) => $r['list'] === 'day'));
$hotWeek = array_values(array_filter($hotAll, fn($r) => $r['list'] === 'week'));
$posts   = (new Collection('forum_posts'))->published();
$lines   = (new Collection('content_cards'))->published("grp = 'forum_line'");
?>
```
热榜两 `<ol>` 用 helper 输出行：
```php
<?php foreach ($hotDay as $r): ?>
      <li class="hot-row" data-demo="打开帖子详情"><span class="hot-rank"><?= (int)$r['rank'] ?></span><span class="hot-title"><?= e($r['title']) ?></span><span class="hot-cat"><?= e($r['category']) ?></span><span class="hot-fire"><?= e($r['heat']) ?></span></li>
<?php endforeach; ?>
```
（week 同理，外层 `<ol ... data-list="week" hidden>`。）

- [ ] **Step 3: 写内容流（循环 forum_posts，复现 cover 两型）**

```php
<div class="feed reveal d1" id="feed">
<?php foreach ($posts as $p):
    $tags = array_filter(array_map('trim', explode(',', $p['tags'])));
    $avStyle = $p['author_avatar_style'] !== '' ? ' style="' . e($p['author_avatar_style']) . '"' : '';
?>
  <div class="post" data-cat="<?= e($p['category']) ?>" data-demo="打开帖子详情">
<?php if ($p['cover_type'] === 'img'):
        $emoji = ['proto'=>'🧪','bio'=>'🧬','pit'=>'💊'][$p['category']] ?? '🧬';
        $gradClass = 'grad' . ($p['cover_variant'] ? ',\'' . $p['cover_variant'] . '\'' : '');
?>
    <div class="cover"><img src="<?= e($p['cover_ref']) ?>" alt="" onerror="this.parentElement.classList.add('grad'<?= $p['cover_variant'] ? ",'" . e($p['cover_variant']) . "'" : '' ?>);this.remove();this.parentElement.innerHTML='<?= e($emoji) ?>'"><span class="toptag"><?= e($p['toptag']) ?></span></div>
<?php else: ?>
    <div class="cover grad<?= $p['cover_variant'] ? ' ' . e($p['cover_variant']) : '' ?>"><span></span><?= e($p['cover_ref']) ?></div>
<?php endif; ?>
    <div class="pbody"><h4><?= e($p['title']) ?></h4>
      <div class="ptags"><?php foreach ($tags as $t): ?><span><?= e($t) ?></span><?php endforeach; ?></div>
      <div class="pfoot"><span class="who"><span class="av"<?= $avStyle ?>><?= e($p['author_avatar_char']) ?></span><?= e($p['author_name']) ?></span><span class="like"><?= e($p['likes']) ?></span></div></div>
  </div>
<?php endforeach; ?>
</div>
```

> **核对：** img 型帖子的 onerror emoji 与现有源一致（protocol→🧪、bioinfo→🧬(g2)、adc→💊）。grad 型的 emoji 存 `cover_ref`（📄/💼/🦠/🔬/🧠），variant g2/g3 复现。此段务必与 `forum.html` 129–176 行逐帖比对。

- [ ] **Step 4: 写四条内容线 + 关注/冷启动 + 页脚 + 内联脚本**

四条内容线循环 `$lines`（复现 `.ico` 底色从 extra.ico_style、`<ul><li>` 从 extra.items）。关注动态与冷启动区文字走 snip（含「张老师 · 抗体工程 · 12.8k 获赞」「5–10 每周官方更新」「4 核心内容线」）。`<footer>`→include。尾部内联脚本（标签筛选 + 热榜切换）逐字保留。搜索区（forum-search，属 P3c 全局 CSS 组件）markup 逐字保留，placeholder/示例 chip 文案走 snip。

- [ ] **Step 5: Lint + 预览核对**

Run: `php -l public/forum.php`；预览 `/forum.php`：搜索框、每日/每周热榜切换（各 6 行）、标签筛选、瀑布流 8 帖（封面两型、头像底色、likes）、四条内容线、关注/冷启动。console 无错。

- [ ] **Step 6: Commit**

```bash
git add public/forum.php bin/seed.php
git commit -m "feat(mabseek): dynamic forum.php (hot/posts/lines from DB; filters kept in code)"
```

---

## Task 15: about.php

**Files:**
- Create: `mabseek/public/about.php`
- Modify: `mabseek/bin/seed.php`（补 about snippet 清单）

**snippet 清单（补 seed，逐字，取自 `about.html`）：** `about.hero.*`（breadcrumb 尾/eyebrow/title(含 grad)/lead/4 个 tag 锚文案）、`about.team.eyebrow/title/sub`、成果卡区无（走 content_cards.about_achievement，已 seed）、团队免责说明 `about.team.disclaimer`、位置图说明 `about.loc.cap`、`about.platform.*`（eyebrow/title/正文/三条 干湿环 li/按钮）、`about.news.eyebrow/title` + 四个筛选 chip 文案 + 了解更多按钮、`about.intl.*`（eyebrow/title(含 grad)/sub + 中印尼卡标题/正文 + timeline 三条 + PRA 卡标题/正文）、`about.contact.*`（eyebrow/title(含 txt-neon)/联系行）。

**渲染集合：** 双负责人 → `team_members`（复现 `.ph`/`.ph.g2` + nm/aff/dir + role/role.green）；成果卡 → `content_cards.about_achievement`；新闻列表 → `news`（5 条，复现 date d/m + tag 颜色按 category + data-nc + data-demo）。

- [ ] **Step 1: 补 about snippets 到 seed，运行**

Run: `php bin/seed.php`。

- [ ] **Step 2: 写 about.php —— 顶部 + 团队（循环 team_members + 成果卡）**

```php
<?php
require __DIR__ . '/../app/bootstrap.php';
$active = 'about'; $navOnDark = false; $contactHref = '#contact';
$team = (new Collection('team_members'))->published();
$achv = (new Collection('content_cards'))->published("grp = 'about_achievement'");
$news = (new Collection('news'))->published();
?>
```
双负责人循环（复现 `.ph`/`.ph.g2`、role 类型）：
```php
<div class="leads reveal d1">
<?php foreach ($team as $m):
    $phClass = 'ph' . ($m['avatar_variant'] ? ' ' . e($m['avatar_variant']) : '');
    $roleClass = 'role' . ($m['role_type'] === 'ai' ? ' green' : '');
?>
  <div class="lead-card">
    <div class="<?= $phClass ?>"><?= e($m['avatar_char']) ?></div>
    <div>
      <div class="nm"><?= e($m['name']) ?></div>
      <div class="aff"><?= e($m['affiliation']) ?></div>
      <div class="dir"><?= e($m['direction']) ?></div>
      <span class="<?= $roleClass ?>"><?= e($m['role_label']) ?></span>
    </div>
  </div>
<?php endforeach; ?>
</div>
```
成果卡 `grid-2` 循环 `$achv`（复现 `reveal`/`d1..d3` + `<h3 style="font-size:16px">` + emoji icon）。团队免责说明 + 位置图（location.png + onerror 降级 🏛️，markup 逐字保留，说明走 snip）。

- [ ] **Step 3: 写平台介绍 + 新闻列表（循环 news）+ 国际合作 + 联系 + 页脚**

平台介绍区文字走 snip（含三条干/湿/环 li）。新闻列表循环 `$news`：
```php
<div id="news-list">
<?php foreach ($news as $it):
    $tagClass = $it['category'] === 'res' ? 'tag green' : 'tag';
    $tagText  = ['res'=>'科研类','edu'=>'育人类','daily'=>'日常活动'][$it['category']] ?? '';
?>
  <div class="news-item" data-nc="<?= e($it['category']) ?>" data-demo="打开新闻详情"><div class="date"><div class="d"><?= e($it['date_day']) ?></div><div class="m"><?= e($it['date_ym']) ?></div></div><div class="n-body"><span class="<?= $tagClass ?>" style="font-size:11px"><?= e($tagText) ?></span><h4><?= e($it['title']) ?></h4><p><?= e($it['summary']) ?></p></div></div>
<?php endforeach; ?>
</div>
```
> **核对现有源：** res→`tag green`「科研类」，edu→`tag`「育人类」，daily→`tag`「日常活动」。date_ym 现有源为 `2026·08`（新闻列表）与 index 卡 `2026 · 08` 不同——各自 seed 值已分别存储，勿混用。

国际合作区 + 中印尼卡 + timeline + PRA 卡文字走 snip（PRA 卡正文用已修正的「…并积极与国内外高校、科研院所及行业企业探索共建联合实验室的合作机会。」）。联系区（section-dark #contact）走 snip + 表单逐字保留。`<footer>`→include。脚本 `main.js` + 尾部内联新闻筛选逐字保留。

- [ ] **Step 4: Lint + 预览核对**

Run: `php -l public/about.php`；预览 `/about.php`：双负责人卡（张/马、role 配色）、4 成果卡、免责说明、位置图、平台介绍（干/湿/环）、新闻 5 条（筛选正常）、国际合作（timeline + PRA）、联系表单、页脚。console 无错。

- [ ] **Step 5: Commit**

```bash
git add public/about.php bin/seed.php
git commit -m "feat(mabseek): dynamic about.php (team/news/achievements from DB)"
```

---

## Task 16: 后台外壳 + 登录（admin.php + login + shell）

**Files:**
- Create: `mabseek/public/admin.php`
- Create: `mabseek/app/admin/login.php`
- Create: `mabseek/app/admin/shell.php`（页头/侧栏/flash，复用 style.css）

- [ ] **Step 1: 写 admin.php（前置控制器）**

```php
<?php
require __DIR__ . '/../app/bootstrap.php';

// 登出
if (($_GET['action'] ?? '') === 'logout') { auth_logout(); redirect('admin.php'); }

// 未登录 → 登录流程
if (!auth_check()) {
    $error = null;
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        csrf_verify_or_die();
        $u = trim((string)($_POST['username'] ?? ''));
        $p = (string)($_POST['password'] ?? '');
        $ip = client_ip();
        if (auth_is_locked($ip, $u)) {
            $error = '尝试过于频繁，请 15 分钟后再试。';
        } elseif ($u !== '' && auth_verify_credentials($u, $p)) {
            auth_record_attempt($ip, $u, true);
            auth_login_user($u);
            audit('login');
            redirect('admin.php');
        } else {
            auth_record_attempt($ip, $u, false);
            audit('login_failed', 'user', $u);
            $error = '用户名或密码错误';   // 统一模糊
        }
    }
    include __DIR__ . '/../app/admin/login.php';
    exit;
}

// 已登录 → 路由到模块
$module = preg_replace('/[^a-z_]/', '', (string)($_GET['m'] ?? 'dashboard'));
$allowed = ['dashboard','news','forum_posts','forum_hot','team','partners','cards','snippets','password'];
if (!in_array($module, $allowed, true)) $module = 'dashboard';

$moduleFile = __DIR__ . '/../app/admin/' . $module . '.php';
include __DIR__ . '/../app/admin/shell.php';       // 打开外壳（含侧栏、flash 起始）
include $moduleFile;                               // 模块内容
include __DIR__ . '/../app/admin/shell_end.php';   // 闭合外壳
```

- [ ] **Step 2: 写 login.php（复用 page-hero + feedback 样式）**

登录表单：单列卡片，复用 `.container`/`.card`/`.btn-green`；含 `csrf_field()`；显示 `$error`（若有）；`autocomplete` 合理设置；不泄露账号存在性。页面顶部可 include nav？—— 后台独立，**不含前台 nav**，用极简品牌头。给出完整 HTML（`<!DOCTYPE>`…`</html>`），引用 `assets/css/style.css`。

- [ ] **Step 3: 写 shell.php + shell_end.php（侧栏菜单 + flash）**

shell.php：`<!DOCTYPE>` + `<head>`（引 style.css）+ 顶栏（品牌 + 当前用户 `e($_SESSION['uid'])` + 登出链接）+ 左侧菜单（9 模块链接 `admin.php?m=…`，当前高亮）+ `<main>` 开始 + 渲染 `flash_take()`。shell_end.php：闭合 `</main></body></html>`。菜单与卡片一律复用现有 `.btn`/`.card`/`.tag` 令牌，不新增前台 CSS；后台专属细节样式写入 shell.php 内联 `<style>`（作用域仅后台）。

- [ ] **Step 4: Lint + 预览登录流程**

Run: `php -l public/admin.php && php -l app/admin/login.php && php -l app/admin/shell.php`；预览 `/admin.php`：显示登录页；错误密码提交 5 次后显示锁定文案；正确密码（`admin`/`mabseek2026`）登录进入仪表盘外壳。检查 cookie 具 HttpOnly（`preview_network` 或 eval `document.cookie` 不含 sid）。

- [ ] **Step 5: Commit**

```bash
git add public/admin.php app/admin/login.php app/admin/shell.php app/admin/shell_end.php
git commit -m "feat(mabseek): admin entry — login (rate-limited, CSRF), shell, routing"
```

---

## Task 17: 仪表盘 dashboard.php

**Files:**
- Create: `mabseek/app/admin/dashboard.php`

- [ ] **Step 1: 写 dashboard.php**

显示：各集合条目数（`(new Collection($t))->count()` 遍历）、snippets 数、最近 10 条 `audit_log`（`e()` 转义）、当前登录用户与会话信息。纯读，无表单。

- [ ] **Step 2: Lint + 预览**

Run: `php -l app/admin/dashboard.php`；预览登录后 `/admin.php` 显示概览数字与审计列表。

- [ ] **Step 3: Commit**

```bash
git add app/admin/dashboard.php
git commit -m "feat(mabseek): admin dashboard (counts + recent audit)"
```

---

## Task 18: 集合 CRUD 通用组件 + news 模块

**Files:**
- Create: `mabseek/app/admin/crud.php`（通用列表/表单/保存/删除逻辑，供各集合模块复用）
- Create: `mabseek/app/admin/news.php`
- Test: `mabseek/tests/test_crud_fields.php`（校验字段定义完整）

**设计：** `crud.php` 提供 `admin_crud(array $cfg)`，`$cfg` 含 `table`、`title`、`fields`（每字段 name/label/type[text|textarea|select|checkbox|image]/options/required）、可选 `where`。处理 `?a=new|edit|save|delete&id=`，POST 走 `csrf_verify_or_die()` + PRG + `audit()`。列表支持发布切换与排序编辑。各集合模块只声明 `$cfg` 并调用 `admin_crud($cfg)`。

- [ ] **Step 1: 写 test_crud_fields.php（失败先行）**

```php
<?php
putenv('MABSEEK_DB=:memory:');
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/repositories/Collection.php';
// news 字段集合必须与表列（除时间戳/id）一致
$newsFields = ['date_day','date_ym','category','title','summary','image','sort','published'];
$cols = db()->query("PRAGMA table_info(news)")->fetchAll(PDO::FETCH_COLUMN, 1);
$editable = array_values(array_diff($cols, ['id','created_at','updated_at']));
sort($newsFields); sort($editable);
check($newsFields === $editable, 'news editable fields match schema');
```

- [ ] **Step 2: 运行，确认失败或通过**

Run: `php tests/run.php`
Expected: 若字段列表与 schema 不符则 FAIL（本步用于锁定字段契约）。修正 `$newsFields` 直至与表列一致，PASS。

- [ ] **Step 3: 写 crud.php**

实现 `admin_crud($cfg)`：列表视图（表格：各字段摘要 + 发布状态 + 编辑/删除；顶部“新增”按钮；排序列可改）；表单视图（按 `fields` 生成输入，`old()` 回填并 `e()` 转义，`csrf_field()`；image 类型调用 Task 25 的上传处理）；save（`Collection::create/update`，白名单已在仓库层保证）；delete（二次确认，前端 `confirm` + 后端校验 CSRF）；每次写操作 `audit($action,$table,$id)`。所有输出 `e()`。

- [ ] **Step 4: 写 news.php**

```php
<?php
admin_crud([
  'table' => 'news',
  'title' => '新闻与活动',
  'fields' => [
    ['name'=>'date_day','label'=>'日(如 08)','type'=>'text','required'=>true],
    ['name'=>'date_ym','label'=>'年月(如 2026·08)','type'=>'text','required'=>true],
    ['name'=>'category','label'=>'分类','type'=>'select','options'=>['res'=>'科研类','edu'=>'育人类','daily'=>'日常活动'],'required'=>true],
    ['name'=>'title','label'=>'标题','type'=>'text','required'=>true],
    ['name'=>'summary','label'=>'摘要','type'=>'textarea','required'=>true],
    ['name'=>'image','label'=>'配图(可选)','type'=>'image'],
    ['name'=>'sort','label'=>'排序','type'=>'text'],
    ['name'=>'published','label'=>'发布','type'=>'checkbox'],
  ],
]);
```

- [ ] **Step 5: Lint + 预览 CRUD**

Run: `php -l app/admin/crud.php && php -l app/admin/news.php`；预览 `/admin.php?m=news`：列出 5 条 → 新增一条 → 前台 about.php 出现 → 编辑标题 → 前台更新 → 下架 → 前台消失 → 删除。每步核对 `audit_log`。缺 CSRF 提交返回 403（可用 eval 去掉 token 提交验证）。

- [ ] **Step 6: Commit**

```bash
git add app/admin/crud.php app/admin/news.php tests/test_crud_fields.php
git commit -m "feat(mabseek): generic admin CRUD + news module (publish/sort/audit/CSRF)"
```

---

## Task 19–23: 其余集合模块（forum_posts / forum_hot / team / partners / cards）

**Files:**
- Create: `mabseek/app/admin/forum_posts.php`
- Create: `mabseek/app/admin/forum_hot.php`
- Create: `mabseek/app/admin/team.php`
- Create: `mabseek/app/admin/partners.php`
- Create: `mabseek/app/admin/cards.php`

每个模块声明 `$cfg` 调 `admin_crud()`。字段定义如下（与各表列一致）：

- [ ] **Step 1: forum_posts.php**

fields: category(select: all/pit/proto/paper/bio/job → 用现有 data-cat 取值 `pit/proto/paper/bio/job`)、cover_type(select img/grad)、cover_ref(text，img=路径/grad=emoji)、cover_variant(select ''/g2/g3)、toptag(text)、title(text)、tags(text，逗号分隔)、author_name(text)、author_avatar_char(text)、author_avatar_style(text)、likes(text)、sort、published。

- [ ] **Step 2: forum_hot.php**

fields: list(select day/week)、rank(text)、title(text)、category(text，如 `# 文献精读`)、heat(text，如 `🔥 1.2k`)、sort、published。

- [ ] **Step 3: team.php**

fields: name、affiliation、direction(textarea)、role_label、role_type(select science/ai)、avatar_char、avatar_variant(select ''/g2)、sort、published。

- [ ] **Step 4: partners.php**

fields: name、mark、sub、logo_image(image，可选)、demo(text)、sort、published。

- [ ] **Step 5: cards.php**

fields: grp(select，列出已知分组 home_pain/about_achievement/forum_line/agent_capability/agent_matrix/edu_info/edu_lecture/edu_grow)、icon、title、body(textarea)、extra(textarea，JSON，附说明「结构化字段，如 fix/items/ico_style」)、sort、published。

- [ ] **Step 6: Lint + 预览每模块**

Run: `php -l app/admin/forum_posts.php app/admin/forum_hot.php app/admin/team.php app/admin/partners.php app/admin/cards.php`；逐模块预览列表 + 一次编辑 → 前台对应页更新。

- [ ] **Step 7: Commit**

```bash
git add app/admin/forum_posts.php app/admin/forum_hot.php app/admin/team.php app/admin/partners.php app/admin/cards.php
git commit -m "feat(mabseek): admin modules for forum_posts/forum_hot/team/partners/cards"
```

---

## Task 24: 文案片段模块 snippets.php

**Files:**
- Create: `mabseek/app/admin/snippets.php`

- [ ] **Step 1: 写 snippets.php**

按 `Snippets::allGrouped()` 分组展示，每组一节；每片段一行：label（中文）+ skey（灰字）+ 输入（type=text→`<input>`，textarea→`<textarea>`）；单个大表单一次性保存所有，或每组一个表单。POST：`csrf_verify_or_die()`，遍历提交项 `Snippets::updateValue($id,$value)`，`audit('update','snippet',$skey)`，PRG + flash。输出全部 `e()`。

> **安全提示：** 含 markup 的片段（如 `home.hero.title`）在前台用 `snip_raw` 渲染。后台编辑这些值即等于可注入 HTML——但后台是**已认证的单管理员**，且值仅来自受信任管理员。仍在编辑器旁标注「此项含版式标记，请谨慎编辑」。前台对片段值不做二次转义（保持版式），这是刻意权衡；纯文本片段（占绝大多数）经 `snip` 转义。

- [ ] **Step 2: Lint + 预览**

Run: `php -l app/admin/snippets.php`；预览 `/admin.php?m=snippets`：改 `footer.slogan` → 保存 → 所有前台页脚更新。

- [ ] **Step 3: Commit**

```bash
git add app/admin/snippets.php
git commit -m "feat(mabseek): admin snippets editor (grouped, CSRF, audit)"
```

---

## Task 25: 图片上传（严格校验）

**Files:**
- Create: `mabseek/app/admin/upload.php`（`handle_upload($field): ?string` 返回相对 URL 或 null）
- Test: `mabseek/tests/test_upload.php`

- [ ] **Step 1: 写 test_upload.php（失败先行，测校验函数纯逻辑）**

```php
<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/admin/upload.php';
// 扩展名映射
check(upload_ext_for_mime('image/png') === 'png', 'png mime → png');
check(upload_ext_for_mime('image/gif') === null, 'gif rejected');
check(upload_ext_for_mime('image/jpeg') === 'jpg', 'jpeg → jpg');
// 随机文件名格式
$n = upload_random_name('png');
check(preg_match('/^[a-f0-9]{32}\.png$/', $n) === 1, 'random name format');
```

- [ ] **Step 2: 运行，确认失败**

Run: `php tests/run.php`
Expected: FAIL —「upload_ext_for_mime not defined」。

- [ ] **Step 3: 写 upload.php**

```php
<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';

function upload_ext_for_mime(string $mime): ?string {
    return UPLOAD_ALLOWED[$mime] ?? null;
}
function upload_random_name(string $ext): string {
    return bin2hex(random_bytes(16)) . '.' . $ext;
}
/** 处理 $_FILES[$field]；成功返回相对 URL，失败抛异常，未上传返回 null */
function handle_upload(string $field): ?string {
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK)        throw new RuntimeException('上传失败(错误码 ' . $f['error'] . ')');
    if ($f['size'] > UPLOAD_MAX_BYTES)         throw new RuntimeException('文件超过 2MB 上限');
    if (!is_uploaded_file($f['tmp_name']))     throw new RuntimeException('非法上传');

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($f['tmp_name']);
    $ext = upload_ext_for_mime($mime);
    if ($ext === null)                         throw new RuntimeException('仅支持 JPG/PNG/WebP');
    if (getimagesize($f['tmp_name']) === false) throw new RuntimeException('文件不是有效图片');

    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0750, true);
    $name = upload_random_name($ext);
    $dest = UPLOAD_DIR . '/' . $name;
    if (!move_uploaded_file($f['tmp_name'], $dest)) throw new RuntimeException('保存失败');
    @chmod($dest, 0640);
    return UPLOAD_URL . '/' . $name;             // 前台相对 URL
}
```

- [ ] **Step 4: 运行测试，确认通过**

Run: `php tests/run.php`
Expected: PASS。

- [ ] **Step 5: 接入 crud.php 的 image 字段**

在 `crud.php` 保存逻辑：若字段 type=image 且有上传文件，调 `handle_upload()` 得 URL 存入该列；无上传则保留原值。表单显示当前图缩略 + `<input type=file accept="image/*">`。`try/catch` 上传异常 → flash 错误 + 不保存。

- [ ] **Step 6: 预览上传**

预览 news 或 partners 编辑：上传 png → 成功入库 + 前台显示；上传 .gif/.txt → 被拒并提示；上传 >2MB → 被拒。

- [ ] **Step 7: Commit**

```bash
git add app/admin/upload.php tests/test_upload.php app/admin/crud.php
git commit -m "feat(mabseek): strict image upload (finfo+getimagesize+2MB+random name) wired into CRUD"
```

---

## Task 26: 修改密码 password.php

**Files:**
- Create: `mabseek/app/admin/password.php`

- [ ] **Step 1: 写 password.php**

表单：当前密码 + 新密码 + 确认新密码 + `csrf_field()`。POST：`csrf_verify_or_die()`；校验当前密码 `auth_verify_credentials($_SESSION['uid'], $cur)`；新密码长度 ≥ 10 且两次一致；`auth_change_password()`；`audit('change_password')`；成功 flash + 保持登录（可选 `session_regenerate_id(true)`）。失败模糊提示。输出 `e()`。

- [ ] **Step 2: Lint + 预览**

Run: `php -l app/admin/password.php`；预览：错误当前密码被拒；正确修改后用新密码可登录、旧密码失效（重登验证）。改回 `mabseek2026` 以便后续验收（或记录新密码）。

- [ ] **Step 3: Commit**

```bash
git add app/admin/password.php
git commit -m "feat(mabseek): admin change-password (verify current, Argon2id rehash, audit)"
```

---

## Task 27: Nginx 配置样例 deploy/nginx.conf.sample

**Files:**
- Create: `mabseek/deploy/nginx.conf.sample`

- [ ] **Step 1: 写 nginx.conf.sample**

包含：`server_tokens off`；`root .../mabseek/public`；强制 HTTPS（80→443 跳转）；`index index.php`；整洁 URL `location / { try_files $uri $uri/ /index.php?$query_string; }` 及旧 `.html`→`.php` 301（`location = /index.html { return 301 /; }` 等 6 条 + `/` 映射）；PHP-FPM `location ~ \.php$ { fastcgi_pass unix:...; ... }`；**上传目录禁 PHP**：`location ^~ /assets/images/uploads/ { location ~ \.php$ { deny all; } }`（或 `fastcgi` 不匹配该前缀）；安全头（CSP 允许 `'self'` + inline style/script[现有站点用内联]、`img-src 'self' data:`；`X-Content-Type-Options nosniff`；`X-Frame-Options DENY`；`Referrer-Policy strict-origin-when-cross-origin`；`Strict-Transport-Security`）；注释说明 `app/`、`data/`、`bin/`、`tests/` 在 web 根外不可达，`data/mabseek.sqlite` 权限 0640 属主 php-fpm 用户。附 PHP 本地开发提示（`php -S localhost:8777 -t public`，仅开发）。

- [ ] **Step 2: 语法自检（若本机有 nginx）**

Run: `nginx -t -c $(pwd)/deploy/nginx.conf.sample 2>&1 | head || echo '本机无 nginx，跳过'`
Expected: 通过或提示跳过（样例含 server 块，实际部署需并入 http 上下文——文件顶部注释说明）。

- [ ] **Step 3: Commit**

```bash
git add deploy/nginx.conf.sample
git commit -m "feat(mabseek): nginx site sample — clean URLs, 301, upload no-exec, security headers"
```

---

## Task 28: 全站验证、文档更新、清理旧 HTML

**Files:**
- Modify: `mabseek/docs/architecture.md`（增补 P5 后端章节）
- Delete: 根下 6 个旧 `.html`（验证通过后）
- Modify: `.claude/launch.json`（默认 `mabseek-php`）

- [ ] **Step 1: 逐页最终核对（PHP 版 vs 旧 HTML）**

对 6 页各跑 `preview_snapshot` + `preview_console_logs` + `preview_network`：导航 6 项 + CTA 目标（index/about=`#contact`，余=`index.php#contact`）、页脚四列、各集合条目数与文字、深浅底节奏、交互（热榜/筛选/折叠/弹幕/图谱/对话演示/架构图）全部与旧版一致。记录任何差异并修复到对应 `public/*.php`。

- [ ] **Step 2: 后台完整走查**

登录 → 每模块 CRUD + 发布/排序 + 图片上传校验 + 改密 + CSRF 缺失被拒 + 锁定触发 + 审计记录齐全。

- [ ] **Step 3: seed 幂等复跑**

Run: `php bin/seed.php && php bin/seed.php`
Expected: 第二次全 `= … skip`；集合计数不变；已改文案不被覆盖。

- [ ] **Step 4: 更新 architecture.md**

新增「P5 · 后端内容管理」章节：方案 A 目录（public/app/data 隔离）、渲染/URL 模型、数据模型概览、安全基线要点、后台模块清单、seed 幂等、范围边界（交互组件留代码）。指向本 plan 与 spec。

- [ ] **Step 5: 删除旧 HTML（确认 PHP 版一致后）**

```bash
cd mabseek
git rm index.html technology.html agent.html education.html forum.html about.html
```

- [ ] **Step 6: 全量 lint + 测试**

Run: `find public app bin -name '*.php' -print0 | xargs -0 -n1 php -l | grep -v 'No syntax errors' || echo 'all clean'; php tests/run.php`
Expected: `all clean`；测试 `0 failed`.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "feat(mabseek): P5 verification, architecture docs, remove legacy static HTML"
```

---

## 完成标准回顾

- [ ] 6 页 PHP 动态渲染，前台与旧 HTML 逐区一致（导航/页脚/文字/节奏/交互）。
- [ ] 所有集合（news/forum_posts/forum_hot/team/partners/content_cards）+ snippets 可后台增删改，即时反映前台。
- [ ] seed 幂等，现有文案逐字入库，`admin`/`mabseek2026` 哈希创建。
- [ ] 安全基线：Argon2id、会话加固、登录锁定、CSRF、PDO 预处理、输出转义、上传严格校验、审计、web 根隔离、nginx 安全头/301/上传禁 PHP。
- [ ] 零第三方框架/依赖；不改视觉；不杜撰。
