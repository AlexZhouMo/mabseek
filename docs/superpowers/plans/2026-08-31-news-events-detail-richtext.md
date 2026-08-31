# 新闻与活动：详情页 + 富文本正文 实施计划

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 为「新闻与活动」增加前台详情页与后台富文本正文（含内插图），复用现有 config-driven CRUD 引擎与 `news` 表。

**Architecture:** 后台 contenteditable 编辑器提交 HTML → 服务端 `sanitize_html()` DOMDocument 白名单净化 → 存入 `news.body` → 前台 `public/news.php?id=N` 直出已净化 HTML（空则回退摘要）。首页近况卡片与 about 新闻列表都改为读 `news` 表并链接详情页。

**Tech Stack:** PHP 8、pdo_sqlite、ext-dom（DOMDocument）、原生 JS（contenteditable + `document.execCommand`）、零构建。

**设计依据:** [docs/superpowers/specs/2026-08-31-news-events-detail-richtext-design.md](../specs/2026-08-31-news-events-detail-richtext-design.md)

**通用约定:**
- 每个任务在功能分支上按 TDD 推进；测试用 `php tests/run.php` 跑全量（`test_*.php` 自动收集）。
- 测试库为内存库：文件头 `putenv('MABSEEK_DB=:memory:');`。
- 提交署名:`git -c user.name="AlexZhouMo" -c user.email="AlexZhouMo@users.noreply.github.com" commit`（公开仓库隐私要求）。提交信息以 `Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>` 结尾。

---

## 文件结构

| 动作 | 文件 | 职责 |
|------|------|------|
| 新建 | `app/html_sanitizer.php` | `sanitize_html()` 白名单净化（纯函数，依赖 ext-dom） |
| 新建 | `tests/test_sanitizer.php` | 净化器安全性单测 |
| 新建 | `public/news.php` | 前台新闻详情页 |
| 新建 | `public/assets/js/richtext.js` | contenteditable 编辑器（工具栏 + 插图上传） |
| 改 | `app/db.php` | `migrate()` 幂等补 `body` 列；新表 DDL 含 `body` |
| 改 | `app/repositories/Collection.php` | `COLUMNS['news']` 追加 `'body'` |
| 改 | `app/admin/news.php` | `fields` 追加 `body`（richtext） |
| 改 | `app/admin/crud.php` | `richtext` 字段类型（表单/保存/列表排除）+ `a=upload` 端点 + require 净化器 |
| 改 | `public/assets/css/admin.css` | 编辑器样式 |
| 改 | `public/assets/css/style.css` | `.article-body` 详情页正文排版 |
| 改 | `public/about.php` | 新闻列表项包 `<a>` 链接详情页 |
| 改 | `public/index.php` | 近况卡片改读 `news` 表并链接详情页 |
| 改 | `deploy/deploy-mabseek.sh` | 依赖检查加 `dom` 扩展 |
| 改 | `deploy/DEPLOY-ubuntu-http.md` | 第 0 步扩展检查加 `dom` |

---

## Task 1: HTML 净化器（安全核心，先立住）

**Files:**
- Create: `app/html_sanitizer.php`
- Test: `tests/test_sanitizer.php`

- [ ] **Step 1: 写失败测试** `tests/test_sanitizer.php`

