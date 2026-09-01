# 论坛发帖复用新闻富文本编辑器 —— 设计

- 日期：2026-09-01
- 状态：已确认，待实现
- 关联页面：`public/thread-new.php`、`public/thread-edit.php`、`public/thread.php`、`app/admin/news.php`

## 目标

让论坛发帖 / 编辑页使用与后台"新闻与活动"正文相同的富文本编辑器（含图片上传），
替换现有的纯文本 `<textarea>`。编辑器只保留一份定义，新闻端与论坛端共用。

## 已定决策

1. **编辑器能力**：完整能力，会员也能插入图片。工具栏沿用现状（加粗 / 斜体 / H2 / H3 / 无序列表 / 有序列表 / 链接 / 插入图片）。
2. **正文渲染**：统一按已净化的 HTML 直出。库中当前 `forum_threads` 存量为 0 条，不做纯文本回退分支、不做数据迁移。
3. **长度校验**：先 `sanitize_html` 净化，再 `strip_tags` 取纯文本长度，判 1–5000 字；纯文本为空则拒绝。图片与标签不计入长度。
4. **复用方式**：抽取共享 partial（方案 A），新闻后台与发帖 / 编辑页共同引用。
5. **会员上传端点**：新增 `public/upload.php`，会员登录 + CSRF 独立鉴权，复用现有图片校验；不复用后台管理员端点。
6. **会员上传限流**：本期不加（已有登录 + CSRF + 大小 + 类型约束；发帖本身有 `thread_can_post_now` 限流）。

## 组件与边界

### 新增 `public/partials/richtext-field.php`
唯一的富文本编辑器定义。渲染工具栏 + `contenteditable` 编辑区 + 隐藏 `<textarea class="rt-source">`。

- 入参（约定通过局部变量传入，include 前赋值）：
  - `$rtName`：字段名（隐藏 textarea 的 `name`）。
  - `$rtValue`：当前值（HTML 字符串，渲染前再经 `sanitize_html()`，杜绝回填自 XSS）。
  - `$rtUploadUrl`：图片上传端点 URL，写入容器的 `data-upload-url` 属性。
  - `$rtRequired`（可选，bool）：是否必填（仅用于前端提示，权威校验在服务端）。
- 输出结构与现状一致：`<div class="rt-field" data-rt data-upload-url="...">` 内含 `.rt-toolbar`、`.rt-editor`、隐藏 `.rt-source`。

### 改 `app/admin/crud.php`（`richtext` 分支）
将现有内联的工具栏 / 编辑区 / 隐藏 textarea 标记，替换为 `include` 该 partial：
- `$rtName = $name`，`$rtValue = $cur`，`$rtUploadUrl = 'admin.php?m=news&a=upload'`，`$rtRequired = !empty($f['required'])`。
- 保留 `$needsRtScript = true`（页尾仍加载 `richtext.js`）。
- 新闻端行为、外观、上传路径均不变。

### 改 `public/thread-new.php` / `public/thread-edit.php`
- 正文 `<textarea name="body">` 替换为 include 该 partial：
  - `$rtName = 'body'`，`$rtValue = $in['body']`，`$rtUploadUrl = 'upload.php'`，`$rtRequired = true`。
- 页尾加载 `<script src="assets/js/richtext.js" defer></script>`。
- 去掉 `<textarea>` 上原有的 `maxlength="5000"`（对 HTML 无意义）；正文长度由服务端权威校验。
- 分类、标题字段与错误展示、CSRF、取消链接保持不变。

### 改 `public/assets/js/richtext.js`
- 上传地址从写死的 `admin.php?m=news&a=upload` 改为读取 `field.getAttribute('data-upload-url')`；
  若缺省则回退到 `admin.php?m=news&a=upload`（兼容旧调用）。
- 其余逻辑（工具栏命令、提交前把 `editor.innerHTML` 同步进 `.rt-source`）不变。

### 新增 `public/upload.php`（会员图片上传端点）
- `require bootstrap.php`；`header('Cache-Control: no-store')`。
- `member_check()`：未登录拒绝。
- 仅接受 POST，否则 405 + `Allow: POST`。
- `csrf_verify_or_die()`。
- `header('Content-Type: application/json; charset=utf-8')`。
- 复用 `handle_upload('file')`（来自 `app/admin/upload.php`，已在 bootstrap 链或按需 require）：
  - 成功：`{"ok":true,"url":"<UPLOAD_URL>/xxx"}`。
  - 未选文件：`{"ok":false,"error":"未选择文件"}`。
  - 校验失败（`RuntimeException`）：HTTP 400 + `{"ok":false,"error":"<msg>"}`。
