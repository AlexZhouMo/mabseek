# MabSeek 抗体求索 · 架构设计文档

> 版本：2026-08-30（P5：PHP + SQLite 后端内容管理接入）｜P1–P4 纯静态基线见 §10
> 项目：MabSeek 抗体求索门户 · 清华大学医学院
> 定位：AI 驱动的抗体发现平台官网（展示型静态站）

本文档描述 MabSeek 门户网站的整体架构、目录结构、页面与脚本职责、约定与约束。配套文档见 [ui-style-guide.md](ui-style-guide.md)（UI 风格规范）。

---

## 1. 总体设计原则

| 原则 | 说明 |
|---|---|
| **纯静态** | 无构建、无依赖、无后端。所有页面为手写 HTML，浏览器双击即可打开运行。 |
| **零第三方库** | 全站仅用原生 HTML / CSS / JavaScript，不引入任何框架或 CDN。 |
| **交互前端模拟** | 所有"提交/下单/详情/搜索"类交互均为前端演示：`data-demo` 触发 toast 提示，表单 `preventDefault` 后弹提示并 `reset()`，不发任何网络请求。 |
| **渐进增强** | 全站尊重 `prefers-reduced-motion`；动画/`IntersectionObserver` 均有降级路径，禁用后内容依然完整可读。 |
| **增量演进** | CSS/JS 按阶段（P1→P4）**追加**，每段带作用域守卫，绝不改动既有规则、绝不产生跨页面副作用。 |
| **不杜撰** | 涉及真实人物/机构（张林琦、马维英、清华/北大/中科院/协和等）只用姓名 + 定性方向；所有数字均为演示/示意，不作为经核实的事实呈现。 |

---

## 2. 目录结构

```
mabseek/
├── index.html            首页（品牌主张 · 痛点解法 · 近况 · 合作/联系）
├── technology.html       技术平台（架构图 · Data/AI/Wet 三支柱 · 经典案例）
├── agent.html            Antibody Agent（对话演示 · 四大能力 · 干湿闭环 · 智能体矩阵）
├── education.html        教育（元视频课程 · 交互式知识图谱 · 知识地图 · 资源区）
├── forum.html            论坛（AI 搜索 · 每日/每周热榜 · 内容流 · 四条内容线）
├── about.html            了解我们（双负责人 · 位置 · 平台介绍 · 新闻 · 国际合作 · 联系）
├── assets/
│   ├── css/
│   │   └── style.css     全站唯一样式表（设计令牌 + 工具类 + 分阶段增量段）
│   ├── js/
│   │   ├── main.js           全站通用脚本（所有页面加载）
│   │   ├── hero-anim.js      首页英雄区 Canvas 抗体组装动画
│   │   ├── agent-demo.js     Agent 页对话演示（打字机循环）
│   │   ├── agent-cases.js    Agent 页案例示范循环动图
│   │   └── knowledge-graph.js 教育页 SVG 交互式知识图谱
│   └── images/           位图素材（多数带 onerror 兜底，可缺省）
└── docs/
    ├── architecture.md       本文档
    ├── ui-style-guide.md     UI 风格规范
    └── superpowers/          各阶段设计规格（specs/）与实施计划（plans/）
```

**关键约束：全站仅一个样式表 `assets/css/style.css`。** 页面级专属样式（如 Agent 对话 UI、教育播放器、论坛瀑布流）写在各页 `<head>` 的局部 `<style>` 中，作用域限于本页，不进入全局表。

---

## 3. 页面地图与导航

全站共 **6 个页面**，统一顶部导航含 6 个主链接 + 1 个「联系我们」CTA：

```
首页 · 技术平台 · Antibody Agent · 教育 · 论坛 · 了解我们   [联系我们]
index  technology  agent           education  forum   about
```

- **`.active` 高亮**：每页导航将当前页链接标为 `class="active"`。
- **CTA 目标**：`联系我们` 指向联系表单区 `#contact`。首页与 about.html 本页含 `#contact`，CTA 用本地锚 `#contact`；其余页面无联系区，CTA 指向 `index.html#contact`（跨页锚点）——这是**有意为之**的差异，非错误。
- **深色页导航**：index.html 与 technology.html 首屏为深色，导航加 `.nav--on-dark`（首屏透明、滚动后深色实底）。其余页面用默认浅色导航。
- **统一页脚**：全站四列页脚（品牌 / 探索 / 社区 / 联系）内容一致；「社区」列的「新闻活动」指向 `about.html#news`（对应 about.html 的 `#news` 分区）。

