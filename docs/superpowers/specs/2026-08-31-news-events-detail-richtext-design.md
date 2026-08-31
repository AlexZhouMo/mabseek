# 新闻与活动：详情页 + 富文本正文 设计文档

**日期:** 2026-08-31
**状态:** 已定稿，待实施计划

## 目标

为现有「新闻与活动」模块增加两项能力：

1. **前台详情页** —— 首页「近况」卡片与「了解我们」页新闻列表都可点击，跳转到一篇新闻的独立详情页。
2. **后台富文本正文** —— 管理员在后台用所见即所得（WYSIWYG）编辑器撰写文章正文，正文支持内嵌图片。

本功能**扩展**现有 config-driven CRUD 引擎与 `news` 表，不引入框架、不引入构建步骤、不引入外部前端库，保持项目「零外部依赖、纯 PHP 8 + SQLite」的一贯气质。

## 已定决策（brainstorming 阶段确认）

| 议题 | 决策 |
|------|------|
| 富文本方案 | **方案 A：`contenteditable` 自研极简编辑器**（非 vendored Quill/Trix，非 Markdown） |
| 正文插图 | **支持**，编辑器「插入图片」复用现有上传校验 |
| 详情页入口 | **两处都接入 `news` 表**：`index.php` 首页近况卡片 + `about.php` 新闻列表 |
| 空正文处理 | **摘要兜底**：`body` 为空时详情页渲染 `summary` |
| HTML 净化 | **服务端 DOMDocument 白名单**（唯一可信边界；前端库不可信） |

## 非目标（YAGNI）

- 不做草稿/版本历史/定时发布。
- 不做未发布文章的后台预览页（详情页仅对 `published=1` 可见）。
- 不做正文全文搜索、评论、分页。
- 不做富文本的表格、视频、代码块（白名单只覆盖新闻文章常用元素）。

---

## 1. 架构与数据流

```
后台 news 表单（contenteditable 编辑器）
   │  提交 body（HTML 字符串）
   ▼
admin_crud_save → sanitize_html()   ← 白名单净化（唯一可信边界）
   │  净化后的安全 HTML
   ▼
news.body 列（SQLite）
   │
   ▼
public/news.php?id=N  → 直接输出已净化 body（空则回退 e(summary)）
```

新增/改动的单元及其单一职责：

| 单元 | 职责 | 依赖 |
|------|------|------|
| `app/html_sanitizer.php` | 纯函数 `sanitize_html(string $html): string`，DOMDocument 白名单净化 | `ext-dom` |
| `app/db.php` | `migrate()` 幂等补 `body` 列；新表 DDL 含 `body` | pdo_sqlite |
| `app/repositories/Collection.php` | `COLUMNS['news']` 追加 `'body'` | — |
| `app/admin/crud.php` | 新增 `richtext` 字段类型（表单渲染 + save 分支 + 列表排除）；新增 `a=upload` 动作 | `upload.php`, `html_sanitizer.php` |
| `app/admin/news.php` | 字段配置追加 `body` | — |
| `public/assets/js/richtext.js` | contenteditable 编辑器 + 工具栏 + 插图上传（前端） | — |
| `public/assets/css/admin.css` | 编辑器样式 | — |
| `public/news.php` | 前台详情页（扁平路由，复用 nav/footer partial） | Collection |
| `public/index.php` / `public/about.php` | 新闻入口接入详情页链接 | Collection |

---

## 2. 数据库迁移（幂等加 `body` 列）

现有生产库由 `CREATE TABLE IF NOT EXISTS` 建表，不会自动获得新列；部署脚本保留 DB，故 schema 变更必须**附加、幂等**。SQLite 的 `ALTER TABLE ADD COLUMN` 无 `IF NOT EXISTS`，通过 `PRAGMA table_info` 判断列是否存在。

在 `app/db.php` 的 `migrate()` 主体之后追加：

```php
// 幂等补列：先查 PRAGMA table_info 判断列是否已存在，再决定 ADD COLUMN
function add_column_if_missing(PDO $pdo, string $table, string $col, string $ddl): void {
    $cols = $pdo->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array($col, $cols, true)) {
        $pdo->exec("ALTER TABLE $table ADD COLUMN $ddl");
    }
}
```

