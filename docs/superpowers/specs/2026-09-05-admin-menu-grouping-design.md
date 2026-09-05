# 设计文档：后台菜单归类 + 页面风格统一

- 日期：2026-09-05
- 状态：已批准，待实现

## 背景

MabSeek 后台（`public/admin.php` + `app/admin/*`）当前侧栏是 13 项扁平菜单，无分组；且 `members` / `threads` / `feedback` 三个自定义模块页面用了无样式的 `.admin-table`，与走 `admin_crud` 引擎的模块（`.data-table` + `.page-head` + `.abtn-*`）风格不一致。本次：① 侧栏按类型归类为分组结构；② 三个页面美化对齐标准风格。

## 现状

- **菜单**：`app/admin/shell.php` 的 `$adminMenu` 为「key => label」扁平数组，侧栏 `foreach` 直接平铺 13 项。`edu_reviews` 的 label 是「教育 · 往期回顾」（前缀模拟分组）。
- **页面风格两套**：
  - **标准**（crud 模块 news/cards/team/…）：`.card` > `.page-head`(标题+新增) > `.table-wrap` > `.data-table`；按钮 `.abtn abtn-sm abtn-default/danger/primary`。样式在 `public/assets/css/admin.css` 定义齐全。
  - **旧**（自定义 members/threads/feedback）：`.card` > 裸 `<h3 style="margin-bottom:16px">` > 裸 `.admin-table`（**admin.css 中 0 定义 → 浏览器默认样式**）；按钮 `.abtn`（无尺寸/语义修饰），删除用内联 `style="color:#c0392b"`。
- 侧栏样式：`.admin-sidebar a` / `a:hover` / `a.on` 已定义。

## 需求一：菜单归类

**数据结构**（`shell.php`）：`$adminMenu` 改为「分组 => [key => label]」二级结构：

```php
$adminMenu = [
  '_top'  => ['dashboard' => '仪表盘'],
  '内容'  => ['news' => '新闻与活动', 'cards' => '内容卡片', 'snippets' => '文案片段',
              'team' => '团队成员', 'partners' => '合作伙伴'],
  '教育'  => ['edu_reviews' => '往期回顾'],
  '论坛'  => ['forum_posts' => '帖子', 'forum_hot' => '热榜', 'threads' => '发帖'],
  '系统'  => ['members' => '会员管理', 'feedback' => '联系反馈', 'password' => '修改密码'],
];
```

- 分组标题 `_top` 特判：不输出标题，直接列子项（仪表盘置顶）。
- 其余分组：输出灰色小字分组标题 + 缩进子菜单项。
- 当前 `$module` 高亮（`.on`）逻辑不变（比对子项 key）。
- 子菜单名简化：去「教育 · 」前缀（→往期回顾）、论坛三项去「论坛」前缀（→帖子/热榜/发帖）。

**排序**：仪表盘 → 内容（含团队/伙伴，属内容展示数据）→ 教育 → 论坛 → 系统（会员/反馈/密码，运营与账号类）。

**样式**（`admin.css`）：新增 `.admin-sidebar .nav-group-title`（分组标题：灰色小字、字号约 12px、上间距、不可点）；子项保持现有 `a` 样式（可选轻微左缩进）。

**白名单**（`admin.php`）：`$allowed` 数组不变（已含全部 key）；仅 shell.php 的展示结构变。

## 需求二：页面美化（members / threads / feedback）

三页改为标准风格，仅改 HTML 结构与 class，**保留全部 POST 动作逻辑**（停用/启用/重置/删除/下架/标记等）：

- 裸 `<h3 style="…">标题（共 N …）</h3>` → `.page-head` > `<h3>` 结构（统计数字保留在标题文本内或右侧）。
- `.admin-table` → `.table-wrap` > `.data-table`。
- 操作按钮：`.abtn` → `.abtn abtn-sm abtn-default`；删除类按钮 → `.abtn-danger`（移除内联 `style="color:#c0392b"`）。
- 状态标注（已停用 / 未处理 / 已下架等）：用语义色文本或 `.tag`，保留可读性。
- 空状态：`.data-table` 的 `.empty` 单元格风格（或保留原「暂无」提示）。

## 非目标（YAGNI）

- 不做可折叠/手风琴侧栏（静态分组标题即可，符合极简纯 PHP 风格）。
- 不改各模块的业务逻辑、数据模型、路由白名单。
- 不给 `.admin-table` 补样式（改用 `.data-table` 后该 class 全站清零）。
- 不改 crud 引擎模块（它们已是标准风格）。

## 测试与验证

- `tests/run.php` 全套回归（纯展示层，不新增单测）。
- 起服务人工验证：
  - 侧栏显示 5 段（仪表盘 + 内容/教育/论坛/系统），各分组标题 + 缩进子项；当前页高亮正确；点各项跳转正常。
  - members/threads/feedback 三页为 `.data-table` 精致风格（表头背景、圆角、hover），按钮统一（默认/危险语义）。
  - 三页既有动作（停用/启用/重置密码/删除/下架/恢复/标记已处理）仍正常工作。
