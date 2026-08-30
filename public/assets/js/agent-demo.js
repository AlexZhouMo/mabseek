/* ============================================================
   MabSeek — Antibody Agent 对话演示
   打字机式流程演示：一句话 → AI 设计 → 湿实验下单
   ============================================================ */
(function () {
  'use strict';
  var feed = document.getElementById('chat-feed');
  if (!feed) return;
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  var script = [
    { role: 'user', text: '帮我设计一个靶向 PD-L1 的高亲和力纳米抗体，并给出可下单方案。' },
    { role: 'agent', kind: 'step', text: '🔍 检索知识库与文献：命中 PD-L1 相关文献 128 篇、结构 6 个…' },
    { role: 'agent', kind: 'step', text: '🧬 生成候选序列：基于结构约束设计 240 个候选 VHH…' },
    { role: 'agent', kind: 'step', text: '📈 亲和力预测 + 可开发性评估：虚拟筛选保留 18 个高潜力序列…' },
    { role: 'agent', kind: 'step', text: '🧩 结构分析：Top-3 完成表位对接与结合能计算…' },
    { role: 'agent', kind: 'result', text: '✅ 方案就绪：Top-1 预测 KD ≈ 0.8 nM，可开发性良好。已生成《Nb_affinity_maturation_report.md》，可一键下单表达纯化与功能验证。' }
  ];

  var i = 0;
  function bubble(role) {
    var wrap = document.createElement('div');
    wrap.className = 'msg ' + role;
    var av = document.createElement('span');
    av.className = 'msg-av';
    av.textContent = role === 'user' ? '你' : 'AI';
    var b = document.createElement('div');
    b.className = 'msg-bubble';
    if (role === 'user') { wrap.appendChild(b); wrap.appendChild(av); }
    else { wrap.appendChild(av); wrap.appendChild(b); }
    feed.appendChild(wrap);
    feed.scrollTop = feed.scrollHeight;
    return b;
  }

  function typeText(el, text, done) {
    if (reduce) { el.textContent = text; done && done(); return; }
    var n = 0;
    (function tick() {
      el.textContent = text.slice(0, n);
      feed.scrollTop = feed.scrollHeight;
      if (n++ < text.length) setTimeout(tick, 18);
      else done && done();
    })();
  }

  function next() {
    if (i >= script.length) {
      // 结束后追加下单按钮
      var cta = document.createElement('div');
      cta.className = 'msg agent';
      cta.innerHTML = '<span class="msg-av">AI</span><div class="msg-bubble" style="background:transparent;border:0;padding:0">' +
        '<a href="#wetlab" class="btn btn-green" style="padding:10px 18px;font-size:14px">🧪 一键下单湿实验</a></div>';
      feed.appendChild(cta);
      feed.scrollTop = feed.scrollHeight;
      // 循环
      setTimeout(function () { feed.innerHTML = ''; i = 0; next(); }, 6000);
      return;
    }
    var item = script[i++];
    var b = bubble(item.role);
    if (item.kind === 'result') b.classList.add('is-result');
    if (item.kind === 'step') b.classList.add('is-step');
    var delay = item.role === 'user' ? 700 : 500;
    // 思考中占位
    if (item.role === 'agent' && !reduce) {
      b.innerHTML = '<span class="dots"><i></i><i></i><i></i></span>';
      setTimeout(function () { b.textContent = ''; typeText(b, item.text, function () { setTimeout(next, delay); }); }, 650);
    } else {
      typeText(b, item.text, function () { setTimeout(next, delay); });
    }
  }

  // 进入视口后启动
  if ('IntersectionObserver' in window) {
    var started = false;
    var io = new IntersectionObserver(function (e) {
      if (e[0].isIntersecting && !started) { started = true; next(); io.disconnect(); }
    }, { threshold: 0.3 });
    io.observe(feed);
  } else { next(); }
})();