在 `migrate()` 末尾调用：

```php
add_column_if_missing($pdo, 'news', 'body', "body TEXT NOT NULL DEFAULT ''");
```

同时把 `news` 的 `CREATE TABLE` DDL 补上 `body TEXT NOT NULL DEFAULT ''`（新库、内存测试库两条路径都保证有列）。

`app/repositories/Collection.php` 的 `COLUMNS['news']` 追加 `'body'`：

```php
'news' => ['date_day','date_ym','category','title','summary','image','sort','published','body'],
```

**幂等保证：** `migrate()` 每次连库都执行；`add_column_if_missing` 对已有列是无操作。老库补列后历史数据 `body=''`，详情页走摘要兜底。

---

## 3. 后台：`richtext` 字段类型

### 3.1 字段声明

`app/admin/news.php` 的 `fields` 数组追加一项（置于 `summary` 之后、`image` 之前或末尾，具体顺序在实施时定，需求为在表单中位于摘要附近）：

```php
['name' => 'body', 'label' => '正文', 'type' => 'richtext'],
```

### 3.2 表单渲染（`admin_crud_form` 新增分支）

在 `crud.php` 现有 `textarea/select/image/text` 分支旁新增 `richtext`：

- 渲染一个工具栏（按钮：加粗 `bold`、斜体 `italic`、标题 `H2`、`H3`、无序列表 `insertUnorderedList`、有序列表 `insertOrderedList`、插入链接、插入图片）。
- 渲染一个 `contenteditable` 编辑区 `<div class="rt-editor" contenteditable="true">`，初值为当前 `body`（**注意：编辑区初值是已净化 HTML，可直接作为 innerHTML 输出，无需再转义**）。
- 渲染一个隐藏 `<textarea name="body" hidden>`，承载真正提交的 HTML。
- 在字段处按需引入编辑器脚本：`<script src="assets/js/richtext.js" defer></script>`（仅含 richtext 字段的表单页加载）。

编辑区与隐藏 textarea 的初值填充需注意：`contenteditable` 区里放**原始 HTML**（`echo $cur`，不转义，因入库前已净化）；隐藏 textarea 里放同一份内容供无 JS 时兜底提交（`e($cur)` 转义，作为文本值）。JS 在表单 `submit` 前把编辑区 `innerHTML` 写回隐藏 textarea。

### 3.3 保存分支（`admin_crud_save`）

在字段类型判断链中新增：

```php
} elseif ($type === 'richtext') {
    $data[$name] = sanitize_html((string)($_POST[$name] ?? ''));
}
```

`crud.php` 顶部 `require_once __DIR__ . '/../html_sanitizer.php';`（与现有 `require_once __DIR__ . '/upload.php';` 并列）。

### 3.4 列表排除

`admin_crud_list` 的 `$listFields` 过滤条件追加：排除 `type === 'richtext'`（与 `checkbox`/`image` 同理，避免整段 HTML 塞进表格造成错位）：

```php
fn($f) => $f['type'] !== 'checkbox' && $f['type'] !== 'image' && $f['type'] !== 'richtext'
       && $f['name'] !== 'published' && $f['name'] !== 'sort'
```

### 3.5 插图上传端点（`admin_crud` 新增 `a=upload`）

`admin.php` 已登录路由内（已过 `auth_check` 与强制改密网关），`admin_crud` 增加动作分支：

```php
if ($a === 'upload') {
    while (ob_get_level() > 0) { ob_end_clean(); }
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405); header('Allow: POST'); exit('Method Not Allowed');
    }
    csrf_verify_or_die();
    header('Content-Type: application/json');
    try {
        $url = handle_upload('file');
        echo json_encode($url !== null ? ['ok' => true, 'url' => $url]
                                        : ['ok' => false, 'error' => '未选择文件']);
    } catch (\RuntimeException $ex) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $ex->getMessage()]);
    }
    exit;
}
```

置于 `admin_crud()` 内 `save/delete` 判断之前或之后皆可，但必须在 `new/edit/list` 之前，且自行 `exit`（返回 JSON，不走 shell 版式）。

