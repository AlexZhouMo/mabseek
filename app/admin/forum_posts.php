<?php
$cfg = [
    'table' => 'forum_posts',
    'title' => '论坛帖子',
    'fields' => [
        ['name'=>'category', 'label'=>'分类', 'type'=>'select', 'options'=>[
            'pit'=>'# 实验踩坑', 'proto'=>'# Protocol 分享', 'paper'=>'# 文献精读', 'bio'=>'# 生信工具', 'job'=>'# 求职招聘',
        ], 'required'=>true],
        ['name'=>'cover_type', 'label'=>'封面类型', 'type'=>'select', 'options'=>['img'=>'图片', 'grad'=>'渐变色块(emoji)'], 'required'=>true],
        ['name'=>'cover_ref', 'label'=>'封面内容(图片路径 或 emoji)', 'type'=>'text', 'required'=>true],
        ['name'=>'cover_variant', 'label'=>'封面样式', 'type'=>'select', 'options'=>[''=>'默认', 'g2'=>'样式2', 'g3'=>'样式3']],
        ['name'=>'toptag', 'label'=>'角标(可选)', 'type'=>'text'],
        ['name'=>'title', 'label'=>'标题', 'type'=>'text', 'required'=>true],
        ['name'=>'tags', 'label'=>'标签(逗号分隔)', 'type'=>'text'],
        ['name'=>'author_name', 'label'=>'作者名', 'type'=>'text', 'required'=>true],
        ['name'=>'author_avatar_char', 'label'=>'作者头像字', 'type'=>'text', 'required'=>true],
        ['name'=>'author_avatar_style', 'label'=>'头像样式(CSS，可选)', 'type'=>'text'],
        ['name'=>'likes', 'label'=>'点赞(如 ❤️ 328)', 'type'=>'text'],
        ['name'=>'sort', 'label'=>'排序', 'type'=>'text'],
        ['name'=>'published', 'label'=>'发布', 'type'=>'checkbox'],
    ],
];
admin_crud($cfg);
