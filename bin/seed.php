<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/bootstrap.php';

echo "Seeding MabSeek DB...\n";
$pdo = db();

// ── 1) 初始管理员（幂等：已存在不动，保护改过的密码）──
$has = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ((int)$has === 0) {
    $now = iso_now();
    $pdo->prepare('INSERT INTO users(username,password_hash,must_change_password,created_at,updated_at) VALUES(?,?,1,?,?)')
        ->execute([SEED_ADMIN_USER, password_hash(SEED_ADMIN_PASS, PASSWORD_ARGON2ID), $now, $now]);
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
    ['date_day'=>'08','date_ym'=>'2026·08','category'=>'res','title'=>'MabSeek 平台技术升级：亲和力预测模型精度再提升','summary'=>'最新一轮迭代显著提升虚拟筛选命中率，缩短候选分子验证周期。','image'=>null,'sort'=>1,'published'=>1],
    ['date_day'=>'07','date_ym'=>'2026·07','category'=>'edu','title'=>'《疫苗的力量》交互式课程正式上线','summary'=>'元视频 + 三层知识图谱，面向学生、从业者与公众开放基础版。','image'=>null,'sort'=>2,'published'=>1],
    ['date_day'=>'06','date_ym'=>'2026·06','category'=>'res','title'=>'实验室最新研究成果发表于领域顶级期刊','summary'=>'抗体设计与免疫机制相关工作获同行高度评价。','image'=>null,'sort'=>3,'published'=>1],
    ['date_day'=>'05','date_ym'=>'2026·05','category'=>'daily','title'=>'组会纪实与学术沙龙：师生共话前沿方向','summary'=>'平台宣传片发布，记录实验室日常科研与交流活动。','image'=>null,'sort'=>4,'published'=>1],
    ['date_day'=>'04','date_ym'=>'2026·04','category'=>'edu','title'=>'学生斩获学术竞赛奖项，人才培养成果显著','summary'=>'科普讲座与公益授课走进高校，扩大科学影响力。','image'=>null,'sort'=>5,'published'=>1],
]);

// ── 3) team_members（about.html 双负责人，逐字）──
seed_collection('team_members', [
    ['name'=>'张林琦','affiliation'=>'清华大学 · 实验室负责人','direction'=>'长期从事抗体工程、疫苗研发与病毒免疫研究，为 MabSeek 奠定抗体科学与湿实验验证的专业根基。','role_label'=>'科学负责人 · 抗体 / 疫苗 / 病毒免疫','role_type'=>'science','avatar_char'=>'张','avatar_variant'=>'','sort'=>1,'published'=>1],
    ['name'=>'马维英','affiliation'=>'清华大学 · 实验室负责人','direction'=>'深耕人工智能、大模型与机器学习，为 MabSeek 的 AI 抗体设计、预测与分析能力提供核心算法支撑。','role_label'=>'AI 负责人 · 人工智能 / 大模型 / 机器学习','role_type'=>'ai','avatar_char'=>'马','avatar_variant'=>'g2','sort'=>2,'published'=>1],
]);

// ── 4) partners（index.html logo 墙，逐字）──
seed_collection('partners', [
    ['name'=>'清华大学','mark'=>'清','sub'=>'','logo_image'=>null,'demo'=>'清华大学联合实验室详情','sort'=>1,'published'=>1],
    ['name'=>'北京大学','mark'=>'北','sub'=>'','logo_image'=>null,'demo'=>'北京大学联合实验室详情','sort'=>2,'published'=>1],
    ['name'=>'Eijkman 研究所','mark'=>'EJ','sub'=>'印度尼西亚','logo_image'=>null,'demo'=>'Eijkman 研究所合作','sort'=>3,'published'=>1],
    ['name'=>'PRA 国际联盟','mark'=>'PRA','sub'=>'','logo_image'=>null,'demo'=>'PRA 国际联盟合作','sort'=>4,'published'=>1],
]);