前端 `richtext.js` 的「插入图片」：`FormData` 携带文件字段 `file` + CSRF 令牌字段（名与 `csrf_field()` 一致），`fetch('admin.php?m=news&a=upload', {method:'POST', body})`，成功则在光标处 `document.execCommand('insertHTML', false, '<img src="'+url+'" alt="">')`，失败弹出 `error`。CSRF 令牌通过页面上已存在的隐藏字段或一个 `data-` 属性读取。

**完全复用** `handle_upload()` 的既有校验：≤2MB、`finfo` MIME、`getimagesize`、仅 jpg/png/webp、随机文件名、`0640` 权限。

---

## 4. HTML 净化（`app/html_sanitizer.php`）

服务端唯一可信净化点。存储的富文本会原样渲染到公开页面，是存储型 XSS 的主要面，净化只认服务端（前端编辑器可被绕过）。

```php
<?php
declare(strict_types=1);

/**
 * 白名单净化用户提交的富文本 HTML，返回可安全直出的 HTML 字符串。
 * 依赖 ext-dom。
 */
function sanitize_html(string $html): string { /* 见下方规则 */ }
```

### 规则

- **解析：** `DOMDocument`，UTF-8。用 `mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8')` 或 `<?xml encoding>` 技巧避免中文乱码；`loadHTML` 加 `LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD` 避免注入 `<html><body>` 包裹。空/纯空白输入直接返回 `''`。
- **标签白名单：** `p h2 h3 strong b em i u ul ol li a img br blockquote`。
  - 不在白名单的元素：**解包**（unwrap，保留其子节点/文字，删除标签本身）。
  - 危险容器 `script style iframe object embed`：**整体删除**（含子孙）。
- **属性白名单：**
  - `a`：仅保留 `href`，且 `href` 必须匹配 `^https?://`（否则删除 `href`）；强制设置 `rel="noopener noreferrer"`；其余属性删除。
  - `img`：仅保留 `src`（必须以 `assets/images/uploads/` 开头，即站内上传图；否则**删除整个 img 节点**）与 `alt`；其余属性删除。
  - 其它所有元素：删除**全部**属性（含 `on*` 事件处理器、`style`、`class`、`id`）。
- **输出：** 序列化 `<body>` 的 innerHTML（不含 `<body>` 包裹标签）。

### 边界与失败处理

- 解析失败（`loadHTML` 返回 false / 抛错）：返回 `''`（宁可丢内容也不存危险 HTML）。
- 允许空结果（老数据、清空正文）。

---

## 5. 前台详情页与入口接入

### 5.1 `public/news.php`（新扁平页）

遵循现有扁平页模式（`include partials/nav.php` 头、`partials/footer.php` 尾，与 `about.php` 一致）。

```php
$id  = (int)($_GET['id'] ?? 0);
$rows = $id > 0 ? (new Collection('news'))->published('id = ?', [$id]) : [];
$item = $rows[0] ?? null;
if ($item === null) { http_response_code(404); /* 渲染简洁 404 版式（复用 nav/footer） */ }
```

- 查不到、未发布、或 `id<=0` → `404` + 简洁「未找到该新闻」版式 + 返回链接。
- 版式（存在时）：
  1. 返回/面包屑链接（回 `about.php#news`）。
  2. 分类标签（`category` → 复用 about.php 里 `res/edu/daily` 的标签文案映射）+ 日期（`date_day` + `date_ym`）。
  3. 标题 `<h1>`（`e(title)`）。
  4. 封面图：`image` 非空则 `<img src=e(image)>`。
  5. 正文区 `.article-body`：`body` 非空 → **直接输出 `body`**（已净化）；为空 → `<p><?= e($item['summary']) ?></p>` 兜底。
- `<title>` = `e(title) . ' · MabSeek'`；`<meta name="description">` = `e(summary)`（利于分享/SEO）。
- `.article-body` 排版样式（`h2/h3/p/ul/ol/blockquote/img/a` 的间距、行高、图片自适应宽度）并入 `public/assets/css/style.css`。

### 5.2 `about.php` 列表接入（[about.php:138](public/about.php:138)）

