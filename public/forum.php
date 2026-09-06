<?php
require __DIR__ . '/../app/bootstrap.php';
member_check();                                   // 未登录跳 login.php
$active = 'forum'; $navOnDark = false; $navSolidDark = true; $contactHref = 'index.php#contact';
$lines   = (new Collection('content_cards'))->published("grp='forum_line'");
// 首屏首批：多取一条判断「加载更多」显隐
$firstBatch = thread_list_by_category('all', FORUM_PAGE_SIZE + 1, 0);
$hasMore    = count($firstBatch) > FORUM_PAGE_SIZE;
$firstBatch = array_slice($firstBatch, 0, FORUM_PAGE_SIZE);
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

<!-- ============ 会员真实帖流（分类筛选 + 加载更多） ============ -->
<section class="section" id="threads" style="padding-top:44px">
  <div class="container">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;gap:16px;flex-wrap:wrap">
      <div>
        <span class="eyebrow">会员发布</span>
        <h2 class="section-title">会员们最近发了什么</h2>
      </div>
      <a class="btn btn-purple" href="thread-new.php">发帖</a>
    </div>
    <div class="filter-bar reveal">
      <span class="chip-f on" data-f="all">🔥 全部</span>
<?php foreach (THREAD_CATEGORIES as $k => $label): ?>
      <span class="chip-f" data-f="<?= e($k) ?>"><?= e($label) ?></span>
<?php endforeach; ?>
    </div>

    <div class="feed reveal d1" id="feed">
<?php foreach ($firstBatch as $t):
      $author = ($t['author_nickname'] ?? '') !== '' ? $t['author_nickname'] : ($t['author_username'] ?? '');
      $catLabel = THREAD_CATEGORIES[$t['category']] ?? $t['category'];
?>
      <a class="post" href="thread.php?id=<?= (int)$t['id'] ?>" style="text-decoration:none;color:inherit;display:block">
<?php if (($t['cover'] ?? '') !== ''): ?>
        <div class="cover"><img src="<?= e($t['cover']) ?>" alt="" onerror="this.parentElement.classList.add('grad');this.remove()"><span class="toptag"><?= e($catLabel) ?></span></div>
<?php else: ?>
        <div class="cover grad"><span class="toptag"><?= e($catLabel) ?></span>🧬</div>
<?php endif; ?>
        <div class="pbody"><h4><?= e($t['title']) ?></h4>
          <div class="pfoot"><span class="who"><span class="av"><?= e(mb_substr($author, 0, 1)) ?></span><?= e($author) ?></span><span><?= e($t['created_at']) ?></span></div></div>
      </a>
<?php endforeach; ?>
    </div>
<?php if (!$firstBatch): ?>
    <p id="feed-empty" style="color:var(--ink-3)">还没有会员帖子，<a href="thread-new.php">来发第一帖</a>。</p>
<?php endif; ?>
    <div class="text-center" style="margin-top:20px">
      <button class="btn btn-outline" id="loadmore"<?= $hasMore ? '' : ' hidden' ?>>加载更多</button>
    </div>
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

<!-- 页脚 -->
<?php include __DIR__ . '/partials/footer.php'; ?>

<script src="assets/js/main.js"></script>
<script>
(function () {
  var chips    = document.querySelectorAll('.chip-f');
  var feed     = document.getElementById('feed');
  var loadmore = document.getElementById('loadmore');
  var cat      = 'all';
  var offset   = feed ? feed.querySelectorAll('.post').length : 0;

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c];
    });
  }

  function cardHtml(it) {
    var initial = it.author ? it.author.slice(0, 1) : '';
    var cover = it.cover
      ? '<div class="cover"><img src="' + esc(it.cover) + '" alt="" onerror="this.parentElement.classList.add(\'grad\');this.remove()"><span class="toptag">' + esc(it.catLabel) + '</span></div>'
      : '<div class="cover grad"><span class="toptag">' + esc(it.catLabel) + '</span>🧬</div>';
    return '<a class="post" href="thread.php?id=' + it.id + '" style="text-decoration:none;color:inherit;display:block">'
      + cover
      + '<div class="pbody"><h4>' + esc(it.title) + '</h4>'
      + '<div class="pfoot"><span class="who"><span class="av">' + esc(initial) + '</span>' + esc(it.author) + '</span><span>' + esc(it.created_at) + '</span></div></div>'
      + '</a>';
  }

  function fetchBatch(replace) {
    var url = 'threads-api.php?category=' + encodeURIComponent(cat) + '&offset=' + offset;
    fetch(url, { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data || !data.ok) return;
        if (replace) { feed.innerHTML = ''; }
        data.items.forEach(function (it) { feed.insertAdjacentHTML('beforeend', cardHtml(it)); });
        offset += data.items.length;
        loadmore.hidden = !data.hasMore;
      })
      .catch(function () { /* 网络错误：静默，用户可重试 */ });
  }

  chips.forEach(function (c) {
    c.addEventListener('click', function () {
      chips.forEach(function (x) { x.classList.remove('on'); });
      c.classList.add('on');
      cat = c.getAttribute('data-f');
      offset = 0;
      fetchBatch(true);   // 切分类：从第 1 批重新拉取并替换
    });
  });

  if (loadmore) {
    loadmore.addEventListener('click', function () { fetchBatch(false); });  // 追加下一批
  }
})();
</script>
</body>
</html>
