<?php
declare(strict_types=1);
/**
 * MabSeek V4.4 幂等迁移
 * 覆盖：屏 2 Agent 左右翻转 + 主标重构 · 屏 3 Data L 形四象限 · 屏 4a Lab 三段重排 · 屏 5 Loop 清理
 * 幂等：所有辅助函数在无变化时返回 0；总 $changed 二次运行必须为 0。
 */
require_once __DIR__ . '/../app/bootstrap.php';

echo "== V4.4 迁移开始 ==\n";
$pdo = db();

// ── 辅助函数(与 migrate-v43 同,前缀 m44 避免函数重复定义) ──────────────
function m44_update_snippet(string $key, string $newValue): int {
    $s = db()->prepare('SELECT value FROM snippets WHERE skey = ?');
    $s->execute([$key]);
    $curr = $s->fetchColumn();
    if ($curr === false) return 0;
    if ((string)$curr === $newValue) return 0;
    db()->prepare('UPDATE snippets SET value = ?, updated_at = ? WHERE skey = ?')
        ->execute([$newValue, iso_now(), $key]);
    return 1;
}
function m44_upsert_snippet(string $key, string $newValue, string $grp, string $type = 'text', string $label = ''): int {
    $s = db()->prepare('SELECT value FROM snippets WHERE skey = ?');
    $s->execute([$key]);
    $curr = $s->fetchColumn();
    $now = iso_now();
    if ($curr === false) {
        db()->prepare('INSERT INTO snippets(skey,value,grp,label,type,sort,created_at,updated_at) VALUES(?,?,?,?,?,0,?,?)')
            ->execute([$key, $newValue, $grp, $label, $type, $now, $now]);
        return 1;
    }
    if ((string)$curr === $newValue) return 0;
    db()->prepare('UPDATE snippets SET value = ?, updated_at = ? WHERE skey = ?')
        ->execute([$newValue, $now, $key]);
    return 1;
}
function m44_upsert_card(string $grp, int $sort, array $row): int {
    $expected = [
        'title'     => (string)($row['title'] ?? ''),
        'body'      => (string)($row['body']  ?? ''),
        'icon'      => (string)($row['icon']  ?? ''),
        'extra'     => (string)($row['extra'] ?? ''),
        'published' => (int)($row['published'] ?? 1),
    ];
    $s = db()->prepare('SELECT id,title,body,icon,extra,published FROM content_cards WHERE grp = ? AND sort = ?');
    $s->execute([$grp, $sort]);
    $curr = $s->fetch();
    $now = iso_now();
    if (!$curr) {
        db()->prepare('INSERT INTO content_cards(grp,sort,icon,title,body,extra,published,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?)')
            ->execute([$grp, $sort, $expected['icon'], $expected['title'], $expected['body'], $expected['extra'], $expected['published'], $now, $now]);
        return 1;
    }
    $diff = false;
    foreach (['title','body','icon','extra'] as $f) {
        if ((string)$curr[$f] !== $expected[$f]) { $diff = true; break; }
    }
    if (!$diff && (int)$curr['published'] === $expected['published']) return 0;
    db()->prepare('UPDATE content_cards SET title=?, body=?, icon=?, extra=?, published=?, updated_at=? WHERE id = ?')
        ->execute([$expected['title'], $expected['body'], $expected['icon'], $expected['extra'], $expected['published'], $now, $curr['id']]);
    return 1;
}
function m44_delete_cards_beyond(string $grp, int $maxSort): int {
    $s = db()->prepare('SELECT COUNT(*) FROM content_cards WHERE grp = ? AND sort > ?');
    $s->execute([$grp, $maxSort]);
    $n = (int)$s->fetchColumn();
    if ($n === 0) return 0;
    db()->prepare('DELETE FROM content_cards WHERE grp = ? AND sort > ?')->execute([$grp, $maxSort]);
    return $n;
}
function m44_delete_snippets(array $keys): int {
    if (!$keys) return 0;
    $ph = implode(',', array_fill(0, count($keys), '?'));
    $s = db()->prepare("SELECT COUNT(*) FROM snippets WHERE skey IN ($ph)");
    $s->execute($keys);
    $n = (int)$s->fetchColumn();
    if ($n === 0) return 0;
    db()->prepare("DELETE FROM snippets WHERE skey IN ($ph)")->execute($keys);
    return $n;
}

$changed = 0;

// ─── 屏 2 · Agent 左右翻转 + 主标重构 ─────────────────────────────
$c = m44_upsert_snippet('platform.agent.brand', 'Antibody Agent', 'platform_agent', 'text', '智能体·品牌大标');
echo "  屏 2 · agent.brand upsert: $c\n"; $changed += $c;