---

## 4. 各页面职责

| 页面 | 主要分区 | 深/浅节奏 | 加载的 JS |
|---|---|---|---|
| **index.html** | 深色英雄区（Canvas 抗体动画）→ 我们能做什么（浅）→ 四大痛点解法（深）→ 我们的近况（浅横向滚动）→ 合作伙伴 + 联系表单（深 `#contact`） | 深-浅-深-浅-深 | `main.js`, `hero-anim.js` |
| **technology.html** | 深色页头 → 整体技术架构图（可悬停/点击展开）→ Data/AI/Wet 三支柱交替带 → 经典案例三维 | 全程深/浅支柱交替 | `main.js` |
| **agent.html** | 英雄区 + 对话演示 → 四大能力 → 案例示范循环动图（深）→ 干湿闭环五步流程 → 智能体矩阵 → CTA | 浅为主，案例示范深 | `main.js`, `agent-demo.js`, `agent-cases.js` |
| **education.html** | 页头 → 课程 Banner + 三栏 → 课时播放专区（弹幕+留言）→ 交互式知识图谱 → 学习路径知识地图（章节树）→ 资源区 → 跨板块联动 → 讲座/成长资源 | 浅为主 | `main.js`, `knowledge-graph.js` + 内联（弹幕、折叠面板） |
| **forum.html** | 页头 + AI 搜索 → 每日/每周热榜 → 话题标签 + 瀑布流内容流 → 四条内容线 → 关注动态/冷启动 | 浅为主 | `main.js` + 内联（标签筛选、热榜切换） |
| **about.html** | 页头 → 实验室与核心团队（**双负责人** + 成果卡 + 位置图）→ 平台介绍 → 新闻与活动（筛选 + 了解更多）→ 国际科研合作 → 联系我们（深 `#contact`） | 浅为主，联系区深 | `main.js` + 内联（新闻筛选） |

---

## 5. JavaScript 架构

### 5.1 全站通用：`main.js`（每页加载，IIFE，无依赖）

单个立即执行函数，按元素存在性守卫，职责：

1. **导航** — 滚动超过 8px 加 `.scrolled` 阴影；`.nav-toggle` 汉堡切换 `.nav.open`；点击链接收起移动端菜单。
2. **滚动渐显** — `IntersectionObserver` 对 `.reveal` 元素加 `.in`（阈值 0.12）；无 IO 时直接全部显示。
3. **数字滚动** — `[data-num]` 进入视口后 count-up 缓动到目标值。
4. **首屏粒子网络** — 若存在 `#hero-canvas`，绘制粒子连线抗体网络背景（`prefers-reduced-motion` 下不启动）。
5. **演示提示** — 所有 `[data-demo]` 元素点击 → `preventDefault` → `showToast(该元素的 data-demo 文案)`。
6. **联系表单** — 若存在 `#contact-form`，`submit` → `preventDefault` → toast「已收到你的反馈，{称呼}！…」→ `reset()`。
7. **架构图节点** — `.arch-node` 点击切换 `.open`（桌面 hover 走 CSS，触屏靠此）。
8. 暴露 `window.mabToast(msg)` 供其它脚本复用 toast。

### 5.2 页面专属脚本（守卫式，其它页面惰性、零副作用）

| 脚本 | 页面 | 目标元素 | 行为 |
|---|---|---|---|
| `hero-anim.js` | index | `#antibody-canvas` | Canvas2D：粒子从随机位置组装成 Y 形抗体骨架并向靶点对接，6s 循环；reduced-motion 显示静止终态。 |
| `agent-demo.js` | agent | `#chat-feed` | 打字机式对话演示（用户提问→检索→设计→预测→结构→结果→下单按钮），进入视口后启动，结束 6s 后循环。 |
| `agent-cases.js` | agent | `.case-anim` | 三张卡片循环微动效：打字机+序列点亮、候选条重排、表位脉冲。 |
| `knowledge-graph.js` | education | `#kg` | 纯 SVG 知识图谱：7 节点 + 边，点击高亮邻居并在 `#kg-panel` 显示详情 + 唤起 Agent 链接。 |

