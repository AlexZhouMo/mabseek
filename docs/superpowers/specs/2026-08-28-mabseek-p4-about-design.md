# MabSeek P4 · 了解我们（about.html）Design Spec

**日期：** 2026-08-28
**阶段：** P4（了解我们页面升级；中英切换 i18n 本期不做）
**页面：** `about.html`

## 1. 目标

把 `about.html` 对齐 P1/P2/P3 的统一外壳（七区导航 + 统一页脚），并按 PRD-20260825「关于我们」段（[51]-[57]）补齐四项内容：

1. **核心团队**改为双人并列——张林琦（科学侧）+ 马维英（AI 侧）。（PRD [52]）
2. **位置展示**图片块。（PRD [51][54]）
3. 近况（新闻）加**「了解更多」链接**。（PRD [53]）
4. **联系我们 + 快速反馈**表单 + 联系方式。（PRD [55][57]）

既有内容本体（平台介绍、新闻筛选、国际合作、四张定性成果卡）**保留、不重写**。采用「统一外壳 + 增量新增」策略，与 P3a/P3b/P3c 一致。

**i18n（中英切换）本期明确不做**——PRD [58] 仅为「是否需要」式开放提问，用户已确认本期不做，如需另立独立阶段（brainstorm→spec→plan）。

## 2. 背景与现状

`about.html`（208 行）是 **P1 之前的旧外壳**：

- 导航 5 项、顺序旧（首页 / 教育 / Antibody Agent / 论坛 / 网站介绍），标题「网站介绍」，CTA「免费试用 Agent」。
- 页头 `.page-hero` + eyebrow + 标题 + lead + 锚点 tag-row（核心团队/平台/新闻/国际合作）。
- `#team` 实验室与核心团队：单人「张老师」`.profile`（照片占位 + 简介 + `data-num` 示意指标 + 四张 `.card` + 免责说明）。
- `#platform` MabSeek 平台介绍（`.intl` 两栏 + 图片 onerror 兜底）。
- `#news` 新闻与活动：`.news-tabs` chip 筛选（全部/育人/科研/日常）+ 5 条 `.news-item`（页尾内联脚本按 `data-nf`/`data-nc` show/hide）。
- `#intl` 国际科研合作（中印尼合作 + `.timeline` + PRA）。
- 渐变「联系 CTA」段（两个按钮，`data-demo` 邮箱，无表单）。
- 旧版页脚（产品/探索/关于 三列）。
- `<head>` 页面局部 `<style>`（`.profile/.stat-row/.stat-pill/.news-tabs/.news-item/.intl/.intl-media/.timeline`）+ 页尾内联 `<script>`（新闻筛选）。

参考 PRD [54]「以图片为主体，参考清华和北大主页」——**仅版式参考**，不搬运其文字/数据。

## 3. 约束（标准约束，全程遵守）

- **纯静态站**，零后端；所有交互前端模拟（`data-demo` + toast、`#contact-form` 前端模拟提交，沿用全站惯例）。
- **不杜撰**：张林琦、马维英为真实公众人物，仅使用用户提供的姓名 + 定性角色描述（研究方向），**不编造论文数、专利数、具体头衔**；`data-num` 指标沿用既有「示意」标注 + 免责说明（正式上线同步清华官网主页）。位置图不杜撰具体地址，图注沿用全站既有 `清华大学医学院`。联系方式沿用全站既有 `contact@mabseek.org · 清华大学医学院`。
- 参考站（清华/北大主页）仅版式参考，不搬运内容。
- 增量样式（如有）**追加**到 `style.css` 末尾（P3c 段之后），不改既有规则；`node -e` 校验花括号平衡。
- i18n 留待未来独立阶段，本阶段不做。
- 执行方式：master 分支上 subagent-driven-development（用户已就本项目授权）。

## 4. 外壳统一

### 4.1 导航（替换）

