#!/usr/bin/env bash
#
# deploy-mabseek.sh —— 阿里云 Ubuntu 一键部署 / 更新 MabSeek（公网 IP + HTTP）
#
# 放置位置：服务器家目录 ~/deploy-mabseek.sh
#
# 两种模式：
#   • 在线模式（不加参数）：从 GitHub 拉取最新代码后部署
#       sudo bash ~/deploy-mabseek.sh
#       sudo BRANCH=<分支名> bash ~/deploy-mabseek.sh      # 指定分支（默认 main）
#   • 离线模式（追加压缩包路径）：解压本地 tar 包后部署（无需联网/无需 git）
#       sudo bash ~/deploy-mabseek.sh /path/to/mabseek-deploy.tgz   # 含路径：按该路径找包
#       sudo bash ~/deploy-mabseek.sh mabseek-deploy.tgz            # 纯文件名：须与本脚本同目录
#
# 行为（两模式共用）：
#   1) 取得新代码到暂存区（在线=git 工作副本 /opt/mabseek-src；离线=临时解压目录）
#   2) 把代码目录（app/ bin/ public/ deploy/）铺开到 /var/www/html
#   3) 保留数据库（data/）与已上传图片（public/assets/images/uploads/）—— 历史数据不丢，并留一份备份
#   4) 幂等 seed、迁移历史图片路径为 .webp、刷新权限、重载 php-fpm/nginx、本机自检；全过程带时间戳详细日志
#
# 幂等：seed.php 只补缺不删数据；migrate 用 CREATE TABLE IF NOT EXISTS；
#       图片路径迁移仅在对应 .webp 存在时才改写，可反复运行。
#       故本脚本可反复运行：首次建库+初始数据，之后仅更新代码、历史数据原样保留。
#
# 说明：仓库为公开仓库，在线模式 git clone 走 HTTPS 无需认证。若日后转为私有仓库，
#       需在服务器上为 root 配置 git 凭据（如 gh auth / PAT / 部署密钥），或改用离线模式。

set -euo pipefail

# ─────────────────────────── 配置 ───────────────────────────
REPO_URL="https://github.com/AlexZhouMo/mabseek.git"   # GitHub 仓库（在线模式）
BRANCH="${BRANCH:-main}"             # 在线模式部署分支（可用环境变量覆盖）
SRC_DIR="/opt/mabseek-src"           # 服务器上的 git 工作副本（仅存代码，不放业务数据）
ROOT="/var/www/html"                 # 项目根 = docroot 的父目录
DOCROOT_NAME="public"                # docroot 目录名（config.php 写死，勿改）
WEBUSER="www-data"                   # php-fpm / nginx 运行用户
CODE_DIRS=(app bin public deploy)    # 每次部署要整体替换的代码目录
DATA_DIR="$ROOT/data"                # 数据库目录（保留）
UPLOAD_REL="public/assets/images/uploads"   # 上传目录（保留）
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)" # 本脚本所在目录（纯文件名离线包在此查找）

# ─────────────────────────── 日志 ───────────────────────────
if [ -t 1 ]; then C1=$'\033[1;36m'; CG=$'\033[1;32m'; CW=$'\033[1;33m'; CE=$'\033[1;31m'; C0=$'\033[0m'; else C1=; CG=; CW=; CE=; C0=; fi
ts()   { date '+%H:%M:%S'; }
step() { printf '\n%s==>%s %s[%s]%s %s\n' "$C1" "$C0" "$C1" "$(ts)" "$C0" "$*"; }
log()  { printf '    %s· %s%s\n' "$C0" "$*" "$C0"; }
ok()   { printf '    %s✔ %s%s\n' "$CG" "$*" "$C0"; }
warn() { printf '    %s! %s%s\n' "$CW" "$*" "$C0"; }
die()  { printf '\n%s✘ 失败：%s%s\n' "$CE" "$*" "$C0" >&2; exit 1; }
run()  { log "\$ $*"; "$@"; }
# 在 SRC_DIR 内执行 git（带 safe.directory，避免 root 下的 dubious ownership 报错）
gitc() { git -C "$SRC_DIR" -c safe.directory="$SRC_DIR" "$@"; }
# 清理临时目录（离线解压区、上传暂存区）——变量未设时安全跳过
cleanup_tmp() {
  [ "${CLEAN_STAGE:-0}" = "1" ] && [ -n "${STAGE:-}" ] && rm -rf "$STAGE" 2>/dev/null || true
  [ -n "${PRESERVE_UPLOADS:-}" ] && rm -rf "$PRESERVE_UPLOADS" 2>/dev/null || true
}

