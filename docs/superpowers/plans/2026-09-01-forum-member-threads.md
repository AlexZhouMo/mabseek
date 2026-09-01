# 论坛会员发帖（B）实现计划

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 让登录会员发布纯文本帖子，前台按最新展示、有独立详情页，作者可编辑/删除本人帖，管理员可下架/删除任意帖。

**Architecture:** 新建 `forum_threads` 表（外键 `user_id → users(id) ON DELETE CASCADE`），与 CMS 策展的 `forum_posts` 并存。领域逻辑集中在可单测的 `app/threads.php`。前台新增 `thread.php`/`thread-new.php`/`thread-edit.php` 与 `forum.php` 内「会员发布」板块；后台新增 `app/admin/threads.php`。发布即公开，以频率限制 + 停用拦截防刷，作者操作强校验 user_id。

**Tech Stack:** PHP 8 + SQLite（pdo_sqlite）、Argon2id 会话体系（复用 A 阶段）、服务端渲染、零依赖测试（`tests/run.php`）。

**关联 spec:** `docs/superpowers/specs/2026-09-01-forum-member-threads-design.md`

---

## 文件结构

**新增：** `app/threads.php`（领域逻辑）、`public/thread.php`（详情）、`public/thread-new.php`（发帖）、`public/thread-edit.php`（编辑/删除本人帖）、`app/admin/threads.php`（后台治理）、`tests/test_threads.php`。

**修改：** `app/config.php`（类别与限流常量）、`app/db.php`（`migrate()` 建表+索引）、`app/bootstrap.php`（require threads.php）、`public/forum.php`（会员发布板块+入口）、`public/admin.php`（模块白名单加 threads）、`app/admin/shell.php`（侧栏菜单）。

---

## Task 1: 配置常量

**Files:** Modify: `app/config.php`

- [ ] **Step 1: 在 `app/config.php` 的验证码常量段之后新增**

```php

// ── 论坛会员发帖 ──
const THREAD_CATEGORIES = [
    'pit'   => '# 实验踩坑',
    'proto' => '# Protocol 分享',
    'paper' => '# 文献精读',
    'bio'   => '# 生信工具',
    'job'   => '# 求职招聘',
];
const THREAD_RATE_MIN_SECONDS = 60;    // 两帖最小间隔
const THREAD_RATE_DAILY_MAX   = 20;    // 单会员 24 小时最多发帖数
```

- [ ] **Step 2: 校验 + 提交**

Run: `php -l app/config.php && php tests/run.php` — 预期 `0 failed`。
```bash
git add app/config.php
git commit -m "feat(forum): 会员发帖类别与限流常量"
```

---

## Task 2: 数据库迁移（forum_threads 表 + 索引）

**Files:** Modify: `app/db.php`（`migrate()` 内）；Test: `tests/test_db.php`

- [ ] **Step 1: 追加失败测试到 `tests/test_db.php` 末尾**

```php
// ── forum_threads 迁移断言 ──
$tables2 = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
check(in_array('forum_threads', $tables2, true), 'forum_threads 表存在');
$tcols = $pdo->query("PRAGMA table_info(forum_threads)")->fetchAll(PDO::FETCH_COLUMN, 1);
foreach (['user_id','category','title','body','status','created_at','updated_at'] as $c) {
    check(in_array($c, $tcols, true), "forum_threads.$c 列存在");
}
$idx2 = $pdo->query("SELECT name FROM sqlite_master WHERE type='index'")->fetchAll(PDO::FETCH_COLUMN);
check(in_array('idx_threads_status_created', $idx2, true), '索引 idx_threads_status_created 存在');
migrate($pdo);
$tcols2 = $pdo->query("PRAGMA table_info(forum_threads)")->fetchAll(PDO::FETCH_COLUMN, 1);
check(count(array_keys($tcols2, 'title')) === 1, '重复 migrate 后 title 仅一列');
```

- [ ] **Step 2: 运行，确认 FAIL**

Run: `php tests/run.php` — 预期「forum_threads 表存在」失败。

- [ ] **Step 3: 在 `migrate()` 末尾（users 回填之后、`}` 之前）追加**

```php
    $pdo->exec(<<<SQL
    CREATE TABLE IF NOT EXISTS forum_threads (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
      category TEXT NOT NULL,
      title TEXT NOT NULL,
      body TEXT NOT NULL,
      status TEXT NOT NULL DEFAULT 'published',
      created_at TEXT NOT NULL, updated_at TEXT NOT NULL
    );
    SQL);
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_threads_status_created ON forum_threads(status, created_at DESC)");
```

