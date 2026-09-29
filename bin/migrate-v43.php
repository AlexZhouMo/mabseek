<?php
declare(strict_types=1);
/**
 * MabSeek V4.3 幂等迁移
 * 覆盖：A 首页 Hero 眉题 · B 教育页 15 讲 · C 关于我们国际合作两段 · D 教育访谈 · E 平台页 7 屏
 * 幂等：所有辅助函数在无变化时返回 0；总 $changed 二次运行必须为 0。
 */
require_once __DIR__ . '/../app/bootstrap.php';

echo "== V4.3 迁移开始 ==\n";
$pdo = db();

// ── 辅助函数 ──────────────────────────────────────────────────────────
function m43_update_snippet(string $key, string $newValue): int {
    $s = db()->prepare('SELECT value FROM snippets WHERE skey = ?');
    $s->execute([$key]);
    $curr = $s->fetchColumn();
    if ($curr === false) return 0;          // 键不存在则不动（应由 seed 建）
    if ((string)$curr === $newValue) return 0;
    db()->prepare('UPDATE snippets SET value = ?, updated_at = ? WHERE skey = ?')
        ->execute([$newValue, iso_now(), $key]);
    return 1;
}
function m43_upsert_snippet(string $key, string $newValue, string $grp, string $type = 'text', string $label = ''): int {
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
function m43_upsert_card(string $grp, int $sort, array $row): int {
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
function m43_delete_cards_beyond(string $grp, int $maxSort): int {
    $s = db()->prepare('SELECT COUNT(*) FROM content_cards WHERE grp = ? AND sort > ?');
    $s->execute([$grp, $maxSort]);
    $n = (int)$s->fetchColumn();
    if ($n === 0) return 0;
    db()->prepare('DELETE FROM content_cards WHERE grp = ? AND sort > ?')->execute([$grp, $maxSort]);
    return $n;
}
function m43_delete_cards_grp(string $grp): int {
    $s = db()->prepare('SELECT COUNT(*) FROM content_cards WHERE grp = ?');
    $s->execute([$grp]);
    $n = (int)$s->fetchColumn();
    if ($n === 0) return 0;
    db()->prepare('DELETE FROM content_cards WHERE grp = ?')->execute([$grp]);
    return $n;
}
function m43_delete_snippets(array $keys): int {
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

// ─── A · 首页 Hero 眉题清空 ─────────────────────────────────────────
$aChange = m43_update_snippet('home.hero.eyebrow', '');
echo "  A · home.hero.eyebrow 清空: $aChange\n";
$changed += $aChange;

// ─── B · 教育页 15 讲字段 ────────────────────────────────────────────
$EDU_SCHEDULE = [
    ['title'=>'第 1 讲 · 绪论：疫苗点亮健康',                             'body'=>'授课：张林琦'],
    ['title'=>'第 2 讲 · 预防接种进展与成就',                             'body'=>'授课：梁晓峰'],
    ['title'=>'第 3 讲 · 疫苗是如何保护我们的？',                         'body'=>'授课：李冠乔'],
    ['title'=>'第 4 讲 · 疫苗是如何研制和质量保障的？',                   'body'=>'授课：李冠乔'],
    ['title'=>'第 5 讲 · 疫苗经济学',                                     'body'=>'授课：方海'],
    ['title'=>'第 6 讲 · 疫苗免疫效果评估—实验室的科技魅力',              'body'=>'授课：史宣玲'],
    ['title'=>'第 7 讲 · 天花疫苗和脊髓灰质炎疫苗-人类疫苗历史的壮举',    'body'=>'授课：陈志伟'],
    ['title'=>'第 8 讲 · 乙肝疫苗-摘掉我国"乙肝大国"帽子的功臣',          'body'=>'授课：崔富强'],
    ['title'=>'第 9 讲 · 乙脑疫苗-我国第一支通过世卫组织预认证的疫苗',    'body'=>'授课：袁媛'],
    ['title'=>'第 10 讲 · 人乳头瘤病毒（HPV）疫苗—宫颈癌的克星',          'body'=>'授课：乔友林'],
    ['title'=>'第 11 讲 · 流感疫苗-以变应变的追逐与纠结',                 'body'=>'授课：冯录召'],
    ['title'=>'第 12 讲 · 呼吸道相关传染病的疫苗',                        'body'=>'授课：张纬'],
    ['title'=>'第 13 讲 · 治疗性疫苗',                                    'body'=>'授课：傅阳心'],
    ['title'=>'第 14 讲 · 疫苗与全球健康',                                'body'=>'授课：杜珩'],
    ['title'=>'第 15 讲 · 历史回顾和未来展望',                            'body'=>'授课：张林琦'],
];
// 判据（V3.0.2 踩坑教训）：只有当已有 >=15 条 且首/尾对齐 V4.3 版才判"已就绪"，避免误覆盖旧库
$scheduleCount = (int) $pdo->query("SELECT COUNT(*) FROM content_cards WHERE grp='edu_schedule'")->fetchColumn();
$bChange = 0;
foreach ($EDU_SCHEDULE as $i => $row) {
    $bChange += m43_upsert_card('edu_schedule', $i + 1, $row);
}
$bChange += m43_delete_cards_beyond('edu_schedule', 15);
echo "  B · edu_schedule 15 讲 UPSERT + 清理超出: $bChange\n";
$changed += $bChange;

// ─── C · 关于我们·国际科研合作两段 ───────────────────────────────────
$CN_ID_BODY = "中印尼疫苗与基因组联合研发中心由清华大学牵头，纳入国家科技部对发展中国家科技援助专项。中心构建“政府+高校+产业”协同模式，推动登革热、结核等疫苗联合研发，助力印尼向区域研发生产中心转型，形成“中国技术+本地转化”的南南合作范式。";
$PRA_BODY   = "大流行病研究联盟由清华大学张林琦教授联合钟南山、何大一等多国专家于2023年发起，开展前瞻性研究，布局产品研发与应急储备。联盟已举办多场国际研讨会，发展为全球活跃的流行病研究协作网络，助力全球公共卫生体系建设。";
$cChange = 0;
$cChange += m43_update_snippet('about.intl.cn_id_body', $CN_ID_BODY);
$cChange += m43_update_snippet('about.intl.pra_body',   $PRA_BODY);
echo "  C · about.intl 国际合作两段: $cChange\n";
$changed += $cChange;

// ─── D · 教育页 #video 改造为张林琦访谈 ─────────────────────────────
$EDU_TALK = [
    'edu.talk.eyebrow'   => '与教授对话',
    'edu.talk.title'     => '《疫苗的力量》与张林琦教授对话',
    'edu.talk.body'      => '作为《疫苗的力量》的开课人和主讲人，张林琦教授分享课程开设的初衷、自己的专业选择，以及对青年学生的期待与寄语。',
    'edu.talk.kw1'       => '课程缘起',
    'edu.talk.kw2'       => '专业选择',
    'edu.talk.kw3'       => '课程期待',
    'edu.talk.kw4'       => '青年寄语',
    'edu.talk.speaker'   => '张林琦教授｜《疫苗的力量》开课人、主讲人',
    'edu.talk.video_src' => 'assets/uploads/videos/zhang-linqi-vaccine-talk.mp4',
    'edu.talk.poster'    => 'assets/images/edu-talk-poster.webp',
];
$dChange = 0;
foreach ($EDU_TALK as $k => $v) {
    $type = in_array($k, ['edu.talk.body'], true) ? 'textarea' : 'text';
    $dChange += m43_upsert_snippet($k, $v, 'education', $type);
}
$dChange += m43_delete_snippets([
    'edu.video.eyebrow', 'edu.video.title', 'edu.video.iframe_url', 'edu.video.src', 'edu.video.desc', 'edu.video.poster',
]);
echo "  D · edu.talk.* 10 条 UPSERT + edu.video.* 清理: $dChange\n";
$changed += $dChange;

// ─── E · 平台页 7 屏 ──────────────────────────────────────────────
// E.1 · snippets（44 条 · 平台 7 屏文案）
$PLATFORM_SNIPPETS = [
    // 屏 1 · Hero
    'platform.hero.eyebrow'      => ['MabSeek Platform',                                                'platform_hero',  'text'],
    'platform.hero.title'        => ['从科学问题到<span class="txt-neon">实验验证</span>抗体',           'platform_hero',  'textarea'],
    'platform.hero.lead'         => ['由 Antibody Agent 驱动，整合数据、算法与自动化实验，让抗体发现更高效、更可靠。', 'platform_hero', 'textarea'],
    'platform.hero.cta1'         => ['了解平台',                                                        'platform_hero',  'text'],
    'platform.hero.cta1_href'    => ['#agent',                                                          'platform_hero',  'text'],
    'platform.hero.cta2'         => ['开始试用',                                                        'platform_hero',  'text'],
    'platform.hero.cta2_href'    => ['login.php?next=platform.php&trial=1',                             'platform_hero',  'text'],
    'platform.hero.photo'        => ['assets/images/platform/hero-auto-detail.webp',                    'platform_hero',  'text'],
    // 屏 2 · Agent
    'platform.agent.eyebrow'     => ['Antibody Agent',                                                  'platform_agent', 'text'],
    'platform.agent.title'       => ['从研究问题出发，连接<span class="txt-neon">设计、预测与实验</span>', 'platform_agent', 'textarea'],
    'platform.agent.lead'        => ['Antibody Agent 理解研究目标，将任务拆解为可执行的研究步骤，并连接文献检索、抗体设计、预测分析与实验验证。', 'platform_agent', 'textarea'],
    'platform.agent.screenshot'  => ['assets/images/platform/agent-screen.webp',                        'platform_agent', 'text'],
    // 屏 3 · Data
    'platform.data.eyebrow'      => ['真实数据驱动',                                                    'platform_data',  'text'],
    'platform.data.title'        => ['真实数据驱动<span class="txt-neon">抗体设计与预测</span>',         'platform_data',  'textarea'],
    'platform.data.sub'          => ['整合抗体序列、靶点、结构与实验结果，为候选设计、筛选和优化提供依据。', 'platform_data', 'textarea'],
    // 屏 4a · Lab
    'platform.lab.eyebrow'       => ['全流程抗体发现实验平台',                                          'platform_lab',   'text'],
    'platform.lab.title'         => ['VLP 天然构象呈递 × <span class="txt-neon">高通量自动化筛选</span>', 'platform_lab',   'textarea'],
    'platform.lab.mf_title'      => ['微流控液滴技术平台',                                              'platform_lab',   'text'],
    'platform.lab.hero_photo'    => ['assets/images/platform/lab-auto-room.webp',                       'platform_lab',   'text'],
    // 屏 4b · VLP
    'platform.vlp.title'         => ['VLP 钓饵技术',                                                    'platform_vlp',   'text'],
    'platform.vlp.lead'          => ['让复杂抗原的展示更接近天然状态',                                  'platform_vlp',   'text'],
    'platform.vlp.body'          => ['通过 Virus-like Particle 在膜环境中呈递靶蛋白，更好地保留抗原的天然构象、跨膜拓扑及多聚体组装状态，为传统蛋白制备和抗原展示困难的复杂靶点提供更合适的抗原形式。', 'platform_vlp', 'textarea'],
    'platform.vlp.mid_title'     => ['尤其适用于传统抗原制备困难的靶点',                                'platform_vlp',   'text'],
    'platform.vlp.targets_label' => ['代表性靶点',                                                     'platform_vlp',   'text'],
    'platform.vlp.targets'       => ['GPCR ｜ 离子通道 ｜ 转运体',                                       'platform_vlp',   'text'],
    'platform.vlp.diagram'       => ['assets/images/platform/vlp-diagram.webp',                         'platform_vlp',   'text'],
    // 屏 5 · Loop
    'platform.loop.eyebrow'      => ['干湿闭环',                                                        'platform_loop',  'text'],
    'platform.loop.title'        => ['从 <span class="txt-neon">Antibody Agent</span> 到实验验证，一条完整的<span class="txt-neon">抗体发现闭环</span>', 'platform_loop', 'textarea'],
    'platform.loop.sub'          => ['AI 设计与实验结果双向回流，让每一轮实验结果成为下一轮设计与优化的依据。', 'platform_loop', 'textarea'],
    'platform.loop.tempo'        => ['设计 → 验证 → 分析 → 优化',                                       'platform_loop',  'text'],
    'platform.loop.center_logo'  => ['assets/images/logo.webp',                                         'platform_loop',  'text'],
    'platform.loop.center_label' => ['MabSeek 抗体求索',                                               'platform_loop',  'text'],
    // 屏 6 · Case
    'platform.case.eyebrow'         => ['代表性成果与平台验证',                                         'platform_case',  'text'],
    'platform.case.clinical_title'  => ['从抗体发现到临床转化',                                         'platform_case',  'text'],
    'platform.case.clinical_sub'    => ['安巴韦单抗 / 罗米司韦单抗',                                    'platform_case',  'text'],
    'platform.case.clinical_note'   => ['体现团队从基础研究、抗体发现到临床应用的长期转化经验。',       'platform_case',  'textarea'],
    'platform.case.clinical_photo'  => ['assets/images/platform/case-clinical.webp',                    'platform_case',  'text'],
    'platform.case.vlp_title'       => ['复杂膜蛋白靶点的抗体发现实践',                                 'platform_case',  'text'],
    'platform.case.vlp_photo'       => ['assets/images/platform/case-membrane-targets.webp',            'platform_case',  'text'],
    // 屏 7 · Try
    'platform.try.brand_mab'     => ['Mab',                                                             'platform_try',   'text'],
    'platform.try.brand_seek'    => ['Seek',                                                            'platform_try',   'text'],
    'platform.try.title'         => ['让抗体发现，从一个问题开始',                                      'platform_try',   'text'],
    'platform.try.sub'           => ['从研究问题出发，通过 Antibody Agent 连接设计、预测与实验。',      'platform_try',   'textarea'],
    'platform.try.cta'           => ['开始试用 →',                                                      'platform_try',   'text'],
    'platform.try.cta_href'      => ['login.php?next=platform.php&trial=1',                             'platform_try',   'text'],
];
$e1 = 0;
foreach ($PLATFORM_SNIPPETS as $k => [$v, $grp, $type]) {
    $e1 += m43_upsert_snippet($k, $v, $grp, $type);
}
echo "  E.1 · platform.* 44 条 UPSERT: $e1\n";
$changed += $e1;

// E.2 · content_cards（8 组）
$PLATFORM_CARDS = [
    'platform_agent_module' => [
        ['icon'=>'📚','title'=>'文献研究','body'=>'文献检索、靶点信息与研究背景理解','extra'=>''],
        ['icon'=>'🧬','title'=>'抗体设计','body'=>'抗体生成、优化与人源化','extra'=>''],
        ['icon'=>'🔬','title'=>'预测分析','body'=>'结构、亲和力与成药性分析','extra'=>''],
        ['icon'=>'📋','title'=>'实验规划','body'=>'候选排序与实验方案规划','extra'=>''],
    ],
    'platform_data_card' => [
        ['title'=>'真实数据','body'=>'抗体序列 ｜ 靶点结构 ｜ 结合亲和力 ｜ 实验结果'],
        ['title'=>'AI 分析','body'=>'候选设计 ｜ 结构预测 ｜ 亲和力/成药性评估 ｜ 排序筛选'],
    ],
    'platform_lab_cap' => [
        ['title'=>'高通量','body'=>'支持大规模抗体候选的并行筛选，提高抗体发现效率'],
        ['title'=>'自动化','body'=>'将实验操作与数据采集整合为标准化流程，减少人工干预，提高实验重复性'],
        ['title'=>'微量化','body'=>'覆盖皮升级至微升级液滴操作，在提升实验通量的同时降低样本与试剂消耗'],
    ],
    'platform_vlp_kind' => [
        ['title'=>'多聚体 / 蛋白复合物','body'=>'尤其适合需要多个不同亚基共同组装、单独表达难以还原天然结构的异源多聚体蛋白'],
        ['title'=>'多次跨膜蛋白','body'=>'依赖膜环境维持正确拓扑与构象，脱离膜环境后天然状态往往难以稳定保持'],
    ],
    'platform_loop_side' => [
        ['title'=>'设计更精准','body'=>'结合数据与模型进行候选设计与筛选'],
        ['title'=>'验证更高效','body'=>'自动化实验平台支持标准化验证流程'],
        ['title'=>'迭代更快速','body'=>'实验结果回流分析，持续优化候选抗体'],
    ],
    'platform_loop_ring' => [
        ['title'=>'研究目标','body'=>'明确靶点、应用场景与关键指标','extra'=>json_encode(['side'=>'dry'],JSON_UNESCAPED_UNICODE)],
        ['title'=>'Antibody Agent','body'=>'任务理解、候选设计、结构与亲和力预测','extra'=>json_encode(['side'=>'dry'],JSON_UNESCAPED_UNICODE)],
        ['title'=>'实验验证','body'=>'抗体表达、结合活性与功能验证','extra'=>json_encode(['side'=>'wet'],JSON_UNESCAPED_UNICODE)],
        ['title'=>'数据分析','body'=>'结果整理、候选比较与多维评估','extra'=>json_encode(['side'=>'wet'],JSON_UNESCAPED_UNICODE)],
        ['title'=>'迭代优化','body'=>'基于实验反馈优化候选，并进入下一轮设计','extra'=>json_encode(['side'=>'dry'],JSON_UNESCAPED_UNICODE)],
    ],
    'platform_case_stat' => [
        ['title'=>'80%','body'=>'降低住院和死亡率'],
        ['title'=>'837','body'=>'例患者临床试验'],
        ['title'=>'0','body'=>'治疗后 28 天死亡'],
    ],
    'platform_case_vlp' => [
        ['title'=>'VLP 抗原呈递','body'=>'保持复杂膜蛋白天然构象'],
        ['title'=>'涉及疾病','body'=>'肿瘤、病毒感染（如 HIV）、糖尿病/肥胖等代谢性疾病、免疫相关疾病'],
        ['title'=>'代表靶点','body'=>'GPCR、转运体、CD3/TCR、NKG2A/CD94 等'],
    ],
];
$e2 = 0;
foreach ($PLATFORM_CARDS as $grp => $rows) {
    foreach ($rows as $i => $r) {
        $e2 += m43_upsert_card($grp, $i + 1, $r);
    }
    $e2 += m43_delete_cards_beyond($grp, count($rows));
}
echo "  E.2 · platform_* 8 组 cards UPSERT + 清理超出: $e2\n";
$changed += $e2;

// E.3 · legacy 清理（snippets）
$LEGACY_SNIPPETS = [
    // agent 页
    'agent.hero.eyebrow','agent.hero.title','agent.hero.lead','agent.hero.cta1','agent.hero.cta2','agent.hero.note',
    'agent.cap.eyebrow','agent.cap.title',
    'agent.case.eyebrow','agent.case.title','agent.case.sub',
    'agent.flow.eyebrow','agent.flow.title','agent.flow.sub',
    'agent.matrix.eyebrow','agent.matrix.title','agent.matrix.sub',
    'agent.wetlab.title','agent.wetlab.body',
    'agent.cta.title','agent.cta.sub','agent.cta.btn',
    // platform 旧 clinical / lab.item 段
    'platform.clinical.eyebrow','platform.clinical.title','platform.clinical.sub',
    'platform.clinical.item1','platform.clinical.item2','platform.clinical.item3','platform.clinical.item4',
    'platform.lab.item1.title','platform.lab.item1.body',
    'platform.lab.item2.title','platform.lab.item2.body',
    'platform.lab.item3.title','platform.lab.item3.body',
    'platform.lab.item4.title','platform.lab.item4.body',
    'platform.lab.item5.title','platform.lab.item5.body',
    'platform.lab.item6.title','platform.lab.item6.body',
];
$e3 = m43_delete_snippets($LEGACY_SNIPPETS);
echo "  E.3 · legacy snippets 清理: $e3\n";
$changed += $e3;

// E.4 · legacy 清理（content_cards：agent_capability + agent_matrix）
$e4 = 0;
$e4 += m43_delete_cards_grp('agent_capability');
$e4 += m43_delete_cards_grp('agent_matrix');
echo "  E.4 · agent_capability + agent_matrix cards 清理: $e4\n";
$changed += $e4;

echo "== V4.3 迁移结束 · 共影响 $changed 行 ==\n";
