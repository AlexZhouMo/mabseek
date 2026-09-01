# 论坛发帖复用新闻富文本编辑器 实现计划

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 让论坛发帖 / 编辑页使用与后台新闻正文相同的富文本编辑器（含会员图片上传），编辑器只保留一份共享定义。

**Architecture:** 把富文本编辑器标记抽成共享 partial（`public/partials/richtext-field.php`），后台新闻 CRUD 与论坛发帖/编辑页共同引用；`richtext.js` 的上传地址参数化（`data-upload-url`）；新增会员图片上传端点 `public/upload.php`；论坛正文保存前经 `sanitize_html()` 净化、以纯文本长度校验，详情页直出净化后 HTML。

**Tech Stack:** PHP（无框架）、SQLite、原生 JS（`contenteditable` + `execCommand`）、零依赖自研测试套件（`tests/run.php`）。

**关联规格：** `docs/superpowers/specs/2026-09-01-forum-richtext-editor-design.md`

**已锁定的产品决策：** 正文长度按“净化后纯文本”计（1–5000 字，标签/图片不计入）；因此**仅含图片、无文字的帖子会被拒绝**（纯文本为空）。这是既定行为，不是缺陷。

---

## 文件结构

- **新建** `public/partials/richtext-field.php` — 唯一的富文本编辑器标记（工具栏 + `contenteditable` 编辑区 + 隐藏 `textarea`），参数化字段名 / 当前值 / 上传 URL。
- **新建** `public/upload.php` — 会员图片上传端点（会员登录 + CSRF + 复用 `handle_upload`）。
- **修改** `app/threads.php` — `thread_validate_body` 改为按纯文本长度校验。
- **修改** `app/bootstrap.php` — 全局加载 `html_sanitizer.php`，使前台页面可调用 `sanitize_html()`。
- **修改** `app/admin/crud.php` — `richtext` 分支改为 `include` 共享 partial。
- **修改** `public/assets/js/richtext.js` — 上传地址从写死改为读 `data-upload-url`。
- **修改** `public/assets/css/admin.css` / `public/assets/css/style.css` — 把 `.rt-*` 编辑器样式从 admin.css 迁移到共享 style.css。
- **修改** `public/thread-new.php` — 正文换成富文本编辑器 + 净化落库。
- **修改** `public/thread-edit.php` — 正文换成富文本编辑器 + 净化落库。
- **修改** `public/thread.php` — 正文改为直出净化后 HTML。
- **修改** `tests/test_threads.php` — 补充富文本长度校验用例。

---

## Task 1: 正文校验改为按纯文本长度

**Files:**
- Modify: `app/threads.php:7`
- Test: `tests/test_threads.php`

- [ ] **Step 1: 写失败测试**

在 `tests/test_threads.php` 中，紧跟现有 `thread_validate_body` 三行断言（约第 14–16 行）之后，追加：

```php
// 富文本：按“净化后纯文本”长度计（标签/图片不计入）
check(thread_validate_body('<p>正文内容</p>') === true, 'HTML 正文按纯文本计=合法');
check(thread_validate_body('<img src="x.png">') === false, '仅图片(纯文本为空)被拒');
check(thread_validate_body('<p>' . str_repeat('字', 5000) . '</p>') === true, 'HTML 纯文本 5000 通过');
check(thread_validate_body('<p>' . str_repeat('字', 5001) . '</p>') === false, 'HTML 纯文本 5001 被拒');
```

- [ ] **Step 2: 运行测试确认失败**

Run: `php tests/run.php`
Expected: `test_threads.php` 段出现 `✗ FAIL: 仅图片(纯文本为空)被拒`（当前实现按原始串长度计，`<img src="x.png">` 长度 >0 会误判为合法）。

- [ ] **Step 3: 最小实现**

把 `app/threads.php:7` 由：

```php
function thread_validate_body(string $v): bool { $n = mb_strlen(trim($v)); return $n >= 1 && $n <= 5000; }
```

改为：

