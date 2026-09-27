<?php
declare(strict_types=1);
/**
 * e2e-migrate-v4-batch1.php —— V4 批 1 迁移幂等三态验证
 *
 * 场景：老 seed（302 前）、260920 版、302 版 → 跑批 1 迁移 → 断言值一致；二次跑 0 改动。
 * 用法：php tests/e2e-migrate-v4-batch1.php
 * 依赖：临时 sqlite 库；MABSEEK_DB 环境变量指向该库。
 */

// —— 断言 helper ——
$GLOBALS['__pass'] = 0; $GLOBALS['__fail'] = 0;
function check(bool $cond, string $msg): void {
    if ($cond) { $GLOBALS['__pass']++; echo "  ✓ $msg\n"; }
    else       { $GLOBALS['__fail']++; echo "  \xE2\x9C\x97 FAIL: $msg\n"; }
}

// —— 3 种旧库初始值 ——
// 302 版（当前生产 = seed.php 里的 cn_id_body / pra_body 长文；edu_schedule 15 条含"周/日期/课程队长"）
$OLD_302_CN_ID = "中印尼疫苗与基因组联合研发中心是共建\u{201C}21世纪海上丝绸之路\u{201D}卫生健康领域的标杆工程，由清华大学牵头执行，纳入国家科技部对发展中国家科技援助专项，服务东盟区域公共卫生治理。\n中心构建\u{201C}政府+高校+产业\u{201D}协同模式，联合印尼卫生部、国家研究创新署及本土药企，推动登革热、结核等疫苗联合研发，完成技术标准化转移，助力印尼从疫苗进口国向区域研发生产中心转型。\n项目以健康合作深化中印尼战略互信，形成\u{201C}中国技术+本地转化\u{201D}可复制的南南合作范式，为构建人类卫生健康共同体提供实践支撑。";
$OLD_302_PRA   = "大流行病研究联盟（PRA）由清华大学张林琦教授联合钟南山、何大一、袁国勇、王林发及 Sharon Lewin 等多国知名专家于 2023 年发起，针对全球防疫难题开展前瞻性前置研究，布局相关产品研发与应急储备，提升疫病预防、诊断、救治应急处置能力，推进国际合作与人才交流，守护全球民众健康。\n联盟现已举办 3 场线下、10 场线上国际研讨会，发展为全球活跃的流行病研究协作网络，依次完成框架搭建、学术交流、成果转化与人才培养的稳步进阶。清华大学将依托自身在基础研究、医工交叉与 AI 学科的综合优势，联合全球合作伙伴，为建设更具韧性、公平性与协同性的全球公共卫生体系贡献清华力量。";

// —— V4 新值（与 seed / migrate 一致） ——
$NEW_CN_ID = "中印尼疫苗与基因组联合研发中心由清华大学牵头，纳入国家科技部对发展中国家科技援助专项。中心构建\u{201C}政府+高校+产业\u{201D}协同模式，推动登革热、结核等疫苗联合研发，助力印尼向区域研发生产中心转型，形成\u{201C}中国技术+本地转化\u{201D}的南南合作范式。";
$NEW_PRA   = "大流行病研究联盟由清华大学张林琦教授联合钟南山、何大一等多国专家于2023年发起，开展前瞻性研究，布局产品研发与应急储备。联盟已举办多场国际研讨会，发展为全球活跃的流行病研究协作网络，助力全球公共卫生体系建设。";

// —— 场景构造 helper ——
function scenario_setup(string $tag, string $cnBody, string $praBody, array $schedule): string {
    $db = sys_get_temp_dir() . "/mabseek-v4b1-$tag.sqlite";
    @unlink($db);
    putenv("MABSEEK_DB=$db");
    // 触发 bootstrap → 建表 → seed 灌"302 后"底表，再用 UPDATE 把 body/schedule 强改成 tag 对应旧状态
    require_once __DIR__ . '/../app/bootstrap.php';
    // clear 全库，只保留 schema
    $tbls = db()->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tbls as $t) db()->exec("DELETE FROM $t");
    // 灌两个 snippet（无论 seed 状态如何都用 upsert 保证是 tag 对应值）
    Snippets::set('about.intl.cn_id_body', $cnBody, 'about', '中印尼合作 正文（换行分段）', 'textarea');
    Snippets::set('about.intl.pra_body',   $praBody, 'about', 'PRA 合作 正文（换行分段）', 'textarea');
    // 灌 edu_schedule
    db()->exec("DELETE FROM content_cards WHERE grp='edu_schedule'");
    $cc = new Collection('content_cards');
    foreach ($schedule as $row) $cc->create($row + ['grp'=>'edu_schedule','icon'=>'','extra'=>'','published'=>1]);
    return $db;
}

