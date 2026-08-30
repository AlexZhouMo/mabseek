<?php /* 全站统一页脚 */ ?>
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <div class="brand"><span class="logo"><svg viewBox="0 0 24 24" fill="none"><path d="M7 3v4c0 2.8 2.2 5 5 5s5 2.2 5 5v4M17 3v4c0 2.8-2.2 5-5 5" stroke="white" stroke-width="2" stroke-linecap="round"/></svg></span><span>MabSeek<small>抗体求索 · 清华大学医学院</small></span></div>
        <p><?= snip('footer.brand.tagline') ?></p>
        <div class="footer-social" style="margin-top:18px">
          <span title="微信" data-demo="扫码关注 MabSeek 公众号">💬</span>
          <span title="邮箱" data-demo="联系邮箱：<?= snip('contact.email') ?>">✉️</span>
        </div>
      </div>
      <div><h5>探索</h5><ul>
        <li><a href="technology.php">技术平台</a></li>
        <li><a href="agent.php">Antibody Agent</a></li>
        <li><a href="education.php">教育</a></li>
      </ul></div>
      <div><h5>社区</h5><ul>
        <li><a href="forum.php">论坛</a></li>
        <li><a href="about.php">了解我们</a></li>
        <li><a href="about.php#news">新闻活动</a></li>
      </ul></div>
      <div><h5>联系</h5><ul>
        <li><a href="index.php#contact">联系我们</a></li>
        <li><a href="mailto:<?= snip('contact.email') ?>"><?= snip('contact.email') ?></a></li>
        <li><?= snip('contact.org') ?></li>
      </ul></div>
    </div>
    <div class="footer-bottom">
      <span><?= snip('footer.copyright') ?></span>
      <span><?= snip('footer.slogan') ?></span>
    </div>
  </div>
</footer>
