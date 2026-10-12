<?php
// V5.0 后台「准入名单」模块：列表 + 筛选 + 单条增删 + 批量导入 + 补录邮箱 + 一键建号
// 动作均 POST + CSRF，已处于 admin.php 的 auth_is_admin() 硬闸之内。

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    while (ob_get_level() > 0) { ob_end_clean(); }
    csrf_verify_or_die();
    $act = (string)($_POST['act'] ?? '');

    // ── 单条添加 ──
    if ($act === 'add') {
        $email    = trim((string)($_POST['email'] ?? ''));
        $category = (string)($_POST['category'] ?? '');
        $name     = trim((string)($_POST['name'] ?? ''));
        $sid      = trim((string)($_POST['student_id'] ?? ''));
        $note     = trim((string)($_POST['note'] ?? ''));

        if (!in_array($category, ['project_team', 'zhang_lab', 'course_vaccine', 'legacy'], true)) {
            flash_set('error', '分类不合法');
        } elseif ($email !== '' && !member_validate_email($email)) {
            flash_set('error', '邮箱格式不正确');
        } elseif ($email !== '' && allowed_email_find($email)) {
            flash_set('warn', '该邮箱已在名单中：' . $email);
        } else {
            $newId = allowed_email_upsert(compact('email', 'category', 'name', 'note') + ['student_id' => $sid]);
            audit('allowed_email_add', 'allowed_emails', (string)$newId);
            flash_set('ok', '已添加 1 条（id=' . $newId . '）');
        }
        redirect('admin.php?m=allowed_emails');
    }

    // ── 批量导入 ──
    if ($act === 'import') {
        $category = (string)($_POST['category'] ?? '');
        $text     = (string)($_POST['bulk'] ?? '');
        if (!in_array($category, ['project_team', 'zhang_lab', 'course_vaccine'], true)) {
            flash_set('error', '请选择导入分类');
        } else {
            $lines = preg_split('/\r?\n/', trim($text));
            $added = $skipped = $failed = 0;
            $failedLines = [];
            foreach ($lines as $idx => $line) {
                $line = trim($line);
                if ($line === '') continue;
                $cols = array_map('trim', explode(',', $line));
                // 首行若为表头（email,...）跳过
                if ($idx === 0 && in_array('email', array_map('strtolower', $cols), true)) continue;
                $email = $cols[0] ?? '';
                $name  = $cols[1] ?? '';
                $sid   = $cols[2] ?? '';
                if ($email !== '' && !member_validate_email($email)) {
                    $failed++;
                    $failedLines[] = $idx + 1;
                    continue;
                }
                if ($email !== '' && allowed_email_find($email)) { $skipped++; continue; }
                allowed_email_upsert([
                    'email' => $email, 'category' => $category, 'name' => $name, 'student_id' => $sid,
                    'status' => $email === '' ? 'pending' : 'active',
                ]);
                $added++;
            }
            audit('allowed_email_import', 'allowed_emails', "cat=$category · added=$added · skipped=$skipped · failed=$failed");
            $msg = "✅ 新增 $added 条 · ⊘ 跳过 $skipped 条（已存在）";
            if ($failed > 0) $msg .= " · ❌ $failed 行格式错误（行号：" . implode(',', $failedLines) . "）";
            flash_set($failed > 0 ? 'warn' : 'ok', $msg);
        }
        redirect('admin.php?m=allowed_emails');
    }

    // ── 一键建号 ──
    if ($act === 'provision') {
        $id  = (int)($_POST['id'] ?? 0);
        $row = $id > 0 ? allowed_email_get($id) : null;
        if (!$row || $row['status'] !== 'active' || $row['email'] === '') {
            flash_set('error', '只能对 active 且有邮箱的条目一键建号');
        } else {
            $username = allowed_email_gen_username((string)$row['email'], (string)$row['student_id']);
            if (!member_validate_username($username)) {
                flash_set('error', "自动生成的用户名 \"$username\" 不合法，请改用手动「添加会员」");
            } else {
                // 生成 12 位 CSPRNG 临时密码（同 member_reset_password 字母表，去易混字符）
                $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
                $tmp = '';
                for ($i = 0; $i < 12; $i++) $tmp .= $alphabet[random_int(0, strlen($alphabet) - 1)];

                $newUserId = member_register($username, $tmp, (string)$row['email'], '', (string)$row['name']);
                // 强制首次登录改密
                db()->prepare('UPDATE users SET must_change_password=1, updated_at=? WHERE id=?')
                    ->execute([iso_now(), $newUserId]);
                allowed_email_mark_provisioned($id, $newUserId);
                audit('allowed_email_provision', 'allowed_emails', (string)$id);
                // 凭据仅本次展示一次（flash 存 session，下次渲染即清；不落库明文、不入日志）
                flash_set('warn', "已为 {$row['email']} 建号。username: {$username} · password: {$tmp}（请立即线下转交，刷新即消失）");
            }
        }
        redirect('admin.php?m=allowed_emails');
    }

    // ── 补录邮箱 ──
    if ($act === 'complete') {
        $id    = (int)($_POST['id'] ?? 0);
        $email = trim((string)($_POST['email'] ?? ''));
        $row   = $id > 0 ? allowed_email_get($id) : null;
        if (!$row || $row['status'] !== 'pending') {
            flash_set('error', '只能对 pending 条目补录邮箱');
        } elseif (!member_validate_email($email)) {
            flash_set('error', '邮箱格式不正确');
        } elseif (allowed_email_find($email)) {
            flash_set('warn', '该邮箱已在名单中：' . $email);
        } else {
            allowed_email_upsert([
                'id' => $id, 'email' => $email, 'category' => $row['category'],
                'name' => $row['name'], 'student_id' => $row['student_id'],
                'note' => $row['note'], 'status' => 'active',
            ]);
            audit('allowed_email_complete', 'allowed_emails', (string)$id);
            flash_set('ok', '已补录邮箱并置为 active：' . $email);
        }
        redirect('admin.php?m=allowed_emails');
    }

    // ── 停用 / 启用 / 删除 ──
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { flash_set('error', '目标 id 无效'); redirect('admin.php?m=allowed_emails'); }

    if ($act === 'disable') {
        allowed_email_set_status($id, 'disabled');
        audit('allowed_email_disable', 'allowed_emails', (string)$id);
        flash_set('ok', '已停用（id=' . $id . '）');
    } elseif ($act === 'enable') {
        allowed_email_set_status($id, 'active');
        audit('allowed_email_enable', 'allowed_emails', (string)$id);
        flash_set('ok', '已启用（id=' . $id . '）');
    } elseif ($act === 'delete') {
        allowed_email_delete($id);
        audit('allowed_email_delete', 'allowed_emails', (string)$id);
        flash_set('ok', '已删除（id=' . $id . '）');
    } else {
        flash_set('error', '未知操作：' . $act);
    }
    redirect('admin.php?m=allowed_emails');
}

