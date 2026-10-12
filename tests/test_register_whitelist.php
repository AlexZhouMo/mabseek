<?php
// register.php 白名单准入链路端到端测试（仓储级 + register 核心逻辑验证）
putenv('MABSEEK_DB=:memory:');
require_once __DIR__ . '/../app/bootstrap.php';
db();

// 构造白名单四态
allowed_email_upsert(['email' => 'ok@x.com',       'category' => 'course_vaccine', 'name' => '甲']);
allowed_email_upsert(['email' => 'disabled@x.com', 'category' => 'project_team']);
allowed_email_set_status(allowed_email_find('disabled@x.com')['id'], 'disabled');
$provId = allowed_email_upsert(['email' => 'done@x.com', 'category' => 'legacy']);
allowed_email_mark_provisioned($provId, 42);

// ── can_register 四态 ──
check(allowed_email_can_register('ok@x.com')['ok']       === true,  '白名单 active → 放行');
check(allowed_email_can_register('miss@x.com')['ok']     === false, '白名单外 → 拒绝');
check(allowed_email_can_register('miss@x.com')['reason'] === 'not_in_whitelist', '拒绝 reason=not_in_whitelist');
check(allowed_email_can_register('disabled@x.com')['ok'] === false, 'disabled → 拒绝');
check(allowed_email_can_register('disabled@x.com')['reason'] === 'status_disabled', 'reason=status_disabled');
check(allowed_email_can_register('done@x.com')['ok']     === false, 'provisioned → 拒绝');
check(allowed_email_can_register('done@x.com')['reason'] === 'status_provisioned', 'reason=status_provisioned');

// 大小写不敏感
check(allowed_email_can_register('OK@X.COM')['ok']       === true,  'NOCASE 命中');

// ── 注册成功后 mark provisioned 的核心流程（模拟 register.php 的 mark 分支）──
$newId = member_register('alice5', 'abc12345', 'ok@x.com', '', 'Alice');
$allowedRow = allowed_email_find('ok@x.com');
check($allowedRow !== null && $allowedRow['status'] === 'active', '注册前该邮箱还是 active');
allowed_email_mark_provisioned((int)$allowedRow['id'], $newId);

$after = allowed_email_find('ok@x.com');
check($after['status'] === 'provisioned', '注册成功后状态变 provisioned');
check((int)$after['provisioned_user_id'] === $newId, 'provisioned_user_id 关联正确');

// 再用同邮箱注册 → can_register 应拒绝
check(allowed_email_can_register('ok@x.com')['ok'] === false, '已 provisioned 后再注册被拒');

// 清库 for 下一组测试
db()->exec('DELETE FROM allowed_emails');
db()->exec("DELETE FROM users WHERE username='alice5'");
