/* ============================================================
   MabSeek 教育 — 交互式知识图谱
   纯 SVG + 原生 JS：点击节点高亮邻居、显示详情、一键唤起 AI 咨询
   ============================================================ */
(function () {
  'use strict';
  var host = document.getElementById('kg');
  if (!host) return;

  // 节点：《疫苗的力量》课程知识点 + 三层图谱能力
  var nodes = [
    { id: 'center', label: '疫苗的力量', x: 400, y: 250, r: 46, type: 'center',
      desc: '课程核心：从病毒免疫到疫苗研发的完整知识体系，元视频拆解 + 分层知识图谱。' },
    { id: 'type',   label: '疫苗类型', x: 200, y: 120, r: 34, type: 'free',
      desc: '灭活、减毒、mRNA、载体、重组蛋白疫苗的原理与适用场景。' },
    { id: 'immune', label: '免疫应答', x: 610, y: 120, r: 34, type: 'free',
      desc: '固有免疫与适应性免疫、抗体产生机制、免疫记忆的形成。' },
    { id: 'crowd',  label: '接种人群', x: 640, y: 300, r: 32, type: 'free',
      desc: '儿童、老人、孕妇、免疫低下人群的接种策略与注意事项。' },
    { id: 'adverse',label: '不良反应', x: 520, y: 400, r: 32, type: 'token',
      desc: 'Token 进阶：不良反应识别、分级与处置，结合真实病例数据的个性化路径。' },
    { id: 'paper',  label: '相关论文', x: 220, y: 390, r: 34, type: 'ai',
      desc: 'AI 文献生成：导入论文/专利，自动梳理逻辑脉络，生成专属知识图谱。' },
    { id: 'exp',    label: '实验经验', x: 120, y: 270, r: 32, type: 'token',
      desc: 'Token 进阶：课题组一线实验 Knowhow 与踩坑经验，个性化学习路径。' }
  ];
  var edges = [
    ['center','type'],['center','immune'],['center','crowd'],
    ['center','adverse'],['center','paper'],['center','exp'],
    ['type','immune'],['immune','crowd'],['crowd','adverse'],
    ['paper','exp'],['exp','type'],['adverse','paper']
  ];

  var TYPE_COLOR = { center:'#6D3BEB', free:'#00E0A4', token:'#7C4DFF', ai:'#4b6bff' };
  var TYPE_NAME  = { center:'课程核心', free:'免费基础版', token:'Token 进阶', ai:'AI 文献生成' };

  var W = 800, H = 500;
  var ns = 'http://www.w3.org/2000/svg';
  var svg = document.createElementNS(ns, 'svg');
  svg.setAttribute('viewBox', '0 0 ' + W + ' ' + H);
  svg.setAttribute('class', 'kg-svg');
  svg.style.width = '100%'; svg.style.height = 'auto'; svg.style.display = 'block';

  // 边
  var edgeEls = {};
  edges.forEach(function (e, i) {
    var a = byId(e[0]), b = byId(e[1]);
    var line = document.createElementNS(ns, 'line');
    line.setAttribute('x1', a.x); line.setAttribute('y1', a.y);
    line.setAttribute('x2', b.x); line.setAttribute('y2', b.y);
    line.setAttribute('stroke', '#C9CEE8'); line.setAttribute('stroke-width', '1.6');
    line.setAttribute('class', 'kg-edge');
    line.style.transition = 'stroke .25s, stroke-width .25s, opacity .25s';
    svg.appendChild(line);
    edgeEls[e[0] + '_' + e[1]] = line;
  });

  // 节点
  var nodeEls = {};
  nodes.forEach(function (n) {
    var g = document.createElementNS(ns, 'g');
    g.setAttribute('class', 'kg-node');
    g.style.cursor = 'pointer';
    g.style.transition = 'transform .2s';

    var glow = document.createElementNS(ns, 'circle');
    glow.setAttribute('cx', n.x); glow.setAttribute('cy', n.y);
    glow.setAttribute('r', n.r + 8); glow.setAttribute('fill', TYPE_COLOR[n.type]);
    glow.setAttribute('opacity', '0.14');

    var c = document.createElementNS(ns, 'circle');
    c.setAttribute('cx', n.x); c.setAttribute('cy', n.y); c.setAttribute('r', n.r);
    c.setAttribute('fill', TYPE_COLOR[n.type]);
    c.style.transition = 'r .2s, filter .2s';

    var t = document.createElementNS(ns, 'text');
    t.setAttribute('x', n.x); t.setAttribute('y', n.y);
    t.setAttribute('text-anchor', 'middle'); t.setAttribute('dominant-baseline', 'central');
    t.setAttribute('fill', '#fff');
    t.setAttribute('font-size', n.type === 'center' ? '15' : '13');
    t.setAttribute('font-weight', '700');
    t.style.pointerEvents = 'none';
    t.textContent = n.label;

    g.appendChild(glow); g.appendChild(c); g.appendChild(t);
    g.addEventListener('mouseenter', function () { g.style.transform = 'scale(1.06)'; g.style.transformOrigin = n.x + 'px ' + n.y + 'px'; });
    g.addEventListener('mouseleave', function () { g.style.transform = 'scale(1)'; });
    g.addEventListener('click', function () { selectNode(n.id); });
    svg.appendChild(g);
    nodeEls[n.id] = { g: g, glow: glow, circle: c };
  });

  host.appendChild(svg);

  // 详情面板
  var panel = document.getElementById('kg-panel');

  function selectNode(id) {
    var neighbors = {};
    neighbors[id] = true;
    edges.forEach(function (e) {
      if (e[0] === id) neighbors[e[1]] = true;
      if (e[1] === id) neighbors[e[0]] = true;
    });
    // 节点淡入淡出
    nodes.forEach(function (n) {
      var el = nodeEls[n.id];
      el.g.style.opacity = neighbors[n.id] ? '1' : '0.28';
    });
    // 边高亮
    edges.forEach(function (e) {
      var line = edgeEls[e[0] + '_' + e[1]];
      var on = (e[0] === id || e[1] === id);
      line.setAttribute('stroke', on ? '#6D3BEB' : '#C9CEE8');
      line.setAttribute('stroke-width', on ? '2.4' : '1.6');
      line.style.opacity = on ? '1' : '0.4';
    });
    // 面板
    var n = byId(id);
    if (panel) {
      panel.innerHTML =
        '<span class="tag" style="background:' + hexA(TYPE_COLOR[n.type], .12) + ';color:' + TYPE_COLOR[n.type] + ';border-color:' + hexA(TYPE_COLOR[n.type], .3) + '">' + TYPE_NAME[n.type] + '</span>' +
        '<h4 style="font-size:20px;margin:12px 0 8px">' + n.label + '</h4>' +
        '<p style="color:var(--ink-3);font-size:15px">' + n.desc + '</p>' +
        '<div style="display:flex;gap:10px;margin-top:16px;flex-wrap:wrap">' +
          '<a href="agent.html" class="btn btn-purple" style="padding:10px 18px;font-size:14px">🤖 唤起 AI 咨询</a>' +
          '<a href="#video" class="btn btn-outline" style="padding:10px 18px;font-size:14px">▶ 跳转对应片段</a>' +
        '</div>';
      panel.classList.add('active');
    }
  }

  function byId(id) { for (var i = 0; i < nodes.length; i++) if (nodes[i].id === id) return nodes[i]; }
  function hexA(hex, a) {
    var n = parseInt(hex.slice(1), 16);
    return 'rgba(' + (n >> 16 & 255) + ',' + (n >> 8 & 255) + ',' + (n & 255) + ',' + a + ')';
  }

  // 默认选中中心
  selectNode('center');
})();
