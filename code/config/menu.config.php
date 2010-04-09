<?php
//$GLOBALS['config']['menu'] = 'only_for_test';
$menu = array();

$menu['dashboard']['index']['text'] = '近况';
$menu['dashboard']['index']['tab'] = true;

$menu['default']['info']['text'] = '系统消息';

$menu['user']['login']['text'] = '登录';
$menu['user']['logout']['text'] = '退出';

$menu['user']['settings']['text'] = '设置';
$menu['user']['permission']['text'] = '同事';

$menu['message']['index']['text'] = '消息';

$GLOBALS['config']['menu'] = $menu;

$tab = array();

$tab[] = array( 'm'=>'dashboard' , 'a'=>'' , 't'=>'近况' );
$tab[] = array( 'm'=>'doc' , 'a'=>'' , 't'=>'文档' );
$tab[] = array( 'm'=>'todo' , 'a'=>'' , 't'=>'TODO' );
$tab[] = array( 'm'=>'report' , 'a'=>'' , 't'=>'报告' );
$tab[] = array( 'm'=>'project' , 'a'=>'' , 't'=>'项目' );


$GLOBALS['config']['tab'] = $tab;
?>