<?php
declare(strict_types=1);
/**
 * migrate-home-news-title.php —— 首页近况标题「新闻 · 发表 · 活动」订正为「新闻 · 活动」
 *
 * 幂等 & 安全：仅当值仍为旧文案时替换，管理员改过的不动；反复运行无旧值即 0 改动。
 *
 * 用法：php bin/migrate-home-news-title.php
 */
require_once __DIR__ . '/../app/bootstrap.php';

$pdo = db();
$now = date('Y-m-d\TH:i:sP');

$upd = $pdo->prepare("UPDATE snippets SET value = :new, updated_at = :t WHERE skey = 'home.news.title' AND value = :old");
$upd->execute([':new' => '新闻 · 活动', ':t' => $now, ':old' => '新闻 · 发表 · 活动']);
echo "  home.news.title 订正: {$upd->rowCount()} 行\n";

echo "迁移完成。\n";
