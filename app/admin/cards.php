<?php
$cfg = [
    'table' => 'content_cards',
    'title' => '内容卡片',
    'fields' => [
        ['name'=>'grp', 'label'=>'分组', 'type'=>'select', 'options'=>[
            'home_pain'=>'首页·四大痛点',
            'about_achievement'=>'关于·成果卡',
            'forum_line'=>'论坛·内容线',
            'agent_capability'=>'Agent·能力卡',
            'agent_matrix'=>'Agent·智能体矩阵',
            'edu_info'=>'教育·课程/育人理念',
            'edu_lecture'=>'教育·讲座沙龙',
            'edu_grow'=>'教育·成长资源',
        ], 'required'=>true],
        ['name'=>'icon', 'label'=>'图标(emoji，可选)', 'type'=>'text'],
        ['name'=>'title', 'label'=>'标题', 'type'=>'text', 'required'=>true],
        ['name'=>'body', 'label'=>'正文(可选)', 'type'=>'textarea'],
        ['name'=>'extra', 'label'=>'结构化字段 JSON(如 fix / items / ico_style，可选)', 'type'=>'textarea'],
        ['name'=>'sort', 'label'=>'排序', 'type'=>'text'],
        ['name'=>'published', 'label'=>'发布', 'type'=>'checkbox'],
    ],
];
admin_crud($cfg);
