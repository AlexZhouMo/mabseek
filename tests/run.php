<?php
declare(strict_types=1);
// 用法: php tests/run.php  —— 依次 require 所有 test_*.php，统计断言
error_reporting(E_ALL);
ini_set('display_errors', '1');
$GLOBALS['__pass'] = 0; $GLOBALS['__fail'] = 0;
function check(bool $cond, string $msg): void {
    if ($cond) { $GLOBALS['__pass']++; echo "  ✓ $msg\n"; }
    else       { $GLOBALS['__fail']++; echo "  ✗ FAIL: $msg\n"; }
}
foreach (glob(__DIR__ . '/test_*.php') as $f) {
    echo "\n# " . basename($f) . "\n";
    require $f;
}
echo "\n==== {$GLOBALS['__pass']} passed, {$GLOBALS['__fail']} failed ====\n";
exit($GLOBALS['__fail'] > 0 ? 1 : 0);
