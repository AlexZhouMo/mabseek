# MabSeek P4 · 了解我们（about.html）Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 把 `about.html` 对齐 P1 统一外壳（七区导航 + 统一页脚），并按 PRD-0825 升级内容：核心团队改为双人（张林琦 + 马维英）、新增位置展示图、近况加「了解更多」链接、渐变 CTA 段替换为 `.section-dark #contact` 联系/快速反馈模块。既有平台介绍、新闻筛选、国际合作、四张成果卡全部保留。i18n 本期不做。

**Architecture:** 纯静态站，零后端。所有改动集中在 `about.html` 一个文件：外壳块替换 + `#team`/`#news`/联系段内容升级 + `<head>` 局部 `<style>` 增补双人卡与位置图样式（**作用域内，不改全站 `style.css`**）。联系表单 `id="contact-form"` 由既有 `main.js` 处理器接管（前端模拟提交 → toast → reset），**零新 JS**；既有新闻筛选内联脚本保留不动。

**Tech Stack:** 原生 HTML / CSS（CSS 变量 + 工具类）。无构建、无依赖、无新增脚本。复用 `main.js`（`[data-demo]`→toast、`#contact-form` 提交、`.reveal`、`.nav` 汉堡）。验证用 `preview_*`（root `.claude/launch.json` 的 `mabseek` 项，端口 8777；预览标签可能缓存旧 css/html，取计算样式前先 cache-bust；取色/取高前注入 `*{transition:none!important}`；`preview_eval` 视口可能报 0，功能/视觉核查前对 `.reveal` 强制 `.add('in')`，以计算 CSS 值为准）。

**内容来源（权威，不杜撰）：** 张林琦、马维英为真实公众人物，仅用姓名 + 定性研究方向（张=抗体/病毒免疫/疫苗；马=AI/大模型/机器学习）+ 在 MabSeek 中的角色；**不编造论文数、专利数、头衔、简历细节**；双人卡不含 `data-num` 数值。位置图 onerror 兜底，图注 `清华大学医学院`（全站既有）。联系方式 `contact@mabseek.org · 清华大学医学院`（全站既有）。用户已确认姓名与本期范围。

---

## 文件结构

| 文件 | 动作 | 职责 |
|---|---|---|
| `about.html` | 改 | `<head>` 局部 `<style>` 增补 `.leads/.lead-card/.loc-*`；`<title>` 与 breadcrumb 文案；导航→P1 七区（了解我们 active，CTA→`#contact`）；`#team` 单人→双人 + 位置图；`#news` 加「了解更多」；渐变 CTA 段→`.section-dark #contact` 联系/反馈模块；页脚→P1 页脚 |

**不动：** `index.html` / `technology.html` / `agent.html` / `education.html` / `forum.html` / `main.js` / `knowledge-graph.js` / `assets/css/style.css`；about.html 既有 `#platform`/`#intl` 分区、四张成果卡结构、新闻筛选内联脚本、hero 的 tag-row 锚点。

---

## Task 1: about.html — 外壳统一 + 内容升级

**Files:**
- Modify: `about.html`

> 先 `Read` `mabseek/about.html` 全文，确保每处 Edit 精确匹配。以下 8 处编辑（A–H），保留未提及的一切既有内容。

- [ ] **Step A: `<head>` 局部 `<style>` 增补双人卡 + 位置图样式**

在 `about.html` 的 `<head>` 局部 `<style>` 块内、其闭合 `</style>`（现第 30 行）**之前**，追加：