```php
<?php
require_once __DIR__ . '/../app/html_sanitizer.php';

// 危险容器整体删除
$r = sanitize_html('<p>hi</p><script>alert(1)</script>');
check(strpos($r, 'script') === false, 'script 标签及内容被删除');
check(strpos($r, 'alert') === false, 'script 内文本一并删除');
check(strpos($r, '<p>hi</p>') !== false, '白名单 p 保留');

// 事件属性剥离 + 非站内图删除
$r = sanitize_html('<img src="http://evil/x.png" onerror="alert(1)">');
check(strpos($r, 'onerror') === false, 'onerror 事件属性被剥离');
check(strpos($r, '<img') === false, '非站内 img 整体删除');

// 站内上传图保留 src/alt
$r = sanitize_html('<img src="assets/images/uploads/a.jpg" alt="图" width="9">');
check(strpos($r, 'assets/images/uploads/a.jpg') !== false, '站内 img 保留');
check(strpos($r, 'alt="图"') !== false, 'img alt 保留');
check(strpos($r, 'width') === false, 'img 其它属性剥离');

// javascript: 链接去 href、保留文字
$r = sanitize_html('<a href="javascript:alert(1)">x</a>');
check(strpos($r, 'javascript') === false, 'javascript: href 被删除');
check(strpos($r, 'x') !== false, '链接文字保留');

// http(s) 链接保留 href 并补 rel
$r = sanitize_html('<a href="https://a.com" title="t">x</a>');
check(strpos($r, 'href="https://a.com"') !== false, 'https href 保留');
check(strpos($r, 'noopener') !== false, 'a 补 rel=noopener');
check(strpos($r, 'title') === false, 'a 其它属性剥离');

// 非白名单元素解包（保留文字）
$r = sanitize_html('<div onclick="x"><strong>粗</strong>u</div>');
check(strpos($r, 'div') === false, 'div 被解包');
check(strpos($r, 'onclick') === false, 'onclick 不残留');
check(strpos($r, '<strong>粗</strong>') !== false, '内层白名单 strong 保留');
check(strpos($r, 'u') !== false, 'div 内文本保留');

// 中文往返不乱码
$r = sanitize_html('<p>抗体求索</p>');
check(strpos($r, '抗体求索') !== false, '中文不乱码');

// 空输入
check(sanitize_html('') === '', '空输入返回空');
check(sanitize_html("  \n ") === '', '纯空白返回空');
```

- [ ] **Step 2: 运行确认失败**

Run: `php tests/run.php`
Expected: FAIL —— `Call to undefined function sanitize_html()`（或 require 时 fatal）。

- [ ] **Step 3: 实现 `app/html_sanitizer.php`**

```php
<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';   // 需要 UPLOAD_URL 判定站内图

const SANITIZE_ALLOWED_TAGS = ['p','h2','h3','strong','b','em','i','u','ul','ol','li','a','img','br','blockquote'];
const SANITIZE_DROP_TAGS    = ['script','style','iframe','object','embed'];

/** 白名单净化富文本 HTML，返回可安全直出的 HTML；依赖 ext-dom。失败/空 → '' */
function sanitize_html(string $html): string {
    $html = trim($html);
    if ($html === '') return '';

    $dom = new DOMDocument('1.0', 'UTF-8');
    // <?xml encoding> 前缀：强制 UTF-8 解析，避免中文被当 Latin-1；NOIMPLIED/NODEFDTD：不注入 html/body/DTD
    $wrapped = '<?xml encoding="UTF-8"?><div>' . $html . '</div>';
    $prev = libxml_use_internal_errors(true);
    $ok = $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    if ($ok === false) return '';

    $root = $dom->getElementsByTagName('div')->item(0);   // 我们包裹的最外层 div
    if ($root === null) return '';

    sanitize_node($root);

    $out = '';
    foreach (iterator_to_array($root->childNodes) as $child) {
        $out .= $dom->saveHTML($child);
    }
    return trim($out);
}

/** 递归净化：自底向上，删除危险容器、解包非白名单元素、清洗白名单元素属性 */
function sanitize_node(DOMNode $node): void {
    foreach (iterator_to_array($node->childNodes) as $child) {
        if ($child instanceof DOMText) continue;               // 文本保留
        if (!($child instanceof DOMElement)) {                 // 注释 / PI 等：删除
            $node->removeChild($child);
            continue;
        }
        $tag = strtolower($child->tagName);

        if (in_array($tag, SANITIZE_DROP_TAGS, true)) {         // 危险容器整体删除
            $node->removeChild($child);
            continue;
        }

        sanitize_node($child);                                  // 先处理子孙

        if (!in_array($tag, SANITIZE_ALLOWED_TAGS, true)) {     // 非白名单：解包
            sanitize_unwrap($child);
            continue;
        }
        sanitize_attrs($child, $tag);                           // 白名单：清洗属性
    }
}

/** 解包：把元素的子节点提到它前面，再删除元素本身 */
function sanitize_unwrap(DOMElement $el): void {
    $parent = $el->parentNode;
    if ($parent === null) return;
    while ($el->firstChild) {
        $parent->insertBefore($el->firstChild, $el);
    }
    $parent->removeChild($el);
}

/** 按标签重建属性白名单：先读需保留的值，删光全部属性，再按需补回 */
function sanitize_attrs(DOMElement $el, string $tag): void {
    if ($tag === 'a') {
        $href = trim($el->getAttribute('href'));
        sanitize_strip_attrs($el);
        if ($href !== '' && preg_match('#^https?://#i', $href)) {
            $el->setAttribute('href', $href);
            $el->setAttribute('rel', 'noopener noreferrer');
        }
    } elseif ($tag === 'img') {
        $src = trim($el->getAttribute('src'));
        $alt = $el->getAttribute('alt');
        sanitize_strip_attrs($el);
        if ($src !== '' && str_starts_with($src, UPLOAD_URL . '/')) {   // 仅站内上传图
            $el->setAttribute('src', $src);
            $el->setAttribute('alt', $alt);
        } else {
            $el->parentNode?->removeChild($el);                          // 外链图整体删除
        }
    } else {
        sanitize_strip_attrs($el);                                       // 其余白名单元素：删光属性
    }
}

/** 删除元素上所有属性 */
function sanitize_strip_attrs(DOMElement $el): void {
    foreach (iterator_to_array($el->attributes) as $attr) {
        $el->removeAttribute($attr->name);
    }
}
```

