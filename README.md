# MabSeek 抗体求索

清华大学医学院实验室对外网站 + 内容管理后台。零外部依赖，纯 PHP 8 + SQLite，可在任意支持 PHP 的主机上直接部署。

## 技术栈

- **PHP 8**（无 Composer、无框架、零外部依赖）
- **SQLite**（通过 `pdo_sqlite`，单文件数据库）
- 前端为服务端渲染的原生 HTML/CSS/JS，无构建步骤

## 目录结构

采用 **Web 根隔离**：只有 `public/` 作为站点根目录对外暴露，其余目录都在文档根之上，无法被 HTTP 直接访问。

```
mabseek/
├── public/            ← 唯一对外文档根（nginx/php -S 指向这里）
│   ├── index.php        首页
│   ├── platform.php     平台（合并原技术平台 + Antibody Agent 两页，V4 版）
│   ├── technology.php   → 301 转跳 platform.php（legacy stub，保留历史外链兼容）
│   ├── agent.php        → 301 转跳 platform.php（legacy stub，保留历史外链兼容）
│   ├── education.php    教育（课程视频 · 教育往期回顾）
│   ├── edu-review.php   教育回顾详情
│   ├── forum.php        论坛（会员发帖 · 富文本）
│   ├── threads-api.php  论坛帖子列表异步接口（分页/筛选，JSON）
│   ├── thread-new.php   发帖（富文本编辑器 + 图片上传）
│   ├── thread-edit.php  编辑本人帖
│   ├── thread.php       帖子详情（直出净化后 HTML）
│   ├── news.php         新闻详情
│   ├── upload.php       会员图片上传端点（登录 + CSRF，复用图片校验）
│   ├── feedback.php     联系反馈提交端点（CSRF）
│   ├── about.php        了解我们（团队成员 · 国际合作）
│   ├── login.php        登录（?next=/?trial= 参数透传）
│   ├── register.php     注册（同步 SciencePal）
│   ├── logout.php       登出
│   ├── account.php      会员账户
│   ├── captcha.php      图形验证码
│   ├── admin.php        管理后台入口
│   ├── partials/        公共片段（head-meta.php 等）
│   └── assets/          静态资源（css/js/images/videos，含 uploads/ 上传目录）
├── app/               ← 应用代码（在文档根之上，不可 HTTP 访问）
│   ├── config.php       常量配置（路径、会话、登录风控、上传限制、seed 账号）
│   ├── config.local.php.example  SciencePal 密钥模板（复制为 config.local.php 并填入）
│   ├── bootstrap.php    统一引导（会话硬化、迁移、依赖装配）
│   ├── db.php           PDO 连接 + 幂等建表迁移（CREATE TABLE IF NOT EXISTS）
│   ├── auth.php         登录校验、会话、登录失败锁定、审计日志
│   ├── csrf.php         CSRF 令牌
│   ├── captcha.php      图形验证码
│   ├── members.php      会员账号（注册/登录/状态）
│   ├── sciencepal.php   SciencePal 合作方账号同步
│   ├── threads.php      论坛帖子（校验/发布/治理，正文按纯文本长度校验）
│   ├── edu_reviews.php  教育往期回顾（校验/查询）
│   ├── feedback.php     联系反馈（校验/存储）
│   ├── html_sanitizer.php  富文本白名单净化（DOM 白名单，仅放行站内上传图）
│   ├── helpers.php      转义/时间/客户端 IP 等工具
│   ├── admin/           后台各功能模块（含 edu_video 课程视频上传）
│   └── repositories/    数据访问层
├── bin/
│   ├── seed.php                        幂等初始化：建库、建管理员账号、灌入初始内容
│   ├── migrate-images-webp.php         静态图片路径 .png/.jpg → .webp（保护上传图）
│   ├── migrate-contact-email.php       订正历史联系邮箱
│   ├── migrate-feedback-iteration.php  反馈迭代
│   ├── migrate-news-category.php       新闻分类
│   ├── migrate-home-news-title.php     首页近况标题
│   ├── migrate-20260909-team-edu.php   20260909 团队/教育
│   ├── migrate-web-update-260920.php   260920 网页更新（logo 墙/教育页/张老师成果/国际合作）
│   ├── migrate-web-update-302.php      V3.0.2 网页更新
│   ├── migrate-v4-batch1.php           V4 批 1 网页更新（合并平台与 Agent）
│   ├── migrate-v4-batch2b1.php         V4 批 2b-1 平台页（21 条 platform.* + home.hero.cta 订正）
│   ├── migrate-v4-batch2b2a.php        V4 批 2b-2a 平台页视觉升级（4 条新 snippet + 36 孤儿清理）
│   └── migrate-v43.php                 V4.3 网页更新（首页题眉/教育15讲/关于国际合作/教育访谈/平台7屏）
├── data/              ← SQLite 数据库文件所在（git 忽略，运行时生成）
├── deploy/            ← 部署脚本与样例（见「部署」）
├── docs/              ← 架构文档、UI 规范
└── tests/
    └── run.php          零依赖测试套件
```

