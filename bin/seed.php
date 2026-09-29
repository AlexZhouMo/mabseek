<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap.php';

echo "Seeding MabSeek DB...\n";
$pdo = db();

// ── 1) 初始管理员（幂等：已存在不动，保护改过的密码）──
$has = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ((int)$has === 0) {
    $now = iso_now();
    $pdo->prepare('INSERT INTO users(username,password_hash,must_change_password,role,status,created_at,updated_at) VALUES(?,?,1,?,?,?,?)')
        ->execute([SEED_ADMIN_USER, password_hash(SEED_ADMIN_PASS, PASSWORD_ARGON2ID), 'admin', 'active', $now, $now]);
    echo "  + admin created\n";
} else {
    echo "  = users exist, skip\n";
}

/** 集合幂等插入：按 (sort) 顺序，若表已有等量行则跳过整表 */
function seed_collection(string $table, array $rows): void {
    $c = new Collection($table);
    if ($c->count() >= count($rows)) { echo "  = $table has data, skip\n"; return; }
    foreach ($rows as $r) $c->create($r);
    echo "  + $table: " . count($rows) . " rows\n";
}

/** 分组幂等插入：content_cards 已整表 seed，需按 grp 计数保护 */
function seed_cards_group(string $grp, array $rows): void {
    $q = db()->prepare("SELECT COUNT(*) FROM content_cards WHERE grp = ?");
    $q->execute([$grp]);
    if ((int)$q->fetchColumn() >= count($rows)) { echo "  = content_cards[$grp] present, skip\n"; return; }
    $c = new Collection('content_cards');
    foreach ($rows as $r) { $c->create($r); }
    echo "  + content_cards[$grp]: " . count($rows) . " rows\n";
}

// ── 2) news（about.html #news，5 条，逐字）──
seed_collection('news', [
    ['date_day'=>'08','date_ym'=>'2026·08','category'=>'research','title'=>'MabSeek 平台技术升级：亲和力预测模型精度再提升','summary'=>'最新一轮迭代显著提升虚拟筛选命中率，缩短候选分子验证周期。','image'=>null,'sort'=>1,'published'=>1],
    ['date_day'=>'07','date_ym'=>'2026·07','category'=>'team','title'=>'《疫苗的力量》交互式课程正式上线','summary'=>'元视频 + 三层知识图谱，面向学生、从业者与公众开放基础版。','image'=>null,'sort'=>2,'published'=>1],
    ['date_day'=>'06','date_ym'=>'2026·06','category'=>'research','title'=>'实验室最新研究成果发表于领域顶级期刊','summary'=>'抗体设计与免疫机制相关工作获同行高度评价。','image'=>null,'sort'=>3,'published'=>1],
    ['date_day'=>'05','date_ym'=>'2026·05','category'=>'team','title'=>'组会纪实与学术沙龙：师生共话前沿方向','summary'=>'平台宣传片发布，记录实验室日常科研与交流活动。','image'=>null,'sort'=>4,'published'=>1],
    ['date_day'=>'04','date_ym'=>'2026·04','category'=>'team','title'=>'学生斩获学术竞赛奖项，人才培养成果显著','summary'=>'科普讲座与公益授课走进高校，扩大科学影响力。','image'=>null,'sort'=>5,'published'=>1],
]);

// ── 2b) edu_reviews（教育往期回顾，3 条示例）──
seed_collection('edu_reviews', [
    ['title'=>'《疫苗的力量》公开课回顾','summary'=>'首期公开课全程实录，从免疫基础到抗体机制的系统讲解，反响热烈。','cover'=>'assets/images/forum-adc.webp','body'=>'<p>本期公开课系统讲解了疫苗与免疫的基础知识，涵盖免疫系统识别抗原、抗体中和机制、疫苗免疫应答等核心内容。现场提问踊跃，课后反馈良好。</p>','sort'=>1,'published'=>1],
    ['title'=>'抗体设计工作坊纪实','summary'=>'从序列设计到亲和力预测的完整流程实践，学员动手体验 AI 辅助虚拟筛选。','cover'=>'assets/images/forum-bioinfo.webp','body'=>'<p>抗体设计工作坊聚焦从序列设计到亲和力预测的完整流程，学员动手实践 AI 辅助的虚拟筛选，深入理解干湿闭环验证的关键环节与成药性评估要点。</p>','sort'=>2,'published'=>1],
    ['title'=>'学术讲座 · AI 驱动的抗体发现','summary'=>'邀请领域专家分享 AI 大模型在抗体发现中的最新进展与实践经验。','cover'=>'assets/images/forum-protocol.webp','body'=>'<p>讲座回顾了近年 AI 在抗体发现领域的突破，结合实验室实际案例探讨了大模型辅助设计的落地路径。</p>','sort'=>3,'published'=>1],
]);

// ── 3) team_members（about.html 双负责人，逐字）──
seed_collection('team_members', [
    ['name'=>'张林琦','affiliation'=>'清华大学 · 实验室负责人','direction'=>'长期从事抗体工程、疫苗研发与病毒免疫研究，为 MabSeek 奠定抗体科学与湿实验验证的专业根基。','role_label'=>'科学负责人 · 抗体 / 疫苗 / 病毒免疫','role_type'=>'science','avatar_char'=>'张','avatar_img'=>'assets/images/team/zhang.webp','sort'=>1,'published'=>1],
]);

// ── 4) partners（index.html logo 墙，逐字）──
seed_collection('partners', [
    ['name'=>'清华大学','mark'=>'清','sub'=>'','logo_image'=>'assets/images/partners/p01.webp','demo'=>'','sort'=>1,'published'=>1],
    ['name'=>'北京大学','mark'=>'北','sub'=>'','logo_image'=>'assets/images/partners/p02.webp','demo'=>'','sort'=>2,'published'=>1],
    ['name'=>'PRA 国际联盟','mark'=>'PRA','sub'=>'','logo_image'=>'assets/images/partners/p03.webp','demo'=>'','sort'=>3,'published'=>1],
    ['name'=>'Universitas Indonesia','mark'=>'UI','sub'=>'印度尼西亚','logo_image'=>'assets/images/partners/p04.webp','demo'=>'','sort'=>4,'published'=>1],
    ['name'=>'北京清华长庚医院','mark'=>'长庚','sub'=>'','logo_image'=>'assets/images/partners/p05.webp','demo'=>'','sort'=>5,'published'=>1],
    ['name'=>'中国医学科学院血液学研究所·血液病医院','mark'=>'血研','sub'=>'','logo_image'=>'assets/images/partners/p06.webp','demo'=>'','sort'=>6,'published'=>1],
    ['name'=>'中国医学科学院北京协和医学院','mark'=>'协和','sub'=>'','logo_image'=>'assets/images/partners/p07.webp','demo'=>'','sort'=>7,'published'=>1],
    ['name'=>'BRIN','mark'=>'BRIN','sub'=>'印度尼西亚','logo_image'=>'assets/images/partners/p08.webp','demo'=>'','sort'=>8,'published'=>1],
    ['name'=>'LPDP','mark'=>'LPDP','sub'=>'印度尼西亚','logo_image'=>'assets/images/partners/p09.webp','demo'=>'','sort'=>9,'published'=>1],
    ['name'=>'北京友谊医院','mark'=>'友谊','sub'=>'','logo_image'=>'assets/images/partners/p10.webp','demo'=>'','sort'=>10,'published'=>1],
    ['name'=>'沃森生物 WALVAX','mark'=>'WALVAX','sub'=>'','logo_image'=>'assets/images/partners/p11.webp','demo'=>'','sort'=>11,'published'=>1],
    ['name'=>'天木生物 TMAXTREE','mark'=>'TMAX','sub'=>'','logo_image'=>'assets/images/partners/p12.webp','demo'=>'','sort'=>12,'published'=>1],
    ['name'=>'BADAN POM','mark'=>'POM','sub'=>'印度尼西亚','logo_image'=>'assets/images/partners/p13.webp','demo'=>'','sort'=>13,'published'=>1],
    ['name'=>'思路迪医药 3D Medicines','mark'=>'3D','sub'=>'','logo_image'=>'assets/images/partners/p14.webp','demo'=>'','sort'=>14,'published'=>1],
    ['name'=>'revvity','mark'=>'REV','sub'=>'','logo_image'=>'assets/images/partners/p15.webp','demo'=>'','sort'=>15,'published'=>1],
    ['name'=>'东富龙 Tofflon','mark'=>'TFL','sub'=>'','logo_image'=>'assets/images/partners/p16.webp','demo'=>'','sort'=>16,'published'=>1],
    ['name'=>'Kemenkes','mark'=>'KMK','sub'=>'印度尼西亚','logo_image'=>'assets/images/partners/p17.webp','demo'=>'','sort'=>17,'published'=>1],
]);