将 `<!-- 导航 -->` 到其 `</header>` 整块替换为 P1 七区导航：

- 链接顺序：`首页(index.html) / 技术平台(technology.html) / Antibody Agent(agent.html) / 教育(education.html) / 论坛(forum.html) / 了解我们(about.html)[active]`。
- 右侧 CTA：`<a href="#contact" class="btn btn-green">联系我们</a>`（指向本页新增的 `#contact` 联系区——本页即联系内容所有者）。
- 普通 `.nav`（`.page-hero` 浅色）；保留 `.nav-toggle` 汉堡。

### 4.2 页脚（替换）

将 `<!-- 页脚 -->` 到其 `</footer>` 整块替换为 P1 统一页脚（与 education/forum 同款）：品牌简介 + `.footer-social`；「探索」列（技术平台 / Antibody Agent / 教育）；「社区」列（论坛 / 了解我们 / 新闻活动 `about.html#news`）；「联系」列（联系我们 `#contact` 本页 / `mailto:contact@mabseek.org` / 清华大学医学院）；`.footer-bottom` 两段版权行。

## 5. 内容升级

### 5.1 核心团队 → 双人并列（PRD [52]）

把 `#team` 内单人 `.profile`（张老师）升级为**两位负责人并列**：

- **张林琦**（清华大学）— 研究方向：抗体工程 · 疫苗 · 病毒免疫（对应 MabSeek 的「科学 / 抗体」侧）。
- **马维英**（清华大学）— 研究方向：人工智能 · 大模型 · 机器学习（对应 MabSeek 的「AI」侧）。
- 版式：并排两张负责人卡（复用既有 `.profile` 照片占位 + 简介结构，或以 `.grid-2` 承载两个精简 profile；实现时择一，保证窄屏竖排）。照片用姓氏首字占位（张 / 马），图注为姓名 + `清华大学医学院`。
- 文案：**定性一句话研究方向 + 在 MabSeek 中的角色**（张=抗体科学负责人、马=AI 负责人），突出「AI × 抗体」的互补。**不出现论文数/专利数/头衔等硬数值**（如保留 `data-num` 指标，必须维持「示意」标注）。
- 保留下方四张定性成果卡（📄 学术论文 / 🧾 专利 / 🏆 奖项 / 👩‍🔬 核心团队）与免责说明（更新为「详细简历与成果参考清华大学医学院官网张林琦、马维英主页，正式上线时同步更新」）。

### 5.2 位置展示（PRD [51][54]）

在 `#team` 内（或其后）新增「我们的位置」图片块：

- 一张实验室/校园全景 `<img src="assets/images/location.png" alt="MabSeek 实验室位置">`，`onerror` 兜底为品牌渐变块（沿用全站 `international.png`/`agent-hero.png` 惯例）。
- 图注 `清华大学医学院`（真实、全站既有），可附一句定性说明「立足北京市国合基地」（此语已在既有 `#intl` 使用，属站内既有措辞）。
- 不杜撰门牌地址、经纬度或 3D 全景数据。

### 5.3 近况「了解更多」（PRD [53]）

- 保留既有 `#news` 新闻筛选（全部/育人/科研/日常）与 5 条 `.news-item` 及其内联脚本，不改。
- 在新闻列表尾部新增一行居中「了解更多 →」链接：`<a class="btn btn-outline" data-demo="正式版将展示完整新闻与活动列表">了解更多 →</a>`（前端演示，`data-demo`→toast）。
- PRD「最好可以添加搜索功能」为可选项，本期不做（YAGNI；论坛已实现 AI 搜索演示）。

### 5.4 联系我们 + 快速反馈（PRD [55][57]）

将既有渐变「联系 CTA」段替换为 index.html 的 canonical 联系/反馈模块：

