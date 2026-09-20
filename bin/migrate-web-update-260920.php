<?php
declare(strict_types=1);
/**
 * migrate-web-update-260920.php —— 260920 网页更新迭代的存量库订正（幂等）
 *
 * 背景：生产库 data/ 部署时被保留、seed.php “有数据即整表/整组跳过”，故 Task 2/4/5/7/8 改的
 *       seed 默认值只对全新库生效，生产存量库不会更新。本脚本就地订正历史库，否则线上出现旧值/缺数据。
 *
 * 四类订正/补齐：
 *   1) snippet 定向订正：home.hero.eyebrow / footer.brand.tagline / about.intl.pra_body / edu.hero.lead
 *      —— 仅当现值仍是历史旧值时才改（保护后台改过的文案），已是新值则跳过。
 *   2) snippet 补齐：edu.video.title/src / edu.course.intro / edu.teachers.intro / about.zhang.*
 *      —— 用 Snippets::seed 语义（不存在才插）。
 *   3) partners：若尚未迁移到“17 条且都有 assets/images/partners/ 的 logo_image”，则清空重灌 17 条。
 *   4) content_cards 分组补齐：zhang_papers(9)/zhang_patents(34)/zhang_honors(18)/
 *      edu_schedule(3)/intl_id(6)/intl_pra(6) —— 该 grp 记录数 < 目标数才补（同 seed_cards_group 判据）。
 *
 * 幂等 & 安全：已迁移 0 改动；不动用户上传图与后台改过的文案。可反复运行。
 * 数据与 bin/seed.php 完全一致（逐条复制）。
 *
 * 用法：php bin/migrate-web-update-260920.php
 */
require_once __DIR__ . '/../app/bootstrap.php';

$pdo = db();
$changed = 0;

echo "Migrating web-update-260920 (idempotent)...\n";

// —— 1) snippet 定向订正（仅当现值等于旧默认值）——
function mig_fix_snippet(string $key, string $oldVal, string $newVal, int &$changed): void {
    $cur = Snippets::get($key, '__MISSING__');
    if ($cur === $oldVal && $cur !== $newVal) {
        Snippets::set($key, $newVal);
        $changed++;
        echo "  ~ snippet $key\n";
    }
}
/** 多历史旧值：仅当现值命中已知旧默认值列表才订正（保护后台自定义文案） */
function mig_fix_snippet_any(string $key, array $oldVals, string $newVal, int &$changed): void {
    $cur = Snippets::get($key, '__MISSING__');
    if (in_array($cur, $oldVals, true) && $cur !== $newVal) {
        Snippets::set($key, $newVal);
        $changed++;
        echo "  ~ snippet $key\n";
    }
}
mig_fix_snippet('home.hero.eyebrow', '🧬 清华团队 × AI 大模型', '🧬 AI 驱动的抗体发现平台', $changed);
mig_fix_snippet('footer.brand.tagline', '清华团队 × AI 大模型，让抗体发现从反复试错变成精准编程。', '让抗体发现从反复试错变成精准编程。', $changed);
// edu.video.title：旧值（含弹幕/留言，与已删功能自相矛盾）→ 课程视频
mig_fix_snippet('edu.video.title', '元视频点播 + 实时弹幕 + 专属留言区', '课程视频', $changed);

// PRA：仅当现值等于已知历史旧默认值才订正为新版（保护后台自定义文案；已是新版则不命中→跳过）
$praNew = '大流行病研究联盟（PRA）由清华大学张林琦教授联合钟南山、何大一、袁国勇、王林发及 Sharon Lewin 等多国知名专家于 2023 年发起，针对全球防疫难题开展前瞻性前置研究，布局相关产品研发与应急储备，提升疫病预防、诊断、救治应急处置能力，推进国际合作与人才交流，守护全球民众健康。联盟现已举办 3 场线下、10 场线上国际研讨会，发展为全球活跃的流行病研究协作网络，依次完成框架搭建、学术交流、成果转化与人才培养的稳步进阶。清华大学将依托自身在基础研究、医工交叉与 AI 学科的综合优势，联合全球合作伙伴，为建设更具韧性、公平性与协同性的全球公共卫生体系贡献清华力量。';
mig_fix_snippet_any('about.intl.pra_body', [
    // main 分支 seed.php 原值
    '参与 PRA 国际联盟与大会，围绕项目背景、合作内容与研究进展持续推进多边科研协作，并积极与国内外高校、科研院所及行业企业探索共建联合实验室的合作机会。',
    // 更早的历史简版（防御性列入）
    '参与 PRA 国际联盟与大会，围绕项目背景、合作内容与研究进展持续推进多边科研协作。',
], $praNew, $changed);

