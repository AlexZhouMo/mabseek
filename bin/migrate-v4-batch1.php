<?php
declare(strict_types=1);
/**
 * migrate-v4-batch1.php —— V4 批 1 存量库订正（幂等）
 *
 * 背景：seed "有数据即跳过"，故本轮 seed 改动只对全新库生效，存量库需就地订正。
 *
 * 三类订正：
 *   1) snippet 定向订正（仅命中已知旧默认值才改，保护后台改过的文案）：
 *      home.hero.eyebrow 保底 / footer.brand.tagline 保底 /
 *      about.intl.cn_id_body / about.intl.pra_body
 *   2) edu_schedule 整表判据："15 条 & 首尾均为新版'第 X 讲 …'"不满足即清空重灌
 *      （沿用 302 迁移思路，将"周/日期/课程队长"整表订正）
 *   3) edu.video.iframe_url snippet 存在性保证（新增字段，缺则插入空默认值）
 *
 * 幂等 & 安全：已迁移 0 改动；不动上传图与后台改过的文案。可反复运行。
 * 用法：php bin/migrate-v4-batch1.php
 */
require_once __DIR__ . '/../app/bootstrap.php';

$pdo = db();
$changed = 0;

echo "Migrating web-update-v4-batch1 (idempotent)...\n";

// 复用 302 已建立的 helper 签名；用 function_exists 守卫，避免与其它进程共载时重复定义
if (!function_exists('mig_fix_snippet_any')) {
    function mig_fix_snippet_any(string $key, array $oldVals, string $newVal, int &$changed): void {
        $cur = Snippets::get($key, '__MISSING__');
        if (in_array($cur, $oldVals, true) && $cur !== $newVal) {
            Snippets::set($key, $newVal);
            $changed++;
            echo "  ~ snippet $key\n";
        }
    }
}

// —— 1) 保底题眉 / footer tagline（V3.0.2/260920 已改过，此处再保险） ——
mig_fix_snippet_any('home.hero.eyebrow',
    ['🧬 清华团队 × AI 大模型', '🧬 清华团队 × AI大模型'],
    '', $changed);

mig_fix_snippet_any('footer.brand.tagline',
    ['清华团队 × AI 大模型，让抗体发现从反复试错变成精准编程。'],
    '让抗体发现从反复试错变成精准编程。', $changed);

// —— 2) 中印尼 / PRA 文案精简为 V4 版 ——
$oldCnIdBody = "中印尼疫苗与基因组联合研发中心是共建\u{201C}21世纪海上丝绸之路\u{201D}卫生健康领域的标杆工程，由清华大学牵头执行，纳入国家科技部对发展中国家科技援助专项，服务东盟区域公共卫生治理。\n中心构建\u{201C}政府+高校+产业\u{201D}协同模式，联合印尼卫生部、国家研究创新署及本土药企，推动登革热、结核等疫苗联合研发，完成技术标准化转移，助力印尼从疫苗进口国向区域研发生产中心转型。\n项目以健康合作深化中印尼战略互信，形成\u{201C}中国技术+本地转化\u{201D}可复制的南南合作范式，为构建人类卫生健康共同体提供实践支撑。";
$newCnIdBody = "中印尼疫苗与基因组联合研发中心由清华大学牵头，纳入国家科技部对发展中国家科技援助专项。中心构建\u{201C}政府+高校+产业\u{201D}协同模式，推动登革热、结核等疫苗联合研发，助力印尼向区域研发生产中心转型，形成\u{201C}中国技术+本地转化\u{201D}的南南合作范式。";
mig_fix_snippet_any('about.intl.cn_id_body',
    [
        $oldCnIdBody,
        // 更早的 seed 老 3 段版（302 之前）——已在 302 迁移中订正过；此处仅覆盖历史散逸库
        '与印尼 Eijkman 研究所等机构开展联合科研、人才互访、联合培养、学术交流与公共卫生合作，成果持续纪实。',
    ],
    $newCnIdBody, $changed);

$oldPraBody = "大流行病研究联盟（PRA）由清华大学张林琦教授联合钟南山、何大一、袁国勇、王林发及 Sharon Lewin 等多国知名专家于 2023 年发起，针对全球防疫难题开展前瞻性前置研究，布局相关产品研发与应急储备，提升疫病预防、诊断、救治应急处置能力，推进国际合作与人才交流，守护全球民众健康。\n联盟现已举办 3 场线下、10 场线上国际研讨会，发展为全球活跃的流行病研究协作网络，依次完成框架搭建、学术交流、成果转化与人才培养的稳步进阶。清华大学将依托自身在基础研究、医工交叉与 AI 学科的综合优势，联合全球合作伙伴，为建设更具韧性、公平性与协同性的全球公共卫生体系贡献清华力量。";
$oldPraBodyCompact = str_replace("\n", '', $oldPraBody);   // 302 之前的连排版
$newPraBody = "大流行病研究联盟由清华大学张林琦教授联合钟南山、何大一等多国专家于2023年发起，开展前瞻性研究，布局产品研发与应急储备。联盟已举办多场国际研讨会，发展为全球活跃的流行病研究协作网络，助力全球公共卫生体系建设。";
mig_fix_snippet_any('about.intl.pra_body',
    [
        $oldPraBody,
        $oldPraBodyCompact,
        // 260920 已知 3 个中间态旧值
        '参与 PRA 国际联盟与大会，围绕项目背景、合作内容与研究进展持续推进多边科研协作，并积极与国内外高校、科研院所及行业企业探索共建联合实验室的合作机会。',
        '参与 PRA 国际联盟与大会，围绕项目背景、合作内容与研究进展持续推进多边科研协作。',
    ],
    $newPraBody, $changed);