> `migrate()` 内不得调用 `iso_now()`（test_db.php 未加载 helpers.php）；此处仅常量 SQL，无时间戳。

- [ ] **Step 4: 运行，确认 PASS**

Run: `php tests/run.php` — 预期全绿、`0 failed`。

- [ ] **Step 5: 提交**

```bash
git add app/db.php tests/test_db.php
git commit -m "feat(forum): forum_threads 表迁移 + status/created 索引"
```

---

## Task 3: threads.php 校验函数 + bootstrap 装配

**Files:** Create: `app/threads.php`；Modify: `app/bootstrap.php`；Test: `tests/test_threads.php`

- [ ] **Step 1: 创建 `tests/test_threads.php`（先只测校验，失败）**

```php
<?php
putenv('MABSEEK_DB=:memory:');
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/threads.php';
db();

check(thread_validate_title('一个标题') === true, 'title 合法');
check(thread_validate_title('') === false, 'title 空被拒');
check(thread_validate_title('   ') === false, 'title 纯空白被拒');
check(thread_validate_title(str_repeat('字', 121)) === false, 'title 过长被拒');

check(thread_validate_body('正文内容') === true, 'body 合法');
check(thread_validate_body('') === false, 'body 空被拒');
check(thread_validate_body(str_repeat('字', 5001)) === false, 'body 过长被拒');

check(thread_valid_category('pit') === true, 'category 合法');
check(thread_valid_category('nope') === false, 'category 非法被拒');
```

- [ ] **Step 2: 运行，确认 FAIL**

Run: `php tests/run.php` — 预期「Failed opening required .../app/threads.php」。

- [ ] **Step 3: 创建 `app/threads.php`（先只放校验）**

```php
<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function thread_validate_title(string $v): bool { $n = mb_strlen(trim($v)); return $n >= 1 && $n <= 120; }
function thread_validate_body(string $v): bool { $n = mb_strlen(trim($v)); return $n >= 1 && $n <= 5000; }
function thread_valid_category(string $v): bool { return array_key_exists($v, THREAD_CATEGORIES); }
```

- [ ] **Step 4: 在 `app/bootstrap.php` 于 `require_once __DIR__ . '/members.php';` 之后新增**

```php
require_once __DIR__ . '/threads.php';
```

> `thread_valid_category` 依赖 `THREAD_CATEGORIES` 常量。test_threads.php 通过 `require db.php` 间接加载 `config.php`（db.php 首行 require config.php），常量可用。

- [ ] **Step 5: 运行，确认 PASS**

Run: `php tests/run.php` — 预期 `0 failed`。

- [ ] **Step 6: 提交**

```bash
git add app/threads.php app/bootstrap.php tests/test_threads.php
git commit -m "feat(forum): 帖子字段校验(title/body/category) + bootstrap 装配"
```

---

## Task 4: 创建 / 查询 / 公开列表

**Files:** Modify: `app/threads.php`、`tests/test_threads.php`

- [ ] **Step 1: 追加失败测试**

```php

// ── 创建 + 查询 + 公开列表 ──（用独立用户，末尾清理）
$now = iso_now();
db()->prepare('INSERT INTO users(username,password_hash,nickname,role,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?)')
    ->execute(['t_user', 'x', '发帖人', 'member', 'active', $now, $now]);
$uid = (int)db()->lastInsertId();

$tid = thread_create($uid, 'pit', '  我的第一帖  ', "第一行\n第二行");
check($tid > 0, '创建返回新 id');
$row = thread_get($tid);
check($row['status'] === 'published', '硬编码 status=published');
check((int)$row['user_id'] === $uid, 'user_id 落库正确');
check($row['title'] === '我的第一帖', 'title 已 trim');

$pub = thread_get_public($tid);
check($pub !== null && $pub['author_nickname'] === '发帖人', '公开详情附作者昵称');

$list = thread_list_published(20);
check(count($list) >= 1 && $list[0]['id'] === $tid, '公开列表含新帖、最新在前');

// 注入韧性
$tid2 = thread_create($uid, 'bio', "x'; DROP TABLE forum_threads;--", "\" OR 1=1");
check(thread_get($tid2)['title'] === "x'; DROP TABLE forum_threads;--", '注入串原样入库、参数化生效');

// 清理
db()->prepare('DELETE FROM forum_threads WHERE user_id = ?')->execute([$uid]);
db()->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
```