> **迁移脚本约定**：所有 `bin/migrate-*.php` 幂等设计，可重复运行，命名按迭代批次编号。部署脚本 `deploy/deploy-mabseek.sh` 会在 seed 之后按顺序调用。新增数据库内容改动时必须同步：`seed.php` 种子默认值 + 新迁移脚本 + `deploy-mabseek.sh` 接入 —— 详见 [CLAUDE.md](CLAUDE.md)。

## 本地运行

```bash
php bin/seed.php          # 首次运行：初始化数据库与初始内容（幂等，可重复执行）
php -S localhost:8778 -t public
```

打开 http://localhost:8778 查看前台，http://localhost:8778/admin.php 进入后台。

> 在 Claude Code / 预览环境中可直接用 `.claude/launch.json` 里的 `mabseek-php` 配置启动。

## 管理后台

- 入口 `public/admin.php`，登录后可管理各页面内容。
- **内容模块**：仪表盘 · 新闻 · 团队成员（支持头像上传，无头像时用文字头像）· 合作伙伴 · 内容卡片 · 文案片段（snippets）· 会员管理 · 论坛帖子治理 · 教育往期回顾 · **课程视频上传**（`edu_video`，≤50MB，存 `assets/videos/`；V4.3 后教育页 `#video` 改为张林琦访谈专用模块，视频文件手动 scp 到 `assets/uploads/videos/zhang-linqi-vaccine-talk.mp4`）· 联系反馈查看 · 修改密码。
- 初始管理员账号由 `bin/seed.php` 依据 `app/config.php` 中的 `SEED_ADMIN_USER` / `SEED_ADMIN_PASS` 创建（默认 `admin` / `mabseek2026`），**首次登录强制修改密码**。生产环境请在 seed 前改掉默认值，或改后立即修改。

### 安全措施

- 密码使用 **Argon2id** 哈希存储，绝不落库明文。
- 全站表单 **CSRF** 令牌校验。
- **登录失败锁定**：单 (IP, 用户) 连续失败 5 次 或 单 IP 15 分钟内失败 20 次即锁定 15 分钟（防用户名轮换绕过与 Argon2id 资源耗尽）。
- 会话硬化：登录后 `session_regenerate_id`、绑定 User-Agent、空闲 30 分钟 / 绝对 8 小时超时。
- 上传限制：≤ 2MB，仅 `jpeg` / `png` / `webp`（通过 finfo 嗅探 + `getimagesize` 校验，随机文件名落盘）。
- **富文本净化**：新闻正文与论坛帖子的富文本一律经服务端 DOM 白名单净化（`app/html_sanitizer.php`）后落库、且渲染前再净化一次；仅放行站内上传图片，外链图与脚本/事件属性一律剥离。
- 关键操作写入审计日志。

## 会员与论坛

- 会员可注册/登录，在论坛发帖、编辑与删除本人帖子。
- 发帖/编辑复用与后台新闻相同的富文本编辑器（工具栏 + 图片插入）；工具栏采用 CSS `position: sticky` 悬浮，编辑器可见范围内始终吸附在顶栏下方，长文中随处插图无需回滚。正文按「净化后纯文本」长度校验（1–5000 字，标签与图片不计入），停用账号禁止发帖与上传。
- 会员图片经独立端点 `public/upload.php`（会员登录 + CSRF + 复用后台图片校验）上传，与管理员上传端点隔离。
- 论坛帖子列表通过 `public/threads-api.php` 异步加载（支持分页与分类筛选，返回 JSON）。

## 教育与反馈