### 5.3 内联脚本（页面尾部 `<script>`）

- **education.html**：弹幕生成循环 + `.acc-head` 折叠面板展开。
- **forum.html**：`.chip-f` 话题筛选（按 `data-cat` 显隐）+ `.hot-tab` 每日/每周热榜切换。
- **about.html**：`[data-nf]` 新闻分类筛选（按 `data-nc` 显隐）。

**守卫模式统一约定**：每个专属脚本首行检查目标元素是否存在，不存在则 `return`。因此所有脚本可安全地被任意页面加载而不报错——实际按需引入以省流量。

---

## 6. CSS 架构（`style.css`，约 570 行）

分层组织，从上到下：

1. **设计令牌**（`:root`）— 品牌色、中性色、渐变、阴影、圆角、断点、字体栈（详见 [ui-style-guide.md](ui-style-guide.md)）。
2. **基础重置** — box-sizing、字体、标题字重、链接/图片默认。
3. **布局工具类** — `.container` / `.section` / `.bg-soft` / `.grad-text` / `.eyebrow` / `.section-title` / `.section-sub`。
4. **组件** — `.btn`(系列) / `.nav`(系列) / `.hero` / `.metric` / `.card` / `.grid-2·3·4` / `.case` / `.logo-wall` / `.page-hero` / `.tag` / `.footer` / `.reveal`。
5. **响应式** — `@media (max-width:960px)`（栅格塌陷、指标 2 列）与 `@media (max-width:720px)`（导航→汉堡）。
6. **分阶段增量段**（均带注释标记 + 作用域守卫，"不影响其它页面"）：
   - **P1**：深/浅双主题（`.section-dark` / `.section-light` / `.nav--on-dark`）、首页英雄区 `.hero-c`、四大痛点 `.pain-grid`、近况横滚 `.news-scroller`、快速反馈 `.feedback`。
   - **P2**：技术平台 `.tech-hero` / `.arch-diagram` / `.pillar` / `.case-dims`。
   - **P3a**：Agent 案例示范 `.case-anim`（含 `prefers-reduced-motion` 静止终态）。
   - **P3b**：教育知识地图 `.kmap`（复用 `.accordion` 组件）。
   - **P3c**：论坛 `.forum-search` / `.hot-tabs` / `.hot-list`。

> 增量演进纪律：新阶段样式一律**追加**到表尾并加阶段注释；页面级一次性样式优先放页面局部 `<style>`。约束保证任何一次修改的影响面可预测。

---

## 7. 数据与内容模型

- **无数据层**：所有内容硬编码在 HTML 中。新闻、帖子、热榜、课时、知识图谱节点均为静态标记或脚本内数组常量（如 `knowledge-graph.js` 的 `nodes`、`agent-demo.js` 的 `script`）。
- **图片策略**：位图放 `assets/images/`，绝大多数 `<img>` 带 `onerror` 兜底（切换为品牌渐变块 / emoji / 隐藏容器），因此缺图不破版、不报功能错误。
- **图标**：全部使用 Emoji + 内联 SVG（品牌 logo），无图标字体依赖。
- **favicon**：内联 data-URI SVG（🧬），零额外请求。

---

## 8. 交互与状态约定

| 交互类型 | 实现 | 用户可见反馈 |
|---|---|---|
| 演示占位（详情/下单/搜索/社交） | `[data-demo="文案"]` | 底部 toast 弹出文案，2.6s 后消失 |
| 联系/反馈表单 | `#contact-form` + main.js | toast 致谢 + 表单清空（无网络请求） |
| 分类筛选（新闻/帖子） | 内联脚本按 `data-*` 显隐 | 列表即时过滤 |
| 标签页切换（热榜/播放） | 内联脚本切 `.on` / `hidden` | 面板切换 |
| 架构节点 / 图谱节点 | CSS hover + JS click | 展开详情 / 高亮邻居 + 侧栏 |
| 折叠资源 | `.accordion` + 内联脚本 | max-height 过渡展开 |