// ── 7) content_cards（各分组，逐字）──
// 7a. 首页四大痛点（index.html .pain-grid）
seed_collection('content_cards', array_merge([
    ['grp'=>'home_pain','icon'=>'','title'=>'难成药靶点','body'=>'GPCR、离子通道等跨膜靶点结构复杂、表达量低，抗原极难制备。','extra'=>json_encode(['fix'=>'→ VLP 技术保持天然构象，结合湿实验平台高效分离'], JSON_UNESCAPED_UNICODE),'sort'=>1,'published'=>1],
    ['grp'=>'home_pain','icon'=>'','title'=>'AI 模型数据','body'=>'公开库多为正样本，缺负样本与完整物理信息，模型"垃圾进垃圾出"。','extra'=>json_encode(['fix'=>'→ 正/负结合抗体序列构建完整数据库，提供精准训练基准'], JSON_UNESCAPED_UNICODE),'sort'=>2,'published'=>1],
    ['grp'=>'home_pain','icon'=>'','title'=>'干湿结合','body'=>'计算与实验割裂，候选分子从设计到验证需数月，试错成本高昂。','extra'=>json_encode(['fix'=>'→ 干湿闭环，以显著提升命中率、缩短验证周期为目标（示意）'], JSON_UNESCAPED_UNICODE),'sort'=>3,'published'=>1],
    ['grp'=>'home_pain','icon'=>'','title'=>'成药性评估','body'=>'成药性风险高，后期易聚集、免疫原性高，临床前废弃率高。','extra'=>json_encode(['fix'=>'→ 成药性评估 Agent，在设计早期多维评估、从源头规避风险'], JSON_UNESCAPED_UNICODE),'sort'=>4,'published'=>1],
    // 7b. about 四张成果卡（about.html #team .grid-2）
    ['grp'=>'about_achievement','icon'=>'📄','title'=>'学术论文成果','body'=>'系列研究成果发表于领域顶级期刊，内化沉淀入平台知识库，支撑 Antibody Agent 的文献问答能力。','extra'=>'','sort'=>5,'published'=>1],
    ['grp'=>'about_achievement','icon'=>'🧾','title'=>'专利成果汇总','body'=>'抗体设计算法与湿实验方法相关专利，构成平台核心技术壁垒。','extra'=>'','sort'=>6,'published'=>1],
    ['grp'=>'about_achievement','icon'=>'🏆','title'=>'科研奖项与荣誉','body'=>'承担国家级科研项目，获多项学术与产业化荣誉。','extra'=>'','sort'=>7,'published'=>1],
    ['grp'=>'about_achievement','icon'=>'👩‍🔬','title'=>'核心团队','body'=>'由博士研究团队与湿实验平台工程师组成，产学研深度融合。','extra'=>'','sort'=>8,'published'=>1],
]));

// ── 8) snippets（零散文案，逐字）──
$S = [
    // 全站页脚（footer.php 共用）
    ['footer.brand.tagline','让抗体发现从反复试错变成精准编程。','common','页脚品牌简介','textarea'],
    ['footer.copyright','© 2026 MabSeek 抗体求索 · 清华大学医学院实验室. 保留所有权利。','common','页脚版权行','text'],
    ['contact.email','m13673741782@163.com','common','联系邮箱','text'],
    ['contact.org','清华大学医学院','common','联系单位','text'],

    // index.html
    ['home.hero.eyebrow','','home','首页 Hero 眉题（留空则不显示）','text'],
    ['home.hero.title','让抗体发现<br>从"反复试错"变成 <span class="txt-neon">"精准编程"</span>','home','首页 Hero 标题(含标记)','textarea'],
    ['home.hero.sub','从头设计一支完美结合靶点的抗体——AI 生成 + 干湿闭环验证，一站直达。','home','首页 Hero 副标题','textarea'],
    ['home.hero.cta','免费试用 MabSeek 平台 →','home','首页 Hero 按钮','text'],
    ['home.can.eyebrow','我们能做什么','home','能做什么 眉题','text'],
    ['home.can.title','把靶点交给 AI，<span class="txt-green">从设计到验证一条闭环</span>','home','能做什么 标题(含标记)','textarea'],
    ['home.can.sub','AI 从头序列设计 · 结构与亲和力预测 · 干湿闭环一站交付。过去分散在多个团队、数月起步的流程，被压缩为连续、可追溯、可下单的闭环。','home','能做什么 说明','textarea'],
    ['home.can.link','进入技术平台，了解完整技术优势 →','home','能做什么 链接','text'],
    ['home.pain.eyebrow','痛点共鸣 · 针对性解法','home','痛点 眉题','text'],
    ['home.pain.title','抗体发现行业的四大痛点，<span class="txt-neon">各有解法</span>','home','痛点 标题(含标记)','textarea'],
    ['home.pain.cta','查看实际案例 →','home','痛点 按钮','text'],
    ['home.news.eyebrow','我们的近况','home','近况 眉题','text'],
    ['home.news.title','新闻 · 活动','home','近况 标题','text'],
    ['home.news.link','进入「了解我们」查看全部近况 →','home','近况 链接','text'],
    ['home.contact.eyebrow','意见反馈·寻求合作·加入我们','home','联系 眉题','text'],
    ['home.contact.title','与顶尖机构<span class="txt-neon">共建生态</span>','home','联系 标题(含标记)','textarea'],
    ['home.contact.h3','有建议或遇到了问题？欢迎告诉我们','home','联系 小标题','text'],
    // 首页近况横滚卡（结构固定，文字可编辑）——沿用 news 集合渲染？否：首页卡片文案与 about 不同，用 snippet
    // ── V4.3 · 平台页 7 屏（Hero/Agent/Data/Lab/VLP/Loop/Case/Try）──
    // 屏 1 · Hero
    ['platform.hero.eyebrow',    'MabSeek Platform',                                                'platform_hero',  '平台 Hero·眉题', 'text'],
    ['platform.hero.title',      '从科学问题到<span class="txt-neon">实验验证</span>抗体',           'platform_hero',  '平台 Hero·标题(含标记)', 'textarea'],
    ['platform.hero.lead',       '由 Antibody Agent 驱动，整合数据、算法与自动化实验，让抗体发现更高效、更可靠。', 'platform_hero', '平台 Hero·说明', 'textarea'],
    ['platform.hero.cta1',       '了解平台',                                                        'platform_hero',  '平台 Hero·主 CTA', 'text'],
    ['platform.hero.cta1_href',  '#agent',                                                          'platform_hero',  '平台 Hero·主 CTA 链接', 'text'],
    ['platform.hero.cta2',       '开始试用',                                                        'platform_hero',  '平台 Hero·副 CTA', 'text'],
    ['platform.hero.cta2_href',  'login.php?next=platform.php&trial=1',                             'platform_hero',  '平台 Hero·副 CTA 链接', 'text'],
    ['platform.hero.photo',      'assets/images/platform/hero-auto-detail.webp',                    'platform_hero',  '平台 Hero·实拍图路径', 'text'],
    // 屏 2 · Agent
    ['platform.agent.eyebrow',   'Antibody Agent',                                                  'platform_agent', '智能体·眉题', 'text'],
    ['platform.agent.title',     '从研究问题出发，连接<span class="txt-neon">设计、预测与实验</span>', 'platform_agent', '智能体·标题(含标记)', 'textarea'],
    ['platform.agent.lead',      'Antibody Agent 理解研究目标，将任务拆解为可执行的研究步骤，并连接文献检索、抗体设计、预测分析与实验验证。', 'platform_agent', '智能体·说明', 'textarea'],
    ['platform.agent.screenshot','assets/images/platform/agent-screen.webp',                        'platform_agent', '智能体·截图路径', 'text'],
    // 屏 3 · Data
    ['platform.data.eyebrow',    '真实数据驱动',                                                    'platform_data',  '数据驱动·眉题', 'text'],
    ['platform.data.title',      '真实数据驱动<span class="txt-neon">抗体设计与预测</span>',         'platform_data',  '数据驱动·标题(含标记)', 'textarea'],
    ['platform.data.sub',        '整合抗体序列、靶点、结构与实验结果，为候选设计、筛选和优化提供依据。', 'platform_data', '数据驱动·副标题', 'textarea'],
    // 屏 4a · Lab
    ['platform.lab.eyebrow',     '全流程抗体发现实验平台',                                          'platform_lab',   '实验平台·眉题', 'text'],
    ['platform.lab.title',       'VLP 天然构象呈递 × <span class="txt-neon">高通量自动化筛选</span>', 'platform_lab',   '实验平台·标题(含标记)', 'textarea'],
    ['platform.lab.mf_title',    '微流控液滴技术平台',                                              'platform_lab',   '实验平台·微流控小标', 'text'],
    ['platform.lab.hero_photo',  'assets/images/platform/lab-auto-room.webp',                       'platform_lab',   '实验平台·全景图路径', 'text'],
    // 屏 4b · VLP
    ['platform.vlp.title',       'VLP 钓饵技术',                                                    'platform_vlp',   'VLP·标题', 'text'],
    ['platform.vlp.lead',        '让复杂抗原的展示更接近天然状态',                                  'platform_vlp',   'VLP·副题', 'text'],
    ['platform.vlp.body',        '通过 Virus-like Particle 在膜环境中呈递靶蛋白，更好地保留抗原的天然构象、跨膜拓扑及多聚体组装状态，为传统蛋白制备和抗原展示困难的复杂靶点提供更合适的抗原形式。', 'platform_vlp', 'VLP·正文', 'textarea'],
    ['platform.vlp.mid_title',   '尤其适用于传统抗原制备困难的靶点',                                'platform_vlp',   'VLP·中标', 'text'],
    ['platform.vlp.targets_label','代表性靶点',                                                     'platform_vlp',   'VLP·靶点标签', 'text'],
    ['platform.vlp.targets',     'GPCR ｜ 离子通道 ｜ 转运体',                                       'platform_vlp',   'VLP·代表性靶点', 'text'],
    ['platform.vlp.diagram',     'assets/images/platform/vlp-diagram.webp',                         'platform_vlp',   'VLP·示意图路径', 'text'],
    // 屏 5 · Loop 干湿闭环
    ['platform.loop.eyebrow',    '干湿闭环',                                                        'platform_loop',  '干湿闭环·眉题', 'text'],
    ['platform.loop.title',      '从 <span class="txt-neon">Antibody Agent</span> 到实验验证，一条完整的<span class="txt-neon">抗体发现闭环</span>', 'platform_loop', '干湿闭环·标题(含标记)', 'textarea'],
    ['platform.loop.sub',        'AI 设计与实验结果双向回流，让每一轮实验结果成为下一轮设计与优化的依据。', 'platform_loop', '干湿闭环·副标题', 'textarea'],
    ['platform.loop.tempo',      '设计 → 验证 → 分析 → 优化',                                       'platform_loop',  '干湿闭环·节奏', 'text'],
    ['platform.loop.center_logo','assets/images/logo.webp',                                         'platform_loop',  '干湿闭环·中心 logo 路径', 'text'],
    ['platform.loop.center_label','MabSeek 抗体求索',                                               'platform_loop',  '干湿闭环·中心标签', 'text'],
    // 屏 6 · Case
    ['platform.case.eyebrow',       '代表性成果与平台验证',                                         'platform_case',  '成果·眉题', 'text'],
    ['platform.case.clinical_title','从抗体发现到临床转化',                                         'platform_case',  '临床成果·标题', 'text'],
    ['platform.case.clinical_sub',  '安巴韦单抗 / 罗米司韦单抗',                                    'platform_case',  '临床成果·副标题', 'text'],
    ['platform.case.clinical_note', '体现团队从基础研究、抗体发现到临床应用的长期转化经验。',       'platform_case',  '临床成果·注解', 'textarea'],
    ['platform.case.clinical_photo','assets/images/platform/case-clinical.webp',                    'platform_case',  '临床成果·图片路径', 'text'],
    ['platform.case.vlp_title',     '复杂膜蛋白靶点的抗体发现实践',                                 'platform_case',  '膜蛋白实践·标题', 'text'],
    ['platform.case.vlp_photo',     'assets/images/platform/case-membrane-targets.webp',            'platform_case',  '膜蛋白实践·图片路径', 'text'],
    // 屏 7 · Try
    ['platform.try.brand_mab',   'Mab',                                                             'platform_try',   'Try·品牌白色部分', 'text'],
    ['platform.try.brand_seek',  'Seek',                                                            'platform_try',   'Try·品牌绿色部分', 'text'],
    ['platform.try.title',       '让抗体发现，从一个问题开始',                                      'platform_try',   'Try·标题', 'text'],
    ['platform.try.sub',         '从研究问题出发，通过 Antibody Agent 连接设计、预测与实验。',      'platform_try',   'Try·副标题', 'textarea'],
    ['platform.try.cta',         '开始试用 →',                                                      'platform_try',   'Try·CTA 文案', 'text'],
    ['platform.try.cta_href',    'login.php?next=platform.php&trial=1',                             'platform_try',   'Try·CTA 链接', 'text'],
];
foreach ($S as $s) Snippets::seed($s[0], $s[1], $s[2], $s[3], $s[4] ?? 'text');
echo "  + snippets(home/common): " . count($S) . " seeded (idempotent)\n";

