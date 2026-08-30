<?php
$cfg = [
    'table' => 'team_members',
    'title' => '团队成员',
    'fields' => [
        ['name'=>'name', 'label'=>'姓名', 'type'=>'text', 'required'=>true],
        ['name'=>'affiliation', 'label'=>'单位/头衔', 'type'=>'text', 'required'=>true],
        ['name'=>'direction', 'label'=>'研究方向简介', 'type'=>'textarea', 'required'=>true],
        ['name'=>'role_label', 'label'=>'角色标签', 'type'=>'text', 'required'=>true],
        ['name'=>'role_type', 'label'=>'角色类型', 'type'=>'select', 'options'=>['science'=>'科研方向', 'ai'=>'AI 方向'], 'required'=>true],
        ['name'=>'avatar_char', 'label'=>'头像字', 'type'=>'text', 'required'=>true],
        ['name'=>'avatar_variant', 'label'=>'头像样式', 'type'=>'select', 'options'=>[''=>'默认', 'g2'=>'样式2']],
        ['name'=>'sort', 'label'=>'排序', 'type'=>'text'],
        ['name'=>'published', 'label'=>'发布', 'type'=>'checkbox'],
    ],
];
admin_crud($cfg);
