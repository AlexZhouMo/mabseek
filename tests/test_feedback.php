<?php
putenv('MABSEEK_DB=:memory:');
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/feedback.php';
db();

// ── 字段校验 ──
check(feedback_validate_name('张三') === true, 'name 合法');
check(feedback_validate_name('') === false, 'name 空被拒');
check(feedback_validate_name('  ') === false, 'name 纯空白被拒');
check(feedback_validate_name(str_repeat('字', 51)) === false, 'name 过长被拒');

check(feedback_validate_email('a@b.com') === true, 'email 合法');
check(feedback_validate_email('') === false, 'email 空被拒');
check(feedback_validate_email('not-an-email') === false, 'email 非法被拒');

check(feedback_validate_message('一句反馈') === true, 'message 合法');
check(feedback_validate_message('') === false, 'message 空被拒');
check(feedback_validate_message(str_repeat('字', 2001)) === false, 'message 过长被拒');

// ── 落库 ──
$id = feedback_create('  李四  ', 'lisi@x.com', '  这是反馈内容  ', '1.2.3.4');
check($id > 0, '创建返回新 id');
$row = feedback_get($id);
check($row['name'] === '李四' && $row['message'] === '这是反馈内容', 'name/message 已 trim');
check($row['email'] === 'lisi@x.com' && $row['ip'] === '1.2.3.4', 'email/ip 落库');
check($row['status'] === 'new', '硬编码 status=new');

// ── 状态标记 ──
feedback_set_status($id, 'done');
check(feedback_get($id)['status'] === 'done', '标记已处理生效');
feedback_set_status($id, 'new');
check(feedback_get($id)['status'] === 'new', '标记未处理生效');
feedback_set_status($id, 'hack');
check(feedback_get($id)['status'] === 'new', '非法状态归为 new');

// ── 频率限制 ──（同 IP 刚提交过 → 不可再提交）
check(feedback_recent_count_by_ip('1.2.3.4', 60) >= 1, '近 60 秒该 IP 计数>=1');
check(feedback_can_submit_now('1.2.3.4') === false, '刚提交过 → 60 秒内不可再提交');
check(feedback_can_submit_now('9.9.9.9') === true, '新 IP 可提交');

// 日上限：造 FEEDBACK_RATE_DAILY_MAX 条今日记录（错开秒数避开 60s 限制判断，仅验日上限）
$now = time();
for ($i = 0; $i < FEEDBACK_RATE_DAILY_MAX; $i++) {
    db()->prepare("INSERT INTO feedback(name,email,message,status,ip,created_at) VALUES('x','x@x.com','m','new','8.8.8.8',?)")
        ->execute([date('c', $now - 3600 - $i)]);   // 1 小时前起，避开 60s 窗口
}
check(feedback_recent_count_by_ip('8.8.8.8', 86400) >= FEEDBACK_RATE_DAILY_MAX, '该 IP 24h 计数达上限');
check(feedback_can_submit_now('8.8.8.8') === false, '达日上限 → 不可再提交');

// ── 删除 + 列表 ──
$before = count(feedback_list());
feedback_delete($id);
check(feedback_get($id) === null, '删除后消失');
check(count(feedback_list()) === $before - 1, '列表减少一条');

// 注入韧性
$injId = feedback_create("x'; DROP TABLE feedback;--", 'a@b.com', 'body', '0.0.0.0');
check(feedback_get($injId) !== null, '注入串原样入库、参数化生效');
