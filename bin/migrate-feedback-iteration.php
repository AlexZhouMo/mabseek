<?php
declare(strict_types=1);
/**
 * migrate-feedback-iteration.php —— 2026-09-08 反馈迭代第1-4批的存量库订正（合一脚本）
 *
 * 幂等 & 安全：各节仅在检测到旧状态时改动；反复运行旧状态消失即 0 改动。
 *   仅按精确 grp / skey / 旧分类key 定向订正，不误伤用户数据与管理员在后台改过的其它文案。
 *
 * 用法：php bin/migrate-feedback-iteration.php
 */
require_once __DIR__ . '/../app/bootstrap.php';

$pdo = db();
$now = date('Y-m-d\TH:i:sP');

// ── 1) 论坛历史帖分类 key 迁移（旧5类 → 新7类）──
//    paper 不变；industry/essay/qa 为新增无历史帖。
$catMap = ['proto' => 'method', 'pit' => 'method', 'bio' => 'resource', 'job' => 'other'];
$catUpd = $pdo->prepare("UPDATE forum_threads SET category = :new, updated_at = :t WHERE category = :old");
foreach ($catMap as $old => $new) {
    $catUpd->execute([':new' => $new, ':t' => $now, ':old' => $old]);
    echo "  分类 $old -> $new: {$catUpd->rowCount()} 行\n";
}

// ── 2) 删除论坛「核心内容板块」四条内容线（content_cards）+ 眉题/标题 snippet ──
$delLines = $pdo->exec("DELETE FROM content_cards WHERE grp = 'forum_line'");
echo "  删除 content_cards forum_line: {$delLines} 行\n";
$delLineTexts = $pdo->exec("DELETE FROM snippets WHERE skey IN ('forum.lines.eyebrow','forum.lines.title')");
echo "  删除 forum.lines.* snippet: {$delLineTexts} 行\n";

// ── 3) 删除页脚标语 snippet ──
$delSlogan = $pdo->exec("DELETE FROM snippets WHERE skey = 'footer.slogan'");
echo "  删除 footer.slogan: {$delSlogan} 行\n";

// ── 4) 删除教育页「学术讲座 + 成长资源」卡片(content_cards) + snippet ──
$delEduCards = $pdo->exec("DELETE FROM content_cards WHERE grp IN ('edu_lecture','edu_grow')");
echo "  删除 content_cards edu_lecture/edu_grow: {$delEduCards} 行\n";
$delEduSnip = $pdo->exec("DELETE FROM snippets WHERE skey IN ('edu.lecture.eyebrow','edu.lecture.title','edu.grow.eyebrow','edu.grow.title')");
echo "  删除 edu.lecture/grow snippet: {$delEduSnip} 行\n";

// ── 5) 反馈联系区文案订正（仅当值仍为旧文案时替换，管理员改过的不动）──
$copyMap = [
    'home.contact.eyebrow' => ['合作伙伴 · 联系我们', '意见反馈·寻求合作·加入我们'],
    'home.contact.h3'      => ['有靶点或合作意向？给我们留个言', '有建议或遇到了问题？欢迎告诉我们'],
];
$copyUpd = $pdo->prepare("UPDATE snippets SET value = :new, updated_at = :t WHERE skey = :k AND value = :old");
foreach ($copyMap as $k => $pair) {
    $copyUpd->execute([':new' => $pair[1], ':t' => $now, ':k' => $k, ':old' => $pair[0]]);
    echo "  文案 $k: {$copyUpd->rowCount()} 行\n";
}

echo "迁移完成。\n";
