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
│   ├── education.php    教育
│   ├── forum.php        论坛
│   ├── about.php        了解我们
│   ├── admin.php        管理后台入口
│   └── assets/          静态资源（css/js/images，含 uploads/ 上传目录）
├── app/               ← 应用代码（在文档根之上，不可 HTTP 访问）
│   ├── config.php       常量配置（路径、会话、登录风控、上传限制、seed 账号）
│   ├── bootstrap.php    统一引导（会话硬化、迁移、依赖装配）
│   ├── db.php           PDO 连接 + 幂等建表迁移（CREATE TABLE IF NOT EXISTS）
│   ├── auth.php         登录校验、会话、登录失败锁定、审计日志
│   ├── csrf.php         CSRF 令牌
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
- 初始管理员账号由 `bin/seed.php` 依据 `app/config.php` 中的 `SEED_ADMIN_USER` / `SEED_ADMIN_PASS` 创建，**首次登录强制修改密码**。生产环境请在 seed 前改掉默认值，或改后立即修改。

### 安全措施

- 密码使用 **Argon2id** 哈希存储，绝不落库明文。
- 全站表单 **CSRF** 令牌校验。
- **登录失败锁定**：单 (IP, 用户) 连续失败 5 次 或 单 IP 15 分钟内失败 20 次即锁定 15 分钟（防用户名轮换绕过与 Argon2id 资源耗尽）。
- 会话硬化：登录后 `session_regenerate_id`、绑定 User-Agent、空闲 30 分钟 / 绝对 8 小时超时。
- 上传限制：≤ 2MB，仅 `jpeg` / `png` / `webp`。
- 关键操作写入审计日志。

## 测试

```bash
php tests/run.php
```

零依赖自研断言套件，覆盖认证、CSRF、仓储、辅助函数等。

## 部署

面向 Ubuntu + nginx + php-fpm 的 HTTP 部署，详见 [deploy/DEPLOY-ubuntu-http.md](deploy/DEPLOY-ubuntu-http.md)。

- [deploy/pack-mac.sh](deploy/pack-mac.sh) — 在本机打包发布产物。
- [deploy/deploy-mabseek.sh](deploy/deploy-mabseek.sh) — 部署到目标服务器。
- [deploy/nginx-var-www-html-http.conf.sample](deploy/nginx-var-www-html-http.conf.sample) — nginx 站点样例（HTTP；`*.html → *.php` 301；上传目录禁执行 PHP；安全响应头）。启用 HTTPS 时在此基础上增加 443 server 块。

nginx 文档根务必指向 `public/`，切勿指向项目根目录，以保持 Web 根隔离。

## 文档

- [docs/architecture.md](docs/architecture.md) — 架构说明
- [docs/ui-style-guide.md](docs/ui-style-guide.md) — UI 规范
- `docs/superpowers/` — 设计（specs）与实施计划（plans）归档
