# MabSeek 部署手册 —— 阿里云 Ubuntu（nginx + PHP，公网 IP + HTTP）

目标：把项目部署到 `/var/www/html`，docroot 为 `/var/www/html/public`，
以「公网 IP + http（暂无证书）」方式访问后台并成功登录。

布局（关键）：
```
/var/www/html/            ← 项目根（tar 解压到这里）
├── public/               ← nginx docroot，唯一对外目录
│   ├── index.php admin.php ...
│   ├── assets/  partials/
├── app/                  ← 应用代码，在 docroot 之上，URL 不可达
├── bin/                  ← seed.php 等命令行脚本
├── data/                 ← SQLite 库（www-data 可写）
└── deploy/               ← nginx 样例
```
> `app/config.php` 里 `UPLOAD_DIR = BASE_ROOT/public/...` 把 docroot 目录名写死为
> `public`。**docroot 目录必须叫 `public`,且 `app/ data/ bin/` 必须是它的兄弟目录。**

---

## 0. 前置检查（在服务器上）

```bash
# PHP-FPM 的 socket 路径（记下版本，后面 nginx 配置要用）
ls /run/php/php*-fpm.sock
# 确认扩展齐全：需要 pdo_sqlite、sodium(Argon2id)
php -m | grep -Ei 'pdo_sqlite|sodium'
# nginx / php-fpm 是否在跑
systemctl status nginx --no-pager | head -3
systemctl status php*-fpm --no-pager | head -3
```
若 `pdo_sqlite` 或 `sodium` 缺失：`sudo apt-get install -y php-sqlite3 php-sodium && sudo systemctl restart php*-fpm`。

> 阿里云控制台：确保安全组**入方向放行 80 端口**，否则公网访问不通。

---

## 1. 本地打包（在你的 Mac 上，项目目录内）

排除开发库、git、文档、缓存等，只打 4 个目录：

```bash
cd /Users/zhoumo/Documents/Claude/mabseek
COPYFILE_DISABLE=1 tar \
  --exclude='.DS_Store' --exclude='.git' --exclude='node_modules' \
  --exclude='*.sqlite' --exclude='*.sqlite-*' \
  -czf /tmp/mabseek-deploy.tgz \
  app bin public deploy
ls -lh /tmp/mabseek-deploy.tgz
```
> 不打包 `data/` 里的 sqlite —— 生产库将由服务器上的 seed 全新生成。

---

## 2. 上传到服务器

把 `<user>@<公网IP>` 换成你的实际值：

```bash
scp /tmp/mabseek-deploy.tgz <user>@<公网IP>:/tmp/
```

---

## 3. 解压到 /var/www/html（在服务器上）

```bash
sudo mkdir -p /var/www/html
sudo tar -xzf /tmp/mabseek-deploy.tgz -C /var/www/html
ls /var/www/html          # 应看到 app bin public deploy
```

---

## 4. 目录与权限

原则：源码只读、`data/` 与上传目录对 www-data 可写。

```bash
# 创建可写目录
sudo mkdir -p /var/www/html/data /var/www/html/public/assets/images/uploads

# 全部归 root，统一基础权限
sudo chown -R root:root /var/www/html
sudo find /var/www/html -type d -exec chmod 755 {} \;
sudo find /var/www/html -type f -exec chmod 644 {} \;

# 仅可写目录交给 php-fpm 用户 www-data
sudo chown -R www-data:www-data /var/www/html/data /var/www/html/public/assets/images/uploads
sudo chmod 750 /var/www/html/data /var/www/html/public/assets/images/uploads
```

---

## 5. 初始化数据库（全新 seed）

以 www-data 身份运行,保证生成的库文件属主正确:

```bash
cd /var/www/html
sudo -u www-data php bin/seed.php
ls -l /var/www/html/data/           # 应出现 mabseek.sqlite，属主 www-data
sudo chmod 640 /var/www/html/data/mabseek.sqlite
```
seed 会创建管理员 **admin / mabseek2026**,并置 `must_change_password=1`(首次登录强制改密)。

