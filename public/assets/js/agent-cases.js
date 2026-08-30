/* MabSeek · Antibody Agent 页「案例示范」循环动图
   守卫式：无 .case-anim 元素则不运行（其它页面惰性、零副作用）。
   prefers-reduced-motion: reduce 时显示静止终态、不启动循环。 */
(function () {
  if (!document.querySelector('.case-anim')) return;
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* 卡1：打字机提示 + 候选序列逐格点亮，循环 */
  var prompt = document.querySelector('.ca1-prompt');
  var seq = document.querySelectorAll('.ca1-seq i');
  var promptText = '描述靶点与研发目标…';
  if (prompt && seq.length) {
    if (reduce) {
      prompt.textContent = promptText;
      for (var s0 = 0; s0 < seq.length; s0++) { seq[s0].classList.add('on'); }
    } else {
      var run1 = function () {
        var ti = 0;
        prompt.textContent = '';
        for (var s = 0; s < seq.length; s++) { seq[s].classList.remove('on'); }
        var typer = setInterval(function () {
          prompt.textContent = promptText.slice(0, ti + 1);
          ti++;
          if (ti >= promptText.length) {
            clearInterval(typer);
            var si = 0;
            var filler = setInterval(function () {
              if (seq[si]) { seq[si].classList.add('on'); }
              si++;
              if (si >= seq.length) { clearInterval(filler); setTimeout(run1, 1600); }
            }, 170);
          }
        }, 90);
      };
      run1();
    }
  }

  /* 卡2：候选条按位次循环重排（宽度纯样式，不显示数值） */
  var bars = document.querySelectorAll('.ca2-bar');
  if (bars.length) {
    var widths = [92, 74, 60, 48, 36];
    for (var b = 0; b < bars.length; b++) { bars[b].style.width = widths[b] + '%'; }
    var slots = [0, 1, 2, 3, 4];
    var place = function () {
      for (var i = 0; i < bars.length; i++) {
        bars[i].style.transform = 'translateY(' + (slots[i] * 22) + 'px)';
      }
    };
    place();
    if (!reduce) { setInterval(function () { slots.unshift(slots.pop()); place(); }, 1800); }
  }
})();
