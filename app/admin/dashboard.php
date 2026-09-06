<?php
// Admin dashboard: read-only overview (counts + current session + recent audit).
// Included mid-output by the admin shell, inside an already-open container.
// Emits only an inner content fragment (no page wrappers, no strict_types declare).

$collectionLabels = [
    'news'          => '新闻与活动',
    'team_members'  => '团队成员',
    'partners'      => '合作伙伴',
    'content_cards' => '内容卡片',
];

$counts = [];
foreach ($collectionLabels as $table => $label) {
    $counts[$table] = (new Collection($table))->count();
}
$snippetCount = (int) db()->query('SELECT COUNT(*) FROM snippets')->fetchColumn();

$auditRows = db()->query(
    "SELECT user, action, entity, entity_id, ip, created_at FROM audit_log ORDER BY id DESC LIMIT 10"
)->fetchAll();

$uid  = $_SESSION['uid']  ?? '';
$born = (int) ($_SESSION['born'] ?? 0);
$last = (int) ($_SESSION['last'] ?? 0);
$ua   = $_SESSION['ua']   ?? '';
?>
<div class="stack">

  <div class="card">
    <h3 style="margin-bottom:16px">内容概览</h3>
    <div class="stat-grid">
      <?php foreach ($collectionLabels as $table => $label): ?>
        <div class="stat">
          <div class="stat-num"><?= (int) $counts[$table] ?></div>
          <div class="stat-label"><?= e($label) ?></div>
        </div>
      <?php endforeach; ?>
      <div class="stat">
        <div class="stat-num"><?= $snippetCount ?></div>
        <div class="stat-label">文案片段</div>
      </div>
    </div>
  </div>

  <div class="card">
    <h3 style="margin-bottom:16px">当前会话</h3>
    <ul class="kv">
      <li><strong>当前用户</strong><?= e($uid) ?></li>
      <li><strong>登录时间</strong><?= $born ? date('Y-m-d H:i:s', $born) : '—' ?></li>
      <li><strong>最近活动</strong><?= $last ? date('Y-m-d H:i:s', $last) : '—' ?></li>
      <li><strong>浏览器 UA</strong><?= e($ua) ?></li>
    </ul>
  </div>

  <div class="card">
    <h3 style="margin-bottom:16px">最近操作日志（10 条）</h3>
    <?php if (empty($auditRows)): ?>
      <div class="table-wrap"><table class="data-table"><tbody><tr><td class="empty">暂无记录</td></tr></tbody></table></div>
    <?php else: ?>
      <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>时间</th>
            <th>用户</th>
            <th>动作</th>
            <th>对象</th>
            <th>对象 ID</th>
            <th>IP</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($auditRows as $row): ?>
            <tr>
              <td><?= e($row['created_at']) ?></td>
              <td class="col-primary"><?= e($row['user']) ?></td>
              <td><?= e($row['action']) ?></td>
              <td><?= e($row['entity']) ?></td>
              <td><?= e($row['entity_id']) ?></td>
              <td><?= e($row['ip']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    <?php endif; ?>
  </div>

</div>