// ── technology 页 snippets（逐字，取自 technology.html）──
$S_tech = [
    ['tech.hero.eyebrow','技术平台','tech','技术页 Hero 眉题','text'],
    ['tech.hero.title','从数据到验证，<span class="txt-neon">一条自主可控的抗体发现闭环</span>','tech','技术页 Hero 标题(含标记)','textarea'],
    ['tech.hero.sub','AI 智能中枢编排干实验设计与湿实验验证，端到端可追溯——把分散数月的流程，压缩为连续、可下单的闭环。','tech','技术页 Hero 副标题','textarea'],
    ['tech.hero.cta','免费试用 Antibody Agent →','tech','技术页 Hero 按钮','text'],
    ['tech.arch.eyebrow','整体技术架构','tech','架构区 眉题','text'],
    ['tech.arch.title','以 <span class="txt-neon">AI 智能中枢</span> 编排的干湿闭环','tech','架构区 标题(含标记)','textarea'],
    ['tech.arch.sub','把鼠标移到任一环节查看详情（触屏点击展开）。','tech','架构区 说明','textarea'],
    ['tech.arch.cta','进入 Antibody Agent →','tech','架构区 按钮','text'],
    ['tech.case.eyebrow','经典案例','tech','案例区 眉题','text'],
    ['tech.case.title','安巴韦单抗 / 罗米司韦单抗','tech','案例区 标题','text'],
    ['tech.case.sub','中国首个自主知识产权新冠中和抗体，已获批上市。','tech','案例区 说明','textarea'],
    ['tech.case.cta','用 Antibody Agent 开始你的项目 →','tech','案例区 按钮','text'],
    ['tech.p1.eyebrow','Data to train · 强调数据','tech','支柱1 眉题','text'],
    ['tech.p1.title','<span class="txt-green">Data</span> to train','tech','支柱1 标题(含标记)','textarea'],
    ['tech.p1.sub','模型的上限由数据决定。我们不止收集正样本，更补齐负样本与完整物理信息，从源头避免"垃圾进垃圾出"。','tech','支柱1 说明','textarea'],
    ['tech.p1.point1','正 / 负结合抗体序列数据库','tech','支柱1 要点1','text'],
    ['tech.p1.point2','完整物理信息，而非只有正样本','tech','支柱1 要点2','text'],
    ['tech.p1.point3','为 AI 提供精准训练基准','tech','支柱1 要点3','text'],
    ['tech.p1.link','进入 Antibody Agent →','tech','支柱1 链接','text'],
    ['tech.p2.eyebrow','AI for science · 强调算法','tech','支柱2 眉题','text'],
    ['tech.p2.title','<span class="txt-neon">AI</span> for science','tech','支柱2 标题(含标记)','textarea'],
    ['tech.p2.sub','以领域模型完成从头设计与精准预测，让抗体从"反复试错"变成"精准编程"。','tech','支柱2 说明','textarea'],
    ['tech.p2.point1','从头抗体序列设计','tech','支柱2 要点1','text'],
    ['tech.p2.point2','结构与亲和力预测','tech','支柱2 要点2','text'],
    ['tech.p2.point3','成药性评估 Agent，早期规避风险','tech','支柱2 要点3','text'],
    ['tech.p2.link','进入 Antibody Agent →','tech','支柱2 链接','text'],
    ['tech.p3.eyebrow','Wet lab to validate · 强调自动化湿实验','tech','支柱3 眉题','text'],
    ['tech.p3.title','<span class="txt-green">Wet lab</span> to validate','tech','支柱3 标题(含标记)','textarea'],
    ['tech.p3.sub','自动化湿实验平台承接 AI 设计，快速验证并把真实数据回流，形成越用越准的闭环。','tech','支柱3 说明','textarea'],
    ['tech.p3.point1','VLP 抗原制备，保持天然构象','tech','支柱3 要点1','text'],
    ['tech.p3.point2','高通量分离与表征','tech','支柱3 要点2','text'],
    ['tech.p3.point3','干湿闭环，数据回流训练','tech','支柱3 要点3','text'],
    ['tech.p3.link','进入 Antibody Agent →','tech','支柱3 链接','text'],
];
foreach ($S_tech as $s) Snippets::seed($s[0], $s[1], $s[2], $s[3], $s[4] ?? 'text');
echo "  + snippets(tech): " . count($S_tech) . " seeded (idempotent)\n";

