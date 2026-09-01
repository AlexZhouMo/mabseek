<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/captcha.php';
$_SESSION = [];

captcha_store('AB12');
check(captcha_answer_ok('ab12') === true, '验证码正确(大小写不敏感)');

captcha_store('AB12');
check(captcha_answer_ok('zzzz') === false, '验证码错误');

captcha_store('AB12');
check(captcha_answer_ok('ab12') === true && captcha_answer_ok('ab12') === false, '一次性：用后即失效');

$_SESSION['__captcha'] = ['hash' => hash('sha256', 'ab12'), 'exp' => 1];  // 远古过期
check(captcha_answer_ok('ab12') === false, '过期验证码被拒');
