<?php
// 富文本编辑器字段：工具栏 + contenteditable 编辑区 + 隐藏 textarea。
// 由调用方在 include 前赋值以下局部变量：
//   $rtName      string  隐藏 textarea 的 name（提交字段名）
//   $rtValue     string  当前值（HTML；此处再净化一次，杜绝回填自 XSS）
//   $rtUploadUrl string  图片上传端点 URL（写入 data-upload-url）
//   $rtRequired  bool    可选，是否必填（仅前端提示；权威校验在服务端）
$rtClean = sanitize_html((string)($rtValue ?? ''));
?>
<div class="rt-field" data-rt data-upload-url="<?= e((string)($rtUploadUrl ?? '')) ?>">
  <div class="rt-toolbar">
    <button type="button" class="rt-btn" data-cmd="bold" title="加粗"><b>B</b></button>
    <button type="button" class="rt-btn" data-cmd="italic" title="斜体"><i>I</i></button>
    <button type="button" class="rt-btn" data-cmd="formatBlock" data-val="h2">H2</button>
    <button type="button" class="rt-btn" data-cmd="formatBlock" data-val="h3">H3</button>
    <button type="button" class="rt-btn" data-cmd="insertUnorderedList" title="无序列表">• 列表</button>
    <button type="button" class="rt-btn" data-cmd="insertOrderedList" title="有序列表">1. 列表</button>
    <button type="button" class="rt-btn" data-cmd="createLink" title="链接">链接</button>
    <button type="button" class="rt-btn" data-rt-image title="插入图片">插入图片</button>
  </div>
  <div class="rt-editor" contenteditable="true"><?= $rtClean ?></div>
  <textarea name="<?= e((string)($rtName ?? '')) ?>" class="rt-source" hidden><?= e($rtClean) ?></textarea>
</div>
