<?php
require __DIR__ . '/../app/bootstrap.php';
$active = 'index'; $navOnDark = true; $contactHref = '#contact';
$painCards = (new Collection('content_cards'))->published("grp = 'home_pain'");
$partners  = (new Collection('partners'))->published();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MabSeek 抗体求索 · AI 驱动的抗体发现平台 | 清华大学医学院</title>
<meta name="description" content="清华团队 × AI 大模型，让抗体发现从反复试错变成精准编程。MabSeek 抗体求索 · 清华大学医学院。">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🧬</text></svg>">
</head>
<body>

<?php include __DIR__ . '/partials/nav.php'; ?>

<!-- ============ L1 英雄区（深） ============ -->
<section class="hero-c">
  <canvas id="antibody-canvas"></canvas>
  <div class="hero-c-inner">
    <span class="eyebrow reveal"><?= snip('home.hero.eyebrow') ?></span>
    <h1 class="reveal d1"><?= snip_raw('home.hero.title') ?></h1>
    <p class="hero-c-sub reveal d2"><?= snip('home.hero.sub') ?></p>
  </div>
  <a href="agent.php" class="btn btn-green btn-lg hero-c-cta reveal d3"><?= snip('home.hero.cta') ?></a>
</section>

<!-- ============ L2 我们能做什么（浅） ============ -->
<section class="section section-light">
  <div class="container">
    <div style="max-width:820px">
      <span class="eyebrow green reveal"><?= snip('home.can.eyebrow') ?></span>
      <h2 class="section-title reveal d1"><?= snip_raw('home.can.title') ?></h2>
      <p class="section-sub reveal d2"><?= snip('home.can.sub') ?></p>
      <a href="technology.php" class="link-more reveal d3" style="margin-top:18px"><?= snip('home.can.link') ?></a>
    </div>
  </div>
</section>

<!-- ============ L3 四大痛点 × MabSeek 解法（深） ============ -->
<section class="section section-dark">
  <div class="container">
    <div class="text-center">
      <span class="eyebrow reveal"><?= snip('home.pain.eyebrow') ?></span>
      <h2 class="section-title reveal d1"><?= snip_raw('home.pain.title') ?></h2>
    </div>
    <div class="pain-grid">
<?php foreach ($painCards as $i => $c): $ex = json_decode($c['extra'] ?: '{}', true); ?>
      <div class="pain-cell reveal<?= $i ? ' d' . $i : '' ?>">
        <div class="p-title"><?= e($c['title']) ?></div>
        <p><?= e($c['body']) ?></p>
        <div class="p-fix"><?= e($ex['fix'] ?? '') ?></div>
      </div>
<?php endforeach; ?>
    </div>
    <div class="text-center" style="margin-top:30px">
      <a href="technology.php" class="btn btn-outline reveal"><?= snip('home.pain.cta') ?></a>
    </div>
  </div>
</section>

<!-- ============ L4 我们的近况（浅） ============ -->
<section class="section section-light">
  <div class="container">
    <div class="text-center" style="margin-bottom:8px">
      <span class="eyebrow green reveal"><?= snip('home.news.eyebrow') ?></span>
      <h2 class="section-title reveal d1"><?= snip('home.news.title') ?></h2>
    </div>
    <div class="news-scroller reveal d2">
<?php for ($n = 1; $n <= 4; $n++): ?>
      <div class="news-card" data-demo="打开新闻详情：<?= $n === 1 ? '平台技术升级' : ($n === 2 ? '交互式课程上线' : ($n === 3 ? '顶刊发表' : '国际交流')) ?>">
        <div class="nc-thumb"><?= snip("home.newscard.$n.thumb") ?></div>
        <div class="nc-body"><div class="nc-date"><?= snip("home.newscard.$n.date") ?></div><h4><?= snip("home.newscard.$n.title") ?></h4><p><?= snip("home.newscard.$n.body") ?></p></div>
      </div>
<?php endfor; ?>
    </div>
    <div class="text-center"><a href="about.php#news" class="link-more reveal"><?= snip('home.news.link') ?></a></div>
  </div>
</section>

<!-- ============ L5 合作伙伴 + 联系我们（深） ============ -->
<section class="section section-dark" id="contact">
  <div class="container">
    <div class="text-center" style="margin-bottom:36px">
      <span class="eyebrow reveal"><?= snip('home.contact.eyebrow') ?></span>
      <h2 class="section-title reveal d1"><?= snip_raw('home.contact.title') ?></h2>
    </div>
    <div class="logo-wall reveal" style="margin-bottom:40px">
<?php foreach ($partners as $p): ?>
      <a class="logo-chip" data-demo="<?= e($p['demo']) ?>"><span class="mark"><?= e($p['mark']) ?></span><?= e($p['name']) ?><?= $p['sub'] !== '' ? '<small>' . e($p['sub']) . '</small>' : '' ?></a>
<?php endforeach; ?>
    </div>
    <div class="text-center">
      <h3 style="color:#fff;font-size:22px"><?= snip('home.contact.h3') ?></h3>
      <p class="section-sub" style="margin:10px auto 0"><?= snip('contact.email') ?> · <?= snip('contact.org') ?></p>
    </div>
    <form class="feedback" id="contact-form">
      <div class="fb-row">
        <input type="text" name="name" placeholder="你的称呼" required>
        <input type="email" name="email" placeholder="邮箱" required>
      </div>
      <textarea name="message" placeholder="简单描述你的靶点 / 需求 / 合作意向" required></textarea>
      <button type="submit" class="btn btn-green fb-submit">提交反馈</button>
    </form>
  </div>
</section>

<?php include __DIR__ . '/partials/footer.php'; ?>

<script src="assets/js/main.js"></script>
<script src="assets/js/hero-anim.js"></script>
</body>
</html>
