# 首页英雄区改为图片背景（左文右图 · 响应式）设计

- 日期：2026-09-02
- 状态：已确认，待实现
- 关联文件：`public/index.php`、`public/assets/css/style.css`、`public/assets/js/hero-anim.js`（删除）、`public/assets/images/hero-home.jpg`（新增）

## 目标

把首页顶部英雄区（`.hero-c`）从「全屏 Canvas2D 抗体组装动画 + 居中文字 + 右下角 CTA」改造为
「抗体图片作背景（图案锚右）+ 文字左对齐置左 + CTA 跟随文字」，并保证浏览器窗口任意缩放下的兼容性与可读性。

## 已定决策

1. **图片管理**：静态资源写死，路径 `public/assets/images/hero-home.jpg`。
2. **画布动画**：删除 `<canvas>`、删除 `hero-anim.js` 及其 `<script>` 引用、删除 `#antibody-canvas` CSS。
3. **布局**：行业常用「左文右图」——文字块左对齐、垂直居中，收在容器左侧栏，宽度上限约 560px（不超过视口 52%），避开右侧图案。
4. **CTA 位置**：紧跟副标题下方，随左侧文字左对齐（去掉右下角浮动 CTA）。
5. **响应式策略**：图片始终作 `cover` 背景、焦点靠右；叠加从左到右深色渐变遮罩保证文字可读；窄屏加强遮罩，不做上下堆叠。
6. **技术实现**：CSS `background-image`（非 `<img>` 元素）。
7. **图片压缩**：实现阶段把源图压到 ≤400KB（jpg/webp 均可，最终落地为 hero-home.jpg）。

## 前置条件（实现阶段必须满足）

- 源图（用户在对话中提供的绿/紫抗体图，右侧为抗体结构、左侧为深色空白）需以文件形式存在于磁盘，实现时压缩后另存为 `public/assets/images/hero-home.jpg`。
- 若实现开始时磁盘上找不到源图文件，先向用户索取文件路径，不得凭空生成占位图。
- 压缩目标：长边 ≤ 1920px，质量使体积 ≤ 400KB，保持 16:9 左右比例（源图约 1704×941）。

## HTML 结构（public/index.php 英雄区）

现有：
```html
<section class="hero-c">
  <canvas id="antibody-canvas"></canvas>
  <div class="hero-c-inner">
    <span class="eyebrow reveal">…</span>
    <h1 class="reveal d1">…</h1>
    <p class="hero-c-sub reveal d2">…</p>
  </div>
  <a href="agent.php" class="btn btn-green btn-lg hero-c-cta reveal d3">…</a>
</section>
```

改为（去掉 canvas；CTA 移入 inner，跟随文字）：
```html
<section class="hero-c">
  <div class="hero-c-inner">
    <span class="eyebrow reveal"><?= snip('home.hero.eyebrow') ?></span>
    <h1 class="reveal d1"><?= snip_raw('home.hero.title') ?></h1>
    <p class="hero-c-sub reveal d2"><?= snip('home.hero.sub') ?></p>
    <a href="agent.php" class="btn btn-green btn-lg hero-c-cta reveal d3"><?= snip('home.hero.cta') ?></a>
  </div>
</section>
```

- 文案 snippet（`home.hero.*`）与 `reveal` 动画类保持不变。
- 删除 `index.php` 末尾的 `<script src="assets/js/hero-anim.js"></script>`；保留 `main.js`。

## CSS（public/assets/css/style.css）

删除：`#antibody-canvas { … }` 规则。

改写 `.hero-c` 系列为：
```css
/* 首页英雄区：图片背景（图案锚右）+ 左对齐文字 */
.hero-c {
  position: relative;
  min-height: 88vh;
  display: flex;
  align-items: center;                 /* 竖向居中 */
  overflow: hidden;
  padding: calc(var(--nav-h) + 72px) 24px 40px;
  background-color: var(--bg-dark);    /* 兜底：图未加载时不塌陷 */
  background-image: url('../images/hero-home.jpg');
  background-size: cover;
  background-position: right center;   /* 焦点锚右，抗体不被裁没 */
  background-repeat: no-repeat;
}
/* 左侧深色渐变遮罩：保证文字可读，同时右侧抗体透出（颜色对齐 --bg-dark #070A14 = rgb(7,10,20)） */
.hero-c::before {
  content: "";
  position: absolute; inset: 0; z-index: 1;
  background: linear-gradient(90deg,
    rgba(7,10,20,.92) 0%, rgba(7,10,20,.72) 32%, rgba(7,10,20,.42) 55%, rgba(7,10,20,0) 78%);
}
.hero-c .hero-c-inner {
  position: relative; z-index: 2;
  width: 100%;
  max-width: var(--maxw);              /* 站点容器最大宽度，现为 1200px，与页面栅格一致 */
  margin: 0 auto;
  text-align: left;
}
/* 文字实际内容再收窄，避开右侧图案 */
.hero-c .hero-c-inner > * { max-width: min(560px, 52vw); }
.hero-c h1 { font-size: clamp(30px, 5.4vw, 62px); line-height: 1.14; color: #fff; }
.hero-c .hero-c-sub { color: var(--ink-on-dark-2); font-size: clamp(15px,2vw,19px); margin-top: 20px; }
.hero-c .hero-c-cta { display: inline-flex; margin-top: 28px; }

@media (max-width: 720px) {
  .hero-c { min-height: 92vh; }
  /* 窄屏：加强遮罩、文字占满，抗体作氛围隐约可见 */
  .hero-c::before {
    background: linear-gradient(90deg,
      rgba(7,10,20,.95) 0%, rgba(7,10,20,.88) 55%, rgba(7,10,20,.7) 100%);
  }
  .hero-c .hero-c-inner > * { max-width: 100%; }
}
```

说明：
- 容器最大宽度沿用站点变量 `--maxw`（现为 1200px），与页面其余栅格一致。
- 遮罩颜色对齐 `--bg-dark`（#070A14 = rgb(7,10,20)）；百分比为初值，实现阶段按真实观感在浏览器里微调（保证 375/768/1440 三档都清晰）。

## 清理

- 删除文件 `public/assets/js/hero-anim.js`（106 行，仅服务该动画）。实现前 `grep -rn "hero-anim\|antibody-canvas" public` 确认无其它引用后再删。

## 验证

1. 起本地服务（`php -S localhost:8778 -t public` 或 preview）。
2. 浏览器在 1440 / 768 / 375 三档宽度：
   - 文字左对齐、清晰可读，不与右侧抗体重叠；
   - 抗体锚在右侧、随缩放不被裁没；
   - 无横向滚动条、无溢出。
3. 控制台无报错；确认 `hero-anim.js` 删除后其它脚本（`main.js`）不受影响。
4. 截图存档三档宽度对比。

## 不做（YAGNI）

- 不做后台可上传/换图（本期静态写死）。
- 不做 `<picture>` 多裁切图 / 移动端换图。
- 不做上下堆叠的移动端版式。
- 不改英雄区文案内容（仅版式）。