- [ ] **Step 4: 运行确认通过**

Run: `php tests/run.php`
Expected: PASS（新增 sanitizer 断言全绿；其余用例不受影响）。

- [ ] **Step 5: 提交**

```bash
git add app/html_sanitizer.php tests/test_sanitizer.php
git -c user.name="AlexZhouMo" -c user.email="AlexZhouMo@users.noreply.github.com" \
  commit -m "feat(news): HTML 白名单净化器 sanitize_html + 单测

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Task 2: DB 迁移补 `body` 列 + Collection 白名单 + 字段配置

一并改动，因 `tests/test_crud_fields.php` 断言「news 可编辑字段与表结构一致」——列与字段必须同时增加。

**Files:**
- Modify: `app/db.php`（`migrate()` 末尾 + `news` 建表 DDL）
- Modify: `app/repositories/Collection.php:8`（`COLUMNS['news']`）
- Modify: `app/admin/news.php`（`fields`）
- Test: `tests/test_db.php`（追加迁移幂等 + body 读写断言）

- [ ] **Step 1: 追加失败测试** 到 `tests/test_db.php` 末尾

```php
// body 列存在（新库）
$newsCols = $pdo->query("PRAGMA table_info(news)")->fetchAll(PDO::FETCH_COLUMN, 1);
check(in_array('body', $newsCols, true), 'news.body 列存在');

// 迁移幂等：重复 migrate 不报错、body 只有一列
migrate($pdo);
$cols2 = $pdo->query("PRAGMA table_info(news)")->fetchAll(PDO::FETCH_COLUMN, 1);
check(count(array_keys($cols2, 'body')) === 1, '重复 migrate 后 body 仅一列');

// Collection 能写读 body
require_once __DIR__ . '/../app/repositories/Collection.php';
$id = (new Collection('news'))->create([
    'date_day'=>'01','date_ym'=>'2026·09','category'=>'res',
    'title'=>'T','summary'=>'S','body'=>'<p>正文</p>','sort'=>0,'published'=>1,
]);
$row = (new Collection('news'))->find($id);
check(($row['body'] ?? '') === '<p>正文</p>', 'Collection 读写 news.body');
```

- [ ] **Step 2: 运行确认失败**

Run: `php tests/run.php`
Expected: FAIL —— `news.body 列存在` 失败；`Collection 读写 news.body` 读回空（因白名单未含 body，写入被过滤）。

- [ ] **Step 3a: `app/db.php` —— `news` 建表 DDL 加 body**

把 `news` 建表语句改为（在 `summary TEXT NOT NULL,` 后加 `body`）：

```sql
    CREATE TABLE IF NOT EXISTS news (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      date_day TEXT NOT NULL, date_ym TEXT NOT NULL,
      category TEXT NOT NULL, title TEXT NOT NULL, summary TEXT NOT NULL,
      body TEXT NOT NULL DEFAULT '',
      image TEXT, sort INTEGER DEFAULT 0, published INTEGER DEFAULT 1,
      created_at TEXT NOT NULL, updated_at TEXT NOT NULL
    );
```

- [ ] **Step 3b: `app/db.php` —— 幂等补列 helper + 调用**

在 `migrate()` 函数的 `SQL);` 之后、函数闭合 `}` 之前追加：

```php
    add_column_if_missing($pdo, 'news', 'body', "body TEXT NOT NULL DEFAULT ''");