- [ ] **Step 2: 运行，确认 FAIL**（undefined function thread_create）

- [ ] **Step 3: 在 `app/threads.php` 追加**

```php
function thread_create(int $userId, string $category, string $title, string $body): int {
    $now = iso_now();
    db()->prepare("INSERT INTO forum_threads(user_id,category,title,body,status,created_at,updated_at)
                   VALUES(?,?,?,?,'published',?,?)")
        ->execute([$userId, $category, trim($title), trim($body), $now, $now]);
    return (int)db()->lastInsertId();
}
function thread_get(int $id): ?array {
    $q = db()->prepare('SELECT * FROM forum_threads WHERE id = ? LIMIT 1');
    $q->execute([$id]);
    return $q->fetch() ?: null;
}
function thread_get_public(int $id): ?array {
    $q = db()->prepare(
        "SELECT t.*, u.nickname AS author_nickname, u.username AS author_username
         FROM forum_threads t JOIN users u ON u.id = t.user_id
         WHERE t.id = ? AND t.status = 'published' LIMIT 1");
    $q->execute([$id]);
    return $q->fetch() ?: null;
}
function thread_list_published(int $limit, int $offset = 0): array {
    $q = db()->prepare(
        "SELECT t.*, u.nickname AS author_nickname, u.username AS author_username
         FROM forum_threads t JOIN users u ON u.id = t.user_id
         WHERE t.status = 'published'
         ORDER BY t.created_at DESC, t.id DESC LIMIT ? OFFSET ?");
    $q->execute([$limit, $offset]);
    return $q->fetchAll();
}
```

- [ ] **Step 4: 运行，确认 PASS** — `php tests/run.php`，`0 failed`。

- [ ] **Step 5: 提交**

```bash
git add app/threads.php tests/test_threads.php
git commit -m "feat(forum): 帖子创建/查询/公开列表(JOIN 作者名)"
```

---

## Task 5: 作者编辑/删除（越权校验）+ 限流

**Files:** Modify: `app/threads.php`、`tests/test_threads.php`

- [ ] **Step 1: 追加失败测试**（放在 Task 4 的「清理」之前）

```php

// ── 作者越权校验 ──
$oid = $uid;                                        // 帖主
db()->prepare('INSERT INTO users(username,password_hash,role,status,created_at,updated_at) VALUES(?,?,?,?,?,?)')
    ->execute(['t_other', 'x', 'member', 'active', $now, $now]);
$other = (int)db()->lastInsertId();

$editTid = thread_create($oid, 'pit', '原标题', '原正文');
check(thread_update($editTid, $other, 'pit', '篡改', '篡改') === false, '非作者更新被拒');
check(thread_get($editTid)['title'] === '原标题', '数据未被越权修改');
check(thread_update($editTid, $oid, 'bio', '新标题', '新正文') === true, '作者本人更新成功');
check(thread_get($editTid)['title'] === '新标题', '作者更新生效');
check(thread_delete($editTid, $other) === false, '非作者删除被拒');
check(thread_delete($editTid, $oid) === true, '作者本人删除成功');
check(thread_get($editTid) === null, '删除后消失');

// ── 限流 ──
check(thread_recent_count_by_user($oid, 60) >= 1, '近 60 秒发帖计数>=1');
check(thread_can_post_now($oid) === false, '刚发过帖 → 60 秒内不可再发');

db()->prepare('DELETE FROM users WHERE id = ?')->execute([$other]);
db()->prepare('DELETE FROM forum_threads WHERE user_id = ?')->execute([$other]);
```

- [ ] **Step 2: 运行，确认 FAIL**（undefined function thread_update）

- [ ] **Step 3: 在 `app/threads.php` 追加**

```php
function thread_update(int $id, int $userId, string $category, string $title, string $body): bool {
    $st = db()->prepare('UPDATE forum_threads SET category=?, title=?, body=?, updated_at=? WHERE id=? AND user_id=?');
    $st->execute([$category, trim($title), trim($body), iso_now(), $id, $userId]);
    return $st->rowCount() > 0;
}
function thread_delete(int $id, int $userId): bool {
    $st = db()->prepare('DELETE FROM forum_threads WHERE id=? AND user_id=?');
    $st->execute([$id, $userId]);
    return $st->rowCount() > 0;
}
function thread_recent_count_by_user(int $userId, int $sinceSeconds): int {
    $since = date('c', time() - $sinceSeconds);
    $q = db()->prepare('SELECT COUNT(*) FROM forum_threads WHERE user_id = ? AND created_at >= ?');
    $q->execute([$userId, $since]);
    return (int)$q->fetchColumn();
}
function thread_can_post_now(int $userId): bool {
    if (thread_recent_count_by_user($userId, THREAD_RATE_MIN_SECONDS) > 0) return false;
    if (thread_recent_count_by_user($userId, 86400) >= THREAD_RATE_DAILY_MAX) return false;
    return true;
}
```

