<?php
declare(strict_types=1);
/**
 * migrate-web-update-302.php —— V3.0.2 网页更新的存量库订正（幂等）
 *
 * 背景：生产库 data/ 部署时保留、seed “有数据即跳过”，故本轮 seed 改动只对全新库生效，
 *       存量库需就地订正，否则线上出现旧 logo 错位/旧文案/教学安排不全。
 *
 * 三类订正/补齐：
 *   1) partners：若不满足“正确映射指纹”则清空重灌 17 条正确记录。
 *   2) snippet 定向订正（仅当现值命中已知旧默认值才改，保护后台改过的文案）：
 *      edu.course.intro / edu.teachers.intro / about.intl.cn_id_title / cn_id_body / pra_title / pra_body
 *   3) edu_schedule 补齐到 15 条（该 grp 记录数 < 15 才补）。
 *
 * 幂等 & 安全：已迁移 0 改动；不动用户上传图与后台改过的文案。可反复运行。
 * 用法：php bin/migrate-web-update-302.php
 */
require_once __DIR__ . '/../app/bootstrap.php';

$pdo = db();
$changed = 0;

echo "Migrating web-update-302 (idempotent)...\n";

function mig_fix_snippet_any(string $key, array $oldVals, string $newVal, int &$changed): void {
    $cur = Snippets::get($key, '__MISSING__');
    if (in_array($cur, $oldVals, true) && $cur !== $newVal) {
        Snippets::set($key, $newVal);
        $changed++;
        echo "  ~ snippet $key\n";
    }
}

// —— 1) partners 校准 ——
$rows = $pdo->query("SELECT sort,name,logo_image FROM partners ORDER BY sort")->fetchAll(PDO::FETCH_ASSOC);
$byS = [];
foreach ($rows as $r) $byS[(int)$r['sort']] = $r;
$correct =
    isset($byS[3])  && $byS[3]['name']==='PRA 国际联盟' && strpos((string)$byS[3]['logo_image'],'p03.webp')!==false &&
    isset($byS[15]) && $byS[15]['name']==='revvity'     && strpos((string)$byS[15]['logo_image'],'p15.webp')!==false &&
    isset($byS[9])  && $byS[9]['name']==='LPDP'          && strpos((string)$byS[9]['logo_image'],'p09.webp')!==false &&
    isset($byS[6])  && $byS[6]['name']==='中国医学科学院血液学研究所·血液病医院' &&
    isset($byS[7])  && $byS[7]['name']==='中国医学科学院北京协和医学院' &&
    count($byS) >= 17;
if (!$correct) {
    $pdo->exec('DELETE FROM partners');
    $partners = [
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
    ];
    $pc = new Collection('partners');
    foreach ($partners as $p) $pc->create($p);
    $changed += count($partners);
    echo "  ~ partners 重灌 17 条\n";
}

// —— 2) snippet 定向订正 ——
mig_fix_snippet_any('edu.course.intro',
    ['本课程立足清华科研一线，以真实案例为切入点，深度解析疫苗的历史演变、科学逻辑与全球治理。课程融合理论教学、案例剖析、企业调研与实验实践，旨在点亮抗体与疫苗研发兴趣，培养兼具家国情怀与国际视野的复合型领军人才。'],
    "本课程立足清华科研一线，以真实案例为切入点，深度解析疫苗的历史演变、科学逻辑与全球治理。\n课程融合理论教学、案例剖析、企业调研与实验实践，旨在点亮抗体与疫苗研发兴趣，培养兼具家国情怀与国际视野的复合型领军人才。",
    $changed);

