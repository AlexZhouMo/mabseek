# MabSeek 项目梳理与轻度重构 实施计划

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 删除已核实的无用文件/配置、修正过时文档引用、硬化 `.gitignore`，并在可证前提下清理死代码，全程零运行时行为变更。

**Architecture:** 纯清理型改动，按「删除类别」分组小步提交；每步以运行既有测试套件 `php tests/run.php`（全绿）+ 针对性 grep 作为回归验证。不重组目录、不改任何运行时 PHP 逻辑与 DB schema。

**Tech Stack:** PHP 8 + pdo_sqlite（零外部依赖）；bash / git；测试为 `tests/run.php` 自研零依赖断言。

> 工作目录约定：除非特别说明，所有相对路径都相对 `mabseek/`（即 `/Users/zhoumo/Documents/Claude/mabseek`）。`git` 命令在仓库根 `/Users/zhoumo/Documents/Claude` 执行，路径带 `mabseek/` 前缀。设计依据见 `docs/superpowers/specs/2026-08-30-project-cleanup-design.md`。

---

### Task 1: 基线 — 确认测试套件全绿

**Files:**
- Test: `mabseek/tests/run.php`（只读运行，不修改）

- [ ] **Step 1: 运行测试套件记录基线**

Run（在 `mabseek/` 目录）:
```bash
cd /Users/zhoumo/Documents/Claude/mabseek && php tests/run.php
```
Expected: 全部断言通过，退出码 0（输出以「全部通过」或等价 OK 结尾）。若非全绿，**停止并上报**——基线不绿则无法判断后续删除是否引入回归。

- [ ] **Step 2: 记录基线通过数**

把 Step 1 输出的用例/断言数量记下，作为后续每次回归的对照值。本任务无提交。

---

### Task 2: 清除构建产物与系统垃圾（untracked）

**Files:**
- Delete: `mabseek/deploy/mabseek-deploy.tgz`
- Delete: `.DS_Store`、`mabseek/.DS_Store`、`mabseek/docs/.DS_Store`、`mabseek/public/.DS_Store`
- Delete: `mabseek/.superpowers/`（整目录，8 个文件，含 `server.pid`/`server.log`）

- [ ] **Step 1: 确认这些都是 untracked（不会误删版本内容）**

Run（仓库根）:
```bash
cd /Users/zhoumo/Documents/Claude && git status --short -- mabseek/deploy/mabseek-deploy.tgz mabseek/.superpowers .DS_Store mabseek/.DS_Store mabseek/docs/.DS_Store mabseek/public/.DS_Store
```
Expected: 每行都以 `??` 开头（untracked）。若有任何一行是 tracked（`M`/空格开头），停止并上报。

- [ ] **Step 2: 删除**

Run（仓库根）:
```bash
cd /Users/zhoumo/Documents/Claude && \
rm -f mabseek/deploy/mabseek-deploy.tgz \
      .DS_Store mabseek/.DS_Store mabseek/docs/.DS_Store mabseek/public/.DS_Store && \
rm -rf mabseek/.superpowers
```
Expected: 无输出（成功）。

- [ ] **Step 3: 回归测试**

Run:
```bash
cd /Users/zhoumo/Documents/Claude/mabseek && php tests/run.php
```
Expected: 与 Task 1 基线一致，全绿。

- [ ] **Step 4: 说明（无需提交）**

本任务删除的全是 untracked 文件，不产生 git 提交。它们不再出现在 `git status` 中即完成。`.gitignore` 硬化在 Task 6 处理，避免再次误入库。

---

### Task 3: 收尾仓库根无关实验遗留（tracked-deleted）

**Files:**
- Delete (git): `invoke_llm_spark.py`
- Delete (git): `tests/test_invoke_llm.py`

> 背景：二者是与 mabseek 无关的旧实验，磁盘上已删除，但 git 仍记录为「已删除待提交」。此处正式提交这两处删除。注意路径是**仓库根**下的 `tests/`，不是 `mabseek/tests/`。

- [ ] **Step 1: 确认状态为已删除（` D`）**

Run（仓库根）:
```bash
cd /Users/zhoumo/Documents/Claude && git status --short -- invoke_llm_spark.py tests/test_invoke_llm.py
```
Expected: 两行均为 ` D`（tracked、磁盘已删、待提交）。