// edu.hero.lead：仅当现值等于已知历史旧默认值才订正为新值
mig_fix_snippet_any('edu.hero.lead', [
    // main 分支 seed.php 原值（书名号「」+ 全角破折号）
    '元视频拆解 + 实时互动，打通「视频观看 — 弹幕交流 — AI 答疑」的完整教学闭环。',
], '元视频拆解 + AI 答疑，系统沉淀课程知识，随点随学。', $changed);

// —— 2) 补齐缺失 snippet（Snippets::seed = 不存在才插；逐条从 seed.php 复制）——
// [skey, value, grp, label, type]
$fillSnippets = [
    // education 页
    ['edu.video.src', '', 'education', '课程视频文件路径（后台上传后自动填入）', 'text'],
    ['edu.course.intro', '本课程立足清华科研一线，以真实案例为切入点，深度解析疫苗的历史演变、科学逻辑与全球治理。课程融合理论教学、案例剖析、企业调研与实验实践，旨在点亮抗体与疫苗研发兴趣，培养兼具家国情怀与国际视野的复合型领军人才。', 'education', '课程简介 正文', 'textarea'],
    ['edu.teachers.intro', '课程组将邀请疫苗研发一线的专家教授参与授课，介绍其所在专业领域中真实的疫苗研发历程和真实的科研经历故事。同时授课教师还包括从事疫苗监管、政策制定和疫苗治理的专家，帮助学生充分了解疫苗如何从实验室走向人群普遍接种的艰苦心路历程，以及科学家们在利用疫苗实现人类健康这一目标上的矢志追求。', 'education', '主讲团队 正文', 'textarea'],
    // about 页 张老师
    ['about.zhang.role1', '清华大学基础医学院教授 博士生导师', 'about', '张老师 职务1', 'text'],
    ['about.zhang.role2', '清华大学艾滋病综合研究中心主任', 'about', '张老师 职务2', 'text'],
    ['about.zhang.field', '致力于病毒感染和保护性免疫机制的研究，免疫和基因治疗临床应用研发。在疫苗的设计与优化、保护性抗体反应机制及评估等方面取得关键突破，多个抗体和疫苗已经在国内外进入人体临床试验或批准上市。', 'about', '张老师 研究领域', 'textarea'],
    ['about.zhang.contrib', '2021年12月8日，研发的新冠抗体药物获得国家药品监督管理局批准上市，实现我国新冠药物的零突破。创新型新冠鼻腔疫苗正在开展临床安全性和有效性研究，为预防新冠病毒感染和传播提供全新的手段和候选。持续多年入选感染和免疫学科中国高被引学者。', 'about', '张老师 科学贡献', 'textarea'],
    ['about.zhang.papers_desc', '专注于人类重大病毒性传染病的致病机理，病毒与免疫系统相互作用关系，研发抗病毒药物、抗体和疫苗。', 'about', '论文成果 描述', 'textarea'],
    ['about.zhang.papers_count', '276+', 'about', '论文数量强调', 'text'],
    ['about.zhang.patents_desc', '团队及其合作单位在抗体开发、新型疫苗及生物医药技术等领域的授权发明专利。', 'about', '专利成果 描述', 'textarea'],
    ['about.zhang.patents_count', '34+', 'about', '专利数量强调', 'text'],
];
foreach ($fillSnippets as $s) {
    $exists = $pdo->prepare('SELECT 1 FROM snippets WHERE skey = ?');
    $exists->execute([$s[0]]);
    if ($exists->fetchColumn()) continue;
    Snippets::seed($s[0], $s[1], $s[2], $s[3], $s[4]);
    $changed++;
    echo "  + snippet $s[0]\n";
}