mig_fix_snippet_any('edu.teachers.intro',
    ['课程组将邀请疫苗研发一线的专家教授参与授课，介绍其所在专业领域中真实的疫苗研发历程和真实的科研经历故事。同时授课教师还包括从事疫苗监管、政策制定和疫苗治理的专家，帮助学生充分了解疫苗如何从实验室走向人群普遍接种的艰苦心路历程，以及科学家们在利用疫苗实现人类健康这一目标上的矢志追求。'],
    "课程组将邀请疫苗研发一线的专家教授参与授课，介绍其所在专业领域中真实的疫苗研发历程和真实的科研经历故事。\n同时授课教师还包括从事疫苗监管、政策制定和疫苗治理的专家，帮助学生充分了解疫苗如何从实验室走向人群普遍接种的艰苦心路历程，以及科学家们在利用疫苗实现人类健康这一目标上的矢志追求。",
    $changed);

mig_fix_snippet_any('about.intl.cn_id_title',
    ['🌏 核心专项 · 中印尼深度合作'],
    '中印尼疫苗与基因组联合研发中心', $changed);

mig_fix_snippet_any('about.intl.cn_id_body',
    ['与印尼 Eijkman 研究所等机构开展联合科研、人才互访、联合培养、学术交流与公共卫生合作，成果持续纪实。'],
    "中印尼疫苗与基因组联合研发中心是共建“21世纪海上丝绸之路”卫生健康领域的标杆工程，由清华大学牵头执行，纳入国家科技部对发展中国家科技援助专项，服务东盟区域公共卫生治理。\n中心构建“政府+高校+产业”协同模式，联合印尼卫生部、国家研究创新署及本土药企，推动登革热、结核等疫苗联合研发，完成技术标准化转移，助力印尼从疫苗进口国向区域研发生产中心转型。\n项目以健康合作深化中印尼战略互信，形成“中国技术+本地转化”可复制的南南合作范式，为构建人类卫生健康共同体提供实践支撑。",
    $changed);

mig_fix_snippet_any('about.intl.pra_title',
    ['🤝 PRA 等国际合作项目'],
    '大流行病研究联盟（PRA）', $changed);

mig_fix_snippet_any('about.intl.pra_body',
    ['大流行病研究联盟（PRA）由清华大学张林琦教授联合钟南山、何大一、袁国勇、王林发及 Sharon Lewin 等多国知名专家于 2023 年发起，针对全球防疫难题开展前瞻性前置研究，布局相关产品研发与应急储备，提升疫病预防、诊断、救治应急处置能力，推进国际合作与人才交流，守护全球民众健康。联盟现已举办 3 场线下、10 场线上国际研讨会，发展为全球活跃的流行病研究协作网络，依次完成框架搭建、学术交流、成果转化与人才培养的稳步进阶。清华大学将依托自身在基础研究、医工交叉与 AI 学科的综合优势，联合全球合作伙伴，为建设更具韧性、公平性与协同性的全球公共卫生体系贡献清华力量。'],
    "大流行病研究联盟（PRA）由清华大学张林琦教授联合钟南山、何大一、袁国勇、王林发及 Sharon Lewin 等多国知名专家于 2023 年发起，针对全球防疫难题开展前瞻性前置研究，布局相关产品研发与应急储备，提升疫病预防、诊断、救治应急处置能力，推进国际合作与人才交流，守护全球民众健康。\n联盟现已举办 3 场线下、10 场线上国际研讨会，发展为全球活跃的流行病研究协作网络，依次完成框架搭建、学术交流、成果转化与人才培养的稳步进阶。清华大学将依托自身在基础研究、医工交叉与 AI 学科的综合优势，联合全球合作伙伴，为建设更具韧性、公平性与协同性的全球公共卫生体系贡献清华力量。",
    $changed);

// —— 3) edu_schedule 订正为 15 周（就地重灌）——
// 判据不能用 “count < 15”：部署时 seed 先于本迁移运行，seed_cards_group 对已有旧
// 记录（旧 3 讲）是“补齐”语义（不删旧的直接追加 15 条 → 18 条新旧混杂），本迁移必须能纠正。
// 故判据改为：不满足“恰好 15 条且首条为新版‘第 1 周 …’格式”即清空重灌，天然幂等。
$rowsSched = $pdo->query("SELECT sort,title FROM content_cards WHERE grp='edu_schedule' ORDER BY sort")->fetchAll(PDO::FETCH_ASSOC);
$schedOk = count($rowsSched) === 15
    && ($rowsSched[0]['title'] ?? '') === '第 1 周 · 2026/09/15 · 绪论：疫苗点亮健康'
    && ($rowsSched[14]['title'] ?? '') === '第 15 周 · 2026/12/29 · 历史回顾和未来展望';
