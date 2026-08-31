# MabSeek 部署手册 —— 阿里云 Ubuntu（nginx + PHP，公网 IP + HTTP）

目标：把项目部署到 `/var/www/html`，docroot 为 `/var/www/html/public`，
以「公网 IP + http（暂无证书）」方式访问后台并成功登录。

布局（关键）：
```
/var/www/html/            ← 项目根（deploy-mabseek.sh 铺开到这里）
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

## 1. 一键部署 / 更新（在服务器上）

把 `deploy/deploy-mabseek.sh` 放到服务器家目录 `~`，之后每次更新只需再跑一遍它。脚本支持两种模式：

**① 在线模式（不加参数，从 GitHub 拉取）** —— 推荐日常使用：

```bash
# 首次：把脚本放到 ~（二选一）
scp deploy/deploy-mabseek.sh <user>@<公网IP>:~        # 从本机推
#   或在服务器上直接从仓库取：
curl -fsSL https://raw.githubusercontent.com/AlexZhouMo/mabseek/main/deploy/deploy-mabseek.sh -o ~/deploy-mabseek.sh

# 一键部署 / 更新（拉 main 最新代码并自动部署）
sudo bash ~/deploy-mabseek.sh
#   指定分支：sudo BRANCH=<分支名> bash ~/deploy-mabseek.sh
```

**② 离线模式（追加压缩包路径，解压本地 tar 包）** —— 服务器无法联网/无 git 时：

```bash
# 在本机用 deploy/pack-mac.sh 打出 mabseek-deploy.tgz，scp 到服务器后：
sudo bash ~/deploy-mabseek.sh /path/to/mabseek-deploy.tgz   # 含路径：按该路径找包
sudo bash ~/deploy-mabseek.sh mabseek-deploy.tgz            # 纯文件名：须与脚本同一目录（~）
```

两种模式**取得代码之后的动作完全一致**：铺开到 `/var/www/html` → **保留 `data/` 数据库与 `public/assets/images/uploads/` 上传图片**，并在 `/var/www/mabseek-backup-<时间戳>` 留一份备份 → 幂等 `seed` → 刷新权限 → 重载 php-fpm/nginx → 本机自检。可反复运行，历史数据不丢。在线模式的 git 工作副本存于 `/opt/mabseek-src`（首次 `git clone`，之后 `fetch` + `reset --hard`）。

> 前置：服务器需先装好 `php-fpm`（含 `pdo_sqlite`、`sodium` 扩展）、`nginx`，并放行 80 端口（见第 0 步）；在线模式另需 `git`。仓库为公开仓库，在线拉取无需认证；若日后转私有，需为服务器上的 root 配置 git 凭据，或改用离线模式。
> **首次部署**还要手动装一次 nginx 站点配置（第 4 步），装好后脚本自检即通过；之后的更新脚本会自动重载 nginx，无需再动。

> 下面第 2–6 步是脚本内部动作的原理说明与排错参考。**常规更新无需手动执行**；唯一需要手动做一次的是第 4 步（首次配置 nginx）。

---

## 2. 目录与权限

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

## 3. 初始化数据库（全新 seed）

以 www-data 身份运行,保证生成的库文件属主正确:

```bash
cd /var/www/html
sudo -u www-data php bin/seed.php
ls -l /var/www/html/data/           # 应出现 mabseek.sqlite，属主 www-data
sudo chmod 640 /var/www/html/data/mabseek.sqlite
```
seed 会创建管理员 **admin / mabseek2026**,并置 `must_change_password=1`(首次登录强制改密)。

---

## 4. 安装 nginx 站点配置

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

## 5. 验证（在服务器上，本机自测）

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

## 6. 以后启用 HTTPS（有证书时）

1. 证书就绪后,在现有 HTTP 样例 `deploy/nginx-var-www-html-http.conf.sample` 基础上增加 443 server 块(443 + 80→301 跳转 + 安全头)。
2. **仅在 443 的 PHP location 里**加回 `fastcgi_param HTTPS on;` —— 此时浏览器走 https,
   Secure Cookie 才正确生效。
3. 最简路径:`sudo apt-get install -y certbot python3-certbot-nginx && sudo certbot --nginx`,
   certbot 会自动补全 443 与跳转;完成后再确认 PHP location 里有 `HTTPS on;`。

---

## 常见问题

- **登录报「CSRF 校验失败」**:多半是 nginx 里误加了 `fastcgi_param HTTPS on;`(http 场景),
  或浏览器用了缓存/后退键回放旧登录页。先按第 4 步核对配置,再用无痕窗口打开全新登录页。
- **上传图片 500 / 无法写入**:检查 `data/` 和 `public/assets/images/uploads/` 属主是否为
  `www-data`、权限 750(第 2 步)。
- **502 Bad Gateway**:socket 路径填错。按第 0 步 `ls /run/php/` 的实际值改第 4 步。