```

在文件末尾（`migrate()` 之外）新增 helper：

```php
/** 幂等补列：PRAGMA 判断列是否存在，缺失才 ALTER（SQLite 无 ADD COLUMN IF NOT EXISTS） */
function add_column_if_missing(PDO $pdo, string $table, string $col, string $ddl): void {
    $cols = $pdo->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array($col, $cols, true)) {
        $pdo->exec("ALTER TABLE $table ADD COLUMN $ddl");
    }
}
```

- [ ] **Step 3c: `app/repositories/Collection.php:8` —— 白名单加 body**

```php
        'news'          => ['date_day','date_ym','category','title','summary','image','sort','published','body'],
```

- [ ] **Step 3d: `app/admin/news.php` —— fields 追加 body（摘要之后）**

在 `summary` 行之后插入：

```php
        ['name'=>'body',     'label'=>'正文',              'type'=>'richtext'],
```

- [ ] **Step 4: 运行确认通过**

Run: `php tests/run.php`
Expected: PASS —— 含 `test_crud_fields.php` 的「字段与表结构一致」（现在两边都含 body）。

- [ ] **Step 5: 提交**

```bash
git add app/db.php app/repositories/Collection.php app/admin/news.php tests/test_db.php
git -c user.name="AlexZhouMo" -c user.email="AlexZhouMo@users.noreply.github.com" \
  commit -m "feat(news): 幂等迁移 news.body 列 + Collection 白名单 + 正文字段

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Task 3: `richtext` 字段类型 + 插图上传端点

**Files:**
- Modify: `app/admin/crud.php`（顶部 require；`admin_crud` 加 `a=upload`；`admin_crud_save` 加 richtext 分支；`admin_crud_list` 排除 richtext；`admin_crud_form` 加 richtext 渲染）

- [ ] **Step 1: 顶部 require 净化器**

`crud.php` 第 6 行 `require_once __DIR__ . '/upload.php';` 之后加：

```php
require_once __DIR__ . '/../html_sanitizer.php';
```

- [ ] **Step 2: `admin_crud()` 加 `a=upload` 端点**

在 `admin_crud()` 内、`if ($a === 'save' || $a === 'delete') {` 之前插入：

```php
    if ($a === 'upload') {
        while (ob_get_level() > 0) { ob_end_clean(); }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405); header('Allow: POST'); exit('Method Not Allowed');
        }
        csrf_verify_or_die();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $url = handle_upload('file');
            echo json_encode($url !== null ? ['ok'=>true,'url'=>$url] : ['ok'=>false,'error'=>'未选择文件'], JSON_UNESCAPED_UNICODE);
        } catch (\RuntimeException $ex) {
            http_response_code(400);
            echo json_encode(['ok'=>false,'error'=>$ex->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
```

- [ ] **Step 3: `admin_crud_save()` 加 richtext 分支**

在类型判断链中，`} elseif ($type === 'select') {` 之前插入：

```php
        } elseif ($type === 'richtext') {
            $data[$name] = sanitize_html((string)($_POST[$name] ?? ''));
```

- [ ] **Step 4: `admin_crud_list()` 排除 richtext 列**

把 `$listFields` 的过滤闭包改为：

```php
    $listFields = array_filter(
        $cfg['fields'],
        fn($f) => $f['type'] !== 'checkbox' && $f['type'] !== 'image' && $f['type'] !== 'richtext'
               && $f['name'] !== 'published' && $f['name'] !== 'sort'
    );
```

- [ ] **Step 5: `admin_crud_form()` 加 richtext 渲染**

在表单字段渲染的 `<?php if ($type === 'textarea'): ?>` 分支之前（即紧跟 `<label class="field-label">` 之后的类型分支链里）新增。具体：把现有

```php
<?php if ($type === 'textarea'): ?>
```

改为在其前插入 richtext 分支——最终该段为：

```php
<?php if ($type === 'richtext'):
        $rt = sanitize_html($cur);   // 即使 old() 回填未净化内容，渲染前再净化，杜绝后台自 XSS
?>
      <div class="rt-field" data-rt>
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
        <div class="rt-editor" contenteditable="true"><?= $rt ?></div>
        <textarea name="<?= e($name) ?>" class="rt-source" hidden><?= e($rt) ?></textarea>
      </div>
      <script src="assets/js/richtext.js" defer></script>
<?php elseif ($type === 'textarea'): ?>
```

