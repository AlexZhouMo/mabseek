/* MabSeek 首页英雄区：靶点 → 抗体 逐步组装并对接 的循环动画。纯 Canvas2D，无依赖。 */
(function () {
  'use strict';
  var canvas = document.getElementById('antibody-canvas');
  if (!canvas || !canvas.getContext) return;
  var ctx = canvas.getContext('2d');
  var DPR = Math.min(window.devicePixelRatio || 1, 2);
  var W, H, cx, cy;
  var PURPLE = '124,77,255', NEON = '0,224,164', BLUE = '75,107,255';

  // 抗体 Y 形骨架的目标锚点（相对中心的比例坐标），粒子最终归位于此
  var YSHAPE = [
    [0, 0.18], [0, 0.05], [0, -0.08],            // 主干 (Fc)
    [-0.13, -0.22], [-0.22, -0.32], [-0.30, -0.4],// 左臂 (Fab)
    [0.13, -0.22], [0.22, -0.32], [0.30, -0.4]    // 右臂 (Fab)
  ];
  var particles = [];   // 组装抗体的粒子
  var target = [];      // 右侧"靶点"点云

  function build() {
    W = canvas.clientWidth; H = canvas.clientHeight;
    canvas.width = W * DPR; canvas.height = H * DPR;
    ctx.setTransform(DPR, 0, 0, DPR, 0, 0);
    cx = W * 0.42; cy = H * 0.56;
    var scale = Math.min(W, H) * 0.62;

    particles = YSHAPE.map(function (p) {
      return {
        hx: cx + p[0] * scale, hy: cy + p[1] * scale,           // 归位坐标
        x: Math.random() * W, y: Math.random() * H,             // 起始随机
        r: 6 + Math.random() * 4,
        c: [PURPLE, NEON, BLUE][(Math.random() * 3) | 0]
      };
    });

    target = [];
    var tx = cx + scale * 0.62, ty = cy - scale * 0.36, tr = scale * 0.16;
    for (var i = 0; i < 26; i++) {
      var a = Math.random() * Math.PI * 2, rad = Math.random() * tr;
      target.push({ x: tx + Math.cos(a) * rad, y: ty + Math.sin(a) * rad, r: 2 + Math.random() * 3 });
    }
  }

  function drawTarget() {
    for (var i = 0; i < target.length; i++) {
      var t = target[i];
      ctx.beginPath(); ctx.arc(t.x, t.y, t.r, 0, Math.PI * 2);
      ctx.fillStyle = 'rgba(255,255,255,.45)'; ctx.fill();
    }
  }

  function drawAntibody(prog) {
    // prog: 0→1 组装进度。连接骨架线
    ctx.lineWidth = 2;
    ctx.strokeStyle = 'rgba(' + NEON + ',' + (0.5 * prog) + ')';
    var seg = [[0,1],[1,2],[2,3],[3,4],[4,5],[2,6],[6,7],[7,8]];
    for (var s = 0; s < seg.length; s++) {
      var a = particles[seg[s][0]], b = particles[seg[s][1]];
      ctx.beginPath(); ctx.moveTo(a.x, a.y); ctx.lineTo(b.x, b.y); ctx.stroke();
    }
    for (var i = 0; i < particles.length; i++) {
      var p = particles[i];
      ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fillStyle = 'rgba(' + p.c + ',.85)'; ctx.fill();
      ctx.beginPath(); ctx.arc(p.x, p.y, p.r + 5, 0, Math.PI * 2);
      ctx.fillStyle = 'rgba(' + p.c + ',.12)'; ctx.fill();
    }
  }

  var t0 = null, CYCLE = 6000; // 6s 一循环
  function frame(ts) {
    if (t0 === null) t0 = ts;
    var loop = ((ts - t0) % CYCLE) / CYCLE;         // 0→1
    var assemble = Math.min(loop / 0.55, 1);         // 前 55% 组装
    var eased = 1 - Math.pow(1 - assemble, 3);
    var dock = loop > 0.6 ? Math.min((loop - 0.6) / 0.25, 1) : 0; // 60%~85% 对接位移

    ctx.clearRect(0, 0, W, H);
    // 背景光晕
    var g = ctx.createRadialGradient(cx, cy, 0, cx, cy, Math.max(W, H) * 0.5);
    g.addColorStop(0, 'rgba(124,77,255,.16)'); g.addColorStop(1, 'rgba(7,10,20,0)');
    ctx.fillStyle = g; ctx.fillRect(0, 0, W, H);

    var shift = dock * (W * 0.06);
    for (var i = 0; i < particles.length; i++) {
      var p = particles[i];
      p.x += ((p.hx + shift) - p.x) * 0.06 * (0.3 + eased);
      p.y += (p.hy - p.y) * 0.06 * (0.3 + eased);
    }
    drawTarget();
    drawAntibody(eased);
    requestAnimationFrame(frame);
  }

  function staticFrame() {
    // reduced-motion：直接归位并画一帧
    ctx.clearRect(0, 0, W, H);
    for (var i = 0; i < particles.length; i++) { particles[i].x = particles[i].hx; particles[i].y = particles[i].hy; }
    drawTarget(); drawAntibody(1);
  }

  build();
  window.addEventListener('resize', function () { build(); if (reduced) staticFrame(); });
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (reduced) staticFrame(); else requestAnimationFrame(frame);
})();
