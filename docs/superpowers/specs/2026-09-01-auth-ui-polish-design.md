# 认证页 UI 美化 + 导航入口修复 设计文档

> 状态：设计已确认，待评审 → 进入实现计划
> 日期：2026-09-01
> 关联：[会员账号体系 A+C](2026-09-01-user-auth-member-accounts-design.md)

## 1. 目标与范围

纯前端视觉优化，**不改动任何 PHP 逻辑**。解决四个问题：

1. 导航会员入口未登录只显示「登录」（去掉「/ 注册」）。
2. 该入口配色适配浅底与深底导航——当前紫字在深色 hero 导航上发暗。
3. 登录/注册页内容被 `position:fixed` 的 72px 导航遮挡，调整到合理位置。
4. 登录、注册（及账号中心）表单页参考行业主流风格美化——采用**居中卡片**版式。

**范围**：`public/partials/nav.php`、`public/login.php`、`public/register.php`、`public/account.php`、`public/assets/css/style.css`。CSRF / 验证码 / 蜜罐 / 校验 / 自动登录 / 会话等逻辑全部保持不变。

## 2. 导航入口（`nav.php` + `style.css`）

### 2.1 文案
- 未登录：`.nav-auth` 文案由「登录 / 注册」改为「登录」。
- 已登录：维持「👤 昵称」→ `account.php`（不变）。
- 注册入口改由登录页内的「还没账号？去注册」承载（登录页已有该链接）。

### 2.2 双底自适应（描边药丸）
`.nav-auth` 由纯文字改为**描边药丸按钮**，两种导航底都清晰：

| 场景 | 样式 |
|---|---|
| 浅色导航（白底，默认） | 透明底 + `1px solid var(--purple)` 描边 + `var(--purple)` 文字；hover 填充 `var(--purple-050)` |
| 深色导航（`.nav--on-dark`，如首页/教育/论坛 hero） | 透明底 + `1px solid rgba(255,255,255,.5)` 描边 + `#fff` 文字；hover 文字/描边转 `var(--green-neon)` |

- 药丸尺寸：`padding: 7px 18px; border-radius: 999px; font-weight: 700; font-size: 14px;`，覆盖 `.btn-ghost` 原先的 `padding:8px 6px`。
- 已登录态「👤 昵称」沿用同一 `.nav-auth` 药丸样式，保持一致。
- 移动端（≤960px）：沿用现有 `.nav-actions .btn.nav-auth { display: inline-flex; }` 规则，保证入口始终可见。

## 3. 遮挡修复 + 认证页布局

- 病因：认证页用 `<main ... style="margin:48px auto">`，48px < `--nav-h`(72px)，标题被 fixed 导航压住。
- 修复：认证页改用全屏布局容器 `.auth-wrap`：
```css
.auth-wrap {
  min-height: 100vh;
  display: flex; align-items: center; justify-content: center;
  padding: calc(var(--nav-h) + 40px) 20px 48px;   /* 顶部避开 fixed 导航 */
  background: var(--grad-aurora), var(--bg-soft);
}
```
卡片在视口垂直居中，`padding-top` 保证任何屏高都不被导航遮挡。

## 4. 认证卡片视觉规范（新增 `.auth-*` 样式，置于 `style.css` 末尾）

以美观为导向选定配色：白卡 + 品牌紫主按钮 + 极光背景（与全站 CTA 体系一致，紫色在白卡上对比与质感最佳）。