// ── agent 页 snippets：V4.3 已合并入 platform.php，agent.php 变 301 stub，历史 snippet 由 migrate-v43 清理 ──

// ── education 页 snippets（逐字，取自 education.html）──
$S_edu = [
    ['edu.hero.breadcrumb','教育','education','教育页 面包屑','text'],
    ['edu.hero.eyebrow','新型教育科研范式','education','教育页 Hero 眉题','text'],
    ['edu.hero.title','让知识<span class="grad-text">系统沉淀、清晰可循</span>','education','教育页 Hero 标题(含标记)','textarea'],
    ['edu.hero.lead','元视频拆解 + AI 答疑，系统沉淀课程知识，随点随学。','education','教育页 Hero 说明','textarea'],
    ['edu.banner.tag','精品课程','education','课程 Banner 标签','text'],
    ['edu.banner.title','《疫苗的力量》','education','课程 Banner 标题','text'],
    ['edu.banner.sub','元视频课程体系 · 精准适配学生、疫苗研发从业者、接种人群与新手父母','education','课程 Banner 副标题','textarea'],
    // V4.3 · 张林琦访谈《疫苗的力量》
    ['edu.talk.eyebrow','与教授对话','education','访谈 眉题','text'],
    ['edu.talk.title','《疫苗的力量》与张林琦教授对话','education','访谈 标题','text'],
    ['edu.talk.body','作为《疫苗的力量》的开课人和主讲人，张林琦教授分享课程开设的初衷、自己的专业选择，以及对青年学生的期待与寄语。','education','访谈 正文','textarea'],
    ['edu.talk.kw1','课程缘起','education','访谈 关键词1','text'],
    ['edu.talk.kw2','专业选择','education','访谈 关键词2','text'],
    ['edu.talk.kw3','课程期待','education','访谈 关键词3','text'],
    ['edu.talk.kw4','青年寄语','education','访谈 关键词4','text'],
    ['edu.talk.speaker','张林琦教授｜《疫苗的力量》开课人、主讲人','education','访谈 讲者标注','text'],
    ['edu.talk.video_src','assets/uploads/videos/zhang-linqi-vaccine-talk.mp4','education','访谈 视频路径（生产手动 scp 上传）','text'],
    ['edu.talk.poster','assets/images/edu-talk-poster.webp','education','访谈 海报路径','text'],
    ['edu.course.intro',"本课程立足清华科研一线，以真实案例为切入点，深度解析疫苗的历史演变、科学逻辑与全球治理。\n课程融合理论教学、案例剖析、企业调研与实验实践，旨在点亮抗体与疫苗研发兴趣，培养兼具家国情怀与国际视野的复合型领军人才。",'education','课程简介 正文（换行分段）','textarea'],
    ['edu.teachers.intro',"课程组将邀请疫苗研发一线的专家教授参与授课，介绍其所在专业领域中真实的疫苗研发历程和真实的科研经历故事。\n同时授课教师还包括从事疫苗监管、政策制定和疫苗治理的专家，帮助学生充分了解疫苗如何从实验室走向人群普遍接种的艰苦心路历程，以及科学家们在利用疫苗实现人类健康这一目标上的矢志追求。",'education','主讲团队 正文（换行分段）','textarea'],
];
foreach ($S_edu as $s) Snippets::seed($s[0], $s[1], $s[2], $s[3], $s[4] ?? 'text');
echo "  + snippets(education): " . count($S_edu) . " seeded (idempotent)\n";

// ── forum 页 snippets（逐字，取自 forum.html；注意 5–10 为 EN-DASH）──
$S_forum = [
    ['forum.hero.breadcrumb','论坛','forum','论坛页 面包屑','text'],
    ['forum.hero.eyebrow','抗体领域高质量交流社区','forum','论坛页 Hero 眉题','text'],
    ['forum.hero.title','提问有人答，分享有人看<br><span class="grad-text">高手愿意来，新手能成长</span>','forum','论坛页 Hero 标题(含标记)','textarea'],
    ['forum.hero.lead','小红书式内容流，降低发帖门槛，用算法推荐与话题标签替代传统板块。这里有硬核干货、隐藏高人、前沿话题，也有互助求助的氛围。','forum','论坛页 Hero 说明','textarea'],
    ['forum.search.placeholder','用一句话描述你想找的，比如「ELISA 背景高怎么排查」','forum','搜索框 占位符','text'],
    ['forum.search.label','试试：','forum','搜索框 提示标签','text'],
    ['forum.search.btn','搜索','forum','搜索框 按钮','text'],
    ['forum.search.chip1','纳米抗体适合 AI 设计吗','forum','搜索 示例1','text'],
    ['forum.search.chip2','双抗结构设计有哪些坑','forum','搜索 示例2','text'],
    ['forum.search.chip3','怎么排查细胞培养污染','forum','搜索 示例3','text'],
];
foreach ($S_forum as $s) Snippets::seed($s[0], $s[1], $s[2], $s[3], $s[4] ?? 'text');
echo "  + snippets(forum): " . count($S_forum) . " seeded (idempotent)\n";