所有交互均为**无状态、无持久化**——刷新即回到初始态。

---

## 9. 本地预览

预览配置见 `.claude/launch.json`（名称 `mabseek`，端口 8777）。任意静态服务器均可，例如：

```bash
cd mabseek && python3 -m http.server 8777
```

> 注意：`<script src>` / `<link href>` 未做缓存破坏（cache-busting）参数，预览时如改动 CSS/JS 未生效，需硬刷新或加查询串强制重取。

> **P5（PHP 版）预览**：`.claude/launch.json` 名称 `mabseek-php`（`php -S localhost:8778 -t public`，现为默认预览配置）。首次需先 `php bin/seed.php` 建库并写入文案；旧静态 `mabseek`（8777）仅供历史对照。

---

## 10. 演进历史（阶段）

| 阶段 | 范围 | 交付 |
|---|---|---|
| P1 | 统一外壳 + 首页 | 6 区导航 + 统一页脚 + 深/浅主题 + 首页改版 |
| P2 | 技术平台页 | 架构图 + 三支柱 + 经典案例 |
| P3a | Antibody Agent 页 | 对话演示 + 案例示范动图 + 智能体矩阵 |
| P3b | 教育页 | 元视频/弹幕/知识图谱 + 学习路径知识地图 |
| P3c | 论坛页 | AI 搜索 + 每日/每周热榜 + 内容流 |
| P4 | 了解我们页 | 双负责人（张林琦/马维英）+ 位置图 + 新闻「了解更多」+ 联系/反馈表单 |
| P5 | 后端内容管理 | PHP+SQLite 动态渲染（markup 不变）+ `admin.php` 后台 + 严格安全基线 + 幂等种子（见 §11） |

> 明确未做（YAGNI）：i18n 中英切换、真实搜索引擎。（P5 已补：后端内容管理与管理员账户系统——见 §11。）

---

## 11. P5 · 后端内容管理（PHP + SQLite）

> 本阶段将 P1–P4 的纯静态站点升级为 **PHP 8 + SQLite** 动态渲染：**前端 markup / 样式 / 交互逐字保留**（已验证 6 页「可见文本」与「标签·类骨架」逐页一致），仅将原先硬编码的文案与集合数据改为从数据库读取。上文 §1「纯静态 / 无后端」、§7「无数据层」等描述对应 P1–P4 基线；内容交付层以本节为准。零第三方框架 / 依赖（仅需 PHP `pdo_sqlite`）。

### 11.1 目录结构（方案 A：web 根隔离）

```
mabseek/
├── public/                  ← nginx 文档根（仅此目录对外可达）
│   ├── index.php … about.php    6 个页面（由 SQLite 渲染）
│   ├── admin.php                后台入口（登录 + 模块路由）
│   └── assets/                  css / js / images（含 images/uploads 上传目录）
├── app/                     ← web 根之外，URL 不可达
│   ├── bootstrap.php config.php db.php helpers.php csrf.php auth.php
│   ├── repositories/            Collection.php（集合）· Snippets.php（文案）
│   └── admin/                   shell · login · dashboard · crud（通用组件）
│                                + 各集合模块 + snippets · password · upload
├── data/                    ← web 根之外；mabseek.sqlite（gitignore）权限 0640
├── bin/seed.php             ← 幂等种子（把既有文案逐字写入库）
├── tests/                   ← run.php + test_*.php（零依赖断言）
└── deploy/nginx-var-www-html-http.conf.sample ← 站点样例（HTTP · .html→.php 301 · 上传禁 PHP · 安全头）
```

`app/`、`data/`、`bin/`、`tests/` 均在文档根之外，无法通过 URL 触达（纵深防御）。

### 11.2 渲染与 URL 模型

