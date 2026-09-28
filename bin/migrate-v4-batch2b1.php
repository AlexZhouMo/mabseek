<?php
declare(strict_types=1);

/**
 * V4 批 2b-1 幂等迁移：MabSeek 平台页骨架
 *
 * - 补齐 21 条 platform.* snippet（用 Snippets::seed，不覆盖后台已改动）
 * - 就地订正 home.hero.cta 现值：仅当值仍为旧字面"免费试用 Antibody Agent →"
 *   时更新为"免费试用 MabSeek 平台 →"，保护后台可能已改的值
 * - 二次跑幂等：所有 seed 已存在则跳过；home.hero.cta 值已改则跳过 → 0 改动
 *
 * 用法：sudo -u www-data php bin/migrate-v4-batch2b1.php
 * 挂入：deploy/deploy-mabseek.sh 的 MIGRATIONS 数组
 */

require __DIR__ . '/../app/bootstrap.php';

$new = [
    ['platform.hero.cta1',        '开始试用',                                                       'platform_hero',     '平台 Hero·主 CTA', 'text'],
    ['platform.hero.cta2',        '了解详情',                                                       'platform_hero',     '平台 Hero·副 CTA', 'text'],
    ['platform.clinical.eyebrow', '临床与学术成果',                                                 'platform_clinical', '临床成果·小标签',  'text'],
    ['platform.clinical.title',   '真实世界数据 · 复杂靶点覆盖',                                    'platform_clinical', '临床成果·标题',    'html'],
    ['platform.clinical.sub',     '已支撑多个新药项目推进临床阶段，覆盖 GPCR、离子通道等复杂膜蛋白靶点。', 'platform_clinical', '临床成果·副标题',  'text'],
    ['platform.clinical.item1',   '▸ 支撑 XX 家药企的抗体发现 pipeline',                            'platform_clinical', '临床成果·条目 1',  'text'],
    ['platform.clinical.item2',   '▸ 已推进 X 个候选进入 IND-enabling 阶段',                        'platform_clinical', '临床成果·条目 2',  'text'],
    ['platform.clinical.item3',   '▸ 覆盖 GLP-1R / CXCR4 / CD3 等复杂膜蛋白',                       'platform_clinical', '临床成果·条目 3',  'text'],
    ['platform.clinical.item4',   '▸ Nature / Cell 系列论文 X 篇（清华医学院）',                    'platform_clinical', '临床成果·条目 4',  'text'],
    ['platform.lab.item1.title',  'VLP 类病毒颗粒',                                                 'platform_lab',      '实验·VLP 标题',    'text'],
    ['platform.lab.item1.body',   '类病毒颗粒展示 · 真核 / 原核表达',                               'platform_lab',      '实验·VLP 描述',    'text'],
    ['platform.lab.item2.title',  '表达纯化',                                                       'platform_lab',      '实验·纯化 标题',   'text'],
    ['platform.lab.item2.body',   'HEK293 / CHO 表达 · 亲和层析纯化',                               'platform_lab',      '实验·纯化 描述',   'text'],
    ['platform.lab.item3.title',  '亲和力测定',                                                     'platform_lab',      '实验·亲和力 标题', 'text'],
    ['platform.lab.item3.body',   'SPR / BLI / ELISA 多平台并行',                                   'platform_lab',      '实验·亲和力 描述', 'text'],
    ['platform.lab.item4.title',  '功能验证',                                                       'platform_lab',      '实验·功能 标题',   'text'],
    ['platform.lab.item4.body',   '细胞水平活性 · 报告基因 · FACS',                                 'platform_lab',      '实验·功能 描述',   'text'],
    ['platform.lab.item5.title',  '结构解析',                                                       'platform_lab',      '实验·结构 标题',   'text'],
    ['platform.lab.item5.body',   'Cryo-EM / X-ray · 抗原抗体复合物',                               'platform_lab',      '实验·结构 描述',   'text'],
    ['platform.lab.item6.title',  '微流控筛选',                                                     'platform_lab',      '实验·微流控 标题', 'text'],
    ['platform.lab.item6.body',   '单细胞液滴 · 高通量克隆筛选',                                    'platform_lab',      '实验·微流控 描述', 'text'],
];

$before = (int) db()->query("SELECT COUNT(*) FROM snippets WHERE skey LIKE 'platform.%'")->fetchColumn();

foreach ($new as [$skey, $value, $grp, $label, $type]) {
    Snippets::seed($skey, $value, $grp, $label, $type);
}

$after    = (int) db()->query("SELECT COUNT(*) FROM snippets WHERE skey LIKE 'platform.%'")->fetchColumn();
$inserted = $after - $before;

// home.hero.cta 订正（仅当仍是旧字面时）
$oldCta = '免费试用 Antibody Agent →';
$newCta = '免费试用 MabSeek 平台 →';
$u = db()->prepare('UPDATE snippets SET value = ?, updated_at = ? WHERE skey = ? AND value = ?');
$u->execute([$newCta, iso_now(), 'home.hero.cta', $oldCta]);
$updatedCta = $u->rowCount();

echo "[migrate-v4-batch2b1] platform.* inserted: {$inserted}\n";
echo "[migrate-v4-batch2b1] home.hero.cta updated: {$updatedCta}\n";