---

## 6. 安装 nginx 站点配置

样例已随包上传到 `/var/www/html/deploy/nginx-var-www-html-http.conf.sample`。

```bash
sudo cp /var/www/html/deploy/nginx-var-www-html-http.conf.sample \
        /etc/nginx/sites-available/mabseek

# ★ 把 socket 改成第 0 步查到的实际版本（例：php8.1）
sudo sed -i 's#php8.3-fpm.sock#php8.1-fpm.sock#' /etc/nginx/sites-available/mabseek
#   —— 若本来就是 8.3 可跳过这条

# 启用本站，停用默认站（避免默认站抢占 80 default_server）
sudo ln -sf /etc/nginx/sites-available/mabseek /etc/nginx/sites-enabled/mabseek
sudo rm -f /etc/nginx/sites-enabled/default

sudo nginx -t && sudo systemctl reload nginx
```

> ⚠ 该配置**刻意不含** `fastcgi_param HTTPS on;`。因为你用 http 访问,一旦设了它,
> PHP 会给会话 Cookie 打 Secure 标志,浏览器在 http 下不回传 → 每次请求都是新会话
> → 登录必报「CSRF 校验失败」。这一步千万别手动加回去。

---

## 7. 验证（在服务器上，本机自测）

```bash
# 首页与后台应 200
curl -sI http://127.0.0.1/         | head -1
curl -sI http://127.0.0.1/admin.php | head -1

# 会话 Cookie 不应带 Secure（http 下的正确表现）
curl -s -D - http://127.0.0.1/admin.php -o /dev/null | grep -i 'set-cookie'

# 越权检查：以下应 404/403，证明 app/data/partials 不可达
curl -sI http://127.0.0.1/data/mabseek.sqlite | head -1   # 404
curl -sI http://127.0.0.1/partials/           | head -1   # 403
```

冷启动登录自测(应返回 `302` 跳到 admin.php,证明 CSRF/会话链路正常):

```bash
JAR=$(mktemp)
TOK=$(curl -s -c "$JAR" http://127.0.0.1/admin.php \
      | grep -o 'name="_csrf" value="[^"]*"' | sed 's/.*value="//;s/"//')
curl -s -b "$JAR" -c "$JAR" -X POST http://127.0.0.1/admin.php \
     --data-urlencode "_csrf=$TOK" \
     --data-urlencode "username=admin" \
     --data-urlencode "password=mabseek2026" \
     -o /dev/null -w '%{http_code} -> %{redirect_url}\n'
```

最后用浏览器访问 `http://<公网IP>/admin.php`,用 `admin / mabseek2026` 登录,
系统会**强制你立即修改初始密码**(≥10 位),改完即进入后台。

---

## 8. 以后启用 HTTPS（有证书时）

1. 证书就绪后,在现有 HTTP 样例 `deploy/nginx-var-www-html-http.conf.sample` 基础上增加 443 server 块(443 + 80→301 跳转 + 安全头)。
2. **仅在 443 的 PHP location 里**加回 `fastcgi_param HTTPS on;` —— 此时浏览器走 https,
   Secure Cookie 才正确生效。
3. 最简路径:`sudo apt-get install -y certbot python3-certbot-nginx && sudo certbot --nginx`,
   certbot 会自动补全 443 与跳转;完成后再确认 PHP location 里有 `HTTPS on;`。

---

## 常见问题

- **登录报「CSRF 校验失败」**:多半是 nginx 里误加了 `fastcgi_param HTTPS on;`(http 场景),
  或浏览器用了缓存/后退键回放旧登录页。先按第 6 步核对配置,再用无痕窗口打开全新登录页。
- **上传图片 500 / 无法写入**:检查 `data/` 和 `public/assets/images/uploads/` 属主是否为
  `www-data`、权限 750(第 4 步)。
- **502 Bad Gateway**:socket 路径填错。按第 0 步 `ls /run/php/` 的实际值改第 6 步。
