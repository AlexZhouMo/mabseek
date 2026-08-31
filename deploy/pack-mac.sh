#!/usr/bin/env bash
#
# pack-mac.sh —— 在 macOS 本地把 MabSeek 打包成可上传的部署包
#
# 用法：
#   cd /Users/zhoumo/Documents/Claude/mabseek
#   ./deploy/pack-mac.sh                 # 输出到 deploy/mabseek-deploy.tgz
#   ./deploy/pack-mac.sh ~/Desktop/x.tgz # 自定义输出路径
#
# 打包内容：app bin public deploy（含本目录下的部署脚本）
# 刻意排除：开发用的 sqlite、.git、node_modules、系统垃圾文件
#           —— 生产库由服务器端 seed 幂等生成/保留，不随包覆盖。

set -euo pipefail

# ---- 彩色日志 ----
if [ -t 1 ]; then C1=$'\033[1;36m'; C2=$'\033[1;32m'; CW=$'\033[1;33m'; C0=$'\033[0m'; else C1=; C2=; CW=; C0=; fi
log()  { printf '%s[pack]%s %s\n'  "$C1" "$C0" "$*"; }
ok()   { printf '%s[ ok ]%s %s\n'  "$C2" "$C0" "$*"; }
die()  { printf '%s[fail]%s %s\n'  "$CW" "$C0" "$*" >&2; exit 1; }

# ---- 定位项目根（脚本在 <root>/deploy/ 下）----
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
OUT="${1:-$SCRIPT_DIR/mabseek-deploy.tgz}"

log "项目根：$ROOT"
log "输出包：$OUT"

# ---- 校验目录结构 ----
for d in app bin public deploy; do
  [ -d "$ROOT/$d" ] || die "缺少目录 $d/，请在 mabseek 项目根运行本脚本"
done
[ -f "$ROOT/bin/seed.php" ]        || die "缺少 bin/seed.php"
[ -f "$ROOT/public/admin.php" ]    || die "缺少 public/admin.php"
ok "目录结构校验通过"

# ---- 打包（COPYFILE_DISABLE 避免 macOS 的 ._ 资源叉文件）----
log "开始打包……"
COPYFILE_DISABLE=1 tar \
  --exclude='.DS_Store' \
  --exclude='.git' \
  --exclude='node_modules' \
  --exclude='*.sqlite' \
  --exclude='*.sqlite-wal' \
  --exclude='*.sqlite-shm' \
  --exclude='deploy/*.tgz' \
  -czf "$OUT" \
  -C "$ROOT" \
  app bin public deploy

SIZE="?"
[ -f "$OUT" ] && SIZE="$(du -h "$OUT" 2>/dev/null | awk 'NR==1{print $1}')"
[ -n "$SIZE" ] || SIZE="?"
ok "打包完成: $OUT (${SIZE})"

echo
log "下一步（离线部署）：把包和部署脚本一起上传到服务器的家目录"
printf '    %sscp %s deploy/deploy-mabseek.sh <user>@<公网IP>:~/%s\n' "$C2" "$OUT" "$C0"
log "然后在服务器上执行（离线模式：追加包文件名，与脚本同在 ~）："
printf '    %ssudo bash ~/deploy-mabseek.sh mabseek-deploy.tgz%s\n' "$C2" "$C0"
log "（若服务器可联网，也可不打包，直接 sudo bash ~/deploy-mabseek.sh 走 git 在线部署）"