- 返回的 JSON 结构与后台上传端点一致，前端 `richtext.js` 无需区分。

## 数据流

### 发帖 / 编辑保存
1. 提交前 JS 把编辑区 `innerHTML` 写入隐藏 `textarea[name=body]`。
2. 服务端读取 `$_POST['body']`。
3. 校验顺序（沿用现有 if-elseif 链，替换 body 校验）：账号状态 → 分类白名单 → 标题 1–120 → 正文校验 → 限流。
4. 正文校验：`$clean = sanitize_html($body)`；`mb_strlen(trim(strip_tags($clean)))` 落在 1–5000 之间否则报错。
5. 存库：`thread_create` / `thread_update` 写入 **净化后的** HTML（`$clean`），而非原始 `$body`。

### 图片插入
编辑区点"插入图片" → `richtext.js` 选文件 → POST 到 `data-upload-url`（论坛为 `upload.php`）
→ 端点存文件到 `UPLOAD_DIR`、返回 `UPLOAD_URL/xxx` → JS 在光标处插入 `<img src="UPLOAD_URL/xxx">`。

### 渲染
`public/thread.php` 正文由：
```php
<article ...><?= nl2br(e($t['body'])) ?></article>
```
改为直出净化后的 HTML：
```php
<article ...><?= $t['body'] ?></article>
```
（`$t['body']` 存库时已 `sanitize_html`，为纵深防御可在渲染前再净化一次——见"开放实现细节"。）

## 校验（后端权威）

修改 `app/threads.php`：
- `thread_validate_body(string $v)` 语义改为"对已净化 HTML 的纯文本长度校验"。
  推荐实现：调用方先净化，再把净化结果交给校验；或让校验内部 `strip_tags` 后计数。
  最终判定：`$n = mb_strlen(trim(strip_tags($clean)))`，`return $n >= 1 && $n <= 5000;`
- `thread_create` / `thread_update` 接收并存储净化后的 HTML。
- 标题、分类、`thread_can_post_now`、作者硬校验、软删除等逻辑不变。

## 安全

- 会员为半可信来源；净化在服务端白名单完成，与新闻共用同一套 `sanitize_html()`
  （标签白名单 `p/h2/h3/strong/b/em/i/u/ul/ol/li/a/img/br/blockquote`；`a` 仅放行 http(s) 并加 `rel=noopener noreferrer`；`img` 仅放行 `UPLOAD_URL/` 开头的站内图，外链图删除）。
- 上传端点独立鉴权：会员登录 + CSRF + POST-only + MIME 嗅探 + `getimagesize` + `≤UPLOAD_MAX_BYTES(2MB)` + 随机文件名 + `0640`，不复用管理员端点。
- CSRF：论坛表单已含 `_csrf`，`richtext.js` 上传时会带上该令牌。

## 测试

### `sanitize_html`（会员场景补充用例）
- `<script>alert(1)</script>` → 危险容器删除。
- 元素上的事件属性（如 `onclick`）→ 属性被清洗。
- 外链图 `<img src="https://evil/x.png">` → 整体删除；站内图 `<img src="<UPLOAD_URL>/x.png">` → 保留。
- 非白名单标签（如 `<div>`）→ 解包保留文本。

### 发帖 / 编辑
- 纯文本长度边界：0（拒绝）、1（通过）、5000（通过）、5001（拒绝）；含标签时按 `strip_tags` 计。
- 空正文（仅空标签 / 空白）→ 拒绝。
- 提交富文本 → 落库为净化后 HTML；`thread.php` 正确直出。
- 停用账号、限流、非法分类、超长标题仍按原逻辑拦截。
- 编辑他人 / 不存在帖子仍 404。

### `public/upload.php`
- 未登录 → 拒绝（重定向登录 / 非 200）。
- 缺 / 错 CSRF → 拒绝。
- 非 POST → 405。
- 非图片、超 2MB → 400 + error JSON。
- 正常图片 → `{ok:true,url:"<UPLOAD_URL>/..."}`，文件落盘。

## 开放实现细节（实现时定，不影响设计）

- `thread.php` 渲染前是否再 `sanitize_html` 一次（纵深防御）：倾向"是"，成本低。
- `handle_upload` 的引入方式：`public/upload.php` 内 `require_once app/admin/upload.php`，或将其提升为通用 helper；实现时就近处理，不改其行为。
- partial 入参传递采用局部变量约定（include 前赋值），与现有 `partials/nav.php`（依赖 `$active` / `$navOnDark`）风格一致。

## 不做（YAGNI）

- 不做纯文本 / HTML 自适应渲染分支（无存量）。
- 不做存量数据迁移。
- 不为会员上传单独加限流。
- 不扩展工具栏能力（与新闻保持一致）。