- 每页 `.php` 在页首 `require app/bootstrap.php`，随后用 `snip()`/`snip_raw()` 取文案、用 `Collection::published()` 取集合条目渲染——**输出的 HTML 与旧静态页逐字一致**，仅 `.html` 链接改为 `.php`。
- `snip()` 转义输出（绝大多数纯文本文案）；`snip_raw()` 用于极少数本身含版式标记的标题（如各页 `*.hero.title`），后台编辑器对这些键显式标注「含版式标记，谨慎编辑」。
- 旧 `*.html → *.php` 的 301 由 nginx 承担（见 `deploy/nginx-var-www-html-http.conf.sample`）。

### 11.3 数据模型概览

| 表 | 用途 |
|---|---|
| `news` / `forum_posts` / `forum_hot` / `team_members` / `partners` | 各集合条目（含 `sort` / `published`） |
| `content_cards` | 跨页共享卡片，按 `grp` 分组（首页痛点 / 关于成果 / 论坛内容线 / Agent 能力·矩阵 / 教育课程·讲座·成长），结构化字段存 `extra`(JSON) |
| `snippets` | 键值文案（`skey`/`value` + `grp`/`label`/`type`），供 `snip()` 读取 |
| `users` | 管理员（Argon2id 哈希 + `must_change_password`） |
| `login_attempts` / `audit_log` | 登录审计与锁定 · 操作审计 |

- `Collection`：白名单列、`ORDER BY sort ASC, id ASC`，`published()` 只返 `published=1`。
- `Snippets`：分组读取 + 幂等 upsert；`seed()` 仅在键缺失时写入。

### 11.4 安全基线

- **认证**：Argon2id 哈希；`admin`/`mabseek2026` 为初始种子且 `must_change_password=1`，首登强制改密（≥10 位、异于旧密码）。
- **会话加固**：HttpOnly + SameSite=Strict + `secure`（HTTPS 时）；空闲与绝对超时；UA 绑定；登录/改密后 `session_regenerate_id`。
- **登录风控**：按 (IP) 与 (IP,用户) 双维度、时间窗内失败计数触发锁定（先于口令校验，防 Argon2id 资源耗尽与枚举）。
- **CSRF**：`hash_equals` 校验；写操作仅接受 POST（GET → 405，缺 token → 403）。
- **注入 / XSS**：全程 PDO 预处理；输出统一 `e()`/`snip()` 转义。
- **上传**：`finfo` 真实 MIME + `getimagesize` + 2MB 上限 + 扩展白名单（jpg/png/webp）+ 随机文件名（不采信客户端文件名）；nginx 对上传目录禁用 PHP 执行。
- **审计**：登录 / 增删改 / 改密均写 `audit_log`（用户 / 动作 / 对象 / IP / 时间）。
- **部署头**：nginx `server_tokens off` + CSP（放行 inline 与 data:，因站点用内联样式/脚本）+ HSTS + `X-Content-Type-Options` + `X-Frame-Options: DENY` + `Referrer-Policy`。

### 11.5 后台模块

入口 `public/admin.php`，侧栏 9 项：**仪表盘**（只读概览 + 最近审计）· 新闻与活动 · 论坛帖子 · 论坛热榜 · 团队成员 · 合作伙伴 · 内容卡片 · **文案片段** · **修改密码**。

- 六个集合模块复用通用组件 `app/admin/crud.php`（列表 / 表单 / 保存 / 删除 + 「发布·排序」内联快存），以纯 `$cfg` 声明字段；`_managed` 隐藏字段机制支持部分快存与整表编辑的复选框语义。
- 文案片段模块按分组一表编辑，仅对**变更项**写库并审计。

### 11.6 种子幂等与范围边界

- `php bin/seed.php` 可重复运行：集合有数据即 `skip`、`users` 存在即 `skip`、`snippets` 逐键仅补缺失——**已改文案不被覆盖**，重复运行集合计数不变（已验证）。
- **范围边界（交互组件留代码）**：`hero-anim` / `agent-demo` / `agent-cases` / `knowledge-graph` 及各页内联筛选、弹幕等交互仍为静态 JS，不入库、不后台配置，保持 §5 既有行为与「无状态、无网络请求」语义。数字仍为示意，不杜撰。

参见实施计划 `docs/superpowers/plans/2026-08-29-mabseek-p5-backend-cms.md`（及 `docs/superpowers/specs/` 下对应设计规格）。
