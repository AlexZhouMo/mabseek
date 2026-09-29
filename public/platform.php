<?php
require __DIR__ . '/../app/bootstrap.php';
$active = 'platform';
$navOnDark = false;
$navSolidDark = true;
$contactHref = 'index.php#contact';

// ── content_cards 数据源 ──
$agentMods  = (new Collection('content_cards'))->published("grp='platform_agent_module'");
$dataCards  = (new Collection('content_cards'))->published("grp='platform_data_card'");
$labCaps    = (new Collection('content_cards'))->published("grp='platform_lab_cap'");
$vlpKinds   = (new Collection('content_cards'))->published("grp='platform_vlp_kind'");
$loopSides  = (new Collection('content_cards'))->published("grp='platform_loop_side'");
$loopRings  = (new Collection('content_cards'))->published("grp='platform_loop_ring'");
$caseStats  = (new Collection('content_cards'))->published("grp='platform_case_stat'");
$caseVlps   = (new Collection('content_cards'))->published("grp='platform_case_vlp'");

// ── 纯展示型静态数据（不进后台）──
$DATA_PLOTS = [
    'hero' => [
        'src'          => 'assets/images/platform/data-glp1r-binding.webp',
        'title'        => 'GLP-1R (GPCR)',
        'tags'         => ['血糖调节', '体重控制', '心血管保护'],
        'caption'      => 'Binding to 293T-GLP-1R · 候选抗体结合曲线',
        'detail'       => 'assets/images/platform/data-glp1r-sequences.webp',
        'detail_label' => '抗体候选序列节选',
    ],
    'twin' => [
        ['src'   => 'assets/images/platform/data-cxcr4.webp',
         'title' => 'CXCR4 (GPCR)',
         'tags'  => ['HIV 共受体', '肿瘤微环境', 'NHL/MM/AML 靶点']],
        ['src'   => 'assets/images/platform/data-cd3.webp',
         'title' => 'CD3',
         'tags'  => ['T 细胞标志物', 'TCE 靶点', '肿瘤/自免']],
    ],
];
$LAB_MICRO = [
    ['label' => '皮升级液滴', 'items' => [
        ['src' => 'assets/images/platform/lab-pico-generation.webp', 'caption' => '液滴生成 Droplet generation'],
        ['src' => 'assets/images/platform/lab-pico-injection.webp',  'caption' => '微注入 Pico-injection'],
        ['src' => 'assets/images/platform/lab-pico-fads.webp',       'caption' => '检测分选 FADS'],
    ]],
    ['label' => '微升级液滴', 'items' => [
        ['src' => 'assets/images/platform/lab-micro-generation.webp','caption' => '液滴生成 Micro-droplet generation'],
        ['src' => 'assets/images/platform/lab-micro-injection.webp', 'caption' => '微注入 Micro-injection'],
        ['src' => 'assets/images/platform/lab-micro-sorting.webp',   'caption' => '检测分选 Micro-droplet sorting'],
    ]],
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MabSeek 平台 · Antibody Agent 驱动的抗体发现全流程 | 清华大学医学院</title>
<meta name="description" content="MabSeek 平台由 Antibody Agent 驱动，整合数据、算法与自动化实验，覆盖 GPCR / 离子通道 / 转运体等复杂膜蛋白靶点的抗体发现全流程。">
<link rel="stylesheet" href="assets/css/style.css">
<?php include __DIR__ . '/partials/head-meta.php'; ?>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🧬</text></svg>">
</head>
<body class="platform-page">

<?php include __DIR__ . '/partials/nav.php'; ?>

<!-- ═══════════════ 屏 1 · Hero #hero（深） ═══════════════ -->
<section class="hero" id="hero">
  <div class="hero-bg"></div>
  <div class="container">
    <div class="hero-grid">
      <div>
        <span class="eyebrow reveal"><?= snip('platform.hero.eyebrow', 'MabSeek Platform') ?></span>
        <h1 class="reveal d1"><?= snip_raw('platform.hero.title', '从科学问题到<span class="txt-neon">实验验证</span>抗体') ?></h1>
        <p class="lead reveal d2"><?= snip('platform.hero.lead', '由 Antibody Agent 驱动，整合数据、算法与自动化实验，让抗体发现更高效、更可靠。') ?></p>
        <div class="hero-cta reveal d3">
          <a href="<?= snip('platform.hero.cta1_href', '#agent') ?>" class="btn btn-green btn-lg"><?= snip('platform.hero.cta1', '了解平台') ?></a>
          <a href="<?= snip('platform.hero.cta2_href', 'login.php?next=platform.php&trial=1') ?>" data-trial-cta="1" class="btn btn-purple btn-lg"><?= snip('platform.hero.cta2', '开始试用') ?></a>
        </div>
      </div>
      <div class="reveal d2 hero-flow">
        <div class="pflow">
          <div class="pflow-node pflow-node--dry">研究问题</div>
          <div class="pflow-arrow">→</div>
          <div class="pflow-node pflow-node--brand">Antibody Agent</div>
          <div class="pflow-arrow">→</div>
          <div class="pflow-node pflow-node--dry">自动化实验</div>
          <div class="pflow-arrow">→</div>
          <div class="pflow-node pflow-node--brand">结果与迭代</div>
        </div>
        <div class="hero-photo">
          <img src="<?= snip('platform.hero.photo', 'assets/images/platform/hero-auto-detail.webp') ?>" alt="自动化实验设备特写" loading="lazy">
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════ 屏 2 · Agent #agent（白） ═══════════════ -->
<section class="section section-light" id="agent">
  <div class="container">
    <div class="agent-grid">
      <div class="agent-left">
        <span class="eyebrow reveal"><?= snip('platform.agent.eyebrow', 'Antibody Agent') ?></span>
        <h2 class="section-title reveal d1"><?= snip_raw('platform.agent.title', '从研究问题出发，连接<span class="txt-neon">设计、预测与实验</span>') ?></h2>
        <p class="section-sub reveal d2"><?= snip('platform.agent.lead', 'Antibody Agent 理解研究目标，将任务拆解为可执行的研究步骤。') ?></p>
        <div class="agent-modules reveal d3">
<?php foreach ($agentMods as $m): ?>
          <div class="agent-mod">
            <span class="agent-mod-ico"><?= e($m['icon']) ?></span>
            <div>
              <h4><?= e($m['title']) ?></h4>
              <p><?= e($m['body']) ?></p>
            </div>
          </div>
<?php endforeach; ?>
        </div>
      </div>
      <div class="agent-right reveal d1">
        <img src="<?= snip('platform.agent.screenshot', 'assets/images/platform/agent-screen.webp') ?>" alt="Antibody Agent 运行截图" loading="lazy">
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════ 屏 3 · Data #data（深） ═══════════════ -->
<section class="section section-dark" id="data">
  <div class="container">
    <div class="text-center" style="margin-bottom:32px">
      <span class="eyebrow reveal"><?= snip('platform.data.eyebrow', '真实数据驱动') ?></span>
      <h2 class="section-title reveal d1"><?= snip_raw('platform.data.title', '真实数据驱动<span class="txt-neon">抗体设计与预测</span>') ?></h2>
      <p class="section-sub reveal d2"><?= snip('platform.data.sub', '整合抗体序列、靶点、结构与实验结果，为候选设计、筛选和优化提供依据。') ?></p>
    </div>

    <div class="data-hero-grid reveal">
      <div class="data-side">
<?php foreach ($dataCards as $c): ?>
        <div class="data-side-card">
          <h4><?= e($c['title']) ?></h4>
          <p><?= e($c['body']) ?></p>
        </div>
<?php endforeach; ?>
      </div>
      <div class="data-main">
        <div class="data-flow">
          <span class="data-flow-pill data-flow-pill--in">数据输入</span>
          <span class="data-flow-arrow">→</span>
          <span class="data-flow-pill data-flow-pill--ai">AI 分析</span>
          <span class="data-flow-arrow">→</span>
          <span class="data-flow-pill data-flow-pill--in">候选输出</span>
        </div>
        <div class="data-plot data-plot--hero">
          <div class="data-plot-head">
            <b><?= e($DATA_PLOTS['hero']['title']) ?></b>
            <div class="data-plot-tags">
<?php foreach ($DATA_PLOTS['hero']['tags'] as $t): ?>
              <span class="data-plot-tag"><?= e($t) ?></span>
<?php endforeach; ?>
            </div>
          </div>
          <img src="<?= e($DATA_PLOTS['hero']['src']) ?>" alt="GLP-1R 结合曲线" loading="lazy">
          <p class="data-plot-caption"><?= e($DATA_PLOTS['hero']['caption']) ?></p>
          <details class="data-plot-details">
            <summary><?= e($DATA_PLOTS['hero']['detail_label']) ?></summary>
            <img src="<?= e($DATA_PLOTS['hero']['detail']) ?>" alt="抗体候选序列" loading="lazy">
          </details>
        </div>
      </div>
    </div>

    <div class="data-twin-grid reveal d1">
<?php foreach ($DATA_PLOTS['twin'] as $p): ?>
      <div class="data-plot">
        <div class="data-plot-head">
          <b><?= e($p['title']) ?></b>
          <div class="data-plot-tags">
<?php foreach ($p['tags'] as $t): ?>
            <span class="data-plot-tag"><?= e($t) ?></span>
<?php endforeach; ?>
          </div>
        </div>
        <img src="<?= e($p['src']) ?>" alt="<?= e($p['title']) ?>" loading="lazy">
      </div>
<?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════ 屏 4a · Lab #lab 全流程实验平台（白） ═══════════════ -->
<section class="section section-light" id="lab">
  <div class="container">
    <div class="lab-hero-photo reveal">
      <img src="<?= snip('platform.lab.hero_photo', 'assets/images/platform/lab-auto-room.webp') ?>" alt="自动化抗体发现实验平台全景" loading="lazy">
    </div>

    <div class="lab-grid" style="margin-top:28px">
      <div>
        <span class="eyebrow reveal"><?= snip('platform.lab.eyebrow', '全流程抗体发现实验平台') ?></span>
        <h2 class="section-title reveal d1"><?= snip_raw('platform.lab.title', 'VLP 天然构象呈递 × <span class="txt-neon">高通量自动化筛选</span>') ?></h2>
      </div>
      <div class="lab-caps reveal d1">
<?php foreach ($labCaps as $c): ?>
        <div class="lab-cap">
          <h4><?= e($c['title']) ?></h4>
          <p><?= e($c['body']) ?></p>
        </div>
<?php endforeach; ?>
      </div>
    </div>

    <div class="lab-mf" style="margin-top:40px">
      <h3 class="lab-mf-title reveal"><?= snip('platform.lab.mf_title', '微流控液滴技术平台') ?></h3>
<?php foreach ($LAB_MICRO as $row): ?>
      <div class="lab-mf-row reveal d1">
        <div class="lab-mf-label"><?= e($row['label']) ?></div>
        <div class="lab-mf-items">
<?php foreach ($row['items'] as $it): ?>
          <figure class="lab-mf-item">
            <img src="<?= e($it['src']) ?>" alt="<?= e($it['caption']) ?>" loading="lazy">
            <figcaption><?= e($it['caption']) ?></figcaption>
          </figure>
<?php endforeach; ?>
        </div>
      </div>
<?php endforeach; ?>
    </div>

    <!-- ═════ 屏 4b · VLP 钓饵技术（同白，虚线接续） ═════ -->
    <div class="subsection vlp-subsection" style="margin-top:40px">
      <div class="vlp-grid">
        <div class="vlp-left reveal">
          <img src="<?= snip('platform.vlp.diagram', 'assets/images/platform/vlp-diagram.webp') ?>" alt="VLP 钓饵技术示意" loading="lazy">
        </div>
        <div class="vlp-right">
          <h2 class="section-title reveal"><?= snip('platform.vlp.title', 'VLP 钓饵技术') ?></h2>
          <p class="vlp-lead reveal d1"><?= snip('platform.vlp.lead', '让复杂抗原的展示更接近天然状态') ?></p>
          <p class="vlp-body reveal d2"><?= snip('platform.vlp.body') ?></p>
          <h3 class="vlp-mid reveal d3"><?= snip('platform.vlp.mid_title', '尤其适用于传统抗原制备困难的靶点') ?></h3>
          <div class="vlp-kinds reveal d3">
<?php foreach ($vlpKinds as $k): ?>
            <div class="vlp-kind">
              <h4><?= e($k['title']) ?></h4>
              <p><?= e($k['body']) ?></p>
            </div>
<?php endforeach; ?>
          </div>
          <div class="vlp-targets reveal d3">
            <span class="vlp-targets-label"><?= snip('platform.vlp.targets_label', '代表性靶点') ?>：</span>
            <span class="vlp-targets-value"><?= snip('platform.vlp.targets', 'GPCR ｜ 离子通道 ｜ 转运体') ?></span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════ 屏 5 · Loop #loop 干湿闭环（深） ═══════════════ -->
<section class="section section-dark" id="loop">
  <div class="container">
    <div class="text-center" style="margin-bottom:32px">
      <span class="eyebrow reveal"><?= snip('platform.loop.eyebrow', '干湿闭环') ?></span>
      <h2 class="section-title reveal d1"><?= snip_raw('platform.loop.title', '从 <span class="txt-neon">Antibody Agent</span> 到实验验证，一条完整的<span class="txt-neon">抗体发现闭环</span>') ?></h2>
      <p class="section-sub reveal d2"><?= snip('platform.loop.sub', 'AI 设计与实验结果双向回流，让每一轮实验结果成为下一轮设计与优化的依据。') ?></p>
    </div>

    <div class="loop-grid">
      <div class="loop-side">
<?php foreach ($loopSides as $s): ?>
        <div class="loop-side-card reveal">
          <h4><?= e($s['title']) ?></h4>
          <p><?= e($s['body']) ?></p>
        </div>
<?php endforeach; ?>
      </div>
      <div class="loop-ring reveal d1">
        <div class="loop-ring-center">
          <img src="<?= snip('platform.loop.center_logo', 'assets/images/logo.webp') ?>" alt="MabSeek" onerror="this.style.display='none'">
          <span><?= snip('platform.loop.center_label', 'MabSeek 抗体求索') ?></span>
        </div>
<?php
$ringCount = count($loopRings) ?: 5;
foreach ($loopRings as $i => $n):
    $extra = json_decode($n['extra'] ?: '{}', true) ?: [];
    $side = $extra['side'] ?? 'dry';
    // rotate(--ang) 后 translateY(-165) 沿半径外推：--ang=0 时节点在 12 点方向，
    // 起点 = 「研究目标」在 12 点，顺时针分布 5 个节点。
    $angle = $i * (360 / $ringCount);
?>
        <div class="loop-ring-node loop-ring-node--<?= e($side) ?>" style="--ang:<?= $angle ?>deg">
          <h4><?= e($n['title']) ?></h4>
          <p><?= e($n['body']) ?></p>
        </div>
<?php endforeach; ?>
      </div>
    </div>

    <div class="loop-tempo reveal d2" style="margin-top:28px">
      <?= snip('platform.loop.tempo', '设计 → 验证 → 分析 → 优化') ?>
    </div>
  </div>
</section>

<!-- ═══════════════ 屏 6 · Case #case 代表性成果（白） ═══════════════ -->
<section class="section section-light" id="case">
  <div class="container">
    <div class="text-center" style="margin-bottom:32px">
      <span class="eyebrow reveal"><?= snip('platform.case.eyebrow', '代表性成果与平台验证') ?></span>
    </div>

    <!-- 上·临床转化 -->
    <div class="case-block case-block--clinical">
      <div class="case-photo reveal">
        <img src="<?= snip('platform.case.clinical_photo', 'assets/images/platform/case-clinical.webp') ?>" alt="安巴韦单抗 / 罗米司韦单抗" loading="lazy">
      </div>
      <div class="case-text">
        <h3 class="reveal"><?= snip('platform.case.clinical_title', '从抗体发现到临床转化') ?></h3>
        <div class="case-sub reveal d1"><?= snip('platform.case.clinical_sub', '安巴韦单抗 / 罗米司韦单抗') ?></div>
        <div class="case-stats reveal d2">
<?php foreach ($caseStats as $s): ?>
          <div class="case-stat">
            <div class="case-stat-num"><?= e($s['title']) ?></div>
            <div class="case-stat-label"><?= e($s['body']) ?></div>
          </div>
<?php endforeach; ?>
        </div>
        <p class="case-note reveal d3"><?= snip('platform.case.clinical_note') ?></p>
      </div>
    </div>

    <!-- 下·复杂膜蛋白靶点实践 -->
    <div class="case-block case-block--vlp" style="margin-top:48px">
      <div class="case-text">
        <h3 class="reveal"><?= snip('platform.case.vlp_title', '复杂膜蛋白靶点的抗体发现实践') ?></h3>
        <div class="case-vlp-rows reveal d1">
<?php foreach ($caseVlps as $v): ?>
          <div class="case-vlp-row">
            <div class="case-vlp-title"><?= e($v['title']) ?></div>
            <div class="case-vlp-body"><?= e($v['body']) ?></div>
          </div>
<?php endforeach; ?>
        </div>
      </div>
      <div class="case-photo reveal d1">
        <img src="<?= snip('platform.case.vlp_photo', 'assets/images/platform/case-membrane-targets.webp') ?>" alt="复杂膜蛋白靶点" loading="lazy">
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════ 屏 7 · Try #try 试用 CTA（纯黑） ═══════════════ -->
<section class="try-cta" id="try">
  <div class="container">
    <div class="try-brand reveal"><span class="try-brand-mab"><?= snip('platform.try.brand_mab', 'Mab') ?></span><span class="try-brand-seek"><?= snip('platform.try.brand_seek', 'Seek') ?></span></div>
    <h2 class="try-title reveal d1"><?= snip('platform.try.title', '让抗体发现，从一个问题开始') ?></h2>
    <p class="try-sub reveal d2"><?= snip('platform.try.sub', '从研究问题出发，通过 Antibody Agent 连接设计、预测与实验。') ?></p>
    <a href="<?= snip('platform.try.cta_href', 'login.php?next=platform.php&trial=1') ?>" data-trial-cta="1" class="btn btn-green btn-lg try-btn reveal d3"><?= snip('platform.try.cta', '开始试用 →') ?></a>
  </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>

<script src="assets/js/main.js"></script>
</body>
</html>