（其余 `elseif ($type === 'select')` / `image` / `else text` 分支保持原样。）

- [ ] **Step 6: 手工冒烟（本地起服务）**

Run: 用 `.claude/launch.json` 的 `mabseek-php` 启动，登录后台 `admin.php?m=news&a=new`。
Expected: 正文字段显示工具栏 + 可编辑区；无 PHP 报错。（前端行为在 Task 4 接入 JS 后再验证。）

- [ ] **Step 7: 全量测试 + 提交**

```bash
php tests/run.php   # 期望仍全绿（本任务不新增断言，确保未破坏既有）
git add app/admin/crud.php
git -c user.name="AlexZhouMo" -c user.email="AlexZhouMo@users.noreply.github.com" \
  commit -m "feat(news): CRUD 引擎支持 richtext 字段类型 + 插图上传端点

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Task 4: 前端编辑器 richtext.js + 样式

**Files:**
- Create: `public/assets/js/richtext.js`
- Modify: `public/assets/css/admin.css`（追加编辑器样式）

- [ ] **Step 1: 创建 `public/assets/js/richtext.js`**

```js
(function () {
  document.querySelectorAll('[data-rt]').forEach(function (field) {
    var editor = field.querySelector('.rt-editor');
    var source = field.querySelector('.rt-source');
    var form = field.closest('form');
    if (!editor || !source || !form) return;

    // 工具栏命令
    field.querySelectorAll('.rt-btn[data-cmd]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        editor.focus();
        var cmd = btn.getAttribute('data-cmd');
        var val = btn.getAttribute('data-val') || null;
        if (cmd === 'createLink') {
          var url = prompt('链接地址（http/https）：', 'https://');
          if (!url) return;
          document.execCommand('createLink', false, url);
        } else if (cmd === 'formatBlock') {
          document.execCommand('formatBlock', false, val);
        } else {
          document.execCommand(cmd, false, null);
        }
      });
    });

    // 插入图片：复用后台上传端点，返回站内 URL
    var imgBtn = field.querySelector('[data-rt-image]');
    if (imgBtn) {
      imgBtn.addEventListener('click', function () {
        var input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/jpeg,image/png,image/webp';
        input.addEventListener('change', function () {
          if (input.files && input.files[0]) uploadImage(input.files[0]);
        });
        input.click();
      });
    }

    function uploadImage(file) {
      var token = form.querySelector('input[name="_csrf"]');
      var data = new FormData();
      data.append('file', file);
      if (token) data.append('_csrf', token.value);
      editor.focus();
      fetch('admin.php?m=news&a=upload', { method: 'POST', body: data })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (res && res.ok && res.url) {
            document.execCommand('insertHTML', false, '<img src="' + res.url + '" alt="">');
          } else {
            alert('图片上传失败：' + ((res && res.error) || '未知错误'));
          }
        })
        .catch(function () { alert('图片上传失败：网络错误'); });
    }

    // 提交前把编辑区内容同步进隐藏 textarea（服务端会再净化）
    form.addEventListener('submit', function () {
      source.value = editor.innerHTML;
    });
  });
})();
```

- [ ] **Step 2: 追加编辑器样式到 `public/assets/css/admin.css` 末尾**

```css
/* 富文本编辑器 */
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
```

- [ ] **Step 3: 手工冒烟（本地起服务，验证工作流）**

Run: 启动服务，登录 → `admin.php?m=news&a=new` → 填必填项 → 正文里输入文字、加粗、插入一张本地图 → 保存 → 回列表 → 编辑该条。
Expected:
- 加粗/标题/列表/链接生效；插入图片后编辑区出现图，且 `assets/images/uploads/` 落一文件；
- 保存成功回列表（列表不显示正文列，无错位）；
- 重新编辑时正文原样回显。

用 preview 工具核对（若可用）：`preview_console_logs` 无报错；`preview_network` 中 `a=upload` 返回 `{ok:true,url:...}`。

- [ ] **Step 4: 提交**

```bash
git add public/assets/js/richtext.js public/assets/css/admin.css
git -c user.name="AlexZhouMo" -c user.email="AlexZhouMo@users.noreply.github.com" \
  commit -m "feat(news): 后台富文本编辑器（contenteditable + 插图上传）

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Task 5: 前台详情页 public/news.php

