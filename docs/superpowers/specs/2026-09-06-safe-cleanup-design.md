# 设计文档：安全清理冗余项（不影响功能）

- 日期：2026-09-06
- 状态：已批准，待实现

## 目标

删除冗余、无效、备份的文件/图片/代码/DB 记录，绝不影响现有功能。基于只读盘点（引用证据齐全）。四类全清。

## 第1类：纯垃圾文件（零引用零风险）

- `public/assets/images/_backup/`（9 图，已 gitignore、未跟踪）
- `public/assets/images/_backup2/`（6 图，未跟踪）
- 6 个 `.DS_Store`（已 gitignore、未跟踪）
- `public/assets/images/uploads/d9da9677b8504a4c7ee92ec83ecb59c0.png`（上传测试残留，live DB 所有图片列无引用，uploads/* 已 gitignore）
- `public/assets/js/knowledge-graph.js`（全站无 `<script>` 引用，知识图谱板块已下线）
- education.php 死 CSS（第 75-82 行区：`.kg-layout`/`#kg`/`#kg-panel`/`.layer-list`/`.layer`/`.layer .num`/`.layer h4`/`.layer p` + 注释 `/* 知识图谱布局 */`；body 无对应 DOM）

## 第2类：孤儿 snippet（seed 代码 + DB 行同删）

前台从不渲染的 snippet（已下线板块文案草稿），共约 49 个 key：
- `home.newscard.1~4.*`（16 个，seed.php 113-129 行，含注释，是 home 组末尾可整段删）
- `edu.kg.*`/`edu.map.*`/`edu.res.*`/`edu.link.*`（13 个，seed.php 233-245，夹在 edu.video 与 edu.lecture 之间可整段删）
- `forum.hot.*`/`forum.feed.*`/`forum.follow.*`/`forum.cold.*`（seed.php 266-287 区间）

**关键风险**：forum 区间**夹杂需保留的 `forum.lines.*` 和 `forum.search.chip3`**（273-274 行附近），**不可按行号整段删**。

**做法（安全）**：按 key 精确删除——
1. 定义孤儿 key 前缀集合：`home.newscard.`、`edu.kg.`、`edu.map.`、`edu.res.`、`edu.link.`、`forum.hot.`、`forum.feed.`、`forum.follow.`、`forum.cold.`。
2. 从 seed.php 删除所有以这些前缀开头的 snippet 行（逐行匹配删除，保留 forum.lines/forum.search 等）。
3. 从 live DB 删除对应 snippet 行（`DELETE FROM snippets WHERE k LIKE 'home.newscard.%' OR ...`）。
4. seed 幂等：两处都删才彻底（否则重跑 seed 回插）。

## 第3类：nl2br_e 函数

- 删 `app/helpers.php:7` 的 `nl2br_e()` 函数（生产零调用）。
- 同步删 `tests/test_helpers.php:5` 的测试行。

## 第4类：docs 架构图

- 删 `docs/panorama-arch.png`、`docs/panorama-uml.png`（未跟踪、零引用草稿）。

## 明确不动（活字段/有风险，盘点确认）

- `news.image`、`partners.logo_image`、`partners.sub`（seed 为空但 admin+前台在用）。
- 老库 `team_members.avatar_variant`（当前 DB 已无此列；删迁移风险>收益）。
- thread-new/edit 相似验证块（新建vs编辑语义差异，合并易回归）。
- `docs/PRD-*.docx`（git 已标记删除，随本次提交落地）。
- content_cards 8 个 grp、edu_reviews 3 条（全部前台渲染）。

## 流程与验证

1. 删文件（1、4类）+ 改 education.php（删死CSS）。
2. 改 helpers.php + test_helpers.php（3类）。
3. 改 seed.php（2类：按 key 前缀删孤儿行，保留 forum.lines/search）。
4. 清 live DB 孤儿 snippet 行（DELETE ... WHERE k LIKE 各前缀）。
5. **跑 tests/run.php 确保全绿**（重点：test_helpers 去 nl2br_e 行后仍通过；test_db 的 forum_posts/hot DROP 断言不动）。
6. 浏览器验证首页/教育/论坛/agent 各页正常（功能无损）。
7. git rm 已删的 PRD docx + 提交。

## 非目标

- 不重构现有工作逻辑（仅删死代码/死数据/垃圾文件）。
- 不动任何活字段、活 snippet、活 grp。