// GET：读列表 + 筛选
$filter = [
    'category' => (string)($_GET['cat'] ?? ''),
    'status'   => (string)($_GET['st']  ?? ''),
    'search'   => trim((string)($_GET['q'] ?? '')),
];
$rows = allowed_email_list($filter);

$catLabels = [
    'project_team'   => '项目团队',
    'zhang_lab'      => '张老师实验室',
    'course_vaccine' => '疫苗课',
    'legacy'         => '历史账号',
];
$statusLabels = [
    'active'      => '可注册',
    'pending'     => '待补录',
    'provisioned' => '已建号',
    'disabled'    => '已停用',
];
?>
<style>
/* V5.0 准入名单：防止横向滚动 */
.card .data-table td code { word-break: break-all; font-size: 12px; }
.card .data-table td.col-actions .row-actions { display: flex; flex-wrap: wrap; gap: 4px; justify-content: flex-end; }
.card .data-table td.col-actions .row-actions form { margin: 0; }
.card .data-table td.col-actions input[type="email"] { width: 140px; }
</style>
<div class="card">
  <div class="page-head">
    <h3>准入名单</h3>
    <span class="page-head-meta">共 <?= count($rows) ?> 条（<?= e(implode(' / ', array_filter([
        $filter['category'] ? '分类=' . ($catLabels[$filter['category']] ?? $filter['category']) : '',
        $filter['status']   ? '状态=' . ($statusLabels[$filter['status']] ?? $filter['status']) : '',
        $filter['search']   ? '搜索=' . $filter['search'] : '',
    ])) ?: '全部') ?>）</span>
  </div>

  <form method="get" action="admin.php" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:12px 0 16px">
    <input type="hidden" name="m" value="allowed_emails">
    <select name="cat">
      <option value="">全部分类</option>
