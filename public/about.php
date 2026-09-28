<?php
require __DIR__ . '/../app/bootstrap.php';
$active = 'about'; $navOnDark = false; $navSolidDark = true; $contactHref = '#contact';
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
<meta name="description" content="MabSeek 实验室概况与成果：实验室与核心团队介绍、新闻与活动、中印尼深度合作与 PRA 国际科研合作纪实。">
<link rel="stylesheet" href="assets/css/style.css">
<?php include __DIR__ . '/partials/head-meta.php'; ?>
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
/* 负责人卡 */
.leads { display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:24px; margin-top:22px; }
.lead-card { display:flex; gap:18px; align-items:flex-start; background:#fff; border:1px solid var(--line); border-radius:var(--radius); padding:22px; box-shadow:var(--sh-sm); }
.lead-card .ph { flex:0 0 84px; width:84px; height:84px; border-radius:16px; background:var(--grad-brand); color:#fff; display:grid; place-items:center; font-size:34px; font-weight:800; }
.lead-card img.ph { object-fit:cover; object-position:center top; }
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
/* 张老师手风琴 */
.accordion { border:1px solid var(--line); border-radius:var(--radius); overflow:hidden; background:#fff; }
.acc-item { border-bottom:1px solid var(--line); } .acc-item:last-child { border-bottom:0; }
.acc-head { padding:14px 18px; display:flex; align-items:center; justify-content:space-between; cursor:pointer; font-weight:700; color:var(--purple); }
.acc-head:hover { background:var(--bg-soft); }
.acc-head .arrow { transition:.25s; } .acc-item.open .acc-head .arrow { transform:rotate(180deg); }
.acc-body { max-height:0; overflow:hidden; transition:max-height .35s ease; }
.acc-body .inner { padding:14px 18px; color:var(--ink-2); }
.res-item { display:flex; gap:12px; padding:7px 0; border-bottom:1px dashed var(--line); }
.res-item:last-child { border-bottom:0; } .res-item .k { flex:1; } .res-item .lockbadge { color:var(--ink-3); font-size:13px; white-space:nowrap; }
</style>
</head>
<body>

<!-- 导航 -->
<?php include __DIR__ . '/partials/nav.php'; ?>

<!-- 页头 -->
<section class="page-hero page-hero--about-v4" style="background:linear-gradient(180deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0) 45%, rgba(120,120,120,0.71) 75%, rgba(0,0,0,0.74) 100%), url('assets/images/lab-panorama.webp') center/cover;min-height:480px;height:66vh;display:flex;flex-direction:column;justify-content:flex-end">
<style>
.page-hero--about-v4 h1 { color: #fff !important; text-shadow: 0 2px 6px rgba(0,0,0,.45); }
.page-hero--about-v4 h1 .grad-text { -webkit-text-fill-color: #fff !important; color: #fff !important; background: none !important; }
.page-hero--about-v4 .lead { color: rgba(255,255,255,.9) !important; text-shadow: 0 1px 3px rgba(0,0,0,.4); }
.page-hero--about-v4 .container { padding-bottom: 8vh; }
@media (max-width: 768px) {
  .page-hero--about-v4 { height: 50vh !important; min-height: 360px !important; }
  .page-hero--about-v4 .container { padding-bottom: 6vh; }
}
</style>
  <div class="container">
    <h1 class="reveal d1"><?= snip_raw('about.hero.title') ?></h1>
    <p class="lead reveal d2"><?= snip('about.hero.lead') ?></p>
  </div>
</section>

<!-- 实验室与核心团队 -->
<section class="section" id="team">
  <div class="container">
    <span class="eyebrow reveal">实验室负责人</span>
    <h2 class="section-title reveal d1">实验室负责人 · 张林琦</h2>

    <div class="zhang-basic reveal" style="display:flex;gap:22px;background:#fff;border:1px solid var(--line);border-radius:var(--radius);padding:24px;box-shadow:var(--sh-sm);margin-top:18px">
      <img src="assets/images/team/zhang.webp" alt="张林琦" style="flex:0 0 132px;width:132px;height:168px;object-fit:cover;object-position:center top;border-radius:12px" onerror="var d=document.createElement('div');d.textContent='张';d.style.cssText='flex:0 0 132px;width:132px;height:168px;border-radius:12px;background:var(--grad-brand);color:#fff;display:grid;place-items:center;font-size:44px;font-weight:800';this.replaceWith(d)">
      <div>
        <div style="font-size:22px;font-weight:800">张林琦</div>
        <div style="font-size:13px;color:var(--ink-3);margin:4px 0 12px"><?= snip('about.zhang.role1') ?> · <?= snip('about.zhang.role2') ?></div>
        <p style="color:var(--ink-2);line-height:1.7"><b style="color:var(--purple)">研究领域　</b><?= snip('about.zhang.field') ?></p>
        <p style="color:var(--ink-2);line-height:1.7;margin-top:8px"><b style="color:var(--purple)">科学贡献　</b><?= snip('about.zhang.contrib') ?></p>
      </div>
    </div>

<?php $papers = (new Collection('content_cards'))->published("grp='zhang_papers'"); ?>
    <div class="card reveal" style="margin-top:16px">
      <h3 style="font-size:17px">📄 学术论文成果</h3>
      <p style="color:var(--ink-2);margin-top:6px"><?= snip('about.zhang.papers_desc') ?>团队发表文章 <span style="color:#c0392b;font-weight:800;font-size:22px"><?= snip('about.zhang.papers_count') ?></span></p>
      <div class="accordion zhang-acc" style="margin-top:12px"><div class="acc-item">
        <div class="acc-head"><span>近 5 年代表性文章</span><span class="arrow">▾</span></div>
        <div class="acc-body"><div class="inner">
<?php foreach ($papers as $p): ?>          <p style="font-size:13px;line-height:1.6;margin-bottom:10px"><?= e($p['title']) ?></p>
<?php endforeach; ?>        </div></div>
      </div></div>
    </div>

<?php $patents = (new Collection('content_cards'))->published("grp='zhang_patents'"); ?>
    <div class="card reveal" style="margin-top:16px">
      <h3 style="font-size:17px">🧾 专利成果汇总</h3>
      <p style="color:var(--ink-2);margin-top:6px"><?= snip('about.zhang.patents_desc') ?><span style="color:#c0392b;font-weight:800;font-size:22px"><?= snip('about.zhang.patents_count') ?></span> 项</p>
      <div class="accordion zhang-acc" style="margin-top:12px"><div class="acc-item">
        <div class="acc-head"><span>近 5 年专利</span><span class="arrow">▾</span></div>
        <div class="acc-body"><div class="inner">
<?php foreach ($patents as $p): ?>          <div class="res-item"><span class="k"><?= e($p['title']) ?></span><span class="lockbadge"><?= e($p['body']) ?></span></div>
<?php endforeach; ?>        </div></div>
      </div></div>
    </div>

<?php $honors = (new Collection('content_cards'))->published("grp='zhang_honors'"); ?>
    <div class="card reveal" style="margin-top:16px">
      <h3 style="font-size:17px">🏆 科研奖项与荣誉</h3>
      <p style="color:var(--ink-2);margin-top:6px">全国科技系统抗击新冠肺炎疫情先进个人　·　非洲科学院院士</p>
      <div class="accordion zhang-acc" style="margin-top:12px"><div class="acc-item">
        <div class="acc-head"><span>查看代表性荣誉</span><span class="arrow">▾</span></div>
        <div class="acc-body"><div class="inner">
<?php foreach ($honors as $h): ?>          <div class="res-item"><span class="k"><?= e($h['title']) ?></span><span class="lockbadge"><?= e($h['body']) ?></span></div>
<?php endforeach; ?>        </div></div>
      </div></div>
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
      <span class="chip-f" data-nf="team" style="padding:8px 16px;border-radius:999px;font-size:14px;font-weight:700;border:1px solid var(--line);background:#fff;color:var(--ink-2);cursor:pointer"><?= snip('about.news.chip_edu') ?></span>
      <span class="chip-f" data-nf="research" style="padding:8px 16px;border-radius:999px;font-size:14px;font-weight:700;border:1px solid var(--line);background:#fff;color:var(--ink-2);cursor:pointer"><?= snip('about.news.chip_res') ?></span>
      <span class="chip-f" data-nf="product" style="padding:8px 16px;border-radius:999px;font-size:14px;font-weight:700;border:1px solid var(--line);background:#fff;color:var(--ink-2);cursor:pointer"><?= snip('about.news.chip_daily') ?></span>
    </div>
    <div id="news-list">
<?php foreach ($news as $it):
    $tagClass = $it['category'] === 'research' ? 'tag green' : 'tag';
    $tagText  = ['research'=>'研究进展','team'=>'团队动态','product'=>'产品发布'][$it['category']] ?? '';
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
    <?php
    if (!function_exists('render_carousel')) {
        function render_carousel(array $cards, string $cid) {
            // 先过滤出有 img 的有效卡；全空则不输出空外壳
            $slides = [];
            foreach ($cards as $c) { $ex=json_decode($c['extra']?:'{}',true); $img=$ex['img']??''; if($img==='')continue; $slides[]=['img'=>$img,'title'=>$c['title']]; }
            if (!$slides) return;
            echo '<div class="intl-marquee" id="'.e($cid).'"><div class="intl-track">';
            // 复制两遍以配合 translateX(-50%) 无缝循环；第二遍对读屏隐藏，避免重复
            foreach ([$slides, $slides] as $pass => $group) {
                foreach ($group as $s) {
                    $hidden = $pass ? ' aria-hidden="true"' : '';
                    echo '<div class="intl-item"'.$hidden.'><img src="'.e($s['img']).'" alt="'.e($s['title']).'" loading="lazy"></div>';
                }
            }
            echo '</div></div>';
        }
    }
    $intlId = (new Collection('content_cards'))->published("grp='intl_id'");
    $intlPra = (new Collection('content_cards'))->published("grp='intl_pra'");
    ?>
    <div class="card reveal" style="margin-bottom:20px">
      <h3 style="font-size:18px"><?= snip('about.intl.cn_id_title') ?></h3>
      <div style="margin:8px 0 14px"><?= snip_paras('about.intl.cn_id_body') ?></div>
      <?php render_carousel($intlId, 'car-id'); ?>
    </div>
    <div class="card reveal d1" id="collab">
      <h3 style="font-size:18px"><?= snip('about.intl.pra_title') ?></h3>
      <div style="margin:8px 0 14px"><?= snip_paras('about.intl.pra_body') ?></div>
      <?php render_carousel($intlPra, 'car-pra'); ?>
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
    <?php include __DIR__ . '/partials/feedback-notice.php'; ?>
    <form class="feedback reveal d2" id="contact-form" method="post" action="feedback.php">
      <?= csrf_field() ?>
      <input type="hidden" name="from" value="about.php">
      <div style="position:absolute;left:-9999px" aria-hidden="true">
        <label>请勿填写<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
      </div>
      <div class="fb-row">
        <input type="text" name="name" placeholder="你的称呼" required>
        <input type="text" name="email" placeholder="联系方式：邮箱/学号" required>
      </div>
      <textarea name="message" placeholder="有建议、想合作、或愿意加入我们？留下你的信息，我们会尽快处理。" required></textarea>
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
// 张老师手风琴折叠
document.querySelectorAll('.acc-head').forEach(function(h){h.addEventListener('click',function(){var it=h.parentElement,b=h.nextElementSibling;it.classList.toggle('open');b.style.maxHeight=it.classList.contains('open')?b.scrollHeight+'px':'0';});});
</script>
</body>
</html>
