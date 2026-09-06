<?php
require_once __DIR__ . '/../edu_reviews.php';   // edu_review_derive()

$cfg = [
    'table' => 'edu_reviews',
    'title' => '教育 · 往期回顾',
    'fields' => [
        ['name'=>'title',    'label'=>'标题',                        'type'=>'text',     'required'=>true],
        ['name'=>'cover',    'label'=>'缩略图(可选，留空取正文首图)', 'type'=>'image'],
        ['name'=>'summary',  'label'=>'摘要(可选，留空取正文前 30 字)', 'type'=>'textarea'],
        ['name'=>'body',     'label'=>'图文正文',                    'type'=>'richtext'],
        ['name'=>'sort',     'label'=>'排序',                        'type'=>'text'],
        ['name'=>'published','label'=>'发布',                        'type'=>'checkbox'],
    ],
    'derive' => 'edu_review_derive',   // 净化后、写库前派生 cover / summary
];
admin_crud($cfg);