<?php foreach ($catLabels as $v => $t): ?>
      <option value="<?= e($v) ?>"<?= $filter['category'] === $v ? ' selected' : '' ?>><?= e($t) ?></option>
<?php endforeach; ?>
    </select>
    <select name="st">
      <option value="">全部状态</option>
<?php foreach ($statusLabels as $v => $t): ?>
      <option value="<?= e($v) ?>"<?= $filter['status'] === $v ? ' selected' : '' ?>><?= e($t) ?></option>
<?php endforeach; ?>
    </select>
    <input type="search" name="q" placeholder="邮箱 / 姓名 / 学号" value="<?= e($filter['search']) ?>" style="min-width:220px">
    <button class="abtn abtn-sm" type="submit">筛选</button>
    <a class="abtn abtn-sm abtn-default" href="admin.php?m=allowed_emails">清空</a>
  </form>

  <div style="display:flex;gap:8px;margin-bottom:12px">
    <button class="abtn abtn-sm" type="button" onclick="document.getElementById('add-form').hidden=!document.getElementById('add-form').hidden">+ 添加</button>
    <button class="abtn abtn-sm abtn-default" type="button" onclick="document.getElementById('import-form').hidden=!document.getElementById('import-form').hidden">⇅ 批量导入</button>
  </div>

  <!-- 添加 -->
  <div id="add-form" hidden style="padding:12px;background:#fafbff;border:1px solid var(--line);border-radius:8px;margin-bottom:16px">
    <form method="post" action="admin.php?m=allowed_emails" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
      <?= csrf_field() ?>
      <input type="hidden" name="act" value="add">
      <input name="email"      type="email" placeholder="邮箱（空=pending 待补录）" style="min-width:220px">
      <select name="category" required>
        <option value="">分类...</option>
        <option value="project_team">项目团队</option>
        <option value="zhang_lab">张老师实验室</option>
        <option value="course_vaccine">疫苗课</option>
      </select>
      <input name="name"       placeholder="姓名">
      <input name="student_id" placeholder="学号（可选）">
      <input name="note"       placeholder="备注" style="min-width:160px">
      <button class="abtn abtn-sm" type="submit">保存</button>
      <button class="abtn abtn-sm abtn-default" type="button" onclick="document.getElementById('add-form').hidden=true">取消</button>
    </form>
  </div>

  <!-- 批量导入 -->
  <div id="import-form" hidden style="padding:12px;background:#fafbff;border:1px solid var(--line);border-radius:8px;margin-bottom:16px">
    <form method="post" action="admin.php?m=allowed_emails">
      <?= csrf_field() ?>
      <input type="hidden" name="act" value="import">
      <div style="display:flex;gap:8px;align-items:center;margin-bottom:8px">
        <label>目标分类
          <select name="category" required>
            <option value="">...</option>
            <option value="project_team">项目团队</option>
            <option value="zhang_lab">张老师实验室</option>
            <option value="course_vaccine">疫苗课</option>
          </select>
        </label>
        <small style="color:var(--ink-3)">每行一个邮箱，或 CSV（email,name,student_id）；首行表头可选</small>
      </div>
      <textarea name="bulk" rows="8" style="width:100%;font-family:ui-monospace,Menlo,monospace;font-size:13px" placeholder="alice@mabseek.com&#10;bob@mabseek.com&#10;&#10;# 或 CSV:&#10;# email,name,student_id&#10;# gongc24@mails.tsinghua.edu.cn,龚灿,2024010994"></textarea>
      <div style="margin-top:8px;display:flex;gap:8px">
        <button class="abtn abtn-sm" type="submit">导入</button>
        <button class="abtn abtn-sm abtn-default" type="button" onclick="document.getElementById('import-form').hidden=true">取消</button>
      </div>
    </form>
  </div>

  <div class="table-wrap">
  <table class="data-table">
    <thead><tr>
      <th>ID</th><th>邮箱</th><th>分类</th><th>姓名</th><th>学号</th><th>状态</th><th class="col-actions">操作</th>
    </tr></thead>
    <tbody>