// ── about 页 snippets（逐字，取自 about.html）──
$S_about = [
    // Hero
    ['about.hero.breadcrumb','了解我们','about','了解我们页 面包屑','text'],
    ['about.hero.eyebrow','实验室概况与成果','about','了解我们页 Hero 眉题','text'],
    ['about.hero.title','以顶尖科研成果<span class="grad-text">支撑高质量育人</span>','about','了解我们页 Hero 标题(含标记)','textarea'],
    ['about.hero.lead','清华大学医学院 MabSeek 实验室，聚焦抗体工程、疫苗研发与病毒免疫，打造 AI 驱动的抗体发现平台与新型科学教育范式。','about','了解我们页 Hero 说明','textarea'],
    ['about.hero.tag_team','核心团队','about','Hero 标签 核心团队','text'],
    ['about.hero.tag_platform','MabSeek 平台','about','Hero 标签 MabSeek 平台','text'],
    ['about.hero.tag_news','新闻活动','about','Hero 标签 新闻活动','text'],
    ['about.hero.tag_intl','国际合作','about','Hero 标签 国际合作','text'],
    // 实验室与核心团队
    ['about.team.eyebrow','1 · 实验室与核心团队','about','核心团队 眉题','text'],
    ['about.team.title','实验室与核心团队','about','核心团队 标题','text'],
    ['about.team.sub','MabSeek 依托抗体工程、疫苗研发与病毒免疫的深厚科学积累，结合 AI 与大模型能力，打通从抗体设计到湿实验验证的完整闭环。','about','核心团队 说明','textarea'],
    ['about.team.disclaimer','注：以上为门户展示模板，详细简历与成果列表参考清华大学医学院官网张林琦主页，正式上线时同步更新。','about','核心团队 免责说明','textarea'],
    ['about.zhang.role1','清华大学基础医学院教授 博士生导师','about','张老师 职务1','text'],
    ['about.zhang.role2','清华大学艾滋病综合研究中心主任','about','张老师 职务2','text'],
    ['about.zhang.field','致力于病毒感染和保护性免疫机制的研究，免疫和基因治疗临床应用研发。在疫苗的设计与优化、保护性抗体反应机制及评估等方面取得关键突破，多个抗体和疫苗已经在国内外进入人体临床试验或批准上市。','about','张老师 研究领域','textarea'],
    ['about.zhang.contrib','2021年12月8日，研发的新冠抗体药物获得国家药品监督管理局批准上市，实现我国新冠药物的零突破。创新型新冠鼻腔疫苗正在开展临床安全性和有效性研究，为预防新冠病毒感染和传播提供全新的手段和候选。持续多年入选感染和免疫学科中国高被引学者。','about','张老师 科学贡献','textarea'],
    ['about.zhang.papers_desc','专注于人类重大病毒性传染病的致病机理，病毒与免疫系统相互作用关系，研发抗病毒药物、抗体和疫苗。','about','论文成果 描述','textarea'],
    ['about.zhang.papers_count','276+','about','论文数量强调','text'],
    ['about.zhang.patents_desc','团队及其合作单位在抗体开发、新型疫苗及生物医药技术等领域的授权发明专利。','about','专利成果 描述','textarea'],
    ['about.zhang.patents_count','34+','about','专利数量强调','text'],
    ['about.loc.cap','清华大学医学院 · 立足北京市国合基地','about','位置展示 说明','text'],
    // MabSeek 平台介绍
    ['about.platform.eyebrow','MabSeek 平台介绍','about','平台介绍 眉题','text'],
    ['about.platform.title','AI 设计 × 湿实验交付，一体化平台','about','平台介绍 标题','text'],
    ['about.platform.body','MabSeek 将抗体发现的干实验（AI 设计、预测、分析）与湿实验（表达纯化、功能验证）整合为一个连续闭环，让科研团队与药企从「一句话需求」直达「可交付结果」。','about','平台介绍 正文','textarea'],
    ['about.platform.li1','Antibody Agent：文献问答 · 序列设计 · 亲和力预测 · 结构分析','about','平台介绍 要点(干)','textarea'],
    ['about.platform.li2','湿实验平台：一键下单表达纯化与功能验证，线下交付','about','平台介绍 要点(湿)','textarea'],
    ['about.platform.li3','数据双向溯源，AI 预测与实验结果持续迭代','about','平台介绍 要点(环)','textarea'],
    ['about.platform.btn','进入 Antibody Agent →','about','平台介绍 按钮','text'],
    // 新闻与活动
    ['about.news.eyebrow','2 · 新闻与活动分享','about','新闻活动 眉题','text'],
    ['about.news.title','实验室动态一览','about','新闻活动 标题','text'],
    ['about.news.chip_all','全部','about','新闻筛选 全部','text'],
    ['about.news.chip_edu','团队动态','about','新闻筛选 团队动态','text'],
    ['about.news.chip_res','研究进展','about','新闻筛选 研究进展','text'],
    ['about.news.chip_daily','产品发布','about','新闻筛选 产品发布','text'],
    ['about.news.more','了解更多 →','about','新闻活动 按钮','text'],
    // 国际科研合作
    ['about.intl.eyebrow','3 · 国际科研合作','about','国际合作 眉题','text'],
    ['about.intl.title','立足<span class="grad-text">北京市国合基地</span>，联通全球','about','国际合作 标题(含标记)','textarea'],
    ['about.intl.sub','以中印尼深度合作为核心专项，联动 PRA 国际大会与各国合作实验室，推动联合科研与公共卫生合作。','about','国际合作 说明','textarea'],
    ['about.intl.cn_id_title','中印尼疫苗与基因组联合研发中心','about','中印尼合作 标题','text'],
    ['about.intl.cn_id_body',"中印尼疫苗与基因组联合研发中心由清华大学牵头，纳入国家科技部对发展中国家科技援助专项。中心构建“政府+高校+产业”协同模式，推动登革热、结核等疫苗联合研发，助力印尼向区域研发生产中心转型，形成“中国技术+本地转化”的南南合作范式。",'about','中印尼合作 正文（换行分段）','textarea'],
    ['about.intl.tl1_title','联合科研','about','时间线1 标题','text'],
    ['about.intl.tl1_body','共建抗体与疫苗研究课题，共享数据与平台能力','about','时间线1 正文','textarea'],
    ['about.intl.tl2_title','人才互访与联合培养','about','时间线2 标题','text'],
    ['about.intl.tl2_body','互派研究人员与研究生，共育国际化科研人才','about','时间线2 正文','textarea'],
    ['about.intl.tl3_title','公共卫生合作','about','时间线3 标题','text'],
    ['about.intl.tl3_body','面向区域传染病防控的联合攻关与科普','about','时间线3 正文','textarea'],
    ['about.intl.pra_title','大流行病研究联盟（PRA）','about','PRA 合作 标题','text'],
    ['about.intl.pra_body',"大流行病研究联盟由清华大学张林琦教授联合钟南山、何大一等多国专家于2023年发起，开展前瞻性研究，布局产品研发与应急储备。联盟已举办多场国际研讨会，发展为全球活跃的流行病研究协作网络，助力全球公共卫生体系建设。",'about','PRA 合作 正文（换行分段）','textarea'],
    // 联系我们
    ['about.contact.eyebrow','联系我们','about','联系我们 眉题','text'],
    ['about.contact.title','寻求合作 · 加入我们 · <span class="txt-neon">使用平台</span>','about','联系我们 标题(含标记)','textarea'],
    ['about.contact.sub','m13673741782@163.com · 清华大学医学院','about','联系我们 说明','text'],
];
foreach ($S_about as $s) Snippets::seed($s[0], $s[1], $s[2], $s[3], $s[4] ?? 'text');
echo "  + snippets(about): " . count($S_about) . " seeded (idempotent)\n";

// ── education 页 content_cards（分组幂等，逐字）──
// 课程简介 / 育人理念（education.html .three-col info-card）
seed_cards_group('edu_info', [
    ['grp'=>'edu_info','icon'=>'📘','title'=>'课程简介','body'=>'','extra'=>json_encode(['items'=>['从病毒免疫到疫苗研发的完整知识体系','原版课程完整留存，拆解为独立元视频片段','支持精准点播单个知识点，无需通看全片','按知识点精准点播，边看边学'],'ico_style'=>''], JSON_UNESCAPED_UNICODE),'sort'=>1,'published'=>1],
    ['grp'=>'edu_info','icon'=>'💡','title'=>'育人理念','body'=>'','extra'=>json_encode(['items'=>['让科研教育有趣、好玩、可高频使用','知识分层开放，从科普到科研全覆盖','AI 全程陪伴答疑，降低学习门槛','搭建前后辈互助传承的成长生态'],'ico_style'=>''], JSON_UNESCAPED_UNICODE),'sort'=>2,'published'=>1],
]);

// 教学安排（education.php 折叠面板）
seed_cards_group('edu_schedule', [
    ['grp'=>'edu_schedule','icon'=>'','title'=>'第 1 讲 · 绪论：疫苗点亮健康','body'=>'授课：张林琦','extra'=>'','sort'=>1,'published'=>1],
    ['grp'=>'edu_schedule','icon'=>'','title'=>'第 2 讲 · 预防接种进展与成就','body'=>'授课：梁晓峰','extra'=>'','sort'=>2,'published'=>1],
    ['grp'=>'edu_schedule','icon'=>'','title'=>'第 3 讲 · 疫苗是如何保护我们的？','body'=>'授课：李冠乔','extra'=>'','sort'=>3,'published'=>1],
    ['grp'=>'edu_schedule','icon'=>'','title'=>'第 4 讲 · 疫苗是如何研制和质量保障的？','body'=>'授课：李冠乔','extra'=>'','sort'=>4,'published'=>1],
    ['grp'=>'edu_schedule','icon'=>'','title'=>'第 5 讲 · 疫苗经济学','body'=>'授课：方海','extra'=>'','sort'=>5,'published'=>1],
    ['grp'=>'edu_schedule','icon'=>'','title'=>'第 6 讲 · 疫苗免疫效果评估—实验室的科技魅力','body'=>'授课：史宣玲','extra'=>'','sort'=>6,'published'=>1],
    ['grp'=>'edu_schedule','icon'=>'','title'=>'第 7 讲 · 天花疫苗和脊髓灰质炎疫苗-人类疫苗历史的壮举','body'=>'授课：陈志伟','extra'=>'','sort'=>7,'published'=>1],
    ['grp'=>'edu_schedule','icon'=>'','title'=>'第 8 讲 · 乙肝疫苗-摘掉我国“乙肝大国”帽子的功臣','body'=>'授课：崔富强','extra'=>'','sort'=>8,'published'=>1],
    ['grp'=>'edu_schedule','icon'=>'','title'=>'第 9 讲 · 乙脑疫苗-我国第一支通过世卫组织预认证的疫苗','body'=>'授课：袁媛','extra'=>'','sort'=>9,'published'=>1],
    ['grp'=>'edu_schedule','icon'=>'','title'=>'第 10 讲 · 人乳头瘤病毒（HPV）疫苗—宫颈癌的克星','body'=>'授课：乔友林','extra'=>'','sort'=>10,'published'=>1],
    ['grp'=>'edu_schedule','icon'=>'','title'=>'第 11 讲 · 流感疫苗-以变应变的追逐与纠结','body'=>'授课：冯录召','extra'=>'','sort'=>11,'published'=>1],
    ['grp'=>'edu_schedule','icon'=>'','title'=>'第 12 讲 · 呼吸道相关传染病的疫苗','body'=>'授课：张纬','extra'=>'','sort'=>12,'published'=>1],
    ['grp'=>'edu_schedule','icon'=>'','title'=>'第 13 讲 · 治疗性疫苗','body'=>'授课：傅阳心','extra'=>'','sort'=>13,'published'=>1],
    ['grp'=>'edu_schedule','icon'=>'','title'=>'第 14 讲 · 疫苗与全球健康','body'=>'授课：杜珩','extra'=>'','sort'=>14,'published'=>1],
    ['grp'=>'edu_schedule','icon'=>'','title'=>'第 15 讲 · 历史回顾和未来展望','body'=>'授课：张林琦','extra'=>'','sort'=>15,'published'=>1],
]);