- [ ] **Step 2: 暂存删除**

Run（仓库根）:
```bash
cd /Users/zhoumo/Documents/Claude && git rm --cached --quiet invoke_llm_spark.py tests/test_invoke_llm.py 2>/dev/null; git add -A invoke_llm_spark.py tests/test_invoke_llm.py
```
Expected: 无报错。（文件已不在磁盘，`git add -A <path>` 会把删除纳入暂存。）

- [ ] **Step 3: 提交**

Run（仓库根）:
```bash
cd /Users/zhoumo/Documents/Claude && git commit -q -m "chore: remove unrelated invoke_llm experiment leftovers

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>" && git log --oneline -1
```
Expected: 打印新提交行。

---

### Task 4: 退役 HTTPS 版 nginx 样例 + 修正过时引用

**Files:**
- Delete (git): `mabseek/deploy/nginx.conf.sample`
- Modify: `mabseek/docs/architecture.md`（约 211、220 行）
- Modify: `mabseek/deploy/DEPLOY-ubuntu-http.md`（约 170 行）

> 现实：线上运行的是 HTTP 样例 `deploy/nginx-var-www-html-http.conf.sample`；HTTPS/443 版 `nginx.conf.sample` 已从磁盘删除。此处正式退役它，并把文档引用改指现存 HTTP 样例。历史归档（P5 plan/spec）**不改**。

- [ ] **Step 1: 确认 HTTPS 样例为 tracked-deleted，且 HTTP 样例存在**

Run:
```bash
cd /Users/zhoumo/Documents/Claude && git status --short -- mabseek/deploy/nginx.conf.sample && ls -1 mabseek/deploy/nginx-var-www-html-http.conf.sample
```
Expected: 第一行 ` D mabseek/deploy/nginx.conf.sample`；第二行列出 HTTP 样例文件名（存在）。

- [ ] **Step 2: 暂存 HTTPS 样例的删除**

Run:
```bash
cd /Users/zhoumo/Documents/Claude && git add -A mabseek/deploy/nginx.conf.sample
```
Expected: 无报错。

- [ ] **Step 3: 改 `architecture.md` 目录树那行**

把（约 211 行）：
```
└── deploy/nginx.conf.sample ← 站点样例（整洁 URL · 301 · 上传禁 PHP · 安全头）
```
改为：
```
└── deploy/nginx-var-www-html-http.conf.sample ← 站点样例（HTTP · .html→.php 301 · 上传禁 PHP · 安全头）
```

- [ ] **Step 4: 改 `architecture.md` 正文引用那行**

把（约 220 行）：
```
- 整洁 URL 与旧 `*.html → *.php` 的 301 由 nginx 承担（见 `deploy/nginx.conf.sample`）。
```
改为：
```
- 旧 `*.html → *.php` 的 301 由 nginx 承担（见 `deploy/nginx-var-www-html-http.conf.sample`）。
```
（去掉「整洁 URL 与」——HTTP 样例采用 `try_files … =404`，不做无扩展名整洁 URL，措辞与实际一致。）

- [ ] **Step 5: 改 `DEPLOY-ubuntu-http.md` 证书章节那行**

把（约 170 行）：
```
1. 证书就绪后,参考 `deploy/nginx.conf.sample`(内含 443 + 80→301 跳转 + 安全头)。
```
改为：
```
1. 证书就绪后,在现有 HTTP 样例 `deploy/nginx-var-www-html-http.conf.sample` 基础上增加 443 server 块(443 + 80→301 跳转 + 安全头)。
```

- [ ] **Step 6: 确认无残留指向已删文件的引用（历史归档除外）**

Run:
```bash
cd /Users/zhoumo/Documents/Claude/mabseek && grep -rn "nginx.conf.sample" docs/architecture.md deploy/DEPLOY-ubuntu-http.md
```
Expected: 无输出（这两份现行文档已不再引用旧文件名）。P5 plan/spec 里仍有引用属历史归档，不在本次检查范围。

- [ ] **Step 7: 提交**

Run:
```bash
cd /Users/zhoumo/Documents/Claude && git add mabseek/docs/architecture.md mabseek/deploy/DEPLOY-ubuntu-http.md && git commit -q -m "docs(mabseek): retire HTTPS nginx sample, point docs to HTTP sample

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>" && git log --oneline -1
```
Expected: 打印新提交行。