// ── 5) forum_hot（forum.html #hot，每日 6 + 每周 6，逐字）──
seed_collection('forum_hot', [
    ['list'=>'day','rank'=>1,'title'=>'顶刊拆解：双抗结构设计的三种范式与踩坑','category'=>'# 文献精读','heat'=>'🔥 1.2k','sort'=>1,'published'=>1],
    ['list'=>'day','rank'=>2,'title'=>'ELISA 总是背景高？这 5 个洗板细节 90% 的人忽略了','category'=>'# 实验踩坑','heat'=>'🔥 980','sort'=>2,'published'=>1],
    ['list'=>'day','rank'=>3,'title'=>'AlphaFold3 跑 Nb 表位分析：参数与结果解读实录','category'=>'# 生信工具','heat'=>'🔥 856','sort'=>3,'published'=>1],
    ['list'=>'day','rank'=>4,'title'=>'抗体序列特征分析：从 FASTA 到可视化的一条龙脚本','category'=>'# 生信工具','heat'=>'🔥 742','sort'=>4,'published'=>1],
    ['list'=>'day','rank'=>5,'title'=>'细胞培养污染排查手册：那些论文里不写的坑','category'=>'# 实验踩坑','heat'=>'🔥 610','sort'=>5,'published'=>1],
    ['list'=>'day','rank'=>6,'title'=>'ADC 偶联比 DAR 老是不稳定？记录一次踩坑复盘','category'=>'# 实验踩坑','heat'=>'🔥 523','sort'=>6,'published'=>1],
    ['list'=>'week','rank'=>1,'title'=>'2026 抗体研发岗求职时间线 + 面经合集（持续更新）','category'=>'# 求职招聘','heat'=>'🔥 5.6k','sort'=>7,'published'=>1],
    ['list'=>'week','rank'=>2,'title'=>'纳米抗体（VHH）综述：为什么它是 AI 设计的最佳试验田','category'=>'# 文献精读','heat'=>'🔥 4.8k','sort'=>8,'published'=>1],
    ['list'=>'week','rank'=>3,'title'=>'顶刊拆解：双抗结构设计的三种范式与踩坑','category'=>'# 文献精读','heat'=>'🔥 4.2k','sort'=>9,'published'=>1],
    ['list'=>'week','rank'=>4,'title'=>'抗体序列特征分析：从 FASTA 到可视化的一条龙脚本','category'=>'# 生信工具','heat'=>'🔥 3.9k','sort'=>10,'published'=>1],
    ['list'=>'week','rank'=>5,'title'=>'ELISA 总是背景高？这 5 个洗板细节 90% 的人忽略了','category'=>'# 实验踩坑','heat'=>'🔥 3.1k','sort'=>11,'published'=>1],
    ['list'=>'week','rank'=>6,'title'=>'AlphaFold3 跑 Nb 表位分析：参数与结果解读实录','category'=>'# 生信工具','heat'=>'🔥 2.7k','sort'=>12,'published'=>1],
]);