// ── about 页 张老师四部分 content_cards（分组幂等，逐字）──
// 学术论文成果（近 5 年代表性文章，完整引用）
seed_cards_group('zhang_papers', [
    ['grp'=>'zhang_papers','icon'=>'','title'=>'Lan J, Ge J, Yu J, Shan S, Zhou H, Fan S, Zhang Q, Shi X, Wang Q, Zhang L, Wang X. Structure of the SARS-CoV-2 spike receptor-binding domain bound to the ACE2 receptor. Nature. 2020 May;581(7807):215-220.','body'=>'','extra'=>'','sort'=>1,'published'=>1],
    ['grp'=>'zhang_papers','icon'=>'','title'=>'Ju B, Zhang Q, Ge J, Wang R, Sun J, Ge X, Yu J, Shan S, Zhou B, Song S, Tang X, Yu J, Lan J, Yuan J, Wang H, Zhao J, Zhang S, Wang Y, Shi X, Liu L, Zhao J, Wang X, Zhang Z, Zhang L. Human neutralizing antibodies elicited by SARS-CoV-2 infection. Nature. 2020 Aug;584(7819):115-119.','body'=>'','extra'=>'','sort'=>2,'published'=>1],
    ['grp'=>'zhang_papers','icon'=>'','title'=>'Wang R, Zhang Q, Ge J, Ren W, Zhang R, Lan J, Ju B, Su B, Yu F, Chen P, Liao H, Feng Y, Li X, Shi X, Zhang Z, Zhang F, Ding Q, Zhang T, Wang X, Zhang L. Analysis of SARS-CoV-2 variant mutations reveals neutralization escape mechanisms and the ability to use ACE2 receptors from additional species. Immunity. 2021 Jul 13;54(7):1611-1621.e5.','body'=>'','extra'=>'','sort'=>3,'published'=>1],
    ['grp'=>'zhang_papers','icon'=>'','title'=>'Shan S, Luo S, Yang Z, Hong J, Su Y, Ding F, Fu L, Li C, Chen P, Ma J, Shi X, Zhang Q, Berger B, Zhang L, Peng J. Deep learning guided optimization of human antibody against SARS-CoV-2 variants with broad neutralization. Proc Natl Acad Sci U S A. 2022 Mar 15;119(11):e2122954119.','body'=>'','extra'=>'','sort'=>4,'published'=>1],
    ['grp'=>'zhang_papers','icon'=>'','title'=>'Qi H, Liu B, Wang X, Zhang L. The humoral response and antibodies against SARS-CoV-2 infection. Nat Immunol. 2022 Jul;23(7):1008-1020.','body'=>'','extra'=>'','sort'=>5,'published'=>1],
    ['grp'=>'zhang_papers','icon'=>'','title'=>'Ju B, Zhang Q, Wang Z, Aw ZQ, Chen P, Zhou B, Wang R, Ge X, Lv Q, Cheng L, Zhang R, Wong YH, Chen H, Wang H, Shan S, Liao X, Shi X, Liu L, Chu JJH, Wang X, Zhang Z, Zhang L. Infection with wild-type SARS-CoV-2 elicits broadly neutralizing and protective antibodies against omicron subvariants. Nat Immunol. 2023 Apr;24(4):690-699.','body'=>'','extra'=>'','sort'=>6,'published'=>1],
    ['grp'=>'zhang_papers','icon'=>'','title'=>'Wang R, Han Y, Zhang R, Zhu J, Nan X, Liu Y, Yang Z, Zhou B, Yu J, Lin Z, Li J, Chen P, Wang Y, Li Y, Liu D, Shi X, Wang X, Zhang Q, Yang YR, Li T, Zhang L. Dissecting the intricacies of human antibody responses to SARS-CoV-1 and SARS-CoV-2 infection. Immunity. 2023 Nov 14;56(11):2635-2649.','body'=>'','extra'=>'','sort'=>7,'published'=>1],
    ['grp'=>'zhang_papers','icon'=>'','title'=>'Idoudi F, Wang R, Tian L, Yang Z, Chik KK, Chen P, Benabderrazek R, Fu L, Tan R, Dhaouadi S, Benlasfar Z, Somia M, Zhang Q, Shi X, Chan JF, Yuen KY, Wang X, Bouhaouala-Zahar B, Zhang L. Decoding antibody response to MERS-CoV in wild dromedary camels. Proc Natl Acad Sci U S A. 2026 Feb 09;123(7):e2513716123.','body'=>'','extra'=>'','sort'=>8,'published'=>1],
    ['grp'=>'zhang_papers','icon'=>'','title'=>'Zhang Q, Chen P, Guo F, Zhou R, Guo R, Ge X, Yang Q, Xie X, Xia W, Fan J, Yang Z, Xu Y, Huang H, Li J, Wang H, Liao H, Shi X, Liu N, Chen Y, Chen Z, Ma J, Wang X, Zhang T, Zhang L. Twenty-year persistence of SARS-CoV-1 immune imprinting shapes antibody responses to SARS-CoV-2 infection. Immunity. 2026 Nov 10;59:1-16.','body'=>'','extra'=>'','sort'=>9,'published'=>1],
]);

