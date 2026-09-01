# 发帖/编辑页按注册页规范重构 设计文档

> 状态：设计已确认，待评审 → 进入实现计划
> 日期：2026-09-01
> 关联：[认证页 UI 美化](2026-09-01-auth-ui-polish-design.md) · [论坛会员发帖](2026-09-01-forum-member-threads-design.md)

## 1. 目标与范围

把论坛发帖页与编辑页按注册页的居中卡片规范重构，解决与认证页同样的两个问题：被 72px 固定导航遮挡、表单裸样式不美观。

**范围**：`public/thread-new.php`（发帖）、`public/thread-edit.php`（编辑），两页保持一致视觉。**纯前端结构 + class 改动，不改任何 PHP 逻辑**（CSRF、字段校验、限流、停用拦截、作者硬校验、`act=delete`/`act=update` 分支、跳转全部照旧）。

**不做**：两页的「帖子不存在」404 兜底片段（边缘路径）；论坛详情页 `thread.php`、后台治理页、`forum.php` 列表——本轮不动。

## 2. 复用现有样式（基本零新增 CSS）

上一轮认证页美化已在 `public/assets/css/style.css` 定义了完整 `.auth-*` 体系（`.auth-wrap` / `.auth-card` / `.auth-card.wide` / `.auth-brand` / `.auth-title` / `.auth-sub` / `.auth-card .input`（含 `textarea.input` 与 `:focus` 紫色高亮）/ `.auth-error` / `.auth-alt` / `.auth-submit`）。本设计直接复用，无需新增样式类。

- `.auth-card .input` 已覆盖 `<select>`、`<input>`、`<textarea>`（三者在模板中均带 `class="input"`），focus 紫色高亮一致。
- `textarea.input { resize: vertical; }` 已有，正文框可纵向拉伸。

## 3. 版式结构

两页外层由原 `<main class="container" style="max-width:640px;margin:48px auto">…</main>` 替换为：

```
.auth-wrap                    （极光背景 + padding-top: calc(--nav-h + 40px) 避开固定导航 + 居中）
  └─ .auth-card.wide          （560px，比注册 420 宽，正文 textarea 更舒展）
       ├─ .auth-brand         🧬 MabSeek
       ├─ .auth-title         「发布帖子」/「编辑帖子」
       ├─ .auth-sub           一行说明
       ├─ .auth-error         $err（若有）
       ├─ <form>              分类 select · 标题 input · 正文 textarea · 主按钮 + 取消
```

- 主按钮：`<button class="btn btn-purple auth-submit">`（全宽），后跟「取消」链接（发帖→`forum.php`，编辑→`thread.php?id=`）。取消链接放按钮下方，套 `.auth-alt` 居中风格或按钮旁次要链接（实现时置于卡片内、按钮之后，`.auth-alt` 居中）。
- 副标题文案：发帖页「分享经验、提问求助，与社区一起成长。」；编辑页「修改后保存即更新。」

## 4. 各页改动

### 4.1 `thread-new.php`
- 顶部 PHP（`member_check`、停用/限流校验、`$err`/`$in`、`thread_create` 跳转）与 head、nav/footer include **不变**。
- 仅替换 `<main>…</main>` 主体为 `.auth-wrap > .auth-card.wide` 结构。
- 表单 `action="thread-new.php"`、字段 name（category/title/body）、`csrf_field()`、`THREAD_CATEGORIES` 下拉、maxlength 等**全部保留**。

### 4.2 `thread-edit.php`
- 顶部 PHP（`member_check`、作者 404 校验、`act=delete`/`act=update`、`$err`/`$in`）与 head、nav/footer include **不变**。
- 仅替换主表单 `<main>…</main>` 主体为同款结构；保留隐藏字段 `<input type="hidden" name="act" value="update">`、`action="thread-edit.php?id=…"`。
- 「帖子不存在」404 分支的 `<main>` 片段**保持现状**。

## 5. 验证

- PHP 逻辑零改动：`php tests/run.php` 仍 **176 passed, 0 failed**。
- 预览实机（`mabseek-php`，需登录会员）：
  1. `thread-new.php` 标题不再被导航遮挡，卡片居中，品牌头/标题/副标题/紫色 focus/全宽紫按钮/取消链接齐全。
  2. 分类下拉、标题、正文 textarea 样式统一，textarea 可拉伸。
  3. 发帖成功跳详情；触发校验错误时 `.auth-error` 顶部条显示。
  4. `thread-edit.php` 同款版式；编辑保存跳详情、取消回详情。
  5. 桌面与移动（≤960px）卡片自适应不溢出。

## 6. 文件清单

**修改：**
- `public/thread-new.php`：主体结构改 `.auth-wrap/.auth-card.wide`。
- `public/thread-edit.php`：主表单结构同上（404 片段不动）。

**不新增文件、不新增 CSS、不改 PHP 逻辑。**（若实机发现 `<select>` 在卡片内观感需微调，允许在 `style.css` 追加极小的 select 外观规则，属实现期微调。）
