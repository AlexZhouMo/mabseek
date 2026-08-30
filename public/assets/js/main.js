/* ============================================================
   MabSeek — 全站通用脚本
   导航 · 滚动渐显 · 指标数字滚动 · 首屏粒子抗体网络
   纯原生 JS，无依赖，浏览器直接打开即可运行
   ============================================================ */
(function () {
  'use strict';

  /* ---------- 导航：滚动阴影 + 移动端展开 ---------- */
  var nav = document.querySelector('.nav');
  if (nav) {
    var onScroll = function () {
      nav.classList.toggle('scrolled', window.scrollY > 8);
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    var toggle = nav.querySelector('.nav-toggle');
    if (toggle) toggle.addEventListener('click', function () { nav.classList.toggle('open'); });
    nav.querySelectorAll('.nav-links a').forEach(function (a) {
      a.addEventListener('click', function () { nav.classList.remove('open'); });
    });
  }

  /* ---------- 滚动渐显 ---------- */
  var reveals = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window && reveals.length) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    reveals.forEach(function (el) { io.observe(el); });
  } else {
    reveals.forEach(function (el) { el.classList.add('in'); });
  }

  /* ---------- 指标数字滚动 count-up ---------- */
  function animateNum(el) {
    var target = parseFloat(el.getAttribute('data-num'));
    var dur = 1400, start = null, dec = (target % 1 !== 0) ? 1 : 0;
    function step(ts) {
      if (!start) start = ts;
      var p = Math.min((ts - start) / dur, 1);
      var eased = 1 - Math.pow(1 - p, 3);
      el.textContent = (target * eased).toFixed(dec);
      if (p < 1) requestAnimationFrame(step);
      else el.textContent = target.toFixed(dec);
    }
    requestAnimationFrame(step);
  }
  var nums = document.querySelectorAll('[data-num]');
  if ('IntersectionObserver' in window && nums.length) {
    var io2 = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { animateNum(e.target); io2.unobserve(e.target); }
      });
    }, { threshold: 0.6 });
    nums.forEach(function (el) { io2.observe(el); });
  }

  /* ---------- 首屏粒子抗体网络 ---------- */
  var canvas = document.getElementById('hero-canvas');
  if (canvas && canvas.getContext) {
    var ctx = canvas.getContext('2d');
    var W, H, DPR = Math.min(window.devicePixelRatio || 1, 2);
    var pts = [];
    var COLORS = ['109,59,235', '0,224,164', '75,107,255'];

    function resize() {
      W = canvas.clientWidth; H = canvas.clientHeight;
      canvas.width = W * DPR; canvas.height = H * DPR;
      ctx.setTransform(DPR, 0, 0, DPR, 0, 0);
      var count = Math.round(Math.min(90, (W * H) / 14000));
      pts = [];
      for (var i = 0; i < count; i++) {
        pts.push({
          x: Math.random() * W, y: Math.random() * H,
          vx: (Math.random() - 0.5) * 0.35, vy: (Math.random() - 0.5) * 0.35,
          r: Math.random() * 2.4 + 1.2,
          c: COLORS[(Math.random() * COLORS.length) | 0]
        });
      }
    }

    function frame() {
      ctx.clearRect(0, 0, W, H);
      for (var i = 0; i < pts.length; i++) {
        var p = pts[i];
        p.x += p.vx; p.y += p.vy;
        if (p.x < 0 || p.x > W) p.vx *= -1;
        if (p.y < 0 || p.y > H) p.vy *= -1;
        for (var j = i + 1; j < pts.length; j++) {
          var q = pts[j], dx = p.x - q.x, dy = p.y - q.y, d = Math.sqrt(dx * dx + dy * dy);
          if (d < 128) {
            ctx.strokeStyle = 'rgba(' + p.c + ',' + (0.16 * (1 - d / 128)) + ')';
            ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(p.x, p.y); ctx.lineTo(q.x, q.y); ctx.stroke();
          }
        }
      }
      for (var k = 0; k < pts.length; k++) {
        var pt = pts[k];
        ctx.beginPath(); ctx.arc(pt.x, pt.y, pt.r, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(' + pt.c + ',0.55)'; ctx.fill();
      }
      requestAnimationFrame(frame);
    }
    resize();
    window.addEventListener('resize', resize);
    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) frame();
  }

  /* ---------- 通用：可点击卡片提示（演示占位链接） ---------- */
  document.querySelectorAll('[data-demo]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      e.preventDefault();
      var msg = el.getAttribute('data-demo') || '该功能将在正式版本上线';
      showToast(msg);
    });
  });

  var toastTimer;
  function showToast(msg) {
    var t = document.getElementById('mab-toast');
    if (!t) {
      t = document.createElement('div');
      t.id = 'mab-toast';
      t.style.cssText = 'position:fixed;left:50%;bottom:36px;transform:translateX(-50%) translateY(20px);' +
        'background:#10132B;color:#fff;padding:13px 22px;border-radius:999px;font-size:14px;font-weight:600;' +
        'box-shadow:0 12px 40px rgba(16,19,43,.3);z-index:3000;opacity:0;transition:.35s;pointer-events:none;max-width:86vw;';
      document.body.appendChild(t);
    }
    t.textContent = msg;
    requestAnimationFrame(function () { t.style.opacity = '1'; t.style.transform = 'translateX(-50%) translateY(0)'; });
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () {
      t.style.opacity = '0'; t.style.transform = 'translateX(-50%) translateY(20px)';
    }, 2600);
  }

  /* ---------- 快速反馈表单：前端模拟提交（无后端） ---------- */
  var fbForm = document.getElementById('contact-form');
  if (fbForm) {
    fbForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var name = (fbForm.querySelector('[name="name"]') || {}).value || '';
      showToast('已收到你的反馈' + (name ? ('，' + name) : '') + '！我们会尽快通过邮件联系你。');
      fbForm.reset();
    });
  }

  /* ---------- 技术平台架构图：触屏/点击展开节点详情（桌面 hover 走 CSS） ---------- */
  var archNodes = document.querySelectorAll('.arch-node');
  for (var ni = 0; ni < archNodes.length; ni++) {
    (function (node) {
      node.addEventListener('click', function () { node.classList.toggle('open'); });
    })(archNodes[ni]);
  }

  window.mabToast = showToast;
})();
