<?php
$cfg = [
    'table' => 'news',
    'title' => '新闻与活动',
    'fields' => [
        ['name'=>'date_day', 'label'=>'日(如 08)',        'type'=>'text',     'required'=>true],
        ['name'=>'date_ym',  'label'=>'年月(如 2026·08)', 'type'=>'text',     'required'=>true],
        ['name'=>'category', 'label'=>'分类',              'type'=>'select',   'options'=>['res'=>'科研类','edu'=>'育人类','daily'=>'日常活动'], 'required'=>true],
        ['name'=>'title',    'label'=>'标题',              'type'=>'text',     'required'=>true],
        ['name'=>'summary',  'label'=>'摘要',              'type'=>'textarea', 'required'=>true],
        ['name'=>'image',    'label'=>'配图(可选)',        'type'=>'image'],
        ['name'=>'sort',     'label'=>'排序',              'type'=>'text'],
        ['name'=>'published','label'=>'发布',              'type'=>'checkbox'],
    ],
];
admin_crud($cfg);
