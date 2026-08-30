<?php
putenv('MABSEEK_DB=:memory:');
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../app/repositories/Collection.php';
require_once __DIR__ . '/../app/repositories/Snippets.php';

$news = new Collection('news');
$id = $news->create(['date_day'=>'08','date_ym'=>'2026·08','category'=>'res','title'=>'T','summary'=>'S','image'=>null,'sort'=>1,'published'=>1]);
check($id > 0, 'create returns id');
check($news->find($id)['title'] === 'T', 'find returns row');
$news->update($id, ['title'=>'T2']);
check($news->find($id)['title'] === 'T2', 'update persists');
check(count($news->published()) === 1, 'published lists 1');
$news->update($id, ['published'=>0]);
check(count($news->published()) === 0, 'unpublished excluded');
$news->delete($id);
check($news->find($id) === null, 'delete removes');

Snippets::set('home.hero.title','Hello','home','标题');
check(Snippets::get('home.hero.title') === 'Hello', 'snippet get');
check(Snippets::get('missing','def') === 'def', 'snippet default');
