(function () {
  document.querySelectorAll('[data-rt]').forEach(function (field) {
    var editor = field.querySelector('.rt-editor');
    var source = field.querySelector('.rt-source');
    var form = field.closest('form');
    if (!editor || !source || !form) return;
    var uploadUrl = field.getAttribute('data-upload-url') || 'admin.php?m=news&a=upload';
    var toolbar = field.querySelector('.rt-toolbar');

    // 悬浮态：编辑器聚焦时标记，失焦时清除
    editor.addEventListener('focusin', function () { field.setAttribute('data-rt-active', '1'); syncFloat(); });
    editor.addEventListener('focusout', function () { field.removeAttribute('data-rt-active'); unfloat(); });

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

    function applyFloatPosition() {
      if (!floating || !toolbar) return;
      var rect = field.getBoundingClientRect();
      toolbar.style.left = rect.left + 'px';
      toolbar.style.width = rect.width + 'px';
    }

    // 触发条件：编辑框聚焦中，且其顶部已滚过视口顶部、底部仍在视口下方
    // （即编辑框正被滚动“穿过”，工具栏原位已离开视口，但编辑区尚未结束）
    function syncFloat() {
      if (!toolbar) return;
      var rect = field.getBoundingClientRect();
      var active = field.getAttribute('data-rt-active') === '1';
      var shouldFloat = active && rect.top < 0 && rect.bottom > toolbar.offsetHeight;
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
      if (toolbar) { toolbar.style.left = ''; toolbar.style.width = ''; }
      spacer.style.height = '';
    }

    window.addEventListener('scroll', syncFloat, { passive: true });
    window.addEventListener('resize', syncFloat);

    // 提交前把编辑区内容同步进隐藏 textarea（服务端会再净化）
    form.addEventListener('submit', function () {
      source.value = editor.innerHTML;
    });
  });
})();