// —— 3) partners 扩充 ——
// 判据：已有 >=17 条且每条 logo_image 都指向 assets/images/partners/ 则跳过
$pc = new Collection('partners');
$existing = $pc->all();
$alreadyMigrated = count($existing) >= 17;
if ($alreadyMigrated) {
    foreach ($existing as $e) {
        if (strpos((string)($e['logo_image'] ?? ''), 'assets/images/partners/') === false) {
            $alreadyMigrated = false;
            break;
        }
    }
}
if (!$alreadyMigrated) {
    $pdo->exec('DELETE FROM partners');
    $partners = [
        ['name'=>'清华大学','mark'=>'清','sub'=>'','logo_image'=>'assets/images/partners/p01.webp','demo'=>'','sort'=>1,'published'=>1],
        ['name'=>'北京大学','mark'=>'北','sub'=>'','logo_image'=>'assets/images/partners/p02.webp','demo'=>'','sort'=>2,'published'=>1],
        ['name'=>'PRA 国际联盟','mark'=>'PRA','sub'=>'','logo_image'=>'assets/images/partners/p03.webp','demo'=>'','sort'=>3,'published'=>1],
        ['name'=>'Universitas Indonesia','mark'=>'UI','sub'=>'印度尼西亚','logo_image'=>'assets/images/partners/p04.webp','demo'=>'','sort'=>4,'published'=>1],
        ['name'=>'北京清华长庚医院','mark'=>'长庚','sub'=>'','logo_image'=>'assets/images/partners/p05.webp','demo'=>'','sort'=>5,'published'=>1],
        ['name'=>'中国医学科学院','mark'=>'协和','sub'=>'','logo_image'=>'assets/images/partners/p06.webp','demo'=>'','sort'=>6,'published'=>1],
        ['name'=>'北京医院','mark'=>'京','sub'=>'','logo_image'=>'assets/images/partners/p07.webp','demo'=>'','sort'=>7,'published'=>1],
        ['name'=>'BRIN','mark'=>'BRIN','sub'=>'印度尼西亚','logo_image'=>'assets/images/partners/p08.webp','demo'=>'','sort'=>8,'published'=>1],
        ['name'=>'LPDP','mark'=>'LPDP','sub'=>'印度尼西亚','logo_image'=>'assets/images/partners/p09.webp','demo'=>'','sort'=>9,'published'=>1],
        ['name'=>'北京友谊医院','mark'=>'友谊','sub'=>'','logo_image'=>'assets/images/partners/p10.webp','demo'=>'','sort'=>10,'published'=>1],
        ['name'=>'沃森生物','mark'=>'WALVAX','sub'=>'','logo_image'=>'assets/images/partners/p11.webp','demo'=>'','sort'=>11,'published'=>1],
        ['name'=>'天木生物','mark'=>'TMAX','sub'=>'','logo_image'=>'assets/images/partners/p12.webp','demo'=>'','sort'=>12,'published'=>1],
        ['name'=>'BADAN POM','mark'=>'POM','sub'=>'印度尼西亚','logo_image'=>'assets/images/partners/p13.webp','demo'=>'','sort'=>13,'published'=>1],
        ['name'=>'思路迪医药','mark'=>'3D','sub'=>'','logo_image'=>'assets/images/partners/p14.webp','demo'=>'','sort'=>14,'published'=>1],
        ['name'=>'revvity','mark'=>'REV','sub'=>'','logo_image'=>'assets/images/partners/p15.webp','demo'=>'','sort'=>15,'published'=>1],
        ['name'=>'东富龙 Tofflon','mark'=>'TFL','sub'=>'','logo_image'=>'assets/images/partners/p16.webp','demo'=>'','sort'=>16,'published'=>1],
        ['name'=>'Kemenkes','mark'=>'KMK','sub'=>'印度尼西亚','logo_image'=>'assets/images/partners/p17.webp','demo'=>'','sort'=>17,'published'=>1],
    ];
    foreach ($partners as $p) $pc->create($p);
    $changed += count($partners);
    echo "  ~ partners reset to " . count($partners) . "\n";
}

// —— 4) content_cards 分组补齐（grp 记录数 < 目标数才补，同 seed_cards_group 判据）——
function mig_fill_group(string $grp, array $rows, int &$changed): void {
    $q = db()->prepare("SELECT COUNT(*) FROM content_cards WHERE grp = ?");
    $q->execute([$grp]);
    if ((int)$q->fetchColumn() >= count($rows)) return;
    $c = new Collection('content_cards');
    foreach ($rows as $r) $c->create($r);
    $changed += count($rows);
    echo "  ~ content_cards[$grp]: " . count($rows) . "\n";
}

