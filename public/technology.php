<?php
require __DIR__ . '/../app/bootstrap.php';
$active = 'technology'; $navOnDark = true; $contactHref = 'index.php#contact';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>技术平台 · MabSeek 抗体求索 | 清华大学医学院</title>
<meta name="description" content="MabSeek 技术平台：AI 智能中枢编排的干湿闭环架构、Data / AI / Wet lab 三大能力与经典案例（安巴韦/罗米司韦单抗）。">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🧬</text></svg>">
</head>
<body>

<!-- ============ 导航 ============ -->
<?php include __DIR__ . '/partials/nav.php'; ?>

<!-- ============ T1 页头英雄区（深） ============ -->
<section class="tech-hero section-dark">
  <div class="container">
    <span class="eyebrow reveal"><?= snip('tech.hero.eyebrow') ?></span>
    <h1 class="reveal d1"><?= snip_raw('tech.hero.title') ?></h1>
    <p class="section-sub reveal d2"><?= snip('tech.hero.sub') ?></p>
    <div class="reveal d3" style="margin-top:24px"><a href="agent.php" class="btn btn-green"><?= snip('tech.hero.cta') ?></a></div>
  </div>
</section>

<!-- ============ T2 整体技术架构（深，悬停/点击展开） ============ -->
<section class="section section-dark">
  <div class="container">
    <div class="text-center">
      <span class="eyebrow reveal"><?= snip('tech.arch.eyebrow') ?></span>
      <h2 class="section-title reveal d1"><?= snip_raw('tech.arch.title') ?></h2>
      <p class="section-sub reveal d2"><?= snip('tech.arch.sub') ?></p>
    </div>
    <div class="arch-diagram reveal d2">
      <div class="arch-io">
        <div class="arch-node" tabindex="0">
          <div class="an-title">靶点 / 科学家</div>
          <div class="an-sub">闭环起点</div>
          <div class="arch-detail"><p>科学家提出靶点与研发目标，平台将其转化为可计算、可验证的设计任务。</p></div>
        </div>
      </div>
      <div class="arch-core">
        <div class="arch-node arch-hub" tabindex="0">
          <div class="an-badge">AI 智能中枢</div>
          <div class="an-title">编排干湿实验 · 统一数据流</div>
          <div class="arch-detail"><ul><li>统一编排 AI 设计与湿实验验证</li><li>数据在干湿之间闭环回流</li><li>全流程可追溯</li></ul></div>
        </div>
        <div class="arch-flow">↓ 编排 ↓</div>
        <div class="arch-pair">
          <div class="arch-node" tabindex="0">
            <div class="an-title">干实验</div>
            <div class="an-sub">AI 从头设计</div>
            <div class="arch-detail"><ul><li>从头序列设计</li><li>结构与亲和力预测</li><li>成药性评估</li></ul></div>
          </div>
          <div class="arch-loop">← 干湿闭环迭代 →</div>
          <div class="arch-node" tabindex="0">
            <div class="an-title">湿实验</div>
            <div class="an-sub">自动化验证</div>
            <div class="arch-detail"><ul><li>VLP 抗原制备</li><li>高通量分离与表征</li><li>数据回流训练</li></ul></div>
          </div>
        </div>
      </div>
      <div class="arch-io">
        <div class="arch-node" tabindex="0">
          <div class="an-title">数据与外部模型</div>
          <div class="an-sub">接入与训练</div>
          <div class="arch-detail"><p>整合正 / 负结合抗体序列数据与外部模型，为 AI 提供精准训练基准。</p></div>
        </div>
      </div>
    </div>
    <div class="text-center" style="margin-top:28px"><a href="agent.php" class="btn btn-outline reveal"><?= snip('tech.arch.cta') ?></a></div>
  </div>
</section>