**Files:**
- Create: `public/news.php`
- Modify: `public/assets/css/style.css`（追加 `.article-body` 排版）

- [ ] **Step 1: 创建 `public/news.php`**

```php
<?php
require __DIR__ . '/../app/bootstrap.php';
$active = 'about'; $navOnDark = false; $contactHref = 'index.php#contact';

$id   = (int)($_GET['id'] ?? 0);
$rows = $id > 0 ? (new Collection('news'))->published('id = ?', [$id]) : [];
$item = $rows[0] ?? null;

$tagMap = ['res'=>'科研类','edu'=>'育人类','daily'=>'日常活动'];
if ($item === null) {
    http_response_code(404);
    $pageTitle = '未找到该新闻 · MabSeek 抗体求索';
} else {
    $pageTitle = $item['title'] . ' · MabSeek 抗体求索';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<?php if ($item !== null): ?>
<meta name="description" content="<?= e($item['summary']) ?>">
<?php else: ?>
<meta name="robots" content="noindex">
<?php endif; ?>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🧬</text></svg>">
</head>
<body>

<?php include __DIR__ . '/partials/nav.php'; ?>

<?php if ($item === null): ?>
<section class="page-hero">
  <div class="container">
    <div class="breadcrumb"><a href="index.php">首页</a> / <a href="about.php#news">新闻与活动</a> / 未找到</div>
    <h1>未找到该新闻</h1>
    <p class="lead">该新闻可能已下线或不存在。</p>
    <p style="margin-top:18px"><a href="about.php#news" class="btn btn-outline">返回新闻与活动</a></p>
  </div>
</section>
<?php else:
    $tagText  = $tagMap[$item['category']] ?? '';
    $tagClass = $item['category'] === 'res' ? 'tag green' : 'tag';
    $body = (string)($item['body'] ?? '');
?>
<article class="section">
  <div class="container" style="max-width:820px">
    <div class="breadcrumb reveal"><a href="index.php">首页</a> / <a href="about.php#news">新闻与活动</a> / <?= e($item['title']) ?></div>
    <div class="reveal" style="margin:14px 0 10px">
      <?php if ($tagText !== ''): ?><span class="<?= $tagClass ?>" style="font-size:12px"><?= e($tagText) ?></span><?php endif; ?>
      <span style="color:var(--ink-3);font-size:13px;margin-left:10px"><?= e($item['date_ym']) ?> · <?= e($item['date_day']) ?></span>
    </div>
    <h1 class="reveal d1" style="font-size:30px;line-height:1.3"><?= e($item['title']) ?></h1>
<?php if (!empty($item['image'])): ?>
    <img class="reveal d2" src="<?= e($item['image']) ?>" alt="<?= e($item['title']) ?>" style="width:100%;border-radius:var(--radius-lg);margin:22px 0;box-shadow:var(--sh)">
<?php endif; ?>
    <div class="article-body reveal d2">
<?php if (trim($body) !== ''): ?>
      <?= $body /* 已在写入时净化，直出 */ ?>
<?php else: ?>
      <p><?= e($item['summary']) ?></p>
<?php endif; ?>
    </div>
    <div style="margin-top:34px"><a href="about.php#news" class="link-more">← 返回新闻与活动</a></div>
  </div>
</article>
<?php endif; ?>

<?php include __DIR__ . '/partials/footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>
```

- [ ] **Step 2: 追加 `.article-body` 排版到 `public/assets/css/style.css` 末尾**

```css
/* 新闻详情正文 */
.article-body { font-size: 16px; line-height: 1.85; color: var(--ink-2, #333); }
.article-body h2 { font-size: 22px; margin: 26px 0 12px; }
.article-body h3 { font-size: 18px; margin: 20px 0 10px; }
.article-body p { margin: 14px 0; }
.article-body ul, .article-body ol { padding-left: 24px; margin: 14px 0; }
.article-body li { margin: 6px 0; }
.article-body a { color: var(--purple, #6b5bff); text-decoration: underline; }
.article-body img { max-width: 100%; height: auto; border-radius: 10px; margin: 16px 0; display: block; }
.article-body blockquote { margin: 18px 0; padding: 10px 18px; border-left: 4px solid var(--purple-100, #cfd0ff); background: var(--purple-050, #f4f4ff); color: var(--ink-2, #444); }
```

