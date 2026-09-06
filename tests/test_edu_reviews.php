<?php
putenv('MABSEEK_DB=:memory:');
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/edu_reviews.php';

// ── 提取正文首图 ──
$body = '<p>前言</p><img src="' . UPLOAD_URL . '/a.png"><img src="' . UPLOAD_URL . '/b.png">';
check(edu_review_first_image($body) === UPLOAD_URL . '/a.png', '提取第一张图 src');
check(edu_review_first_image('<p>无图正文</p>') === '', '无图返回空');
check(edu_review_first_image("<img src='" . UPLOAD_URL . "/c.png'>") === UPLOAD_URL . '/c.png', '单引号 src 也能提取');

// ── 摘要派生：前 30 字 + 省略号 ──
$long = '<p>' . str_repeat('字', 50) . '</p>';
check(mb_strlen(edu_review_summary($long)) === 31, '长文摘要=30字+省略号(31字符)');
check(mb_substr(edu_review_summary($long), -1) === '…', '长文摘要末尾是省略号');
$short = '<p>短短的正文</p>';
check(edu_review_summary($short) === '短短的正文', '短文摘要=原文、不加省略号');
check(edu_review_summary('<p>   </p>') === '', '空白正文摘要为空');
check(edu_review_summary('<p>abc</p><h2>def</h2>') === 'abcdef', '摘要跨标签取纯文本');

// ── derive 整体：仅在字段为空时派生 ──
$d1 = edu_review_derive(['title'=>'t','cover'=>'','summary'=>'','body'=>$body]);
check($d1['cover'] === UPLOAD_URL . '/a.png', 'derive: cover 空→首图');
check($d1['summary'] === '前言', 'derive: summary 空→纯文本');

$d2 = edu_review_derive(['title'=>'t','cover'=>UPLOAD_URL . '/keep.png','summary'=>'手写摘要','body'=>$body]);
check($d2['cover'] === UPLOAD_URL . '/keep.png', 'derive: cover 已填→保留');
check($d2['summary'] === '手写摘要', 'derive: summary 已填→保留');

$d3 = edu_review_derive(['title'=>'t','cover'=>'','summary'=>'','body'=>'<p>无图纯文本正文</p>']);
check($d3['cover'] === '', 'derive: 无图→cover 留空');
check($d3['summary'] === '无图纯文本正文', 'derive: summary 派生自纯文本');
