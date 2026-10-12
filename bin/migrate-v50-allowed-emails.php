<?php
declare(strict_types=1);
/**
 * MabSeek V5.0 幂等迁移 · 注册准入白名单
 * 覆盖：建 allowed_emails 表（db.php 已幂等建，这里显式再跑一次防新库漏表）
 *      + legacy 回灌（现有 users.email → allowed_emails，category=legacy, status=provisioned）
 *      + 外挂名单导入（deploy/seeds/*.local.php；文件不存在仅 warn 不 fail）
 * 幂等：所有写入前先查重；二次运行必 $changed = 0
 */
require_once __DIR__ . '/../app/bootstrap.php';

echo "== V5.0 allowed-emails 迁移开始 ==\n";
$pdo    = db();
$changed = 0;

// ── Step 1: 建表 + 索引（防新库漏表；app/db.php 已幂等建） ──
$pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS allowed_emails (
  id                   INTEGER PRIMARY KEY AUTOINCREMENT,
  email                TEXT NOT NULL DEFAULT '',
  category             TEXT NOT NULL,
  name                 TEXT NOT NULL DEFAULT '',
  student_id           TEXT NOT NULL DEFAULT '',
  status               TEXT NOT NULL DEFAULT 'active',
  provisioned_user_id  INTEGER DEFAULT NULL,
  note                 TEXT NOT NULL DEFAULT '',
  created_at           TEXT NOT NULL,
  updated_at           TEXT NOT NULL
);
SQL);
$pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_allowed_emails_email ON allowed_emails(email) WHERE email <> ''");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_allowed_emails_cat_status ON allowed_emails(category, status)");
echo "  ✓ 表与索引就位\n";

// ── Step 2: Legacy 回灌 ──
$legacyIn = 0;
$users = $pdo->query("SELECT id, email, nickname FROM users WHERE role='member' AND email <> ''")->fetchAll();
foreach ($users as $u) {
    if (allowed_email_find((string)$u['email'])) continue;   // 幂等：已回灌过就跳过
    $now = iso_now();
    $pdo->prepare("INSERT INTO allowed_emails(email,category,name,student_id,status,provisioned_user_id,note,created_at,updated_at)
                   VALUES(?,'legacy',?,'','provisioned',?,'auto-backfilled from users',?,?)")
        ->execute([(string)$u['email'], (string)$u['nickname'], (int)$u['id'], $now, $now]);
    $legacyIn++;
}
echo "  ✓ legacy 回灌 $legacyIn 条\n";
$changed += $legacyIn;

// ── Step 3: 导入疫苗课学员 ──
$vaccineFile = __DIR__ . '/../deploy/seeds/students-emails.local.php';
$vaccineActive = $vaccinePending = 0;
if (file_exists($vaccineFile)) {
    $rows = require $vaccineFile;
    foreach ($rows as $r) {
        $email = trim((string)($r[0] ?? ''));
        $name  = (string)($r[1] ?? '');
        $sid   = (string)($r[2] ?? '');

        // 幂等查重：有邮箱按邮箱查；无邮箱按 (name, student_id) 查
        if ($email !== '' && allowed_email_find($email)) continue;
        if ($email === '') {
            $q = $pdo->prepare("SELECT id FROM allowed_emails WHERE email='' AND name=? AND student_id=? LIMIT 1");
            $q->execute([$name, $sid]);
            if ($q->fetchColumn() !== false) continue;
        }

        allowed_email_upsert([
            'email'      => $email,
            'category'   => 'course_vaccine',
            'name'       => $name,
            'student_id' => $sid,
            'status'     => $email === '' ? 'pending' : 'active',
        ]);
        $email === '' ? $vaccinePending++ : $vaccineActive++;
    }
    echo "  ✓ course_vaccine 导入 · active=$vaccineActive · pending=$vaccinePending\n";
    $changed += ($vaccineActive + $vaccinePending);
} else {
    echo "  ! warn: $vaccineFile 不存在，跳过（在线部署首次需 scp 该文件到服务器）\n";
}

// ── Step 4: 导入张老师实验室 ──
$zhangFile = __DIR__ . '/../deploy/seeds/zhang-lab-emails.local.php';
$zhangIn = 0;
if (file_exists($zhangFile)) {
    $rows = require $zhangFile;
    foreach ($rows as $r) {
        $email = trim((string)($r[0] ?? ''));
        $name  = (string)($r[1] ?? '');
        if ($email === '') continue;                           // 张老师名单不该有空邮箱
        if (allowed_email_find($email)) continue;              // 幂等
        allowed_email_upsert([
            'email'    => $email,
            'category' => 'zhang_lab',
            'name'     => $name,
            'status'   => 'active',
        ]);
        $zhangIn++;
    }
    echo "  ✓ zhang_lab 导入 $zhangIn 条\n";
    $changed += $zhangIn;
} else {
    echo "  ! warn: $zhangFile 不存在，跳过\n";
}

echo "== 迁移结束 · 共改动 $changed 行 ==\n";
exit(0);
