<?php
// 反馈提交结果提示条：根据 ?fb= 渲染。用在 #contact 表单上方。
$fb = (string)($_GET['fb'] ?? '');
if ($fb === 'ok'): ?>
    <div class="fb-notice fb-ok" role="status">✓ 反馈已收到，我们会尽快通过邮件联系你。</div>
<?php elseif ($fb === 'err'): ?>
    <div class="fb-notice fb-err" role="alert">✗ 请检查填写内容：称呼、有效邮箱与反馈内容均为必填。</div>
<?php elseif ($fb === 'rate'): ?>
    <div class="fb-notice fb-err" role="alert">✗ 提交过于频繁，请稍后再试。</div>
<?php endif; ?>