```php
function thread_validate_body(string $v): bool { $n = mb_strlen(trim(strip_tags($v))); return $n >= 1 && $n <= 5000; }
```

- [ ] **Step 4: 运行测试确认通过**

Run: `php tests/run.php`
Expected: 全部 PASS（新增 4 条 + 原有纯文本用例仍通过，因纯文本 `strip_tags` 后不变）。

- [ ] **Step 5: 提交**

```bash
git add app/threads.php tests/test_threads.php
git commit -m "feat(forum): 正文校验按净化后纯文本长度计(标签/图片不计入)"
```

> **净化器测试说明：** `sanitize_html` 代码本身不改动，且 `tests/test_sanitizer.php` 已覆盖会员场景所需用例（`<script>` 删除、事件属性剥离——`img` 与保留元素 `div` 皆有、外链图删除、站内图保留、路径穿越删除、`javascript:` 链接去 href）。故本计划不新增净化器测试（避免重复）。会员正文与新闻正文走同一净化函数，无新代码路径。

---

## Task 2: 全局加载 HTML 净化器

**Files:**
- Modify: `app/bootstrap.php:11`

前台页面（`thread-new.php` / `thread-edit.php` / `thread.php`）以及共享 partial 都要调用 `sanitize_html()`，需保证其函数已加载。`bootstrap.php` 当前未 require `html_sanitizer.php`。

- [ ] **Step 1: 加载净化器**

在 `app/bootstrap.php` 中 `require_once __DIR__ . '/threads.php';`（第 11 行）之后新增一行：

```php
require_once __DIR__ . '/html_sanitizer.php';
```

- [ ] **Step 2: 冒烟验证函数可用**

Run: `php -r 'require "app/bootstrap.php"; echo sanitize_html("<p>ok</p><script>x</script>");'`
Expected: 输出 `<p>ok</p>`（`script` 被删），无致命错误。

- [ ] **Step 3: 回归测试**

Run: `php tests/run.php`
Expected: 全部 PASS（无回归）。

- [ ] **Step 4: 提交**

```bash
git add app/bootstrap.php
git commit -m "chore: bootstrap 全局加载 html_sanitizer 供前台净化正文"
```

---

## Task 3: 抽取共享富文本 partial 并让新闻后台引用

**Files:**
- Create: `public/partials/richtext-field.php`
- Modify: `app/admin/crud.php:250-267`

- [ ] **Step 1: 新建共享 partial**

创建 `public/partials/richtext-field.php`，内容如下（标记与现有新闻编辑器完全一致，新增 `data-upload-url`）：

```php
<?php
// 富文本编辑器字段：工具栏 + contenteditable 编辑区 + 隐藏 textarea。
// 由调用方在 include 前赋值以下局部变量：
//   $rtName      string  隐藏 textarea 的 name（提交字段名）
//   $rtValue     string  当前值（HTML；此处再净化一次，杜绝回填自 XSS）
//   $rtUploadUrl string  图片上传端点 URL（写入 data-upload-url）
//   $rtRequired  bool    可选，是否必填（仅前端提示；权威校验在服务端）
$rtClean = sanitize_html((string)($rtValue ?? ''));
?>
<div class="rt-field" data-rt data-upload-url="<?= e((string)($rtUploadUrl ?? '')) ?>">
  <div class="rt-toolbar">
    <button type="button" class="rt-btn" data-cmd="bold" title="加粗"><b>B</b></button>
    <button type="button" class="rt-btn" data-cmd="italic" title="斜体"><i>I</i></button>
    <button type="button" class="rt-btn" data-cmd="formatBlock" data-val="h2">H2</button>
    <button type="button" class="rt-btn" data-cmd="formatBlock" data-val="h3">H3</button>
    <button type="button" class="rt-btn" data-cmd="insertUnorderedList" title="无序列表">• 列表</button>
    <button type="button" class="rt-btn" data-cmd="insertOrderedList" title="有序列表">1. 列表</button>
    <button type="button" class="rt-btn" data-cmd="createLink" title="链接">链接</button>
    <button type="button" class="rt-btn" data-rt-image title="插入图片">插入图片</button>
  </div>
  <div class="rt-editor" contenteditable="true"><?= $rtClean ?></div>
  <textarea name="<?= e((string)($rtName ?? '')) ?>" class="rt-source" hidden><?= e($rtClean) ?></textarea>
</div>
```

