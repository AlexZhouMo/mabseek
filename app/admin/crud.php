<?php
declare(strict_types=1);
// 通用后台 CRUD 组件：列表 / 表单 / 保存 / 删除（含 CSRF + PRG + 审计）。
// 由 public/admin.php 在输出前 require_once；模块（如 news.php）声明 $cfg 后调用 admin_crud($cfg)。
// 写操作（save/delete）在处理起始先清空外壳缓冲并校验 CSRF，随后重定向（PRG）。
require_once __DIR__ . '/upload.php';
require_once __DIR__ . '/../html_sanitizer.php';

function admin_crud(array $cfg): void
{
    // 与 admin.php 同法从请求派生模块键（已净化为 [a-z_]），用于构造 URL
    $m = preg_replace('/[^a-z_]/', '', (string)($_GET['m'] ?? ''));
    $a = (string)($_GET['a'] ?? 'list');

    if ($a === 'upload') {
        while (ob_get_level() > 0) { ob_end_clean(); }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405); header('Allow: POST'); exit('Method Not Allowed');
        }
        csrf_verify_or_die();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $url = handle_upload('file');
            echo json_encode($url !== null ? ['ok'=>true,'url'=>$url] : ['ok'=>false,'error'=>'未选择文件'], JSON_UNESCAPED_UNICODE);
        } catch (\RuntimeException $ex) {
            http_response_code(400);
            echo json_encode(['ok'=>false,'error'=>$ex->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    if ($a === 'save' || $a === 'delete') {
        while (ob_get_level() > 0) { ob_end_clean(); }  // 丢弃外壳已缓冲输出，稍后干净重定向
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {   // 写操作仅接受 POST：防 GET 绕过 CSRF/被 <img> 等触发
            http_response_code(405);
            header('Allow: POST');
            exit('Method Not Allowed');
        }
        csrf_verify_or_die();
        if ($a === 'delete') {
            admin_crud_delete($cfg, $m);
        } else {
            admin_crud_save($cfg, $m);
        }
        return; // 上述分支必以 redirect() 结束
    }

    if ($a === 'new' || $a === 'edit') {
        admin_crud_form($cfg, $m);
        return;
    }

    admin_crud_list($cfg, $m);
}

function admin_crud_save(array $cfg, string $m): void
{
    $id = (int)($_POST['id'] ?? 0);
    $managed = array_filter(explode(',', (string)($_POST['_managed'] ?? '')));
    $fieldsByName = [];
    foreach ($cfg['fields'] as $f) $fieldsByName[$f['name']] = $f;

    $data = [];
    $errors = [];
    foreach ($managed as $name) {
        if (!isset($fieldsByName[$name])) continue;          // 只接受配置中声明的字段（防注入未知列）
        $f = $fieldsByName[$name];
        $type = $f['type'];
        if ($type === 'checkbox') {
            $data[$name] = isset($_POST[$name]) ? 1 : 0;
        } elseif ($type === 'image') {
            try {
                $uploaded = handle_upload($name);                     // ?string：新图 URL；未上传返回 null
            } catch (\RuntimeException $ex) {
                old_set($_POST);
                flash_set('error', $f['label'] . '：' . $ex->getMessage());
                redirect($id > 0 ? "admin.php?m=$m&a=edit&id=$id" : "admin.php?m=$m&a=new");
            }
            if ($uploaded !== null) {
                $data[$name] = $uploaded;                             // 新上传 → 覆盖
            } elseif (array_key_exists($name, $_POST)) {
                $data[$name] = trim((string)$_POST[$name]);           // 隐藏字段回传的现有路径 → 保留
            }
        } elseif ($type === 'richtext') {
            $data[$name] = sanitize_html((string)($_POST[$name] ?? ''));
            if (!empty($f['required']) && strip_tags($data[$name]) === '') $errors[] = $f['label'];
        } elseif ($type === 'select') {
            $val = (string)($_POST[$name] ?? '');
            $opts = $f['options'] ?? [];
            if (!array_key_exists($val, $opts)) {                     // 白名单校验，拒绝非法选项
                $errors[] = $f['label'];
            } else {
                $data[$name] = $val;
            }
        } else { // text / textarea
            $val = trim((string)($_POST[$name] ?? ''));
            if (!empty($f['required']) && $val === '') $errors[] = $f['label'];
            if ($name === 'sort') $val = (string)(int)$val;           // 排序转整数，避免非数字破坏排序
            $data[$name] = $val;
        }
    }
    if ($errors) {
        old_set($_POST);
        flash_set('error', '请检查以下字段：' . implode('、', $errors));
        redirect($id > 0 ? "admin.php?m=$m&a=edit&id=$id" : "admin.php?m=$m&a=new");
    }
    if ($id > 0) {
        (new Collection($cfg['table']))->update($id, $data);
        audit('update', $cfg['table'], (string)$id);
        flash_set('ok', '已更新');
    } else {
        $newId = (new Collection($cfg['table']))->create($data);
        audit('create', $cfg['table'], (string)$newId);
        flash_set('ok', '已新增');
    }
    redirect("admin.php?m=$m");
}

function admin_crud_delete(array $cfg, string $m): void
{
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        (new Collection($cfg['table']))->delete($id);
        audit('delete', $cfg['table'], (string)$id);
        flash_set('ok', '已删除');
    }
    redirect("admin.php?m=$m");
}

function admin_crud_trim(string $s): string {
    if (function_exists('mb_strimwidth')) return mb_strimwidth($s, 0, 40, '…', 'UTF-8');
    return strlen($s) > 40 ? substr($s, 0, 40) . '…' : $s;   // 无 mbstring 时降级
}

function admin_crud_list(array $cfg, string $m): void
{
    $rows = (new Collection($cfg['table']))->all($cfg['where'] ?? null);
    // 列表数据列：排除 checkbox / image，以及由「发布 · 排序」控件统一管理的 published/sort，避免重复成列
    $listFields = array_filter(
        $cfg['fields'],
        fn($f) => $f['type'] !== 'checkbox' && $f['type'] !== 'image' && $f['type'] !== 'richtext'
               && $f['name'] !== 'published' && $f['name'] !== 'sort'
    );
    $listFields = array_values($listFields);
?>
<div class="card">
  <div class="page-head">
    <h3><?= e((string)($cfg['title'] ?? '')) ?></h3>
    <a href="admin.php?m=<?= e($m) ?>&a=new" class="abtn abtn-primary">新增</a>
  </div>
<?php if (empty($rows)): ?>
  <div class="table-wrap">
    <table class="data-table">
      <tbody><tr><td class="empty">暂无数据，点击右上角「新增」创建第一条。</td></tr></tbody>
    </table>
  </div>
<?php else: ?>
  <div class="table-wrap">
  <table class="data-table">
    <thead>
      <tr>
<?php foreach ($listFields as $f): ?>
        <th><?= e((string)$f['label']) ?></th>
<?php endforeach; ?>
        <th>发布 · 排序</th>
        <th class="col-actions">操作</th>
      </tr>
    </thead>
    <tbody>
<?php foreach ($rows as $row): ?>
      <tr>
<?php foreach ($listFields as $i => $f):
            $raw = (string)($row[$f['name']] ?? '');
            if ($f['type'] === 'select') {
                $label = $f['options'][$raw] ?? $raw;
?>
        <td><?php if ($label !== '') : ?><span class="tag"><?= e((string)$label) ?></span><?php endif; ?></td>
<?php       } else {
                $cls = 'col-text' . ($i === 0 ? ' col-primary' : '');
?>
        <td><span class="<?= $cls ?>" title="<?= e($raw) ?>"><?= e(admin_crud_trim($raw)) ?></span></td>
<?php       } ?>
<?php endforeach; ?>
        <td>
          <form method="post" action="admin.php?m=<?= e($m) ?>&a=save" class="inline-edit">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
            <input type="hidden" name="_managed" value="published,sort">
            <label class="switch"><input type="checkbox" name="published" value="1" <?= !empty($row['published']) ? 'checked' : '' ?>> 发布</label>
            <input type="number" name="sort" value="<?= (int)($row['sort'] ?? 0) ?>" class="input-sm" aria-label="排序">
            <button class="abtn abtn-sm abtn-default" type="submit">保存</button>
          </form>
        </td>
        <td class="col-actions">
          <span class="row-actions">
            <a href="admin.php?m=<?= e($m) ?>&a=edit&id=<?= (int)$row['id'] ?>" class="abtn abtn-sm abtn-default">编辑</a>
            <form method="post" action="admin.php?m=<?= e($m) ?>&a=delete" style="display:inline;margin:0" onsubmit="return confirm('确认删除？此操作不可恢复。')">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
              <button class="abtn abtn-sm abtn-danger" type="submit">删除</button>
            </form>
          </span>
        </td>
      </tr>
<?php endforeach; ?>
    </tbody>
  </table>
  </div>
<?php endif; ?>
</div>
<?php
}

function admin_crud_form(array $cfg, string $m): void
{
    $id = (int)($_GET['id'] ?? 0);
    $row = $id > 0 ? (new Collection($cfg['table']))->find($id) : null;
    if ($id > 0 && $row === null) {
        while (ob_get_level() > 0) { ob_end_clean(); }  // 记录不存在：清缓冲后干净重定向
        flash_set('error', '记录不存在');
        redirect("admin.php?m=$m");
    }
    $managedAll = implode(',', array_map(fn($f) => $f['name'], $cfg['fields']));
?>
<div class="card">
  <h3 style="margin-bottom:18px"><?= e((string)($cfg['title'] ?? '')) ?> · <?= $id > 0 ? '编辑' : '新增' ?></h3>
  <form method="post" action="admin.php?m=<?= e($m) ?>&a=save" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <input type="hidden" name="_managed" value="<?= e($managedAll) ?>">
<?php $needsRtScript = false; ?>
<?php foreach ($cfg['fields'] as $f):
        $name = $f['name'];
        $type = $f['type'];
        $cur  = old($name, (string)($row[$name] ?? ''));
        $req  = !empty($f['required']) ? 'required' : '';
?>
    <div class="field">
<?php if ($type === 'checkbox'): ?>
<?php
        $hasOld  = isset($_SESSION['__old']);
        $checked = $hasOld ? array_key_exists($name, $_SESSION['__old'])
                           : ($row ? (int)($row[$name] ?? 0) : 1);
?>
      <label class="field-check">
        <input type="checkbox" name="<?= e($name) ?>" value="1" <?= $checked ? 'checked' : '' ?>> <?= e((string)$f['label']) ?>
      </label>
<?php else: ?>
      <label class="field-label"><?= e((string)$f['label']) ?></label>
<?php if ($type === 'richtext'):
        $rt = sanitize_html($cur);   // 即使 old() 回填未净化内容，渲染前再净化，杜绝后台自 XSS
?>
      <div class="rt-field" data-rt>
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
        <div class="rt-editor" contenteditable="true"><?= $rt ?></div>
        <textarea name="<?= e($name) ?>" class="rt-source" hidden><?= e($rt) ?></textarea>
      </div>
      <?php $needsRtScript = true; ?>
<?php elseif ($type === 'textarea'): ?>
      <textarea name="<?= e($name) ?>" rows="4" class="textarea" <?= $req ?>><?= e($cur) ?></textarea>
<?php elseif ($type === 'select'): ?>
      <select name="<?= e($name) ?>" class="select" <?= $req ?>>
<?php foreach (($f['options'] ?? []) as $ov => $ol): ?>
        <option value="<?= e((string)$ov) ?>" <?= (string)$ov === $cur ? 'selected' : '' ?>><?= e((string)$ol) ?></option>
<?php endforeach; ?>
      </select>
<?php elseif ($type === 'image'):
        $curImg = (string)($row[$name] ?? '');
?>
<?php if ($curImg !== ''): ?>
      <img src="<?= e($curImg) ?>" alt="当前图片" class="thumb">
<?php endif; ?>
      <input type="file" name="<?= e($name) ?>" accept="image/*">
      <input type="hidden" name="<?= e($name) ?>" value="<?= e($curImg) ?>">
      <div class="field-hint">支持 JPG / PNG / WebP，≤ 2MB；未选择文件时保留原图</div>
<?php else: // text ?>
      <input type="text" name="<?= e($name) ?>" value="<?= e($cur) ?>" class="input" <?= $req ?>>
<?php endif; ?>
<?php endif; ?>
    </div>
<?php endforeach; ?>
<?php if ($needsRtScript): ?>
    <script src="assets/js/richtext.js" defer></script>
<?php endif; ?>
    <div class="form-actions">
      <button class="abtn abtn-primary" type="submit">保存</button>
      <a href="admin.php?m=<?= e($m) ?>" class="cancel">取消</a>
    </div>
  </form>
</div>
<?php
    old_clear();  // 清除回填数据，避免泄漏到下一次 GET
}
