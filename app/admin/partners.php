<?php
$cfg = [
    'table' => 'partners',
    'title' => '合作伙伴',
    'fields' => [
        ['name'=>'name', 'label'=>'名称', 'type'=>'text', 'required'=>true],
        ['name'=>'mark', 'label'=>'标识字(如 清)', 'type'=>'text', 'required'=>true],
        ['name'=>'sub', 'label'=>'副标题(可选)', 'type'=>'text'],
        ['name'=>'logo_image', 'label'=>'Logo 图片(可选)', 'type'=>'image'],
        ['name'=>'demo', 'label'=>'悬浮提示文案(可选)', 'type'=>'text'],
        ['name'=>'sort', 'label'=>'排序', 'type'=>'text'],
        ['name'=>'published', 'label'=>'发布', 'type'=>'checkbox'],
    ],
];
admin_crud($cfg);
