<?php
require __DIR__ . '/../app/bootstrap.php';
$active = 'forum'; $navOnDark = false; $contactHref = 'index.php#contact';
$hotAll  = (new Collection('forum_hot'))->published();
$hotDay  = array_values(array_filter($hotAll, fn($r) => $r['list'] === 'day'));
$hotWeek = array_values(array_filter($hotAll, fn($r) => $r['list'] === 'week'));
$posts   = (new Collection('forum_posts'))->published();
$lines   = (new Collection('content_cards'))->published("grp='forum_line'");
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>论坛 · 抗体领域高质量交流社区 | MabSeek</title>
<meta name="description" content="MabSeek 论坛：小红书式内容流的抗体领域交流社区。硬核干货、隐藏高人、前沿话题，提问有人答，分享有人看，高手愿意来，新手能成长。">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🧬</text></svg>">
<style>
/* ---- 论坛专属 ---- */
.filter-bar { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:6px; }
.chip-f { padding:8px 16px; border-radius:999px; font-size:14px; font-weight:700; border:1px solid var(--line); background:#fff; color:var(--ink-2); cursor:pointer; transition:.2s; }
.chip-f:hover { border-color:var(--purple-400); color:var(--purple); }
.chip-f.on { background:var(--grad-purple); color:#fff; border-color:transparent; box-shadow:var(--sh-purple); }
.feed { column-count:3; column-gap:20px; }
.post { break-inside:avoid; background:#fff; border:1px solid var(--line); border-radius:var(--radius); overflow:hidden; margin-bottom:20px; box-shadow:var(--sh-sm); transition:.25s; cursor:pointer; }
.post:hover { transform:translateY(-4px); box-shadow:var(--sh-lg); }
.post .cover { position:relative; }
.post .cover img { width:100%; display:block; }
.post .cover.grad { height:150px; background:var(--grad-brand); display:grid;place-items:center;color:#fff;font-size:40px; }
.post .cover.grad.g2 { background:linear-gradient(135deg,#12D6A0,#4b6bff); }
.post .cover.grad.g3 { background:linear-gradient(135deg,#7C4DFF,#00E0A4); }
.post .cover .toptag { position:absolute; top:10px; left:10px; background:rgba(255,255,255,.92); color:var(--purple); font-size:12px;font-weight:700;padding:4px 10px;border-radius:999px; }
.post .pbody { padding:14px 16px 16px; }
.post .pbody h4 { font-size:15px; line-height:1.4; }
.post .pbody .ptags { margin:8px 0; display:flex; gap:6px; flex-wrap:wrap; }
.post .pbody .ptags span { font-size:12px; color:var(--purple); font-weight:600; }
.post .pfoot { display:flex; align-items:center; gap:8px; font-size:13px; color:var(--ink-3); }
.post .pfoot .who { display:flex; align-items:center; gap:7px; flex:1; }
.post .pfoot .av { width:24px;height:24px;border-radius:50%;background:var(--grad-purple);color:#fff;display:grid;place-items:center;font-size:11px;font-weight:700; }
.post .pfoot .like { display:flex;align-items:center;gap:4px; }
.line-card { background:#fff;border:1px solid var(--line);border-radius:var(--radius);padding:24px;box-shadow:var(--sh-sm); }
.line-card .ico { width:48px;height:48px;border-radius:13px;display:grid;place-items:center;font-size:22px;margin-bottom:14px; }
.line-card h3 { font-size:18px; } .line-card ul { margin-top:10px; }
.line-card ul li { display:flex;gap:8px;font-size:14px;color:var(--ink-2);margin-bottom:8px; }
.line-card ul li::before { content:"#"; color:var(--green); font-weight:800; }
@media (max-width:960px){ .feed{ column-count:2; } }
@media (max-width:600px){ .feed{ column-count:1; } }
</style>
</head>
<body>

<!-- 导航 -->
<?php include __DIR__ . '/partials/nav.php'; ?>

<!-- 页头 -->
<section class="page-hero">
  <div class="container">
    <div class="breadcrumb reveal"><a href="index.php">首页</a> / <?= snip('forum.hero.breadcrumb') ?></div>
    <span class="eyebrow reveal"><?= snip('forum.hero.eyebrow') ?></span>
    <h1 class="reveal d1"><?= snip_raw('forum.hero.title') ?></h1>
    <p class="lead reveal d2"><?= snip('forum.hero.lead') ?></p>
    <div class="forum-search reveal d3">
      <div class="fs-box">
        <span class="fs-ico">🔍</span>
        <input class="fs-input" type="text" placeholder="<?= snip('forum.search.placeholder') ?>" aria-label="AI 智能搜索">
        <button class="btn btn-green fs-btn" data-demo="正式版将接入语义搜索，理解你的问题意图并返回最相关的帖子"><?= snip('forum.search.btn') ?></button>
      </div>
      <div class="fs-examples">
        <span class="fs-label"><?= snip('forum.search.label') ?></span>
        <span class="fs-chip" data-demo="正式版将接入语义搜索：理解意图，返回最相关的帖子"><?= snip('forum.search.chip1') ?></span>
        <span class="fs-chip" data-demo="正式版将接入语义搜索：理解意图，返回最相关的帖子"><?= snip('forum.search.chip2') ?></span>
        <span class="fs-chip" data-demo="正式版将接入语义搜索：理解意图，返回最相关的帖子"><?= snip('forum.search.chip3') ?></span>
      </div>
    </div>
  </div>
</section>

<!-- ============ 社区热榜（每日/每周，新增） ============ -->
<section class="section bg-soft" id="hot">
  <div class="container">
    <div style="margin-bottom:28px">
      <span class="eyebrow reveal"><?= snip('forum.hot.eyebrow') ?></span>
      <h2 class="section-title reveal d1"><?= snip('forum.hot.title') ?></h2>
      <p class="section-sub reveal d2"><?= snip('forum.hot.sub') ?></p>
    </div>
    <div class="hot-tabs reveal">
      <button class="hot-tab on" data-tab="day"><?= snip('forum.hot.tab_day') ?></button>
      <button class="hot-tab" data-tab="week"><?= snip('forum.hot.tab_week') ?></button>
    </div>
    <ol class="hot-list reveal d1" data-list="day">
<?php foreach ($hotDay as $r): ?>
      <li class="hot-row" data-demo="打开帖子详情"><span class="hot-rank"><?= (int)$r['rank'] ?></span><span class="hot-title"><?= e($r['title']) ?></span><span class="hot-cat"><?= e($r['category']) ?></span><span class="hot-fire"><?= e($r['heat']) ?></span></li>
<?php endforeach; ?>
    </ol>
    <ol class="hot-list reveal d1" data-list="week" hidden>
<?php foreach ($hotWeek as $r): ?>
      <li class="hot-row" data-demo="打开帖子详情"><span class="hot-rank"><?= (int)$r['rank'] ?></span><span class="hot-title"><?= e($r['title']) ?></span><span class="hot-cat"><?= e($r['category']) ?></span><span class="hot-fire"><?= e($r['heat']) ?></span></li>
<?php endforeach; ?>
    </ol>
  </div>
</section>

<!-- 话题标签 + 推荐流 -->
<section class="section" style="padding-top:44px">
  <div class="container">
    <div class="filter-bar reveal">
      <span class="chip-f on" data-f="all">🔥 推荐</span>
      <span class="chip-f" data-f="pit"># 实验踩坑</span>
      <span class="chip-f" data-f="proto"># Protocol 分享</span>
      <span class="chip-f" data-f="paper"># 文献精读</span>
      <span class="chip-f" data-f="bio"># 生信工具</span>
      <span class="chip-f" data-f="job"># 求职招聘</span>
    </div>
    <p style="font-size:13px;color:var(--ink-3);margin:0 0 22px"><?= snip('forum.feed.note') ?></p>

    <div class="feed reveal d1" id="feed">
<?php foreach ($posts as $p):
    $tags = array_filter(array_map('trim', explode(',', $p['tags'] ?? '')));
    $avStyle = $p['author_avatar_style'] !== '' ? ' style="' . e($p['author_avatar_style']) . '"' : '';
    $variant = in_array($p['cover_variant'], ['g2', 'g3'], true) ? $p['cover_variant'] : '';
    $addVariant = $variant !== '' ? ",'$variant'" : '';
?>
      <div class="post" data-cat="<?= e($p['category']) ?>" data-demo="打开帖子详情">
<?php if ($p['cover_type'] === 'img'):
        $emoji = ['proto'=>'🧪','bio'=>'🧬','pit'=>'💊'][$p['category']] ?? '🧬';
?>
        <div class="cover"><img src="<?= e($p['cover_ref']) ?>" alt="" onerror="this.parentElement.classList.add('grad'<?= $addVariant ?>);this.remove();this.parentElement.innerHTML='<?= e($emoji) ?>'"><span class="toptag"><?= e($p['toptag']) ?></span></div>
<?php else: ?>
        <div class="cover grad<?= $variant !== '' ? ' ' . $variant : '' ?>"><span></span><?= e($p['cover_ref']) ?></div>
<?php endif; ?>
        <div class="pbody"><h4><?= e($p['title']) ?></h4>
          <div class="ptags"><?php foreach ($tags as $t): ?><span><?= e($t) ?></span><?php endforeach; ?></div>
          <div class="pfoot"><span class="who"><span class="av"<?= $avStyle ?>><?= e($p['author_avatar_char']) ?></span><?= e($p['author_name']) ?></span><span class="like"><?= e($p['likes']) ?></span></div></div>
      </div>
<?php endforeach; ?>
    </div>
    <div class="text-center" style="margin-top:20px"><button class="btn btn-outline" data-demo="正式版将加载更多推荐内容"><?= snip('forum.feed.loadmore') ?></button></div>
  </div>
</section>

<!-- 四条内容线 -->
<section class="section bg-soft">
  <div class="container">
    <div class="text-center" style="margin-bottom:44px">
      <span class="eyebrow reveal"><?= snip('forum.lines.eyebrow') ?></span>
      <h2 class="section-title reveal d1"><?= snip_raw('forum.lines.title') ?></h2>
    </div>
    <div class="grid-2">
<?php foreach ($lines as $i => $l): $ex = json_decode($l['extra'] ?: '{}', true); $rev = $i ? ' d' . $i : ''; $icoStyle = !empty($ex['ico_style']) ? ' style="' . e($ex['ico_style']) . '"' : ''; ?>
      <div class="line-card reveal<?= $rev ?>"><div class="ico"<?= $icoStyle ?>><?= e($l['icon']) ?></div><h3><?= e($l['title']) ?></h3>
        <ul><?php foreach (($ex['items'] ?? []) as $it): ?><li><?= e($it) ?></li><?php endforeach; ?></ul></div>
<?php endforeach; ?>
    </div>
  </div>
</section>

<!-- 关注动态 + 冷启动 -->
<section class="section">
  <div class="container">
    <div class="grid-2" style="align-items:center">
      <div class="reveal">
        <span class="eyebrow"><?= snip('forum.follow.eyebrow') ?></span>
        <h2 style="font-size:26px;margin:14px 0 12px"><?= snip('forum.follow.title') ?></h2>
        <p style="color:var(--ink-3)"><?= snip('forum.follow.body') ?></p>
        <div style="display:flex;gap:12px;margin-top:20px;flex-wrap:wrap">
          <div class="card" style="padding:16px 18px;display:flex;align-items:center;gap:12px"><span class="av" style="width:40px;height:40px;border-radius:50%;background:var(--grad-purple);color:#fff;display:grid;place-items:center;font-weight:700">张</span><div><b style="font-size:14px"><?= snip('forum.follow.card_name') ?></b><div style="font-size:12px;color:var(--ink-3)"><?= snip('forum.follow.card_meta') ?></div></div><button class="btn btn-purple" style="padding:7px 16px;font-size:13px" data-demo="已关注"><?= snip('forum.follow.card_btn') ?></button></div>
        </div>
      </div>
      <div class="reveal d1" style="background:var(--grad-brand);border-radius:var(--radius-lg);padding:36px;color:#fff">
        <span class="tag" style="background:rgba(255,255,255,.18);color:#fff;border-color:rgba(255,255,255,.3)"><?= snip('forum.cold.tag') ?></span>
        <h3 style="font-size:22px;margin:14px 0 10px"><?= snip('forum.cold.title') ?></h3>
        <p style="opacity:.92"><?= snip('forum.cold.body') ?></p>
        <div style="display:flex;gap:24px;margin-top:22px">
          <div><div style="font-size:30px;font-weight:800"><?= snip('forum.cold.stat1_num') ?></div><div style="opacity:.85;font-size:13px"><?= snip('forum.cold.stat1_label') ?></div></div>
          <div><div style="font-size:30px;font-weight:800"><?= snip('forum.cold.stat2_num') ?></div><div style="opacity:.85;font-size:13px"><?= snip('forum.cold.stat2_label') ?></div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 页脚 -->
<?php include __DIR__ . '/partials/footer.php'; ?>

<script src="assets/js/main.js"></script>
<script>
// 标签筛选
var chips = document.querySelectorAll('.chip-f');
var posts = document.querySelectorAll('#feed .post');
chips.forEach(function (c) {
  c.addEventListener('click', function () {
    chips.forEach(function (x) { x.classList.remove('on'); });
    c.classList.add('on');
    var f = c.getAttribute('data-f');
    posts.forEach(function (p) {
      p.style.display = (f === 'all' || p.getAttribute('data-cat') === f) ? '' : 'none';
    });
  });
});

// 热榜：每日 / 每周 切换
var hotTabs = document.querySelectorAll('.hot-tab');
var hotLists = document.querySelectorAll('.hot-list');
hotTabs.forEach(function (tab) {
  tab.addEventListener('click', function () {
    hotTabs.forEach(function (x) { x.classList.remove('on'); });
    tab.classList.add('on');
    var t = tab.getAttribute('data-tab');
    hotLists.forEach(function (l) {
      var show = (l.getAttribute('data-list') === t);
      l.hidden = !show;
      if (show) l.classList.add('in'); // 默认隐藏的榜单加载时拿不到 reveal 的 .in，切换时补上
    });
  });
});
</script>
</body>
</html>
