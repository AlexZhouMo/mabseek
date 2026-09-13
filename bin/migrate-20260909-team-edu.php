<?php
declare(strict_types=1);
/**
 * migrate-20260909-team-edu.php —— 20260909 反馈：删核心团队马老师 + 清教育页图谱营销文案
 *
 * 幂等 & 安全：各节仅在检测到旧状态时改动；反复运行旧状态消失即 0 改动。
 *   删除按精确 name+role_type；文案订正按精确旧值全等匹配（含 HTML 标签），
 *   不误伤管理员在后台改过的文案与后台新增的团队成员。
 *
 * 用法：php bin/migrate-20260909-team-edu.php
 */
require_once __DIR__ . '/../app/bootstrap.php';

$pdo = db();
$now = date('Y-m-d\TH:i:sP');

// ── 1) 删除核心团队马老师（team_members 种子人物）──
$delMa = $pdo->prepare("DELETE FROM team_members WHERE name = :n AND role_type = :r");
$delMa->execute([':n' => '马维英', ':r' => 'ai']);
echo "  删除 team_members 马维英: {$delMa->rowCount()} 行\n";

// ── 2) 团队区文案订正 + 3) 教育页图谱营销文案订正（仅当值仍为旧文案时替换）──
$copyMap = [
    'about.team.title' => [
        '两位负责人，AI 与抗体科学的交汇',
        '实验室与核心团队',
    ],
    'about.team.sub' => [
        'MabSeek 由抗体与病毒免疫的科学积累，叠加人工智能与大模型能力共同驱动——「AI × 抗体」正是两位负责人研究方向的交汇。',
        'MabSeek 依托抗体工程、疫苗研发与病毒免疫的深厚科学积累，结合 AI 与大模型能力，打通从抗体设计到湿实验验证的完整闭环。',
    ],
    'about.team.disclaimer' => [
        '注：以上为门户展示模板，详细简历与成果列表参考清华大学医学院官网张林琦、马维英主页，正式上线时同步更新。',
        '注：以上为门户展示模板，详细简历与成果列表参考清华大学医学院官网张林琦主页，正式上线时同步更新。',
    ],
    'edu.hero.title' => [
        '让知识<span class="grad-text">可交互、可溯源、可生长</span>',
        '让知识<span class="grad-text">系统沉淀、清晰可循</span>',
    ],
    'edu.hero.lead' => [
        '元视频拆解 + 分层交互式知识图谱 + 实时互动，打通「视频观看 — 弹幕交流 — 图谱梳理 — AI 答疑」完整教学闭环。',
        '元视频拆解 + 实时互动，打通「视频观看 — 弹幕交流 — AI 答疑」的完整教学闭环。',
    ],
    'edu.banner.sub' => [
        '元视频 + 交互式知识图谱体系 · 精准适配学生、疫苗研发从业者、接种人群与新手父母',
        '元视频课程体系 · 精准适配学生、疫苗研发从业者、接种人群与新手父母',
    ],
];
$copyUpd = $pdo->prepare("UPDATE snippets SET value = :new, updated_at = :t WHERE skey = :k AND value = :old");
foreach ($copyMap as $k => $pair) {
    $copyUpd->execute([':new' => $pair[1], ':t' => $now, ':k' => $k, ':old' => $pair[0]]);
    echo "  文案 $k: {$copyUpd->rowCount()} 行\n";
}

// ── 4) edu_info「课程简介」卡片 items 内替换（content_cards.extra 是 JSON）──
$OLD_ITEM = '视频画面与知识图谱双向跳转溯源';
$NEW_ITEM = '按知识点精准点播，边看边学';
$rows = $pdo->query("SELECT id, extra FROM content_cards WHERE grp = 'edu_info'")->fetchAll(PDO::FETCH_ASSOC);
$eduInfoChanged = 0;
$eduUpd = $pdo->prepare("UPDATE content_cards SET extra = :e, updated_at = :t WHERE id = :id");
foreach ($rows as $row) {
    $data = json_decode((string)$row['extra'], true);
    if (!is_array($data) || empty($data['items']) || !is_array($data['items'])) continue;
    $hit = false;
    foreach ($data['items'] as $i => $it) {
        if ($it === $OLD_ITEM) { $data['items'][$i] = $NEW_ITEM; $hit = true; }
    }
    if ($hit) {
        $eduUpd->execute([
            ':e'  => json_encode($data, JSON_UNESCAPED_UNICODE),
            ':t'  => $now,
            ':id' => $row['id'],
        ]);
        $eduInfoChanged++;
    }
}
echo "  edu_info 卡片图谱条订正: {$eduInfoChanged} 张\n";

echo "迁移完成。\n";