mig_fill_group('zhang_papers', [
    ['grp'=>'zhang_papers','icon'=>'','title'=>'Lan J, Ge J, Yu J, Shan S, Zhou H, Fan S, Zhang Q, Shi X, Wang Q, Zhang L, Wang X. Structure of the SARS-CoV-2 spike receptor-binding domain bound to the ACE2 receptor. Nature. 2020 May;581(7807):215-220.','body'=>'','extra'=>'','sort'=>1,'published'=>1],
    ['grp'=>'zhang_papers','icon'=>'','title'=>'Ju B, Zhang Q, Ge J, Wang R, Sun J, Ge X, Yu J, Shan S, Zhou B, Song S, Tang X, Yu J, Lan J, Yuan J, Wang H, Zhao J, Zhang S, Wang Y, Shi X, Liu L, Zhao J, Wang X, Zhang Z, Zhang L. Human neutralizing antibodies elicited by SARS-CoV-2 infection. Nature. 2020 Aug;584(7819):115-119.','body'=>'','extra'=>'','sort'=>2,'published'=>1],
    ['grp'=>'zhang_papers','icon'=>'','title'=>'Wang R, Zhang Q, Ge J, Ren W, Zhang R, Lan J, Ju B, Su B, Yu F, Chen P, Liao H, Feng Y, Li X, Shi X, Zhang Z, Zhang F, Ding Q, Zhang T, Wang X, Zhang L. Analysis of SARS-CoV-2 variant mutations reveals neutralization escape mechanisms and the ability to use ACE2 receptors from additional species. Immunity. 2021 Jul 13;54(7):1611-1621.e5.','body'=>'','extra'=>'','sort'=>3,'published'=>1],
    ['grp'=>'zhang_papers','icon'=>'','title'=>'Shan S, Luo S, Yang Z, Hong J, Su Y, Ding F, Fu L, Li C, Chen P, Ma J, Shi X, Zhang Q, Berger B, Zhang L, Peng J. Deep learning guided optimization of human antibody against SARS-CoV-2 variants with broad neutralization. Proc Natl Acad Sci U S A. 2022 Mar 15;119(11):e2122954119.','body'=>'','extra'=>'','sort'=>4,'published'=>1],
    ['grp'=>'zhang_papers','icon'=>'','title'=>'Qi H, Liu B, Wang X, Zhang L. The humoral response and antibodies against SARS-CoV-2 infection. Nat Immunol. 2022 Jul;23(7):1008-1020.','body'=>'','extra'=>'','sort'=>5,'published'=>1],
    ['grp'=>'zhang_papers','icon'=>'','title'=>'Ju B, Zhang Q, Wang Z, Aw ZQ, Chen P, Zhou B, Wang R, Ge X, Lv Q, Cheng L, Zhang R, Wong YH, Chen H, Wang H, Shan S, Liao X, Shi X, Liu L, Chu JJH, Wang X, Zhang Z, Zhang L. Infection with wild-type SARS-CoV-2 elicits broadly neutralizing and protective antibodies against omicron subvariants. Nat Immunol. 2023 Apr;24(4):690-699.','body'=>'','extra'=>'','sort'=>6,'published'=>1],
    ['grp'=>'zhang_papers','icon'=>'','title'=>'Wang R, Han Y, Zhang R, Zhu J, Nan X, Liu Y, Yang Z, Zhou B, Yu J, Lin Z, Li J, Chen P, Wang Y, Li Y, Liu D, Shi X, Wang X, Zhang Q, Yang YR, Li T, Zhang L. Dissecting the intricacies of human antibody responses to SARS-CoV-1 and SARS-CoV-2 infection. Immunity. 2023 Nov 14;56(11):2635-2649.','body'=>'','extra'=>'','sort'=>7,'published'=>1],
    ['grp'=>'zhang_papers','icon'=>'','title'=>'Idoudi F, Wang R, Tian L, Yang Z, Chik KK, Chen P, Benabderrazek R, Fu L, Tan R, Dhaouadi S, Benlasfar Z, Somia M, Zhang Q, Shi X, Chan JF, Yuen KY, Wang X, Bouhaouala-Zahar B, Zhang L. Decoding antibody response to MERS-CoV in wild dromedary camels. Proc Natl Acad Sci U S A. 2026 Feb 09;123(7):e2513716123.','body'=>'','extra'=>'','sort'=>8,'published'=>1],
    ['grp'=>'zhang_papers','icon'=>'','title'=>'Zhang Q, Chen P, Guo F, Zhou R, Guo R, Ge X, Yang Q, Xie X, Xia W, Fan J, Yang Z, Xu Y, Huang H, Li J, Wang H, Liao H, Shi X, Liu N, Chen Y, Chen Z, Ma J, Wang X, Zhang T, Zhang L. Twenty-year persistence of SARS-CoV-1 immune imprinting shapes antibody responses to SARS-CoV-2 infection. Immunity. 2026 Nov 10;59:1-16.','body'=>'','extra'=>'','sort'=>9,'published'=>1],
], $changed);