```css
/* 双人负责人卡 */
.leads { display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-top:22px; }
.lead-card { display:flex; gap:18px; align-items:flex-start; background:#fff; border:1px solid var(--line); border-radius:var(--radius); padding:22px; box-shadow:var(--sh-sm); }
.lead-card .ph { flex:0 0 84px; width:84px; height:84px; border-radius:16px; background:var(--grad-brand); color:#fff; display:grid; place-items:center; font-size:34px; font-weight:800; }
.lead-card .ph.g2 { background:linear-gradient(135deg,#12D6A0,#4b6bff); }
.lead-card .nm { font-size:20px; font-weight:800; }
.lead-card .aff { font-size:13px; color:var(--ink-3); margin:2px 0 10px; }
.lead-card .dir { font-size:13.5px; color:var(--ink-2); line-height:1.6; }
.lead-card .role { display:inline-block; margin-top:12px; font-size:12px; font-weight:700; padding:4px 12px; border-radius:999px; background:var(--purple-050); color:var(--purple); }
.lead-card .role.green { background:var(--green-100); color:#06a97c; }
/* 位置展示 */
.loc-block { margin-top:32px; }
.loc-media { border-radius:var(--radius-lg); overflow:hidden; box-shadow:var(--sh-lg); }
.loc-media img { width:100%; display:block; }
.loc-media.grad { background:var(--grad-brand); min-height:280px; display:grid; place-items:center; color:#fff; font-size:64px; }
.loc-cap { text-align:center; font-size:13px; color:var(--ink-3); margin-top:12px; }
@media (max-width:720px){ .leads{ grid-template-columns:1fr; } }
```

（`--green-100/--purple-050/--grad-brand/--radius/--radius-lg/--sh-sm/--sh-lg/--line/--ink-2/--ink-3/--purple/--green` 均在全站 `:root` 已定义。）

- [ ] **Step B: 更新 `<title>`**

将（现第 6 行）：

```html
<title>网站介绍 · 实验室概况与成果 | MabSeek 抗体求索</title>
```

替换为：

```html
<title>了解我们 · 实验室概况与成果 | MabSeek 抗体求索</title>
```

- [ ] **Step C: 替换导航块**

将 `about.html` 中 `<!-- 导航 -->` 到其 `</header>` 的整块（现第 34–48 行，旧 5 项导航 + 「免费试用 Agent」CTA）替换为 P1 七区导航（`了解我们` active，CTA「联系我们」→ 本页 `#contact`）：

```html
<!-- 导航 -->
<header class="nav">
  <div class="container">
    <a class="brand" href="index.html"><span class="logo"><svg viewBox="0 0 24 24" fill="none"><path d="M7 3v4c0 2.8 2.2 5 5 5s5 2.2 5 5v4M17 3v4c0 2.8-2.2 5-5 5" stroke="white" stroke-width="2" stroke-linecap="round"/></svg></span><span>MabSeek<small>抗体求索 · 清华大学医学院</small></span></a>
    <nav class="nav-links">
      <a href="index.html">首页</a>
      <a href="technology.html">技术平台</a>
      <a href="agent.html">Antibody Agent</a>
      <a href="education.html">教育</a>
      <a href="forum.html">论坛</a>
      <a href="about.html" class="active">了解我们</a>
    </nav>
    <div class="nav-actions"><a href="#contact" class="btn btn-green">联系我们</a></div>
    <button class="nav-toggle" aria-label="菜单"><span></span><span></span><span></span></button>
  </div>
</header>
```

- [ ] **Step D: 更新页头 breadcrumb 文案**

将（现第 53 行）：

```html
    <div class="breadcrumb reveal"><a href="index.html">首页</a> / 网站介绍</div>
```

替换为：

```html
    <div class="breadcrumb reveal"><a href="index.html">首页</a> / 了解我们</div>
```

- [ ] **Step E: `#team` 单人 profile → 双人负责人 + 位置图**

将 `#team` 分区内从 `<span class="eyebrow reveal">1 · 实验室与核心团队</span>`（现第 66 行）到 `.profile` 的闭合 `</div>`（现第 89 行）整段替换为下面内容（双人卡 + 保留的四张成果卡 + 更新后的免责说明 + 位置图）：

