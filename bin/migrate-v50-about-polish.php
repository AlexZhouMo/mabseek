<?php
declare(strict_types=1);
/**
 * MabSeek V5.0 · about 页收尾迁移
 * 覆盖：about 页三条 eyebrow 去数字前缀（team/news/intl）
 * 幂等：UPDATE 带 WHERE value = old_value，二次运行必 0 改动。
 */
require_once __DIR__ . '/../app/bootstrap.php';

echo "== V5.0 about-polish 迁移开始 ==\n";
$pdo = db();

function m50a_update(string $key, string $old, string $new): int {
    $s = db()->prepare('UPDATE snippets SET value = ?, updated_at = ? WHERE skey = ? AND value = ?');
    $s->execute([$new, iso_now(), $key, $old]);
    return $s->rowCount();
}

$changed = 0;
$changed += m50a_update('about.team.eyebrow', '1 · 实验室与核心团队', '实验室与核心团队');
$changed += m50a_update('about.news.eyebrow', '2 · 新闻与活动分享', '新闻与活动分享');
$changed += m50a_update('about.intl.eyebrow', '3 · 国际科研合作', '国际科研合作');

echo "  ✓ about eyebrows 订正 $changed 条\n";
echo "== 迁移结束 · 共改动 $changed 行 ==\n";
exit(0);
