<?php
declare(strict_types=1);
// 端到端：起 mock → 用临时库调用 sciencepal_* + scp_sync_* 断言全链路。
// 用法：php tests/e2e-sciencepal.php （脚本自起/自停 mock）
putenv('MABSEEK_DB=' . sys_get_temp_dir() . '/scp-e2e.sqlite');
putenv('SCIENCEPAL_API_BASE=http://localhost:8899');
putenv('SCIENCEPAL_PARTNER_KEY=c9d2e6a4-3f7b-4c1a-8e5d-6a2b9f0c4d71');
@unlink(sys_get_temp_dir() . '/scp-e2e.sqlite');
@unlink(sys_get_temp_dir() . '/scp-mock-created.json');

$root = dirname(__DIR__);
$mock = proc_open('php -S localhost:8899 ' . escapeshellarg($root . '/tests/mock/sciencepal-mock.php'),
    [1 => ['file', '/tmp/e2e-mock.log', 'a'], 2 => ['file', '/tmp/e2e-mock.log', 'a']], $pipes);
usleep(700000);

require $root . '/app/bootstrap.php';
$fail = 0;
function check(string $name, bool $ok): void { global $fail; echo ($ok ? "PASS " : "FAIL ") . $name . "\n"; if (!$ok) $fail++; }

// 1) provision created
$r = sciencepal_provision('newuser@x.com', 'abc12345');
check('provision created', $r['status'] === 'created');
scp_sync_upsert(1, 'newuser@x.com', $r['status'], '');
check('sync row created', (scp_sync_get(1)['status'] ?? '') === 'created');

// 2) provision exists
$r = sciencepal_provision('exists@x.com', 'abc12345');
check('provision exists', $r['status'] === 'exists');

// 3) sync_password updated（对已开通 newuser）
$r = sciencepal_sync_password('newuser@x.com', 'newpass12');
check('sync_password updated', $r['status'] === 'updated');

// 4) sync_password 404（未开通）
$r = sciencepal_sync_password('nobody@x.com', 'newpass12');
check('sync_password notfound', $r['status'] === 'notfound');

// 5) 错误 key → error（模拟鉴权失败）
putenv('SCIENCEPAL_PARTNER_KEY=wrongkey');
$r = sciencepal_provision('another@x.com', 'abc12345');
check('wrong key -> error', $r['status'] === 'error');
putenv('SCIENCEPAL_PARTNER_KEY=c9d2e6a4-3f7b-4c1a-8e5d-6a2b9f0c4d71');

// 6) mock 停机 → error（本站优先：调用方应容错）
proc_terminate($mock); usleep(300000);
$r = sciencepal_provision('offline@x.com', 'abc12345');
check('server down -> error (not fatal)', $r['status'] === 'error');

// 清理
foreach ($pipes ?? [] as $p) { if (is_resource($p)) fclose($p); }
proc_close($mock);
@unlink(sys_get_temp_dir() . '/scp-e2e.sqlite');
@unlink(sys_get_temp_dir() . '/scp-e2e.sqlite-wal');
@unlink(sys_get_temp_dir() . '/scp-e2e.sqlite-shm');
@unlink(sys_get_temp_dir() . '/scp-mock-created.json');

echo $fail === 0 ? "\nALL PASS\n" : "\n$fail FAILED\n";
exit($fail === 0 ? 0 : 1);