// —— 3) edu.video.iframe_url snippet 存在性保证 ——
$exists = (int)$pdo->query("SELECT COUNT(*) FROM snippets WHERE skey='edu.video.iframe_url'")->fetchColumn();
if ($exists === 0) {
    Snippets::seed('edu.video.iframe_url', '', 'education',
        '视频外链嵌入 URL（优先于本地视频，如 //player.bilibili.com/player.html?bvid=xxx 或 https://www.youtube.com/embed/xxx）',
        'text');
    $changed++;
    echo "  + snippet edu.video.iframe_url\n";
}

// —— 4) edu_schedule 整表订正为 V4 版（15 讲，去周/日期/课程队长；订正教师姓名） ——
$rowsSched = $pdo->query("SELECT sort,title,body FROM content_cards WHERE grp='edu_schedule' ORDER BY sort")->fetchAll(PDO::FETCH_ASSOC);
$schedOk = count($rowsSched) === 15
    && ($rowsSched[0]['title']  ?? '') === '第 1 讲 · 绪论：疫苗点亮健康'
    && ($rowsSched[14]['title'] ?? '') === '第 15 讲 · 历史回顾和未来展望'
    && ($rowsSched[0]['body']   ?? '') === '授课：张林琦'
    && ($rowsSched[8]['body']   ?? '') === '授课：袁媛'
    && ($rowsSched[11]['body']  ?? '') === '授课：张纬';
if (!$schedOk) {
    $pdo->exec("DELETE FROM content_cards WHERE grp='edu_schedule'");
    $sched = [
        ['title'=>'第 1 讲 · 绪论：疫苗点亮健康','body'=>'授课：张林琦','sort'=>1],
        ['title'=>'第 2 讲 · 预防接种进展与成就','body'=>'授课：梁晓峰','sort'=>2],
        ['title'=>'第 3 讲 · 疫苗是如何保护我们的？','body'=>'授课：李冠乔','sort'=>3],
        ['title'=>'第 4 讲 · 疫苗是如何研制和质量保障的？','body'=>'授课：李冠乔','sort'=>4],
        ['title'=>'第 5 讲 · 疫苗经济学','body'=>'授课：方海','sort'=>5],
        ['title'=>'第 6 讲 · 疫苗免疫效果评估—实验室的科技魅力','body'=>'授课：史宣玲','sort'=>6],
        ['title'=>'第 7 讲 · 天花疫苗和脊髓灰质炎疫苗-人类疫苗历史的壮举','body'=>'授课：陈志伟','sort'=>7],
        ['title'=>"第 8 讲 · 乙肝疫苗-摘掉我国\u{201C}乙肝大国\u{201D}帽子的功臣",'body'=>'授课：崔富强','sort'=>8],
        ['title'=>'第 9 讲 · 乙脑疫苗-我国第一支通过世卫组织预认证的疫苗','body'=>'授课：袁媛','sort'=>9],
        ['title'=>'第 10 讲 · 人乳头瘤病毒（HPV）疫苗—宫颈癌的克星','body'=>'授课：乔友林','sort'=>10],
        ['title'=>'第 11 讲 · 流感疫苗-以变应变的追逐与纠结','body'=>'授课：冯录召','sort'=>11],
        ['title'=>'第 12 讲 · 呼吸道相关传染病的疫苗','body'=>'授课：张纬','sort'=>12],
        ['title'=>'第 13 讲 · 治疗性疫苗','body'=>'授课：傅阳心','sort'=>13],
        ['title'=>'第 14 讲 · 疫苗与全球健康','body'=>'授课：杜珩','sort'=>14],
        ['title'=>'第 15 讲 · 历史回顾和未来展望','body'=>'授课：张林琦','sort'=>15],
    ];
    $cc = new Collection('content_cards');
    foreach ($sched as $s) {
        $cc->create(['grp'=>'edu_schedule','icon'=>'','title'=>$s['title'],'body'=>$s['body'],'extra'=>'','sort'=>$s['sort'],'published'=>1]);
    }
    $changed += count($sched);
    echo "  ~ edu_schedule 重灌 15 条\n";
}

echo "Done. changed=$changed\n";