- [ ] **Step 4: 运行，确认 PASS** — `0 failed`。

- [ ] **Step 5: 提交**

```bash
git add app/threads.php tests/test_threads.php
git commit -m "feat(forum): 作者编辑/删除(user_id 越权硬校验) + 发帖限流"
```

---

## Task 6: 后台函数（列表/下架/删除）

**Files:** Modify: `app/threads.php`、`tests/test_threads.php`

- [ ] **Step 1: 追加失败测试**（Task 5 的清理行之前）

```php

// ── 后台治理 + 级联删除 ──
$aTid = thread_create($oid, 'pit', '待治理帖', '正文');
thread_set_status($aTid, 'hidden');
check(thread_get_public($aTid) === null, '下架后公开详情不可见');
$ids = array_column(thread_list_published(50), 'id');
check(!in_array($aTid, $ids, true), '下架后不在公开列表');
check(count(thread_admin_list()) >= 1, '后台列表含全部(含 hidden)');
thread_set_status($aTid, 'published');
check(thread_get_public($aTid) !== null, '恢复后公开可见');
thread_admin_delete($aTid);
check(thread_get($aTid) === null, '后台删除生效');

// 级联：删帖主 → 其帖随外键消失
$cTid = thread_create($oid, 'bio', '级联测试', '正文');
db()->prepare('DELETE FROM users WHERE id = ?')->execute([$oid]);
check(thread_get($cTid) === null, '删除会员级联删除其帖');
```

> 注意：此段末尾删除了 `$oid` 帖主，故 Task 4 原有的清理行 `DELETE FROM users WHERE id = $uid`（=$oid）与 `DELETE FROM forum_threads WHERE user_id=$uid` 仍幂等无害，保留即可。

- [ ] **Step 2: 运行，确认 FAIL**（undefined function thread_admin_list）

- [ ] **Step 3: 在 `app/threads.php` 追加**

```php
function thread_admin_list(): array {
    return db()->query(
        "SELECT t.*, u.nickname AS author_nickname, u.username AS author_username
         FROM forum_threads t LEFT JOIN users u ON u.id = t.user_id
         ORDER BY t.created_at DESC, t.id DESC")->fetchAll();
}
function thread_set_status(int $id, string $status): void {
    $status = $status === 'hidden' ? 'hidden' : 'published';
    db()->prepare('UPDATE forum_threads SET status = ?, updated_at = ? WHERE id = ?')
        ->execute([$status, iso_now(), $id]);
}
function thread_admin_delete(int $id): void {
    db()->prepare('DELETE FROM forum_threads WHERE id = ?')->execute([$id]);
}
```

- [ ] **Step 4: 运行，确认 PASS** — `0 failed`。

- [ ] **Step 5: 提交**

```bash
git add app/threads.php tests/test_threads.php
git commit -m "feat(forum): 后台帖子治理(列表/下架恢复/删除) + 级联删除验证"
```

---

## Task 7: 发帖页 public/thread-new.php

**Files:** Create: `public/thread-new.php`

- [ ] **Step 1: 创建**

