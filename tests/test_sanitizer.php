<?php
require_once __DIR__ . '/../app/html_sanitizer.php';

// 危险容器整体删除（script）
$r = sanitize_html('<p>hi</p><script>alert(1)</script>');
check(strpos($r, 'script') === false, 'script 标签及内容被删除');
check(strpos($r, 'alert') === false, 'script 内文本一并删除');
check(strpos($r, '<p>hi</p>') !== false, '白名单 p 保留');

// 危险容器整体删除（style）
$r = sanitize_html('<p>x</p><style>body{}</style>');
check(strpos($r, 'style') === false, 'style 标签被删除');
check(strpos($r, 'body{}') === false, 'style 内文本一并删除');
check(strpos($r, '<p>x</p>') !== false, 'style 旁的白名单 p 保留');

// 事件属性剥离 + 非站内图删除
$r = sanitize_html('<img src="http://evil/x.png" onerror="alert(1)">');
check(strpos($r, 'onerror') === false, 'onerror 事件属性被剥离');
check(strpos($r, '<img') === false, '非站内 img 整体删除');

// 站内上传图保留 src/alt
$r = sanitize_html('<img src="' . UPLOAD_URL . '/a.jpg" alt="图" width="9">');
check(strpos($r, UPLOAD_URL . '/a.jpg') !== false, '站内 img 保留');
check(strpos($r, 'alt="图"') !== false, 'img alt 保留');
check(strpos($r, 'width') === false, 'img 其它属性剥离');

// 路径穿越图整体删除
$r = sanitize_html('<img src="' . UPLOAD_URL . '/../../secret.png">');
check(strpos($r, '<img') === false, '路径穿越 img 整体删除');

// javascript: 链接去 href、保留文字
$r = sanitize_html('<a href="javascript:alert(1)">x</a>');
check(strpos($r, 'javascript') === false, 'javascript: href 被删除');
check(strpos($r, 'x') !== false, '链接文字保留');

// http(s) 链接保留 href 并补 rel
$r = sanitize_html('<a href="https://a.com" title="t">x</a>');
check(strpos($r, 'href="https://a.com"') !== false, 'https href 保留');
check(strpos($r, 'noopener') !== false, 'a 补 rel=noopener');
check(strpos($r, 'title') === false, 'a 其它属性剥离');

// 非白名单元素解包（保留文字）
$r = sanitize_html('<div onclick="x"><strong>粗</strong>u</div>');
check(strpos($r, 'div') === false, 'div 被解包');
check(strpos($r, 'onclick') === false, 'onclick 不残留');
check(strpos($r, '<strong>粗</strong>') !== false, '内层白名单 strong 保留');
check(strpos($r, '</strong>u') !== false, 'div 内文本保留');

// 中文往返不乱码
$r = sanitize_html('<p>抗体求索</p>');
check(strpos($r, '抗体求索') !== false, '中文不乱码');

// 空输入
check(sanitize_html('') === '', '空输入返回空');
check(sanitize_html("  \n ") === '', '纯空白返回空');
