<?php
declare(strict_types=1);

/**
 * V4 批 2b-2a 幂等迁移：MabSeek 平台页视觉升级
 * - 补齐 4 条 platform.hero.eyebrow + platform.data.{eyebrow,title,sub}
 * - 删除 36 个孤儿 agent.* / tech.* snippet（Task 4 grep 后固化）
 * - 幂等：seed 已在则跳过；DELETE WHERE IN 无匹配则 0 影响
 */

require __DIR__ . '/../app/bootstrap.php';

$new = [
    ['platform.hero.eyebrow', 'MabSeek 平台 · AI + 湿实验一站式',                              'platform_hero', '平台 Hero·眉题',   'text'],
    ['platform.data.eyebrow', '真实数据驱动',                                                   'platform_data', '数据驱动·眉题',    'text'],
    ['platform.data.title',   'AI 平台已支撑的靶点谱系',                                        'platform_data', '数据驱动·标题',    'text'],
    ['platform.data.sub',     '覆盖 GPCR、T 细胞激动、免疫肿瘤等靶点类型',                      'platform_data', '数据驱动·副标题',  'text'],
];

$beforeIns = (int) db()->query("SELECT COUNT(*) FROM snippets WHERE skey LIKE 'platform.%'")->fetchColumn();
foreach ($new as [$k, $v, $g, $l, $t]) {
    Snippets::seed($k, $v, $g, $l, $t);
}
$inserted = (int) db()->query("SELECT COUNT(*) FROM snippets WHERE skey LIKE 'platform.%'")->fetchColumn() - $beforeIns;

// 孤儿 agent.* / tech.* 清理（Task 4 grep 后固化，36 条）
$orphans = [
    'agent.case.eyebrow',
    'agent.case.sub',
    'agent.case.title',
    'agent.hero.cta1',
    'agent.hero.cta2',
    'agent.hero.eyebrow',
    'tech.arch.cta',
    'tech.arch.eyebrow',
    'tech.arch.sub',
    'tech.arch.title',
    'tech.case.cta',
    'tech.case.eyebrow',
    'tech.case.sub',
    'tech.case.title',
    'tech.hero.cta',
    'tech.hero.eyebrow',
    'tech.hero.sub',
    'tech.hero.title',
    'tech.p1.eyebrow',
    'tech.p1.link',
    'tech.p1.point1',
    'tech.p1.point2',
    'tech.p1.point3',
    'tech.p1.sub',
    'tech.p1.title',
    'tech.p2.eyebrow',
    'tech.p2.link',
    'tech.p2.point1',
    'tech.p2.point2',
    'tech.p2.point3',
    'tech.p2.sub',
    'tech.p2.title',
    'tech.p3.link',
    'tech.p3.point1',
    'tech.p3.point2',
    'tech.p3.point3',
];

$deleted = 0;
if (!empty($orphans)) {
    $placeholders = implode(',', array_fill(0, count($orphans), '?'));
    $d = db()->prepare("DELETE FROM snippets WHERE skey IN ({$placeholders})");
    $d->execute($orphans);
    $deleted = $d->rowCount();
}

echo "[migrate-v4-batch2b2a] platform.* inserted: {$inserted}\n";
echo "[migrate-v4-batch2b2a] orphan snippets deleted: {$deleted}\n";
