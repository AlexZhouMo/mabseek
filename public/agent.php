<?php
require __DIR__ . '/../app/bootstrap.php';
$active = 'agent'; $navOnDark = false; $contactHref = 'index.php#contact';
$caps   = (new Collection('content_cards'))->published("grp='agent_capability'");
$matrix = (new Collection('content_cards'))->published("grp='agent_matrix'");
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Antibody Agent · 专属领域 AI 智能体 | MabSeek</title>
<meta name="description" content="Antibody Agent：集文献检索问答、抗体序列设计、亲和力预测、结构分析于一体，打通 MabSeek 湿实验平台，一句话出方案，线下实验室直接交付结果，真正实现干湿闭环。">
<link rel="stylesheet" href="assets/css/style.css">
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
.flow-step { position:relative; background:#fff;border:1px solid var(--line);border-radius:var(--radius);padding:22px 16px;text-align:center;box-shadow:var(--sh-sm); }
.flow-step .n { width:40px;height:40px;border-radius:12px;margin:0 auto 12px;display:grid;place-items:center;font-size:20px;color:#fff; }
.flow-step h4 { font-size:15px; } .flow-step p { font-size:12.5px;color:var(--ink-3);margin-top:6px; }
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
          <a href="#flow" class="btn btn-green btn-lg"><?= snip('agent.hero.cta1') ?></a>
          <a href="#agents" class="btn btn-outline btn-lg"><?= snip('agent.hero.cta2') ?></a>
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

<!-- 核心能力 -->
<section class="section section-light">
  <div class="container">
    <div class="text-center" style="margin-bottom:44px">
      <span class="eyebrow reveal"><?= snip('agent.cap.eyebrow') ?></span>
      <h2 class="section-title reveal d1"><?= snip_raw('agent.cap.title') ?></h2>
    </div>
    <div class="grid-4">
<?php foreach ($caps as $i => $c): $ex = json_decode($c['extra'] ?: '{}', true); $icoCls = !empty($ex['ico_class']) ? ' ' . $ex['ico_class'] : ''; $rev = $i ? ' d' . $i : ''; ?>
      <div class="card reveal<?= $rev ?>"><div class="ico<?= e($icoCls) ?>"><?= e($c['icon']) ?></div><h3><?= e($c['title']) ?></h3><p><?= e($c['body']) ?></p></div>
<?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ A3 案例示范（深，循环动图，新增） ============ -->
<section class="section section-dark">
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

<!-- 干湿闭环 -->
<section class="section bg-soft" id="flow">
  <div class="container">
    <div class="text-center" style="margin-bottom:44px">
      <span class="eyebrow green reveal"><?= snip('agent.flow.eyebrow') ?></span>
      <h2 class="section-title reveal d1"><?= snip_raw('agent.flow.title') ?></h2>
      <p class="section-sub reveal d2"><?= snip('agent.flow.sub') ?></p>
    </div>
    <div class="flow reveal">
      <div class="flow-step dry"><div class="n" style="background:var(--grad-purple)">💬</div><h4>一句话需求</h4><p>描述靶点与目标，Agent 理解任务</p><span class="tag-mini" style="background:var(--purple-050);color:var(--purple)">干 · AI</span><span class="flow-arrow">→</span></div>
      <div class="flow-step dry"><div class="n" style="background:var(--grad-purple)">🧬</div><h4>AI 设计筛选</h4><p>序列设计 + 亲和力预测 + 结构分析</p><span class="tag-mini" style="background:var(--purple-050);color:var(--purple)">干 · AI</span><span class="flow-arrow">→</span></div>
      <div class="flow-step wet"><div class="n" style="background:var(--grad-green);color:#04352a">🧪</div><h4>表达纯化</h4><p>一键下单，线下实验室执行</p><span class="tag-mini" style="background:var(--green-100);color:#06a97c">湿 · 实验</span><span class="flow-arrow">→</span></div>
      <div class="flow-step wet"><div class="n" style="background:var(--grad-green);color:#04352a">🔬</div><h4>功能验证</h4><p>结合活性与功能实验验证</p><span class="tag-mini" style="background:var(--green-100);color:#06a97c">湿 · 实验</span><span class="flow-arrow">→</span></div>
      <div class="flow-step" style="background:var(--grad-brand)"><div class="n" style="background:rgba(255,255,255,.2)">📦</div><h4 style="color:#fff">结果交付</h4><p style="color:rgba(255,255,255,.85)">数据回流，与 AI 预测双向溯源</p><span class="tag-mini" style="background:rgba(255,255,255,.2);color:#fff">闭环</span></div>
    </div>
    <div id="wetlab" class="reveal d1" style="margin-top:32px;display:grid;grid-template-columns:1fr 1fr;gap:28px;align-items:center;background:#fff;border:1px solid var(--line);border-radius:var(--radius-lg);padding:32px;box-shadow:var(--sh)">
      <div>
        <h3 style="font-size:24px"><?= snip('agent.wetlab.title') ?></h3>
        <p style="color:var(--ink-3);margin-top:10px"><?= snip('agent.wetlab.body') ?></p>
        <div class="tag-row" style="margin-top:16px"><span class="tag">表达纯化</span><span class="tag">亲和力测定</span><span class="tag green">功能验证</span><span class="tag">结构解析</span></div>
        <a href="#" class="btn btn-green" style="margin-top:20px" data-demo="正式版将开放在线下单">🧪 一键下单湿实验</a>
      </div>
      <div style="border-radius:var(--radius);overflow:hidden;box-shadow:var(--sh)"><img src="assets/images/antibody-structure.png" alt="抗体结构" onerror="this.parentElement.style.display='none'"></div>
    </div>
  </div>
</section>

<!-- 智能体矩阵 -->
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

<!-- CTA -->
<section class="section-sm">
  <div class="container">
    <div class="reveal" style="background:var(--grad-brand);border-radius:var(--radius-lg);padding:56px 40px;text-align:center;color:#fff;box-shadow:var(--sh-lg)">
      <h2 style="font-size:clamp(26px,3.6vw,38px)"><?= snip('agent.cta.title') ?></h2>
      <p style="opacity:.92;font-size:17px;margin:14px auto 26px;max-width:560px"><?= snip('agent.cta.sub') ?></p>
      <a href="#try" class="btn btn-green btn-lg"><?= snip('agent.cta.btn') ?></a>
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
