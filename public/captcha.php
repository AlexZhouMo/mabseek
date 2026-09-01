<?php
require __DIR__ . '/../app/bootstrap.php';
$answer = captcha_generate();
captcha_store($answer);
captcha_render_png($answer);