```html
    <span class="eyebrow reveal">1 · 实验室与核心团队</span>
    <h2 class="section-title reveal d1">两位负责人，AI 与抗体科学的交汇</h2>
    <p class="section-sub reveal d2">MabSeek 由抗体与病毒免疫的科学积累，叠加人工智能与大模型能力共同驱动——「AI × 抗体」正是两位负责人研究方向的交汇。</p>
    <div class="leads reveal d1">
      <div class="lead-card">
        <div class="ph">张</div>
        <div>
          <div class="nm">张林琦</div>
          <div class="aff">清华大学 · 实验室负责人</div>
          <div class="dir">长期从事抗体工程、疫苗研发与病毒免疫研究，为 MabSeek 奠定抗体科学与湿实验验证的专业根基。</div>
          <span class="role">科学负责人 · 抗体 / 疫苗 / 病毒免疫</span>
        </div>
      </div>
      <div class="lead-card">
        <div class="ph g2">马</div>
        <div>
          <div class="nm">马维英</div>
          <div class="aff">清华大学 · 实验室负责人</div>
          <div class="dir">深耕人工智能、大模型与机器学习，为 MabSeek 的 AI 抗体设计、预测与分析能力提供核心算法支撑。</div>
          <span class="role green">AI 负责人 · 人工智能 / 大模型 / 机器学习</span>
        </div>
      </div>
    </div>
    <div class="grid-2" style="margin-top:24px">
      <div class="card reveal"><h3 style="font-size:16px">📄 学术论文成果</h3><p>系列研究成果发表于领域顶级期刊，内化沉淀入平台知识库，支撑 Antibody Agent 的文献问答能力。</p></div>
      <div class="card reveal d1"><h3 style="font-size:16px">🧾 专利成果汇总</h3><p>抗体设计算法与湿实验方法相关专利，构成平台核心技术壁垒。</p></div>
      <div class="card reveal d2"><h3 style="font-size:16px">🏆 科研奖项与荣誉</h3><p>承担国家级科研项目，获多项学术与产业化荣誉。</p></div>
      <div class="card reveal d3"><h3 style="font-size:16px">👩‍🔬 核心团队</h3><p>由博士研究团队与湿实验平台工程师组成，产学研深度融合。</p></div>
    </div>
    <p class="reveal" style="font-size:13px;color:var(--ink-3);margin-top:16px">注：以上为门户展示模板，详细简历与成果列表参考清华大学医学院官网张林琦、马维英主页，正式上线时同步更新。</p>
    <div class="loc-block reveal">
      <div class="loc-media"><img src="assets/images/location.png" alt="MabSeek 实验室位置" onerror="this.parentElement.classList.add('grad');this.remove();this.parentElement.innerHTML='🏛️'"></div>
      <div class="loc-cap">清华大学医学院 · 立足北京市国合基地</div>
    </div>
```

> 注：删除了原单人 `.profile` 中的 `data-num` 示意指标（避免两位实名负责人出现易被误读的数值）；四张成果卡文案原样保留，仅补 `.reveal`。

- [ ] **Step F: `#news` 新闻列表尾部新增「了解更多」链接**

定位 `#news` 分区内 `#news-list` 的闭合 `</div>`（现第 130 行，紧接其后是 `</div>\n</section>`）。在 `#news-list` 的闭合 `</div>` **之后**、`#news` 的 `.container` 闭合 `</div>` **之前**，插入：

```html
    <div class="text-center reveal" style="margin-top:26px"><a class="btn btn-outline" data-demo="正式版将展示完整新闻与活动列表">了解更多 →</a></div>
```

- [ ] **Step G: 渐变「联系 CTA」段 → `.section-dark #contact` 联系/反馈模块**

将 `<!-- 联系 CTA -->` 注释与其后的 `<section class="section-sm">…</section>` 整块（现第 163–175 行）替换为：

```html
<!-- 联系我们 + 快速反馈 -->
<section class="section section-dark" id="contact">
  <div class="container">
    <div class="text-center" style="margin-bottom:36px">
      <span class="eyebrow reveal">联系我们</span>
      <h2 class="section-title reveal d1">寻求合作 · 加入我们 · <span class="txt-neon">使用平台</span></h2>
      <p class="section-sub reveal d2" style="margin:10px auto 0">contact@mabseek.org · 清华大学医学院</p>
    </div>
    <form class="feedback reveal d2" id="contact-form">
      <div class="fb-row">
        <input type="text" name="name" placeholder="你的称呼" required>
        <input type="email" name="email" placeholder="邮箱" required>
      </div>
      <textarea name="message" placeholder="简单描述你的靶点 / 需求 / 合作意向" required></textarea>
      <button type="submit" class="btn btn-green fb-submit">提交反馈</button>
    </form>
  </div>
</section>
```

（`.section-dark/.feedback/.fb-row/.fb-submit/.txt-neon` 全站 `style.css` 已定义、index.html 已在用；`#contact-form` 提交由 `main.js` 既有处理器接管 → toast + reset，无需新 JS。）

