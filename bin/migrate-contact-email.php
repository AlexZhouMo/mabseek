<?php
declare(strict_types=1);
/**
 * migrate-contact-email.php —— 把站点联系邮箱从旧值订正为新值
 *
 * 背景：联系邮箱存于 snippets 表（contact.email、about.contact.sub 等），随页面动态渲染。
 *       生产库 data/ 部署时被保留、seed “有数据即跳过”，故 seed.php 里改新值对存量库无效，
 *       需本脚本就地订正历史记录。
 *
 * 幂等 & 安全：
 *   - 仅把值中仍包含旧邮箱的 snippet 替换为新邮箱（定向替换，不动其它文案）
 *   - 已是新邮箱、或管理员在后台改成别的值的记录，都不受影响
 *   - 可反复运行：无旧值时 0 改动
 *
 * 用法：php bin/migrate-contact-email.php
 */
require_once __DIR__ . '/../app/bootstrap.php';

const OLD_EMAIL = 'contact@mabseek.org';
const NEW_EMAIL = 'm13673741782@163.com';

$pdo = db();

echo "Migrating contact email {" . OLD_EMAIL . "} -> {" . NEW_EMAIL . "}...\n";

$rows = $pdo->query(
    "SELECT id, skey, value FROM snippets WHERE value LIKE '%" . OLD_EMAIL . "%'"
)->fetchAll(PDO::FETCH_ASSOC);

$updated = 0;
$upd = $pdo->prepare(
    "UPDATE snippets SET value = :v, updated_at = :t WHERE id = :id"
);
foreach ($rows as $row) {
    $newVal = str_replace(OLD_EMAIL, NEW_EMAIL, (string)$row['value']);
    $upd->execute([
        ':v'  => $newVal,
        ':t'  => date('Y-m-d\TH:i:sP'),
        ':id' => $row['id'],
    ]);
    $updated++;
    echo "  ~ snippets.{$row['skey']}#{$row['id']}\n";
}

echo "  = done: {$updated} snippet(s) updated\n";
