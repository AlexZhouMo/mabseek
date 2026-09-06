<?php
// 缩略图上传字段：file 选择 → 上传 public/upload.php → URL 写入隐藏字段 + 展示预览。
// 由调用方在 include 前赋值：
//   $coverName  string  隐藏字段 name（提交字段名，通常 'cover'）
//   $coverValue string  当前封面 URL（编辑页回填；无则空）
$coverName  = (string)($coverName ?? 'cover');
$coverValue = (string)($coverValue ?? '');
$hasCover   = $coverValue !== '';
?>
<div class="cover-field" data-cover-field data-upload-url="upload.php">
  <input type="hidden" name="<?= e($coverName) ?>" class="cover-src" value="<?= e($coverValue) ?>">
  <div class="cover-preview"<?= $hasCover ? '' : ' hidden' ?>>
    <img class="cover-img" src="<?= e($coverValue) ?>" alt="缩略图预览" style="max-width:220px;border-radius:10px;display:block">
  </div>
  <div style="margin-top:8px;display:flex;align-items:center;gap:10px">
    <label class="btn btn-outline" style="cursor:pointer;margin:0">
      选择图片<input type="file" class="cover-file" accept="image/jpeg,image/png,image/webp" hidden>
    </label>
    <span class="cover-hint" style="font-size:13px;color:var(--ink-3)"><?= $hasCover ? '已上传，可重新选择' : '支持 JPG/PNG/WebP，≤2MB' ?></span>
  </div>
</div>
<script>
(function () {
  var wrap = document.currentScript.previousElementSibling;
  while (wrap && !wrap.hasAttribute('data-cover-field')) { wrap = wrap.previousElementSibling; }
  if (!wrap) return;
  var form    = wrap.closest('form');
  var fileIn  = wrap.querySelector('.cover-file');
  var srcIn   = wrap.querySelector('.cover-src');
  var preview = wrap.querySelector('.cover-preview');
  var img     = wrap.querySelector('.cover-img');
  var hint    = wrap.querySelector('.cover-hint');
  var url     = wrap.getAttribute('data-upload-url');

  fileIn.addEventListener('change', function () {
    if (!fileIn.files || !fileIn.files[0]) return;
    if (!window.fetch) { alert('当前浏览器不支持图片上传'); return; }
    var token = form.querySelector('input[name="_csrf"]');
    var data = new FormData();
    data.append('file', fileIn.files[0]);
    if (token) data.append('_csrf', token.value);
    hint.textContent = '上传中…';
    fetch(url, { method: 'POST', body: data })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res && res.ok && res.url) {
          srcIn.value = res.url;
          img.src = res.url;
          preview.hidden = false;
          hint.textContent = '已上传，可重新选择';
        } else {
          hint.textContent = (res && res.error) ? res.error : '上传失败';
        }
      })
      .catch(function () { hint.textContent = '上传失败，请重试'; });
  });
})();
</script>