---

### Task 5: 删除孤儿图片 + 清除其文档引用

**Files:**
- Delete (git): `mabseek/public/assets/images/case-study.png`
- Delete (git): `mabseek/public/assets/images/hero-antibody.png`
- Modify: `mabseek/docs/superpowers/specs/2026-08-28-mabseek-p1-homepage-design.md`（约 72 行）

- [ ] **Step 1: 删除前二次证明 0 引用**

Run:
```bash
cd /Users/zhoumo/Documents/Claude/mabseek && \
echo "case-study:" && grep -rn "case-study" app public bin tests docs; \
echo "hero-antibody:" && grep -rn "hero-antibody" app public bin tests docs
```
Expected: `case-study` 无任何输出；`hero-antibody` 仅命中 `docs/superpowers/specs/2026-08-28-mabseek-p1-homepage-design.md:72`（下一步会清除）。若出现其它命中，停止并上报。

- [ ] **Step 2: 清除 p1 spec 里的 hero-antibody 引用**

把（约 72 行）：
```
- 无外部资源、无重依赖；`prefers-reduced-motion: reduce` 时降级为静态图（可复用 `assets/images/hero-antibody.png`）。
```
改为：
```
- 无外部资源、无重依赖；`prefers-reduced-motion: reduce` 时降级为静态首帧。
```

- [ ] **Step 3: git rm 两张图片**

Run:
```bash
cd /Users/zhoumo/Documents/Claude && git rm --quiet mabseek/public/assets/images/case-study.png mabseek/public/assets/images/hero-antibody.png
```
Expected: 打印两行 `rm '...'`。

- [ ] **Step 4: 全仓复核已无引用**

Run:
```bash
cd /Users/zhoumo/Documents/Claude/mabseek && grep -rn -e "case-study" -e "hero-antibody" app public bin tests docs; echo "exit=$?"
```
Expected: 无匹配行，`exit=1`（grep 无命中）。

- [ ] **Step 5: 回归测试**

Run:
```bash
cd /Users/zhoumo/Documents/Claude/mabseek && php tests/run.php
```
Expected: 与基线一致，全绿。

- [ ] **Step 6: 提交**

Run:
```bash
cd /Users/zhoumo/Documents/Claude && git add mabseek/docs/superpowers/specs/2026-08-28-mabseek-p1-homepage-design.md && git commit -q -m "chore(mabseek): remove orphan images case-study & hero-antibody

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>" && git log --oneline -1
```
Expected: 打印新提交行（图片删除已在 Step 3 暂存，随此提交一并计入）。

---

### Task 6: 硬化 `.gitignore`

**Files:**
- Modify: `mabseek/.gitignore`

- [ ] **Step 1: 在文件末尾追加三条规则**

在现有内容之后追加（保留原有 5 行不变）：
```
.DS_Store
deploy/*.tgz
.superpowers/
```

追加后 `mabseek/.gitignore` 完整内容应为：
```
data/*.sqlite
data/*.sqlite-*
public/assets/images/uploads/*
!public/assets/images/uploads/.gitkeep
!data/.gitkeep
.DS_Store
deploy/*.tgz
.superpowers/
```

- [ ] **Step 2: 验证忽略生效**

Run:
```bash
cd /Users/zhoumo/Documents/Claude/mabseek && git check-ignore -v deploy/mabseek-deploy.tgz .superpowers/x .DS_Store
```
Expected: 三行都命中，来源为 `mabseek/.gitignore`（对应 `deploy/*.tgz`、`.superpowers/`、`.DS_Store`）。

- [ ] **Step 3: 提交**

Run:
```bash
cd /Users/zhoumo/Documents/Claude && git add mabseek/.gitignore && git commit -q -m "chore(mabseek): gitignore DS_Store, build tarball, .superpowers

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>" && git log --oneline -1
```
Expected: 打印新提交行。

---

### Task 7: 精简 `.claude/launch.json`（去掉暴露全目录的 python 配置）

**Files:**
- Modify: `mabseek/.claude/launch.json`

> 现有两条配置：一条 `python -m http.server 8777 --directory <整个 mabseek>`（会把 `app/`、`data/` 一并暴露，脚枪），一条 `php -S localhost:8778 -t public`（正确的本地预览）。删掉前者，只留后者。