- 教育页除张林琦访谈模块与往期回顾外，展示 `edu_reviews` 卡片，点击进入 `edu-review.php` 详情。
- 张林琦访谈《疫苗的力量》：`#video` 区左文（eyebrow + 标题 + 正文 + 四关键词 chip）右视频（16:9，poster + 播放按钮，点击后加载 `<video controls autoplay>`）；视频文件缺失时降级为海报 + 「视频加载中」提示，不阻塞首屏。视频 44.5MB MP4 由用户手动 scp 到生产 `/var/www/html/public/assets/uploads/videos/`，不进 git、不进 tar 包。
- 访客可通过页面的联系反馈表单（`public/feedback.php`，CSRF 校验）提交需求/合作意向，后台「联系反馈」模块查看。

## 全局试用 CTA（trial-cta）

- 全站带 `data-trial-cta` 的按钮统一被 `assets/js/trial-cta.js` 拦截，弹出 SciencePal 试用申请模态框，会员登录状态由 `partials/head-meta.php` 输出的 meta 决定，未登录跳 `login.php?next=…&trial=1` 后自动回到原页并唤起模态。
- 登录 / 注册页支持 `?next=` 与 `?trial=1` 参数透传（防开放重定向：只接受本站相对路径）。

## 站点配图

- 全站配图为 AI 生成的深色科技风插画（紫 + 荧光绿渐变），统一收纳于 `public/assets/images/`；团队真实头像置于 `assets/images/team/`。
- 静态配图均采用 **WebP** 格式（较 PNG/JPG 显著减小体积、加载更快），照片型质量 q82、logo 与头像 q90 保留透明。数据库里存量的图片路径由 `bin/migrate-images-webp.php` 在部署时幂等迁移，仅替换存在对应 WebP 的引用，不动 `uploads/` 下的用户上传图。
- 生成新 WebP 时**必须用 `cwebp`**，macOS 自带的 `sips` 不能写 WebP（会静默失败）。

## 版本迭代记录

按批次逐步演进，每批含 seed 更新 + 幂等迁移 + 部署脚本接入。

### 260920 · 首轮扩展
- **首页合作伙伴 logo 墙**：17 图三行同向滚动展示。
- **教育页**：课程视频支持后台上传（≤50MB，存 `assets/videos/`），banner 去字 + 两列布局 + 教学安排折叠。
- **了解我们**：负责人张老师详细成果（论文 276+ / 专利 34+ / 荣誉手风琴），医学楼全景背景；国际合作以印尼 / PRA 双图片轮播呈现。
- 迁移：`bin/migrate-web-update-260920.php`

### V3.0.2 · logo 与教育精调
- Logo 修正、教育页分段调整、了解我们页文案精简。
- 迁移：`bin/migrate-web-update-302.php`

### V4 批 1 · SciencePal 对接与全局 CTA
- 会员注册/改密同步 SciencePal（密钥走 `app/config.local.php` 或环境变量，见下）。
- 引入全局 `trial-cta` 拦截 + SciencePal 试用模态框；登录/注册页支持 `?next=` / `?trial=1` 参数。
- 富文本编辑器工具栏改 sticky 悬浮，长文中随处插图无需回滚。
- 迁移：`bin/migrate-v4-batch1.php`

### V4 批 2b · 平台页合并（技术平台 + Antibody Agent → platform.php）
- **导航 6 → 5 项**：`technology.php` 与 `agent.php` 合并为新 `platform.php`，前两页改为 301 stub 保留历史外链兼容。
- 屏 3「AI 平台已支撑的靶点谱系」用三张纯 SVG 数据图（GLP-1R 剂量曲线 / CXCR4 亲和力柱 / CD3 结合特异性 heatmap）替代动画占位。
- 屏 5 干湿闭环从直线流程改为 SVG 环形五节点。
- 平台页各屏 CTA 挂 `data-trial-cta` 接入试用模态；屏 1 hero eyebrow 品牌化。
- 迁移：`bin/migrate-v4-batch2b1.php`（21 条 platform.* + home.hero.cta 订正）
- 迁移：`bin/migrate-v4-batch2b2a.php`（4 条新 snippet + 36 条 orphan 清理）

