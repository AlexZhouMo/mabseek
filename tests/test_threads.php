<?php
putenv('MABSEEK_DB=:memory:');
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/threads.php';
db();

check(thread_validate_title('一个标题') === true, 'title 合法');
check(thread_validate_title('') === false, 'title 空被拒');
check(thread_validate_title('   ') === false, 'title 纯空白被拒');
check(thread_validate_title(str_repeat('字', 121)) === false, 'title 过长被拒');

check(thread_validate_body('正文内容') === true, 'body 合法');
check(thread_validate_body('') === false, 'body 空被拒');
check(thread_validate_body(str_repeat('字', 5001)) === false, 'body 过长被拒');

check(thread_valid_category('pit') === true, 'category 合法');
check(thread_valid_category('nope') === false, 'category 非法被拒');