<!-- ============ T3a Data to train（浅） ============ -->
<section class="section section-light">
  <div class="container">
    <div class="pillar reveal">
      <div class="pillar-text">
        <span class="pillar-ico">🗂️</span>
        <span class="eyebrow green"><?= snip('tech.p1.eyebrow') ?></span>
        <h2 class="section-title"><?= snip_raw('tech.p1.title') ?></h2>
        <p class="section-sub"><?= snip('tech.p1.sub') ?></p>
        <ul class="pillar-points">
          <li><?= snip('tech.p1.point1') ?></li>
          <li><?= snip('tech.p1.point2') ?></li>
          <li><?= snip('tech.p1.point3') ?></li>
        </ul>
        <a href="agent.php" class="link-more"><?= snip('tech.p1.link') ?></a>
      </div>
      <div class="pillar-media"><img src="assets/images/tech-data.png" alt="数据训练" onerror="this.remove()"></div>
    </div>
  </div>
</section>

<!-- ============ T3b AI for science（深） ============ -->
<section class="section section-dark">
  <div class="container">
    <div class="pillar pillar--rev reveal">
      <div class="pillar-text">
        <span class="pillar-ico">🧠</span>
        <span class="eyebrow"><?= snip('tech.p2.eyebrow') ?></span>
        <h2 class="section-title"><?= snip_raw('tech.p2.title') ?></h2>
        <p class="section-sub"><?= snip('tech.p2.sub') ?></p>
        <ul class="pillar-points">
          <li><?= snip('tech.p2.point1') ?></li>
          <li><?= snip('tech.p2.point2') ?></li>
          <li><?= snip('tech.p2.point3') ?></li>
        </ul>
        <a href="agent.php" class="link-more"><?= snip('tech.p2.link') ?></a>
      </div>
      <div class="pillar-media"><img src="assets/images/tech-ai.png" alt="AI 算法" onerror="this.remove()"></div>
    </div>
  </div>
</section>

<!-- ============ T3c Wet lab to validate（浅） ============ -->
<section class="section section-light">
  <div class="container">
    <div class="pillar reveal">
      <div class="pillar-text">
        <span class="pillar-ico">🧪</span>
        <span class="eyebrow green"><?= snip('tech.p3.eyebrow') ?></span>
        <h2 class="section-title"><?= snip_raw('tech.p3.title') ?></h2>
        <p class="section-sub"><?= snip('tech.p3.sub') ?></p>
        <ul class="pillar-points">
          <li><?= snip('tech.p3.point1') ?></li>
          <li><?= snip('tech.p3.point2') ?></li>
          <li><?= snip('tech.p3.point3') ?></li>
        </ul>
        <a href="agent.php" class="link-more"><?= snip('tech.p3.link') ?></a>
      </div>
      <div class="pillar-media"><img src="assets/images/tech-wetlab.png" alt="自动化湿实验" onerror="this.remove()"></div>
    </div>
  </div>
</section>

<!-- ============ T4 经典案例（深） ============ -->
<section class="section section-dark">
  <div class="container">
    <div class="text-center">
      <span class="eyebrow reveal"><?= snip('tech.case.eyebrow') ?></span>
      <h2 class="section-title reveal d1"><?= snip('tech.case.title') ?></h2>
      <p class="section-sub reveal d2"><?= snip('tech.case.sub') ?></p>
    </div>
    <div class="case-dims reveal d2">
      <div class="case-dim">
        <div class="cd-k">难度</div>
        <p>面向难攻克靶点，要求高度特异性、以及复杂构建形式的抗体。</p>
      </div>
      <div class="case-dim">
        <div class="cd-k">速度</div>
        <p>AI 从头生成叠加干湿闭环，显著压缩从设计到验证的研发周期。</p>
      </div>
      <div class="case-dim">
        <div class="cd-k">成本</div>
        <p>减少反复试错、降低后期失败率，从而大幅缩减综合研发成本。</p>
      </div>
    </div>
    <div class="text-center"><a href="agent.php" class="btn btn-green reveal"><?= snip('tech.case.cta') ?></a></div>
  </div>
</section>

<!-- ============ 页脚 ============ -->
<?php include __DIR__ . '/partials/footer.php'; ?>

<script src="assets/js/main.js"></script>
</body>
</html>