- [ ] **Step 3: 手工冒烟**

Run: 起服务，访问 `news.php?id=<有正文的新闻 id>`、`news.php?id=<无正文的老新闻 id>`、`news.php?id=999999`（不存在）、`news.php`（无 id）。
Expected:
- 有正文 → 正文富文本渲染（含插图）；
- 无正文 → 显示摘要；
- 不存在/无 id → 404 版式 + 返回链接；
- 用 `preview_network` 确认不存在 id 的响应码为 404。

- [ ] **Step 4: 提交**

```bash
git add public/news.php public/assets/css/style.css
git -c user.name="AlexZhouMo" -c user.email="AlexZhouMo@users.noreply.github.com" \
  commit -m "feat(news): 前台新闻详情页 news.php + 正文排版（空正文摘要兜底）

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Task 6: 两处入口接入详情页

**Files:**
- Modify: `public/about.php`（新闻列表项 → 链接）
- Modify: `public/index.php`（近况卡片 → 读库 + 链接）

- [ ] **Step 1: `public/about.php` —— 列表项包链接**

把第 138 行的 `.news-item` 从 `<div>` 改为 `<a>`（保留 `data-nc`、去 `data-demo`），使其本身成为链接：

```php
      <a class="news-item" href="news.php?id=<?= (int)$it['id'] ?>" data-nc="<?= e($it['category']) ?>"><div class="date"><div class="d"><?= e($it['date_day']) ?></div><div class="m"><?= e($it['date_ym']) ?></div></div><div class="n-body"><span class="<?= $tagClass ?>" style="font-size:11px"><?= e($tagText) ?></span><h4><?= e($it['title']) ?></h4><p><?= e($it['summary']) ?></p></div></a>
```

筛选 JS 选择器 `#news-list .news-item`（[about.php:200](public/about.php:200)）无需改动——它按 `.news-item` 选中并 toggle `display`，`<a>` 依然命中。

在页内 `<style>`（[about.php:19](public/about.php:19) `.news-item` 规则）追加去链接下划线/继承色，避免 `<a>` 默认样式：

```css
.news-item { text-decoration:none; color:inherit; }
```

（并入该文件已有的 `.news-item { … }` 规则或新增一行皆可。）

- [ ] **Step 2: `public/index.php` —— 顶部装配近况数据**

在第 5 行 `$partners = …;` 之后追加：

```php
$recentNews = array_slice((new Collection('news'))->published(), 0, 4);
```

- [ ] **Step 3: `public/index.php` —— 近况卡片改读库并链接**

把第 74–79 行的 `for ($n=1..4)` 写死 snippet 块整体替换为：

```php
<?php foreach ($recentNews as $n): ?>
      <a class="news-card" href="news.php?id=<?= (int)$n['id'] ?>">
        <div class="nc-thumb"><?php if (!empty($n['image'])): ?><img src="<?= e($n['image']) ?>" alt="<?= e($n['title']) ?>"><?php else: ?>🧬<?php endif; ?></div>
        <div class="nc-body"><div class="nc-date"><?= e($n['date_ym']) ?></div><h4><?= e($n['title']) ?></h4><p><?= e($n['summary']) ?></p></div>
      </a>
<?php endforeach; ?>
```

说明：`home.newscard.*` snippet 自此弃用（留库不删）；`.news-scroller`/`.news-card` 外观与 `home.news.*` 区块文案 snippet 不变。

- [ ] **Step 4: `public/index.php` —— 缩略图图片样式**

确保 `.nc-thumb img` 能正确显示。若 `style.css` 无相应规则，在 `index.php` 现有内联 `<style>`（若有）或 `style.css` 末尾补：

```css
.news-card { text-decoration:none; color:inherit; display:block; }
.nc-thumb img { width:100%; height:100%; object-fit:cover; display:block; }
```

实施时先 `grep -n "nc-thumb" public/assets/css/style.css` 确认已有规则再决定是否补充，避免重复定义。

- [ ] **Step 5: 手工冒烟**

Run: 起服务，首页看近况卡片（应为库中前 4 条，可点击）；点卡片 → 详情页；about 页新闻列表点条目 → 详情页；about 页分类筛选仍工作。
Expected:
- 首页卡片来自 DB，有图显图、无图显 🧬；
- 两处点击都进 `news.php?id=N`；
- `preview_console_logs` 无报错；筛选切换正常。