// 专利成果汇总（title=专利名称，body=专利号）
seed_cards_group('zhang_patents', [
    ['grp'=>'zhang_patents','icon'=>'','title'=>'针对 SARS-CoV-1 的疫苗和药物及作为其活性成分的中和抗体 W322-3D10','body'=>'ZL 2023 1 0908923.6','extra'=>'','sort'=>1,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'SARS-CoV-2 德尔塔突变株 S 蛋白变体及其应用','body'=>'ZL 2021 1 0715301.2','extra'=>'','sort'=>2,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'SARS-CoV-2 S 蛋白变体及其在制备通用疫苗中的应用','body'=>'ZL 2021 1 0715302.7','extra'=>'','sort'=>3,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'一种用于制备 IgA 双聚体的方法','body'=>'ZL 2023 1 0555265.7','extra'=>'','sort'=>4,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'一种广谱中和 SARS-CoV-2 的中和抗体 P5-2H11 及其应用','body'=>'ZL 2023 1 0107070.6','extra'=>'','sort'=>5,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'一种广谱中和 SARS-CoV-2 的中和抗体 P2-1G9 及其应用','body'=>'ZL 2023 1 0130257.8','extra'=>'','sort'=>6,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'一种广谱中和 SARS-CoV-2 的中和抗体 P36-5D2 及其应用','body'=>'ZL 2021 1 1197126.9','extra'=>'','sort'=>7,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'一种具有广泛细胞嗜性和高效感染效率的包膜蛋白及其应用','body'=>'ZL 2023 1 0811160.3','extra'=>'','sort'=>8,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'一种广谱中和冠状病毒的中和抗体 W322-3E1 及其应用','body'=>'ZL 2023 1 0907844.3','extra'=>'','sort'=>9,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'广谱中和抗体 W328-6E10 及其在制备针对冠状病毒的疫苗中的应用','body'=>'ZL 2023 1 0908455.2','extra'=>'','sort'=>10,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'中和抗体 W328-5B1 及其在制备针对 SARS-CoV-1 的疫苗和药物中的应用','body'=>'ZL 2023 1 0912428.2','extra'=>'','sort'=>11,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'一种广谱中和 SARS-CoV-2 的中和抗体 P5S-2B6 及其应用','body'=>'ZL 2023 1 0046056.X','extra'=>'','sort'=>12,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'一种广谱中和 SARS-CoV-2 的中和抗体 P5S-1D8 及其应用','body'=>'ZL 2023 1 0050475.0','extra'=>'','sort'=>13,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'一种新型冠状病毒疫苗','body'=>'ZL 2021 1 0369037.1','extra'=>'','sort'=>14,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'SARS-CoV-2 Spike 蛋白受体结合域二聚体及其应用','body'=>'ZL 2020 1 0369494.6','extra'=>'','sort'=>15,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'一种包含嵌合抗原受体修饰的 T 细胞及其应用','body'=>'ZL 2019 1 1060442.4','extra'=>'','sort'=>16,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'一种嵌合抗原受体及其应用','body'=>'ZL 2019 1 1061313.7','extra'=>'','sort'=>17,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'MERS-CoV 膜蛋白受体结合域二聚体及其编码基因和应用','body'=>'ZL 2020 1 0418710.1','extra'=>'','sort'=>18,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'乙肝病毒的中和抗体 B432 及其应用','body'=>'ZL 2018 1 1442220.4','extra'=>'','sort'=>19,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'乙肝病毒的中和抗体 B826 及其应用','body'=>'ZL 2018 1 1442215.3','extra'=>'','sort'=>20,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'乙肝病毒的中和抗体 B430 及其应用','body'=>'ZL 2018 1 1442794.1','extra'=>'','sort'=>21,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'一种中和 EB 病毒的单克隆抗体及其应用','body'=>'ZL 2020 1 0363336.X','extra'=>'','sort'=>22,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'一种针对 HIV 的双特异性抗体及其编码基因和应用','body'=>'ZL 2019 1 0091714.0','extra'=>'','sort'=>23,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'一种包含嵌合抗原受体 (CAR) 修饰的 T 细胞在制备细胞药物中的用途','body'=>'ZL 2018 1 0257952.X','extra'=>'','sort'=>24,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'单克隆抗体 MERS-4V2 及其编码基因和应用','body'=>'ZL 2017 1 0951002.2','extra'=>'','sort'=>25,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'一种基于黑猩猩腺病毒 68 型和 MERS-CoV 全长膜蛋白的新型冠状病毒疫苗','body'=>'ZL 2018 1 0628239.1','extra'=>'','sort'=>26,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'一种单克隆抗体 ZK2C2 及应用','body'=>'ZL 2017 1 0257084.0','extra'=>'','sort'=>27,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'一种单克隆抗体 ZK2B10 及应用','body'=>'ZL 2017 1 0257734.1','extra'=>'','sort'=>28,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'一种单克隆抗体 Q206 及应用','body'=>'ZL 2016 1 0069699.6','extra'=>'','sort'=>29,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'一种单克隆抗体 Q314 及应用','body'=>'ZL 2016 1 0070023.9','extra'=>'','sort'=>30,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'一种单克隆抗体 Q411 及应用','body'=>'ZL 2016 1 0070024.3','extra'=>'','sort'=>31,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'多肽、提高多肽稳定性的方法、药物组合物以及多肽在制备药物中的用途','body'=>'ZL 2014 1 0535702.X','extra'=>'','sort'=>32,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'一类调控 CCR5 和 CXCR4 基因的融合蛋白及方法','body'=>'ZL 2012 1 0385677.2','extra'=>'','sort'=>33,'published'=>1],
    ['grp'=>'zhang_patents','icon'=>'','title'=>'抗丙型肝炎病毒化合物及其制备方法和应用','body'=>'ZL 2014 1 0284733.2','extra'=>'','sort'=>34,'published'=>1],
]);

// 科研奖项与荣誉（title=荣誉，body=年份）
seed_cards_group('zhang_honors', [
    ['grp'=>'zhang_honors','icon'=>'','title'=>'享受国务院政府特殊津贴专家','body'=>'2023','extra'=>'','sort'=>1,'published'=>1],
    ['grp'=>'zhang_honors','icon'=>'','title'=>'北京市优秀研究生指导教师','body'=>'2022','extra'=>'','sort'=>2,'published'=>1],
    ['grp'=>'zhang_honors','icon'=>'','title'=>'全国科技系统抗击新冠肺炎疫情先进个人','body'=>'2021','extra'=>'','sort'=>3,'published'=>1],
    ['grp'=>'zhang_honors','icon'=>'','title'=>'清华大学第十七届"良师益友"','body'=>'2021','extra'=>'','sort'=>4,'published'=>1],
    ['grp'=>'zhang_honors','icon'=>'','title'=>'中华预防医学会科学技术奖','body'=>'2021','extra'=>'','sort'=>5,'published'=>1],
    ['grp'=>'zhang_honors','icon'=>'','title'=>'教育部中国高等学校十大科技进展','body'=>'2021','extra'=>'','sort'=>6,'published'=>1],
    ['grp'=>'zhang_honors','icon'=>'','title'=>'万科讲席教授','body'=>'2020','extra'=>'','sort'=>7,'published'=>1],
    ['grp'=>'zhang_honors','icon'=>'','title'=>'拜耳讲席教授','body'=>'2019','extra'=>'','sort'=>8,'published'=>1],
    ['grp'=>'zhang_honors','icon'=>'','title'=>'非洲科学院院士','body'=>'2016-至今','extra'=>'','sort'=>9,'published'=>1],
    ['grp'=>'zhang_honors','icon'=>'','title'=>'清华大学医学院十大重大成果','body'=>'2016','extra'=>'','sort'=>10,'published'=>1],
    ['grp'=>'zhang_honors','icon'=>'','title'=>'清华大学教育教学成果一等奖','body'=>'2016','extra'=>'','sort'=>11,'published'=>1],
    ['grp'=>'zhang_honors','icon'=>'','title'=>'强生研究员','body'=>'2015-2017','extra'=>'','sort'=>12,'published'=>1],
    ['grp'=>'zhang_honors','icon'=>'','title'=>'国家科技进步奖二等奖','body'=>'2015','extra'=>'','sort'=>13,'published'=>1],
    ['grp'=>'zhang_honors','icon'=>'','title'=>'国家自然科学基金杰出青年基金','body'=>'2008-至今','extra'=>'','sort'=>14,'published'=>1],
    ['grp'=>'zhang_honors','icon'=>'','title'=>'长江学者奖励计划特聘教授','body'=>'2007-至今','extra'=>'','sort'=>15,'published'=>1],
    ['grp'=>'zhang_honors','icon'=>'','title'=>'国家自然科学基金海外杰出青年基金','body'=>'2003-2006','extra'=>'','sort'=>16,'published'=>1],
    ['grp'=>'zhang_honors','icon'=>'','title'=>'葛兰素史克药物研发奖','body'=>'2001-2002','extra'=>'','sort'=>17,'published'=>1],
    ['grp'=>'zhang_honors','icon'=>'','title'=>'中英友好奖学金','body'=>'1988-1992','extra'=>'','sort'=>18,'published'=>1],
]);