// ── 6) forum_posts（forum.html #feed，8 条，逐字）──
seed_collection('forum_posts', [
    ['category'=>'proto','cover_type'=>'img','cover_ref'=>'assets/images/forum-protocol.png','cover_variant'=>'','toptag'=>'# Protocol','title'=>'ELISA 总是背景高？这 5 个洗板细节 90% 的人忽略了','tags'=>'#实验踩坑,#Protocol分享','author_name'=>'资深博后 · 李','author_avatar_char'=>'博','author_avatar_style'=>'','likes'=>'❤️ 328','sort'=>1,'published'=>1],
    ['category'=>'bio','cover_type'=>'img','cover_ref'=>'assets/images/forum-bioinfo.png','cover_variant'=>'g2','toptag'=>'# 生信工具','title'=>'抗体序列特征分析：从 FASTA 到可视化的一条龙脚本','tags'=>'#生信工具,#Protocol分享','author_name'=>'生信小王','author_avatar_char'=>'生','author_avatar_style'=>'background:var(--grad-green);color:#04352a','likes'=>'❤️ 501','sort'=>2,'published'=>1],
    ['category'=>'paper','cover_type'=>'grad','cover_ref'=>'📄','cover_variant'=>'g3','toptag'=>'','title'=>'顶刊拆解：双抗结构设计的三种范式与踩坑','tags'=>'#文献精读,#前沿热点','author_name'=>'某 PI · 匿名','author_avatar_char'=>'PI','author_avatar_style'=>'','likes'=>'❤️ 742','sort'=>3,'published'=>1],
    ['category'=>'pit','cover_type'=>'img','cover_ref'=>'assets/images/forum-adc.png','cover_variant'=>'','toptag'=>'# 前沿','title'=>'ADC 偶联比 DAR 老是不稳定？记录一次踩坑复盘','tags'=>'#实验踩坑,#ADC','author_name'=>'产业老兵','author_avatar_char'=>'产','author_avatar_style'=>'background:var(--grad-green);color:#04352a','likes'=>'❤️ 289','sort'=>4,'published'=>1],
    ['category'=>'job','cover_type'=>'grad','cover_ref'=>'💼','cover_variant'=>'','toptag'=>'','title'=>'2026 抗体研发岗求职时间线 + 面经合集（持续更新）','tags'=>'#求职招聘,#科研生活','author_name'=>'校友 · 张','author_avatar_char'=>'校','author_avatar_style'=>'','likes'=>'❤️ 613','sort'=>5,'published'=>1],
    ['category'=>'paper','cover_type'=>'grad','cover_ref'=>'🦠','cover_variant'=>'g2','toptag'=>'','title'=>'纳米抗体（VHH）综述：为什么它是 AI 设计的最佳试验田','tags'=>'#文献精读,#纳米抗体','author_name'=>'研一萌新','author_avatar_char'=>'研','author_avatar_style'=>'','likes'=>'❤️ 176','sort'=>6,'published'=>1],
    ['category'=>'pit','cover_type'=>'grad','cover_ref'=>'🔬','cover_variant'=>'g3','toptag'=>'','title'=>'细胞培养污染排查手册：那些论文里不写的坑','tags'=>'#实验踩坑,#避坑指南','author_name'=>'养细胞的人','author_avatar_char'=>'养','author_avatar_style'=>'background:var(--grad-green);color:#04352a','likes'=>'❤️ 455','sort'=>7,'published'=>1],
    ['category'=>'bio','cover_type'=>'grad','cover_ref'=>'🧠','cover_variant'=>'','toptag'=>'','title'=>'AlphaFold3 跑 Nb 表位分析：参数与结果解读实录','tags'=>'#生信工具,#AI制药','author_name'=>'算法同学','author_avatar_char'=>'算','author_avatar_style'=>'','likes'=>'❤️ 388','sort'=>8,'published'=>1],
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
    // 7c. 论坛四条内容线（forum.html .line-card）
    ['grp'=>'forum_line','icon'=>'🎓','title'=>'校友传承线','body'=>'','extra'=>json_encode(['ico_style'=>'background:var(--purple-050);color:var(--purple)','items'=>['校友留言墙：科研感悟、成长心得、毕业寄语','校友风采录：发展去向、职业简介、成长故事','校友动态联动：前后辈互助传承体系']], JSON_UNESCAPED_UNICODE),'sort'=>9,'published'=>1],
    ['grp'=>'forum_line','icon'=>'🙋','title'=>'问答求助线','body'=>'','extra'=>json_encode(['ico_style'=>'background:var(--green-100);color:#06a97c','items'=>['实验技术求助：Western、ELISA、细胞培养','生信分析求助：序列分析、分子模拟、代码报错','文献求助：找不到全文、看不懂关键论文']], JSON_UNESCAPED_UNICODE),'sort'=>10,'published'=>1],
    ['grp'=>'forum_line','icon'=>'📦','title'=>'干货分享线','body'=>'','extra'=>json_encode(['ico_style'=>'background:var(--purple-050);color:var(--purple)','items'=>['实验 Protocol 库：经过验证的实操流程','工具与资源：软件、数据库、脚本推荐','文献精读 + 避坑指南']], JSON_UNESCAPED_UNICODE),'sort'=>11,'published'=>1],
    ['grp'=>'forum_line','icon'=>'💡','title'=>'话题讨论线','body'=>'','extra'=>json_encode(['ico_style'=>'background:var(--green-100);color:#06a97c','items'=>['前沿热点：ADC、双抗、纳米抗体、AI 制药','产业动态：新药获批、融资并购、行业趋势','科研生活：读博日常、压力调节、师生关系']], JSON_UNESCAPED_UNICODE),'sort'=>12,'published'=>1],
]));

