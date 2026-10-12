<?php
putenv('MABSEEK_DB=:memory:');
require_once __DIR__ . '/../app/bootstrap.php';
db();   // 触发建表

// ── 空库行为 ──
check(allowed_email_find('x@y.com') === null, '空库 find 返回 null');
$res = allowed_email_can_register('x@y.com');
check($res['ok'] === false && $res['reason'] === 'not_in_whitelist', '空库 can_register=false (not_in_whitelist)');

// ── upsert active ──
$id1 = allowed_email_upsert(['email' => 'alice@mabseek.com', 'category' => 'project_team', 'name' => 'Alice']);
check($id1 > 0, 'upsert active 返回 id');
$row = allowed_email_find('alice@mabseek.com');
check($row !== null && $row['status'] === 'active', 'active 默认 status=active');
check($row['category'] === 'project_team', 'category 正确落库');
check(allowed_email_find('ALICE@MABSEEK.COM')['id'] == $id1, 'find NOCASE 命中');

$res = allowed_email_can_register('alice@mabseek.com');
check($res['ok'] === true, 'active 邮箱 can_register=true');

// ── upsert 幂等（同邮箱）──
$id1b = allowed_email_upsert(['email' => 'alice@mabseek.com', 'category' => 'project_team']);
check($id1b === $id1, '同邮箱 upsert 幂等返回同 id');

// ── pending 条目（空邮箱）──
$id2 = allowed_email_upsert(['email' => '', 'category' => 'course_vaccine', 'name' => '简泓钰', 'student_id' => '2026011867']);
check($id2 > 0, 'pending 条目插入成功');
$row2 = allowed_email_get($id2);
check($row2['status'] === 'pending', '空邮箱默认 status=pending');

// 再 upsert 同 pending → 幂等
$id2b = allowed_email_upsert(['email' => '', 'category' => 'course_vaccine', 'name' => '简泓钰', 'student_id' => '2026011867']);
check($id2b === $id2, 'pending 同 name+student_id upsert 幂等');

// ── disabled 态拒绝 ──
$id3 = allowed_email_upsert(['email' => 'bob@x.com', 'category' => 'project_team']);
allowed_email_set_status($id3, 'disabled');
$res = allowed_email_can_register('bob@x.com');
check($res['ok'] === false && $res['reason'] === 'status_disabled', 'disabled 拒绝');

// ── provisioned 态拒绝 ──
$id4 = allowed_email_upsert(['email' => 'carol@x.com', 'category' => 'legacy']);
allowed_email_mark_provisioned($id4, 999);
$res = allowed_email_can_register('carol@x.com');
check($res['ok'] === false && $res['reason'] === 'status_provisioned', 'provisioned 拒绝');
$row4 = allowed_email_get($id4);
check((int)$row4['provisioned_user_id'] === 999, 'provisioned_user_id 落库');

// ── list 筛选 ──
$all = allowed_email_list();
check(count($all) >= 4, 'list 全部不为空');
$catList = allowed_email_list(['category' => 'project_team']);
check(count($catList) >= 2, 'list 按分类筛选');
$search = allowed_email_list(['search' => '简泓钰']);
check(count($search) === 1, 'list 按姓名搜索');

// ── delete ──
allowed_email_delete($id4);
check(allowed_email_get($id4) === null, 'delete 生效');

// ── gen_username 规则 ──
check(allowed_email_gen_username('gongc24@mails.tsinghua.edu.cn') === 'gongc24', 'gen_username: 常规邮箱');
check(allowed_email_gen_username('jq-li25@mails.tsinghua.edu.cn') === 'jqli25', 'gen_username: 去掉 - 字符');
check(allowed_email_gen_username('alex.zhou+promo@mabseek.com') === 'alexzhoupromo', 'gen_username: 去掉 .+');
check(allowed_email_gen_username('ab@x.com', '2026011321') === 'u_2026011321', 'gen_username: 短前缀 fallback 到学号');

// 短前缀无学号 → 随机后缀
$u = allowed_email_gen_username('a@x.com');
check(preg_match('/^a_\d{3}$/', $u) === 1, 'gen_username: 短前缀无学号 → 随机后缀 a_NNN');

// 冲突追加 _2
member_register('gongc24', 'abc12345', 'gongc24-real@x.com', '', '占位');
check(allowed_email_gen_username('gongc24@foo.com') === 'gongc24_2', 'gen_username: 冲突追加 _2');

// 清库 for 下一组测试
db()->exec('DELETE FROM allowed_emails');
db()->exec("DELETE FROM users WHERE username='gongc24'");