```php
<?php
require __DIR__ . '/../app/bootstrap.php';
header('Cache-Control: no-store, must-revalidate');
member_check();

$me = member_find_by_username((string)($_SESSION['uid'] ?? ''));
if (!$me) { auth_logout(); redirect('login.php'); }
$uid = (int)$me['id'];

$err = null;
$in = ['category' => 'pit', 'title' => '', 'body' => ''];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify_or_die();
    $in['category'] = (string)($_POST['category'] ?? '');
    $in['title']    = (string)($_POST['title'] ?? '');
    $in['body']     = (string)($_POST['body'] ?? '');

    if (($me['status'] ?? '') !== 'active')            $err = '账号已被停用，无法发帖。';
    elseif (!thread_valid_category($in['category']))   $err = '请选择有效分类。';
    elseif (!thread_validate_title($in['title']))      $err = '标题需 1–120 字。';
    elseif (!thread_validate_body($in['body']))        $err = '正文需 1–5000 字。';
    elseif (!thread_can_post_now($uid))                $err = '发帖过于频繁，请稍后再试。';
    else {
        $tid = thread_create($uid, $in['category'], $in['title'], $in['body']);
        audit('thread_create', 'thread', (string)$tid);
        redirect('thread.php?id=' . $tid);
    }
}
$active = 'forum'; $navOnDark = false;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>发帖 · MabSeek 论坛</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/partials/nav.php'; ?>
<main class="container" style="max-width:640px;margin:48px auto">
  <h1 style="margin-bottom:20px">发布帖子</h1>
  <?php if ($err): ?><p style="color:#c0392b"><?= e($err) ?></p><?php endif; ?>
  <form method="post" action="thread-new.php">
    <?= csrf_field() ?>
    <div class="field">
      <label>分类</label>
      <select class="input" name="category" required>
<?php foreach (THREAD_CATEGORIES as $k => $label): ?>
        <option value="<?= e($k) ?>"<?= $in['category'] === $k ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>标题（≤120 字）</label>
      <input class="input" type="text" name="title" maxlength="120" value="<?= e($in['title']) ?>" required>
    </div>
    <div class="field">
      <label>正文（纯文本，≤5000 字）</label>
      <textarea class="input" name="body" rows="10" maxlength="5000" required><?= e($in['body']) ?></textarea>
    </div>
    <button class="btn btn-purple" type="submit">发布</button>
    <a href="forum.php" style="margin-left:12px">取消</a>
  </form>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
```

- [ ] **Step 2: 校验语法 + 提交**

Run: `php -l public/thread-new.php`
```bash
git add public/thread-new.php
git commit -m "feat(forum): 会员发帖页(校验+停用拦截+限流)"
```

---

## Task 8: 详情页 public/thread.php

**Files:** Create: `public/thread.php`

- [ ] **Step 1: 创建**

```php
<?php
require __DIR__ . '/../app/bootstrap.php';
$id = (int)($_GET['id'] ?? 0);
$t = $id > 0 ? thread_get_public($id) : null;

// 当前登录会员 id（用于判断是否作者）
$myId = 0;
if (auth_check()) {
    $me = member_find_by_username((string)($_SESSION['uid'] ?? ''));
    $myId = $me ? (int)$me['id'] : 0;
}

if (!$t) {
    http_response_code(404);
    $active = 'forum'; $navOnDark = false;
    include __DIR__ . '/partials/nav.php';
    echo '<main class="container" style="max-width:640px;margin:64px auto;text-align:center"><h1>帖子不存在</h1><p><a href="forum.php">返回论坛</a></p></main>';
    include __DIR__ . '/partials/footer.php';
    exit;
}
$author = ($t['author_nickname'] ?? '') !== '' ? $t['author_nickname'] : ($t['author_username'] ?? '');
$catLabel = THREAD_CATEGORIES[$t['category']] ?? $t['category'];
$isOwner = $myId > 0 && (int)$t['user_id'] === $myId;
$active = 'forum'; $navOnDark = false;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($t['title']) ?> · MabSeek 论坛</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/partials/nav.php'; ?>
<main class="container" style="max-width:720px;margin:48px auto">
  <div class="breadcrumb"><a href="index.php">首页</a> / <a href="forum.php">论坛</a></div>
  <span class="eyebrow"><?= e($catLabel) ?></span>
  <h1 style="margin:10px 0 8px"><?= e($t['title']) ?></h1>
  <p style="color:var(--ink-3);font-size:14px;margin-bottom:24px"><?= e($author) ?> · <?= e($t['created_at']) ?></p>
  <article style="line-height:1.9;color:var(--ink-2)"><?= nl2br(e($t['body'])) ?></article>
<?php if ($isOwner): ?>
  <div style="display:flex;gap:10px;margin-top:32px">
    <a class="btn btn-outline" href="thread-edit.php?id=<?= (int)$t['id'] ?>">编辑</a>
    <form method="post" action="thread-edit.php?id=<?= (int)$t['id'] ?>" onsubmit="return confirm('确认删除这篇帖子？')">
      <?= csrf_field() ?><input type="hidden" name="act" value="delete">
      <button class="btn btn-outline" type="submit" style="color:#c0392b">删除</button>
    </form>
  </div>
<?php endif; ?>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
```