- 把每个 `.news-item` 包进 `<a href="news.php?id=<?= (int)$it['id'] ?>" class="news-item-link">…</a>`（或给 `.news-item` 加 `onclick`/包裹 `<a>`；优先真实 `<a>` 以保证可访问性与新窗打开）。
- 去掉 `data-demo="打开新闻详情"` 占位属性。
- 保留现有分类筛选 JS（依赖 `data-nc`，不改）。链接包裹层需保留 `data-nc` 供筛选，或把 `data-nc` 留在被 `<a>` 包裹的 `.news-item` 上并让筛选选择器仍命中——实施时确认筛选选择器 `#news-list .news-item` 仍能选中（若外层加 `<a>`，`.news-item` 仍在，选择器不变即可）。

### 5.3 `index.php` 近况卡片接入（[index.php:73](public/index.php:73)）

- 顶部数据装配处新增：`$recentNews = (new Collection('news'))->published();`，取前 4 条（`array_slice($recentNews, 0, 4)`，按现有 `sort ASC, id ASC` 顺序）。
- 把写死的 4 张 `home.newscard.$n.*` snippet 卡片改为 `foreach` 渲染 `$recentNews`：
  - 每张卡片包 `<a href="news.php?id=<?= (int)$n['id'] ?>">`。
  - 缩略图：`image` 非空 → `<img>`；为空 → 沿用现有 emoji/占位风格（可用一个默认字符）。
  - 标题 `e(title)`、日期 `e(date_ym)`、摘要 `e(summary)`。
- 去掉 `data-demo` 占位。
- 保留 `.news-scroller` / `.news-card` 视觉与 `home.news.*` 区块文案 snippet。
- `home.newscard.*` snippet 变为弃用：**留库不删**（避免影响潜在其它引用），仅前台不再读取。

---

## 6. 测试（`tests/run.php`，零依赖断言）

新增用例：

1. **`sanitize_html` 安全性：**
   - `<script>alert(1)</script>` → 被整体删除，结果不含 `script`。
   - `<img src=x onerror=alert(1)>` → `onerror` 被剥离；因 `src` 非站内 uploads，`img` 被删除。
   - `<a href="javascript:alert(1)">x</a>` → `href` 被删除（非 http/https），文字 `x` 保留。
   - `<a href="https://a.com">x</a>` → 保留 `href`，补 `rel="noopener noreferrer"`。
   - `<img src="assets/images/uploads/a.jpg" alt="图">` → 保留。
   - `<p><strong>粗</strong></p><div onclick=x>u</div>` → 保留 `p/strong`，`div` 解包为文字 `u`，无 `onclick`。
   - 中文正文往返不乱码。
   - 空/纯空白输入 → `''`。
2. **迁移幂等：** 对已建 `news` 表重复调用 `migrate()` 不抛错；`PRAGMA table_info(news)` 含 `body` 且仅一列。
3. **`Collection('news')` 读写 `body`：** `create` 带 `body` → `find` 读回一致。
4. **空正文兜底逻辑：** 若把「body 为空取 summary」抽成小函数（如 `news_body_html($item)`），对空 body 返回转义后的 summary、对非空 body 返回原 body。

---

## 7. 部署影响

净化依赖 `ext-dom`（Ubuntu 的 `php-xml` 包；PHP 多数发行版默认已带）。

- `deploy/deploy-mabseek.sh`：依赖检查扩展列表由 `pdo_sqlite sodium` 扩为 `pdo_sqlite sodium dom`；缺失提示改为 `apt-get install -y php-sqlite3 php-sodium php-xml`。
- `deploy/DEPLOY-ubuntu-http.md` 第 0 步：扩展检查加 `dom`。
- DB 迁移为纯附加、幂等；部署脚本保留 DB / 上传目录的策略不变；老库连库时自动补 `body` 列，无需人工介入。

---

## 实施顺序建议

1. 净化器 `html_sanitizer.php` + 单测（安全核心，先立住）。
2. DB 迁移补列 + `Collection` 白名单 + 单测。
3. `richtext` 字段类型（表单/保存/列表）+ `a=upload` 端点 + `news.php` 字段配置。
4. 前端 `richtext.js` + 编辑器样式。
5. `public/news.php` 详情页 + `.article-body` 样式。
6. `about.php` / `index.php` 入口接入。
7. 部署脚本/文档的 `dom` 扩展检查。
8. 全量 `php tests/run.php` 回归。