// ── 8) snippets（零散文案，逐字）──
$S = [
    // 全站页脚（footer.php 共用）
    ['footer.brand.tagline','清华团队 × AI 大模型，让抗体发现从反复试错变成精准编程。','common','页脚品牌简介','textarea'],
    ['footer.copyright','© 2026 MabSeek 抗体求索 · 清华大学医学院实验室. 保留所有权利。','common','页脚版权行','text'],
    ['footer.slogan','紫 + 荧光绿 · 科技与趣味的平衡','common','页脚标语','text'],
    ['contact.email','contact@mabseek.org','common','联系邮箱','text'],
    ['contact.org','清华大学医学院','common','联系单位','text'],

    // index.html
    ['home.hero.eyebrow','🧬 清华团队 × AI 大模型','home','首页 Hero 眉题','text'],
    ['home.hero.title','让抗体发现<br>从"反复试错"变成 <span class="txt-neon">"精准编程"</span>','home','首页 Hero 标题(含标记)','textarea'],
    ['home.hero.sub','从头设计一支完美结合靶点的抗体——AI 生成 + 干湿闭环验证，一站直达。','home','首页 Hero 副标题','textarea'],
    ['home.hero.cta','免费试用 Antibody Agent →','home','首页 Hero 按钮','text'],
    ['home.can.eyebrow','我们能做什么','home','能做什么 眉题','text'],
    ['home.can.title','把靶点交给 AI，<span class="txt-green">从设计到验证一条闭环</span>','home','能做什么 标题(含标记)','textarea'],
    ['home.can.sub','AI 从头序列设计 · 结构与亲和力预测 · 干湿闭环一站交付。过去分散在多个团队、数月起步的流程，被压缩为连续、可追溯、可下单的闭环。','home','能做什么 说明','textarea'],
    ['home.can.link','进入技术平台，了解完整技术优势 →','home','能做什么 链接','text'],
    ['home.pain.eyebrow','痛点共鸣 · 针对性解法','home','痛点 眉题','text'],
    ['home.pain.title','抗体发现行业的四大痛点，<span class="txt-neon">各有解法</span>','home','痛点 标题(含标记)','textarea'],
    ['home.pain.cta','查看实际案例 →','home','痛点 按钮','text'],
    ['home.news.eyebrow','我们的近况','home','近况 眉题','text'],
    ['home.news.title','新闻 · 发表 · 活动','home','近况 标题','text'],
    ['home.news.link','进入「了解我们」查看全部近况 →','home','近况 链接','text'],
    ['home.contact.eyebrow','合作伙伴 · 联系我们','home','联系 眉题','text'],
    ['home.contact.title','与顶尖机构<span class="txt-neon">共建生态</span>','home','联系 标题(含标记)','textarea'],
    ['home.contact.h3','有靶点或合作意向？给我们留个言','home','联系 小标题','text'],
    // 首页近况横滚卡（结构固定，文字可编辑）——沿用 news 集合渲染？否：首页卡片文案与 about 不同，用 snippet
    ['home.newscard.1.thumb','🧬 平台技术升级','home','近况卡1 缩略','text'],
    ['home.newscard.1.date','2026 · 08','home','近况卡1 日期','text'],
    ['home.newscard.1.title','亲和力预测模型精度再提升','home','近况卡1 标题','text'],
    ['home.newscard.1.body','最新一轮迭代显著提升虚拟筛选命中率，缩短候选分子验证周期。','home','近况卡1 正文','textarea'],
    ['home.newscard.2.thumb','🎓 交互式课程','home','近况卡2 缩略','text'],
    ['home.newscard.2.date','2026 · 07','home','近况卡2 日期','text'],
    ['home.newscard.2.title','《疫苗的力量》交互式课程上线','home','近况卡2 标题','text'],
    ['home.newscard.2.body','元视频 + 三层知识图谱，面向学生、从业者与公众开放基础版。','home','近况卡2 正文','textarea'],
    ['home.newscard.3.thumb','📄 学术发表','home','近况卡3 缩略','text'],
    ['home.newscard.3.date','2026 · 06','home','近况卡3 日期','text'],
    ['home.newscard.3.title','研究成果发表于领域顶级期刊','home','近况卡3 标题','text'],
    ['home.newscard.3.body','抗体设计与免疫机制相关工作获同行高度评价。','home','近况卡3 正文','textarea'],
    ['home.newscard.4.thumb','🌏 国际交流','home','近况卡4 缩略','text'],
    ['home.newscard.4.date','2026 · 05','home','近况卡4 日期','text'],
    ['home.newscard.4.title','中印尼疫苗与基因组联合研究进展','home','近况卡4 标题','text'],
    ['home.newscard.4.body','持续推进 PRA 国际科研合作与联合实验室建设。','home','近况卡4 正文','textarea'],
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

// ── agent 页 snippets（逐字，取自 agent.html）──
$S_agent = [
    ['agent.hero.eyebrow','🤖 专属领域 AI 智能体','agent','Agent 页 Hero 眉题','text'],
    ['agent.hero.title','一句话出方案<br><span class="grad-text">干湿闭环</span>一站交付','agent','Agent 页 Hero 标题(含标记)','textarea'],
    ['agent.hero.lead','Antibody Agent 集文献检索问答、抗体序列设计、亲和力预测、结构分析于一体，更打通 MabSeek 湿实验平台——从 AI 设计到表达纯化、功能验证一站式下单，线下实验室直接交付结果。','agent','Agent 页 Hero 说明','textarea'],
    ['agent.hero.cta1','查看干湿闭环 →','agent','Agent 页 Hero 按钮1','text'],
    ['agent.hero.cta2','探索智能体矩阵','agent','Agent 页 Hero 按钮2','text'],
    ['agent.hero.note','免费额度：今日剩余 100 / 100 次提问','agent','Agent 页 Hero 额度提示','text'],
    ['agent.cap.eyebrow','核心能力','agent','核心能力 眉题','text'],
    ['agent.cap.title','四大能力，<span class="txt-green">贯穿抗体发现全流程</span>','agent','核心能力 标题(含标记)','textarea'],
    ['agent.case.eyebrow','案例示范','agent','案例示范 眉题','text'],
    ['agent.case.title','看 Antibody Agent <span class="txt-neon">怎么工作</span>','agent','案例示范 标题(含标记)','textarea'],
    ['agent.case.sub','三段循环演示，直观呈现从需求到候选、筛选与结构理解的过程（示意动效，非真实结果数据）。','agent','案例示范 说明','textarea'],
    ['agent.flow.eyebrow','干湿闭环 · Dry-to-Wet','agent','干湿闭环 眉题','text'],
    ['agent.flow.title','从 AI 设计到线下交付，<span class="grad-text">一条闭环</span>','agent','干湿闭环 标题(含标记)','textarea'],
    ['agent.flow.sub','AI 设计与湿实验结果双向溯源，每一步都可追踪、可下单、可复现。','agent','干湿闭环 说明','textarea'],
    ['agent.wetlab.title','湿实验平台，直接下单','agent','湿实验卡 标题','text'],
    ['agent.wetlab.body','AI 生成方案后无需切换系统，在同一界面下单表达纯化与功能验证，线下实验室执行并交付结果——真正打通「设计即交付」。','agent','湿实验卡 正文','textarea'],
    ['agent.matrix.eyebrow','智能体矩阵','agent','智能体矩阵 眉题','text'],
    ['agent.matrix.title','Antibody Agent 领衔的科研智能体家族','agent','智能体矩阵 标题','text'],
    ['agent.matrix.sub','按科研场景细分的专属智能体，共享同一知识库与湿实验平台。','agent','智能体矩阵 说明','textarea'],
    ['agent.cta.title','现在就把你的靶点交给 Antibody Agent','agent','CTA 标题','text'],
    ['agent.cta.sub','从一句话到可下单方案，干湿闭环全程陪伴。','agent','CTA 说明','textarea'],
    ['agent.cta.btn','免费试用 Antibody Agent','agent','CTA 按钮','text'],
];
foreach ($S_agent as $s) Snippets::seed($s[0], $s[1], $s[2], $s[3], $s[4] ?? 'text');
echo "  + snippets(agent): " . count($S_agent) . " seeded (idempotent)\n";

// ── agent 页 content_cards（分组幂等，逐字）──
// 核心能力四卡（agent.html .grid-4）
seed_cards_group('agent_capability', [
    ['grp'=>'agent_capability','icon'=>'🔍','title'=>'文献检索问答','body'=>'接入领域知识库与全文文献，秒级定位机制、方法与前沿进展，答案可溯源。','extra'=>json_encode(['ico_class'=>''], JSON_UNESCAPED_UNICODE),'sort'=>1,'published'=>1],
    ['grp'=>'agent_capability','icon'=>'🧬','title'=>'抗体序列设计','body'=>'基于靶点一句话生成候选序列，自动完成人源化与可开发性优化。','extra'=>json_encode(['ico_class'=>'green'], JSON_UNESCAPED_UNICODE),'sort'=>2,'published'=>1],
    ['grp'=>'agent_capability','icon'=>'📈','title'=>'亲和力预测','body'=>'结合能计算 + 机器学习打分，动手实验前完成虚拟筛选与排序。','extra'=>json_encode(['ico_class'=>''], JSON_UNESCAPED_UNICODE),'sort'=>3,'published'=>1],
    ['grp'=>'agent_capability','icon'=>'🧩','title'=>'结构分析','body'=>'结构建模、表位识别与对接分析，理解「为什么结合、结合在哪里」。','extra'=>json_encode(['ico_class'=>'green'], JSON_UNESCAPED_UNICODE),'sort'=>4,'published'=>1],
]);

// 智能体矩阵八卡（agent.html .agent-chip）
seed_cards_group('agent_matrix', [
    ['grp'=>'agent_matrix','icon'=>'🧬','title'=>'Antibody Agent','body'=>'抗体发现与抗体工程 · 主力','extra'=>json_encode(['ai_bg'=>'var(--purple-050)','active'=>true,'link_href'=>'','link_text'=>''], JSON_UNESCAPED_UNICODE),'sort'=>1,'published'=>1],
    ['grp'=>'agent_matrix','icon'=>'🧠','title'=>'General Agent','body'=>'通用科学问答','extra'=>json_encode(['ai_bg'=>'var(--green-100)','active'=>false], JSON_UNESCAPED_UNICODE),'sort'=>2,'published'=>1],
    ['grp'=>'agent_matrix','icon'=>'🧫','title'=>'Biology Agent','body'=>'计算生物学与生信','extra'=>json_encode(['ai_bg'=>'var(--purple-050)','active'=>false], JSON_UNESCAPED_UNICODE),'sort'=>3,'published'=>1],
    ['grp'=>'agent_matrix','icon'=>'📖','title'=>'Survey Agent','body'=>'学术综述与文献调研','extra'=>json_encode(['ai_bg'=>'var(--green-100)','active'=>false], JSON_UNESCAPED_UNICODE),'sort'=>4,'published'=>1],
    ['grp'=>'agent_matrix','icon'=>'⚗️','title'=>'Material Agent','body'=>'材料科学与计算化学','extra'=>json_encode(['ai_bg'=>'var(--purple-050)','active'=>false], JSON_UNESCAPED_UNICODE),'sort'=>5,'published'=>1],
    ['grp'=>'agent_matrix','icon'=>'🏥','title'=>'MIMIC Agent','body'=>'临床研究数据分析','extra'=>json_encode(['ai_bg'=>'var(--green-100)','active'=>false], JSON_UNESCAPED_UNICODE),'sort'=>6,'published'=>1],
    ['grp'=>'agent_matrix','icon'=>'🎓','title'=>'SciencePal Agent','body'=>'生成式 AI 课程助手','extra'=>json_encode(['ai_bg'=>'var(--purple-050)','active'=>false,'link_href'=>'https://sciencepal.ai','link_text'=>'了解 SciencePal ↗'], JSON_UNESCAPED_UNICODE),'sort'=>7,'published'=>1],
    ['grp'=>'agent_matrix','icon'=>'➕','title'=>'更多智能体','body'=>'持续接入新场景','extra'=>json_encode(['ai_bg'=>'var(--green-100)','active'=>false], JSON_UNESCAPED_UNICODE),'sort'=>8,'published'=>1],
]);

// ── education 页 snippets（逐字，取自 education.html）──
$S_edu = [
    ['edu.hero.breadcrumb','教育','education','教育页 面包屑','text'],
    ['edu.hero.eyebrow','新型教育科研范式','education','教育页 Hero 眉题','text'],
    ['edu.hero.title','让知识<span class="grad-text">可交互、可溯源、可生长</span>','education','教育页 Hero 标题(含标记)','textarea'],
    ['edu.hero.lead','元视频拆解 + 分层交互式知识图谱 + 实时互动，打通「视频观看 — 弹幕交流 — 图谱梳理 — AI 答疑」完整教学闭环。','education','教育页 Hero 说明','textarea'],
    ['edu.banner.tag','精品课程','education','课程 Banner 标签','text'],
    ['edu.banner.title','《疫苗的力量》','education','课程 Banner 标题','text'],
    ['edu.banner.sub','元视频 + 交互式知识图谱体系 · 精准适配学生、疫苗研发从业者、接种人群与新手父母','education','课程 Banner 副标题','textarea'],
    ['edu.video.eyebrow','1 · 课时播放专区','education','课时播放 眉题','text'],
    ['edu.video.title','元视频点播 + 实时弹幕 + 专属留言区','education','课时播放 标题','text'],
    ['edu.kg.eyebrow','2 · 教学核心工具','education','知识图谱 眉题','text'],
    ['edu.kg.title','分层交互式知识图谱','education','知识图谱 标题','text'],
    ['edu.kg.sub','沉淀全套课程内容、实验 Knowhow 与一线科研经验。点击任一节点查看详情，一键唤起 AI 咨询、跳转对应课程片段。','education','知识图谱 说明','textarea'],
    ['edu.map.eyebrow','学习路径 · 知识地图','education','知识地图 眉题','text'],
    ['edu.map.title','像翻书一样，顺着章节走完抗体与疫苗','education','知识地图 标题','text'],
    ['edu.map.sub','与关系型「知识图谱」互补：图谱看知识点之间的关联，地图给你一条从基础到 AI 抗体发现的学习路径。点开任一章展开小节，可直达对应视频片段或唤起 Antibody Agent。','education','知识地图 说明','textarea'],
    ['edu.res.eyebrow','3 · 配套学习资源区','education','学习资源 眉题','text'],
    ['edu.res.title','课件 · 习题 · 拓展阅读，权限分层收纳','education','学习资源 标题','text'],
    ['edu.res.sub','对外访客开放公开科普素材；组内学生与科研人员登录后可查看内部实操讲义与科研干货。','education','学习资源 说明','textarea'],
    ['edu.link.tag','4 · 跨板块联动','education','跨板块联动 标签','text'],
    ['edu.link.title','图谱 · 弹幕 · 留言，一键唤起 Antibody Agent','education','跨板块联动 标题','text'],
    ['edu.link.body','图谱任意知识点、弹幕/留言内的专业术语均可一键唤起 AI 咨询，同步跳转对应课程片段与专利论文知识库，形成完整教学闭环。','education','跨板块联动 正文','textarea'],
    ['edu.link.cta','体验 Antibody Agent →','education','跨板块联动 按钮','text'],
    ['edu.lecture.eyebrow','学术讲座与教研活动','education','学术讲座 眉题','text'],
    ['edu.lecture.title','从大牛讲座到民间沙龙','education','学术讲座 标题','text'],
    ['edu.grow.eyebrow','学生学术成长资源','education','成长资源 眉题','text'],
    ['edu.grow.title','陪伴科研人从入门到成长','education','成长资源 标题','text'],
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
    ['forum.hot.eyebrow','社区热榜','forum','热榜 眉题','text'],
    ['forum.hot.title','大家这几天都在看什么','forum','热榜 标题','text'],
    ['forum.hot.sub','按互动热度聚合近期高关注的帖子，帮你快速跟上社区正在讨论的话题。','forum','热榜 说明','textarea'],
    ['forum.hot.tab_day','每日热榜','forum','热榜 每日标签','text'],
    ['forum.hot.tab_week','每周热榜','forum','热榜 每周标签','text'],
    ['forum.feed.note','算法 + 编辑推荐 · 刷到啥看啥','forum','推荐流 说明','text'],
    ['forum.feed.loadmore','加载更多推荐 ↓','forum','推荐流 加载更多','text'],
    ['forum.lines.eyebrow','核心内容板块','forum','内容线 眉题','text'],
    ['forum.lines.title','四条内容线，<span class="grad-text">让社区留得住人</span>','forum','内容线 标题(含标记)','textarea'],
    ['forum.follow.eyebrow','关注动态','forum','关注动态 眉题','text'],
    ['forum.follow.title','关注厉害的人，看他们发了啥','forum','关注动态 标题','text'],
    ['forum.follow.body','个人主页展示发过的内容、获赞数与研究方向；关注 PI、资深博后与产业老兵，第一时间看到他们的分享与动态。','forum','关注动态 正文','textarea'],
    ['forum.follow.card_name','张老师','forum','关注卡 姓名','text'],
    ['forum.follow.card_meta','抗体工程 · 12.8k 获赞','forum','关注卡 元信息','text'],
    ['forum.follow.card_btn','关注','forum','关注卡 按钮','text'],
    ['forum.cold.tag','冷启动策略','forum','冷启动 标签','text'],
    ['forum.cold.title','先有内容，再聚人','forum','冷启动 标题','text'],
    ['forum.cold.body','官方团队每周固定产出 5–10 篇高质量内容，选题从高频问题中来。用硬核干货吸引第一批科研人，形成「提问有人答、分享有人看」的正循环。','forum','冷启动 正文','textarea'],
    ['forum.cold.stat1_num','5–10','forum','冷启动 数据1','text'],
    ['forum.cold.stat1_label','每周官方更新','forum','冷启动 数据1标签','text'],
    ['forum.cold.stat2_num','4','forum','冷启动 数据2','text'],
    ['forum.cold.stat2_label','核心内容线','forum','冷启动 数据2标签','text'],
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
    ['about.team.title','两位负责人，AI 与抗体科学的交汇','about','核心团队 标题','text'],
    ['about.team.sub','MabSeek 由抗体与病毒免疫的科学积累，叠加人工智能与大模型能力共同驱动——「AI × 抗体」正是两位负责人研究方向的交汇。','about','核心团队 说明','textarea'],
    ['about.team.disclaimer','注：以上为门户展示模板，详细简历与成果列表参考清华大学医学院官网张林琦、马维英主页，正式上线时同步更新。','about','核心团队 免责说明','textarea'],
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
    ['about.news.chip_edu','育人类','about','新闻筛选 育人类','text'],
    ['about.news.chip_res','科研类','about','新闻筛选 科研类','text'],
    ['about.news.chip_daily','日常活动','about','新闻筛选 日常活动','text'],
    ['about.news.more','了解更多 →','about','新闻活动 按钮','text'],
    // 国际科研合作
    ['about.intl.eyebrow','3 · 国际科研合作','about','国际合作 眉题','text'],
    ['about.intl.title','立足<span class="grad-text">北京市国合基地</span>，联通全球','about','国际合作 标题(含标记)','textarea'],
    ['about.intl.sub','以中印尼深度合作为核心专项，联动 PRA 国际大会与各国合作实验室，推动联合科研与公共卫生合作。','about','国际合作 说明','textarea'],
    ['about.intl.cn_id_title','🌏 核心专项 · 中印尼深度合作','about','中印尼合作 标题','text'],
    ['about.intl.cn_id_body','与印尼 Eijkman 研究所等机构开展联合科研、人才互访、联合培养、学术交流与公共卫生合作，成果持续纪实。','about','中印尼合作 正文','textarea'],
    ['about.intl.tl1_title','联合科研','about','时间线1 标题','text'],
    ['about.intl.tl1_body','共建抗体与疫苗研究课题，共享数据与平台能力','about','时间线1 正文','textarea'],
    ['about.intl.tl2_title','人才互访与联合培养','about','时间线2 标题','text'],
    ['about.intl.tl2_body','互派研究人员与研究生，共育国际化科研人才','about','时间线2 正文','textarea'],
    ['about.intl.tl3_title','公共卫生合作','about','时间线3 标题','text'],
    ['about.intl.tl3_body','面向区域传染病防控的联合攻关与科普','about','时间线3 正文','textarea'],
    ['about.intl.pra_title','🤝 PRA 等国际合作项目','about','PRA 合作 标题','text'],
    ['about.intl.pra_body','参与 PRA 国际联盟与大会，围绕项目背景、合作内容与研究进展持续推进多边科研协作，并积极与国内外高校、科研院所及行业企业探索共建联合实验室的合作机会。','about','PRA 合作 正文','textarea'],
    // 联系我们
    ['about.contact.eyebrow','联系我们','about','联系我们 眉题','text'],
    ['about.contact.title','寻求合作 · 加入我们 · <span class="txt-neon">使用平台</span>','about','联系我们 标题(含标记)','textarea'],
    ['about.contact.sub','contact@mabseek.org · 清华大学医学院','about','联系我们 说明','text'],
];
foreach ($S_about as $s) Snippets::seed($s[0], $s[1], $s[2], $s[3], $s[4] ?? 'text');
echo "  + snippets(about): " . count($S_about) . " seeded (idempotent)\n";

// ── education 页 content_cards（分组幂等，逐字）──
// 课程简介 / 育人理念（education.html .three-col info-card）
seed_cards_group('edu_info', [
    ['grp'=>'edu_info','icon'=>'📘','title'=>'课程简介','body'=>'','extra'=>json_encode(['items'=>['从病毒免疫到疫苗研发的完整知识体系','原版课程完整留存，拆解为独立元视频片段','支持精准点播单个知识点，无需通看全片','视频画面与知识图谱双向跳转溯源'],'ico_style'=>''], JSON_UNESCAPED_UNICODE),'sort'=>1,'published'=>1],
    ['grp'=>'edu_info','icon'=>'💡','title'=>'育人理念','body'=>'','extra'=>json_encode(['items'=>['让科研教育有趣、好玩、可高频使用','知识分层开放，从科普到科研全覆盖','AI 全程陪伴答疑，降低学习门槛','搭建前后辈互助传承的成长生态'],'ico_style'=>''], JSON_UNESCAPED_UNICODE),'sort'=>2,'published'=>1],
]);

// 学术讲座与教研活动（education.html .grid-2 左栏 .card）
seed_cards_group('edu_lecture', [
    ['grp'=>'edu_lecture','icon'=>'🎤','title'=>'官方讲座','body'=>'实验室邀请领域大牛开展抗体工程、疫苗研发、病毒免疫系列专题讲座。','extra'=>'','sort'=>1,'published'=>1],
    ['grp'=>'edu_lecture','icon'=>'☕','title'=>'民间交流沙龙','body'=>'师生自发组织沙龙与教研分享，派博士生参与互动；对接产业方品宣需求，双向共赢。','extra'=>'','sort'=>2,'published'=>1],
    ['grp'=>'edu_lecture','icon'=>'🎬','title'=>'二次传播','body'=>'发起活动可获得资源支持与视频化二次宣传，持续放大传播力量。','extra'=>'','sort'=>3,'published'=>1],
]);

// 学生学术成长资源（education.html .grid-2 右栏 .card）
seed_cards_group('edu_grow', [
    ['grp'=>'edu_grow','icon'=>'🧰','title'=>'成长干货资源库','body'=>'科研工具、写作模板、职业发展、学术成长干货一站汇总。','extra'=>'','sort'=>1,'published'=>1],
    ['grp'=>'edu_grow','icon'=>'✈️','title'=>'会议与培训','body'=>'优质学术会议、青年学者培训、参会补助资源汇总。','extra'=>'','sort'=>2,'published'=>1],
    ['grp'=>'edu_grow','icon'=>'💰','title'=>'资助与基金','body'=>'科研资助、奖学金、基金申请渠道及组内成功经验分享。','extra'=>'','sort'=>3,'published'=>1],
]);