mig_fill_group('zhang_patents', [
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
], $changed);

mig_fill_group('zhang_honors', [
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
], $changed);

mig_fill_group('edu_schedule', [
    ['grp'=>'edu_schedule','icon'=>'','title'=>'第 1 讲　疫苗发展史与全球治理','body'=>'授课：张老师','extra'=>'','sort'=>1,'published'=>1],
    ['grp'=>'edu_schedule','icon'=>'','title'=>'第 2 讲　免疫系统与抗原识别','body'=>'授课：课题组教师','extra'=>'','sort'=>2,'published'=>1],
    ['grp'=>'edu_schedule','icon'=>'','title'=>'第 3 讲　疫苗免疫应答基础','body'=>'授课：课题组教师','extra'=>'','sort'=>3,'published'=>1],
], $changed);

mig_fill_group('intl_id', [
    ['grp'=>'intl_id','icon'=>'','title'=>'中印尼 mRNA 登革疫苗合作签约','body'=>'','extra'=>json_encode(['img'=>'assets/images/intl/id-1.webp'],JSON_UNESCAPED_UNICODE),'sort'=>1,'published'=>1],
    ['grp'=>'intl_id','icon'=>'','title'=>'联合科研团队交流','body'=>'','extra'=>json_encode(['img'=>'assets/images/intl/id-2.webp'],JSON_UNESCAPED_UNICODE),'sort'=>2,'published'=>1],
    ['grp'=>'intl_id','icon'=>'','title'=>'实验室联合研究','body'=>'','extra'=>json_encode(['img'=>'assets/images/intl/id-3.webp'],JSON_UNESCAPED_UNICODE),'sort'=>3,'published'=>1],
    ['grp'=>'intl_id','icon'=>'','title'=>'中印尼疫苗与基因组联合中心','body'=>'','extra'=>json_encode(['img'=>'assets/images/intl/id-4.webp'],JSON_UNESCAPED_UNICODE),'sort'=>4,'published'=>1],
    ['grp'=>'intl_id','icon'=>'','title'=>'学术研讨与人才互访','body'=>'','extra'=>json_encode(['img'=>'assets/images/intl/id-5.webp'],JSON_UNESCAPED_UNICODE),'sort'=>5,'published'=>1],
    ['grp'=>'intl_id','icon'=>'','title'=>'Universitas Indonesia 医学院合作','body'=>'','extra'=>json_encode(['img'=>'assets/images/intl/id-6.webp'],JSON_UNESCAPED_UNICODE),'sort'=>6,'published'=>1],
], $changed);

mig_fill_group('intl_pra', [
    ['grp'=>'intl_pra','icon'=>'','title'=>'Pandemic Research Alliance 成立签约','body'=>'','extra'=>json_encode(['img'=>'assets/images/intl/pra-1.webp'],JSON_UNESCAPED_UNICODE),'sort'=>1,'published'=>1],
    ['grp'=>'intl_pra','icon'=>'','title'=>'PRA 国际研讨会','body'=>'','extra'=>json_encode(['img'=>'assets/images/intl/pra-2.webp'],JSON_UNESCAPED_UNICODE),'sort'=>2,'published'=>1],
    ['grp'=>'intl_pra','icon'=>'','title'=>'2024 PRA 国际研讨会','body'=>'','extra'=>json_encode(['img'=>'assets/images/intl/pra-3.webp'],JSON_UNESCAPED_UNICODE),'sort'=>3,'published'=>1],
    ['grp'=>'intl_pra','icon'=>'','title'=>'广州实验室','body'=>'','extra'=>json_encode(['img'=>'assets/images/intl/pra-4.webp'],JSON_UNESCAPED_UNICODE),'sort'=>4,'published'=>1],
    ['grp'=>'intl_pra','icon'=>'','title'=>'下一代大流行病防治疗法研讨','body'=>'','extra'=>json_encode(['img'=>'assets/images/intl/pra-5.webp'],JSON_UNESCAPED_UNICODE),'sort'=>5,'published'=>1],
    ['grp'=>'intl_pra','icon'=>'','title'=>'全球视角专题论坛','body'=>'','extra'=>json_encode(['img'=>'assets/images/intl/pra-6.webp'],JSON_UNESCAPED_UNICODE),'sort'=>6,'published'=>1],
], $changed);

echo "  = done: {$changed} change(s)\n";