- [ ] **Step 2: 让新闻 CRUD 引用 partial**

在 `app/admin/crud.php` 中，将现有 `richtext` 渲染块（第 250–267 行，从 `<?php if ($type === 'richtext'):` 到 `<?php $needsRtScript = true; ?>`）整体替换为：

```php
<?php if ($type === 'richtext'):
        $rtName      = $name;
        $rtValue     = $cur;
        $rtUploadUrl = 'admin.php?m=news&a=upload';
        $rtRequired  = !empty($f['required']);
        include __DIR__ . '/../../public/partials/richtext-field.php';
        $needsRtScript = true;
?>
```

（注意：其后紧接的 `<?php elseif ($type === 'textarea'): ?>` 及 `endif` 结构保持不变。）

- [ ] **Step 3: 语法自检**

Run: `php -l app/admin/crud.php && php -l public/partials/richtext-field.php`
Expected: 两个文件均 `No syntax errors detected`。

- [ ] **Step 4: 回归测试**

Run: `php tests/run.php`
Expected: 全部 PASS（`test_crud_fields.php` 等无回归）。

- [ ] **Step 5: 人工验证后台新闻编辑器未变**

启动本地服务（若未运行）：

```bash
php -S localhost:8778 -t public
```

登录后台 → 新闻与活动 → 新增/编辑，确认：工具栏、编辑区、插入图片均正常；查看页面源码确认渲染出 `data-upload-url="admin.php?m=news&a=upload"`。

- [ ] **Step 6: 提交**

```bash
git add public/partials/richtext-field.php app/admin/crud.php
git commit -m "refactor(editor): 抽取共享富文本 partial, 新闻后台改为引用"
```

---

## Task 4: 富文本样式迁移到共享 style.css

**Files:**
- Modify: `public/assets/css/admin.css:263-274`
- Modify: `public/assets/css/style.css`

论坛前台仅加载 `style.css`，而 `.rt-*` 样式当前只在 `admin.css`。编辑器已共享，样式应移入共享表；后台外壳同时加载 `style.css` 与 `admin.css`，迁移后后台仍生效。

- [ ] **Step 1: 从 admin.css 删除 `.rt-*` 规则**

删除 `public/assets/css/admin.css` 第 263–274 行（`.rt-field` … `.rt-editor blockquote` 共 12 行）。

- [ ] **Step 2: 追加到 style.css**

在 `public/assets/css/style.css` 末尾追加以下内容（原样迁移 + 一条渲染态图片防溢出规则）：

```css
/* 富文本编辑器（论坛发帖 / 后台新闻共用） */
.rt-field { border: 1px solid var(--line, #e5e7eb); border-radius: 8px; overflow: hidden; }
.rt-toolbar { display: flex; flex-wrap: wrap; gap: 4px; padding: 8px; background: #f7f7f9; border-bottom: 1px solid var(--line, #e5e7eb); }
.rt-btn { min-width: 34px; height: 30px; padding: 0 10px; border: 1px solid #d0d0d8; border-radius: 6px; background: #fff; cursor: pointer; font-size: 13px; line-height: 28px; }
.rt-btn:hover { background: #eef0ff; border-color: #b9c0ff; }
.rt-editor { min-height: 260px; padding: 14px 16px; outline: none; line-height: 1.7; font-size: 15px; }
.rt-editor:focus { box-shadow: inset 0 0 0 2px rgba(120,110,240,.15); }
.rt-editor img { max-width: 100%; height: auto; border-radius: 6px; }
.rt-editor h2 { font-size: 20px; margin: 14px 0 8px; }
.rt-editor h3 { font-size: 17px; margin: 12px 0 6px; }
.rt-editor p { margin: 8px 0; }
.rt-editor ul, .rt-editor ol { padding-left: 22px; margin: 8px 0; }
.rt-editor blockquote { margin: 10px 0; padding: 6px 14px; border-left: 3px solid #b9c0ff; color: #555; background: #f7f7fb; }

/* 帖子详情正文中的用户上传图片防溢出 */
main article img { max-width: 100%; height: auto; border-radius: 6px; }
```

