<?php
$cfg = [
    'table' => 'forum_hot',
    'title' => '论坛热榜',
    'fields' => [
        ['name'=>'list', 'label'=>'榜单', 'type'=>'select', 'options'=>['day'=>'每日榜', 'week'=>'每周榜'], 'required'=>true],
        ['name'=>'rank', 'label'=>'排名(数字)', 'type'=>'text', 'required'=>true],
        ['name'=>'title', 'label'=>'标题', 'type'=>'text', 'required'=>true],
        ['name'=>'category', 'label'=>'分类(如 # 文献精读)', 'type'=>'text', 'required'=>true],
        ['name'=>'heat', 'label'=>'热度(如 🔥 1.2k)', 'type'=>'text', 'required'=>true],
        ['name'=>'sort', 'label'=>'排序', 'type'=>'text'],
        ['name'=>'published', 'label'=>'发布', 'type'=>'checkbox'],
    ],
];
admin_crud($cfg);
