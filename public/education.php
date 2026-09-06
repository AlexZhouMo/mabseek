<?php
require __DIR__ . '/../app/bootstrap.php';
member_check();                                   // 未登录跳 login.php
$active = 'education'; $navOnDark = false; $navSolidDark = true; $contactHref = 'index.php#contact';
$eduInfo    = (new Collection('content_cards'))->published("grp='edu_info'");
$eduLecture = (new Collection('content_cards'))->published("grp='edu_lecture'");
$eduGrow    = (new Collection('content_cards'))->published("grp='edu_grow'");
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>教育 · 《疫苗的力量》元视频 + 交互式知识图谱 | MabSeek</title>
<meta name="description" content="MabSeek 教育板块：《疫苗的力量》元视频课程、三层交互式知识图谱、学术讲座与学生成长资源，重塑科研教育范式。">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🧬</text></svg>">
<style>
/* ---- 教育页专属组件 ---- */
.course-banner { position: relative; border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--sh-lg); }
.course-banner img { width: 100%; height: 340px; object-fit: cover; }
.course-banner .overlay { position: absolute; inset: 0; background: linear-gradient(90deg, rgba(13,16,48,.78), rgba(13,16,48,.25) 70%); display: flex; align-items: center; padding: 0 48px; }
.course-banner .overlay h2 { color: #fff; font-size: clamp(26px,4vw,42px); max-width: 560px; }
.course-banner .overlay p { color: rgba(255,255,255,.9); margin-top: 12px; max-width: 480px; }
.three-col { display: grid; grid-template-columns: repeat(3,1fr); gap: 24px; margin-top: -60px; position: relative; z-index: 2; padding: 0 24px; }
.info-card { background:#fff; border:1px solid var(--line); border-radius: var(--radius); padding: 26px; box-shadow: var(--sh); }
.info-card .head { display:flex; align-items:center; gap:12px; margin-bottom:14px; }
.info-card .head .ico { width:44px;height:44px;border-radius:12px;display:grid;place-items:center;background:var(--purple-050);color:var(--purple);font-size:20px; }
.info-card h3 { font-size:18px; }
.info-card ul li { display:flex; gap:8px; color:var(--ink-2); font-size:14px; margin-bottom:9px; }
.info-card ul li::before { content:"▸"; color:var(--green); font-weight:700; }
.team-mini { display:flex; align-items:center; gap:10px; margin-bottom:12px; }
.avatar { width:40px;height:40px;border-radius:50%; background:var(--grad-purple); color:#fff; display:grid;place-items:center;font-weight:700;flex:0 0 auto; }

/* 播放器 */
.player-wrap { display:grid; grid-template-columns: 1.5fr 1fr; gap:24px; }
.player { background:#0d1030; border-radius: var(--radius); overflow:hidden; box-shadow: var(--sh-lg); }
.player .screen { position:relative; aspect-ratio:16/9; background:#000; overflow:hidden; }
.player .screen img { width:100%;height:100%;object-fit:cover;opacity:.85; }
.player .screen .playbtn { position:absolute; inset:0; margin:auto; width:76px;height:76px;border-radius:50%;
  background:rgba(255,255,255,.92); display:grid;place-items:center; cursor:pointer; transition:.25s; box-shadow:var(--sh-lg); }
.player .screen .playbtn:hover { transform:scale(1.08); background:#fff; }
.player .screen .playbtn::after { content:""; border-left:20px solid var(--purple); border-top:12px solid transparent; border-bottom:12px solid transparent; margin-left:5px; }
/* 弹幕 */
.danmaku { position:absolute; top:0; left:0; right:0; height:60%; overflow:hidden; pointer-events:none; }
.dm { position:absolute; white-space:nowrap; color:#fff; font-size:13px; font-weight:600; text-shadow:0 1px 4px rgba(0,0,0,.6);
  background:rgba(0,0,0,.18); padding:3px 10px; border-radius:999px; animation: dm-move linear infinite; }
@keyframes dm-move { from { transform: translateX(100%);} to { transform: translateX(-260px);} }
.player .bar { height:5px; background:rgba(255,255,255,.2); }
.player .bar span { display:block; width:38%; height:100%; background:var(--grad-green); }
.player .ctrls { display:flex; align-items:center; gap:14px; padding:12px 16px; color:#c7cbe6; font-size:13px; }
.player .ctrls .chip { margin-left:auto; background:rgba(255,255,255,.1); padding:5px 12px; border-radius:999px; cursor:pointer; }
/* 留言 */
.comments { background:#fff;border:1px solid var(--line);border-radius:var(--radius);padding:20px; }
.comment { display:flex; gap:12px; padding:12px 0; border-bottom:1px solid var(--line); }
.comment:last-child { border-bottom:0; }
.comment .avatar { width:36px;height:36px;font-size:13px; }
.comment .body b { font-size:14px; } .comment .body p { font-size:14px; color:var(--ink-2); margin-top:2px; }
.comment-input { display:flex; gap:8px; margin-top:14px; }
.comment-input input { flex:1; border:1px solid var(--line); border-radius:999px; padding:10px 16px; font-size:14px; font-family:inherit; }
.comment-input input:focus { outline:none; border-color:var(--purple-400); }

/* 课时卡片 */
.lesson-card { background:#fff;border:1px solid var(--line);border-radius:var(--radius); overflow:hidden; box-shadow:var(--sh-sm); transition:.25s; }
.lesson-card:hover { transform:translateY(-5px); box-shadow:var(--sh-lg); }
.lesson-card .thumb { position:relative; aspect-ratio:16/10; background:var(--grad-purple); overflow:hidden; }
.lesson-card .thumb img { width:100%;height:100%;object-fit:cover; }
.lesson-card .thumb .badge { position:absolute; top:10px; left:10px; background:rgba(255,255,255,.9); color:var(--purple); font-size:12px; font-weight:700; padding:4px 10px; border-radius:999px; }
.lesson-card .thumb .dur { position:absolute; bottom:10px; right:10px; background:rgba(0,0,0,.6); color:#fff; font-size:12px; padding:3px 8px; border-radius:6px; }
.lesson-card .meta { padding:16px; }
.lesson-card .meta h4 { font-size:15px; } .lesson-card .meta .sub { font-size:13px; color:var(--ink-3); margin-top:6px; }
.lesson-card .meta .foot { display:flex; justify-content:space-between; align-items:center; margin-top:12px; }
.mini-link { color:var(--purple); font-weight:700; font-size:13px; }

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

<!-- 课程 Banner + 三栏 -->
<section class="section" style="padding-top:40px">
  <div class="container">
    <div class="course-banner reveal">
      <img src="assets/images/education-banner.png" alt="疫苗的力量 课程主视觉">
      <div class="overlay">
        <div>
          <span class="tag green" style="background:rgba(0,224,164,.2);color:#aaffe6;border-color:rgba(0,224,164,.4);margin-bottom:18px"><?= snip('edu.banner.tag') ?></span>
          <h2><?= snip('edu.banner.title') ?></h2>
          <p><?= snip('edu.banner.sub') ?></p>
        </div>
      </div>
    </div>
    <div class="three-col">
<?php if (isset($eduInfo[0])): $ex = json_decode($eduInfo[0]['extra'] ?: '{}', true); $icoStyle = !empty($ex['ico_style']) ? ' style="' . e($ex['ico_style']) . '"' : ''; ?>
      <div class="info-card reveal">
        <div class="head"><span class="ico"<?= $icoStyle ?>><?= e($eduInfo[0]['icon']) ?></span><h3><?= e($eduInfo[0]['title']) ?></h3></div>
        <ul>
<?php foreach (($ex['items'] ?? []) as $it): ?>          <li><?= e($it) ?></li>
<?php endforeach; ?>        </ul>
      </div>
<?php endif; ?>
      <div class="info-card reveal d1">
        <div class="head"><span class="ico" style="background:var(--green-100);color:#06a97c">👥</span><h3>主讲团队</h3></div>
        <div class="team-mini"><span class="avatar">张</span><div><b>张老师</b><div style="font-size:13px;color:var(--ink-3)">清华大学医学院 · 抗体与疫苗方向</div></div></div>
        <div class="team-mini"><span class="avatar" style="background:var(--grad-green);color:#04352a">博</span><div><b>课题组博士团队</b><div style="font-size:13px;color:var(--ink-3)">一线科研经验与实操 Knowhow</div></div></div>
        <p style="font-size:13px;color:var(--ink-3);margin-top:6px">邀请领域大牛开展专题讲座，师生共建教研内容。</p>
      </div>
<?php if (isset($eduInfo[1])): $ex = json_decode($eduInfo[1]['extra'] ?: '{}', true); $icoStyle = !empty($ex['ico_style']) ? ' style="' . e($ex['ico_style']) . '"' : ''; ?>
      <div class="info-card reveal d2">
        <div class="head"><span class="ico"<?= $icoStyle ?>><?= e($eduInfo[1]['icon']) ?></span><h3><?= e($eduInfo[1]['title']) ?></h3></div>
        <ul>
<?php foreach (($ex['items'] ?? []) as $it): ?>          <li><?= e($it) ?></li>
<?php endforeach; ?>        </ul>
      </div>
<?php endif; ?>
    </div>
  </div>
</section>

<!-- 课时播放专区 -->
<section class="section bg-soft" id="video">
  <div class="container">
    <div style="margin-bottom:34px">
      <span class="eyebrow reveal"><?= snip('edu.video.eyebrow') ?></span>
      <h2 class="section-title reveal d1"><?= snip('edu.video.title') ?></h2>
      <p class="section-sub reveal d2">元视频点播、实时弹幕与专属留言区，边看边学边讨论。</p>
    </div>
    <div class="player-wrap reveal">
      <div class="player">
        <div class="screen">
          <img src="assets/images/education-banner.png" alt="课程视频画面">
          <div class="danmaku" id="danmaku"></div>
          <div class="playbtn" data-demo="正式版将唤起独立播放器，侧边常驻知识图谱入口"></div>
        </div>
        <div class="bar"><span></span></div>
        <div class="ctrls">
          <span>▶</span><span>第 03 课：疫苗免疫应答基础</span>
          <span class="chip" data-demo="弹幕已开启，可按时间轴筛选对应片段">💬 弹幕</span>
          <span class="chip">11:59 / 31:24</span>
        </div>
      </div>
      <div class="comments">
        <b style="font-size:15px">课时专属留言区</b>
        <div style="margin-top:12px">
          <div class="comment"><span class="avatar" style="background:var(--grad-purple)">疫</span><div class="body"><b>疫苗探客</b><p>免疫记忆这段讲得很清楚，知识点拆分得刚刚好！</p></div></div>
          <div class="comment"><span class="avatar" style="background:var(--grad-green);color:#04352a">卡</span><div class="body"><b>疫苗卡壳</b><p>请问儿童疫苗时间表在哪里看？点知识图谱能跳转吗？</p></div></div>
        </div>
        <div class="comment-input">
          <input type="text" placeholder="发表留言，师生与访客均可互动…">
          <button class="btn btn-purple" style="padding:10px 18px" data-demo="留言将进入后台审核后展示">发送</button>
        </div>
      </div>
    </div>

    <h3 style="margin:40px 0 18px;font-size:20px" class="reveal">单课时卡片陈列 · 点击唤起播放器</h3>
    <div class="grid-4">
      <div class="lesson-card reveal">
        <div class="thumb"><img src="assets/images/education-banner.png" alt=""><span class="badge">第 01 课</span><span class="dur">28:10</span></div>
        <div class="meta"><h4>疫苗发展史与分类</h4><div class="sub">研发人员重点看这节</div><div class="foot"><span class="mini-link" data-demo="唤起播放器">▶ 播放</span><span class="mini-link" data-demo="跳转知识图谱">🕸 图谱</span></div></div>
      </div>
      <div class="lesson-card reveal d1">
        <div class="thumb"><img src="assets/images/lab-scene.png" onerror="this.style.display='none'" alt=""><span class="badge">第 02 课</span><span class="dur">31:24</span></div>
        <div class="meta"><h4>免疫系统如何识别抗原</h4><div class="sub">新手父母怎么理解接种顺序？</div><div class="foot"><span class="mini-link" data-demo="唤起播放器">▶ 播放</span><span class="mini-link" data-demo="跳转知识图谱">🕸 图谱</span></div></div>
      </div>
      <div class="lesson-card reveal d2">
        <div class="thumb"><img src="assets/images/education-banner.png" alt=""><span class="badge">第 03 课</span><span class="dur">33:02</span></div>
        <div class="meta"><h4>疫苗免疫应答基础</h4><div class="sub">AI 能解释论文中的机制吗？</div><div class="foot"><span class="mini-link" data-demo="唤起播放器">▶ 播放</span><span class="mini-link" data-demo="跳转知识图谱">🕸 图谱</span></div></div>
      </div>
      <div class="lesson-card reveal d3">
        <div class="thumb"><img src="assets/images/antibody-structure.png" onerror="this.style.display='none'" alt=""><span class="badge">第 04 课</span><span class="dur">26:47</span></div>
        <div class="meta"><h4>抗体在疫苗中的作用</h4><div class="sub">研发人员重点看这节</div><div class="foot"><span class="mini-link" data-demo="唤起播放器">▶ 播放</span><span class="mini-link" data-demo="跳转知识图谱">🕸 图谱</span></div></div>
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


<!-- 学术讲座 + 成长资源 -->
<section class="section bg-soft">
  <div class="container">
    <div class="grid-2" style="align-items:start">
      <div class="reveal">
        <span class="eyebrow"><?= snip('edu.lecture.eyebrow') ?></span>
        <h2 style="font-size:26px;margin:14px 0 18px"><?= snip('edu.lecture.title') ?></h2>
<?php foreach ($eduLecture as $i => $c): $mb = $i < count($eduLecture) - 1 ? ' style="margin-bottom:14px"' : ''; ?>
        <div class="card"<?= $mb ?>><h3 style="font-size:16px"><?= e($c['icon']) ?> <?= e($c['title']) ?></h3><p><?= e($c['body']) ?></p></div>
<?php endforeach; ?>
      </div>
      <div class="reveal d1">
        <span class="eyebrow green"><?= snip('edu.grow.eyebrow') ?></span>
        <h2 style="font-size:26px;margin:14px 0 18px"><?= snip('edu.grow.title') ?></h2>
<?php foreach ($eduGrow as $i => $c): $mb = $i < count($eduGrow) - 1 ? ' style="margin-bottom:14px"' : ''; ?>
        <div class="card"<?= $mb ?>><h3 style="font-size:16px"><?= e($c['icon']) ?> <?= e($c['title']) ?></h3><p><?= e($c['body']) ?></p></div>
<?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- 页脚 -->
<?php include __DIR__ . '/partials/footer.php'; ?>

<script src="assets/js/main.js"></script>
<script>
// 弹幕
(function () {
  var texts = ['这个知识点讲得很清楚','HPV疫苗接种年龄是否有限制？','儿童疫苗时间表在哪里看？','mRNA原理终于懂了','点图谱能跳转真方便','AI答疑太强了','求第4课的图谱','免疫记忆这段收藏了'];
  var box = document.getElementById('danmaku');
  if (!box) return;
  var i = 0;
  function spawn() {
    var d = document.createElement('div');
    d.className = 'dm';
    d.textContent = texts[i % texts.length]; i++;
    d.style.top = (Math.random() * 80 + 5) + '%';
    d.style.animationDuration = (7 + Math.random() * 5) + 's';
    box.appendChild(d);
    setTimeout(function () { d.remove(); }, 12000);
  }
  if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    spawn(); setInterval(spawn, 2200);
  }
})();
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
</script>
</body>
</html>