$c = m44_update_snippet('platform.agent.title', '从研究问题出发，连接设计、预测与实验');
echo "  屏 2 · agent.title 去 span: $c\n"; $changed += $c;

$c = m44_delete_snippets(['platform.agent.eyebrow']);
echo "  屏 2 · agent.eyebrow 删除: $c\n"; $changed += $c;

// ─── 屏 3 · Data L 形四象限 ──────────────────────────────────────
$V44_DATA_CARDS = [
    ['icon'=>'📊','title'=>'真实数据','body'=>'正/负结合数据｜抗体序列与靶点信息｜结合与功能实验结果'],
    ['icon'=>'🤖','title'=>'AI 分析','body'=>'抗体生成与优化｜结构与亲和力预测｜候选排序与成药性评估'],
];
foreach ($V44_DATA_CARDS as $i => $row) {
    $c = m44_upsert_card('platform_data_card', $i + 1, $row);
    echo "  屏 3 · data_card[" . ($i+1) . "] upsert: $c\n"; $changed += $c;
}
$c = m44_delete_cards_beyond('platform_data_card', 2);
echo "  屏 3 · data_card beyond 2 删除: $c\n"; $changed += $c;

$V44_DATA_FLOW = [
    ['icon'=>'📥','title'=>'数据输入','body'=>'序列 · 结构 · 靶点'],
    ['icon'=>'🧠','title'=>'AI 分析','body'=>'结构预测 · 亲和力预测 · 候选排序'],
    ['icon'=>'⭐','title'=>'候选输出','body'=>'优先候选（高亲和力、高成药性）'],
];
foreach ($V44_DATA_FLOW as $i => $row) {
    $c = m44_upsert_card('platform_data_flow', $i + 1, $row);
    echo "  屏 3 · data_flow[" . ($i+1) . "] upsert: $c\n"; $changed += $c;
}
$c = m44_delete_cards_beyond('platform_data_flow', 3);
echo "  屏 3 · data_flow beyond 3 删除: $c\n"; $changed += $c;

// ─── 屏 4a · Lab 三段重排 ────────────────────────────────────────
$c = m44_update_snippet('platform.lab.title', '全流程抗体发现实验平台');
echo "  屏 4a · lab.title 升格主标: $c\n"; $changed += $c;

$c = m44_upsert_snippet('platform.lab.sub', 'VLP 天然构象呈递 × 高通量自动化筛选', 'platform_lab', 'textarea', '实验平台·副标');
echo "  屏 4a · lab.sub upsert: $c\n"; $changed += $c;

$c = m44_update_snippet('platform.lab.hero_photo', 'assets/images/platform/lab-hero-photo.webp');
echo "  屏 4a · lab.hero_photo 换路径: $c\n"; $changed += $c;

$c = m44_delete_snippets(['platform.lab.eyebrow']);
echo "  屏 4a · lab.eyebrow 删除: $c\n"; $changed += $c;

$V44_LAB_SCALE = [
    ['title'=>'皮升级液滴','body'=>'液滴生成 10⁷⁻⁸ 个/h · 10¹⁻² pL'],
    ['title'=>'微升级液滴','body'=>'液滴生成 10³⁻⁴ 个/h · 1–3 μL'],
];
foreach ($V44_LAB_SCALE as $i => $row) {
    $c = m44_upsert_card('platform_lab_scale', $i + 1, $row);
    echo "  屏 4a · lab_scale[" . ($i+1) . "] upsert: $c\n"; $changed += $c;
}
$c = m44_delete_cards_beyond('platform_lab_scale', 2);
echo "  屏 4a · lab_scale beyond 2 删除: $c\n"; $changed += $c;

// ─── 屏 5 · Loop 清理 ────────────────────────────────────────────
$c = m44_delete_snippets(['platform.loop.center_label', 'platform.loop.tempo']);
echo "  屏 5 · loop.center_label + loop.tempo 删除: $c\n"; $changed += $c;

// ─── 素材清理:兜底 unlink 旧顶部横幅 ───────────────────────────
$oldPhoto = __DIR__ . '/../public/assets/images/platform/lab-auto-room.webp';
if (is_file($oldPhoto)) {
    @unlink($oldPhoto);
    echo "  屏 4a · unlink lab-auto-room.webp: 1\n";
    $changed += 1;
} else {
    echo "  屏 4a · lab-auto-room.webp 不存在,跳过\n";
}

echo "== V4.4 迁移结束 · 影响 {$changed} 行 ==\n";