- [ ] **Step 1: 用只保留 php 配置的内容覆写文件**

`mabseek/.claude/launch.json` 改为：
```json
{
  "version": "0.0.1",
  "configurations": [
    { "name": "mabseek-php", "runtimeExecutable": "php", "runtimeArgs": ["-S", "localhost:8778", "-t", "public"], "port": 8778 }
  ]
}
```

- [ ] **Step 2: 校验 JSON 合法**

Run:
```bash
cd /Users/zhoumo/Documents/Claude/mabseek && php -r 'json_decode(file_get_contents(".claude/launch.json"), false, 512, JSON_THROW_ON_ERROR); echo "JSON OK\n";'
```
Expected: 输出 `JSON OK`。

- [ ] **Step 3: 提交**

Run:
```bash
cd /Users/zhoumo/Documents/Claude && git add mabseek/.claude/launch.json && git commit -q -m "chore(mabseek): drop launch config that exposed whole project dir

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>" && git log --oneline -1
```
Expected: 打印新提交行。

---

### Task 8: 纳入本会话新建的部署资产与 PRD 文档

**Files:**
- Add (git): `mabseek/deploy/pack-mac.sh`
- Add (git): `mabseek/deploy/deploy-mabseek.sh`
- Add (git): `mabseek/deploy/DEPLOY-ubuntu-http.md`
- Add (git): `mabseek/deploy/nginx-var-www-html-http.conf.sample`
- Add (git): `mabseek/docs/superpowers/plans/2026-08-29-mabseek-p5-backend-cms.md`
- Add (git): `mabseek/docs/PRD-20260808.docx`、`mabseek/docs/PRD-20260825.docx`

> 注：`DEPLOY-ubuntu-http.md` 已在 Task 4 被编辑，但那次 `git add` 只针对已修改的 tracked 文件；若它当时仍是 untracked，Task 4 的 `git add` 已把它纳入。此处再次 `git add` 幂等无害。两个 `.sh` 需可执行位。

- [ ] **Step 1: 确认可执行脚本带执行位**

Run:
```bash
cd /Users/zhoumo/Documents/Claude/mabseek && ls -l deploy/pack-mac.sh deploy/deploy-mabseek.sh | awk '{print $1, $NF}'
```
Expected: 两个文件权限含 `x`（如 `-rwxr-xr-x`）。若缺失，运行 `chmod +x deploy/pack-mac.sh deploy/deploy-mabseek.sh`。

- [ ] **Step 2: 暂存新资产**

Run:
```bash
cd /Users/zhoumo/Documents/Claude && git add \
  mabseek/deploy/pack-mac.sh \
  mabseek/deploy/deploy-mabseek.sh \
  mabseek/deploy/DEPLOY-ubuntu-http.md \
  mabseek/deploy/nginx-var-www-html-http.conf.sample \
  mabseek/docs/superpowers/plans/2026-08-29-mabseek-p5-backend-cms.md \
  mabseek/docs/PRD-20260808.docx \
  mabseek/docs/PRD-20260825.docx
```
Expected: 无报错。

- [ ] **Step 3: 确认脚本执行位已被 git 记录**

Run:
```bash
cd /Users/zhoumo/Documents/Claude && git ls-files -s mabseek/deploy/pack-mac.sh mabseek/deploy/deploy-mabseek.sh
```
Expected: 两行模式均为 `100755`（可执行）。若为 `100644`，运行 `git update-index --chmod=+x mabseek/deploy/pack-mac.sh mabseek/deploy/deploy-mabseek.sh`。

- [ ] **Step 4: 提交**

Run:
```bash
cd /Users/zhoumo/Documents/Claude && git commit -q -m "chore(mabseek): track deploy scripts, HTTP nginx sample, PRD & P5 plan

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>" && git log --oneline -1
```
Expected: 打印新提交行。

---

### Task 9: 可证死代码复核（正确方法）

**Files:**
- 只读扫描 `mabseek/app/`；如确认死代码再 Modify 对应文件。

> 初次快扫方法有误（把 `e()` 误判未用）。此处用可靠方法：对每个函数，统计**非定义处**的调用次数。

- [ ] **Step 1: 逐符号统计调用（排除定义行）**

