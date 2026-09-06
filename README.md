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
│   ├── technology.php   技术平台
│   ├── agent.php        Antibody Agent
│   ├── education.php    教育（课程 · 讲座 · 教育往期回顾）
│   ├── edu-review.php   教育回顾详情
│   ├── forum.php        论坛（会员发帖 · 富文本）
│   ├── threads-api.php  论坛帖子列表异步接口（分页/筛选，JSON）
│   ├── thread-new.php   发帖（富文本编辑器 + 图片上传）
│   ├── thread-edit.php  编辑本人帖
│   ├── thread.php       帖子详情（直出净化后 HTML）
│   ├── upload.php       会员图片上传端点（登录 + CSRF，复用图片校验）
│   ├── feedback.php     联系反馈提交端点（CSRF）
│   ├── about.php        了解我们（团队成员 · 国际合作）
│   ├── admin.php        管理后台入口
│   └── assets/          静态资源（css/js/images，含 uploads/ 上传目录）
├── app/               ← 应用代码（在文档根之上，不可 HTTP 访问）
│   ├── config.php       常量配置（路径、会话、登录风控、上传限制、seed 账号）
│   ├── bootstrap.php    统一引导（会话硬化、迁移、依赖装配）
│   ├── db.php           PDO 连接 + 幂等建表迁移（CREATE TABLE IF NOT EXISTS）
│   ├── auth.php         登录校验、会话、登录失败锁定、审计日志
│   ├── csrf.php         CSRF 令牌
│   ├── members.php      会员账号（注册/登录/状态）
│   ├── threads.php      论坛帖子（校验/发布/治理，正文按纯文本长度校验）
│   ├── edu_reviews.php  教育往期回顾（校验/查询）
│   ├── feedback.php     联系反馈（校验/存储）
│   ├── html_sanitizer.php  富文本白名单净化（DOM 白名单，仅放行站内上传图）
│   ├── helpers.php      转义/时间/客户端 IP 等工具
│   ├── admin/           后台各功能模块
│   └── repositories/    数据访问层
├── bin/
│   └── seed.php         幂等初始化：建库、建管理员账号、灌入初始内容
├── data/              ← SQLite 数据库文件所在（git 忽略，运行时生成）
├── deploy/            ← 部署脚本与样例（见「部署」）
├── docs/              ← 架构文档、UI 规范、PRD、设计/计划归档
└── tests/
    └── run.php          零依赖测试套件
```

## 本地运行

```bash
php bin/seed.php          # 首次运行：初始化数据库与初始内容（幂等，可重复执行）
php -S localhost:8778 -t public
```

打开 http://localhost:8778 查看前台，http://localhost:8778/admin.php 进入后台。

> 在 Claude Code / 预览环境中可直接用 `.claude/launch.json` 里的 `mabseek-php` 配置启动。

## 管理后台

- 入口 `public/admin.php`，登录后可管理各页面内容。
- 内容模块：新闻、团队成员（支持头像上传，无头像时用文字头像）、合作伙伴、内容卡片、文案片段（snippets）、教育往期回顾、会员管理、论坛帖子治理、联系反馈查看。
- 初始管理员账号由 `bin/seed.php` 依据 `app/config.php` 中的 `SEED_ADMIN_USER` / `SEED_ADMIN_PASS` 创建，**首次登录强制修改密码**。生产环境请在 seed 前改掉默认值，或改后立即修改。

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
- 发帖/编辑复用与后台新闻相同的富文本编辑器（工具栏 + 图片插入）；正文按「净化后纯文本」长度校验（1–5000 字，标签与图片不计入），停用账号禁止发帖与上传。
- 会员图片经独立端点 `public/upload.php`（会员登录 + CSRF + 复用后台图片校验）上传，与管理员上传端点隔离。
- 论坛帖子列表通过 `public/threads-api.php` 异步加载（支持分页与分类筛选，返回 JSON）。

## 教育与反馈

- 教育页除课程与讲座外，展示「教育往期回顾」（`edu_reviews`）卡片，点击进入 `edu-review.php` 详情。
- 访客可通过页面的联系反馈表单（`public/feedback.php`，CSRF 校验）提交需求/合作意向，后台「联系反馈」模块查看。

## 站点配图

- 全站配图为 AI 生成的深色科技风插画（紫 + 荧光绿渐变），统一收纳于 `public/assets/images/`；团队真实头像置于 `assets/images/team/`。

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

### 提交前检查（持续集成保障）

提交到 GitHub 前，须确认一键部署脚本能让本次改动在生产环境正常集成。重点：数据库内容改动（`data/` 不进 git、seed 有数据即跳过）必须配套 `seed.php` 种子更新 + 幂等迁移脚本（`bin/migrate-*.php`）+ 接入 `deploy-mabseek.sh`；静态资源须确认已入库且引用已更新；迁移脚本须幂等且不误伤用户数据；在线 / 离线两种部署模式都要覆盖。完整规则见 [CLAUDE.md](CLAUDE.md)。
