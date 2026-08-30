# MabSeek 项目梳理与轻度重构 — 设计文档

**日期**：2026-08-30
**类型**：清理 + 轻度代码整理（不重组目录、零运行时行为变更）

## 目标

在**不影响现有线上功能**的前提下，删除已核实的无用文件/配置、修正过时的文档引用、硬化 `.gitignore`，并在可证明的前提下清理死代码。

## 边界

- **在范围内**：`mabseek/` 项目目录，以及 git 仓库根下与 mabseek 无关的实验遗留（`invoke_llm_spark.py`、`tests/test_invoke_llm.py`）。
- **不在范围内**：兄弟项目 `DACon/`、`QECon/`、`umc-os/`；仓库根 `.claude/commands/`、`.claude/skills/`；所有运行时 PHP 逻辑与数据库 schema；在用的图片与 JS。

> 说明：git 仓库根是 `/Users/zhoumo/Documents/Claude`（混合多项目仓库），并非 mabseek 本身。所有 `git` 操作只针对 mabseek 与上述两个仓库根遗留文件，不触碰兄弟项目。

## 决策记录

| 维度 | 选择 |
|---|---|
| 改动深度 | 清理 + 轻度代码整理（不重组目录） |
| 清理边界 | mabseek + 仓库根遗留 |
| 清理力度 | 方案② 标准 |
| 已删 HTTPS 版 `nginx.conf.sample` | 退役：正式 `git rm`，新 HTTP 样例定为唯一权威配置 |
| PRD docx（~11M） | 保留，并纳入版本管理 |

## 1. 删除（Delete）

| 目标 | 依据 | 方式 |
|---|---|---|
| `deploy/mabseek-deploy.tgz` | 14M 构建产物，可由 `pack-mac.sh` 再生 | `rm`（untracked） |
| `.DS_Store` ×4（仓库根、`mabseek/`、`docs/`、`public/`） | macOS 系统垃圾 | `rm` |
| `.superpowers/brainstorm/…`（8 个文件，含 `server.pid`/`server.log`） | 上次 brainstorm 的临时会话状态 | `rm -rf mabseek/.superpowers/`（untracked） |
| `invoke_llm_spark.py` | 无关实验，磁盘已删（git tracked-deleted） | `git rm` 收尾 |
| `tests/test_invoke_llm.py` | 同上 | `git rm` 收尾 |
| `deploy/nginx.conf.sample`（HTTPS/443 版） | tracked 但磁盘已删；退役 | `git rm` |
| `public/assets/images/case-study.png` | 全项目 **0 引用**（已核实） | `git rm` |
| `public/assets/images/hero-antibody.png` | 仅在旧 P1 设计文档作为「可复用降级图」被提及，`index.php` 从未真正使用（运行时孤儿） | `git rm`，并删除 spec 中那一句引用 |

## 2. 编辑（Modify，不动运行时逻辑）

### 2.1 `mabseek/.gitignore`
在现有基础上追加：
```
.DS_Store
deploy/*.tgz
.superpowers/
```
（现有规则 `data/*.sqlite`、`data/*.sqlite-*`、`public/assets/images/uploads/*` 等保持不变。）

### 2.2 `mabseek/.claude/launch.json`
删除把**整个 mabseek 目录**（含 `app/`、`data/`）暴露在端口 8777 的 `python -m http.server` 配置项；只保留正确的 `php -S localhost:8778 -t public` 配置。

### 2.3 过时引用修正
把指向已删 HTTPS `deploy/nginx.conf.sample` 的引用改指向新的 HTTP 样例 `deploy/nginx-var-www-http.conf.sample`，并补「HTTPS 待证书就绪再启用」说明：
- `docs/architecture.md`（约 211、220 行的目录树与说明）
- `deploy/DEPLOY-ubuntu-http.md`（约 170 行的证书章节）

**不改**：`docs/superpowers/plans/2026-08-29-mabseek-p5-backend-cms.md`、`docs/superpowers/specs/2026-08-29-mabseek-p5-backend-cms-design.md` —— 属历史归档快照，保留当时状态。

## 3. 纳入版本管理（Add，不是删）

本会话新建、目前 untracked、应纳入版本管理：
- `deploy/pack-mac.sh`
- `deploy/deploy-mabseek.sh`
- `deploy/DEPLOY-ubuntu-http.md`
- `deploy/nginx-var-www-http.conf.sample`
- `docs/superpowers/plans/2026-08-29-mabseek-p5-backend-cms.md`
- `docs/PRD-20260808.docx`、`docs/PRD-20260825.docx`

## 4. 轻度代码整理（Verified）

- 用正确方法复核 `app/` 死代码：**逐符号**统计，区分「函数定义」与「调用点」（初次快速扫描把 `e()`（120 处调用）误报为未使用，方法不可靠，其结论作废）。
- **只删可证明从未被调用的符号**，绝不凭猜测删除。
- 现有证据下预期删除量为零或极小；若复核发现确凿死代码，逐个记录后再删。

## 5. 不碰（Out of Scope）

`DACon/`、`QECon/`、`umc-os/`、`.claude/commands/`、`.claude/skills/`、所有运行时 PHP 逻辑与 DB schema、其余 9 张在用图片（`agent-hero`、`antibody-structure`、`education-banner`、`forum-adc`、`forum-bioinfo`、`forum-protocol`、`international`、`lab-scene`）、所有在用 JS（`agent-cases`、`agent-demo`、`hero-anim`、`knowledge-graph`、`main`）。

## 6. 「不影响功能」验证策略

1. **基线**：改动前运行 `php tests/run.php`，记录全绿基线。
2. **回归**：每类删除/编辑后重新运行测试套件，确认仍全绿。
3. **删图前二次证明**：删除任一图片前用 `grep -rn '<basename>' app public bin tests docs` 再次确认 0 引用。
4. **分组小步提交**，便于精确回滚：
   - cruft（tgz / .DS_Store / .superpowers）
   - 仓库根遗留（invoke_llm_spark.py / tests/test_invoke_llm.py）
   - 退役 HTTPS nginx 样例 + 文档引用修正
   - 孤儿图片（case-study / hero-antibody + spec 引用）
   - `.gitignore` 硬化
   - `launch.json` 精简
   - 纳入新部署资产
   - （如有）死代码删除
5. **线上不受影响**：这些改动不进入部署包运行时路径，线上独立部署站点不受影响。

## 成功标准

- `php tests/run.php` 在全部改动后依然全绿。
- 被删图片确认 0 引用；页面无破图。
- 文档不再引用任何磁盘上不存在的文件。
- `git status` 干净、无 tracked-but-deleted 悬挂项；`.gitignore` 覆盖 macOS/构建/会话垃圾。
- 无任何运行时 PHP 逻辑或 DB schema 变更。