- [ ] **Step 3: 人工验证后台样式未回退**

启动服务后访问后台新闻编辑器，确认工具栏/编辑区外观与迁移前一致（样式改由 style.css 提供）。

- [ ] **Step 4: 提交**

```bash
git add public/assets/css/admin.css public/assets/css/style.css
git commit -m "style(editor): rt-* 样式迁移到共享 style.css, 供论坛前台复用"
```

---

## Task 5: 上传地址参数化（richtext.js）

**Files:**
- Modify: `public/assets/js/richtext.js`

- [ ] **Step 1: 读取 data-upload-url**

在 `public/assets/js/richtext.js` 的 `document.querySelectorAll('[data-rt]').forEach(function (field) {` 块内，紧跟 `if (!editor || !source || !form) return;` 之后新增一行：

```js
    var uploadUrl = field.getAttribute('data-upload-url') || 'admin.php?m=news&a=upload';
```

- [ ] **Step 2: 使用变量替换写死地址**

将 `uploadImage` 函数内的：

```js
      fetch('admin.php?m=news&a=upload', { method: 'POST', body: data })
```

改为：

```js
      fetch(uploadUrl, { method: 'POST', body: data })
```

- [ ] **Step 3: 语法自检**

Run: `node --check public/assets/js/richtext.js`
Expected: 无输出（语法正确）。若环境无 node，跳过并在浏览器控制台确认无报错。

- [ ] **Step 4: 人工验证新闻插图仍工作**

后台新闻编辑器点“插入图片”上传一张图，确认图片插入成功（回退默认地址生效）。

- [ ] **Step 5: 提交**

```bash
git add public/assets/js/richtext.js
git commit -m "feat(editor): 上传地址改由 data-upload-url 提供(默认回退新闻端点)"
```

---

## Task 6: 会员图片上传端点

**Files:**
- Create: `public/upload.php`

复用 `handle_upload('file')`（定义于 `app/admin/upload.php`），返回与后台一致的 JSON。会员未登录时 `member_check()` 会重定向到登录页；前端 `fetch` 拿到非 JSON 响应会走失败分支，符合预期。

- [ ] **Step 1: 新建端点**

创建 `public/upload.php`：

```php
<?php
require __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/admin/upload.php';   // handle_upload()
header('Cache-Control: no-store, must-revalidate');
member_check();                                       // 未登录 → 重定向登录

while (ob_get_level() > 0) { ob_end_clean(); }        // 丢弃缓冲，输出干净 JSON
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405); header('Allow: POST'); exit('Method Not Allowed');
}
csrf_verify_or_die();
header('Content-Type: application/json; charset=utf-8');
try {
    $url = handle_upload('file');
    echo json_encode(
        $url !== null ? ['ok' => true, 'url' => $url] : ['ok' => false, 'error' => '未选择文件'],
        JSON_UNESCAPED_UNICODE
    );
} catch (\RuntimeException $ex) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $ex->getMessage()], JSON_UNESCAPED_UNICODE);
}
```

- [ ] **Step 2: 语法自检**

