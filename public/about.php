<?php
require __DIR__ . '/../app/bootstrap.php';
$active = 'about'; $navOnDark = false; $contactHref = '#contact';
$team = (new Collection('team_members'))->published();
$achv = (new Collection('content_cards'))->published("grp = 'about_achievement'");
$news = (new Collection('news'))->published();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>了解我们 · 实验室概况与成果 | MabSeek 抗体求索</title>
<meta name="description" content="MabSeek 实验室概况与成果：实验室与核心团队介绍、MabSeek 平台介绍、新闻与活动、中印尼深度合作与 PRA 国际科研合作纪实。">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🧬</text></svg>">
<style>
.news-tabs { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:22px; }
.news-item { display:flex; gap:18px; padding:20px; background:#fff;border:1px solid var(--line);border-radius:var(--radius);margin-bottom:14px;box-shadow:var(--sh-sm);transition:.2s; cursor:pointer; text-decoration:none; color:inherit; }
.news-item:hover { transform:translateX(4px); box-shadow:var(--sh); border-color:var(--purple-100); }
.news-item .date { flex:0 0 auto; text-align:center; width:64px; }
.news-item .date .d { font-size:26px;font-weight:800;color:var(--purple); } .news-item .date .m { font-size:12px;color:var(--ink-3); }
.news-item .n-body h4 { font-size:16px; } .news-item .n-body p { font-size:13px;color:var(--ink-3);margin-top:4px; }
.intl { display:grid; grid-template-columns:1fr 1fr; gap:32px; align-items:center; }
.intl-media { border-radius:var(--radius-lg); overflow:hidden; box-shadow:var(--sh-lg); }
.timeline { border-left:2px solid var(--purple-100); padding-left:22px; margin-top:18px; }
.timeline .tl { position:relative; margin-bottom:18px; }
.timeline .tl::before { content:""; position:absolute; left:-29px; top:4px; width:12px;height:12px;border-radius:50%;background:var(--grad-purple);box-shadow:0 0 0 4px var(--purple-050); }
.timeline .tl b { font-size:15px; } .timeline .tl p { font-size:13px;color:var(--ink-3); }
/* 双人负责人卡 */
.leads { display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-top:22px; }
.lead-card { display:flex; gap:18px; align-items:flex-start; background:#fff; border:1px solid var(--line); border-radius:var(--radius); padding:22px; box-shadow:var(--sh-sm); }
.lead-card .ph { flex:0 0 84px; width:84px; height:84px; border-radius:16px; background:var(--grad-brand); color:#fff; display:grid; place-items:center; font-size:34px; font-weight:800; }
.lead-card .ph.g2 { background:linear-gradient(135deg,#12D6A0,#4b6bff); }
.lead-card .nm { font-size:20px; font-weight:800; }
.lead-card .aff { font-size:13px; color:var(--ink-3); margin:2px 0 10px; }
.lead-card .dir { font-size:13.5px; color:var(--ink-2); line-height:1.6; }
.lead-card .role { display:inline-block; margin-top:12px; font-size:12px; font-weight:700; padding:4px 12px; border-radius:999px; background:var(--purple-050); color:var(--purple); }
.lead-card .role.green { background:var(--green-100); color:#06a97c; }
/* 位置展示 */
.loc-block { margin-top:32px; }
.loc-media { border-radius:var(--radius-lg); overflow:hidden; box-shadow:var(--sh-lg); }
.loc-media img { width:100%; display:block; }
.loc-media.grad { background:var(--grad-brand); min-height:280px; display:grid; place-items:center; color:#fff; font-size:64px; }
.loc-cap { text-align:center; font-size:13px; color:var(--ink-3); margin-top:12px; }
@media (max-width:720px){ .leads{ grid-template-columns:1fr; } }
</style>
</head>
<body>

<!-- 导航 -->
<?php include __DIR__ . '/partials/nav.php'; ?>

<!-- 页头 -->
<section class="page-hero">
  <div class="container">
    <div class="breadcrumb reveal"><a href="index.php">首页</a> / <?= snip('about.hero.breadcrumb') ?></div>
    <span class="eyebrow reveal"><?= snip('about.hero.eyebrow') ?></span>
    <h1 class="reveal d1"><?= snip_raw('about.hero.title') ?></h1>
    <p class="lead reveal d2"><?= snip('about.hero.lead') ?></p>
    <div class="tag-row reveal d3" style="margin-top:18px">
      <a href="#team" class="tag"><?= snip('about.hero.tag_team') ?></a><a href="#platform" class="tag green"><?= snip('about.hero.tag_platform') ?></a><a href="#news" class="tag"><?= snip('about.hero.tag_news') ?></a><a href="#intl" class="tag green"><?= snip('about.hero.tag_intl') ?></a>
    </div>
  </div>
</section>

<!-- 实验室与核心团队 -->
<section class="section" id="team">
  <div class="container">
    <span class="eyebrow reveal"><?= snip('about.team.eyebrow') ?></span>
    <h2 class="section-title reveal d1"><?= snip('about.team.title') ?></h2>
    <p class="section-sub reveal d2"><?= snip('about.team.sub') ?></p>
    <div class="leads reveal d1">
<?php foreach ($team as $m):
    $phClass = 'ph' . ($m['avatar_variant'] !== '' ? ' ' . e($m['avatar_variant']) : '');
    $roleClass = 'role' . ($m['role_type'] === 'ai' ? ' green' : '');
?>
      <div class="lead-card">
        <div class="<?= $phClass ?>"><?= e($m['avatar_char']) ?></div>
        <div>
          <div class="nm"><?= e($m['name']) ?></div>
          <div class="aff"><?= e($m['affiliation']) ?></div>
          <div class="dir"><?= e($m['direction']) ?></div>
          <span class="<?= $roleClass ?>"><?= e($m['role_label']) ?></span>
        </div>
      </div>
<?php endforeach; ?>
    </div>
    <div class="grid-2" style="margin-top:24px">
<?php foreach ($achv as $i => $c): $rev = $i ? ' d' . $i : ''; ?>
      <div class="card reveal<?= $rev ?>"><h3 style="font-size:16px"><?= e($c['icon']) ?> <?= e($c['title']) ?></h3><p><?= e($c['body']) ?></p></div>
<?php endforeach; ?>
    </div>
    <p class="reveal" style="font-size:13px;color:var(--ink-3);margin-top:16px"><?= snip('about.team.disclaimer') ?></p>
    <div class="loc-block reveal">
      <div class="loc-media"><img src="assets/images/location.png" alt="MabSeek 实验室位置" onerror="var p=this.parentElement;p.classList.add('grad');p.innerHTML='🏛️'"></div>
      <div class="loc-cap"><?= snip('about.loc.cap') ?></div>
    </div>
  </div>
</section>

<!-- MabSeek 平台介绍 -->
<section class="section bg-soft" id="platform">
  <div class="container">
    <div class="intl">
      <div class="reveal">
        <span class="eyebrow green"><?= snip('about.platform.eyebrow') ?></span>
        <h2 style="font-size:30px;margin:14px 0 12px"><?= snip('about.platform.title') ?></h2>
        <p style="color:var(--ink-3)"><?= snip('about.platform.body') ?></p>
        <ul style="margin-top:16px">
          <li style="display:flex;gap:10px;margin-bottom:10px;color:var(--ink-2)"><b style="color:var(--purple)">干</b> <?= snip('about.platform.li1') ?></li>
          <li style="display:flex;gap:10px;margin-bottom:10px;color:var(--ink-2)"><b style="color:#06a97c">湿</b> <?= snip('about.platform.li2') ?></li>
          <li style="display:flex;gap:10px;color:var(--ink-2)"><b style="color:var(--purple)">环</b> <?= snip('about.platform.li3') ?></li>
        </ul>
        <a href="agent.php" class="btn btn-purple" style="margin-top:20px"><?= snip('about.platform.btn') ?></a>
      </div>
      <div class="intl-media reveal d1"><img src="assets/images/agent-hero.png" alt="MabSeek 平台" onerror="this.parentElement.style.display='none'"></div>
    </div>
  </div>
</section>

<!-- 新闻与活动 -->
<section class="section" id="news">
  <div class="container">
    <span class="eyebrow reveal"><?= snip('about.news.eyebrow') ?></span>
    <h2 class="section-title reveal d1"><?= snip('about.news.title') ?></h2>
    <div class="news-tabs reveal d2" style="margin-top:16px">
      <span class="chip-f on" data-nf="all" style="padding:8px 16px;border-radius:999px;font-size:14px;font-weight:700;border:1px solid var(--line);background:var(--grad-purple);color:#fff;cursor:pointer"><?= snip('about.news.chip_all') ?></span>
      <span class="chip-f" data-nf="edu" style="padding:8px 16px;border-radius:999px;font-size:14px;font-weight:700;border:1px solid var(--line);background:#fff;color:var(--ink-2);cursor:pointer"><?= snip('about.news.chip_edu') ?></span>
      <span class="chip-f" data-nf="res" style="padding:8px 16px;border-radius:999px;font-size:14px;font-weight:700;border:1px solid var(--line);background:#fff;color:var(--ink-2);cursor:pointer"><?= snip('about.news.chip_res') ?></span>
      <span class="chip-f" data-nf="daily" style="padding:8px 16px;border-radius:999px;font-size:14px;font-weight:700;border:1px solid var(--line);background:#fff;color:var(--ink-2);cursor:pointer"><?= snip('about.news.chip_daily') ?></span>
    </div>
    <div id="news-list">
<?php foreach ($news as $it):
    $tagClass = $it['category'] === 'res' ? 'tag green' : 'tag';
    $tagText  = ['res'=>'科研类','edu'=>'育人类','daily'=>'日常活动'][$it['category']] ?? '';
?>
      <a class="news-item" href="news.php?id=<?= (int)$it['id'] ?>" data-nc="<?= e($it['category']) ?>"><div class="date"><div class="d"><?= e($it['date_day']) ?></div><div class="m"><?= e($it['date_ym']) ?></div></div><div class="n-body"><span class="<?= $tagClass ?>" style="font-size:11px"><?= e($tagText) ?></span><h4><?= e($it['title']) ?></h4><p><?= e($it['summary']) ?></p></div></a>
<?php endforeach; ?>
    </div>
    <div class="text-center reveal" style="margin-top:26px"><button class="btn btn-outline" data-demo="正式版将展示完整新闻与活动列表"><?= snip('about.news.more') ?></button></div>
  </div>
</section>

<!-- 国际科研合作 -->
<section class="section bg-soft" id="intl">
  <div class="container">
    <div class="text-center" style="margin-bottom:40px">
      <span class="eyebrow green reveal"><?= snip('about.intl.eyebrow') ?></span>
      <h2 class="section-title reveal d1"><?= snip_raw('about.intl.title') ?></h2>
      <p class="section-sub reveal d2"><?= snip('about.intl.sub') ?></p>
    </div>
    <div class="intl">
      <div class="intl-media reveal"><img src="assets/images/international.png" alt="国际科研合作" onerror="this.parentElement.style.background='var(--grad-brand)';this.parentElement.style.minHeight='320px'"></div>
      <div class="reveal d1">
        <div class="card" style="margin-bottom:16px">
          <h3 style="font-size:18px"><?= snip('about.intl.cn_id_title') ?></h3>
          <p style="margin-top:8px"><?= snip('about.intl.cn_id_body') ?></p>
          <div class="timeline">
            <div class="tl"><b><?= snip('about.intl.tl1_title') ?></b><p><?= snip('about.intl.tl1_body') ?></p></div>
            <div class="tl"><b><?= snip('about.intl.tl2_title') ?></b><p><?= snip('about.intl.tl2_body') ?></p></div>
            <div class="tl"><b><?= snip('about.intl.tl3_title') ?></b><p><?= snip('about.intl.tl3_body') ?></p></div>
          </div>
        </div>
        <div class="card" id="collab">
          <h3 style="font-size:18px"><?= snip('about.intl.pra_title') ?></h3>
          <p style="margin-top:8px"><?= snip('about.intl.pra_body') ?></p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 联系我们 + 快速反馈 -->
<section class="section section-dark" id="contact">
  <div class="container">
    <div class="text-center" style="margin-bottom:36px">
      <span class="eyebrow reveal"><?= snip('about.contact.eyebrow') ?></span>
      <h2 class="section-title reveal d1"><?= snip_raw('about.contact.title') ?></h2>
      <p class="section-sub reveal d2" style="margin:10px auto 0"><?= snip('about.contact.sub') ?></p>
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

<!-- 页脚 -->
<?php include __DIR__ . '/partials/footer.php'; ?>

<script src="assets/js/main.js"></script>
<script>
// 新闻筛选
var nchips = document.querySelectorAll('[data-nf]');
var nitems = document.querySelectorAll('#news-list .news-item');
nchips.forEach(function (c) {
  c.addEventListener('click', function () {
    nchips.forEach(function (x) { x.style.background = '#fff'; x.style.color = 'var(--ink-2)'; });
    c.style.background = 'var(--grad-purple)'; c.style.color = '#fff';
    var f = c.getAttribute('data-nf');
    nitems.forEach(function (n) { n.style.display = (f === 'all' || n.getAttribute('data-nc') === f) ? '' : 'none'; });
  });
});
</script>
</body>
</html>