seed_cards_group('intl_id', [
    ['grp'=>'intl_id','icon'=>'','title'=>'中印尼 mRNA 登革疫苗合作签约','body'=>'','extra'=>json_encode(['img'=>'assets/images/intl/id-1.webp'],JSON_UNESCAPED_UNICODE),'sort'=>1,'published'=>1],
    ['grp'=>'intl_id','icon'=>'','title'=>'联合科研团队交流','body'=>'','extra'=>json_encode(['img'=>'assets/images/intl/id-2.webp'],JSON_UNESCAPED_UNICODE),'sort'=>2,'published'=>1],
    ['grp'=>'intl_id','icon'=>'','title'=>'实验室联合研究','body'=>'','extra'=>json_encode(['img'=>'assets/images/intl/id-3.webp'],JSON_UNESCAPED_UNICODE),'sort'=>3,'published'=>1],
    ['grp'=>'intl_id','icon'=>'','title'=>'中印尼疫苗与基因组联合中心','body'=>'','extra'=>json_encode(['img'=>'assets/images/intl/id-4.webp'],JSON_UNESCAPED_UNICODE),'sort'=>4,'published'=>1],
    ['grp'=>'intl_id','icon'=>'','title'=>'学术研讨与人才互访','body'=>'','extra'=>json_encode(['img'=>'assets/images/intl/id-5.webp'],JSON_UNESCAPED_UNICODE),'sort'=>5,'published'=>1],
    ['grp'=>'intl_id','icon'=>'','title'=>'Universitas Indonesia 医学院合作','body'=>'','extra'=>json_encode(['img'=>'assets/images/intl/id-6.webp'],JSON_UNESCAPED_UNICODE),'sort'=>6,'published'=>1],
]);
seed_cards_group('intl_pra', [
    ['grp'=>'intl_pra','icon'=>'','title'=>'Pandemic Research Alliance 成立签约','body'=>'','extra'=>json_encode(['img'=>'assets/images/intl/pra-1.webp'],JSON_UNESCAPED_UNICODE),'sort'=>1,'published'=>1],
    ['grp'=>'intl_pra','icon'=>'','title'=>'PRA 国际研讨会','body'=>'','extra'=>json_encode(['img'=>'assets/images/intl/pra-2.webp'],JSON_UNESCAPED_UNICODE),'sort'=>2,'published'=>1],
    ['grp'=>'intl_pra','icon'=>'','title'=>'2024 PRA 国际研讨会','body'=>'','extra'=>json_encode(['img'=>'assets/images/intl/pra-3.webp'],JSON_UNESCAPED_UNICODE),'sort'=>3,'published'=>1],
    ['grp'=>'intl_pra','icon'=>'','title'=>'广州实验室','body'=>'','extra'=>json_encode(['img'=>'assets/images/intl/pra-4.webp'],JSON_UNESCAPED_UNICODE),'sort'=>4,'published'=>1],
    ['grp'=>'intl_pra','icon'=>'','title'=>'下一代大流行病防治疗法研讨','body'=>'','extra'=>json_encode(['img'=>'assets/images/intl/pra-5.webp'],JSON_UNESCAPED_UNICODE),'sort'=>5,'published'=>1],
    ['grp'=>'intl_pra','icon'=>'','title'=>'全球视角专题论坛','body'=>'','extra'=>json_encode(['img'=>'assets/images/intl/pra-6.webp'],JSON_UNESCAPED_UNICODE),'sort'=>6,'published'=>1],
]);

// ── V4.3 · 平台页 content_cards（8 组，分组幂等）──
seed_cards_group('platform_agent_module', [
    ['grp'=>'platform_agent_module','icon'=>'📚','title'=>'文献研究','body'=>'文献检索、靶点信息与研究背景理解','extra'=>'','sort'=>1,'published'=>1],
    ['grp'=>'platform_agent_module','icon'=>'🧬','title'=>'抗体设计','body'=>'抗体生成、优化与人源化','extra'=>'','sort'=>2,'published'=>1],
    ['grp'=>'platform_agent_module','icon'=>'🔬','title'=>'预测分析','body'=>'结构、亲和力与成药性分析','extra'=>'','sort'=>3,'published'=>1],
    ['grp'=>'platform_agent_module','icon'=>'📋','title'=>'实验规划','body'=>'候选排序与实验方案规划','extra'=>'','sort'=>4,'published'=>1],
]);

seed_cards_group('platform_data_card', [
    ['grp'=>'platform_data_card','icon'=>'','title'=>'真实数据','body'=>'抗体序列 ｜ 靶点结构 ｜ 结合亲和力 ｜ 实验结果','extra'=>'','sort'=>1,'published'=>1],
    ['grp'=>'platform_data_card','icon'=>'','title'=>'AI 分析','body'=>'候选设计 ｜ 结构预测 ｜ 亲和力/成药性评估 ｜ 排序筛选','extra'=>'','sort'=>2,'published'=>1],
]);

seed_cards_group('platform_lab_cap', [
    ['grp'=>'platform_lab_cap','icon'=>'','title'=>'高通量','body'=>'支持大规模抗体候选的并行筛选，提高抗体发现效率','extra'=>'','sort'=>1,'published'=>1],
    ['grp'=>'platform_lab_cap','icon'=>'','title'=>'自动化','body'=>'将实验操作与数据采集整合为标准化流程，减少人工干预，提高实验重复性','extra'=>'','sort'=>2,'published'=>1],
    ['grp'=>'platform_lab_cap','icon'=>'','title'=>'微量化','body'=>'覆盖皮升级至微升级液滴操作，在提升实验通量的同时降低样本与试剂消耗','extra'=>'','sort'=>3,'published'=>1],
]);

seed_cards_group('platform_vlp_kind', [
    ['grp'=>'platform_vlp_kind','icon'=>'','title'=>'多聚体 / 蛋白复合物','body'=>'尤其适合需要多个不同亚基共同组装、单独表达难以还原天然结构的异源多聚体蛋白','extra'=>'','sort'=>1,'published'=>1],
    ['grp'=>'platform_vlp_kind','icon'=>'','title'=>'多次跨膜蛋白','body'=>'依赖膜环境维持正确拓扑与构象，脱离膜环境后天然状态往往难以稳定保持','extra'=>'','sort'=>2,'published'=>1],
]);

seed_cards_group('platform_loop_side', [
    ['grp'=>'platform_loop_side','icon'=>'','title'=>'设计更精准','body'=>'结合数据与模型进行候选设计与筛选','extra'=>'','sort'=>1,'published'=>1],
    ['grp'=>'platform_loop_side','icon'=>'','title'=>'验证更高效','body'=>'自动化实验平台支持标准化验证流程','extra'=>'','sort'=>2,'published'=>1],
    ['grp'=>'platform_loop_side','icon'=>'','title'=>'迭代更快速','body'=>'实验结果回流分析，持续优化候选抗体','extra'=>'','sort'=>3,'published'=>1],
]);

seed_cards_group('platform_loop_ring', [
    ['grp'=>'platform_loop_ring','icon'=>'','title'=>'研究目标','body'=>'明确靶点、应用场景与关键指标','extra'=>json_encode(['side'=>'dry'],JSON_UNESCAPED_UNICODE),'sort'=>1,'published'=>1],
    ['grp'=>'platform_loop_ring','icon'=>'','title'=>'Antibody Agent','body'=>'任务理解、候选设计、结构与亲和力预测','extra'=>json_encode(['side'=>'dry'],JSON_UNESCAPED_UNICODE),'sort'=>2,'published'=>1],
    ['grp'=>'platform_loop_ring','icon'=>'','title'=>'实验验证','body'=>'抗体表达、结合活性与功能验证','extra'=>json_encode(['side'=>'wet'],JSON_UNESCAPED_UNICODE),'sort'=>3,'published'=>1],
    ['grp'=>'platform_loop_ring','icon'=>'','title'=>'数据分析','body'=>'结果整理、候选比较与多维评估','extra'=>json_encode(['side'=>'wet'],JSON_UNESCAPED_UNICODE),'sort'=>4,'published'=>1],
    ['grp'=>'platform_loop_ring','icon'=>'','title'=>'迭代优化','body'=>'基于实验反馈优化候选，并进入下一轮设计','extra'=>json_encode(['side'=>'dry'],JSON_UNESCAPED_UNICODE),'sort'=>5,'published'=>1],
]);

seed_cards_group('platform_case_stat', [
    ['grp'=>'platform_case_stat','icon'=>'','title'=>'80%','body'=>'降低住院和死亡率','extra'=>'','sort'=>1,'published'=>1],
    ['grp'=>'platform_case_stat','icon'=>'','title'=>'837','body'=>'例患者临床试验','extra'=>'','sort'=>2,'published'=>1],
    ['grp'=>'platform_case_stat','icon'=>'','title'=>'0','body'=>'治疗后 28 天死亡','extra'=>'','sort'=>3,'published'=>1],
]);

seed_cards_group('platform_case_vlp', [
    ['grp'=>'platform_case_vlp','icon'=>'','title'=>'VLP 抗原呈递','body'=>'保持复杂膜蛋白天然构象','extra'=>'','sort'=>1,'published'=>1],
    ['grp'=>'platform_case_vlp','icon'=>'','title'=>'涉及疾病','body'=>'肿瘤、病毒感染（如 HIV）、糖尿病/肥胖等代谢性疾病、免疫相关疾病','extra'=>'','sort'=>2,'published'=>1],
    ['grp'=>'platform_case_vlp','icon'=>'','title'=>'代表靶点','body'=>'GPCR、转运体、CD3/TCR、NKG2A/CD94 等','extra'=>'','sort'=>3,'published'=>1],
]);