- [ ] **Step 6: 提交**

```bash
git add public/about.php public/index.php public/assets/css/style.css
git -c user.name="AlexZhouMo" -c user.email="AlexZhouMo@users.noreply.github.com" \
  commit -m "feat(news): 首页近况与 about 列表接入 news 表并链接详情页

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Task 7: 部署脚本/文档增加 ext-dom 依赖检查

**Files:**
- Modify: `deploy/deploy-mabseek.sh:96`（扩展循环）与 `:99`（缺失提示）
- Modify: `deploy/DEPLOY-ubuntu-http.md`（第 0 步扩展检查）

- [ ] **Step 1: `deploy/deploy-mabseek.sh` —— 扩展列表加 dom**

把第 96 行：

```bash
for ext in pdo_sqlite sodium; do
```

改为：

```bash
for ext in pdo_sqlite sodium dom; do
```

把第 99 行的缺失提示：

```bash
[ -z "$MISSING" ] || die "缺少 PHP 扩展:$MISSING （apt-get install -y php-sqlite3 php-sodium && systemctl restart php*-fpm）"
```

改为：

```bash
[ -z "$MISSING" ] || die "缺少 PHP 扩展:$MISSING （apt-get install -y php-sqlite3 php-sodium php-xml && systemctl restart php*-fpm）"
```

- [ ] **Step 2: 语法校验**

Run: `bash -n deploy/deploy-mabseek.sh`
Expected: 无输出（语法正确）。

- [ ] **Step 3: `deploy/DEPLOY-ubuntu-http.md` 第 0 步**

把扩展检查行：

```bash
php -m | grep -Ei 'pdo_sqlite|sodium'
```

改为：

```bash
php -m | grep -Ei 'pdo_sqlite|sodium|dom'
```

并把其下缺失安装提示补上 `php-xml`：

```
若 `pdo_sqlite`、`sodium` 或 `dom` 缺失：`sudo apt-get install -y php-sqlite3 php-sodium php-xml && sudo systemctl restart php*-fpm`。
```

- [ ] **Step 4: 提交**

```bash
git add deploy/deploy-mabseek.sh deploy/DEPLOY-ubuntu-http.md
git -c user.name="AlexZhouMo" -c user.email="AlexZhouMo@users.noreply.github.com" \
  commit -m "chore(deploy): 依赖检查增加 ext-dom（php-xml），供 HTML 净化使用

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Task 8: 全量回归

**Files:** 无改动（验证）

- [ ] **Step 1: 跑全量测试**

Run: `php tests/run.php`
Expected: 全部通过（原 44 条 + 本次新增 sanitizer/db 断言），`0 failed`。

- [ ] **Step 2: 端到端手工核对（本地服务）**

清库重跑 seed 后走一遍：新建带正文+插图的新闻 → 前台首页/about 可见并可点 → 详情页正文/插图正确 → 老数据（无正文）详情页摘要兜底 → 不存在 id → 404。
另核对净化：在正文源码里手动贴 `<script>`/外链 `<img>`（可用浏览器 devtools 改隐藏 textarea 值或直接 POST），保存后详情页不出现脚本/外链图。

- [ ] **Step 3: 无新提交**（前序任务已各自提交）。若手工核对中发现问题，回到对应任务修复并重跑本任务。

---

## Self-Review（写计划后自查）

- **Spec 覆盖:** 净化器(§4→T1)、迁移+白名单(§2→T2)、richtext 类型+上传端点(§3→T3/T4)、详情页+兜底(§5.1→T5)、两处入口(§5.2/5.3→T6)、测试(§6→T1/T2/T8)、部署 dom(§7→T7)——全部有对应任务。
- **类型/命名一致:** `sanitize_html`、`add_column_if_missing`、`COLUMNS['news']` 含 `body`、字段 `type=='richtext'`、上传端点 `a=upload` 字段名 `file`、CSRF 字段 `_csrf`、`UPLOAD_URL='assets/images/uploads'`——跨任务一致。
- **无占位符:** 所有代码步骤含完整代码；测试含真实断言。
- **既有测试兼容:** `test_crud_fields.php` 断言字段==列，T2 同时加列与字段以保持一致。