<?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td>
<?php if ($r['email'] !== ''): ?><code><?= e($r['email']) ?></code>
<?php else: ?><span style="color:var(--ink-3)">⚠ 待补录</span><?php endif; ?>
        </td>
        <td><?= e($catLabels[$r['category']] ?? $r['category']) ?></td>
        <td><?= e($r['name']) ?></td>
        <td><?= e($r['student_id']) ?></td>
        <td>
<?php
$st = (string)$r['status'];
$stTag = ['active' => 'tag', 'pending' => 'tag tag-warn', 'provisioned' => 'tag tag-success', 'disabled' => 'tag tag-danger'][$st] ?? 'tag';
?>
          <span class="<?= $stTag ?>"><?= e($statusLabels[$st] ?? $st) ?></span>
<?php if ($st === 'provisioned' && $r['provisioned_user_id']): ?>
          <small style="color:var(--ink-3)">→ user #<?= (int)$r['provisioned_user_id'] ?></small>
<?php endif; ?>
        </td>
        <td class="col-actions">
          <span class="row-actions">
<?php if ($st === 'pending'): ?>
          <form method="post" action="admin.php?m=allowed_emails" style="display:inline;margin:0">
            <?= csrf_field() ?><input type="hidden" name="act" value="complete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <input type="email" name="email" placeholder="补录邮箱" required style="width:200px">
            <button class="abtn abtn-sm" type="submit">补录</button>
          </form>
<?php endif; ?>
<?php if ($st === 'active'): ?>
          <form method="post" action="admin.php?m=allowed_emails" style="display:inline;margin:0" onsubmit="return confirm('确认为 <?= e($r['email']) ?> 一键建号？\n生成的密码只在页面展示一次。')">
            <?= csrf_field() ?><input type="hidden" name="act" value="provision"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="abtn abtn-sm" type="submit">建号</button>
          </form>
          <form method="post" action="admin.php?m=allowed_emails" style="display:inline;margin:0">
            <?= csrf_field() ?><input type="hidden" name="act" value="disable"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="abtn abtn-sm abtn-default" type="submit">停用</button>
          </form>
<?php elseif ($st === 'disabled'): ?>
          <form method="post" action="admin.php?m=allowed_emails" style="display:inline;margin:0">
            <?= csrf_field() ?><input type="hidden" name="act" value="enable"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="abtn abtn-sm abtn-default" type="submit">启用</button>
          </form>
<?php endif; ?>
          <form method="post" action="admin.php?m=allowed_emails" style="display:inline;margin:0" onsubmit="return confirm('确认删除该条目？不可恢复')">
            <?= csrf_field() ?><input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="abtn abtn-sm abtn-danger" type="submit">删</button>
          </form>
          </span>
        </td>
      </tr>
<?php endforeach; ?>
<?php if (!$rows): ?>
      <tr><td colspan="7" class="empty">暂无条目，点击「+ 添加」或「⇅ 批量导入」开始</td></tr>
<?php endif; ?>
    </tbody>
  </table>
  </div>
</div>