- [ ] **Step H: 替换页脚为 P1 页脚**

将 `about.html` 中 `<!-- 页脚 -->` 到其 `</footer>` 的整块（现第 177–191 行，旧 产品/探索/关于 三列）替换为 P1 统一页脚（联系列指向本页 `#contact`）：

```html
<!-- 页脚 -->
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <div class="brand"><span class="logo"><svg viewBox="0 0 24 24" fill="none"><path d="M7 3v4c0 2.8 2.2 5 5 5s5 2.2 5 5v4M17 3v4c0 2.8-2.2 5-5 5" stroke="white" stroke-width="2" stroke-linecap="round"/></svg></span><span>MabSeek<small>抗体求索 · 清华大学医学院</small></span></div>
        <p>清华团队 × AI 大模型，让抗体发现从反复试错变成精准编程。</p>
        <div class="footer-social" style="margin-top:18px">
          <span title="微信" data-demo="扫码关注 MabSeek 公众号">💬</span>
          <span title="邮箱" data-demo="联系邮箱：contact@mabseek.org">✉️</span>
        </div>
      </div>
      <div><h5>探索</h5><ul>
        <li><a href="technology.html">技术平台</a></li>
        <li><a href="agent.html">Antibody Agent</a></li>
        <li><a href="education.html">教育</a></li>
      </ul></div>
      <div><h5>社区</h5><ul>
        <li><a href="forum.html">论坛</a></li>
        <li><a href="about.html">了解我们</a></li>
        <li><a href="about.html#news">新闻活动</a></li>
      </ul></div>
      <div><h5>联系</h5><ul>
        <li><a href="#contact">联系我们</a></li>
        <li><a href="mailto:contact@mabseek.org">contact@mabseek.org</a></li>
        <li>清华大学医学院</li>
      </ul></div>
    </div>
    <div class="footer-bottom">
      <span>© 2026 MabSeek 抗体求索 · 清华大学医学院实验室. 保留所有权利。</span>
      <span>紫 + 荧光绿 · 科技与趣味的平衡</span>
    </div>
  </div>
</footer>
```

- [ ] **Step I: HTML 结构自检 + 提交**

Run（校验标签基本平衡，section 数量合理）：
`node -e "const s=require('fs').readFileSync('mabseek/about.html','utf8');const so=(s.match(/<section/g)||[]).length,sc=(s.match(/<\/section>/g)||[]).length,fo=(s.match(/<form/g)||[]).length,fc=(s.match(/<\/form>/g)||[]).length;console.log('section',so,sc,'form',fo,fc,(so===sc&&fo===fc)?'OK':'MISMATCH')"`
Expected: `section N N form 1 1 OK`（开合标签数量相等）。

```bash
git add mabseek/about.html
git commit -m "feat(mabseek): harmonize about shell + dual leaders, location, contact form"
```

---

## Task 2: 浏览器验证与响应式

**Files:** 无（仅验证 + 必要微调）

- [ ] **Step 1: 桌面走查**

预览服务器（root `.claude/launch.json` 的 `mabseek` 项，端口 8777；若未运行 `preview_start` name=`mabseek`）。导航到 `http://localhost:8777/about.html`，reload。**取计算样式前先 cache-bust**（重挂带 `?ts=` 的 style.css，并对 HTML 用带查询串的 URL 避开缓存）。功能/视觉核查前对 `.reveal` 强制 `.add('in')`。
- `preview_snapshot`：导航 6 项（首页/技术平台/Antibody Agent/教育/论坛/了解我们）且 `了解我们` active；`#team` 出现**两位负责人**「张林琦」「马维英」；出现位置图块与图注「清华大学医学院 · 立足北京市国合基地」；`#news` 尾部出现「了解更多 →」；出现深色 `#contact` 联系区与反馈表单；页脚为「探索/社区/联系」三列。
- 不杜撰核查：`#team` 文本仅姓名 + 定性方向 + 角色，**无论文数/专利数/头衔/简历硬数值**（无 `data-num`）；免责说明含「张林琦、马维英……正式上线时同步更新」。
- `preview_screenshot` 通读：页头 → 双人团队 + 位置图 → 平台介绍 → 新闻（含了解更多）→ 国际合作 → 深色联系区 → 页脚，视觉连贯（浅/深底节奏正常）。

