<?php
declare(strict_types=1);
/**
 * migrate-news-category.php —— 20260911 反馈：新闻分类改 3 类 + 筛选 chip 文案订正
 *
 * 幂等 & 安全：分类按精确旧 key UPDATE（旧 key 消失后二次运行 0 行）；
 *   chip 文案按精确旧值订正（管理员改过的不动）。
 *
 * 用法：php bin/migrate-news-category.php
 */
require_once __DIR__ . '/../app/bootstrap.php';

$pdo = db();
$now = date('Y-m-d\TH:i:sP');

// ── 1) 新闻历史分类 key 迁移（res→research, edu/daily→team）──
$catMap = ['res' => 'research', 'edu' => 'team', 'daily' => 'team'];
$catUpd = $pdo->prepare("UPDATE news SET category = :new, updated_at = :t WHERE category = :old");
foreach ($catMap as $old => $new) {
    $catUpd->execute([':new' => $new, ':t' => $now, ':old' => $old]);
    echo "  分类 $old -> $new: {$catUpd->rowCount()} 行\n";
}

// ── 2) 新闻筛选 chip 文案订正（复用键名, 仅当值仍为旧文案时替换）──
$chipMap = [
    'about.news.chip_res'   => ['科研类', '研究进展'],
    'about.news.chip_edu'   => ['育人类', '团队动态'],
    'about.news.chip_daily' => ['日常活动', '产品发布'],
];
$snipUpd = $pdo->prepare("UPDATE snippets SET value = :new, updated_at = :t WHERE skey = :k AND value = :old");
foreach ($chipMap as $k => $pair) {
    $snipUpd->execute([':new' => $pair[1], ':t' => $now, ':k' => $k, ':old' => $pair[0]]);
    echo "  chip $k: {$snipUpd->rowCount()} 行\n";
}

echo "迁移完成。\n";