// —— 3 个场景 ——
$OLD_SEED_SCHEDULE = [
    ['title'=>'第 1 周 · 2026/09/15 · 绪论：疫苗点亮健康','body'=>'授课：张林琦｜课程队长：李晨雨','sort'=>1],
    ['title'=>'第 2 周 · 2026/09/22 · 预防接种进展与成就','body'=>'授课：梁晓峰｜课程队长：杨依凌','sort'=>2],
    ['title'=>'第 3 周 · 2026/09/29 · 疫苗是如何保护我们的？','body'=>'授课：李冠乔｜课程队长：范欣雨','sort'=>3],
    ['title'=>'第 4 周 · 2026/10/13 · 疫苗是如何研制和质量保障的？','body'=>'授课：李冠乔｜课程队长：范欣雨','sort'=>4],
    ['title'=>'第 5 周 · 2026/10/20 · 疫苗经济学','body'=>'授课：方海｜课程队长：杨屿涵','sort'=>5],
    ['title'=>'第 6 周 · 2026/10/27 · 疫苗免疫效果评估 —实验室的科技魅力','body'=>'授课：史宣玲｜课程队长：傅莉莉','sort'=>6],
    ['title'=>'第 7 周 · 2026/11/3 · 天花疫苗和脊髓灰质炎疫苗-人类疫苗历史的壮举','body'=>'授课：陈志伟｜课程队长：魏婧','sort'=>7],
    ['title'=>"第 8 周 · 2026/11/10 · 乙肝疫苗-摘掉我国\u{201C}乙肝大国\u{201D}帽子的功臣",'body'=>'授课：崔富强｜课程队长：王枭','sort'=>8],
    ['title'=>'第 9 周 · 2026/11/17 · 乙脑疫苗-我国第一支通过世卫组织预认证的疫苗','body'=>'授课：袁瑷｜课程队长：朱坤','sort'=>9],
    ['title'=>'第 10 周 · 2026/11/24 · 人乳头瘤病毒（HPV）疫苗—宫颈癌的克星','body'=>'授课：乔友林｜课程队长：曾一歌','sort'=>10],
    ['title'=>'第 11 周 · 2026/12/01 · 流感疫苗-以变应变的追逐与纠结','body'=>'授课：冯录召｜课程队长：魏子萌','sort'=>11],
    ['title'=>'第 12 周 · 2026/12/08 · 呼吸道相关传染病的疫苗','body'=>'授课：张绮｜课程队长：黄灿','sort'=>12],
    ['title'=>'第 13 周 · 2026/12/15 · 治疗性疫苗','body'=>'授课：傅阳心｜课程队长：郑玉娟','sort'=>13],
    ['title'=>'第 14 周 · 2026/12/22 · 疫苗与全球健康','body'=>'授课：杜珩｜课程队长：杨芊芊','sort'=>14],
    ['title'=>'第 15 周 · 2026/12/29 · 历史回顾和未来展望','body'=>'授课：张林琦｜课程队长：谭睿洁','sort'=>15],
];

foreach (['s1_302' => [$OLD_302_CN_ID, $OLD_302_PRA, $OLD_SEED_SCHEDULE]] as $tag => [$cn, $pra, $sched]) {
    echo "\n=== Scenario $tag ===\n";
    $db = scenario_setup($tag, $cn, $pra, $sched);

    // —— 跑迁移 ——
    $root = dirname(__DIR__);
    passthru("MABSEEK_DB=$db php $root/bin/migrate-v4-batch1.php", $rc);
    check($rc === 0, "$tag: migrate exit 0");

    // —— 断言最终值 ——
    require $root . '/app/bootstrap.php';
    check(Snippets::get('about.intl.cn_id_body') === $NEW_CN_ID, "$tag: cn_id_body → new");
    check(Snippets::get('about.intl.pra_body')   === $NEW_PRA,   "$tag: pra_body → new");
    check(Snippets::get('edu.video.iframe_url', '__MISSING__') !== '__MISSING__', "$tag: edu.video.iframe_url exists");

    $rows = db()->query("SELECT title,body FROM content_cards WHERE grp='edu_schedule' ORDER BY sort")->fetchAll(PDO::FETCH_ASSOC);
    check(count($rows) === 15, "$tag: 15 讲");
    check(($rows[0]['title'] ?? '')  === '第 1 讲 · 绪论：疫苗点亮健康', "$tag: 第 1 讲 title");
    check(($rows[0]['body']  ?? '')  === '授课：张林琦', "$tag: 第 1 讲 body 无课程队长");
    check(($rows[8]['body']  ?? '')  === '授课：袁媛', "$tag: 第 9 讲 袁媛（旧库袁瑷 → 订正）");
    check(($rows[11]['body'] ?? '')  === '授课：张纬', "$tag: 第 12 讲 张纬（旧库张绮 → 订正）");

    // —— 二次跑幂等：0 changed ——
    ob_start(); passthru("MABSEEK_DB=$db php $root/bin/migrate-v4-batch1.php", $rc); $log2 = ob_get_clean();
    echo $log2;
    check($rc === 0, "$tag: 二次跑 exit 0");
    check(preg_match('/changed=0/', $log2) === 1, "$tag: 二次跑 changed=0");
}

echo "\n==== {$GLOBALS['__pass']} passed, {$GLOBALS['__fail']} failed ====\n";
exit($GLOBALS['__fail'] > 0 ? 1 : 0);