if (!$schedOk) {
    $pdo->exec("DELETE FROM content_cards WHERE grp='edu_schedule'");
    $sched = [
        ['title'=>'第 1 周 · 2026/09/15 · 绪论：疫苗点亮健康','body'=>'授课：张林琦｜课程队长：李晨雨','sort'=>1],
        ['title'=>'第 2 周 · 2026/09/22 · 预防接种进展与成就','body'=>'授课：梁晓峰｜课程队长：杨依凌','sort'=>2],
        ['title'=>'第 3 周 · 2026/09/29 · 疫苗是如何保护我们的？','body'=>'授课：李冠乔｜课程队长：范欣雨','sort'=>3],
        ['title'=>'第 4 周 · 2026/10/13 · 疫苗是如何研制和质量保障的？','body'=>'授课：李冠乔｜课程队长：范欣雨','sort'=>4],
        ['title'=>'第 5 周 · 2026/10/20 · 疫苗经济学','body'=>'授课：方海｜课程队长：杨屿涵','sort'=>5],
        ['title'=>'第 6 周 · 2026/10/27 · 疫苗免疫效果评估 —实验室的科技魅力','body'=>'授课：史宣玲｜课程队长：傅莉莉','sort'=>6],
        ['title'=>'第 7 周 · 2026/11/3 · 天花疫苗和脊髓灰质炎疫苗-人类疫苗历史的壮举','body'=>'授课：陈志伟｜课程队长：魏婧','sort'=>7],
        ['title'=>'第 8 周 · 2026/11/10 · 乙肝疫苗-摘掉我国“乙肝大国”帽子的功臣','body'=>'授课：崔富强｜课程队长：王枭','sort'=>8],
        ['title'=>'第 9 周 · 2026/11/17 · 乙脑疫苗-我国第一支通过世卫组织预认证的疫苗','body'=>'授课：袁瑷｜课程队长：朱坤','sort'=>9],
        ['title'=>'第 10 周 · 2026/11/24 · 人乳头瘤病毒（HPV）疫苗—宫颈癌的克星','body'=>'授课：乔友林｜课程队长：曾一歌','sort'=>10],
        ['title'=>'第 11 周 · 2026/12/01 · 流感疫苗-以变应变的追逐与纠结','body'=>'授课：冯录召｜课程队长：魏子萌','sort'=>11],
        ['title'=>'第 12 周 · 2026/12/08 · 呼吸道相关传染病的疫苗','body'=>'授课：张绮｜课程队长：黄灿','sort'=>12],
        ['title'=>'第 13 周 · 2026/12/15 · 治疗性疫苗','body'=>'授课：傅阳心｜课程队长：郑玉娟','sort'=>13],
        ['title'=>'第 14 周 · 2026/12/22 · 疫苗与全球健康','body'=>'授课：杜珩｜课程队长：杨芊芊','sort'=>14],
        ['title'=>'第 15 周 · 2026/12/29 · 历史回顾和未来展望','body'=>'授课：张林琦｜课程队长：谭睿洁','sort'=>15],
    ];
    $cc = new Collection('content_cards');
    foreach ($sched as $s) {
        $cc->create(['grp'=>'edu_schedule','icon'=>'','title'=>$s['title'],'body'=>$s['body'],'extra'=>'','sort'=>$s['sort'],'published'=>1]);
    }
    $changed += count($sched);
    echo "  ~ edu_schedule 重灌 15 条\n";
}

echo "Done. changed=$changed\n";