- [ ] **Step 2: 交互验证**

- 导航 CTA「联系我们」`preview_click` → 滚动/跳转到本页 `#contact`。
- `preview_click` `#news` 某新闻筛选 chip（如「科研类」`[data-nf=res]`）→ 新闻列表按 `data-nc` 过滤（既有脚本仍工作）。
- `preview_click` 「了解更多 →」→ 弹 toast「正式版将展示完整新闻与活动列表」。
- 联系表单：`preview_fill` `#contact-form [name=name]`=「测试」、`[name=email]`=「a@b.com」、`[name=message]`=「测试留言」，`preview_click` `.fb-submit` → 弹 toast「已收到你的反馈…」且表单被清空（`main.js` `#contact-form` 处理器）。
- 位置图 onerror 兜底：`preview_inspect` `.loc-media`（若 `location.png` 不存在，应带 `grad` 类、渲染渐变块，不破版）。
- `preview_console_logs` level=error 为空；`preview_network` filter=failed 仅可能含 `location.png`（占位图，预期 404，记录即可，不算功能失败）。

- [ ] **Step 3: 移动端响应式**

`preview_resize` preset=`mobile`，reload（cache-bust）：
- `preview_screenshot`：导航折叠为汉堡；`.leads` 双人卡竖排（`@media max-width:720px` 单列）不溢出；`.lead-card` 内头像 + 文字不溢出；联系表单 `.fb-row` 窄屏不溢出。
- 验证视口宽度有效（`innerWidth>0`）后再读 `document.scrollWidth === document.clientWidth`（无横向溢出）。
- `preview_click` `.nav-toggle` 展开菜单。
- 完成 `preview_resize` preset=`desktop` 复位。

- [ ] **Step 4: 最终提交（如有微调）**

```bash
git add -A
git commit -m "chore(mabseek): P4 about page responsive + verification fixes"
```

（若 Step 1–3 无需改动，可跳过本步、不建空提交。）

---

## Self-Review 记录

- **Spec 覆盖**：§4.1 导航 → Task1(Step C) + 标题/breadcrumb(Step B/D)；§4.2 页脚 → Task1(Step H)；§5.1 双人团队 → Task1(Step A CSS + Step E HTML)；§5.2 位置图 → Task1(Step A CSS + Step E HTML)；§5.3 了解更多 → Task1(Step F)；§5.4 联系/反馈 → Task1(Step G)；§6 样式（局部 `<style>` 增补，不动全站 css）→ Task1(Step A)；§7 文件改动（仅 about.html）→ 全覆盖；§8 验证 → Task2。无遗漏。
- **占位符扫描**：无 TBD/TODO；每步给出完整替换块或完整插入代码（含全部双人卡 HTML + CSS + 联系模块 + 页脚）。
- **一致性**：新类 `.leads/.lead-card/.lead-card .ph(.g2)/.nm/.aff/.dir/.role(.green)/.loc-block/.loc-media(.grad)/.loc-cap`（Step A 定义、Step E 使用）一致；复用全站类 `.nav/.nav-links/.footer/.footer-grid/.footer-social/.footer-bottom/.btn/.btn-green/.btn-outline/.section/.section-dark/.section-sub/.section-title/.eyebrow/.grid-2/.card/.feedback/.fb-row/.fb-submit/.txt-neon/.reveal` 与既有 about 类均已存在（`.section-dark/.feedback/.fb-*/.txt-neon` 见 index.html）；`#contact-form` 与 `main.js` 处理器约定的 `[name=name]/[name=email]/[name=message]` 字段一致。
- **不杜撰**：双人仅姓名 + 定性方向 + 角色，无 `data-num`、无论文/专利/头衔硬数值；免责说明更新为双人；位置图 onerror 兜底、图注为站内既有 `清华大学医学院`；联系方式为站内既有 `contact@mabseek.org`。
- **零新 JS**：联系表单靠 `main.js` 既有 `#contact-form` 处理器；「了解更多」/页脚社交/位置图交互靠既有 `[data-demo]`；既有新闻筛选内联脚本保留不动。
- **YAGNI**：不做 i18n、不接后端、不做近况搜索、不改其它页面、不动全站 style.css、不加新配色。
