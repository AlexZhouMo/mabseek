<?php
declare(strict_types=1);
/**
 * migrate-images-webp.php —— 把数据库里存量的静态图片路径 .png/.jpg 迁移为 .webp
 *
 * 背景：仓库自带的 assets/images/*.png|jpg 已统一转为 WebP 并删除原图。
 *       代码引用（.php/.css/seed.php）随代码同步即可，但数据库里已存的历史记录
 *       （team_members.avatar_img、edu_reviews.cover/body 等）仍指向旧的 .png，
 *       部署时 data/ 被保留、seed 又“有数据即跳过”，故需本脚本就地迁移。
 *
 * 幂等 & 安全：
 *   - 遍历所有表的所有文本列，把形如 assets/images/xxx.png|jpg|jpeg 的引用改为 .webp
 *   - 只在对应的 .webp 文件确实存在于 docroot 时才替换 —— 绝不制造死链
 *   - 显式跳过 assets/images/uploads/ 下的用户上传图（那些是真实 png/jpg，无 webp 版本）
 *   - 可反复运行：已是 .webp 的记录不会被再次改动
 *
 * 用法：php bin/migrate-images-webp.php
 */
require_once __DIR__ . '/../app/bootstrap.php';

$pdo = db();

// docroot（public/）绝对路径，用于校验 webp 文件是否真实存在
$docroot = realpath(__DIR__ . '/../public');
if ($docroot === false) {
    fwrite(STDERR, "无法定位 public/ 目录\n");
    exit(1);
}

// 匹配 assets/images/ 下的 png/jpg 引用（可带前导 / 或不带），排除 uploads/
$pattern = '#(?<![a-zA-Z0-9._-])((?:/)?assets/images/(?!uploads/)[a-zA-Z0-9_/-]+)\.(png|jpe?g)#i';

echo "Migrating image paths to .webp...\n";

$totalCells = 0;   // 实际发生改动的单元格数
$skippedNoFile = 0; // 因目标 webp 不存在而跳过的引用数

// 取所有表
$tables = $pdo->query(
    "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"
)->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    // 取该表的主键列与所有文本列
    $cols = $pdo->query("PRAGMA table_info(" . '"' . str_replace('"', '""', $table) . '"' . ")")
                ->fetchAll(PDO::FETCH_ASSOC);

    $pkCol = null;
    $textCols = [];
    foreach ($cols as $col) {
        if ((int)$col['pk'] === 1) $pkCol = $col['name'];
        $type = strtoupper((string)$col['type']);
        // TEXT / VARCHAR / CLOB / 无类型（SQLite 动态类型）都当作可能含字符串
        if ($type === '' || strpos($type, 'CHAR') !== false || strpos($type, 'TEXT') !== false || strpos($type, 'CLOB') !== false) {
            $textCols[] = $col['name'];
        }
    }
    if ($pkCol === null || !$textCols) continue;

    // 逐列扫描含 assets/images/*.png|jpg 的行
    foreach ($textCols as $c) {
        $qi = '"' . str_replace('"', '""', $c) . '"';
        $ti = '"' . str_replace('"', '""', $table) . '"';
        $pk = '"' . str_replace('"', '""', $pkCol) . '"';

        $rows = $pdo->query(
            "SELECT $pk AS __id, $qi AS __val FROM $ti " .
            "WHERE $qi LIKE '%assets/images/%.png' " .
            "   OR $qi LIKE '%assets/images/%.jpg' " .
            "   OR $qi LIKE '%assets/images/%.jpeg'"
        )->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $old = (string)$row['__val'];
            $changedInCell = false;

            $new = preg_replace_callback($pattern, function ($m) use ($docroot, &$skippedNoFile, &$changedInCell) {
                $base = $m[1];                 // 形如 assets/images/team/ma 或 /assets/images/...
                $webpRel = ltrim($base, '/') . '.webp';
                $webpAbs = $docroot . '/' . $webpRel;
                if (is_file($webpAbs)) {       // 只有 webp 真实存在才替换
                    $changedInCell = true;
                    return $base . '.webp';
                }
                $skippedNoFile++;
                return $m[0];                  // 保留原样，绝不制造死链
            }, $old);

            if ($changedInCell && $new !== $old) {
                $upd = $pdo->prepare("UPDATE $ti SET $qi = :v WHERE $pk = :id");
                $upd->execute([':v' => $new, ':id' => $row['__id']]);
                $totalCells++;
                echo "  ~ {$table}.{$c}#{$row['__id']}\n";
            }
        }
    }
}

echo "  = done: {$totalCells} cell(s) updated";
if ($skippedNoFile > 0) echo ", {$skippedNoFile} ref(s) skipped (no .webp on disk)";
echo "\n";