- [ ] **Step 2: 校验 + 提交**

Run: `php -l public/thread.php`
```bash
git add public/thread.php
git commit -m "feat(forum): 帖子详情页(nl2br+e 转义, 作者显示编辑/删除, 404 兜底)"
```

---

## Task 9: 编辑/删除本人帖 public/thread-edit.php

**Files:** Create: `public/thread-edit.php`

- [ ] **Step 1: 创建**

```php
<?php
require __DIR__ . '/../app/bootstrap.php';
header('Cache-Control: no-store, must-revalidate');
member_check();

$me = member_find_by_username((string)($_SESSION['uid'] ?? ''));
if (!$me) { auth_logout(); redirect('login.php'); }
$uid = (int)$me['id'];

$id = (int)($_GET['id'] ?? 0);
$t = $id > 0 ? thread_get($id) : null;
// 仅作者本人；他人或不存在一律 404
if (!$t || (int)$t['user_id'] !== $uid) {
    http_response_code(404);
    $active = 'forum'; $navOnDark = false;
    include __DIR__ . '/partials/nav.php';
    echo '<main class="container" style="max-width:640px;margin:64px auto;text-align:center"><h1>帖子不存在</h1><p><a href="forum.php">返回论坛</a></p></main>';
    include __DIR__ . '/partials/footer.php';
    exit;
}

$err = null;
$in = ['category' => $t['category'], 'title' => $t['title'], 'body' => $t['body']];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify_or_die();
    $act = (string)($_POST['act'] ?? 'update');

    if ($act === 'delete') {
        thread_delete($id, $uid);
        audit('thread_delete', 'thread', (string)$id);
        redirect('forum.php');
    }

    $in['category'] = (string)($_POST['category'] ?? '');
    $in['title']    = (string)($_POST['title'] ?? '');
    $in['body']     = (string)($_POST['body'] ?? '');
    if (!thread_valid_category($in['category']))    $err = '请选择有效分类。';
    elseif (!thread_validate_title($in['title']))   $err = '标题需 1–120 字。';
    elseif (!thread_validate_body($in['body']))     $err = '正文需 1–5000 字。';
    else {
        thread_update($id, $uid, $in['category'], $in['title'], $in['body']);
        audit('thread_update', 'thread', (string)$id);
        redirect('thread.php?id=' . $id);
    }
}
$active = 'forum'; $navOnDark = false;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>编辑帖子 · MabSeek 论坛</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/partials/nav.php'; ?>
<main class="container" style="max-width:640px;margin:48px auto">
  <h1 style="margin-bottom:20px">编辑帖子</h1>
  <?php if ($err): ?><p style="color:#c0392b"><?= e($err) ?></p><?php endif; ?>
  <form method="post" action="thread-edit.php?id=<?= (int)$id ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="act" value="update">
    <div class="field">
      <label>分类</label>
      <select class="input" name="category" required>
<?php foreach (THREAD_CATEGORIES as $k => $label): ?>
        <option value="<?= e($k) ?>"<?= $in['category'] === $k ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>标题（≤120 字）</label>
      <input class="input" type="text" name="title" maxlength="120" value="<?= e($in['title']) ?>" required>
    </div>
    <div class="field">
      <label>正文（纯文本，≤5000 字）</label>
      <textarea class="input" name="body" rows="10" maxlength="5000" required><?= e($in['body']) ?></textarea>
    </div>
    <button class="btn btn-purple" type="submit">保存</button>
    <a href="thread.php?id=<?= (int)$id ?>" style="margin-left:12px">取消</a>
  </form>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
```

- [ ] **Step 2: 校验 + 提交**

Run: `php -l public/thread-edit.php`
```bash
git add public/thread-edit.php
git commit -m "feat(forum): 编辑/删除本人帖(作者硬校验, 他人404)"
```

---

## Task 10: forum.php 会员发布板块 + 入口

**Files:** Modify: `public/forum.php`

- [ ] **Step 1: 顶部数据装配**

在 `public/forum.php` 的 `$lines = ...;` 之后新增：

```php
$threads = thread_list_published(20);
$threadPostHref = auth_check() ? 'thread-new.php' : 'login.php';
```

- [ ] **Step 2: 新增「会员发布」板块**

在「四条内容线」`<!-- 四条内容线 -->` 那节 `<section>` 之前插入：