Run:
```bash
cd /Users/zhoumo/Documents/Claude/mabseek && \
for fn in $(grep -rhoE 'function[[:space:]]+[a-zA-Z_][a-zA-Z0-9_]*' app --include='*.php' | sed -E 's/function[[:space:]]+//' | sort -u); do \
  uses=$(grep -rnE "[^a-zA-Z0-9_>]${fn}[[:space:]]*\(" app public bin tests --include='*.php' | grep -vE "function[[:space:]]+${fn}\b" | wc -l | tr -d ' '); \
  printf '%-32s call-sites=%s\n' "$fn" "$uses"; \
done | sort -t= -k2 -n
```
Expected: 每个函数打印其真实调用点数（已排除定义行）。`call-sites=0` 的才是候选死代码。

- [ ] **Step 2: 对每个 `call-sites=0` 候选逐一核实**

对每个候选，人工确认它确实未通过任何间接方式（可变函数名、字符串回调、模板 include）被使用：
```bash
# 把 <fn> 换成候选名，全仓（含 docs 示例）搜一遍
grep -rn "<fn>" app public bin tests
```
Expected：只有定义处出现。仅当**确证**从未被调用时，才在下一步删除；任何不确定一律**保留**并在提交说明里记录「已核查、保留」。

- [ ] **Step 3: 删除已确证的死代码（若有）并回归**

若 Step 2 确证存在死函数：删除其定义，然后
```bash
cd /Users/zhoumo/Documents/Claude/mabseek && php tests/run.php
```
Expected: 全绿。若测试因删除而失败，说明该函数实际被用到，`git checkout` 还原并保留。

- [ ] **Step 4: 提交（仅当确有删除）**

Run（仅当 Step 3 有实际删除）:
```bash
cd /Users/zhoumo/Documents/Claude && git add -A mabseek/app && git commit -q -m "refactor(mabseek): remove verified dead code

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>" && git log --oneline -1
```
Expected: 打印新提交行。**若无确证死代码，本任务不提交**，在最终报告中注明「已复核，无死代码」。

---

### Task 10: 终检与收尾

**Files:** 无（只读校验）

- [ ] **Step 1: 工作区干净、无悬挂 tracked-deleted**

Run:
```bash
cd /Users/zhoumo/Documents/Claude && git status --short -- mabseek invoke_llm_spark.py tests/test_invoke_llm.py
```
Expected: 与 mabseek 相关无 ` D`（tracked-deleted）悬挂项；本次改动均已提交。剩余的 `?? DACon/ QECon/ umc-os/ .claude/commands/ .claude/skills/` 属范围外，忽略。

- [ ] **Step 2: 无文档指向磁盘不存在的文件（现行文档）**

Run:
```bash
cd /Users/zhoumo/Documents/Claude/mabseek && \
grep -rn "nginx.conf.sample" docs/architecture.md deploy/*.md; \
grep -rn -e "case-study" -e "hero-antibody" app public bin tests docs/architecture.md docs/ui-style-guide.md deploy; echo "checks done"
```
Expected: 无命中，最后打印 `checks done`。

- [ ] **Step 3: 最终回归测试**

Run:
```bash
cd /Users/zhoumo/Documents/Claude/mabseek && php tests/run.php
```
Expected: 与 Task 1 基线一致，全绿。

- [ ] **Step 4: 汇报**

汇总：各任务提交哈希、删除文件清单、Task 9 死代码复核结论（删除了什么或「无」）、最终测试结果。确认无任何运行时 PHP 逻辑 / DB schema 改动。

---

## Self-Review

- **Spec coverage**：设计文档第 1 节（删除）→ Task 2/3/4/5；第 2 节（编辑）→ Task 4（doc）/6（gitignore）/7（launch.json）；第 3 节（纳入版本管理）→ Task 8；第 4 节（死代码）→ Task 9；第 6 节（验证策略：基线+回归+删前grep+分组提交）→ Task 1 及各任务的回归步骤 + Task 10 终检。全部覆盖。
- **Placeholder scan**：无 TBD/TODO；所有编辑给出精确 old→new 文本；所有命令给出预期输出。Task 9 的删除量取决于核实结果，已明确「仅删可证、否则保留并注明」，非占位。
- **Type consistency**：无新增类型/函数；文件名统一用真实全名 `nginx-var-www-html-http.conf.sample`；路径统一标注仓库根 vs `mabseek/` 前缀。