```css
.auth-card {
  width: 100%; max-width: 420px;
  background: #fff; border: 1px solid var(--line);
  border-radius: var(--radius-lg);           /* 28px */
  box-shadow: var(--sh-lg);
  padding: 40px 36px;
}
.auth-brand { display:flex; align-items:center; gap:10px; margin-bottom:22px; font-weight:800; }
.auth-brand .logo { width:36px; height:36px; border-radius:10px; background:var(--grad-purple);
  display:grid; place-items:center; color:#fff; }
.auth-title { font-size:24px; font-weight:800; color:var(--ink); margin-bottom:6px; }
.auth-sub   { font-size:14px; color:var(--ink-3); margin-bottom:26px; }
.auth-card .field { margin-bottom:16px; }
.auth-card label { display:block; font-size:13px; font-weight:600; color:var(--ink-2); margin-bottom:6px; }
.auth-card .input {
  width:100%; padding:12px 14px; border:1px solid var(--line); border-radius:var(--radius-sm);
  font-size:15px; background:#fff; transition:border-color .15s, box-shadow .15s;
}
.auth-card .input:focus {
  outline:none; border-color:var(--purple-400);
  box-shadow:0 0 0 3px var(--purple-050);
}
.auth-submit { width:100%; justify-content:center; margin-top:6px; }   /* 配合 .btn.btn-purple */
.auth-alt { text-align:center; font-size:14px; color:var(--ink-3); margin-top:18px; }
.auth-alt a { color:var(--purple); font-weight:600; }
.auth-error {                     /* 顶部整体错误条 */
  background:#FDECEC; border:1px solid #F5C2C2; color:#c0392b;
  border-radius:var(--radius-sm); padding:10px 14px; font-size:14px; margin-bottom:18px;
}
.auth-field-err { color:#c0392b; font-size:12px; margin-top:5px; }   /* 字段级错误 */
.auth-captcha { display:flex; gap:10px; align-items:center; }
.auth-captcha .input { flex:1; }
.auth-captcha img { height:44px; border-radius:var(--radius-sm); cursor:pointer; border:1px solid var(--line); }
```

- 主按钮复用 `.btn.btn-purple` 加 `.auth-submit`（全宽居中）。
- 蜜罐字段维持屏外定位（`position:absolute;left:-9999px`），不进卡片视觉。

## 5. 模板改动

三个页面把原 `<main class="container" style="margin:48px auto">…` 结构替换为 `.auth-wrap > .auth-card` 结构，字段套用 §4 类名。**表单字段、name、CSRF、验证码、蜜罐、错误变量、跳转逻辑全部照旧**，仅换外层结构与 class。

- `login.php`：品牌头 +「欢迎回来」标题；用户名、密码、（首败后）验证码；`.auth-error` 显示 `$error`；底部「还没账号？去注册」。
- `register.php`：品牌头 +「注册会员」标题；用户名/密码/邮箱/手机/昵称/验证码 + 蜜罐；字段级错误用 `.auth-field-err` 显示 `$errors[...]`；底部「已有账号？去登录」。
- `account.php`：复用 `.auth-card` 与输入样式（宽度放宽到 ~560），两张卡片（基本资料 / 修改密码）纵向排列，顶部标题行含「退出登录」；沿用现有 `$msg`/`$err` 与两个 `do=profile|password` 表单。

## 6. 无逻辑改动 + 验证策略

- 不动 PHP：`php tests/run.php` 仍应 **176 passed, 0 failed**（回归确认）。
- 预览实机验证（`mabseek-php`）：
  1. 首页深色 hero 导航「登录」药丸清晰可见（白描边白字），浅色导航（如登录页顶部）紫描边清晰。
  2. 登录页标题不再被导航遮挡，卡片垂直居中。
  3. 登录/注册卡片渲染符合居中卡片版式；`.input:focus` 紫色高亮。
  4. 桌面与移动（≤960px）both：入口可见、卡片自适应不溢出。
  5. 走一遍登录、注册（含验证码/蜜罐/错误提示）、改密流程，功能不回归。

## 7. 文件清单

**修改：**
- `public/assets/css/style.css`：新增 `.auth-*` 卡片样式 + `.nav-auth` 药丸（浅底）+ `.nav--on-dark .nav-auth`（深底）覆盖。
- `public/partials/nav.php`：文案改「登录」；`.nav-auth` 类保留。
- `public/login.php`：外层结构改 `.auth-wrap/.auth-card`。
- `public/register.php`：同上，字段级错误套 `.auth-field-err`。
- `public/account.php`：改用 `.auth-card` 风格。

**不新增文件，不改 PHP 逻辑。**