```html
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

- 表单 `id="contact-form"` 由既有 `main.js` 处理器接管：`submit` → `preventDefault` → 弹 toast「已收到你的反馈……」→ `reset()`。**无需新 JS。**
- 联系方式为真实既有信息，不杜撰。

## 6. 样式（如需，增量追加 style.css）

- 复用 about 页既有局部 `<style>`（`.profile/.stat-pill/.news-item/.timeline/.intl/.intl-media`）与全站令牌/工具类（`.section/.section-dark/.container/.eyebrow/.section-title/.section-sub/.grid-2/.card/.btn/.btn-green/.btn-outline/.feedback/.fb-row/.fb-submit/.txt-neon/.reveal`），均已存在（`.section-dark/.feedback/.fb-*/.txt-neon` 见 index.html）。
- 双人团队卡若需并排布局微调（如 `.profile` 由单栏改双人网格），可在 about 页局部 `<style>` 内增补，或**追加** P4 段到 `style.css` 末尾（P3c 段之后）。优先改 about 页局部 `<style>`（作用域内、不影响其它页面）。若追加到 `style.css`，`node -e` 校验花括号平衡。

## 7. 文件改动清单

| 文件 | 动作 | 职责 |
|---|---|---|
| `about.html` | 改 | 导航→P1 七区（了解我们 active，CTA→`#contact`）；`#team` 单人→双人（张林琦+马维英）+ 位置图；`#news` 加「了解更多」链接；渐变 CTA 段→`.section-dark #contact` 联系/反馈模块；页脚→P1 页脚；`<head>` 局部 `<style>` 如需并排微调则增补 |
| `assets/css/style.css` | 改（可选，**末尾追加** P4 段） | 仅当双人团队卡布局需要全站级样式时追加；否则不动 |

**不动：** `index.html` / `technology.html` / `agent.html` / `education.html` / `forum.html` / `main.js` / `knowledge-graph.js`；about.html 既有新闻筛选内联脚本、既有 `#platform`/`#intl` 分区、四张成果卡。

## 8. 验证（preview_*，端口 8777）

> 预览标签可能缓存旧 css/html，取计算样式前先 cache-bust；取色/取高前注入 `*{transition:none!important}`；`preview_eval` 视口可能报 0（IntersectionObserver 不触发、宽度读数无效），功能/视觉核查前对 `.reveal` 强制 `.add('in')`，以计算 CSS 值为准。

- 导航 6 链接（首页/技术平台/Antibody Agent/教育/论坛/了解我们）且「了解我们」active；CTA「联系我们」→ 本页 `#contact`（滚动到位）。
- `#team` 出现**两位负责人**（张林琦 + 马维英），姓名/方向正确，无论文数/专利数/头衔等硬数值（`data-num` 若在，带「示意」）；免责说明更新为双人。
- 位置图块出现，图片缺失时 onerror 兜底为渐变块不破版；图注 `清华大学医学院`。
- `#news` 筛选仍工作（点 chip 过滤 5 条新闻）；尾部「了解更多 →」点击弹 toast。
- `#contact` 表单：填写后点「提交反馈」→ 弹 toast「已收到你的反馈…」并清空表单（`main.js` 处理器）。
- 页脚为「探索/社区/联系」三列；`preview_network` filter=failed 为空（图片 onerror 兜底不计失败请求，若报 404 属预期占位，记录即可）；`preview_console_logs` level=error 为空。
- 移动端 `preview_resize` preset=mobile：导航折叠汉堡；双人团队卡竖排不溢出；联系表单窄屏不溢出。完成后 `preview_resize` preset=desktop 复位。

## 9. YAGNI（明确不做）

- 不做 i18n（中英切换）——留未来独立阶段。
- 不接真实后端；表单仅前端模拟提交。
- 不做近况搜索引擎（PRD 可选项）。
- 不重写既有平台介绍 / 国际合作 / 新闻数据；不改其它页面；不加新配色。
- 不编造张林琦 / 马维英的论文数、专利数、头衔或简历细节。