Run: `php -l public/upload.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: 验证非 POST 被拒**

Run（服务运行中）: `curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8778/upload.php`
Expected: `302`（未登录先被 `member_check` 重定向）或登录后 `405`。二者都表明端点未裸奔。

- [ ] **Step 4: 人工验证会员上传闭环（Task 7/8 接入后一并验收）**

登录一个 member 账号，在发帖页编辑器点“插入图片”上传图片，确认返回 `{"ok":true,"url":"assets/images/uploads/..."}` 且图片插入编辑区（此步依赖 Task 7 的页面接入，可在 Task 7 后回验）。

- [ ] **Step 5: 提交**

```bash
git add public/upload.php
git commit -m "feat(forum): 新增会员图片上传端点 public/upload.php(会员登录+CSRF+复用校验)"
```

---

## Task 7: 发帖页接入富文本 + 净化落库

**Files:**
- Modify: `public/thread-new.php`

- [ ] **Step 1: 保存流程改为净化 + 按净化结果校验/落库**

在 `public/thread-new.php` 的 POST 处理块中，找到：

```php
    $in['body']     = (string)($_POST['body'] ?? '');

    if (($me['status'] ?? '') !== 'active')            $err = '账号已被停用，无法发帖。';
    elseif (!thread_valid_category($in['category']))   $err = '请选择有效分类。';
    elseif (!thread_validate_title($in['title']))      $err = '标题需 1–120 字。';
    elseif (!thread_validate_body($in['body']))        $err = '正文需 1–5000 字。';
    elseif (!thread_can_post_now($uid))                $err = '发帖过于频繁，请稍后再试。';
    else {
        $tid = thread_create($uid, $in['category'], $in['title'], $in['body']);
```

改为（新增 `$cleanBody`，校验与落库都用它）：

```php
    $in['body']     = (string)($_POST['body'] ?? '');
    $cleanBody      = sanitize_html($in['body']);      // 服务端权威净化

    if (($me['status'] ?? '') !== 'active')            $err = '账号已被停用，无法发帖。';
    elseif (!thread_valid_category($in['category']))   $err = '请选择有效分类。';
    elseif (!thread_validate_title($in['title']))      $err = '标题需 1–120 字。';
    elseif (!thread_validate_body($cleanBody))         $err = '正文需 1–5000 字。';
    elseif (!thread_can_post_now($uid))                $err = '发帖过于频繁，请稍后再试。';
    else {
        $tid = thread_create($uid, $in['category'], $in['title'], $cleanBody);
```

- [ ] **Step 2: 正文控件换成富文本编辑器**

在同文件的表单中，将：

```php
      <div class="field">
        <label>正文（纯文本，≤5000 字）</label>
        <textarea class="input" name="body" rows="10" maxlength="5000" required><?= e($in['body']) ?></textarea>
      </div>
```

替换为：

```php
      <div class="field">
        <label>正文（支持富文本，≤5000 字）</label>
<?php
        $rtName = 'body'; $rtValue = $in['body']; $rtUploadUrl = 'upload.php'; $rtRequired = true;
        include __DIR__ . '/partials/richtext-field.php';
?>
      </div>
```

- [ ] **Step 3: 加载编辑器脚本**

在同文件的 `<?php include __DIR__ . '/partials/footer.php'; ?>` 之前新增一行：

```php
<script src="assets/js/richtext.js" defer></script>
```

- [ ] **Step 4: 语法自检**

Run: `php -l public/thread-new.php`
Expected: `No syntax errors detected`.

- [ ] **Step 5: 回归测试**

Run: `php tests/run.php`
Expected: 全部 PASS。

- [ ] **Step 6: 人工验证发帖闭环**

以 member 账号访问 `http://localhost:8778/thread-new.php`：
- 编辑器渲染正常（工具栏 + 编辑区，含样式）。
- 输入带加粗/标题/列表的正文，插入一张图片（走 `upload.php`）。
- 发布后跳转详情页（详情页 HTML 直出在 Task 8 完成后完整验收）。
- 尝试仅上传图片、不输文字 → 应报“正文需 1–5000 字。”（既定行为）。

- [ ] **Step 7: 提交**

```bash
git add public/thread-new.php
git commit -m "feat(forum): 发帖页接入富文本编辑器, 正文净化后落库"
```

---

## Task 8: 编辑页接入 + 详情页 HTML 直出

**Files:**
- Modify: `public/thread-edit.php`
- Modify: `public/thread.php`

- [ ] **Step 1: 编辑页保存流程净化 + 校验/落库用净化结果**

在 `public/thread-edit.php` 的 POST 处理（`$act === 'update'` 之后）中，找到：

```php
    $in['category'] = (string)($_POST['category'] ?? '');
    $in['title']    = (string)($_POST['title'] ?? '');
    $in['body']     = (string)($_POST['body'] ?? '');
    if (!thread_valid_category($in['category']))    $err = '请选择有效分类。';
    elseif (!thread_validate_title($in['title']))   $err = '标题需 1–120 字。';
    elseif (!thread_validate_body($in['body']))     $err = '正文需 1–5000 字。';
    else {
        thread_update($id, $uid, $in['category'], $in['title'], $in['body']);
```

改为：

```php
    $in['category'] = (string)($_POST['category'] ?? '');
    $in['title']    = (string)($_POST['title'] ?? '');
    $in['body']     = (string)($_POST['body'] ?? '');
    $cleanBody      = sanitize_html($in['body']);      // 服务端权威净化
    if (!thread_valid_category($in['category']))    $err = '请选择有效分类。';
    elseif (!thread_validate_title($in['title']))   $err = '标题需 1–120 字。';
    elseif (!thread_validate_body($cleanBody))      $err = '正文需 1–5000 字。';
    else {
        thread_update($id, $uid, $in['category'], $in['title'], $cleanBody);
```

- [ ] **Step 2: 编辑页正文控件换成富文本编辑器**

将 `public/thread-edit.php` 表单中的：

```php
      <div class="field">
        <label>正文（纯文本，≤5000 字）</label>
        <textarea class="input" name="body" rows="10" maxlength="5000" required><?= e($in['body']) ?></textarea>
      </div>
```

替换为：

```php
      <div class="field">
        <label>正文（支持富文本，≤5000 字）</label>
<?php
        $rtName = 'body'; $rtValue = $in['body']; $rtUploadUrl = 'upload.php'; $rtRequired = true;
        include __DIR__ . '/partials/richtext-field.php';
?>
      </div>
```

- [ ] **Step 3: 编辑页加载编辑器脚本**

在 `public/thread-edit.php` 的 `<?php include __DIR__ . '/partials/footer.php'; ?>` 之前新增：

```php
<script src="assets/js/richtext.js" defer></script>
```

- [ ] **Step 4: 详情页改为直出净化后 HTML**

在 `public/thread.php` 中，将：

```php
  <article style="line-height:1.9;color:var(--ink-2)"><?= nl2br(e($t['body'])) ?></article>
```

改为（渲染前再净化一次，纵深防御）：

```php
  <article style="line-height:1.9;color:var(--ink-2)"><?= sanitize_html($t['body']) ?></article>
```

- [ ] **Step 5: 语法自检**

Run: `php -l public/thread-edit.php && php -l public/thread.php`
Expected: 两个文件均 `No syntax errors detected`.

- [ ] **Step 6: 回归测试**

Run: `php tests/run.php`
Expected: 全部 PASS。

- [ ] **Step 7: 人工验证编辑/详情闭环**

- 发一篇富文本帖（含加粗/H2/列表/站内图），详情页应正确渲染这些格式且图片不溢出。
- 编辑该帖：编辑器应回填原富文本内容，保存后详情页更新。
- 构造含 `<script>` 或外链 `<img>` 的正文（可用浏览器改 `.rt-editor` 后提交）→ 详情页不出现脚本、外链图被删（净化生效）。
- 他人/不存在帖仍 404。

- [ ] **Step 8: 提交**

```bash
git add public/thread-edit.php public/thread.php
git commit -m "feat(forum): 编辑页接入富文本, 详情页直出净化后 HTML"
```

---

## 完成后

- 全量回归：`php tests/run.php` 全绿。
- 人工走查：发帖 → 详情 → 编辑 → 详情 全链路，含图片上传与净化。
- 若使用 worktree，按 `superpowers:finishing-a-development-branch` 决定合并/PR。
