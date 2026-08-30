<?php
// 文案片段编辑器：按分组展示全部 snippets，单表单一次保存所有改动。
// 由 admin.php 在外壳输出后 include（勿加 declare / 勿 require crud.php）。
// POST 时先清空外壳缓冲、校验 CSRF，仅对“值有变化的已知片段”执行 updateValue + audit，再 PRG 重定向。

// 前台以 snip_raw() 原样渲染（含版式标记）的片段——编辑时提示谨慎，勿破坏既有标记结构。
$rawKeys = [
    'home.hero.title','home.can.title','home.pain.title','home.contact.title',
    'tech.hero.title','tech.arch.title','tech.p1.title','tech.p2.title','tech.p3.title',
    'agent.hero.title','agent.cap.title','agent.case.title','agent.flow.title',
    'forum.hero.title','forum.lines.title','edu.hero.title',
    'about.hero.title','about.intl.title','about.contact.title',
];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    while (ob_get_level() > 0) { ob_end_clean(); }   // 丢弃外壳缓冲，稍后干净重定向
    csrf_verify_or_die();
    $submitted = $_POST['s'] ?? [];
    if (is_array($submitted)) {
        // 以库内现有片段为准：仅更新“存在且值有变化”的项，杜绝未知 id 写入，并避免刷屏审计
        foreach (Snippets::allGrouped() as $rows) {
            foreach ($rows as $r) {
                $id  = (int)$r['id'];
                $key = (string)$id;
                if (!array_key_exists($key, $submitted) || !is_string($submitted[$key])) continue;
                $new = $submitted[$key];
                if ($new === (string)$r['value']) continue;
                Snippets::updateValue($id, $new);
                audit('update', 'snippet', (string)$r['skey']);
            }
        }
    }
    flash_set('ok', '文案已保存');
    redirect('admin.php?m=snippets');
}

$grouped    = Snippets::allGrouped();
$rawSet     = array_flip($rawKeys);
?>
<form method="post" action="admin.php?m=snippets">
  <?= csrf_field() ?>
  <div class="page-head">
    <h3>文案片段</h3>
    <button class="abtn abtn-primary" type="submit">保存全部</button>
  </div>
<?php if (empty($grouped)): ?>
  <div class="card"><p style="color:var(--a-ink-3)">暂无文案片段</p></div>
<?php else: ?>
<?php foreach ($grouped as $grp => $rows): ?>
  <div class="card" style="margin-bottom:16px">
    <h3 style="margin-bottom:16px"><?= e((string)$grp) ?></h3>
<?php foreach ($rows as $r):
        $skey  = (string)$r['skey'];
        $isRaw = isset($rawSet[$skey]);
?>
    <div class="field">
      <label class="field-label">
        <?= e((string)$r['label']) ?>
        <span class="snip-key">（<?= e($skey) ?>）</span>
      </label>
<?php if ($isRaw): ?>
      <div class="snip-warn">⚠ 此项含版式标记，请保持既有 HTML 结构，谨慎编辑</div>
<?php endif; ?>
<?php if (($r['type'] ?? 'text') === 'textarea'): ?>
      <textarea name="s[<?= (int)$r['id'] ?>]" rows="3" class="textarea"><?= e((string)$r['value']) ?></textarea>
<?php else: ?>
      <input type="text" name="s[<?= (int)$r['id'] ?>]" value="<?= e((string)$r['value']) ?>" class="input">
<?php endif; ?>
    </div>
<?php endforeach; ?>
  </div>
<?php endforeach; ?>
  <div class="save-bar"><button class="abtn abtn-primary" type="submit">保存全部</button></div>
<?php endif; ?>
</form>
