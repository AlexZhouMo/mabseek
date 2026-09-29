<?php
require __DIR__ . '/../app/bootstrap.php';
member_check();                                   // 未登录跳 login.php
$active = 'education'; $navOnDark = false; $navSolidDark = true; $contactHref = 'index.php#contact';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>教育 · 《疫苗的力量》元视频课程 | MabSeek</title>
<meta name="description" content="MabSeek 教育板块：《疫苗的力量》元视频课程与往期回顾，重塑科研教育范式。">
<link rel="stylesheet" href="assets/css/style.css">
<?php include __DIR__ . '/partials/head-meta.php'; ?>
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🧬</text></svg>">
<style>
/* ---- 教育页专属组件 ---- */
.course-banner { position: relative; border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--sh-lg); }
.course-banner img { width: 100%; height: auto; max-height: 460px; object-fit: contain; background:#0d1030; display:block; }
.info-card { background:#fff; border:1px solid var(--line); border-radius: var(--radius); padding: 26px; box-shadow: var(--sh); }
.info-card .head { display:flex; align-items:center; gap:12px; margin-bottom:14px; }
.info-card .head .ico { width:44px;height:44px;border-radius:12px;display:grid;place-items:center;background:var(--purple-050);color:var(--purple);font-size:20px; }
.info-card h3 { font-size:18px; }
.info-card ul li { display:flex; gap:8px; color:var(--ink-2); font-size:14px; margin-bottom:9px; }
.info-card ul li::before { content:"▸"; color:var(--green); font-weight:700; }
@media (max-width:720px){ .edu-two-col{ grid-template-columns:1fr !important; } }

/* 折叠资源 */
.accordion { border:1px solid var(--line); border-radius:var(--radius); overflow:hidden; background:#fff; }
.acc-item { border-bottom:1px solid var(--line); }
.acc-item:last-child { border-bottom:0; }
.acc-head { padding:18px 22px; display:flex; align-items:center; justify-content:space-between; cursor:pointer; font-weight:700; }
.acc-head:hover { background:var(--bg-soft); }
.acc-head .arrow { transition:.25s; color:var(--purple); }
.acc-item.open .acc-head .arrow { transform:rotate(180deg); }
.acc-body { max-height:0; overflow:hidden; transition:max-height .35s ease; }
.acc-body .inner { padding:0 22px 20px; color:var(--ink-2); font-size:14px; }
.acc-body .inner .lock { color:var(--ink-3); font-size:13px; }
.res-item { display:flex; align-items:center; gap:10px; padding:8px 0; }
.res-item .k { flex:1; } .res-item .lockbadge { font-size:12px; color:var(--ink-3); }
/* 往期回顾卡片 */
.review-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:20px; }
.review-card { display:block; background:#fff; border:1px solid var(--line); border-radius:var(--radius); overflow:hidden; box-shadow:var(--sh-sm); text-decoration:none; color:inherit; transition:.25s; }
.review-card:hover { transform:translateY(-4px); box-shadow:var(--sh-lg); }
.review-card .rc-cover { aspect-ratio:16/9; background:#f2f2f6; }
.review-card .rc-cover img { width:100%; height:100%; object-fit:cover; display:block; }
.review-card .rc-cover.grad { background:var(--grad-brand); display:grid; place-items:center; color:#fff; font-size:40px; }
.review-card .rc-body { padding:16px 18px; }
.review-card .rc-body h4 { font-size:16px; line-height:1.4; margin:0 0 8px; }
.review-card .rc-body p { font-size:13px; color:var(--ink-3); margin:0; }
@media (max-width:960px){ .review-grid{ grid-template-columns:repeat(2,1fr); } }
@media (max-width:600px){ .review-grid{ grid-template-columns:1fr; } }
</style>
</head>
<body>

<!-- 导航 -->
<?php include __DIR__ . '/partials/nav.php'; ?>

<!-- 页头 -->
<section class="page-hero">
  <div class="container">
    <div class="breadcrumb reveal"><a href="index.php">首页</a> / <?= snip('edu.hero.breadcrumb') ?></div>
    <span class="eyebrow reveal"><?= snip('edu.hero.eyebrow') ?></span>
    <h1 class="reveal d1"><?= snip_raw('edu.hero.title') ?></h1>
    <p class="lead reveal d2"><?= snip('edu.hero.lead') ?></p>
  </div>
</section>

<!-- 课程 Banner + 两列 + 教学安排 -->
<section class="section" style="padding-top:40px">
  <div class="container">
    <div class="course-banner reveal">
      <img src="assets/images/education-banner.webp" alt="疫苗的力量 课程主视觉">
    </div>
    <div class="edu-two-col" style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:24px">
      <div class="info-card reveal">
        <div class="head"><span class="ico">📘</span><h3>课程简介</h3></div>
        <div style="color:var(--ink-2);line-height:1.8"><?= snip_paras('edu.course.intro') ?></div>
      </div>
      <div class="info-card reveal d1">
        <div class="head"><span class="ico" style="background:var(--green-100);color:#06a97c">👥</span><h3>主讲团队</h3></div>
        <div style="color:var(--ink-2);line-height:1.8"><?= snip_paras('edu.teachers.intro') ?></div>
      </div>
    </div>
<?php $schedule = (new Collection('content_cards'))->published("grp='edu_schedule'"); if ($schedule): ?>
    <div class="accordion reveal" style="margin-top:20px">
      <div class="acc-item">
        <div class="acc-head"><span>📋 教学安排（点击展开课程顺序与授课教师）</span><span class="arrow">▾</span></div>
        <div class="acc-body"><div class="inner">
<?php foreach ($schedule as $s): ?>
          <div class="res-item"><span class="k"><b><?= e($s['title']) ?></b></span><span class="lockbadge"><?= e($s['body']) ?></span></div>
<?php endforeach; ?>
        </div></div>
      </div>
    </div>
<?php endif; ?>
  </div>
</section>

<!-- 张林琦访谈《疫苗的力量》 -->
<section class="section bg-soft" id="video">
  <div class="container">
<?php
$talkVideoRel = snip_raw('edu.talk.video_src');
$talkVideoAbs = $talkVideoRel !== '' ? __DIR__ . '/' . ltrim($talkVideoRel, '/') : '';
$talkHasVideo = $talkVideoRel !== '' && $talkVideoAbs !== '' && is_file($talkVideoAbs);
$talkPoster   = snip_raw('edu.talk.poster', 'assets/images/edu-talk-poster.webp');
?>
    <div class="edu-talk">
      <div class="edu-talk-text reveal">
        <span class="eyebrow"><?= snip('edu.talk.eyebrow', '与教授对话') ?></span>
        <h2 class="section-title" style="margin-top:14px"><?= snip('edu.talk.title', '《疫苗的力量》与张林琦教授对话') ?></h2>
        <p class="edu-talk-body" style="margin-top:16px"><?= snip('edu.talk.body') ?></p>
        <div class="edu-talk-chips" style="margin-top:20px">
          <span class="edu-talk-chip"><?= snip('edu.talk.kw1', '课程缘起') ?></span>
          <span class="edu-talk-chip"><?= snip('edu.talk.kw2', '专业选择') ?></span>
          <span class="edu-talk-chip"><?= snip('edu.talk.kw3', '课程期待') ?></span>
          <span class="edu-talk-chip"><?= snip('edu.talk.kw4', '青年寄语') ?></span>
        </div>
      </div>
      <div class="edu-talk-media reveal d1">
<?php if ($talkHasVideo): ?>
        <div class="edu-talk-player" data-poster="<?= e($talkPoster) ?>" data-src="<?= e($talkVideoRel) ?>">
          <img class="edu-talk-poster" src="<?= e($talkPoster) ?>" alt="张林琦教授访谈海报" onerror="this.style.display='none'">
          <button type="button" class="edu-talk-play" aria-label="播放访谈视频">
            <svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor" aria-hidden="true"><path d="M8 5.14v13.72c0 .86.94 1.4 1.68.97l11.1-6.86a1.13 1.13 0 0 0 0-1.94L9.68 4.17A1.13 1.13 0 0 0 8 5.14Z"/></svg>
          </button>
        </div>
<?php else: ?>
        <div class="edu-talk-player edu-talk-player--missing">
          <img class="edu-talk-poster" src="<?= e($talkPoster) ?>" alt="张林琦教授访谈海报" onerror="this.style.display='none'">
          <div class="edu-talk-missing">视频加载中，稍后再试</div>
        </div>
<?php endif; ?>
        <div class="edu-talk-speaker"><?= snip('edu.talk.speaker', '张林琦教授｜《疫苗的力量》开课人、主讲人') ?></div>
      </div>
    </div>
  </div>
</section>

<!-- ============ 往期回顾（后台可管理图文材料） ============ -->
<section class="section bg-soft" id="review">
  <div class="container">
    <div style="margin-bottom:30px">
      <span class="eyebrow reveal">往期回顾</span>
      <h2 class="section-title reveal d1">往期活动与教学回顾</h2>
      <p class="section-sub reveal d2">课程、讲座与活动的图文记录，点击查看详情。</p>
    </div>
<?php $reviews = (new Collection('edu_reviews'))->published(); ?>
<?php if (!$reviews): ?>
    <p style="color:var(--ink-3)">暂无往期回顾内容。</p>
<?php else: ?>
    <div class="review-grid">
<?php foreach ($reviews as $r): ?>
      <a class="review-card" href="edu-review.php?id=<?= (int)$r['id'] ?>">
<?php if (($r['cover'] ?? '') !== ''): ?>
        <div class="rc-cover"><img src="<?= e($r['cover']) ?>" alt="" onerror="this.parentElement.classList.add('grad');this.remove()"></div>
<?php else: ?>
        <div class="rc-cover grad">📚</div>
<?php endif; ?>
        <div class="rc-body">
          <h4><?= e($r['title']) ?></h4>
<?php if (($r['summary'] ?? '') !== ''): ?>
          <p><?= e($r['summary']) ?></p>
<?php endif; ?>
        </div>
      </a>
<?php endforeach; ?>
    </div>
<?php endif; ?>
  </div>
</section>


<!-- 页脚 -->
<?php include __DIR__ . '/partials/footer.php'; ?>

<script src="assets/js/main.js"></script>
<script>
// 折叠面板
document.querySelectorAll('.acc-head').forEach(function (h) {
  h.addEventListener('click', function () {
    var item = h.parentElement;
    var body = h.nextElementSibling;
    item.classList.toggle('open');
    body.style.maxHeight = item.classList.contains('open') ? body.scrollHeight + 'px' : '0';
  });
});
document.querySelectorAll('.acc-item.open .acc-body').forEach(function (b) { b.style.maxHeight = b.scrollHeight + 'px'; });

// 张林琦访谈播放器：点击 poster/按钮后替换为 <video controls autoplay>
document.querySelectorAll('.edu-talk-player[data-src]').forEach(function (p) {
  p.addEventListener('click', function () {
    if (p.querySelector('video')) return;
    var src = p.getAttribute('data-src');
    var poster = p.getAttribute('data-poster') || '';
    var v = document.createElement('video');
    v.controls = true; v.autoplay = true; v.preload = 'auto';
    if (poster) v.poster = poster;
    var s = document.createElement('source'); s.src = src; v.appendChild(s);
    p.innerHTML = '';
    p.appendChild(v);
  });
});
</script>
</body>
</html>