### V4.3 · 网页迭代（首页题眉 · 教育大纲 · 国际合作 · 教育访谈 · 平台 7 屏）
- **首页 Hero**：清空 `home.hero.eyebrow`（删「清华团队 * AI大模型」）。
- **教育页 15 讲**：折叠区字段瘦身为「第 N 讲 · 标题 / 授课：XX」，不含课程队长与日期。
- **关于我们·国际合作**：中印尼中心 / PRA 两段正文强制刷新为需求文档新文案。
- **教育页 `#video` 改造**：由「课程视频上传」占位改为「《疫苗的力量》与张林琦教授对话」访谈模块（左文右视频，四关键词 chip），44.5MB MP4 手动上传到生产。
- **平台页 `platform.php` 全文件重写为 7 屏纵向单页**：Hero / Agent / Data / Lab（4a 全流程 + 4b VLP）/ Loop 干湿闭环 / Case 代表性成果 / Try 试用 CTA。深-浅-深-浅-深-浅-黑节奏。删 `agent-demo.js` / `agent-cases.js` legacy 脚本。
- 迁移：`bin/migrate-v43.php`（幂等 A/B/C/D/E 五块 · 二次运行 0 行变更）

## 静态资源缓存

- CSS/JS 引用统一经 `asset()`（`app/helpers.php`）输出，按文件 `mtime` 追加 `?v=` 版本号。改动前端资源后浏览器会自动拉取新版，避免部署更新后命中旧强缓存。新增前端资源引用时优先用 `asset('assets/...')` 而非写死路径。

## 测试

```bash
php tests/run.php
```

零依赖自研断言套件，覆盖认证、CSRF、仓储、辅助函数等。

## 部署

面向 Ubuntu + nginx + php-fpm 的 HTTP 部署，详见 [deploy/DEPLOY-ubuntu-http.md](deploy/DEPLOY-ubuntu-http.md)。

- [deploy/deploy-mabseek.sh](deploy/deploy-mabseek.sh) — 服务器上的一键部署/更新脚本，双模式：**不加参数**从 GitHub 拉取最新代码部署；**追加压缩包路径**则解压本地 tar 包部署（离线/无 git 环境；纯文件名需与脚本同目录）。两种模式都保留数据库与上传图片，并自动备份。
- [deploy/pack-mac.sh](deploy/pack-mac.sh) — 在本机打包出 `mabseek-deploy.tgz`，供上面的离线模式使用。
- [deploy/nginx-var-www-html-http.conf.sample](deploy/nginx-var-www-html-http.conf.sample) — nginx 站点样例（HTTP；`*.html → *.php` 301；上传目录禁执行 PHP；安全响应头）。启用 HTTPS 时在此基础上增加 443 server 块。

nginx 文档根务必指向 `public/`，切勿指向项目根目录，以保持 Web 根隔离。

### SciencePal 合作方对接（密钥配置）

会员在本站注册成功即同步到 SciencePal 开通账号、本站改密时同步新密码。密钥不入 Git，生产环境需**手工配置一次**（git 拉取不会带来）：

- 复制模板并填入密钥：`cp app/config.local.php.example app/config.local.php`，编辑 `app/config.local.php` 填入 SciencePal 提供的 `SCIENCEPAL_PARTNER_KEY`；`SCIENCEPAL_API_BASE` 默认指向正式环境。
- 或改用环境变量 `SCIENCEPAL_PARTNER_KEY` / `SCIENCEPAL_API_BASE`（优先级高于配置文件），在 php-fpm 池或系统环境中设置。
- `app/config.local.php` 已被 `.gitignore` 排除，切勿提交；密钥只放服务器后端。
- 未配置密钥时同步逻辑自动静默跳过（`enabled=false`），不影响本站注册/改密（本站优先）。
- 同步状态存 `sciencepal_sync` 表（随 `app/db.php` 的 `migrate()` 自动建表，无需单独迁移脚本）。

### 提交前检查（持续集成保障）

提交到 GitHub 前，须确认一键部署脚本能让本次改动在生产环境正常集成。重点：数据库内容改动（`data/` 不进 git、seed 有数据即跳过）必须配套 `seed.php` 种子更新 + 幂等迁移脚本（`bin/migrate-*.php`）+ 接入 `deploy-mabseek.sh`；静态资源须确认已入库且引用已更新；迁移脚本须幂等且不误伤用户数据；在线 / 离线两种部署模式都要覆盖。完整规则见 [CLAUDE.md](CLAUDE.md)。

## 文档

- [docs/architecture.md](docs/architecture.md) — 架构说明
- [docs/ui-style-guide.md](docs/ui-style-guide.md) — UI 规范
