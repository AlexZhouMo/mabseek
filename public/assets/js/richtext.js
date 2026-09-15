(function () {
  var RTDBG = /[?&]rtdbg=1\b/.test(location.search);  // URL 带 ?rtdbg=1 时输出诊断日志
  document.querySelectorAll('[data-rt]').forEach(function (field) {
    var editor = field.querySelector('.rt-editor');
    var source = field.querySelector('.rt-source');
    var form = field.closest('form');
    if (!editor || !source || !form) return;
    var uploadUrl = field.getAttribute('data-upload-url') || 'admin.php?m=news&a=upload';
    var toolbar = field.querySelector('.rt-toolbar');

    // 工具栏按钮 mousedown 时阻止默认行为，避免编辑区失焦导致悬浮态闪跳
    if (toolbar) {
      toolbar.addEventListener('mousedown', function (e) { e.preventDefault(); });
    }

    // 悬浮态：编辑器聚焦时标记；失焦时延迟判定，避免选文字/点按钮的瞬时失焦导致闪烁
    var blurTimer = null;
    editor.addEventListener('focusin', function () {
      if (blurTimer) { clearTimeout(blurTimer); blurTimer = null; }
      field.setAttribute('data-rt-active', '1');
      syncFloat();
    });
    editor.addEventListener('focusout', function () {
      // 延迟到下一帧再判定：若焦点其实落回了本 rt-field（选文字、点工具栏、执行命令），
      // 则视为仍在编辑，不清除悬浮态；只有焦点确实移出整个字段时才 unfloat。
      if (blurTimer) clearTimeout(blurTimer);
      blurTimer = setTimeout(function () {
        blurTimer = null;
        var ae = document.activeElement;
        if (RTDBG) console.log('[rt] focusout-check activeElement=' + (ae && ae.nodeName) + ' inField=' + field.contains(ae));
        if (field.contains(ae)) return;   // 焦点仍在字段内，保持悬浮
        field.removeAttribute('data-rt-active');
        unfloat();
      }, 120);
    });

    // 占位块：悬浮时补上工具栏原本占据的高度，防止内容跳动
    var spacer = document.createElement('div');
    spacer.className = 'rt-toolbar-spacer';
    if (toolbar) toolbar.parentNode.insertBefore(spacer, toolbar.nextSibling);

    // 工具栏命令
    field.querySelectorAll('.rt-btn[data-cmd]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        editor.focus();
        var cmd = btn.getAttribute('data-cmd');
        var val = btn.getAttribute('data-val') || null;
        if (cmd === 'createLink') {
          var url = prompt('链接地址（http/https）：', 'https://');
          if (!url) return;
          document.execCommand('createLink', false, url);
        } else if (cmd === 'formatBlock') {
          document.execCommand('formatBlock', false, val);
        } else {
          document.execCommand(cmd, false, null);
        }
      });
    });

    // 插入图片：复用后台上传端点，返回站内 URL
    var imgBtn = field.querySelector('[data-rt-image]');
    if (imgBtn) {
      imgBtn.addEventListener('click', function () {
        var input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/jpeg,image/png,image/webp';
        input.addEventListener('change', function () {
          if (input.files && input.files[0]) uploadImage(input.files[0]);
        });
        input.click();
      });
    }

    function uploadImage(file) {
      if (!window.fetch) { alert('当前浏览器不支持图片上传'); return; }
      var token = form.querySelector('input[name="_csrf"]');
      var data = new FormData();
      data.append('file', file);
      if (token) data.append('_csrf', token.value);
      editor.focus();
      fetch(uploadUrl, { method: 'POST', body: data })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (res && res.ok && res.url) {
            var img = document.createElement('img');
            img.src = res.url;
            img.alt = '';
            document.execCommand('insertHTML', false, img.outerHTML);
          } else {
            alert('图片上传失败：' + ((res && res.error) || '未知错误'));
          }
        })
        .catch(function () { alert('图片上传失败：网络错误'); });
    }

    var floating = false;      // 当前是否悬浮

    // 探测视口顶部被固定/粘性栏（导航条 / 后台 topbar）占据的高度，
    // 悬浮工具栏应贴在其下方，避免被遮挡。
    function topOffset() {
      var max = 0;
      var bars = document.querySelectorAll('.nav, .admin-topbar');
      for (var i = 0; i < bars.length; i++) {
        var el = bars[i];
        var pos = getComputedStyle(el).position;
        if (pos !== 'fixed' && pos !== 'sticky') continue;
        var r = el.getBoundingClientRect();
        // 仅统计当前确实吸附在视口顶部的栏
        if (r.top <= 1 && r.bottom > max) max = r.bottom;
      }
      return max;
    }

    function applyFloatPosition() {
      if (!floating || !toolbar) return;
      var rect = field.getBoundingClientRect();
      var top = topOffset();
      toolbar.style.left = rect.left + 'px';
      toolbar.style.width = rect.width + 'px';
      toolbar.style.top = top + 'px';
    }

    // 触发条件：编辑框聚焦中，且其顶部已滚过视口顶部（含被顶栏遮挡的部分）、
    // 底部仍在视口下方（即编辑框正被滚动“穿过”，但编辑区尚未结束）
    function syncFloat() {
      if (!toolbar) return;
      var rect = field.getBoundingClientRect();
      var active = field.getAttribute('data-rt-active') === '1';
      var top = topOffset();
      var shouldFloat = active && rect.top < top && rect.bottom > top + toolbar.offsetHeight;
      if (RTDBG) {
        console.log('[rt] sync active=' + active + ' top=' + top +
          ' rectTop=' + Math.round(rect.top) + ' rectBottom=' + Math.round(rect.bottom) +
          ' tbH=' + toolbar.offsetHeight + ' shouldFloat=' + shouldFloat + ' floating=' + floating);
      }
      if (shouldFloat && !floating) {
        floating = true;
        spacer.style.height = toolbar.offsetHeight + 'px';
        field.classList.add('rt-floating');
        applyFloatPosition();
      } else if (shouldFloat && floating) {
        applyFloatPosition();
      } else if (!shouldFloat && floating) {
        unfloat();
      }
    }

    function unfloat() {
      if (!floating) return;
      floating = false;
      field.classList.remove('rt-floating');
      if (toolbar) { toolbar.style.left = ''; toolbar.style.width = ''; toolbar.style.top = ''; }
      spacer.style.height = '';
    }

    // scroll 事件不冒泡，用捕获阶段监听，兼容滚动发生在任意祖先容器的情况
    window.addEventListener('scroll', function (e) {
      if (RTDBG) console.log('[rt] scroll from ' + (e.target.nodeName || e.target) );
      syncFloat();
    }, { passive: true, capture: true });
    window.addEventListener('resize', syncFloat);

    // 提交前把编辑区内容同步进隐藏 textarea（服务端会再净化）
    form.addEventListener('submit', function () {
      source.value = editor.innerHTML;
    });
  });
})();