```php
<!-- ============ 会员发布 ============ -->
<section class="section" id="threads">
  <div class="container">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;gap:16px;flex-wrap:wrap">
      <div>
        <span class="eyebrow">会员发布</span>
        <h2 class="section-title">会员们最近发了什么</h2>
      </div>
      <a class="btn btn-purple" href="<?= e($threadPostHref) ?>">发帖</a>
    </div>
<?php if (!$threads): ?>
    <p style="color:var(--ink-3)">还没有会员帖子，<a href="<?= e($threadPostHref) ?>">来发第一帖</a>。</p>
<?php else: ?>
    <div class="thread-list">
<?php foreach ($threads as $t):
      $author = ($t['author_nickname'] ?? '') !== '' ? $t['author_nickname'] : ($t['author_username'] ?? '');
      $catLabel = THREAD_CATEGORIES[$t['category']] ?? $t['category'];
?>
      <a class="thread-item card" href="thread.php?id=<?= (int)$t['id'] ?>" style="display:block;padding:18px 20px;margin-bottom:12px;text-decoration:none;color:inherit">
        <div style="font-size:12px;color:var(--purple);font-weight:700"><?= e($catLabel) ?></div>
        <h4 style="margin:6px 0;font-size:16px"><?= e($t['title']) ?></h4>
        <div style="font-size:13px;color:var(--ink-3)"><?= e($author) ?> · <?= e($t['created_at']) ?></div>
      </a>
<?php endforeach; ?>
    </div>
<?php endif; ?>
  </div>
</section>
```

> 使用现有 `.card` 与内联样式，避免新增 CSS 文件；`.section`/`.container`/`.eyebrow`/`.section-title`/`.btn` 均为既有样式类。

- [ ] **Step 3: 校验 + 提交**

Run: `php -l public/forum.php`
```bash
git add public/forum.php
git commit -m "feat(forum): forum.php 会员发布板块 + 发帖入口(登录态跳发帖/未登录跳登录)"
```

---

## Task 11: 后台治理 app/admin/threads.php + 菜单 + 白名单

**Files:** Create: `app/admin/threads.php`；Modify: `app/admin/shell.php`、`public/admin.php`

- [ ] **Step 1: 创建 `app/admin/threads.php`**

```php
<?php
// 后台论坛发帖治理：下架/恢复/删除任意帖。均 POST + CSRF，已在 admin.php 的 auth_is_admin() 硬闸内。

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    while (ob_get_level() > 0) { ob_end_clean(); }
    csrf_verify_or_die();
    $act = (string)($_POST['act'] ?? '');
    $id  = (int)($_POST['id'] ?? 0);
    $target = $id > 0 ? thread_get($id) : null;

    if (!$target) {
        flash_set('error', '目标帖子不存在');
        redirect('admin.php?m=threads');
    }
    if ($act === 'hide') {
        thread_set_status($id, 'hidden');
        audit('thread_hide', 'thread', (string)$id);
        flash_set('ok', '已下架帖子 #' . $id);
    } elseif ($act === 'show') {
        thread_set_status($id, 'published');
        audit('thread_show', 'thread', (string)$id);
        flash_set('ok', '已恢复帖子 #' . $id);
    } elseif ($act === 'delete') {
        thread_admin_delete($id);
        audit('thread_admin_delete', 'thread', (string)$id);
        flash_set('ok', '已删除帖子 #' . $id);
    } else {
        flash_set('error', '未知操作');
    }
    redirect('admin.php?m=threads');
}

$threads = thread_admin_list();
?>
<div class="card">
  <h3 style="margin-bottom:16px">论坛发帖（共 <?= count($threads) ?> 篇）</h3>
  <table class="admin-table">
    <thead><tr><th>ID</th><th>标题</th><th>作者</th><th>分类</th><th>状态</th><th>时间</th><th>操作</th></tr></thead>
    <tbody>
<?php foreach ($threads as $t):
      $author = ($t['author_nickname'] ?? '') !== '' ? $t['author_nickname'] : ($t['author_username'] ?? '（已注销）');
      $catLabel = THREAD_CATEGORIES[$t['category']] ?? $t['category'];
?>
      <tr>
        <td><?= (int)$t['id'] ?></td>
        <td><a href="thread.php?id=<?= (int)$t['id'] ?>" target="_blank"><?= e($t['title']) ?></a></td>
        <td><?= e($author) ?></td>
        <td><?= e($catLabel) ?></td>
        <td><?= $t['status'] === 'hidden' ? '<span style="color:#c0392b">已下架</span>' : '公开' ?></td>
        <td><?= e($t['created_at']) ?></td>
        <td style="display:flex;gap:6px;flex-wrap:wrap">
<?php if ($t['status'] === 'hidden'): ?>
          <form method="post" action="admin.php?m=threads"><?= csrf_field() ?><input type="hidden" name="act" value="show"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><button class="abtn" type="submit">恢复</button></form>
<?php else: ?>
          <form method="post" action="admin.php?m=threads"><?= csrf_field() ?><input type="hidden" name="act" value="hide"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><button class="abtn" type="submit">下架</button></form>
<?php endif; ?>
          <form method="post" action="admin.php?m=threads" onsubmit="return confirm('确认删除该帖？不可恢复')"><?= csrf_field() ?><input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><button class="abtn" type="submit" style="color:#c0392b">删除</button></form>
        </td>
      </tr>
<?php endforeach; ?>
<?php if (!$threads): ?>
      <tr><td colspan="7" style="color:#888">暂无帖子</td></tr>
<?php endif; ?>
    </tbody>
  </table>
</div>
```

