<?php
require __DIR__ . '/../app/bootstrap.php';
$active = 'platform'; $navOnDark = false; $navSolidDark = true; $contactHref = 'index.php#contact';
$caps   = (new Collection('content_cards'))->published("grp='agent_capability'");
$matrix = (new Collection('content_cards'))->published("grp='agent_matrix'");
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MabSeek 平台 · AI 驱动的抗体发现全流程 | 清华大学医学院</title>
<meta name="description" content="MabSeek 平台合并 Antibody Agent 与湿实验闭环，一句话需求驱动 AI 抗体设计、亲和力预测与实验规划，覆盖 GPCR 等复杂膜蛋白靶点。">
<link rel="stylesheet" href="assets/css/style.css">
<?php include __DIR__ . '/partials/head-meta.php'; ?>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🧬</text></svg>">
<style>
/* ---- Agent 页专属 ---- */
.chat-ui { background:#fff; border:1px solid var(--line); border-radius:var(--radius-lg); box-shadow:var(--sh-lg); overflow:hidden; }
.chat-top { display:flex; align-items:center; gap:10px; padding:14px 18px; border-bottom:1px solid var(--line); }
.chat-top .brand-dot { width:30px;height:30px;border-radius:9px;background:var(--grad-brand);display:grid;place-items:center;color:#fff;font-size:14px; }
.chat-top .free { margin-left:auto; font-size:12px; color:var(--ink-3); background:var(--bg-soft); padding:5px 12px;border-radius:999px; }
#chat-feed { height:360px; overflow-y:auto; padding:20px; display:flex; flex-direction:column; gap:14px; background:linear-gradient(180deg,#fbfbff,#fff); }
.msg { display:flex; gap:10px; align-items:flex-start; max-width:92%; }
.msg.user { align-self:flex-end; flex-direction:row-reverse; }
.msg-av { width:30px;height:30px;border-radius:9px;flex:0 0 auto;display:grid;place-items:center;font-size:12px;font-weight:700;color:#fff;background:var(--grad-purple); }
.msg.user .msg-av { background:var(--grad-green); color:#04352a; }
.msg-bubble { background:#fff;border:1px solid var(--line);border-radius:14px;padding:11px 15px;font-size:14px;line-height:1.6;box-shadow:var(--sh-sm); min-height:20px; }
.msg.user .msg-bubble { background:var(--grad-purple); color:#fff; border:0; }
.msg-bubble.is-step { background:var(--purple-050); border-color:var(--purple-100); color:var(--ink-2); font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:13px; }
.msg-bubble.is-result { background:var(--green-100); border-color:#b6f2e2; color:#065f46; font-weight:600; }
.dots i { display:inline-block;width:6px;height:6px;border-radius:50%;background:var(--purple-400);margin:0 2px;animation:blink 1.2s infinite; }
.dots i:nth-child(2){animation-delay:.2s} .dots i:nth-child(3){animation-delay:.4s}
@keyframes blink { 0%,80%,100%{opacity:.3} 40%{opacity:1} }
.chat-input { display:flex; align-items:center; gap:10px; padding:14px 18px; border-top:1px solid var(--line); }
.chat-input .box { flex:1; border:1px solid var(--line); border-radius:12px; padding:11px 14px; font-size:14px; color:var(--ink-3); background:var(--bg-soft); }
.chat-input .kb { font-size:12px; color:var(--purple); background:var(--purple-050); padding:6px 11px; border-radius:8px; font-weight:700; }
.chat-input .send { width:38px;height:38px;border-radius:10px;background:var(--grad-purple);color:#fff;display:grid;place-items:center;cursor:pointer;flex:0 0 auto; }

/* 干湿闭环流程 */
.flow { display:grid; grid-template-columns:repeat(5,1fr); gap:12px; align-items:stretch; }
.flow-step { position:relative; background:#fff;border:1px solid var(--line);border-radius:var(--radius);padding:22px 12px;text-align:center;box-shadow:var(--sh-sm); }
.flow-step h4 { font-size:15px; white-space:nowrap; }
.flow-step p { font-size:11px;color:var(--ink-3);margin-top:6px; white-space:nowrap; }
.flow-step .tag-mini { font-size:11px;font-weight:700;padding:3px 9px;border-radius:999px;display:inline-block;margin-top:10px; }
.flow-arrow { position:absolute; right:-13px; top:50%; transform:translateY(-50%); color:var(--purple-400); font-size:18px; z-index:2; }
.flow-step:last-child .flow-arrow { display:none; }
.dry { background:linear-gradient(180deg,#fff,#f7f4ff); }
.wet { background:linear-gradient(180deg,#fff,#f0fbf7); }

/* pinned agents */
.agent-chip { background:#fff;border:1px solid var(--line);border-radius:var(--radius);padding:18px;display:flex;gap:12px;align-items:flex-start;box-shadow:var(--sh-sm);transition:.25s; }
.agent-chip:hover { transform:translateY(-4px); box-shadow:var(--sh); border-color:var(--purple-100); }
.agent-chip.active { border-color:var(--purple); box-shadow:var(--sh-purple); }
.agent-chip .ai { width:42px;height:42px;border-radius:11px;display:grid;place-items:center;font-size:20px;flex:0 0 auto; }
.agent-chip h4 { font-size:15px; } .agent-chip p { font-size:12.5px;color:var(--ink-3);margin-top:3px; }
</style>
</head>
<body>

<!-- 导航 -->
<?php include __DIR__ . '/partials/nav.php'; ?>

<!-- Hero + 对话演示 -->
<section class="hero" id="try">
  <div class="hero-bg"></div>
  <canvas id="hero-canvas"></canvas>
  <div class="container">
    <div class="hero-grid">
      <div>
        <span class="eyebrow reveal"><?= snip('agent.hero.eyebrow') ?></span>
        <h1 class="reveal d1"><?= snip_raw('agent.hero.title') ?></h1>
        <p class="lead reveal d2"><?= snip('agent.hero.lead') ?></p>
        <div class="hero-cta reveal d3">
          <a href="login.php?next=platform.php&trial=1" data-trial-cta="1" class="btn btn-purple btn-lg"><?= snip('platform.hero.cta1', '开始试用') ?></a>
          <a href="#modules" class="btn btn-outline btn-lg"><?= snip('platform.hero.cta2', '了解详情') ?></a>
        </div>
        <div class="hero-note reveal d4"><span class="dot" style="width:8px;height:8px;border-radius:50%;background:var(--green);display:inline-block"></span> <?= snip('agent.hero.note') ?></div>
      </div>
      <div class="reveal d2">
        <div class="chat-ui">
          <div class="chat-top">
            <span class="brand-dot">🧬</span><b style="font-size:14px">Antibody Agent</b>
            <span class="free">Knowledge Base · 抗体发现</span>
          </div>
          <div id="chat-feed"></div>
          <div class="chat-input">
            <span class="kb">📚 知识库</span>
            <div class="box">@antibody_agent 描述你的靶点…</div>
            <span class="send" data-demo="正式版将接入实时推理，本页为流程演示">↑</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 屏 2 · Antibody Agent 四模块 -->
<section class="section section-light" id="modules">
  <div class="container">
    <div class="text-center" style="margin-bottom:44px">
      <span class="eyebrow reveal"><?= snip('agent.cap.eyebrow') ?></span>
      <h2 class="section-title reveal d1"><?= snip_raw('agent.cap.title') ?></h2>
    </div>
    <div class="grid-4">
<?php foreach ($caps as $i => $c): $rev = $i ? ' d' . $i : ''; ?>
      <div class="card reveal<?= $rev ?>" style="text-align:center"><h3><?= e($c['title']) ?></h3><p><?= e($c['body']) ?></p></div>
<?php endforeach; ?>
    </div>
  </div>
</section>

<!-- 屏 3 · 真实数据驱动（GLP-1R / CXCR4 / CD3，2b-1 用 case-anim 3 动图占位）-->
<section class="section section-dark" id="data">
  <div class="container">
    <div class="text-center" style="margin-bottom:8px">
      <span class="eyebrow reveal"><?= snip('agent.case.eyebrow') ?></span>
      <h2 class="section-title reveal d1"><?= snip_raw('agent.case.title') ?></h2>
      <p class="section-sub reveal d2"><?= snip('agent.case.sub') ?></p>
    </div>
    <div class="case-anim-grid reveal d2">
      <div class="case-anim">
        <h3>一句话 → 候选序列</h3>
        <p>用自然语言描述靶点与目标，Agent 逐步生成候选序列。</p>
        <div class="case-stage" aria-hidden="true">
          <div class="ca1-prompt"></div>
          <div class="ca1-seq"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
        </div>
      </div>
      <div class="case-anim">
        <h3>亲和力虚拟筛选排序</h3>
        <p>动手实验前完成虚拟打分与排序，把候选按优先级重排。</p>
        <div class="case-stage" aria-hidden="true">
          <div class="ca2-bars"><span class="ca2-bar"></span><span class="ca2-bar"></span><span class="ca2-bar"></span><span class="ca2-bar"></span><span class="ca2-bar"></span></div>
        </div>
      </div>
      <div class="case-anim">
        <h3>结构 · 表位识别</h3>
        <p>结构建模与表位识别，理解「结合在哪里、为什么结合」。</p>
        <div class="case-stage" aria-hidden="true">
          <div class="ca3-mol"><span class="ca3-anti"></span><span class="ca3-epi"></span></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 屏 4 · 全流程实验平台（VLP + 微流控 6 卡占位，2b-2 补实拍）-->
<section class="section section-light" id="lab">
  <div class="container">
    <div class="text-center" style="margin-bottom:36px">
      <span class="eyebrow reveal"><?= snip('tech.p3.eyebrow', 'Wet lab to validate') ?></span>
      <h2 class="section-title reveal d1"><?= snip_raw('tech.p3.title') ?></h2>
      <p class="section-sub reveal d2"><?= snip('tech.p3.sub') ?></p>
    </div>
    <div class="grid-3" style="gap:20px">
<?php
  $labItems = [
    ['1', '🧬'], ['2', '🧪'], ['3', '📐'],
    ['4', '🔬'], ['5', '⚛️'], ['6', '💧'],
  ];
  foreach ($labItems as $i => [$n, $icon]):
    $rev = $i ? ' d' . min($i, 4) : '';
?>
      <div class="card reveal<?= $rev ?>" style="text-align:center;padding:26px 20px">
        <div style="font-size:36px;line-height:1;margin-bottom:10px"><?= $icon ?></div>
        <h3 style="font-size:16px;font-weight:700;margin:0 0 6px"><?= snip('platform.lab.item' . $n . '.title', 'lab item ' . $n) ?></h3>
        <p style="font-size:13px;color:var(--ink-3);margin:0;line-height:1.55"><?= snip('platform.lab.item' . $n . '.body', '') ?></p>
      </div>
<?php endforeach; ?>
    </div>
  </div>
</section>

<!-- 干湿闭环 -->
<section class="section bg-soft" id="flow">
  <div class="container">
    <div class="text-center" style="margin-bottom:44px">
      <span class="eyebrow green reveal"><?= snip('agent.flow.eyebrow') ?></span>
      <h2 class="section-title reveal d1"><?= snip_raw('agent.flow.title') ?></h2>
      <p class="section-sub reveal d2"><?= snip('agent.flow.sub') ?></p>
    </div>
    <svg class="ring-flow reveal" viewBox="0 0 600 600" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="MabSeek 干湿闭环流程">
      <defs>
        <marker id="arrow" viewBox="0 -5 10 10" refX="8" refY="0" markerWidth="6" markerHeight="6" orient="auto">
          <path d="M0,-5L10,0L0,5" fill="var(--purple-400)"/>
        </marker>
        <linearGradient id="gradBrand" x1="0" y1="1" x2="1" y2="0">
          <stop offset="0%" stop-color="#6D3BEB"/>
          <stop offset="55%" stop-color="#5b6bff"/>
          <stop offset="100%" stop-color="#00E0A4"/>
        </linearGradient>
      </defs>
      <circle cx="300" cy="300" r="80" fill="var(--purple-050)" stroke="var(--purple-100)" stroke-width="2"/>
      <text x="300" y="295" text-anchor="middle" font-size="18" font-weight="700" fill="var(--purple)">MabSeek</text>
      <text x="300" y="320" text-anchor="middle" font-size="14" fill="var(--ink-3)">干湿闭环</text>
<?php
  $nodes = [
    ['一句话需求', '🎯', 'dry'],
    ['AI 设计筛选', '🧠', 'dry'],
    ['表达纯化',   '🧪', 'wet'],
    ['功能验证',   '🔬', 'wet'],
    ['结果交付',   '📊', 'brand'],
  ];
  $cx = 300; $cy = 300; $r = 220;
  foreach ($nodes as $i => [$label, $icon, $kind]):
    $angle = (-90 + $i * 72) * M_PI / 180;
    $x = $cx + $r * cos($angle);
    $y = $cy + $r * sin($angle);
    $fill     = $kind === 'dry' ? 'var(--purple-050)' : ($kind === 'wet' ? 'var(--green-100)' : 'url(#gradBrand)');
    $stroke   = $kind === 'dry' ? 'var(--purple-400)' : ($kind === 'wet' ? '#06a97c' : 'var(--purple)');
    $textFill = $kind === 'brand' ? '#fff' : 'var(--ink)';
?>
      <g class="ring-node ring-node--<?= $kind ?>">
        <circle cx="<?= round($x) ?>" cy="<?= round($y) ?>" r="54" fill="<?= $fill ?>" stroke="<?= $stroke ?>" stroke-width="2.5"/>
        <text x="<?= round($x) ?>" y="<?= round($y - 6) ?>" text-anchor="middle" font-size="22"><?= $icon ?></text>
        <text x="<?= round($x) ?>" y="<?= round($y + 22) ?>" text-anchor="middle" font-size="11" font-weight="700" fill="<?= $textFill ?>"><?= $label ?></text>
      </g>
<?php endforeach; ?>
<?php
  for ($i = 0; $i < 5; $i++):
    $a1 = (-90 + $i * 72) * M_PI / 180;
    $a2 = (-90 + ($i + 1) * 72) * M_PI / 180;
    $x1 = $cx + ($r - 60) * cos($a1); $y1 = $cy + ($r - 60) * sin($a1);
    $x2 = $cx + ($r - 60) * cos($a2); $y2 = $cy + ($r - 60) * sin($a2);
    $arc = $r - 40;
?>
      <path d="M<?= round($x1) ?>,<?= round($y1) ?> A<?= $arc ?>,<?= $arc ?> 0 0 1 <?= round($x2) ?>,<?= round($y2) ?>" fill="none" stroke="var(--purple-400)" stroke-width="2" marker-end="url(#arrow)" stroke-dasharray="4 3" opacity=".7"/>
<?php endfor; ?>
    </svg>
    <div id="wetlab" class="reveal d1" style="margin-top:32px;display:grid;grid-template-columns:1fr 1fr;gap:28px;align-items:center;background:#0d1122;border:1px solid var(--line-dark);border-radius:var(--radius-lg);padding:32px;box-shadow:var(--sh-lg)">
      <div>
        <h3 style="font-size:24px;color:#fff"><?= snip('agent.wetlab.title') ?></h3>
        <p style="color:var(--ink-on-dark-2);margin-top:10px"><?= snip('agent.wetlab.body') ?></p>
        <div class="tag-row" style="margin-top:16px"><span class="tag">表达纯化</span><span class="tag">亲和力测定</span><span class="tag green">功能验证</span><span class="tag">结构解析</span></div>
        <a href="#" class="btn btn-green" style="margin-top:20px" data-demo="正式版将开放在线下单">🧪 一键下单湿实验</a>
      </div>
      <div style="border-radius:var(--radius);overflow:hidden;box-shadow:var(--sh)"><img src="assets/images/antibody-structure.webp" alt="抗体结构" onerror="this.parentElement.style.display='none'"></div>
    </div>
  </div>
</section>

<!-- 屏 6 · 临床与学术成果（左 4 条文字 + 右 agent.matrix 8 chips 占位）-->
<section class="section section-light" id="clinical">
  <div class="container">
    <div class="text-center" style="margin-bottom:34px">
      <span class="eyebrow reveal"><?= snip('platform.clinical.eyebrow', '临床与学术成果') ?></span>
      <h2 class="section-title reveal d1"><?= snip_raw('platform.clinical.title', '真实世界数据 · 复杂靶点覆盖') ?></h2>
      <p class="section-sub reveal d2"><?= snip('platform.clinical.sub', '已支撑多个新药项目推进临床阶段，覆盖 GPCR、离子通道等复杂膜蛋白靶点。') ?></p>
    </div>
    <div style="display:grid;grid-template-columns:1fr;gap:14px;max-width:760px;margin:0 auto">
<?php for ($n = 1; $n <= 4; $n++):
  $default = ['▸ 支撑多家药企的抗体发现 pipeline',
              '▸ 已推进多个候选进入 IND-enabling 阶段',
              '▸ 覆盖 GLP-1R / CXCR4 / CD3 等复杂膜蛋白',
              '▸ Nature / Cell 系列论文（清华医学院）'][$n - 1];
?>
      <div class="reveal<?= $n > 1 ? ' d' . min($n - 1, 3) : '' ?>" style="padding:14px 18px;background:var(--bg-soft);border-radius:var(--radius);font-size:15px;color:var(--ink-2)"><?= snip('platform.clinical.item' . $n, $default) ?></div>
<?php endfor; ?>
    </div>
  </div>
</section>

<!-- 智能体矩阵（作为能力矩阵占位；2b-2 换为 3D 膜蛋白结构）-->
<section class="section section-light" id="agents">
  <div class="container">
    <div style="margin-bottom:34px">
      <span class="eyebrow reveal"><?= snip('agent.matrix.eyebrow') ?></span>
      <h2 class="section-title reveal d1"><?= snip('agent.matrix.title') ?></h2>
      <p class="section-sub reveal d2"><?= snip('agent.matrix.sub') ?></p>
    </div>
<?php $rows = array_chunk($matrix, 4); foreach ($rows as $ri => $row): ?>
    <div class="grid-4"<?= $ri === 1 ? ' style="margin-top:16px"' : '' ?>>
<?php foreach ($row as $j => $m): $ex = json_decode($m['extra'] ?: '{}', true);
  $cls = 'agent-chip' . (!empty($ex['active']) ? ' active' : '') . ' reveal' . ($j ? ' d' . $j : ''); ?>
      <div class="<?= $cls ?>"><span class="ai" style="background:<?= e($ex['ai_bg'] ?? '') ?>"><?= e($m['icon']) ?></span><div><h4><?= e($m['title']) ?></h4><p><?= e($m['body']) ?></p><?php if (!empty($ex['link_href']) && preg_match('#^https?://#i', $ex['link_href'])): ?><a class="chip-link" href="<?= e($ex['link_href']) ?>" target="_blank" rel="noopener"><?= e($ex['link_text']) ?></a><?php endif; ?></div></div>
<?php endforeach; ?>
    </div>
<?php endforeach; ?>
  </div>
</section>

<!-- 屏 7 · CTA 黑底试用 -->
<section class="section-sm" id="cta">
  <div class="container">
    <div class="reveal agent-cta">
      <h2 style="font-size:clamp(26px,3.6vw,38px);color:#fff;position:relative;z-index:1"><?= snip('agent.cta.title') ?></h2>
      <p style="color:var(--ink-on-dark-2);font-size:17px;margin:14px auto 26px;max-width:560px;position:relative;z-index:1"><?= snip('agent.cta.sub') ?></p>
      <a href="login.php?next=platform.php&trial=1" data-trial-cta="1" class="btn btn-green btn-lg" style="position:relative;z-index:1"><?= snip('agent.cta.btn') ?></a>
    </div>
  </div>
</section>

<!-- 页脚 -->
<?php include __DIR__ . '/partials/footer.php'; ?>

<script src="assets/js/main.js"></script>
<script src="assets/js/agent-demo.js"></script>
<script src="assets/js/agent-cases.js"></script>
</body>
</html>