trap 'cleanup_tmp; die "第 $LINENO 行命令返回非零，部署已中止（数据与旧站点未被破坏的部分保持原状）"' ERR

BANNER_START="$(date '+%Y-%m-%d %H:%M:%S')"
printf '%s\n' "════════════════════════════════════════════════"
printf '  MabSeek 部署  %s\n' "$BANNER_START"
printf '%s\n' "════════════════════════════════════════════════"

# ─────────────────── 0. 基本前置 + 模式判定 ───────────────────
step "0/9 环境与参数检查"
[ "$(id -u)" -eq 0 ] || die "请用 root 运行：sudo bash $0"
ok "已具备 root 权限"

PKG_ARG="${1:-}"
if [ -n "$PKG_ARG" ]; then
  MODE="offline"
  # 纯文件名（不含 /）须与脚本同目录；含路径则按给定路径（绝对或相对当前目录）
  if [[ "$PKG_ARG" == */* ]]; then PKG="$PKG_ARG"; else PKG="$SCRIPT_DIR/$PKG_ARG"; fi
  [ -f "$PKG" ] || die "找不到离线包：$PKG（纯文件名需与脚本同目录 $SCRIPT_DIR）"
  ok "离线模式，部署包：$PKG"
else
  MODE="git"
  ok "在线模式，仓库 $REPO_URL（分支 $BRANCH）"
fi

# ─────────────────── 1. 依赖检查 ───────────────────
DEP_LABEL="PHP / 扩展 / php-fpm / nginx"; [ "$MODE" = "git" ] && DEP_LABEL="git / $DEP_LABEL"
step "1/9 依赖检查（$DEP_LABEL）"

if [ "$MODE" = "git" ]; then
  command -v git >/dev/null 2>&1 || die "在线模式需 git（apt-get install -y git），或改用离线模式：sudo bash $0 <包路径>"
  ok "git $(git --version | sed 's/^git version //')"
fi

command -v php >/dev/null 2>&1 || die "未安装 php（apt-get install -y php-fpm php-cli）"
PHP_VER="$(php -r 'echo PHP_VERSION;')"
ok "PHP $PHP_VER"

MISSING=""
for ext in pdo_sqlite sodium dom gd; do
  if php -m | grep -qi "^$ext$"; then ok "PHP 扩展 $ext"; else MISSING="$MISSING $ext"; fi
done
[ -z "$MISSING" ] || die "缺少 PHP 扩展:$MISSING （apt-get install -y php-sqlite3 php-sodium php-xml php-gd && systemctl restart php*-fpm）"

command -v nginx >/dev/null 2>&1 || die "未安装 nginx（apt-get install -y nginx）"
ok "nginx $(nginx -v 2>&1 | sed 's#.*/##')"

# php-fpm socket（供 nginx 配置引用；这里只提示，不强改 nginx 配置）
FPM_SOCK="$(ls /run/php/php*-fpm.sock 2>/dev/null | head -1 || true)"
if [ -n "$FPM_SOCK" ]; then ok "php-fpm socket：$FPM_SOCK"; else warn "未发现 /run/php/php*-fpm.sock，请确认 php-fpm 已启动"; fi

# php-fpm 服务名（部署末尾用于清 opcache）
FPM_SVC="$(systemctl list-unit-files --type=service 2>/dev/null | grep -oE 'php[0-9.]+-fpm\.service' | head -1 || true)"
[ -n "$FPM_SVC" ] && ok "php-fpm 服务：$FPM_SVC" || warn "未识别 php-fpm 服务名，稍后跳过 opcache 重载"

# ─────────────────── 2. 取得新代码到暂存区 ───────────────────
STAGE=""          # 暂存区：内含 app/ bin/ public/ deploy/
CLEAN_STAGE=0     # 1=部署后删除暂存区（离线临时目录要删；git 工作副本要留）
if [ "$MODE" = "git" ]; then
  step "2/9 从 GitHub 拉取代码到 $SRC_DIR"
  run mkdir -p "$(dirname "$SRC_DIR")"
  if [ -d "$SRC_DIR/.git" ]; then
    log "已存在工作副本，执行 fetch + reset --hard origin/$BRANCH"
    run gitc remote set-url origin "$REPO_URL"
    run gitc fetch --prune origin
    run gitc checkout -B "$BRANCH" "origin/$BRANCH"
    run gitc reset --hard "origin/$BRANCH"
    run gitc clean -fd            # 清理未跟踪的游离文件（SRC_DIR 仅存代码，无业务数据）
  else
    if [ -e "$SRC_DIR" ]; then
      ASIDE="${SRC_DIR}.bak-$(date '+%Y%m%d-%H%M%S')"
      warn "$SRC_DIR 存在但非 git 仓库，移到 $ASIDE 后重新 clone"
      run mv "$SRC_DIR" "$ASIDE"
    fi
    log "首次 clone（分支 $BRANCH）"
    run git clone --branch "$BRANCH" "$REPO_URL" "$SRC_DIR"
  fi
  STAGE="$SRC_DIR"
  VERSION="git $(gitc log --oneline -1 2>/dev/null || echo '未知')"
  ok "已同步到最新提交：$VERSION"
else
  step "2/9 解压离线包到临时目录"
  STAGE="$(mktemp -d /tmp/mabseek-stage.XXXXXX)"
  CLEAN_STAGE=1
  run tar -xzf "$PKG" -C "$STAGE"
  VERSION="离线包 $(basename "$PKG")"
  ok "已解压：$VERSION"
fi

# 校验暂存区内容完整
for d in "${CODE_DIRS[@]}"; do
  [ -d "$STAGE/$d" ] || die "代码缺少 $d/，来源可能不完整"
done
[ -f "$STAGE/bin/seed.php" ] && [ -f "$STAGE/public/admin.php" ] || die "代码缺少关键文件（seed.php / admin.php）"
ok "代码内容校验通过"

# ─────────────────── 3. 备份历史数据 ───────────────────
step "3/9 备份数据库与上传目录（安全网）"
BACKUP="/var/www/mabseek-backup-$(date '+%Y%m%d-%H%M%S')"
FIRST_DEPLOY=1
if [ -f "$DATA_DIR/mabseek.sqlite" ] || [ -d "$ROOT/$UPLOAD_REL" ]; then
  FIRST_DEPLOY=0
  run mkdir -p "$BACKUP"
  [ -d "$DATA_DIR" ]        && run cp -a "$DATA_DIR"        "$BACKUP/data"    || true
  [ -d "$ROOT/$UPLOAD_REL" ] && { mkdir -p "$BACKUP/uploads"; cp -a "$ROOT/$UPLOAD_REL/." "$BACKUP/uploads/" 2>/dev/null || true; }
  ok "已备份到 $BACKUP"
else
  warn "未发现历史数据，判定为首次部署（稍后将全新建库）"
fi

# 把上传图片暂存出来（旧 public 即将删除）
PRESERVE_UPLOADS="$(mktemp -d /tmp/mabseek-uploads.XXXXXX)"
if [ -d "$ROOT/$UPLOAD_REL" ]; then
  cp -a "$ROOT/$UPLOAD_REL/." "$PRESERVE_UPLOADS/" 2>/dev/null || true
  ok "已暂存上传图片 $(find "$PRESERVE_UPLOADS" -type f | wc -l | tr -d ' ') 个文件"
fi

# ─────────────────── 4. 铺开新代码（保留 data/ 与上传） ───────────────────
step "4/9 替换代码（从暂存区复制，保留 data/）"
run mkdir -p "$ROOT"
for d in "${CODE_DIRS[@]}"; do
  [ -e "$ROOT/$d" ] && { log "删除旧 $d/"; rm -rf "${ROOT:?}/$d"; }
done
for d in "${CODE_DIRS[@]}"; do
  run cp -a "$STAGE/$d" "$ROOT/$d"     # cp（非 mv）：暂存区/工作副本保持完整
done
ok "新代码已就位：${CODE_DIRS[*]}"

# 确保 data 与 uploads 目录存在，并恢复上传图片
run mkdir -p "$DATA_DIR" "$ROOT/$UPLOAD_REL"
if [ -n "$(ls -A "$PRESERVE_UPLOADS" 2>/dev/null || true)" ]; then
  cp -a "$PRESERVE_UPLOADS/." "$ROOT/$UPLOAD_REL/" 2>/dev/null || true
  ok "已恢复上传图片到 $UPLOAD_REL"
fi
cleanup_tmp                            # 删除离线临时解压区与上传暂存区（git 工作副本保留）
STAGE=""; CLEAN_STAGE=0; PRESERVE_UPLOADS=""

# ─────────────────── 5. 初始化 / 迁移数据库 ───────────────────
step "5/9 数据库 seed（幂等：建表+补缺，绝不删历史数据）"
# 先给 data 目录临时可写属主，保证 seed 能建库
run chown -R "$WEBUSER:$WEBUSER" "$DATA_DIR"
if sudo -u "$WEBUSER" php "$ROOT/bin/seed.php"; then
  ok "seed 完成"
else
  die "seed 执行失败，请检查上面的 PHP 报错"
fi
if [ "$FIRST_DEPLOY" -eq 1 ]; then
  warn "首次部署：管理员 admin / mabseek2026（首次登录强制改密）"
fi

# ─────────────────── 5b. 迁移历史图片路径为 .webp ───────────────────
# 仓库自带静态图已转 WebP 并删除原图；代码引用随代码同步，但保留下来的历史数据库
# 里仍存旧的 .png/.jpg 路径（如团队头像、教育回顾封面/正文），需就地迁移，否则 404。
# 脚本幂等：仅当对应 .webp 实际存在时才替换，且跳过 uploads/ 下的用户上传图。
step "5b/9 迁移数据库中的静态图片路径 .png/.jpg → .webp（幂等，保护用户上传图）"
if [ -f "$ROOT/bin/migrate-images-webp.php" ]; then
  if sudo -u "$WEBUSER" php "$ROOT/bin/migrate-images-webp.php"; then
    ok "图片路径迁移完成"
  else
    die "图片路径迁移失败，请检查上面的 PHP 报错"
  fi
else
  warn "未找到 bin/migrate-images-webp.php，跳过图片路径迁移（旧版代码可忽略）"
fi

# ─────────────────── 5c. 订正联系邮箱 ───────────────────
# 联系邮箱存于 snippets 表随页面渲染；保留下来的历史库里可能仍是旧邮箱，就地订正。
# 脚本幂等：仅替换仍含旧邮箱的记录，不动后台改过的其它文案，无旧值时 0 改动。
step "5c/9 订正数据库中的联系邮箱（幂等，仅替换旧邮箱）"
if [ -f "$ROOT/bin/migrate-contact-email.php" ]; then
  if sudo -u "$WEBUSER" php "$ROOT/bin/migrate-contact-email.php"; then
    ok "联系邮箱订正完成"
  else
    die "联系邮箱订正失败，请检查上面的 PHP 报错"
  fi
else
  warn "未找到 bin/migrate-contact-email.php，跳过邮箱订正（旧版代码可忽略）"
fi

# ─────────────────── 6. 刷新权限 ───────────────────
step "6/9 刷新权限（源码只读，data 与上传目录可写）"
run chown -R root:root "$ROOT"
run find "$ROOT" -type d -exec chmod 755 {} +
run find "$ROOT" -type f -exec chmod 644 {} +
# 可写目录交给 web 用户
run chown -R "$WEBUSER:$WEBUSER" "$DATA_DIR" "$ROOT/$UPLOAD_REL"
run chmod 750 "$DATA_DIR" "$ROOT/$UPLOAD_REL"
[ -f "$DATA_DIR/mabseek.sqlite" ] && run chmod 640 "$DATA_DIR/mabseek.sqlite" || true
ok "权限刷新完成"

# ─────────────────── 7. 重载服务（清 opcache）───────────────────
step "7/9 重载 php-fpm / nginx"
if [ -n "$FPM_SVC" ]; then
  run systemctl reload "$FPM_SVC" || run systemctl restart "$FPM_SVC"
  ok "已重载 $FPM_SVC（清除 opcache，新代码立即生效）"
else
  warn "跳过 php-fpm 重载：若启用了 opcache，可能需手动 systemctl restart php*-fpm"
fi
if nginx -t 2>/dev/null; then
  run systemctl reload nginx
  ok "nginx 配置有效并已重载"
else
  warn "nginx -t 未通过或站点未配置。若首次部署，请先安装站点配置："
  warn "  cp $ROOT/deploy/nginx-var-www-html-http.conf.sample /etc/nginx/sites-available/mabseek"
  warn "  （把 socket 改成 ${FPM_SOCK:-/run/php/phpX.Y-fpm.sock}）"
  warn "  ln -sf /etc/nginx/sites-available/mabseek /etc/nginx/sites-enabled/mabseek && rm -f /etc/nginx/sites-enabled/default"
  warn "  nginx -t && systemctl reload nginx"
fi

# ─────────────────── 8. 自检 ───────────────────
step "9/9 本机自检"
if command -v curl >/dev/null 2>&1; then
  CODE_HOME="$(curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1/ || echo 000)"
  CODE_ADMIN="$(curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1/admin.php || echo 000)"
  CODE_DB="$(curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1/data/mabseek.sqlite || echo 000)"
  log "GET /            → $CODE_HOME"
  log "GET /admin.php   → $CODE_ADMIN"
  log "GET /data/*.sqlite → $CODE_DB（应为 404，代表库不可 URL 访问）"
  [ "$CODE_ADMIN" = "200" ] && ok "后台可访问" || warn "后台返回 $CODE_ADMIN，请检查 nginx 站点配置"
  [ "$CODE_DB" = "404" ]    && ok "数据库不可 URL 访问（安全）" || warn "数据库返回 $CODE_DB，请确认 docroot 指向 $ROOT/$DOCROOT_NAME"
else
  warn "未装 curl，跳过 HTTP 自检"
fi

printf '\n%s════════════════════════════════════════════════%s\n' "$CG" "$C0"
printf '%s  部署完成%s  开始 %s  结束 %s\n' "$CG" "$C0" "$BANNER_START" "$(date '+%Y-%m-%d %H:%M:%S')"
printf '  代码版本：%s\n' "$VERSION"
[ "$FIRST_DEPLOY" -eq 0 ] && printf '  数据已保留；本次备份：%s\n' "$BACKUP"
printf '  浏览器访问：http://<公网IP>/admin.php\n'
printf '%s════════════════════════════════════════════════%s\n' "$CG" "$C0"