- [ ] **Step 2: 侧栏菜单加入「论坛发帖」**

在 `app/admin/shell.php` 的 `$adminMenu` 中 `'members' => '会员管理',` 之后插入：

```php
    'threads'     => '论坛发帖',
```

- [ ] **Step 3: `public/admin.php` 模块白名单加入 threads**

把白名单数组：
```php
$allowed = ['dashboard','news','forum_posts','forum_hot','team','partners','cards','snippets','members','password'];
```
改为：
```php
$allowed = ['dashboard','news','forum_posts','forum_hot','team','partners','cards','snippets','members','threads','password'];
```

- [ ] **Step 4: 校验 + 提交**

Run: `php -l app/admin/threads.php && php -l app/admin/shell.php && php -l public/admin.php`
```bash
git add app/admin/threads.php app/admin/shell.php public/admin.php
git commit -m "feat(forum): 后台论坛发帖治理页(下架/恢复/删除, 审计) + 菜单 + 白名单"
```

---

## Task 12: 全量收尾验证

- [ ] **Step 1: 全量测试**

Run: `php tests/run.php` — 预期全部 PASS、`0 failed`（含 test_threads 全部断言 + 原有不回归）。

- [ ] **Step 2: 端到端实机验证**

Run: `php bin/seed.php && php -S localhost:8778 -t public`（预览环境用 `mabseek-php`）
验证：
1. 未登录访问 `forum.php`「发帖」按钮 → 跳 `login.php`。
2. 会员登录后发帖 → 跳详情页 `thread.php?id=`，正文换行正确、HTML 被转义。
3. 详情页作者本人见「编辑/删除」；编辑保存生效；删除后回 `forum.php`。
4. 他人 `thread-edit.php?id=` → 404。
5. 连续发帖触发 60 秒限流提示。
6. 后台「论坛发帖」下架某帖 → 前台列表/详情不可见；恢复后可见；删除生效。
7. 后台删除该会员 → 其帖级联消失。

- [ ] **Step 3: 无新增未跟踪文件确认**

Run: `git status --short` — 预期干净。

---

## Self-Review（对照 spec）

- §3 数据模型（forum_threads 列/索引/FK 级联/类别/校验）→ Task 1、2、3。✅
- §4 迁移部署（幂等建表、migrate 不用 iso_now、deploy 零改动）→ Task 2。✅
- §5 领域逻辑（create/get/public/list/update/delete/限流/后台）→ Task 4、5、6。✅
- §6 前台页面（forum 板块+入口、thread、thread-new、thread-edit）→ Task 7、8、9、10。✅
- §7 后台治理（列表/下架恢复/删除/审计/菜单/白名单）→ Task 11。✅
- §8 安全（PDO 预处理、e() 转义、CSRF、越权硬校验、限流、hidden 404、硬编码 user_id/status）→ 贯穿 Task 4–11。✅
- §9 测试（校验/创建/列表/越权/限流/后台/级联/注入）→ Task 3–6 测试步骤。✅
- §10 文件清单 → 与本计划任务一一对应。✅

**占位符扫描：** 无。**类型一致性：** `thread_create/thread_get/thread_get_public/thread_list_published/thread_update/thread_delete/thread_recent_count_by_user/thread_can_post_now/thread_admin_list/thread_set_status/thread_admin_delete` 定义与调用签名一致。✅
