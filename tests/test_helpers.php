<?php
require_once __DIR__ . '/../app/helpers.php';
check(e('<a>&"') === '&lt;a&gt;&amp;&quot;', 'e() escapes html + quotes');
check(e(null) === '', 'e(null) === empty string');
check(nl2br_e("a\nb") === "a<br />\nb", 'nl2br_e escapes then breaks');
echo iso_now() . "\n";
check(preg_match('/^\d{4}-\d\d-\d\dT/', iso_now()) === 1, 'iso_now ISO8601');
