(function () {
  document.querySelectorAll('[data-rt]').forEach(function (field) {
    var editor = field.querySelector('.rt-editor');
    var source = field.querySelector('.rt-source');
    var form = field.closest('form');
    if (!editor || !source || !form) return;

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
      var token = form.querySelector('input[name="_csrf"]');
      var data = new FormData();
      data.append('file', file);
      if (token) data.append('_csrf', token.value);
      editor.focus();
      fetch('admin.php?m=news&a=upload', { method: 'POST', body: data })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (res && res.ok && res.url) {
            document.execCommand('insertHTML', false, '<img src="' + res.url + '" alt="">');
          } else {
            alert('图片上传失败：' + ((res && res.error) || '未知错误'));
          }
        })
        .catch(function () { alert('图片上传失败：网络错误'); });
    }

    // 提交前把编辑区内容同步进隐藏 textarea（服务端会再净化）
    form.addEventListener('submit', function () {
      source.value = editor.innerHTML;
    });
  });
})();
