<?php
// 独立测试：模拟 bin/migrate-v50-allowed-emails.php 的核心逻辑，验证两次运行幂等 + 文件缺失降级
putenv('MABSEEK_DB=:memory:');
require_once __DIR__ . '/../app/bootstrap.php';
db();

// 构造几个 legacy 用户
member_register('legacy1', 'abc12345', 'legacy1@x.com', '', 'L1');
member_register('legacy2', 'abc12345', 'legacy2@x.com', '', 'L2');

// 用临时 .local.php shim 进去
$seedDir       = sys_get_temp_dir() . '/mabseek-v50-test-' . uniqid();
mkdir($seedDir, 0700, true);
$studentsFile = $seedDir . '/students-emails.local.php';
file_put_contents($studentsFile, "<?php\nreturn [\n  ['stu1@edu.cn', '甲', '2026001'],\n  ['stu2@edu.cn', '乙', '2026002'],\n  ['', '丙', '2026003'],\n];\n");

/** 迁移脚本核心逻辑的内嵌复刻版（避免 require 整个脚本导致 bootstrap 双加载） */
function do_migrate_v50_test(string $studentsFile): int {
    $changed = 0;

    // 建表（db.php 已建；此处 IF NOT EXISTS 幂等）
    db()->exec("CREATE TABLE IF NOT EXISTS allowed_emails (
        id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT NOT NULL DEFAULT '',
        category TEXT NOT NULL, name TEXT NOT NULL DEFAULT '', student_id TEXT NOT NULL DEFAULT '',
        status TEXT NOT NULL DEFAULT 'active', provisioned_user_id INTEGER DEFAULT NULL,
        note TEXT NOT NULL DEFAULT '', created_at TEXT NOT NULL, updated_at TEXT NOT NULL)");
    db()->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_allowed_emails_email ON allowed_emails(email) WHERE email <> ''");

    // Legacy 回灌
    foreach (db()->query("SELECT id, email, nickname FROM users WHERE role='member' AND email <> ''")->fetchAll() as $u) {
        if (allowed_email_find((string)$u['email'])) continue;
        db()->prepare("INSERT INTO allowed_emails(email,category,name,student_id,status,provisioned_user_id,note,created_at,updated_at)
                       VALUES(?,'legacy',?,'','provisioned',?,'auto-backfilled',?,?)")
            ->execute([(string)$u['email'], (string)$u['nickname'], (int)$u['id'], iso_now(), iso_now()]);
        $changed++;
    }

    // 外挂名单
    if (file_exists($studentsFile)) {
        $rows = require $studentsFile;
        foreach ($rows as $r) {
            $email = trim((string)($r[0] ?? ''));
            $name  = (string)($r[1] ?? '');
            $sid   = (string)($r[2] ?? '');
            if ($email !== '' && allowed_email_find($email)) continue;
            if ($email === '') {
                $q = db()->prepare("SELECT id FROM allowed_emails WHERE email='' AND name=? AND student_id=? LIMIT 1");
                $q->execute([$name, $sid]);
                if ($q->fetchColumn() !== false) continue;
            }
            allowed_email_upsert([
                'email' => $email, 'category' => 'course_vaccine', 'name' => $name, 'student_id' => $sid,
                'status' => $email === '' ? 'pending' : 'active',
            ]);
            $changed++;
        }
    }
    return $changed;
}

// 首跑
$c1 = do_migrate_v50_test($studentsFile);
check($c1 === 5, "首跑改动 5 条（legacy 2 + 疫苗课 2 active + 1 pending），实际 $c1");

// 幂等验证
$c2 = do_migrate_v50_test($studentsFile);
check($c2 === 0, "二次跑改动 0 条（幂等），实际 $c2");

// 验证分类计数
$legacy = allowed_email_list(['category' => 'legacy']);
check(count($legacy) === 2, 'legacy 分类计 2');
$vaccine = allowed_email_list(['category' => 'course_vaccine']);
check(count($vaccine) === 3, 'course_vaccine 分类计 3');
$pending = allowed_email_list(['category' => 'course_vaccine', 'status' => 'pending']);
check(count($pending) === 1, 'course_vaccine pending 计 1');

// 文件缺失降级
unlink($studentsFile);
rmdir($seedDir);
$c3 = do_migrate_v50_test($studentsFile);  // 文件已删
check($c3 === 0, '名单文件缺失时 0 改动 + 不炸');

// 清库 for 下一组测试
db()->exec('DELETE FROM allowed_emails');
db()->exec("DELETE FROM users WHERE username LIKE 'legacy%'");
