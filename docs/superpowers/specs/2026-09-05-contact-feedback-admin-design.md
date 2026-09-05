# 设计文档：联系反馈落库 + 后台查看

- 日期：2026-09-05
- 状态：已批准，待实现

## 背景

MabSeek 是纯 PHP 8 + SQLite、无框架零依赖的实验室对外网站 + CMS 后台。首页与「了解我们」页各有一个「联系我们 / 快速反馈」表单，当前为纯前端模拟：提交只弹 toast，不落库。本次将其改为真实提交——反馈落库，后台新增独立菜单查看与处理。

## 现状

- **前台表单**：`public/index.php`（`#contact`，约 101–107 行）与 `public/about.php`（`#contact`，约 187–194 行）各有 `<form id="contact-form" class="feedback">`，字段 `name` / `email` / `message`，均 `required`。无 `action`、无 CSRF。
- **提交行为**：`public/assets/js/main.js:141–150` 拦截 submit，弹 `showToast` 后 `reset()`，不发请求。
- **后台架构**：`public/admin.php` 白名单路由 `$module` → `app/admin/{module}.php`，`auth_is_admin()` 硬闸内。`app/admin/members.php` 是「只读表格 + POST 治理动作 + CSRF」范式，适合复用。菜单在 `app/admin/shell.php` `$adminMenu`。
- **安全基线**：CSRF 令牌（`csrf_field()` / `csrf_verify_or_die()`）、蜜罐字段（注册页 `website` 字段防机器人）、审计 `audit()`、客户端 IP 工具（`client_ip()` 见 helpers）。

## 数据模型

新表 `feedback`（`app/db.php` `migrate()` 幂等建表）：

```sql
CREATE TABLE IF NOT EXISTS feedback (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name       TEXT NOT NULL,
  email      TEXT NOT NULL,
  message    TEXT NOT NULL,
  status     TEXT NOT NULL DEFAULT 'new',   -- new | done
  ip         TEXT DEFAULT '',
  created_at TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_feedback_created ON feedback(created_at DESC);
```

## 数据层 `app/feedback.php`（新建）

- `feedback_validate_name($v)`：trim 后 1–50 字。
- `feedback_validate_email($v)`：非空、≤254、`FILTER_VALIDATE_EMAIL`。
- `feedback_validate_message($v)`：trim 后 1–2000 字。
- `feedback_create($name,$email,$message,$ip): int`：落库，status 硬编码 'new'。
- `feedback_recent_count_by_ip($ip,$seconds): int` + 常量 `FEEDBACK_RATE_MIN_SECONDS`（60）、`FEEDBACK_RATE_DAILY_MAX`（20）。
- `feedback_can_submit_now($ip): bool`：60 秒内同 IP 有提交 → false；24 小时超日上限 → false。
- `feedback_list(): array`（后台，按时间倒序）。
- `feedback_set_status($id,$status)`：只允许 'new' / 'done'。
- `feedback_delete($id)`。

## 提交端点 `public/feedback.php`（新建，公开访问）

- 仅接受 POST；`csrf_verify_or_die()`。
- 蜜罐：`website` 字段非空 → 静默重定向回来源页（仿注册页，不落库、不提示）。
- 来源页：读取隐藏字段 `from`（白名单 `index.php` / `about.php`，非法回落 `index.php`），重定向锚点 `#contact`。
- 频率限制：`feedback_can_submit_now(client_ip())` 为假 → 重定向回来源页带 `?fb=rate`。
- 字段校验：任一不合法 → 重定向回来源页带 `?fb=err`。
- 全部通过 → `feedback_create(...)` + `audit('feedback_create')` → 重定向回来源页带 `?fb=ok`。
- 非 POST → 405。

## 前台表单改造

- `index.php` / `about.php` 两处 `#contact-form`：
  - 加 `method="post" action="feedback.php"`。
  - 加 `csrf_field()`、隐藏 `from`（`index.php` 或 `about.php`）、蜜罐 `website`（`position:absolute;left:-9999px`，仿注册页）。
  - 页面在 `#contact` 区顶部根据 `$_GET['fb']` 服务端渲染提示条：`ok` 绿色「反馈已收到，我们会尽快联系你」；`err` 红色「请检查填写内容」；`rate` 红色「提交过于频繁，请稍后再试」。
- `main.js`：删除 141–150「快速反馈表单：前端模拟提交」整段（改为真实整页 POST）。

## 后台模块 `app/admin/feedback.php`（新建，仿 members.php）

- POST 动作（CSRF）：`done`（标记已处理）/ `reopen`（标记未处理）/ `delete`。
- 只读表格：ID / 称呼 / 邮箱 / 内容 / 状态 / IP / 时间 / 操作。
- 内容 `e()` 转义直出（纯文本，非富文本）。
- 注册：`public/admin.php` 白名单加 `'feedback'`；`app/admin/shell.php` `$adminMenu` 加 `'feedback' => '联系反馈'`。

## 安全与一致性

- 提交端点公开（访客可反馈），但 CSRF + 蜜罐 + 频率限制防滥用。
- 后台查看在 `auth_is_admin()` 硬闸内。
- 反馈内容为纯文本，后台展示 `e()` 转义，杜绝存储型 XSS。
- name / email / message 参数化入库。

## 测试 `tests/test_feedback.php`（新建）

- 校验函数正/反例（name/email/message 边界）。
- `feedback_create` 落库、字段正确、status='new'。
- `feedback_set_status` 只接受 new/done。
- `feedback_delete` 生效。
- `feedback_recent_count_by_ip` / `feedback_can_submit_now` 频率限制逻辑。

## 非目标（YAGNI）

- 不做邮件通知（仅落库 + 后台查看）。
- 不做反馈回复功能。
- 不改反馈表单的字段结构（沿用 name/email/message）。
- 不做验证码（蜜罐 + 频率限制已足够）。
